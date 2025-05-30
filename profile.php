<?php
require_once 'session_check.php';
redirectIfNotLoggedIn();
require_once 'connect_db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$profilePhoto = $_SESSION['user_profile_photo'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'Registrēts';

$errors = [];
$success = '';

$genres_list = [
    "Daiļliteratūra", "Fantāzija", "Zinātniskā fantastika", "Detektīvs", 
    "Trilleris", "Romāns", "Šausmas", "Biogrāfija", "Vēsture", 
    "Pašpalīdzība", "Atmiņu stāsts", "Dzeja", "Cits"
];
$conditions_list = ["Kā jauna", "Ļoti laba", "Laba", "Pieņemama"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Nezināma kļūda.'];
    if (!$savienojums) { $response['message'] = 'DB savienojuma kļūda.'; echo json_encode($response); exit; }

    $book_id_ajax = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; echo json_encode($response); exit;}

    $can_moderate = ($userRole === 'Moderators' || $userRole === 'Administrators');
    $is_owner = false;
    $book_owner_id = null;
    $book_current_status_for_restore_logic = null;

    $checkStmt = $savienojums->prepare("SELECT LietotajsID, Status FROM bookswap_books WHERE GramatasID = ?");
    if($checkStmt) {
        $checkStmt->bind_param("i", $book_id_ajax);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($bookRow = $checkResult->fetch_assoc()) {
            $book_owner_id = $bookRow['LietotajsID'];
            $book_current_status_for_restore_logic = $bookRow['Status'];
            if ($book_owner_id == $userId) {
                $is_owner = true;
            }
        } else {
            $response['message'] = 'Grāmata nav atrasta.'; echo json_encode($response); $checkStmt->close(); exit;
        }
        $checkStmt->close();
    } else {
        $response['message'] = 'DB kļūda (pārbaude).'; echo json_encode($response); exit;
    }

    switch ($_POST['ajax_action']) {
        case 'schedule_delete_book':
            if (!$is_owner) { $response['message'] = 'Jums nav tiesību dzēst šo grāmatu.'; break; }
            $delete_until = date('Y-m-d H:i:s', time() + (5 * 60));
            $stmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Gaida dzēšanu', GaidaDzesanuLidz = ? WHERE GramatasID = ? AND LietotajsID = ? AND (Status = 'Pieejama' OR Status = 'Gaida apstiprinājumu')");
            if($stmt){
                $stmt->bind_param("sii", $delete_until, $book_id_ajax, $userId);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Grāmata ieplānota dzēšanai.', 'delete_until_timestamp' => strtotime($delete_until) * 1000];
                } else { $response['message'] = 'Neizdevās ieplānot dzēšanu vai grāmata nav atbilstošā statusā.'; }
                $stmt->close();
            } else {$response['message'] = 'DB kļūda (plānot dzēšanu).';}
            break;

        case 'restore_book':
            if (!$is_owner) { $response['message'] = 'Jums nav tiesību atjaunot šo grāmatu.'; break; }
            
            $status_to_restore = 'Pieejama'; 
            $status_to_restore_for_owner = 'Gaida apstiprinājumu';

            $stmt = $savienojums->prepare("UPDATE bookswap_books SET Status = ?, GaidaDzesanuLidz = NULL WHERE GramatasID = ? AND LietotajsID = ? AND Status = 'Gaida dzēšanu'");
            if($stmt){
                $stmt->bind_param("sii", $status_to_restore_for_owner, $book_id_ajax, $userId);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Grāmata atjaunota.', 'new_status' => $status_to_restore_for_owner];
                } else { $response['message'] = 'Neizdevās atjaunot grāmatu.'; }
                $stmt->close();
            } else {$response['message'] = 'DB kļūda (atjaunot).';}
            break;
        case 'approve_book':
            if (!$can_moderate) { $response['message'] = 'Jums nav tiesību apstiprināt grāmatas.'; break; }
            $stmt_approve = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Pieejama' WHERE GramatasID = ? AND Status = 'Gaida apstiprinājumu'");
            if($stmt_approve){
                $stmt_approve->bind_param("i", $book_id_ajax);
                if ($stmt_approve->execute() && $stmt_approve->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Grāmata apstiprināta.'];
                } else { $response['message'] = 'Neizdevās apstiprināt grāmatu vai tā jau ir apstrādāta.'; }
                $stmt_approve->close();
            } else {$response['message'] = 'DB kļūda (apstiprināt).';}
            break;
        case 'reject_book': 
            if (!$can_moderate) { $response['message'] = 'Jums nav tiesību noraidīt grāmatas.'; break; }
            $image_path_reject = null;
            $stmt_get_img_reject = $savienojums->prepare("SELECT Attels FROM bookswap_books WHERE GramatasID = ?");
             if($stmt_get_img_reject){
                $stmt_get_img_reject->bind_param("i", $book_id_ajax);
                $stmt_get_img_reject->execute();
                $result_img_reject = $stmt_get_img_reject->get_result();
                if ($row_img_reject = $result_img_reject->fetch_assoc()) { $image_path_reject = $row_img_reject['Attels']; }
                $stmt_get_img_reject->close();
            }
            $stmt_reject = $savienojums->prepare("DELETE FROM bookswap_books WHERE GramatasID = ? AND Status = 'Gaida apstiprinājumu'");
            if($stmt_reject){
                $stmt_reject->bind_param("i", $book_id_ajax);
                if ($stmt_reject->execute() && $stmt_reject->affected_rows > 0) {
                    if (!empty($image_path_reject) && file_exists($image_path_reject) && strpos($image_path_reject, 'default') === false) {
                        unlink($image_path_reject); 
                    }
                    $response = ['success' => true, 'message' => 'Grāmata noraidīta un dzēsta.'];
                } else { $response['message'] = 'Neizdevās noraidīt/dzēst grāmatu vai tā jau ir apstrādāta.'; }
                $stmt_reject->close();
            } else {$response['message'] = 'DB kļūda (noraidīt).';}
            break;
    }
    if ($savienojums) $savienojums->close();
    echo json_encode($response);
    exit;
}

