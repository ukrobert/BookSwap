<?php
// Подключаем файл проверки сессии
require_once 'session_check.php';

// Перенаправляем неавторизованных пользователей на страницу входа
redirectIfNotLoggedIn();

// Подключаем файл с подключением к БД
require_once 'connect_db.php';

// Инициализируем переменные
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$profilePhoto = $_SESSION['user_profile_photo'] ?? '';
$errors = [];
$success = '';

// --- Логика обработки AJAX запросов для удаления/восстановления ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json'); // Все AJAX ответы будут JSON
    $response = ['success' => false, 'message' => 'Nezināma kļūda.'];

    if (!$savienojums) {
        $response['message'] = 'Nevarēja izveidot savienojumu ar datu bāzi.';
        echo json_encode($response);
        exit;
    }

    $book_id_ajax = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;

    if ($book_id_ajax <= 0) {
        $response['message'] = 'Nederīgs grāmatas ID.';
        echo json_encode($response);
        exit;
    }

    // Проверка, что книга принадлежит пользователю
    $checkOwnerStmt = $savienojums->prepare("SELECT LietotajsID FROM bookswap_books WHERE GramatasID = ?");
    $checkOwnerStmt->bind_param("i", $book_id_ajax);
    $checkOwnerStmt->execute();
    $checkOwnerResult = $checkOwnerStmt->get_result();
    if ($checkOwnerRow = $checkOwnerResult->fetch_assoc()) {
        if ($checkOwnerRow['LietotajsID'] != $userId) {
            $response['message'] = 'Jums nav tiesību veikt šo darbību ar šo grāmatu.';
            echo json_encode($response);
            $checkOwnerStmt->close();
            exit;
        }
    } else {
        $response['message'] = 'Grāmata nav atrasta.';
        echo json_encode($response);
        $checkOwnerStmt->close();
        exit;
    }
    $checkOwnerStmt->close();


    if ($_POST['ajax_action'] === 'schedule_delete_book') {
        $delete_until = date('Y-m-d H:i:s', time() + (5 * 60)); // 5 minutes from now
        $stmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Gaida dzēšanu', GaidaDzesanuLidz = ? WHERE GramatasID = ? AND LietotajsID = ?");
        $stmt->bind_param("sii", $delete_until, $book_id_ajax, $userId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Grāmata ieplānota dzēšanai.';
            $response['delete_until_timestamp'] = strtotime($delete_until) * 1000; // JS uses milliseconds
        } else {
            $response['message'] = 'Neizdevās ieplānot grāmatas dzēšanu: ' . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['ajax_action'] === 'restore_book') {
        $stmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Pieejama', GaidaDzesanuLidz = NULL WHERE GramatasID = ? AND LietotajsID = ? AND Status = 'Gaida dzēšanu'");
        $stmt->bind_param("ii", $book_id_ajax, $userId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Grāmata atjaunota.';
        } else {
            $response['message'] = 'Neizdevās atjaunot grāmatu vai tā vairs nav gaidīšanas režīmā: ' . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['ajax_action'] === 'confirm_final_delete_book') { // Этот action может быть использован JS, если таймер истек на клиенте
        $stmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Dzēsta', GaidaDzesanuLidz = NULL WHERE GramatasID = ? AND LietotajsID = ? AND Status = 'Gaida dzēšanu' AND GaidaDzesanuLidz <= NOW()");
        $stmt->bind_param("ii", $book_id_ajax, $userId);
         if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Grāmata veiksmīgi dzēsta.';
        } else {
            // Это может произойти, если книга была восстановлена или таймер еще не истек
            $response['message'] = 'Neizdevās dzēst grāmatu, iespējams, tā jau ir atjaunota vai dzēšanas laiks nav pagājis.';
        }
        $stmt->close();
    }

    if ($savienojums) $savienojums->close();
    echo json_encode($response);
    exit;
}
// --- Конец логики AJAX ---


// Автоматическое изменение статуса на "Dzēsta" для книг, у которых истек таймер ожидания
if ($savienojums) { // Проверяем, есть ли соединение (оно могло быть закрыто AJAX хендлером)
    try {
        $autoDeleteStmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Dzēsta', GaidaDzesanuLidz = NULL WHERE Status = 'Gaida dzēšanu' AND GaidaDzesanuLidz IS NOT NULL AND GaidaDzesanuLidz <= NOW() AND LietotajsID = ?");
        if ($autoDeleteStmt) {
            $autoDeleteStmt->bind_param("i", $userId);
            $autoDeleteStmt->execute();
            $autoDeleteStmt->close();
        } else {
            // error_log("Prepare failed for auto-delete: " . $savienojums->error);
        }
    } catch (Exception $e) {
        // error_log("Exception during auto-delete: " . $e->getMessage());
    }
} else { // Если соединение было закрыто AJAX, переоткроем его для остальной части страницы
    require 'connect_db.php'; // Это может быть не лучшей практикой, но для текущей структуры...
}


