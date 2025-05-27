<?php
// Подключаем файл проверки сессии
require_once 'session_check.php';

// Перенаправляем неавторизованных пользователей на страницу входа
redirectIfNotLoggedIn();

// Подключаем файл с подключением к БД
require_once 'connect_db.php';

// Инициализируем переменные
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];
$userLocation = '';
$userBio = '';
$profilePhoto = '';
$errors = [];
$success = '';

// Получаем данные пользователя из БД
$stmt = $savienojums->prepare("SELECT Lietotajvards, E_pasts, ProfilaAttels FROM bookswap_users WHERE LietotajsID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $userName = $user['Lietotajvards'];
    $userEmail = $user['E_pasts'];
    $profilePhoto = $user['ProfilaAttels'];
}
$stmt->close();

// Разделяем имя пользователя на имя и фамилию
$nameParts = explode(' ', $userName, 2);
$firstName = $nameParts[0];
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Получаем данные из формы
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Валидация данных
    if (empty($firstName)) {
        $errors[] = 'Vārds ir obligāts lauks';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Uzvārds ir obligāts lauks';
    }
    
    // Если нет ошибок, обновляем профиль
    if (empty($errors)) {
        // Полное имя пользователя
        $fullName = $firstName . ' ' . $lastName;
        
        // Обновляем данные в БД
        $stmt = $savienojums->prepare("UPDATE bookswap_users SET Lietotajvards = ? WHERE LietotajsID = ?");
        $stmt->bind_param("si", $fullName, $userId);
        
        if ($stmt->execute()) {
            // Обновляем данные сессии
            $_SESSION['user_name'] = $fullName;
            
            // Устанавливаем сообщение об успехе
            $success = 'Profils veiksmīgi atjaunināts!';
            
            // Обновляем переменные для отображения
            $userName = $fullName;
            $userLocation = $location;
            $userBio = $bio;
        } else {
            $errors[] = 'Kļūda atjauninot profilu: ' . $savienojums->error;
        }
        $stmt->close();
    }
}

// Обработка изменения пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    // Получаем данные из формы
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';
    
    // Валидация данных
    if (empty($currentPassword)) {
        $errors[] = 'Pašreizējā parole ir obligāta';
    }
    
    if (empty($newPassword)) {
        $errors[] = 'Jaunā parole ir obligāta';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
    }
    
    if ($newPassword !== $confirmNewPassword) {
        $errors[] = 'Jaunās paroles nesakrīt';
    }
    
    // Если нет ошибок, проверяем текущий пароль и обновляем
    if (empty($errors)) {
        // Получаем текущий хешированный пароль из БД
        $stmt = $savienojums->prepare("SELECT Parole FROM bookswap_users WHERE LietotajsID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Проверяем текущий пароль
            if (password_verify($currentPassword, $user['Parole'])) {
                // Хешируем новый пароль
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Обновляем пароль в БД
                $updateStmt = $savienojums->prepare("UPDATE bookswap_users SET Parole = ? WHERE LietotajsID = ?");
                $updateStmt->bind_param("si", $hashedPassword, $userId);
                
                if ($updateStmt->execute()) {
                    $success = 'Parole veiksmīgi atjaunināta!';
                } else {
                    $errors[] = 'Kļūda atjauninot paroli: ' . $savienojums->error;
                }
                $updateStmt->close();
            } else {
                $errors[] = 'Pašreizējā parole ir nepareiza';
            }
        } else {
            $errors[] = 'Lietotājs nav atrasts';
        }
        $stmt->close();
    }
}

// Обработка загрузки фото профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    // Проверяем, был ли загружен файл
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profilePhoto'];
        
        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Atbalstītie failu formāti: JPEG, PNG, GIF';
        }
        
        // Проверяем размер файла (максимум 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Faila izmērs nedrīkst pārsniegt 2MB';
        }
        
        // Если нет ошибок, сохраняем файл
        if (empty($errors)) {
            // Создаем директорию для загрузок, если она не существует
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Генерируем уникальное имя файла
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            // Перемещаем загруженный файл
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Обновляем путь к фото в БД
                $stmt = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = ? WHERE LietotajsID = ?");
                $stmt->bind_param("si", $filePath, $userId);
                
                if ($stmt->execute()) {
                    $profilePhoto = $filePath;
                    $success = 'Profila fotoattēls veiksmīgi atjaunināts!';
                } else {
                    $errors[] = 'Kļūda atjauninot profila fotoattēlu: ' . $savienojums->error;
                }
                $stmt->close();
            } else {
                $errors[] = 'Kļūda augšupielādējot failu';
            }
        }
    } else {
        $errors[] = 'Lūdzu, izvēlieties failu';
    }
}