if ($savienojums) {
    $autoDeleteStmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Dzēsta', GaidaDzesanuLidz = NULL WHERE Status = 'Gaida dzēšanu' AND GaidaDzesanuLidz IS NOT NULL AND GaidaDzesanuLidz <= NOW() AND LietotajsID = ?");
    if ($autoDeleteStmt) {
        $autoDeleteStmt->bind_param("i", $userId);
        $autoDeleteStmt->execute();
        $autoDeleteStmt->close();
    }
} else { require 'connect_db.php'; }

$stmt_user_refresh = $savienojums->prepare("SELECT Lietotajvards, E_pasts, ProfilaAttels, Loma FROM bookswap_users WHERE LietotajsID = ?");
if ($stmt_user_refresh) {
    $stmt_user_refresh->bind_param("i", $userId);
    $stmt_user_refresh->execute();
    $result_user_refresh = $stmt_user_refresh->get_result();
    if ($user_db_data_refresh = $result_user_refresh->fetch_assoc()) {
        $_SESSION['user_name'] = $user_db_data_refresh['Lietotajvards']; $userName = $user_db_data_refresh['Lietotajvards'];
        $_SESSION['user_email'] = $user_db_data_refresh['E_pasts']; $userEmail = $user_db_data_refresh['E_pasts'];
        $_SESSION['user_profile_photo'] = $user_db_data_refresh['ProfilaAttels']; $profilePhoto = $user_db_data_refresh['ProfilaAttels'];
        $_SESSION['user_role'] = $user_db_data_refresh['Loma']; $userRole = $user_db_data_refresh['Loma'];
    }
    $stmt_user_refresh->close();
}