// Получаем данные пользователя из БД (для информации, которая не хранится в сессии или для актуализации)
$stmt_user_details = $savienojums->prepare("SELECT Lietotajvards, E_pasts, ProfilaAttels FROM bookswap_users WHERE LietotajsID = ?");
if ($stmt_user_details) {
    $stmt_user_details->bind_param("i", $userId);
    $stmt_user_details->execute();
    $result_user_details = $stmt_user_details->get_result();

    if ($user_db_data = $result_user_details->fetch_assoc()) {
        if ($userName !== $user_db_data['Lietotajvards']) {
             $_SESSION['user_name'] = $user_db_data['Lietotajvards'];
             $userName = $user_db_data['Lietotajvards'];
        }
        if ($userEmail !== $user_db_data['E_pasts']) {
            $_SESSION['user_email'] = $user_db_data['E_pasts'];
            $userEmail = $user_db_data['E_pasts'];
        }
        if ($profilePhoto !== $user_db_data['ProfilaAttels']) {
            $_SESSION['user_profile_photo'] = $user_db_data['ProfilaAttels'];
            $profilePhoto = $user_db_data['ProfilaAttels'];
        }
    }
    $stmt_user_details->close();
}

$nameParts = explode(' ', $userName, 2);
$firstName = $nameParts[0];
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';