// Обработка удаления фото профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_photo') {
    // Обновляем путь к фото в БД (устанавливаем NULL)
    $stmt = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = NULL WHERE LietotajsID = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        // Если у пользователя было фото, удаляем файл
        if (!empty($profilePhoto) && file_exists($profilePhoto)) {
            unlink($profilePhoto);
        }
        
        $profilePhoto = '';
        $success = 'Profila fotoattēls veiksmīgi noņemts!';
    } else {
        $errors[] = 'Kļūda noņemot profila fotoattēlu: ' . $savienojums->error;
    }
    $stmt->close();
}

// Обработка добавления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_book') {
    // Получаем данные из формы
    $bookTitle = trim($_POST['bookTitle'] ?? '');
    $bookAuthor = trim($_POST['bookAuthor'] ?? '');
    $bookGenre = trim($_POST['bookGenre'] ?? '');
    $bookLanguage = trim($_POST['bookLanguage'] ?? '');
    $bookYear = intval($_POST['bookYear'] ?? 0);
    $bookDescription = trim($_POST['bookDescription'] ?? '');
    
    // Валидация данных
    if (empty($bookTitle)) {
        $errors[] = 'Grāmatas nosaukums ir obligāts lauks';
    }
    
    if (empty($bookAuthor)) {
        $errors[] = 'Autors ir obligāts lauks';
    }
    
    if (empty($bookGenre)) {
        $errors[] = 'Žanrs ir obligāts lauks';
    }
    
    if (empty($bookLanguage)) {
        $errors[] = 'Valoda ir obligāts lauks';
    }
    
    if ($bookYear <= 0) {
        $errors[] = 'Izdošanas gads ir obligāts lauks';
    }
    
    // Если нет ошибок, добавляем книгу
    if (empty($errors)) {
        $bookImage = '';
        
        // Проверяем, был ли загружен файл изображения
        if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['bookImage'];
            
            // Проверяем тип файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = 'Atbalstītie failu formāti: JPEG, PNG, GIF';
            }
            
            // Проверяем размер файла (максимум 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Faila izmērs nedrīkst pārsniegt 2MB';
            }
            
            // Если нет ошибок, сохраняем файл
            if (empty($errors)) {
                try {
                    // Создаем директорию для загрузок, если она не существует
                    $uploadDir = 'uploads/book_images/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            throw new Exception('Nevar izveidot mapi: ' . $uploadDir);
                        }
                    }
                    
                    // Проверяем права на запись в директорию
                    if (!is_writable($uploadDir)) {
                        throw new Exception('Nav rakstīšanas tiesību mapē: ' . $uploadDir);
                    }
                    
                    // Генерируем уникальное имя файла
                    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = 'book_' . time() . '_' . uniqid() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    
                    // Перемещаем загруженный файл
                    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                        throw new Exception('Kļūda augšupielādējot failu. Kods: ' . $file['error']);
                    }
                    
                    $bookImage = $filePath;
                } catch (Exception $e) {
                    $errors[] = 'Kļūda: ' . $e->getMessage();
                }
            }
        }
        
        // Если нет ошибок, добавляем книгу в БД
        if (empty($errors)) {
            try {
                // Текущая дата и время
                $currentDate = date('Y-m-d H:i:s');
                
                // Статус книги по умолчанию - "Доступна"
                $status = 'Pieejama';
                
                // Добавляем книгу в БД - ИСПРАВЛЕНО: не указываем GramatasID в запросе
                $stmt = $savienojums->prepare("INSERT INTO bookswap_books (Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, PievienosanasDatums, Status, LietotajsID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception('Kļūda sagatavojot vaicājumu: ' . $savienojums->error);
                }
                
                $stmt->bind_param("ssssissssi", $bookTitle, $bookAuthor, $bookGenre, $bookLanguage, $bookYear, $bookDescription, $bookImage, $currentDate, $status, $userId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Kļūda izpildot vaicājumu: ' . $stmt->error);
                }
                
                $success = 'Grāmata veiksmīgi pievienota!';
                $stmt->close();
                
                // Очищаем форму после успешного добавления
                $bookTitle = '';
                $bookAuthor = '';
                $bookGenre = '';
                $bookLanguage = '';
                $bookYear = '';
                $bookDescription = '';
                
                // ИСПРАВЛЕНО: Добавляем перенаправление после успешного добавления книги
                // Сохраняем сообщение об успехе в сессии
                $_SESSION['success_message'] = 'Grāmata veiksmīgi pievienota!';
                
                // Перенаправляем на ту же страницу, но с GET-запросом
                header('Location: profile.php');
                exit();
                
            } catch (Exception $e) {
                $errors[] = 'Kļūda pievienojot grāmatu: ' . $e->getMessage();
                
                // Если произошла ошибка и файл был загружен, удаляем его
                if (!empty($bookImage) && file_exists($bookImage)) {
                    unlink($bookImage);
                }
            }
        }
    }
}

