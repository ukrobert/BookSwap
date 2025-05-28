<?php
// Подключаем файл проверки сессии
require_once 'session_check.php';

// Перенаправляем неавторизованных пользователей на страницу входа
redirectIfNotLoggedIn();

// Подключаем файл с подключением к БД
require_once 'connect_db.php';

// Инициализируем переменные
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? ''; // Добавляем проверку на существование
$userEmail = $_SESSION['user_email'] ?? ''; // Добавляем проверку на существование
$userLocation = '';
$userBio = '';
$profilePhoto = $_SESSION['user_profile_photo'] ?? ''; // Берем фото из сессии
$errors = [];
$success = '';

// Получаем данные пользователя из БД (для информации, которая не хранится в сессии или для актуализации)
$stmt = $savienojums->prepare("SELECT Lietotajvards, E_pasts, ProfilaAttels FROM bookswap_users WHERE LietotajsID = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Обновляем данные сессии, если они изменились в БД (кроме фото, оно уже в сессии)
        if ($userName !== $user['Lietotajvards']) {
             $_SESSION['user_name'] = $user['Lietotajvards'];
             $userName = $user['Lietotajvards'];
        }
        if ($userEmail !== $user['E_pasts']) {
            $_SESSION['user_email'] = $user['E_pasts'];
            $userEmail = $user['E_pasts'];
        }
        // Убедимся, что $profilePhoto соответствует тому, что в БД, если сессия устарела
        if ($profilePhoto !== $user['ProfilaAttels']) {
            $_SESSION['user_profile_photo'] = $user['ProfilaAttels'];
            $profilePhoto = $user['ProfilaAttels'];
        }
    }
    $stmt->close();
}


// Разделяем имя пользователя на имя и фамилию
$nameParts = explode(' ', $userName, 2);
$firstName = $nameParts[0];
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // Получаем данные из формы
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    // $location = trim($_POST['location'] ?? ''); // Эти поля не хранятся в users, нужны доп. таблицы
    // $bio = trim($_POST['bio'] ?? '');

    // Валидация данных
    if (empty($firstName)) {
        $errors[] = 'Vārds ir obligāts lauks';
    }
    
    // if (empty($lastName)) { // Фамилия может быть не обязательной
    //     $errors[] = 'Uzvārds ir obligāts lauks';
    // }
    
    // Если нет ошибок, обновляем профиль
    if (empty($errors)) {
        // Полное имя пользователя
        $fullName = $firstName . (!empty($lastName) ? ' ' . $lastName : '');
        
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
            // $userLocation = $location; // Сохранять если есть поле в БД
            // $userBio = $bio;          // Сохранять если есть поле в БД
        } else {
            $errors[] = 'Kļūda atjauninot profilu: ' . $savienojums->error;
        }
        $stmt->close();
    }
}

// Обработка изменения пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';
    
    if (empty($currentPassword)) $errors[] = 'Pašreizējā parole ir obligāta';
    if (empty($newPassword)) $errors[] = 'Jaunā parole ir obligāta';
    elseif (strlen($newPassword) < 8) $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
    if ($newPassword !== $confirmNewPassword) $errors[] = 'Jaunās paroles nesakrīt';
    
    if (empty($errors)) {
        $stmt = $savienojums->prepare("SELECT Parole FROM bookswap_users WHERE LietotajsID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($currentPassword, $user['Parole'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
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
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profilePhoto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) $errors[] = 'Atbalstītie failu formāti: JPEG, PNG, GIF';
        if ($file['size'] > 2 * 1024 * 1024) $errors[] = 'Faila izmērs nedrīkst pārsniegt 2MB';
        
        if (empty($errors)) {
            $uploadDir = 'uploads/profile_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Удаляем старое фото, если оно есть и не является стандартным
                if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false) {
                    unlink($profilePhoto);
                }

                $stmt = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = ? WHERE LietotajsID = ?");
                $stmt->bind_param("si", $filePath, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user_profile_photo'] = $filePath; // Обновляем фото в сессии
                    $profilePhoto = $filePath; // Обновляем локальную переменную для отображения
                    $success = 'Profila fotoattēls veiksmīgi atjaunināts!';
                } else {
                    $errors[] = 'Kļūda atjauninot profila fotoattēlu: ' . $savienojums->error;
                    if (file_exists($filePath)) unlink($filePath); // Удаляем загруженный файл, если обновление БД не удалось
                }
                $stmt->close();
            } else {
                $errors[] = 'Kļūda augšupielādējot failu';
            }
        }
    } else {
        $errors[] = 'Lūdzu, izvēlieties failu vai notika kļūda augšupielādes laikā.';
    }
}