// Обработка обычных POST запросов (не AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    if (isset($_POST['action'])) {
        // ... (существующая логика для update_profile, change_password, upload_photo, remove_photo, add_book) ...
        // Убедитесь, что после успешных действий (особенно add_book) вы делаете header('Location: profile.php'); exit();
        // чтобы избежать повторной отправки и использовать $_SESSION['success_message']

        // Обработка обновления профиля
        if ($_POST['action'] === 'update_profile') {
            $firstName_form = trim($_POST['firstName'] ?? '');
            $lastName_form = trim($_POST['lastName'] ?? '');
            
            if (empty($firstName_form)) $errors[] = 'Vārds ir obligāts lauks';
            
            if (empty($errors)) {
                $fullName = $firstName_form . (!empty($lastName_form) ? ' ' . $lastName_form : '');
                $stmt = $savienojums->prepare("UPDATE bookswap_users SET Lietotajvards = ? WHERE LietotajsID = ?");
                $stmt->bind_param("si", $fullName, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user_name'] = $fullName;
                    $userName = $fullName; // Обновить локальную переменную
                    $firstName = $firstName_form; // Обновить для отображения
                    $lastName = $lastName_form;   // Обновить для отображения
                    $_SESSION['success_message'] = 'Profils veiksmīgi atjaunināts!';
                    header('Location: profile.php'); exit();
                } else {
                    $errors[] = 'Kļūda atjauninot profilu: ' . $savienojums->error;
                }
                $stmt->close();
            }
        }
        // Обработка изменения пароля
        elseif ($_POST['action'] === 'change_password') {
            // ... (код изменения пароля, если успех, то $_SESSION['success_message'] и header('Location: profile.php'); exit();)
            $currentPassword = $_POST['currentPassword'] ?? '';
            $newPassword = $_POST['newPassword'] ?? '';
            $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';
            
            if (empty($currentPassword)) $errors[] = 'Pašreizējā parole ir obligāta';
            if (empty($newPassword)) $errors[] = 'Jaunā parole ir obligāta';
            elseif (strlen($newPassword) < 8) $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
            if ($newPassword !== $confirmNewPassword) $errors[] = 'Jaunās paroles nesakrīt';
            
            if (empty($errors)) {
                $stmt_pwd = $savienojums->prepare("SELECT Parole FROM bookswap_users WHERE LietotajsID = ?");
                $stmt_pwd->bind_param("i", $userId);
                $stmt_pwd->execute();
                $result_pwd = $stmt_pwd->get_result();
                if ($user_pwd = $result_pwd->fetch_assoc()) {
                    if (password_verify($currentPassword, $user_pwd['Parole'])) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateStmt_pwd = $savienojums->prepare("UPDATE bookswap_users SET Parole = ? WHERE LietotajsID = ?");
                        $updateStmt_pwd->bind_param("si", $hashedPassword, $userId);
                        if ($updateStmt_pwd->execute()) {
                             $_SESSION['success_message'] = 'Parole veiksmīgi atjaunināta!';
                             header('Location: profile.php'); exit();
                        } else {
                            $errors[] = 'Kļūda atjauninot paroli: ' . $savienojums->error;
                        }
                        $updateStmt_pwd->close();
                    } else {
                        $errors[] = 'Pašreizējā parole ir nepareiza';
                    }
                } else { $errors[] = 'Lietotājs nav atrasts'; }
                $stmt_pwd->close();
            }
        }
        // Обработка загрузки фото профиля
        elseif ($_POST['action'] === 'upload_photo') {
            // ... (код загрузки фото, если успех, то $_SESSION['success_message'] и header('Location: profile.php'); exit();)
            if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profilePhoto'];
                // ... (валидация файла) ...
                if (empty($errors)) { // Предполагая, что валидация прошла
                    $uploadDir = 'uploads/profile_photos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // ... (удаление старого фото) ...
                        $stmt_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = ? WHERE LietotajsID = ?");
                        $stmt_photo->bind_param("si", $filePath, $userId);
                        if ($stmt_photo->execute()) {
                            $_SESSION['user_profile_photo'] = $filePath;
                            $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi atjaunināts!';
                            header('Location: profile.php'); exit();
                        } else { $errors[] = 'Kļūda atjauninot profila fotoattēlu datubāzē.'; if(file_exists($filePath)) unlink($filePath); }
                        $stmt_photo->close();
                    } else { $errors[] = 'Kļūda augšupielādējot failu.'; }
                }
            } else { $errors[] = 'Lūdzu, izvēlieties failu vai notika kļūda augšupielādes laikā.'; }
        }
         // Обработка удаления фото профиля
        elseif ($_POST['action'] === 'remove_photo') {
            // ... (код удаления фото, если успех, то $_SESSION['success_message'] и header('Location: profile.php'); exit();)
            $stmt_remove_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = NULL WHERE LietotajsID = ?");
            $stmt_remove_photo->bind_param("i", $userId);
            if ($stmt_remove_photo->execute()) {
                // ... (удаление файла с сервера, если он был) ...
                if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false) {
                    unlink($profilePhoto);
                }
                $_SESSION['user_profile_photo'] = '';
                $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi noņemts!';
                header('Location: profile.php'); exit();
            } else { $errors[] = 'Kļūda noņemot profila fotoattēlu.';}
            $stmt_remove_photo->close();
        }
        // Обработка добавления книги
        elseif ($_POST['action'] === 'add_book') {
            // ... (код добавления книги, если успех, то $_SESSION['success_message'] и header('Location: profile.php'); exit();)
            $bookTitle = trim($_POST['bookTitle'] ?? '');
            $bookAuthor = trim($_POST['bookAuthor'] ?? '');
            $bookGenre = trim($_POST['bookGenre'] ?? '');
            $bookLanguage = trim($_POST['bookLanguage'] ?? '');
            $bookYear = intval($_POST['bookYear'] ?? 0);
            $bookStavoklis = trim($_POST['bookCondition'] ?? 'Laba'); // Получаем Stavoklis
            $bookDescription = trim($_POST['bookDescription'] ?? '');
            
            if (empty($bookTitle)) $errors[] = 'Grāmatas nosaukums ir obligāts lauks';
            // ... (остальная валидация) ...

            $bookImage = '';
            if (empty($errors)) {
                if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] === UPLOAD_ERR_OK) {
                    // ... (логика загрузки файла изображения книги) ...
                     $file_book_img = $_FILES['bookImage'];
                    // ... (валидация файла) ...
                    if (empty($errors)) { // Предполагая, что валидация прошла
                        $uploadDir_book_img = 'uploads/book_images/';
                        if (!is_dir($uploadDir_book_img)) mkdir($uploadDir_book_img, 0755, true);
                        $fileExtension_book_img = strtolower(pathinfo($file_book_img['name'], PATHINFO_EXTENSION));
                        $fileName_book_img = 'book_' . $userId . '_' . time() . '_' . uniqid() . '.' . $fileExtension_book_img;
                        $filePath_book_img = $uploadDir_book_img . $fileName_book_img;
                        if (move_uploaded_file($file_book_img['tmp_name'], $filePath_book_img)) {
                            $bookImage = $filePath_book_img;
                        } else { $errors[] = 'Kļūda augšupielādējot grāmatas attēlu.'; }
                    }
                }
                if (empty($errors)) {
                    $currentDate = date('Y-m-d H:i:s');
                    $status = 'Pieejama';
                    $stmt_add_book = $savienojums->prepare("INSERT INTO bookswap_books (Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, PievienosanasDatums, Status, LietotajsID, Stavoklis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_add_book->bind_param("ssssissssis", $bookTitle, $bookAuthor, $bookGenre, $bookLanguage, $bookYear, $bookDescription, $bookImage, $currentDate, $status, $userId, $bookStavoklis);
                    if ($stmt_add_book->execute()) {
                        $_SESSION['success_message'] = 'Grāmata veiksmīgi pievienota!';
                        header('Location: profile.php'); exit();
                    } else {
                        $errors[] = 'Kļūda pievienojot grāmatu: ' . $stmt_add_book->error;
                        if (!empty($bookImage) && file_exists($bookImage)) unlink($bookImage);
                    }
                    $stmt_add_book->close();
                }
            }
        }
    }
}