// Проверяем, есть ли сообщение об успехе в сессии
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    // Удаляем сообщение из сессии, чтобы оно не отображалось повторно
    unset($_SESSION['success_message']);
}

// Получаем книги пользователя
$userBooks = [];
try {
    $stmt = $savienojums->prepare("SELECT GramatasID, Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, Status FROM bookswap_books WHERE LietotajsID = ? ORDER BY PievienosanasDatums DESC");
    
    if (!$stmt) {
        throw new Exception('Kļūda sagatavojot vaicājumu: ' . $savienojums->error);
    }
    
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Kļūda izpildot vaicājumu: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    while ($book = $result->fetch_assoc()) {
        $userBooks[] = $book;
    }
    
    $stmt->close();
} catch (Exception $e) {
    $errors[] = 'Kļūda iegūstot grāmatas: ' . $e->getMessage();
}

// Обработка выхода из системы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Удаляем все данные сессии
    session_unset();
    session_destroy();
    
    // Удаляем cookie "запомнить меня", если она существует
    if (isset($_COOKIE['bookswap_remember'])) {
        setcookie('bookswap_remember', '', time() - 3600, '/');
    }
    
    // Перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jūsu profils | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="auth.css">
  <link rel="stylesheet" href="book.css">
  <style>
    /* Дополнительные стили для библиотеки пользователя */
    .hidden {
      display: none;
    }
    
    .add-book-section {
      margin-top: 20px;
      padding: 20px;
      background-color: var(--color-paper);
      border-radius: var(--radius-lg);
    }
    
    .form-section-title {
      margin-top: 30px;
      margin-bottom: 15px;
      border-bottom: 1px solid var(--color-paper);
      padding-bottom: 10px;
    }
    
    .user-books {
      margin-top: 20px;
    }
    
    .books-grid {
      display: grid;
      grid-template-columns: repeat(1, 1fr);
      gap: 20px;
      margin-top: 20px;
    }
    
    @media (min-width: 768px) {
      .books-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (min-width: 1024px) {
      .books-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    
    .book-card {
      background-color: var(--color-white);
      border-radius: var(--radius-lg);
      overflow: hidden;
      border: 1px solid var(--color-paper);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      display: flex;
      flex-direction: column;
    }
    
    .book-cover-container {
      height: 200px;
      overflow: hidden;
    }
    
    .book-cover {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .book-cover-fallback {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--color-paper);
      color: var(--color-leather);
    }
    
    .book-info {
      padding: 15px;
    }
    
    .book-title {
      font-weight: 600;
      margin-bottom: 5px;
      font-size: 1.1rem;
    }
    
    .book-author {
      color: var(--color-gray);
      margin-bottom: 10px;
      font-size: 0.9rem;
    }
    
    .book-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-bottom: 10px;
    }
    
    .book-tag {
      font-size: 0.7rem;
      padding: 2px 8px;
      background-color: var(--color-paper);
      border-radius: 20px;
    }
    
    .book-status {
      margin-top: 10px;
    }
    
    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .status-badge.pieejama {
      background-color: #e6f7e6;
      color: #2e7d32;
    }
    
    .status-badge.apmainīta {
      background-color: #e3f2fd;
      color: #1565c0;
    }
    
    .status-badge.dzēsta {
      background-color: #fbe9e7;
      color: #d32f2f;
    }
    
    .status-badge.defektīva {
      background-color: #fff8e1;
      color: #ff8f00;
    }
    
    .no-books-message {
      text-align: center;
      padding: 30px;
      color: var(--color-gray);
      background-color: var(--color-paper);
      border-radius: var(--radius-lg);
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <header class="navigation">
    <div class="container">
      <div class="nav-wrapper">
        <!-- Logo & Brand -->
        <a href="index.php" class="brand">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
          <h1 class="brand-name">BookSwap</h1>
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
          <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
          <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
          <form method="POST" action="profile.php" style="display: inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn-primary mobile-btn" id="logoutBtn">Izlogoties</button>
          </form>
        </nav>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
      </div>
      
      <!-- Mobile Menu (Hidden by default) -->
      <div class="mobile-menu" id="mobileMenu">
        <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
        <div class="mobile-actions">
          <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn-primary mobile-btn" id="logoutMobileBtn">Izlogoties</button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <main>
    <section class="profile-section">
      <div class="container">
        <div class="profile-header">
          <h1>Your Profile</h1>
        </div>
        
        <?php if (!empty($success)): ?>
          <div class="toast show">
            <div class="toast-content">
              <div class="toast-icon success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <p class="toast-message"><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button class="toast-close" onclick="this.parentElement.classList.remove('show')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
          <div class="auth-error active">
            <?php foreach ($errors as $error): ?>
              <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <div class="profile-content">
          <div class="profile-sidebar">
            <div class="profile-photo-container">
              <div class="profile-photo" id="profilePhotoPreview">
                <?php if (!empty($profilePhoto) && file_exists($profilePhoto)): ?>
                  <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profile Photo">
                <?php else: ?>
                  <div class="profile-photo-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  </div>
                <?php endif; ?>
              </div>
              <div class="profile-photo-actions">
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="upload_photo">
                  <label for="profilePhotoInput" class="btn-outline btn-small">Mainīt foto</label>
                  <input type="file" id="profilePhotoInput" name="profilePhoto" accept="image/*" class="hidden" onchange="this.form.submit()">
                </form>
                <form method="POST" action="profile.php">
                  <input type="hidden" name="action" value="remove_photo">
                  <button type="submit" id="removePhotoBtn" class="btn-text btn-small">Noņemt</button>
                </form>
              </div>
            </div>
            
            <div class="profile-stats">
              <div class="stat-item">
                <span class="stat-number"><?php echo count($userBooks); ?></span>
                <span class="stat-label">Izliktas grāmatas</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">8</span>
                <span class="stat-label">Maiņas</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">4.8</span>
                <span class="stat-label">Vērtējums</span>
              </div>
            </div>
          </div>
          
          <div class="profile-details">
            <form id="profileForm" method="POST" action="profile.php">
              <input type="hidden" name="action" value="update_profile">
              <div class="form-row">
                <div class="form-group">
                  <label for="firstName">Vārds</label>
                  <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Jūsu vārds" required value="<?php echo htmlspecialchars($firstName); ?>">
                </div>
                <div class="form-group">
                  <label for="lastName">Uzvārds</label>
                  <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Jūsu uzvārds" required value="<?php echo htmlspecialchars($lastName); ?>">
                </div>
              </div>
              <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" class="form-input" placeholder="Jūsu e-pasta adrese" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
              </div>
              <div class="form-group">
                <label for="location">Atrašanās vieta</label>
                <input type="text" id="location" name="location" class="form-input" placeholder="Pilsēta, Valsts" value="<?php echo htmlspecialchars($userLocation); ?>">
              </div>
              <div class="form-group">
                <label for="bio">Biogrāfija</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="4" placeholder="Pastāstiet mums par sevi un savām grāmatu interesēm..."><?php echo htmlspecialchars($userBio); ?></textarea>
              </div>
              <div class="form-group">
                <button type="submit" id="saveProfileBtn" class="btn-primary">Saglabāt izmaiņas</button>
              </div>
            </form>
            
            <div class="form-section-title">
              <h3>Kontu drošība</h3>
            </div>
            
            <button type="button" id="changePasswordBtn" class="btn-outline">Mainīt paroli</button>
            
            <div id="passwordChangeSection" class="password-change-section hidden">
              <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                  <label for="currentPassword">Pašreizējā parole</label>
                  <input type="password" id="currentPassword" name="currentPassword" class="form-input" placeholder="Ievadiet savu pašreizējo paroli">
                </div>
                <div class="form-group">
                  <label for="newPassword">Jaunā parole</label>
                  <input type="password" id="newPassword" name="newPassword" class="form-input" placeholder="Ievadiet savu jauno paroli">
                </div>
                <div class="form-group">
                  <label for="confirmNewPassword">Apstipriniet jauno paroli</label>
                  <input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-input" placeholder="Apstipriniet savu jauno paroli">
                </div>
                <div class="form-actions">
                  <button type="submit" id="savePasswordBtn" class="btn-primary">Atjaunot paroli</button>
                  <button type="button" id="cancelPasswordBtn" class="btn-outline">Atcelt</button>
                </div>
              </form>
            </div>
            
            <!-- Секция библиотеки пользователя -->
            <div class="form-section-title">
              <h3>Mana bibliotēka</h3>
            </div>
            
            <!-- Кнопка добавления книги -->
            <button type="button" id="addBookBtn" class="btn-outline">Pievienot grāmatu</button>
            
            <!-- Форма добавления книги (скрыта по умолчанию) -->
            <div id="addBookSection" class="add-book-section hidden">
              <form method="POST" action="profile.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_book">
                <div class="form-group">
                  <label for="bookTitle">Grāmatas nosaukums</label>
                  <input type="text" id="bookTitle" name="bookTitle" class="form-input" placeholder="Ievadiet grāmatas nosaukumu" required>
                </div>
                <div class="form-group">
                  <label for="bookAuthor">Autors</label>
                  <input type="text" id="bookAuthor" name="bookAuthor" class="form-input" placeholder="Ievadiet autora vārdu" required>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="bookGenre">Žanrs</label>
                    <input type="text" id="bookGenre" name="bookGenre" class="form-input" placeholder="Piemēram, Romāns, Detektīvs" required>
                  </div>
                  <div class="form-group">
                    <label for="bookLanguage">Valoda</label>
                    <input type="text" id="bookLanguage" name="bookLanguage" class="form-input" placeholder="Piemēram, Latviešu, Angļu" required>
                  </div>
                </div>
                <div class="form-group">
                  <label for="bookYear">Izdošanas gads</label>
                  <input type="number" id="bookYear" name="bookYear" class="form-input" placeholder="Piemēram, 2020" required>
                </div>
                <div class="form-group">
                  <label for="bookDescription">Apraksts</label>
                  <textarea id="bookDescription" name="bookDescription" class="form-textarea" rows="4" placeholder="Īss grāmatas apraksts..."></textarea>
                </div>
                <div class="form-group">
                  <label for="bookImage">Grāmatas vāka attēls (neobligāti)</label>
                  <input type="file" id="bookImage" name="bookImage" class="form-input" accept="image/*">
                </div>
                <div class="form-actions">
                  <button type="submit" id="saveBookBtn" class="btn-primary">Pievienot grāmatu</button>
                  <button type="button" id="cancelBookBtn" class="btn-outline">Atcelt</button>
                </div>
              </form>
            </div>
            
            <!-- Список книг пользователя -->
            <div class="user-books">
              <?php if (empty($userBooks)): ?>
                <p class="no-books-message">Jūsu bibliotēkā vēl nav grāmatu. Pievienojiet savu pirmo grāmatu!</p>
              <?php else: ?>
                <div class="books-grid">
                  <?php foreach ($userBooks as $book): ?>
                    <div class="book-card">
                      <div class="book-cover-container">
                        <?php if (!empty($book['Attels']) && file_exists($book['Attels'])): ?>
                          <img src="<?php echo htmlspecialchars($book['Attels']); ?>" alt="<?php echo htmlspecialchars($book['Nosaukums']); ?>" class="book-cover">
                        <?php else: ?>
                          <div class="book-cover-fallback">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                              <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($book['Nosaukums']); ?></h3>
                        <p class="book-author"><?php echo htmlspecialchars($book['Autors']); ?></p>
                        <div class="book-tags">
                          <span class="book-tag"><?php echo htmlspecialchars($book['Zanrs']); ?></span>
                          <span class="book-tag"><?php echo htmlspecialchars($book['Valoda']); ?></span>
                          <span class="book-tag"><?php echo htmlspecialchars($book['IzdosanasGads']); ?></span>
                        </div>
                        <div class="book-status">
                          <span class="status-badge <?php echo strtolower($book['Status']); ?>"><?php echo htmlspecialchars($book['Status']); ?></span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand Section -->
        <div class="footer-brand">
          <a href="index.php" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            <h2 class="brand-name">BookSwap</h2>
          </a>
          <p>Saistieties ar citiem lasītājiem un apmainieties ar grāmatām, kuras jūs mīlat.</p>
          <div class="social-links">
            <a href="#" class="social-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </a>
            <a href="#" class="social-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
            </a>
            <a href="#" class="social-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
            </a>
          </div>
        </div>
        
        <!-- Quick Links -->
        <div class="footer-links">
          <h3 class="footer-title">Ātrās saites</h3>
          <ul>
            <li><a href="browse.php">Pārlūkot grāmatas</a></li>
            <li><a href="how-it-works.php">Kā tas strādā</a></li>
            <li><a href="signup.php">Pievienoties BookSwap</a></li>
            <li><a href="login.php">Pieslēgties</a></li>
          </ul>
        </div>
        
        <!-- Help & Support -->
        <div class="footer-links">
          <h3 class="footer-title">Palīdzība un atbalsts</h3>
          <ul>
            <li><a href="faq.php">BUJ</a></li>
            <li><a href="contact-us.php">Sazināties ar mums</a></li>
            <li><a href="safety-tips.php">Drošības padomi</a></li>
            <li><a href="report-issue.php">Ziņot par problēmu</a></li>
          </ul>
        </div>
        
        <!-- Legal -->
        <div class="footer-links">
          <h3 class="footer-title">Juridiskā informācija</h3>
          <ul>
            <li><a href="terms.php">Pakalpojumu noteikumi</a></li>
            <li><a href="privacy-policy.php">Privātuma politika</a></li>
            <li><a href="cookies.php">Sīkfailu politika</a></li>
            <li><a href="gdpr.php">VDAR</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Setup password change section toggle
      const changePasswordBtn = document.getElementById('changePasswordBtn');
      const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
      const passwordChangeSection = document.getElementById('passwordChangeSection');
      
      if (changePasswordBtn && passwordChangeSection) {
        changePasswordBtn.addEventListener('click', function() {
          passwordChangeSection.classList.remove('hidden');
          this.classList.add('hidden');
        });
      }
      
      if (cancelPasswordBtn && passwordChangeSection && changePasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function() {
          passwordChangeSection.classList.add('hidden');
          changePasswordBtn.classList.remove('hidden');
          
          // Reset password fields
          document.getElementById('currentPassword').value = '';
          document.getElementById('newPassword').value = '';
          document.getElementById('confirmNewPassword').value = '';
        });
      }
      
      // Setup add book section toggle
      const addBookBtn = document.getElementById('addBookBtn');
      const cancelBookBtn = document.getElementById('cancelBookBtn');
      const addBookSection = document.getElementById('addBookSection');
      
      if (addBookBtn && addBookSection) {
        addBookBtn.addEventListener('click', function() {
          addBookSection.classList.remove('hidden');
          this.classList.add('hidden');
        });
      }
      
      if (cancelBookBtn && addBookSection && addBookBtn) {
        cancelBookBtn.addEventListener('click', function() {
          addBookSection.classList.add('hidden');
          addBookBtn.classList.remove('hidden');
          
          // Reset book form fields
          document.getElementById('bookTitle').value = '';
          document.getElementById('bookAuthor').value = '';
          document.getElementById('bookGenre').value = '';
          document.getElementById('bookLanguage').value = '';
          document.getElementById('bookYear').value = '';
          document.getElementById('bookDescription').value = '';
          document.getElementById('bookImage').value = '';
        });
      }
      
      // Auto-hide success message after 3 seconds
      const toast = document.querySelector('.toast.show');
      if (toast) {
        setTimeout(() => {
          toast.classList.remove('show');
        }, 3000);
      }
      
      // Set current year in footer
      document.getElementById('currentYear').textContent = new Date().getFullYear();
    });
  </script>
</body>
</html>