$nameParts = explode(' ', $userName, 2);
$firstName = $nameParts[0];
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_book') {
            $bookTitle = trim($_POST['bookTitleP'] ?? '');
            $bookAuthor = trim($_POST['bookAuthorP'] ?? '');
            $bookGenre = trim($_POST['bookGenreP'] ?? ''); 
            $bookLanguage = trim($_POST['bookLanguageP'] ?? '');
            $bookYear = intval($_POST['bookYearP'] ?? 0);
            $bookStavoklis = trim($_POST['bookConditionP'] ?? 'Laba'); 
            $bookDescription = trim($_POST['bookDescriptionP'] ?? '');
            
            if (empty($bookTitle)) $errors[] = 'Nosaukums ir obligāts.';
            if (empty($bookAuthor)) $errors[] = 'Autors ir obligāts.';
            if (empty($bookGenre) || !in_array($bookGenre, $genres_list)) $errors[] = 'Lūdzu, izvēlieties derīgu žanru.';
            if (empty($bookLanguage)) $errors[] = 'Valoda ir obligāta.';
            if ($bookYear < 1000 || $bookYear > date("Y")) $errors[] = 'Nederīgs izdošanas gads.';
            if (empty($bookStavoklis) || !in_array($bookStavoklis, $conditions_list)) $errors[] = 'Lūdzu, izvēlieties derīgu stāvokli.';

            $bookImage = '';
            if (empty($errors)) {
                if (isset($_FILES['bookImageP']) && $_FILES['bookImageP']['error'] === UPLOAD_ERR_OK) {
                     $file_book_img = $_FILES['bookImageP'];
                    $allowedTypes_book_img = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($file_book_img['type'], $allowedTypes_book_img)) $errors[] = 'Grāmatas attēlam atbalstītie formāti: JPEG, PNG, GIF';
                    if ($file_book_img['size'] > 2 * 1024 * 1024) $errors[] = 'Grāmatas attēla izmērs nedrīkst pārsniegt 2MB';
                    if (empty($errors)) { 
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
                    $status = 'Gaida apstiprinājumu'; 
                    $stmt_add_book = $savienojums->prepare("INSERT INTO bookswap_books (Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, PievienosanasDatums, Status, LietotajsID, Stavoklis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if($stmt_add_book) {
                        $stmt_add_book->bind_param("ssssissssis", $bookTitle, $bookAuthor, $bookGenre, $bookLanguage, $bookYear, $bookDescription, $bookImage, $currentDate, $status, $userId, $bookStavoklis);
                        if ($stmt_add_book->execute()) {
                            $_SESSION['success_message'] = 'Grāmata pievienota un gaida apstiprinājumu!';
                            header('Location: profile.php'); exit();
                        } else {
                            $errors[] = 'Kļūda pievienojot grāmatu: ' . $stmt_add_book->error;
                            if (!empty($bookImage) && file_exists($bookImage)) unlink($bookImage);
                        }
                        $stmt_add_book->close();
                    } else { $errors[] = 'DB Kļūda (pievienot grāmatu).'; }
                }
            }
        } elseif ($_POST['action'] === 'update_profile') {
            $firstName_form = trim($_POST['firstName'] ?? '');
            $lastName_form = trim($_POST['lastName'] ?? '');
            if (empty($firstName_form)) $errors[] = 'Vārds ir obligāts lauks';
            if (empty($errors)) {
                $fullName = $firstName_form . (!empty($lastName_form) ? ' ' . $lastName_form : '');
                $stmt_upd_prof = $savienojums->prepare("UPDATE bookswap_users SET Lietotajvards = ? WHERE LietotajsID = ?");
                $stmt_upd_prof->bind_param("si", $fullName, $userId);
                if ($stmt_upd_prof->execute()) {
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['success_message'] = 'Profils veiksmīgi atjaunināts!';
                    header('Location: profile.php'); exit();
                } else { $errors[] = 'Kļūda atjauninot profilu: ' . $savienojums->error; }
                $stmt_upd_prof->close();
            }
        } elseif ($_POST['action'] === 'change_password') { 
            $currentPassword = $_POST['currentPassword'] ?? ''; $newPassword = $_POST['newPassword'] ?? ''; $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';
            if (empty($currentPassword)) $errors[] = 'Pašreizējā parole ir obligāta';
            if (empty($newPassword)) $errors[] = 'Jaunā parole ir obligāta'; elseif (strlen($newPassword) < 8) $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
            if ($newPassword !== $confirmNewPassword) $errors[] = 'Jaunās paroles nesakrīt';
            if (empty($errors)) {
                $stmt_pwd = $savienojums->prepare("SELECT Parole FROM bookswap_users WHERE LietotajsID = ?");
                $stmt_pwd->bind_param("i", $userId); $stmt_pwd->execute(); $result_pwd = $stmt_pwd->get_result();
                if ($user_pwd = $result_pwd->fetch_assoc()) {
                    if (password_verify($currentPassword, $user_pwd['Parole'])) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateStmt_pwd = $savienojums->prepare("UPDATE bookswap_users SET Parole = ? WHERE LietotajsID = ?");
                        $updateStmt_pwd->bind_param("si", $hashedPassword, $userId);
                        if ($updateStmt_pwd->execute()) { $_SESSION['success_message'] = 'Parole veiksmīgi atjaunināta!'; header('Location: profile.php'); exit(); } 
                        else { $errors[] = 'Kļūda atjauninot paroli: ' . $savienojums->error; }
                        $updateStmt_pwd->close();
                    } else { $errors[] = 'Pašreizējā parole ir nepareiza'; }
                } else { $errors[] = 'Lietotājs nav atrasts'; }
                $stmt_pwd->close();
            }
        } elseif ($_POST['action'] === 'upload_photo') { 
            if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profilePhoto']; $allowedTypes_photo = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes_photo)) $errors[] = 'Atbalstītie failu formāti profilam: JPEG, PNG, GIF';
                if ($file['size'] > 2 * 1024 * 1024) $errors[] = 'Profila foto izmērs nedrīkst pārsniegt 2MB';
                if (empty($errors)) { 
                    $uploadDir = 'uploads/profile_photos/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension; $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false) { unlink($profilePhoto); }
                        $stmt_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = ? WHERE LietotajsID = ?");
                        $stmt_photo->bind_param("si", $filePath, $userId);
                        if ($stmt_photo->execute()) {
                            $_SESSION['user_profile_photo'] = $filePath; $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi atjaunināts!'; header('Location: profile.php'); exit();
                        } else { $errors[] = 'Kļūda atjauninot profila foto datubāzē.'; if(file_exists($filePath)) unlink($filePath); }
                        $stmt_photo->close();
                    } else { $errors[] = 'Kļūda augšupielādējot failu.'; }
                }
            } else { $errors[] = 'Lūdzu, izvēlieties failu vai notika kļūda augšupielādes laikā.'; }
        } elseif ($_POST['action'] === 'remove_photo') { 
            $stmt_remove_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = NULL WHERE LietotajsID = ?");
            $stmt_remove_photo->bind_param("i", $userId);
            if ($stmt_remove_photo->execute()) {
                if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false) { unlink($profilePhoto); }
                $_SESSION['user_profile_photo'] = ''; $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi noņemts!'; header('Location: profile.php'); exit();
            } else { $errors[] = 'Kļūda noņemot profila fotoattēlu.';}
            $stmt_remove_photo->close();
        }
    }
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$displayBooks = [];
if ($userRole === 'Moderators' || $userRole === 'Administrators') {
    $stmt_mod_q = $savienojums->prepare("
        SELECT b.*, u.Lietotajvards AS IesniedzejsVards 
        FROM bookswap_books b
        JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
        WHERE b.Status = 'Gaida apstiprinājumu' 
        ORDER BY b.PievienosanasDatums ASC
    ");
    if ($stmt_mod_q) {
        $stmt_mod_q->execute();
        $result_mod_q = $stmt_mod_q->get_result();
        while ($book_item_mod = $result_mod_q->fetch_assoc()) {
            $displayBooks[] = $book_item_mod;
        }
        $stmt_mod_q->close();
    } else { $errors[] = "Kļūda ielādējot moderācijas sarakstu: " . $savienojums->error; }
} else { 
    $stmt_user_b = $savienojums->prepare("SELECT GramatasID, Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Attels, Status, Stavoklis, GaidaDzesanuLidz FROM bookswap_books WHERE LietotajsID = ? ORDER BY PievienosanasDatums DESC");
    if ($stmt_user_b) {
        $stmt_user_b->bind_param("i", $userId);
        $stmt_user_b->execute();
        $result_user_b = $stmt_user_b->get_result();
        while ($book_item_user = $result_user_b->fetch_assoc()) {
            $displayBooks[] = $book_item_user;
        }
        $stmt_user_b->close();
    } else { $errors[] = "Kļūda ielādējot jūsu grāmatas: " . $savienojums->error; }
}

if ($savienojums) $savienojums->close();
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
    .add-book-section, .password-change-section, .moderation-section { margin-top: 20px; padding: 20px; background-color: var(--color-paper); border-radius: var(--radius-lg); }
    .form-section-title { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid var(--color-paper); padding-bottom: 10px; }
    .user-books, .moderation-queue { margin-top: 20px; }
    .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; margin-top: 20px; }
    .book-card { background-color: var(--color-white); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-paper); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); display: flex; flex-direction: column; position:relative; }
    .book-card.pending-deletion { border-left: 5px solid orange; }
    .book-card.pending-approval { border-left: 5px solid dodgerblue; }
    .book-cover-container { height: 200px; overflow: hidden; background-color: var(--color-light-gray); display: flex; align-items: center; justify-content: center; }
    .book-cover { width: 100%; height: 100%; object-fit: cover; }
    .book-cover-fallback svg { width: 50px; height: 50px; color: var(--color-gray); }
    .book-info { padding: 15px; flex-grow: 1; }
    .book-title { font-weight: 600; margin-bottom: 5px; font-size: 1.1rem; color: var(--color-darkwood); }
    .book-author, .book-submitter { color: var(--color-gray); margin-bottom: 10px; font-size: 0.9rem; }
    .book-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .book-tag { font-size: 0.7rem; padding: 2px 8px; background-color: var(--color-paper); border-radius: 20px; color: var(--color-darkwood); }
    .book-status, .book-condition-display { margin-top: 5px; font-size: 0.8rem;}
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-weight: 500; }
    .status-badge.pieejama { background-color: #e6f7e6; color: #2e7d32; }
    .status-badge.apmainīta { background-color: #e3f2fd; color: #1565c0; }
    .status-badge.dzēsta { background-color: #fbe9e7; color: #d32f2f; opacity: 0.7; }
    .status-badge.defektīva { background-color: #fff8e1; color: #ff8f00; }
    .status-badge.gaida-dzesanu { background-color: #fff3e0; color: #ef6c00; }
    .status-badge.gaida-apstiprinajumu { background-color: #e3f2fd; color: #0d47a1; } 
    .no-books-message { text-align: center; padding: 30px; color: var(--color-gray); background-color: var(--color-light-gray); border-radius: var(--radius-lg); margin-top: 20px; }
    .profile-photo-actions label.btn-outline { cursor: pointer; padding: var(--spacing-1) var(--spacing-3); font-size: 0.75rem; }
    .book-actions-footer { padding: 10px 15px; display: flex; gap: 10px; justify-content: space-around; margin-top: auto; border-top: 1px solid var(--color-paper);}
    .btn-small-action { padding: 5px 10px; font-size: 0.8rem; flex-grow:1; text-align:center; }
    .btn-delete-book { background-color: #ef5350; color: white;} .btn-delete-book:hover { background-color: #e53935;}
    .btn-restore-book { background-color: #66bb6a; color: white;} .btn-restore-book:hover { background-color: #4caf50;}
    .btn-approve-book { background-color: #42a5f5; color: white;} .btn-approve-book:hover { background-color: #1e88e5;}
    .btn-reject-book { background-color: #ef5350; color: white;} .btn-reject-book:hover { background-color: #e53935;}
    .countdown-timer { font-size: 0.8em; color: orange; text-align: center; padding: 5px 0; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
    .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; border: 1px solid #ddd; width: 80%; max-width: 500px; border-radius: var(--radius-lg); box-shadow: 0 5px 15px rgba(0,0,0,0.2); position: relative; }
    .close-button-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 20px; }
    .close-button-modal:hover, .close-button-modal:focus { color: #333; text-decoration: none; cursor: pointer; }
    .modal-content h3 { margin-top: 0; margin-bottom: 20px; font-family: var(--font-serif); color: var(--color-darkwood); }
    .modal-content p { margin-bottom: 15px; color: var(--color-gray); }
    .modal-content input[type="text"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--color-paper); border-radius: var(--radius-md); }
    .modal-footer-buttons { display: flex; justify-content: flex-end; gap: 10px; }
    #confirmDeleteBookBtnModal:disabled, #confirmRejectBookBtnModal:disabled { background-color: #ccc; cursor: not-allowed; }
  </style>
</head>
<body data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
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
                        $profilePicPath_header = $_SESSION['user_profile_photo'] ?? '';
                        $userNameInitial_header = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath_header) && (filter_var($profilePicPath_header, FILTER_VALIDATE_URL) || file_exists($profilePicPath_header))): ?>
                                        <img src="<?php echo htmlspecialchars($profilePicPath_header); ?>?t=<?php echo time(); ?>" alt="Profils">
                                    <?php else: ?>
                                        <div class="profile-button-placeholder-header">
                                            <?php echo htmlspecialchars($userNameInitial_header); ?>
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
        <div class="profile-header"><h1>Jūsu profils</h1></div>
        
        <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
            <?php if (!empty($success)): ?>
            <div class="toast show" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb; margin-bottom: 10px;">
                <div class="toast-content"><div class="toast-icon success" style="color: #155724;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div><p class="toast-message"><?php echo htmlspecialchars($success); ?></p></div>
                <button class="toast-close" onclick="this.parentElement.remove()" style="color: #155724; background: transparent; border: none; font-size: 1.5rem; position: absolute; top: 5px; right: 10px;">×</button>
            </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="auth-error active" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px; border-radius: .25rem; margin-bottom: 10px; position:relative;">
                <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                 <button class="toast-close" onclick="this.parentElement.remove()" style="background:transparent; border:none; font-size:1.5rem; color: #721c24; position:absolute; top:5px; right:10px;">×</button>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
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
                <span class="stat-number">
                    <?php 
                        $activeBookCount = 0;
                        if ($userRole !== 'Moderators' && $userRole !== 'Administrators') {
                             $activeBookCount = count(array_filter($displayBooks, function($book){ 
                                return $book['Status'] !== 'Dzēsta' && $book['Status'] !== 'Apmainīta'; 
                            }));
                        } else { // For moderators/admins, count books awaiting approval
                            $activeBookCount = count($displayBooks); // $displayBooks already filtered for them
                        }
                        echo $activeBookCount;
                    ?>
                </span>
                <span class="stat-label">
                    <?php echo ($userRole === 'Moderators' || $userRole === 'Administrators') ? 'Moderējamās grāmatas' : 'Manas grāmatas'; ?>
                </span>
              </div>
            </div>
          </div>
          
          <div class="profile-details">
            <form id="profileForm" method="POST" action="profile.php">
              <input type="hidden" name="action" value="update_profile">
              <div class="form-row">
                <div class="form-group"><label for="firstName">Vārds</label><input type="text" id="firstName" name="firstName" class="form-input" required value="<?php echo htmlspecialchars($firstName); ?>"></div>
                <div class="form-group"><label for="lastName">Uzvārds</label><input type="text" id="lastName" name="lastName" class="form-input" value="<?php echo htmlspecialchars($lastName); ?>"></div>
              </div>
              <div class="form-group"><label for="email">E-pasts</label><input type="email" id="email" class="form-input" value="<?php echo htmlspecialchars($userEmail); ?>" disabled></div>
              <div class="form-group"><button type="submit" class="btn-primary">Saglabāt izmaiņas</button></div>
            </form>
            
            <div class="form-section-title"><h3>Kontu drošība</h3></div>
            <button type="button" id="changePasswordBtn" class="btn-outline">Mainīt paroli</button>
            <div id="passwordChangeSection" class="password-change-section hidden">
              <form method="POST" action="profile.php"><input type="hidden" name="action" value="change_password">
                <div class="form-group"><label for="currentPassword">Pašreizējā parole</label><input type="password" id="currentPassword" name="currentPassword" class="form-input" required></div>
                <div class="form-group"><label for="newPassword">Jaunā parole</label><input type="password" id="newPassword" name="newPassword" class="form-input" required></div>
                <div class="form-group"><label for="confirmNewPassword">Apstipriniet</label><input type="password" id="confirmNewPassword" name="confirmNewPassword" class="form-input" required></div>
                <div class="form-actions"><button type="submit" class="btn-primary">Atjaunot</button><button type="button" id="cancelPasswordBtn" class="btn-outline">Atcelt</button></div>
              </form>
            </div>
            
            <?php if ($userRole === 'Moderators' || $userRole === 'Administrators'): ?>
                <div class="form-section-title"><h3>Grāmatu moderācija (Gaida apstiprinājumu)</h3></div>
                <div class="moderation-queue">
                    <?php if (empty($displayBooks)): ?>
                        <p class="no-books-message">Nav grāmatu, kas gaida apstiprinājumu.</p>
                    <?php else: ?>
                        <div class="books-grid">
                            <?php foreach ($displayBooks as $book): ?>
                                <div class="book-card pending-approval" id="mod-book-card-<?php echo $book['GramatasID']; ?>" data-bookid="<?php echo $book['GramatasID']; ?>" data-booktitle="<?php echo htmlspecialchars($book['Nosaukums']); ?>">
                                    <div class="book-cover-container">
                                         <?php 
                                          $mod_book_image_path = '';
                                          if (!empty($book['Attels'])) {
                                              if (filter_var($book['Attels'], FILTER_VALIDATE_URL)) $mod_book_image_path = htmlspecialchars($book['Attels']);
                                              elseif (file_exists($book['Attels'])) $mod_book_image_path = htmlspecialchars($book['Attels']);
                                          }
                                        ?>
                                        <?php if ($mod_book_image_path): ?>
                                          <img src="<?php echo $mod_book_image_path; ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($book['Nosaukums']); ?>" class="book-cover">
                                        <?php else: ?>
                                          <div class="book-cover-fallback"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($book['Nosaukums']); ?></h3>
                                        <p class="book-author">Autors: <?php echo htmlspecialchars($book['Autors']); ?></p>
                                        <p class="book-submitter">Iesniedza: <?php echo htmlspecialchars($book['IesniedzejsVards'] ?? 'Nezināms'); ?></p>
                                        <div class="book-tags">
                                            <span class="book-tag"><?php echo htmlspecialchars($book['Zanrs']); ?></span>
                                            <span class="book-tag"><?php echo htmlspecialchars($book['Stavoklis'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="book-status">Status: <span class="status-badge gaida-apstiprinajumu">Gaida apstiprinājumu</span></div>
                                    </div>
                                    <div class="book-actions-footer">
                                        <button type="button" class="btn btn-small-action btn-approve-book" onclick="moderateBook(<?php echo $book['GramatasID']; ?>, 'approve')">Apstiprināt</button>
                                        <button type="button" class="btn btn-small-action btn-reject-book" onclick="openRejectModal(<?php echo $book['GramatasID']; ?>, '<?php echo htmlspecialchars(addslashes($book['Nosaukums'])); ?>')">Noraidīt</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="form-section-title"><h3>Mana bibliotēka</h3></div>
                <button type="button" id="addBookBtn" class="btn-outline">Pievienot grāmatu</button>
                <div id="addBookSection" class="add-book-section hidden">
                    <form method="POST" action="profile.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_book">
                        <div class="form-group"><label for="bookTitleP">Nosaukums*</label><input type="text" id="bookTitleP" name="bookTitleP" class="form-input" required></div>
                        <div class="form-group"><label for="bookAuthorP">Autors*</label><input type="text" id="bookAuthorP" name="bookAuthorP" class="form-input" required></div>
                        <div class="form-row">
                             <div class="form-group">
                                <label for="bookGenreP">Žanrs*</label>
                                <select id="bookGenreP" name="bookGenreP" class="form-input" required>
                                    <option value="" disabled selected>Izvēlieties žanru...</option>
                                    <?php foreach ($genres_list as $genre_item): ?>
                                        <option value="<?php echo htmlspecialchars($genre_item); ?>"><?php echo htmlspecialchars($genre_item); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="bookLanguageP">Valoda*</label><input type="text" id="bookLanguageP" name="bookLanguageP" class="form-input" required></div>
                        </div>
                        <div class="form-group"><label for="bookYearP">Gads* (ne vēlāk par <?php echo date("Y"); ?>)</label><input type="number" id="bookYearP" name="bookYearP" class="form-input" min="1000" max="<?php echo date("Y"); ?>" required></div>
                        <div class="form-group">
                            <label for="bookConditionP">Stāvoklis*</label>
                            <select id="bookConditionP" name="bookConditionP" class="form-input" required>
                                <?php foreach ($conditions_list as $condition_item): ?>
                                    <option value="<?php echo htmlspecialchars($condition_item); ?>" <?php echo ($condition_item === 'Laba' ? 'selected' : ''); ?>><?php echo htmlspecialchars($condition_item); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="bookDescriptionP">Apraksts</label><textarea id="bookDescriptionP" name="bookDescriptionP" class="form-textarea" rows="3"></textarea></div>
                        <div class="form-group"><label for="bookImageP">Vāks</label><input type="file" id="bookImageP" name="bookImageP" class="form-input" accept="image/*"></div>
                        <div class="form-actions"><button type="submit" class="btn-primary">Pievienot</button><button type="button" id="cancelBookBtn" class="btn-outline">Atcelt</button></div>
                    </form>
                </div>
                <div class="user-books">
                    <?php if (empty($displayBooks)): ?>
                        <p class="no-books-message">Jūsu bibliotēkā vēl nav grāmatu.</p>
                    <?php else: ?>
                        <div class="books-grid">
                            <?php foreach ($displayBooks as $book): ?>
                                <div class="book-card <?php echo $book['Status'] === 'Gaida dzēšanu' ? 'pending-deletion' : ($book['Status'] === 'Gaida apstiprinājumu' ? 'pending-approval' : ''); ?>" 
                                     id="book-card-<?php echo $book['GramatasID']; ?>" 
                                     data-bookid="<?php echo $book['GramatasID']; ?>" 
                                     data-booktitle="<?php echo htmlspecialchars($book['Nosaukums']); ?>" 
                                     data-original-status="<?php echo htmlspecialchars($book['Status']); ?>"
                                     data-delete-until="<?php echo $book['Status'] === 'Gaida dzēšanu' && $book['GaidaDzesanuLidz'] ? strtotime($book['GaidaDzesanuLidz']) * 1000 : ''; ?>">
                                    <div class="book-cover-container">
                                         <?php 
                                          $user_book_image_path = '';
                                          if (!empty($book['Attels'])) {
                                              if (filter_var($book['Attels'], FILTER_VALIDATE_URL)) $user_book_image_path = htmlspecialchars($book['Attels']);
                                              elseif (file_exists($book['Attels'])) $user_book_image_path = htmlspecialchars($book['Attels']);
                                          }
                                        ?>
                                        <?php if ($user_book_image_path): ?>
                                          <img src="<?php echo $user_book_image_path; ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($book['Nosaukums']); ?>" class="book-cover">
                                        <?php else: ?>
                                          <div class="book-cover-fallback"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($book['Nosaukums']); ?></h3>
                                        <p class="book-author"><?php echo htmlspecialchars($book['Autors']); ?></p>
                                        <div class="book-tags">
                                            <span class="book-tag"><?php echo htmlspecialchars($book['Zanrs']); ?></span>
                                            <span class="book-tag"><?php echo htmlspecialchars($book['Stavoklis'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="book-status">Status: <span class="status-badge <?php echo htmlspecialchars(strtolower(str_replace(['ā', 'ū', ' '], ['a', 'u', '-'], $book['Status']))); ?>"><?php echo htmlspecialchars($book['Status']); ?></span></div>
                                        <div class="countdown-timer" id="timer-<?php echo $book['GramatasID']; ?>"></div>
                                    </div>
                                    <?php if ($book['Status'] === 'Pieejama' || $book['Status'] === 'Gaida dzēšanu' || $book['Status'] === 'Gaida apstiprinājumu'): ?>
                                    <div class="book-actions-footer">
                                        <?php if ($book['Status'] === 'Pieejama' || $book['Status'] === 'Gaida apstiprinājumu'): ?>
                                            <button type="button" class="btn btn-small-action btn-delete-book" onclick="openDeleteModal(<?php echo $book['GramatasID']; ?>, '<?php echo htmlspecialchars(addslashes($book['Nosaukums'])); ?>')">Dzēst</button>
                                        <?php endif; ?>
                                        <?php if ($book['Status'] === 'Gaida dzēšanu' ): ?>
                                            <button type="button" class="btn btn-small-action btn-restore-book" onclick="restoreBook(<?php echo $book['GramatasID']; ?>)">Atjaunot</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

    <div id="deleteBookModal" class="modal">
        <div class="modal-content">
            <span class="close-button-modal" onclick="closeDeleteModal()">×</span>
            <h3>Apstiprināt grāmatas dzēšanu</h3>
            <p>Lai apstiprinātu "<strong id="modalBookTitle"></strong>" dzēšanu, ievadiet nosaukumu:</p>
            <input type="text" id="confirmBookTitleInput" placeholder="Grāmatas nosaukums">
            <p style="font-size:0.9em; color:var(--color-gray);">Pēc apstiprināšanas būs 5 minūtes, lai atjaunotu.</p>
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Atcelt</button>
                <button type="button" id="confirmDeleteBookBtnModal" class="btn-primary" disabled>Apstiprināt dzēšanu</button>
            </div>
        </div>
    </div>
    <div id="rejectBookModal" class="modal">
        <div class="modal-content">
            <span class="close-button-modal" onclick="closeRejectModal()">×</span>
            <h3>Apstiprināt noraidīšanu</h3>
            <p>Lai noraidītu un dzēstu "<strong id="modalRejectBookTitle"></strong>", ievadiet nosaukumu:</p>
            <input type="text" id="confirmRejectBookTitleInput" placeholder="Grāmatas nosaukums">
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Atcelt</button>
                <button type="button" id="confirmRejectBookBtnModal" class="btn-primary" disabled>Noraidīt un Dzēst</button>
            </div>
        </div>
    </div>
  
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            <h2 class="brand-name">BookSwap</h2>
          </a>
          <p>Saistieties ar citiem lasītājiem un apmainieties ar grāmatām, kuras jūs mīlat.</p>
          <div class="social-links">
            <a href="#" class="social-link"><svg width="20" height="20" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="none" stroke="currentColor" stroke-width="2"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" fill="none" stroke="currentColor" stroke-width="2"></line></svg></a>
            <a href="#" class="social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
            <a href="#" class="social-link"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
          </div>
        </div>
        <div class="footer-links"><h3 class="footer-title">Ātrās saites</h3><ul><li><a href="browse.php">Pārlūkot</a></li><li><a href="how-it-works.php">Kā darbojas</a></li><li><a href="signup.php">Reģistrēties</a></li><li><a href="login.php">Pieslēgties</a></li></ul></div>
        <div class="footer-links"><h3 class="footer-title">Palīdzība</h3><ul><li><a href="faq.php">BUJ</a></li><li><a href="contact-us.php">Kontakti</a></li><li><a href="safety-tips.php">Drošība</a></li><li><a href="report-issue.php">Ziņot</a></li></ul></div>
        <div class="footer-links"><h3 class="footer-title">Juridiskā info</h3><ul><li><a href="terms.php">Noteikumi</a></li><li><a href="privacy-policy.php">Privātums</a></li><li><a href="cookies.php">Sīkdatnes</a></li><li><a href="gdpr.php">VDAR</a></li></ul></div>
      </div>
      <div class="footer-bottom"><p>© <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p></div>
    </div>
  </footer>
  <script src="script.js"></script>
  <script src="profile_js.js"></script> 
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastContainer = document.getElementById('toast-container');
        const existingToasts = toastContainer ? toastContainer.querySelectorAll('.toast.show, .auth-error.active') : [];
        existingToasts.forEach(toast => { setTimeout(() => { if(toast.parentElement) toast.remove(); }, 5000); });
        
        const currentYearSpan = document.getElementById('currentYear');
        if(currentYearSpan) currentYearSpan.textContent = new Date().getFullYear();

        const deleteModal = document.getElementById('deleteBookModal');
        const confirmBookTitleInput = document.getElementById('confirmBookTitleInput');
        const confirmDeleteBookBtnModal = document.getElementById('confirmDeleteBookBtnModal');
        const modalBookTitleSpan = document.getElementById('modalBookTitle');
        let currentBookIdToDelete = null;
        let currentBookTitleToConfirm = "";
        let bookOriginalStatusBeforePendingDelete = 'Pieejama'; 

        window.openDeleteModal = function(bookId, bookTitle) {
            currentBookIdToDelete = bookId;
            currentBookTitleToConfirm = bookTitle;
            const card = document.getElementById(`book-card-${bookId}`);
            if (card) bookOriginalStatusBeforePendingDelete = card.dataset.originalStatus || 'Pieejama';

            if(modalBookTitleSpan) modalBookTitleSpan.textContent = bookTitle;
            if(confirmBookTitleInput) confirmBookTitleInput.value = '';
            if(confirmDeleteBookBtnModal) confirmDeleteBookBtnModal.disabled = true;
            if(deleteModal) deleteModal.style.display = 'block';
        }
        window.closeDeleteModal = function() {
            if(deleteModal) deleteModal.style.display = 'none';
        }
        if(confirmBookTitleInput && confirmDeleteBookBtnModal) {
            confirmBookTitleInput.addEventListener('input', function() {
                confirmDeleteBookBtnModal.disabled = this.value.trim().toLowerCase() !== currentBookTitleToConfirm.toLowerCase();
            });
        }
        if(confirmDeleteBookBtnModal) {
            confirmDeleteBookBtnModal.addEventListener('click', function() {
                if (currentBookIdToDelete && confirmBookTitleInput.value.trim().toLowerCase() === currentBookTitleToConfirm.toLowerCase()) {
                    scheduleBookDeletion(currentBookIdToDelete);
                    closeDeleteModal();
                }
            });
        }
        
        function scheduleBookDeletion(bookId) {
            const formData = new FormData();
            formData.append('ajax_action', 'schedule_delete_book');
            formData.append('book_id', bookId);
            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    updateBookCardToPending(bookId, data.delete_until_timestamp);
                } else { showUIMessage(data.message || 'Kļūda.', 'error'); }
            }).catch(error => showUIMessage('Tīkla kļūda.', 'error'));
        }

        window.restoreBook = function(bookId) {
            const formData = new FormData();
            formData.append('ajax_action', 'restore_book');
            formData.append('book_id', bookId);
            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    updateBookCardToNormal(bookId, data.new_status); 
                } else { showUIMessage(data.message || 'Kļūda.', 'error'); }
            }).catch(error => showUIMessage('Tīkla kļūda.', 'error'));
        }

        let countdownIntervals = {};
        function updateBookCardToPending(bookId, deleteUntilTimestamp) {
            const card = document.getElementById(`book-card-${bookId}`);
            if (!card) return;
            card.classList.remove('pending-approval'); 
            card.classList.add('pending-deletion');
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) { statusBadge.className = 'status-badge gaida-dzesanu'; statusBadge.textContent = 'Gaida dzēšanu'; }
            const actionsFooter = card.querySelector('.book-actions-footer');
            if (actionsFooter) actionsFooter.innerHTML = `<button type="button" class="btn btn-small-action btn-restore-book" onclick="restoreBook(${bookId})">Atjaunot</button>`;
            const timerDisplay = card.querySelector(`#timer-${bookId}`);
            if (timerDisplay && deleteUntilTimestamp) {
                if (countdownIntervals[bookId]) clearInterval(countdownIntervals[bookId]);
                countdownIntervals[bookId] = setInterval(function() { 
                    const now = new Date().getTime(); const distance = deleteUntilTimestamp - now;
                    if (distance < 0) {
                        clearInterval(countdownIntervals[bookId]); timerDisplay.textContent = "Dzēšanas laiks beidzies.";
                        if(actionsFooter && actionsFooter.querySelector('.btn-restore-book')) actionsFooter.querySelector('.btn-restore-book').remove();
                        if(statusBadge) { statusBadge.className = 'status-badge dzesta'; statusBadge.textContent = 'Dzēsta'; }
                        card.style.opacity = '0.6'; return;
                    }
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timerDisplay.textContent = `Līdz dzēšanai: ${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
                }, 1000);
            }
        }
        
        document.querySelectorAll('.book-card.pending-deletion').forEach(card => {
            const bookId = card.dataset.bookid; const deleteUntil = parseInt(card.dataset.deleteUntil, 10);
            if (bookId && deleteUntil && deleteUntil > new Date().getTime()) { updateBookCardToPending(bookId, deleteUntil); } 
            else if (bookId && deleteUntil && deleteUntil <= new Date().getTime()) {
                const timerDisplay = card.querySelector(`#timer-${bookId}`); if(timerDisplay) timerDisplay.textContent = "Dzēšanas laiks beidzies.";
                 const statusBadge = card.querySelector('.status-badge'); if (statusBadge) { statusBadge.className = 'status-badge dzesta'; statusBadge.textContent = 'Dzēsta'; }
                const actionsFooter = card.querySelector('.book-actions-footer'); if(actionsFooter && actionsFooter.querySelector('.btn-restore-book')) actionsFooter.querySelector('.btn-restore-book').remove();
                card.style.opacity = '0.6';
            }
        });

        function updateBookCardToNormal(bookId, newStatusText) {
            const card = document.getElementById(`book-card-${bookId}`); if (!card) return;
            if (countdownIntervals[bookId]) { clearInterval(countdownIntervals[bookId]); delete countdownIntervals[bookId]; }
            card.classList.remove('pending-deletion', 'pending-approval'); card.style.opacity = '1';
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) { 
                let statusClass = newStatusText.toLowerCase().replace(/ā/g, 'a').replace(/ū/g, 'u').replace(/ /g, '-');
                statusBadge.className = `status-badge ${statusClass}`; 
                statusBadge.textContent = newStatusText;
            }
            const timerDisplay = card.querySelector(`#timer-${bookId}`); if(timerDisplay) timerDisplay.textContent = '';
            const actionsFooter = card.querySelector('.book-actions-footer');
            const bookTitle = card.dataset.booktitle || 'grāmatu';
            if (actionsFooter) {
                let buttonsHtml = '';
                if (newStatusText === 'Pieejama' || newStatusText === 'Gaida apstiprinājumu') {
                    buttonsHtml = `<button type="button" class="btn btn-small-action btn-delete-book" onclick="openDeleteModal(${bookId}, '${bookTitle.replace(/'/g, "\\'")}')">Dzēst</button>`;
                }
                actionsFooter.innerHTML = buttonsHtml;
            }
        }

        const rejectModal = document.getElementById('rejectBookModal');
        const confirmRejectBookTitleInput = document.getElementById('confirmRejectBookTitleInput');
        const confirmRejectBookBtnModal = document.getElementById('confirmRejectBookBtnModal');
        const modalRejectBookTitleSpan = document.getElementById('modalRejectBookTitle');
        let currentBookIdToReject = null;
        let currentBookTitleToConfirmReject = "";

        window.openRejectModal = function(bookId, bookTitle) {
            currentBookIdToReject = bookId; currentBookTitleToConfirmReject = bookTitle;
            if(modalRejectBookTitleSpan) modalRejectBookTitleSpan.textContent = bookTitle;
            if(confirmRejectBookTitleInput) confirmRejectBookTitleInput.value = '';
            if(confirmRejectBookBtnModal) confirmRejectBookBtnModal.disabled = true;
            if(rejectModal) rejectModal.style.display = 'block';
        }
        window.closeRejectModal = function() { if(rejectModal) rejectModal.style.display = 'none'; }

        if(confirmRejectBookTitleInput && confirmRejectBookBtnModal) {
            confirmRejectBookTitleInput.addEventListener('input', function() {
                confirmRejectBookBtnModal.disabled = this.value.trim().toLowerCase() !== currentBookTitleToConfirmReject.toLowerCase();
            });
        }
        if(confirmRejectBookBtnModal) {
            confirmRejectBookBtnModal.addEventListener('click', function() {
                if (currentBookIdToReject && confirmRejectBookTitleInput.value.trim().toLowerCase() === currentBookTitleToConfirmReject.toLowerCase()) {
                    moderateBook(currentBookIdToReject, 'reject');
                    closeRejectModal();
                }
            });
        }
        
        window.moderateBook = function(bookId, moderationAction) {
            const formData = new FormData();
            formData.append('ajax_action', moderationAction + '_book');
            formData.append('book_id', bookId);
            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    const cardToRemove = document.getElementById(`mod-book-card-${bookId}`);
                    if (cardToRemove) cardToRemove.remove();
                    const modQueueGrid = document.querySelector('.moderation-queue .books-grid');
                    if (modQueueGrid && modQueueGrid.children.length === 0) {
                        let noBooksMsgEl = document.querySelector('.moderation-queue .no-books-message');
                        if (!noBooksMsgEl) {
                            noBooksMsgEl = document.createElement('p');
                            noBooksMsgEl.className = 'no-books-message';
                            document.querySelector('.moderation-queue').appendChild(noBooksMsgEl);
                        }
                        noBooksMsgEl.textContent = 'Nav grāmatu, kas gaida apstiprinājumu.';
                        noBooksMsgEl.style.display = 'block';
                    }
                } else { showUIMessage(data.message || 'Kļūda.', 'error'); }
            }).catch(error => showUIMessage('Tīkla kļūda.', 'error'));
        }

        function showUIMessage(message, type = 'success') {
            const container = document.getElementById('toast-container') || document.body;
            const toastDiv = document.createElement('div');
            toastDiv.className = `toast show ${type === 'error' ? 'auth-error active' : ''}`;
            toastDiv.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            toastDiv.style.color = type === 'success' ? '#155724' : '#721c24';
            toastDiv.style.borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            if(type === 'error' && !toastDiv.classList.contains('auth-error')) toastDiv.style.padding = '15px';
            let iconSvg = type === 'success' ? 
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' :
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            toastDiv.innerHTML = `
                <div class="toast-content" style="display:flex; align-items:center;">
                    <div class="toast-icon" style="margin-right:10px; color: ${type === 'success' ? '#155724' : '#721c24'};">${iconSvg}</div>
                    <p class="toast-message" style="margin:0;">${message}</p>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()" style="background:none; border:none; font-size:1.2rem; line-height:1; color: ${type === 'success' ? '#155724' : '#721c24'}; position:absolute; top:50%; right:15px; transform:translateY(-50%);">×</button>`;
            toastDiv.style.padding = '15px'; toastDiv.style.borderRadius = '.25rem'; toastDiv.style.marginBottom = '10px'; toastDiv.style.position = 'relative'; 
            if(toastContainer){ toastContainer.appendChild(toastDiv); } 
            else { toastDiv.style.position = 'fixed'; toastDiv.style.top = '20px'; toastDiv.style.right = '20px'; toastDiv.style.zIndex = '1050'; document.body.appendChild(toastDiv); }
            setTimeout(() => { toastDiv.remove(); }, 5000);
        }
    });
  </script>

  <div id="chat-widget-container">
    <div id="chat-toggle-button" title="Atvērt čatu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span id="chat-global-unread-badge" class="hidden"></span>
    </div>

    <div id="chat-window" class="hidden">
        <div id="chat-header">
            <button id="chat-back-button" class="hidden" title="Atpakaļ uz sarakstēm">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <span id="chat-window-title">Sarunas</span>
            <button id="chat-close-button" title="Aizvērt čatu">×</button>
        </div>
        <div id="chat-body">
            <div id="chat-conversation-list">
                <!-- Conversations will be loaded here by JS -->
                <div class="loading-spinner hidden"><div class="spinner"></div></div>
            </div>
            <div id="chat-message-area" class="hidden">
                <div id="chat-messages-display">
                    <!-- Messages will be loaded here by JS -->
                     <div class="loading-spinner hidden"><div class="spinner"></div></div>
                </div>
                <form id="chat-message-form">
                    <input type="text" id="chat-message-input" placeholder="Rakstiet ziņu..." autocomplete="off" disabled>
                    <button type="submit" id="chat-send-button" disabled>Sūtīt</button>
                </form>
            </div>
             <div id="chat-no-conversation-selected">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <p>Izvēlieties sarunu, lai skatītu ziņas.</p>
            </div>
        </div>
    </div>
</div>
<!-- Chat Widget End -->

<!-- Подключаем CSS и JS для чата -->
<link rel="stylesheet" href="chat.css?v=<?php echo time(); // Cache busting ?>">
<script src="chat.js?v=<?php echo time(); // Cache busting ?>"></script>

</body>
</html>