if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$userBooks = [];
// Выбираем также GaidaDzesanuLidz для книг
$stmt_books_list = $savienojums->prepare("SELECT GramatasID, Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Attels, Status, Stavoklis, GaidaDzesanuLidz FROM bookswap_books WHERE LietotajsID = ? ORDER BY PievienosanasDatums DESC");
if ($stmt_books_list) {
    $stmt_books_list->bind_param("i", $userId);
    $stmt_books_list->execute();
    $result_books_list = $stmt_books_list->get_result();
    while ($book_item = $result_books_list->fetch_assoc()) {
        $userBooks[] = $book_item;
    }
    $stmt_books_list->close();
} else {
    $errors[] = "Kļūda sagatavojot vaicājumu grāmatu sarakstam: " . $savienojums->error;
}

if ($savienojums) {
    $savienojums->close();
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
    .hidden { display: none; }
    .add-book-section, .password-change-section { margin-top: 20px; padding: 20px; background-color: var(--color-paper); border-radius: var(--radius-lg); }
    .form-section-title { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid var(--color-paper); padding-bottom: 10px; }
    .user-books { margin-top: 20px; }
    .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; margin-top: 20px; }
    .book-card { background-color: var(--color-white); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-paper); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); display: flex; flex-direction: column; position:relative; }
    .book-card.pending-deletion { border-left: 5px solid orange; }
    .book-cover-container { height: 200px; overflow: hidden; background-color: var(--color-light-gray); display: flex; align-items: center; justify-content: center; }
    .book-cover { width: 100%; height: 100%; object-fit: cover; }
    .book-cover-fallback svg { width: 50px; height: 50px; color: var(--color-gray); }
    .book-info { padding: 15px; flex-grow: 1; }
    .book-title { font-weight: 600; margin-bottom: 5px; font-size: 1.1rem; color: var(--color-darkwood); }
    .book-author { color: var(--color-gray); margin-bottom: 10px; font-size: 0.9rem; }
    .book-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .book-tag { font-size: 0.7rem; padding: 2px 8px; background-color: var(--color-paper); border-radius: 20px; color: var(--color-darkwood); }
    .book-status, .book-condition-display { margin-top: 5px; font-size: 0.8rem;}
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 500; }
    .status-badge.pieejama { background-color: #e6f7e6; color: #2e7d32; }
    .status-badge.apmainīta { background-color: #e3f2fd; color: #1565c0; }
    .status-badge.dzēsta { background-color: #fbe9e7; color: #d32f2f; }
    .status-badge.defektīva { background-color: #fff8e1; color: #ff8f00; }
    .status-badge.gaida-dzesanu { background-color: #fff3e0; color: #ef6c00; } /* New Status */
    .no-books-message { text-align: center; padding: 30px; color: var(--color-gray); background-color: var(--color-light-gray); border-radius: var(--radius-lg); margin-top: 20px; }
    .profile-photo-actions label.btn-outline { cursor: pointer; padding: var(--spacing-1) var(--spacing-3); font-size: 0.75rem; }
    .book-actions-footer { padding: 0 15px 15px 15px; display: flex; gap: 10px; justify-content: flex-end; margin-top: auto;}
    .btn-delete-book { background-color: #ef5350; color: white;}
    .btn-delete-book:hover { background-color: #e53935;}
    .btn-restore-book { background-color: #66bb6a; color: white;}
    .btn-restore-book:hover { background-color: #4caf50;}
    .countdown-timer { font-size: 0.8em; color: orange; text-align: center; padding: 5px 0; }

    /* Delete Confirmation Modal Styles */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
    .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; border: 1px solid #ddd; width: 80%; max-width: 500px; border-radius: var(--radius-lg); box-shadow: 0 5px 15px rgba(0,0,0,0.2); position: relative; }
    .close-button-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 20px; }
    .close-button-modal:hover, .close-button-modal:focus { color: #333; text-decoration: none; cursor: pointer; }
    .modal-content h3 { margin-top: 0; margin-bottom: 20px; font-family: var(--font-serif); color: var(--color-darkwood); }
    .modal-content p { margin-bottom: 15px; color: var(--color-gray); }
    .modal-content input[type="text"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--color-paper); border-radius: var(--radius-md); }
    .modal-footer-buttons { display: flex; justify-content: flex-end; gap: 10px; }
    #confirmDeleteBookBtnModal:disabled { background-color: #ccc; cursor: not-allowed; }
  </style>
</head>
<body>
    <header class="navigation">
        <div class="container">
            <div class="nav-wrapper">
                <a href="index.php" class="brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                    <h1 class="brand-name">BookSwap</h1>
                </a>
                <nav class="desktop-nav">
                    <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
                    <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
                </nav>
                <div class="desktop-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePhoto) && (filter_var($profilePhoto, FILTER_VALIDATE_URL) || file_exists($profilePhoto))): ?>
                                        <img src="<?php echo htmlspecialchars($profilePhoto); ?>?t=<?php echo time(); ?>" alt="Profils">
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
                <button class="mobile-menu-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>
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
        
        <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
            <?php if (!empty($success)): ?>
            <div class="toast show" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb; margin-bottom: 10px;">
                <div class="toast-content">
                <div class="toast-icon success" style="color: #155724;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <p class="toast-message"><?php echo htmlspecialchars($success); ?></p>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()" style="color: #155724;">×</button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="auth-error active" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px; border-radius: .25rem; margin-bottom: 10px;">
                <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
                 <button class="toast-close" onclick="this.parentElement.remove()" style="float:right; background:none; border:none; font-size:1.2rem; line-height:1; color: #721c24;">×</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="profile-content">
          <div class="profile-sidebar">
            <div class="profile-photo-container">
              <div class="profile-photo" id="profilePhotoPreview">
                <?php if (!empty($profilePhoto) && (filter_var($profilePhoto, FILTER_VALIDATE_URL) || file_exists($profilePhoto))): ?>
                  <img src="<?php echo htmlspecialchars($profilePhoto); ?>?t=<?php echo time(); ?>" alt="Profile Photo">
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
                  <button type="submit" class="btn-text btn-small">Noņemt</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
            <div class="profile-stats">
              <div class="stat-item">
                <span class="stat-number"><?php echo count(array_filter($userBooks, function($book){ return $book['Status'] !== 'Dzēsta'; })); ?></span>
                <span class="stat-label">Aktīvas grāmatas</span>
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
                  <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Jūsu uzvārds" value="<?php echo htmlspecialchars($lastName); ?>">
                </div>
              </div>
              <div class="form-group">
                <label for="email">E-pasts</label>
                <input type="email" id="email" class="form-input" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
              </div>
              <div class="form-group">
                <button type="submit" class="btn-primary">Saglabāt izmaiņas</button>
              </div>
            </form>
            
            <div class="form-section-title"><h3>Kontu drošība</h3></div>
            <button type="button" id="changePasswordBtn" class="btn-outline">Mainīt paroli</button>
            <div id="passwordChangeSection" class="password-change-section hidden">
              <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group"><label for="currentPassword">Pašreizējā parole</label><input type="password" id="currentPassword" name="currentPassword" class="form-input" required></div>
                <div class="form-group"><label for="newPassword">Jaunā parole</label><input type="password" id="newPassword" name="newPassword" class="form-input" required></div>
                <div class="form-group"><label for="confirmNewPassword">Apstipriniet jauno paroli</label><input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-input" required></div>
                <div class="form-actions"><button type="submit" class="btn-primary">Atjaunot paroli</button><button type="button" id="cancelPasswordBtn" class="btn-outline">Atcelt</button></div>
              </form>
            </div>
            
            <div class="form-section-title"><h3>Mana bibliotēka</h3></div>
            <button type="button" id="addBookBtn" class="btn-outline">Pievienot grāmatu</button>
            <div id="addBookSection" class="add-book-section hidden">
              <form method="POST" action="profile.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_book">
                <div class="form-group"><label for="bookTitle">Grāmatas nosaukums*</label><input type="text" id="bookTitle" name="bookTitle" class="form-input" required></div>
                <div class="form-group"><label for="bookAuthor">Autors*</label><input type="text" id="bookAuthor" name="bookAuthor" class="form-input" required></div>
                <div class="form-row">
                  <div class="form-group"><label for="bookGenre">Žanrs*</label><input type="text" id="bookGenre" name="bookGenre" class="form-input" required></div>
                  <div class="form-group"><label for="bookLanguage">Valoda*</label><input type="text" id="bookLanguage" name="bookLanguage" class="form-input" required></div>
                </div>
                <div class="form-group"><label for="bookYear">Izdošanas gads*</label><input type="number" id="bookYear" name="bookYear" class="form-input" min="1000" max="<?php echo date("Y"); ?>" required></div>
                <div class="form-group">
                    <label for="bookCondition">Stāvoklis*</label>
                    <select id="bookCondition" name="bookCondition" class="form-input" required>
                        <option value="Laba" selected>Laba</option>
                        <option value="Kā jauna">Kā jauna</option>
                        <option value="Ļoti laba">Ļoti laba</option>
                        <option value="Pieņemama">Pieņemama</option>
                    </select>
                </div>
                <div class="form-group"><label for="bookDescription">Apraksts</label><textarea id="bookDescription" name="bookDescription" class="form-textarea" rows="3"></textarea></div>
                <div class="form-group"><label for="bookImage">Vāka attēls</label><input type="file" id="bookImage" name="bookImage" class="form-input" accept="image/jpeg,image/png,image/gif"></div>
                <div class="form-actions"><button type="submit" class="btn-primary">Pievienot grāmatu</button><button type="button" id="cancelBookBtn" class="btn-outline">Atcelt</button></div>
              </form>
            </div>
            
            <div class="user-books">
              <?php if (empty($userBooks)): ?>
                <p class="no-books-message">Jūsu bibliotēkā vēl nav grāmatu.</p>
              <?php else: ?>
                <div class="books-grid">
                  <?php foreach ($userBooks as $book): ?>
                    <div class="book-card <?php echo $book['Status'] === 'Gaida dzēšanu' ? 'pending-deletion' : ''; ?>" id="book-card-<?php echo $book['GramatasID']; ?>" data-bookid="<?php echo $book['GramatasID']; ?>" data-booktitle="<?php echo htmlspecialchars($book['Nosaukums']); ?>" data-delete-until="<?php echo $book['Status'] === 'Gaida dzēšanu' && $book['GaidaDzesanuLidz'] ? strtotime($book['GaidaDzesanuLidz']) * 1000 : ''; ?>">
                      <div class="book-cover-container">
                        <?php 
                          $book_image_path_display = '';
                          if (!empty($book['Attels'])) {
                              if (filter_var($book['Attels'], FILTER_VALIDATE_URL)) {
                                  $book_image_path_display = htmlspecialchars($book['Attels']);
                              } elseif (file_exists($book['Attels'])) {
                                  $book_image_path_display = htmlspecialchars($book['Attels']);
                              }
                          }
                        ?>
                        <?php if ($book_image_path_display): ?>
                          <img src="<?php echo $book_image_path_display; ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($book['Nosaukums']); ?>" class="book-cover">
                        <?php else: ?>
                          <div class="book-cover-fallback"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" fill="none" stroke="currentColor" stroke-width="1"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" fill="none" stroke="currentColor" stroke-width="1"/></svg></div>
                        <?php endif; ?>
                      </div>
                      <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($book['Nosaukums']); ?></h3>
                        <p class="book-author"><?php echo htmlspecialchars($book['Autors']); ?></p>
                        <div class="book-tags">
                          <span class="book-tag"><?php echo htmlspecialchars($book['Zanrs']); ?></span>
                          <span class="book-tag"><?php echo htmlspecialchars($book['Stavoklis'] ?? 'Nenorādīts'); ?></span>
                        </div>
                         <div class="book-status">
                            Status: <span class="status-badge <?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $book['Status']))); ?>"><?php echo htmlspecialchars($book['Status']); ?></span>
                        </div>
                        <div class="countdown-timer" id="timer-<?php echo $book['GramatasID']; ?>"></div>
                      </div>
                       <div class="book-actions-footer">
                          <?php if ($book['Status'] === 'Pieejama'): ?>
                              <button type="button" class="btn btn-small btn-delete-book" onclick="openDeleteModal(<?php echo $book['GramatasID']; ?>, '<?php echo htmlspecialchars(addslashes($book['Nosaukums'])); ?>')">Dzēst</button>
                              <!-- <button type="button" class="btn btn-small btn-outline">Rediģēt</button> -->
                          <?php elseif ($book['Status'] === 'Gaida dzēšanu'): ?>
                              <button type="button" class="btn btn-small btn-restore-book" onclick="restoreBook(<?php echo $book['GramatasID']; ?>)">Atjaunot</button>
                          <?php elseif ($book['Status'] === 'Dzēsta'): ?>
                              <!-- Можно добавить кнопку для полного удаления из БД, если это нужно -->
                          <?php endif; ?>
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteBookModal" class="modal">
        <div class="modal-content">
            <span class="close-button-modal" onclick="closeDeleteModal()">×</span>
            <h3>Apstiprināt grāmatas dzēšanu</h3>
            <p>Lai apstiprinātu grāmatas "<strong id="modalBookTitle"></strong>" dzēšanu, lūdzu, ievadiet tās nosaukumu zemāk:</p>
            <input type="text" id="confirmBookTitleInput" placeholder="Ievadiet grāmatas nosaukumu šeit">
            <p style="font-size:0.9em; color:var(--color-gray);">Pēc apstiprināšanas grāmata tiks ieplānota dzēšanai. Jums būs 5 minūtes, lai atjaunotu to.</p>
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Atcelt</button>
                <button type="button" id="confirmDeleteBookBtnModal" class="btn-primary" disabled>Apstiprināt dzēšanu</button>
            </div>
        </div>
    </div>
  
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
  <script src="profile_js.js"></script> 
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastContainer = document.getElementById('toast-container');
        const existingToasts = toastContainer ? toastContainer.querySelectorAll('.toast.show, .auth-error.active') : [];
        existingToasts.forEach(toast => {
            setTimeout(() => {
                if(toast.parentElement === toastContainer || toast.classList.contains('auth-error')) { // Ensure it's still there
                    toast.remove();
                }
            }, 5000);
        });

        const currentYearSpan = document.getElementById('currentYear');
        if(currentYearSpan) currentYearSpan.textContent = new Date().getFullYear();

        // Delete book functionality
        const deleteModal = document.getElementById('deleteBookModal');
        const confirmBookTitleInput = document.getElementById('confirmBookTitleInput');
        const confirmDeleteBookBtnModal = document.getElementById('confirmDeleteBookBtnModal');
        const modalBookTitleSpan = document.getElementById('modalBookTitle');
        let currentBookIdToDelete = null;
        let currentBookTitleToConfirm = "";

        window.openDeleteModal = function(bookId, bookTitle) {
            currentBookIdToDelete = bookId;
            currentBookTitleToConfirm = bookTitle;
            if(modalBookTitleSpan) modalBookTitleSpan.textContent = bookTitle;
            if(confirmBookTitleInput) confirmBookTitleInput.value = '';
            if(confirmDeleteBookBtnModal) confirmDeleteBookBtnModal.disabled = true;
            if(deleteModal) deleteModal.style.display = 'block';
        }

        window.closeDeleteModal = function() {
            if(deleteModal) deleteModal.style.display = 'none';
            currentBookIdToDelete = null;
            currentBookTitleToConfirm = "";
        }

        if(confirmBookTitleInput && confirmDeleteBookBtnModal) {
            confirmBookTitleInput.addEventListener('input', function() {
                confirmDeleteBookBtnModal.disabled = this.value.trim() !== currentBookTitleToConfirm;
            });
        }

        if(confirmDeleteBookBtnModal) {
            confirmDeleteBookBtnModal.addEventListener('click', function() {
                if (currentBookIdToDelete && confirmBookTitleInput.value.trim() === currentBookTitleToConfirm) {
                    scheduleBookDeletion(currentBookIdToDelete);
                    closeDeleteModal();
                }
            });
        }
        
        function scheduleBookDeletion(bookId) {
            const formData = new FormData();
            formData.append('ajax_action', 'schedule_delete_book');
            formData.append('book_id', bookId);

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    updateBookCardToPending(bookId, data.delete_until_timestamp);
                } else {
                    showUIMessage(data.message || 'Kļūda, ieplānojot dzēšanu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error scheduling deletion:', error);
                showUIMessage('Tīkla kļūda, mēģiniet vēlāk.', 'error');
            });
        }

        window.restoreBook = function(bookId) {
            const formData = new FormData();
            formData.append('ajax_action', 'restore_book');
            formData.append('book_id', bookId);

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    updateBookCardToNormal(bookId);
                } else {
                    showUIMessage(data.message || 'Kļūda, atjaunojot grāmatu.', 'error');
                }
            })
            .catch(error => {
                console.error('Error restoring book:', error);
                showUIMessage('Tīkla kļūda, mēģiniet vēlāk.', 'error');
            });
        }

        let countdownIntervals = {}; // Store intervals to clear them

        function updateBookCardToPending(bookId, deleteUntilTimestamp) {
            const card = document.getElementById(`book-card-${bookId}`);
            if (!card) return;

            card.classList.add('pending-deletion');
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = 'status-badge gaida-dzesanu'; // Use space for multiple classes
                statusBadge.textContent = 'Gaida dzēšanu';
            }
            
            const actionsFooter = card.querySelector('.book-actions-footer');
            if (actionsFooter) {
                actionsFooter.innerHTML = `<button type="button" class="btn btn-small btn-restore-book" onclick="restoreBook(${bookId})">Atjaunot</button>`;
            }

            const timerDisplay = card.querySelector(`#timer-${bookId}`);
            if (timerDisplay && deleteUntilTimestamp) {
                if (countdownIntervals[bookId]) {
                    clearInterval(countdownIntervals[bookId]);
                }
                countdownIntervals[bookId] = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = deleteUntilTimestamp - now;
                    
                    if (distance < 0) {
                        clearInterval(countdownIntervals[bookId]);
                        timerDisplay.textContent = "Dzēšanas laiks beidzies.";
                        // Optionally, trigger a refresh or final delete AJAX call
                        // For now, rely on PHP to handle it on next page load.
                        // Or, directly call final delete:
                        // confirmFinalDelete(bookId, card); 
                        // (Be cautious with auto-AJAX calls as they can be resource-intensive)
                        // For simplicity, we just update the text. The backend will handle actual deletion.
                        if(actionsFooter.querySelector('.btn-restore-book')) {
                            actionsFooter.querySelector('.btn-restore-book').remove();
                        }
                        statusBadge.className = 'status-badge dzesta';
                        statusBadge.textContent = 'Dzēsta';
                        card.style.opacity = '0.6';


                        return;
                    }
                    
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timerDisplay.textContent = `Līdz dzēšanai: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }, 1000);
            }
        }
        
        // Initialize timers for already pending books on page load
        document.querySelectorAll('.book-card.pending-deletion').forEach(card => {
            const bookId = card.dataset.bookid;
            const deleteUntil = parseInt(card.dataset.deleteUntil, 10);
            if (bookId && deleteUntil && deleteUntil > new Date().getTime()) {
                 updateBookCardToPending(bookId, deleteUntil); // This will start the timer
            } else if (bookId && deleteUntil && deleteUntil <= new Date().getTime()) {
                // If timer already expired on load
                const timerDisplay = card.querySelector(`#timer-${bookId}`);
                if(timerDisplay) timerDisplay.textContent = "Dzēšanas laiks beidzies.";
                 const statusBadge = card.querySelector('.status-badge');
                 if (statusBadge) {
                    statusBadge.className = 'status-badge dzesta';
                    statusBadge.textContent = 'Dzēsta';
                 }
                const actionsFooter = card.querySelector('.book-actions-footer');
                if(actionsFooter && actionsFooter.querySelector('.btn-restore-book')) {
                    actionsFooter.querySelector('.btn-restore-book').remove();
                }
                card.style.opacity = '0.6';
            }
        });


        function updateBookCardToNormal(bookId) {
            const card = document.getElementById(`book-card-${bookId}`);
            if (!card) return;

            if (countdownIntervals[bookId]) {
                clearInterval(countdownIntervals[bookId]);
                delete countdownIntervals[bookId];
            }

            card.classList.remove('pending-deletion');
            card.style.opacity = '1';
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = 'status-badge pieejama';
                statusBadge.textContent = 'Pieejama';
            }
            const timerDisplay = card.querySelector(`#timer-${bookId}`);
            if(timerDisplay) timerDisplay.textContent = '';
            
            const actionsFooter = card.querySelector('.book-actions-footer');
            const bookTitle = card.dataset.booktitle || 'grāmatu';
            if (actionsFooter) {
                actionsFooter.innerHTML = `<button type="button" class="btn btn-small btn-delete-book" onclick="openDeleteModal(${bookId}, '${bookTitle.replace(/'/g, "\\'")}')">Dzēst</button>`;
            }
        }

        function showUIMessage(message, type = 'success') {
            const container = document.getElementById('toast-container') || document.body;
            const toastDiv = document.createElement('div');
            toastDiv.className = `toast show ${type === 'error' ? 'auth-error active' : ''}`; // Use existing styles
            toastDiv.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            toastDiv.style.color = type === 'success' ? '#155724' : '#721c24';
            toastDiv.style.borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            if(type === 'error' && !toastDiv.classList.contains('auth-error')) toastDiv.style.padding = '15px';


            let iconSvg = type === 'success' ? 
                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' :
                '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

            toastDiv.innerHTML = `
                <div class="toast-content" style="display:flex; align-items:center;">
                    <div class="toast-icon" style="margin-right:10px; color: ${type === 'success' ? '#155724' : '#721c24'};">
                       ${iconSvg}
                    </div>
                    <p class="toast-message" style="margin:0;">${message}</p>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()" style="background:none; border:none; font-size:1.2rem; line-height:1; color: ${type === 'success' ? '#155724' : '#721c24'}; position:absolute; top:50%; right:15px; transform:translateY(-50%);">×</button>
            `;
            toastDiv.style.padding = '15px';
            toastDiv.style.borderRadius = '.25rem';
            toastDiv.style.marginBottom = '10px';
            toastDiv.style.position = 'relative'; // For close button positioning

            if(toastContainer){
                 toastContainer.appendChild(toastDiv);
            } else { // Fallback if no specific container
                 toastDiv.style.position = 'fixed';
                 toastDiv.style.top = '20px';
                 toastDiv.style.right = '20px';
                 toastDiv.style.zIndex = '1050';
                 document.body.appendChild(toastDiv);
            }


            setTimeout(() => {
                toastDiv.remove();
            }, 5000);
        }

    });
  </script>
</body>
</html>