// Обработка удаления фото профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_photo') {
    $stmt = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = NULL WHERE LietotajsID = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false) {
            unlink($profilePhoto);
        }
        $_SESSION['user_profile_photo'] = ''; // Обновляем фото в сессии
        $profilePhoto = ''; // Обновляем локальную переменную
        $success = 'Profila fotoattēls veiksmīgi noņemts!';
    } else {
        $errors[] = 'Kļūda noņemot profila fotoattēlu: ' . $savienojums->error;
    }
    $stmt->close();
}


// Обработка добавления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_book') {
    $bookTitle = trim($_POST['bookTitle'] ?? '');
    $bookAuthor = trim($_POST['bookAuthor'] ?? '');
    $bookGenre = trim($_POST['bookGenre'] ?? '');
    $bookLanguage = trim($_POST['bookLanguage'] ?? '');
    $bookYear = intval($_POST['bookYear'] ?? 0);
    $bookDescription = trim($_POST['bookDescription'] ?? '');
    
    if (empty($bookTitle)) $errors[] = 'Grāmatas nosaukums ir obligāts lauks';
    if (empty($bookAuthor)) $errors[] = 'Autors ir obligāts lauks';
    if (empty($bookGenre)) $errors[] = 'Žanrs ir obligāts lauks';
    if (empty($bookLanguage)) $errors[] = 'Valoda ir obligāts lauks';
    if ($bookYear <= 1000 || $bookYear > date("Y")) $errors[] = 'Nederīgs izdošanas gads'; // Примерная валидация года
    
    $bookImage = ''; // Путь к изображению книги
    if (empty($errors)) {
        if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['bookImage'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) $errors[] = 'Grāmatas attēlam atbalstītie formāti: JPEG, PNG, GIF';
            if ($file['size'] > 2 * 1024 * 1024) $errors[] = 'Grāmatas attēla izmērs nedrīkst pārsniegt 2MB';
            
            if (empty($errors)) {
                $uploadDir = 'uploads/book_images/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = 'book_' . $userId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $bookImage = $filePath;
                } else {
                    $errors[] = 'Kļūda augšupielādējot grāmatas attēlu.';
                }
            }
        } // Если файл не загружен или ошибка, $bookImage останется пустым, что допустимо

        if (empty($errors)) { // Добавляем книгу, даже если нет изображения
            $currentDate = date('Y-m-d H:i:s');
            $status = 'Pieejama';
            
            $stmt = $savienojums->prepare("INSERT INTO bookswap_books (Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, PievienosanasDatums, Status, LietotajsID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssissssi", $bookTitle, $bookAuthor, $bookGenre, $bookLanguage, $bookYear, $bookDescription, $bookImage, $currentDate, $status, $userId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Grāmata veiksmīgi pievienota!';
                header('Location: profile.php'); // Перенаправление для предотвращения повторной отправки формы
                exit();
            } else {
                $errors[] = 'Kļūda pievienojot grāmatu: ' . $stmt->error;
                if (!empty($bookImage) && file_exists($bookImage)) unlink($bookImage); // Удаляем загруженный файл, если не удалось добавить книгу
            }
            $stmt->close();
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$userBooks = [];
$stmt = $savienojums->prepare("SELECT GramatasID, Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Attels, Status FROM bookswap_books WHERE LietotajsID = ? ORDER BY PievienosanasDatums DESC");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($book = $result->fetch_assoc()) {
        $userBooks[] = $book;
    }
    $stmt->close();
} else {
    $errors[] = "Kļūda sagatavojot vaicājumu grāmatu sarakstam: " . $savienojums->error;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jūsu profils | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="auth.css"> <!-- Используем auth.css для общих стилей форм и профиля -->
  <link rel="stylesheet" href="book.css"> <!-- Стили для карточек книг -->
  <style>
    .hidden { display: none; }
    .add-book-section { margin-top: 20px; padding: 20px; background-color: var(--color-paper); border-radius: var(--radius-lg); }
    .form-section-title { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid var(--color-paper); padding-bottom: 10px; }
    .user-books { margin-top: 20px; }
    .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
    .book-card { background-color: var(--color-white); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-paper); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); display: flex; flex-direction: column; }
    .book-cover-container { height: 200px; overflow: hidden; background-color: var(--color-light-gray); /* Fallback for no image */ display: flex; align-items: center; justify-content: center; }
    .book-cover { width: 100%; height: 100%; object-fit: cover; }
    .book-cover-fallback svg { width: 50px; height: 50px; color: var(--color-gray); }
    .book-info { padding: 15px; }
    .book-title { font-weight: 600; margin-bottom: 5px; font-size: 1.1rem; color: var(--color-darkwood); }
    .book-author { color: var(--color-gray); margin-bottom: 10px; font-size: 0.9rem; }
    .book-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .book-tag { font-size: 0.7rem; padding: 2px 8px; background-color: var(--color-paper); border-radius: 20px; color: var(--color-darkwood); }
    .book-status { margin-top: 10px; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
    .status-badge.pieejama { background-color: #e6f7e6; color: #2e7d32; }
    .status-badge.apmainīta { background-color: #e3f2fd; color: #1565c0; }
    .status-badge.dzēsta { background-color: #fbe9e7; color: #d32f2f; }
    .status-badge.defektīva { background-color: #fff8e1; color: #ff8f00; }
    .no-books-message { text-align: center; padding: 30px; color: var(--color-gray); background-color: var(--color-light-gray); border-radius: var(--radius-lg); margin-top: 20px; }
    .profile-photo-actions label.btn-outline { /* Ensure "Mainīt foto" looks like a button */
        cursor: pointer;
        padding: var(--spacing-1) var(--spacing-3); /* Match btn-small */
        font-size: 0.75rem; /* Match btn-small */
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
                </nav>
                
                <!-- Desktop Actions -->
                <div class="desktop-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // $profilePicPath уже определена выше и берется из $_SESSION['user_profile_photo']
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePhoto) && file_exists($profilePhoto)): ?>
                                        <img src="<?php echo htmlspecialchars($profilePhoto); ?>?t=<?php echo time(); // Cache busting ?>" alt="Profils">
                                    <?php else: ?>
                                        <div class="profile-button-placeholder-header">
                                            <?php echo htmlspecialchars($userNameInitial); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <form method="POST" action="logout.php" style="display: inline;">
                                <button type="submit" class="btn btn-outline">Izlogoties</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Pieslēgties</a>
                        <a href="signup.php" class="btn btn-primary">Reģistrēties</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>
            
            <!-- Mobile Menu (Hidden by default) -->
            <div class="mobile-menu" id="mobileMenu">
                <a href="browse.php" class="mobile-nav-link">Pārlūkot grāmatas</a>
                <a href="how-it-works.php" class="mobile-nav-link">Kā tas darbojas</a>
                <div class="mobile-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="btn btn-primary mobile-btn" style="margin-bottom: var(--spacing-2);">Mans Profils</a>
                        <form method="POST" action="logout.php" style="display: block; width: 100%;">
                            <button type="submit" class="btn btn-outline mobile-btn">Izlogoties</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
                        <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

  <main>
    <section class="profile-section">
      <div class="container">
        <div class="profile-header">
          <h1>Jūsu profils</h1>
        </div>
        
        <?php if (!empty($success)): ?>
          <div class="toast show" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
            <div class="toast-content">
              <div class="toast-icon success" style="color: #155724;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <p class="toast-message"><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button class="toast-close" onclick="this.parentElement.classList.remove('show')" style="color: #155724;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
          <div class="auth-error active" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
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
                  <img src="<?php echo htmlspecialchars($profilePhoto); ?>?t=<?php echo time(); // Cache busting ?>" alt="Profile Photo">
                <?php else: ?>
                  <div class="profile-photo-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  </div>
                <?php endif; ?>
              </div>
              <div class="profile-photo-actions">
                <form method="POST" action="profile.php" enctype="multipart/form-data" style="display: inline;">
                  <input type="hidden" name="action" value="upload_photo">
                  <label for="profilePhotoInput" class="btn-outline btn-small">Mainīt foto</label>
                  <input type="file" id="profilePhotoInput" name="profilePhoto" accept="image/*" class="hidden" onchange="this.form.submit()">
                </form>
                <?php if (!empty($profilePhoto)): ?>
                <form method="POST" action="profile.php" style="display: inline;">
                  <input type="hidden" name="action" value="remove_photo">
                  <button type="submit" id="removePhotoBtn" class="btn-text btn-small">Noņemt</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="profile-stats">
              <div class="stat-item">
                <span class="stat-number"><?php echo count($userBooks); ?></span>
                <span class="stat-label">Izliktas grāmatas</span>
              </div>
              <!-- <div class="stat-item">
                <span class="stat-number">0</span>
                <span class="stat-label">Maiņas</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">0.0</span>
                <span class="stat-label">Vērtējums</span>
              </div> -->
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
                  <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Jūsu uzvārds" value="<?php echo htmlspecialchars($lastName); ?>">
                </div>
              </div>
              <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" class="form-input" placeholder="Jūsu e-pasta adrese" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
              </div>
              <!-- <div class="form-group">
                <label for="location">Atrašanās vieta</label>
                <input type="text" id="location" name="location" class="form-input" placeholder="Pilsēta, Valsts" value="<?php echo htmlspecialchars($userLocation); ?>">
              </div>
              <div class="form-group">
                <label for="bio">Biogrāfija</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="4" placeholder="Pastāstiet mums par sevi un savām grāmatu interesēm..."><?php echo htmlspecialchars($userBio); ?></textarea>
              </div> -->
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
                  <input type="password" id="currentPassword" name="currentPassword" class="form-input" placeholder="Ievadiet savu pašreizējo paroli" required>
                </div>
                <div class="form-group">
                  <label for="newPassword">Jaunā parole</label>
                  <input type="password" id="newPassword" name="newPassword" class="form-input" placeholder="Ievadiet savu jauno paroli" required>
                </div>
                <div class="form-group">
                  <label for="confirmNewPassword">Apstipriniet jauno paroli</label>
                  <input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-input" placeholder="Apstipriniet savu jauno paroli" required>
                </div>
                <div class="form-actions">
                  <button type="submit" id="savePasswordBtn" class="btn-primary">Atjaunot paroli</button>
                  <button type="button" id="cancelPasswordBtn" class="btn-outline">Atcelt</button>
                </div>
              </form>
            </div>
            
            <div class="form-section-title">
              <h3>Mana bibliotēka</h3>
            </div>
            
            <button type="button" id="addBookBtn" class="btn-outline">Pievienot grāmatu</button>
            
            <div id="addBookSection" class="add-book-section hidden">
              <form method="POST" action="profile.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_book">
                <div class="form-group">
                  <label for="bookTitle">Grāmatas nosaukums*</label>
                  <input type="text" id="bookTitle" name="bookTitle" class="form-input" placeholder="Ievadiet grāmatas nosaukumu" required>
                </div>
                <div class="form-group">
                  <label for="bookAuthor">Autors*</label>
                  <input type="text" id="bookAuthor" name="bookAuthor" class="form-input" placeholder="Ievadiet autora vārdu" required>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="bookGenre">Žanrs*</label>
                    <input type="text" id="bookGenre" name="bookGenre" class="form-input" placeholder="Piemēram, Romāns, Detektīvs" required>
                  </div>
                  <div class="form-group">
                    <label for="bookLanguage">Valoda*</label>
                    <input type="text" id="bookLanguage" name="bookLanguage" class="form-input" placeholder="Piemēram, Latviešu, Angļu" required>
                  </div>
                </div>
                <div class="form-group">
                  <label for="bookYear">Izdošanas gads*</label>
                  <input type="number" id="bookYear" name="bookYear" class="form-input" placeholder="Piemēram, 2020" min="1000" max="<?php echo date("Y"); ?>" required>
                </div>
                <div class="form-group">
                  <label for="bookDescription">Apraksts</label>
                  <textarea id="bookDescription" name="bookDescription" class="form-textarea" rows="4" placeholder="Īss grāmatas apraksts..."></textarea>
                </div>
                <div class="form-group">
                  <label for="bookImage">Grāmatas vāka attēls (neobligāti)</label>
                  <input type="file" id="bookImage" name="bookImage" class="form-input" accept="image/jpeg,image/png,image/gif">
                </div>
                <div class="form-actions">
                  <button type="submit" id="saveBookBtn" class="btn-primary">Pievienot grāmatu</button>
                  <button type="button" id="cancelBookBtn" class="btn-outline">Atcelt</button>
                </div>
              </form>
            </div>
            
            <div class="user-books">
              <?php if (empty($userBooks)): ?>
                <p class="no-books-message">Jūsu bibliotēkā vēl nav grāmatu. Pievienojiet savu pirmo grāmatu!</p>
              <?php else: ?>
                <div class="books-grid">
                  <?php foreach ($userBooks as $book): ?>
                    <div class="book-card">
                      <div class="book-cover-container">
                        <?php if (!empty($book['Attels']) && file_exists($book['Attels'])): ?>
                          <img src="<?php echo htmlspecialchars($book['Attels']); ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($book['Nosaukums']); ?>" class="book-cover">
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
                          <span class="status-badge <?php echo htmlspecialchars(strtolower($book['Status'])); ?>"><?php echo htmlspecialchars($book['Status']); ?></span>
                        </div>
                        <!-- Здесь можно добавить кнопки для редактирования/удаления книги -->
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
            <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
            <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
            <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
          </div>
        </div>
        <div class="footer-links">
          <h3 class="footer-title">Ātrās saites</h3>
          <ul>
            <li><a href="browse.php">Pārlūkot grāmatas</a></li>
            <li><a href="how-it-works.php">Kā tas strādā</a></li>
            <?php if (!isLoggedIn()): ?>
            <li><a href="signup.php">Pievienoties BookSwap</a></li>
            <li><a href="login.php">Pieslēgties</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="footer-links">
          <h3 class="footer-title">Palīdzība un atbalsts</h3>
          <ul>
            <li><a href="faq.php">BUJ</a></li>
            <li><a href="contact-us.php">Sazināties ar mums</a></li>
            <li><a href="safety-tips.php">Drošības padomi</a></li>
            <li><a href="report-issue.php">Ziņot par problēmu</a></li>
          </ul>
        </div>
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
        <p>© <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
      </div>
    </div>
  </footer>

  <script src="script.js"></script> <!-- Основной JS файл для общих функций, например, мобильное меню -->
  <script src="profile_js.js"></script> <!-- JS для специфичных функций страницы профиля -->
  <script>
    // Этот скрипт из profile.php (оригинальный) можно либо оставить, либо его функциональность перенести в profile_js.js
    document.addEventListener('DOMContentLoaded', function() {
      // Auto-hide success/error message after some time
      const toast = document.querySelector('.toast.show');
      if (toast) {
        setTimeout(() => {
          toast.classList.remove('show');
        }, 5000); // Увеличено время, чтобы пользователь успел прочитать
      }
      const authError = document.querySelector('.auth-error.active');
        if (authError) {
            setTimeout(() => {
                authError.classList.remove('active');
            }, 5000);
        }
      
      // Set current year in footer
      const currentYearSpan = document.getElementById('currentYear');
      if(currentYearSpan) {
          currentYearSpan.textContent = new Date().getFullYear();
      }
    });
  </script>
</body>
</html>