<?php
require_once 'session_check.php';
redirectIfNotLoggedIn();
require_once 'connect_db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$profilePhoto = $_SESSION['user_profile_photo'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'Registrēts';
$userAverageRating = 0.0; // Initialize user average rating

// ... (genres_list, conditions_list, current_admins_list ielāde paliek nemainīga) ...
$genres_list = [
    "Daiļliteratūra", "Fantāzija", "Zinātniskā fantastika", "Detektīvs", 
    "Trilleris", "Romāns", "Šausmas", "Biogrāfija", "Vēsture", 
    "Pašpalīdzība", "Atmiņu stāsts", "Dzeja", "Cits"
];
$conditions_list = ["Kā jauna", "Ļoti laba", "Laba", "Pieņemama"];

$current_admins_list = [];
if ($userRole === 'Moderators') {
    if ($savienojums) { 
        $stmt_get_admins = $savienojums->prepare("SELECT LietotajsID, Lietotajvards, E_pasts, ProfilaAttels FROM bookswap_users WHERE Loma = 'Administrators'");
        if ($stmt_get_admins) {
            $stmt_get_admins->execute();
            $result_admins = $stmt_get_admins->get_result();
            while ($admin_row = $result_admins->fetch_assoc()) {
                if (!empty($admin_row['ProfilaAttels'])) {
                    if (!filter_var($admin_row['ProfilaAttels'], FILTER_VALIDATE_URL) && !file_exists($admin_row['ProfilaAttels'])) {
                        $admin_row['ProfilaAttels'] = ''; 
                    }
                }
                $current_admins_list[] = $admin_row;
            }
            $stmt_get_admins->close();
        } else { $errors[] = "Kļūda ielādējot administratoru sarakstu: " . $savienojums->error; }
    }
}

$wishlist_books_for_js = [];
if ($userRole === 'Registrēts') {
    if ($savienojums) {
        $stmt_wishlist = $savienojums->prepare("
            SELECT b.GramatasID, b.Nosaukums, b.Autors, b.Attels, b.Stavoklis, b.Zanrs, w.PievienosanasDatums 
            FROM bookswap_wishlist w
            JOIN bookswap_books b ON w.GramatasID = b.GramatasID
            WHERE w.LietotajsID = ?
            ORDER BY w.PievienosanasDatums DESC
        ");
        if ($stmt_wishlist) {
            $stmt_wishlist->bind_param("i", $userId);
            $stmt_wishlist->execute();
            $result_wishlist = $stmt_wishlist->get_result();
            while ($wish_row = $result_wishlist->fetch_assoc()) {
                $wish_cover_path = '';
                 if (!empty($wish_row['Attels'])) {
                    if (filter_var($wish_row['Attels'], FILTER_VALIDATE_URL)) $wish_cover_path = htmlspecialchars($wish_row['Attels']);
                    elseif (file_exists($wish_row['Attels'])) $wish_cover_path = htmlspecialchars($wish_row['Attels']);
                }
                $wish_row['Attels'] = $wish_cover_path;
                $wishlist_books_for_js[] = $wish_row;
            }
            $stmt_wishlist->close();
        } else { $errors[] = "Kļūda ielādējot vēlmju sarakstu: " . $savienojums->error; }
    }
}

$completed_exchanges_for_review = [];
if ($userRole === 'Registrēts') { 
    if ($savienojums) {
        $stmt_completed_exchanges = $savienojums->prepare("
            SELECT 
                er.PieprasijumaID, er.IniciatorsID, er.AdresatsID, er.PiedavatGramataID, er.VelamaiGramataID, er.Status,
                u_initiator.Lietotajvards AS IniciatorsVards,
                u_adresats.Lietotajvards AS AdresatsVards,
                b_offered.Nosaukums AS PiedavataGramataNosaukums,
                b_requested.Nosaukums AS VelamaGramataNosaukums,
                (SELECT COUNT(*) FROM bookswap_user_reviews ur 
                 WHERE ur.ApmaijnaPieprasijumaID = er.PieprasijumaID AND ur.VertejsID = ?) AS reviews_given_by_current_user
            FROM bookswap_exchange_requests er
            JOIN bookswap_users u_initiator ON er.IniciatorsID = u_initiator.LietotajsID
            JOIN bookswap_users u_adresats ON er.AdresatsID = u_adresats.LietotajsID
            JOIN bookswap_books b_offered ON er.PiedavatGramataID = b_offered.GramatasID
            JOIN bookswap_books b_requested ON er.VelamaiGramataID = b_requested.GramatasID
            WHERE (er.IniciatorsID = ? OR er.AdresatsID = ?) AND er.Status = 'Apstiprināts' 
            ORDER BY er.IzveidotsDatums DESC
        "); 
        
        if ($stmt_completed_exchanges) {
            $stmt_completed_exchanges->bind_param("iii", $userId, $userId, $userId);
            $stmt_completed_exchanges->execute();
            $result_completed = $stmt_completed_exchanges->get_result();
            while ($ex_row = $result_completed->fetch_assoc()) {
                if ($ex_row['IniciatorsID'] == $userId) {
                    $ex_row['other_user_id'] = $ex_row['AdresatsID'];
                    $ex_row['other_user_name'] = $ex_row['AdresatsVards'];
                    $ex_row['book_they_got_title'] = $ex_row['PiedavataGramataNosaukums'];
                    $ex_row['book_you_got_title'] = $ex_row['VelamaGramataNosaukums'];
                } else {
                    $ex_row['other_user_id'] = $ex_row['IniciatorsID'];
                    $ex_row['other_user_name'] = $ex_row['IniciatorsVards'];
                    $ex_row['book_they_got_title'] = $ex_row['VelamaGramataNosaukums'];
                    $ex_row['book_you_got_title'] = $ex_row['PiedavataGramataNosaukums'];
                }
                $stmt_check_review = $savienojums->prepare("SELECT AtsauksmeID FROM bookswap_user_reviews WHERE ApmaijnaPieprasijumaID = ? AND VertejsID = ? AND VertejamaisLietotajsID = ?");
                $stmt_check_review->bind_param("iii", $ex_row['PieprasijumaID'], $userId, $ex_row['other_user_id']);
                $stmt_check_review->execute();
                $stmt_check_review->store_result();
                $ex_row['can_review_other_user'] = ($stmt_check_review->num_rows == 0);
                $stmt_check_review->close();
                $completed_exchanges_for_review[] = $ex_row;
            }
            $stmt_completed_exchanges->close();
        } else { $errors[] = "Kļūda ielādējot pabeigtās maiņas: " . $savienojums->error; }
    }
}

// AJAX request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // ... (visa AJAX apstrādes loģika paliek nemainīga, kā iepriekšējā atbildē) ...
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Nezināma kļūda.'];
    if (!$savienojums) { $response['message'] = 'DB savienojuma kļūda.'; echo json_encode($response); exit; }

    $book_id_ajax = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
    
    $can_moderate_local = ($userRole === 'Moderators' || $userRole === 'Administrators');
    $is_owner = false;
    $book_owner_id = null;

    if ($book_id_ajax > 0) { 
        $checkStmt = $savienojums->prepare("SELECT LietotajsID, Status FROM bookswap_books WHERE GramatasID = ?");
        if($checkStmt) {
            $checkStmt->bind_param("i", $book_id_ajax);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($bookRow = $checkResult->fetch_assoc()) {
                $book_owner_id = $bookRow['LietotajsID'];
                if ($book_owner_id == $userId) {
                    $is_owner = true;
                }
            } else {
                if (!in_array($_POST['ajax_action'], ['create_exchange_request', 'handle_exchange_request', 'update_report_status', 'promote_to_admin', 'demote_admin', 'toggle_wishlist', 'check_wishlist_status', 'submit_user_review'])) {
                     $response['message'] = 'Grāmata nav atrasta.'; echo json_encode($response); $checkStmt->close(); exit;
                }
            }
            $checkStmt->close();
        } else {
            $response['message'] = 'DB kļūda (pārbaude).'; echo json_encode($response); exit;
        }
    }

    switch ($_POST['ajax_action']) {
        case 'schedule_delete_book':
            if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; break; }
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
            if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; break; }
            if (!$is_owner) { $response['message'] = 'Jums nav tiesību atjaunot šo grāmatu.'; break; }
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
            if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; break; }
            if (!$can_moderate_local) { $response['message'] = 'Jums nav tiesību apstiprināt grāmatas.'; break; }
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
            if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; break; }
            if (!$can_moderate_local) { $response['message'] = 'Jums nav tiesību noraidīt grāmatas.'; break; }
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
                    if (!empty($image_path_reject) && file_exists($image_path_reject) && strpos($image_path_reject, 'default') === false && strpos($image_path_reject, 'http') !== 0) {
                        unlink($image_path_reject); 
                    }
                    $response = ['success' => true, 'message' => 'Grāmata noraidīta un dzēsta.'];
                } else { $response['message'] = 'Neizdevās noraidīt/dzēst grāmatu vai tā jau ir apstrādāta.'; }
                $stmt_reject->close();
            } else {$response['message'] = 'DB kļūda (noraidīt).';}
            break;
        case 'update_report_status':
            if (!$can_moderate_local) { $response['message'] = 'Jums nav tiesību mainīt ziņojuma statusu.'; break; }
            $report_id_ajax = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
            $new_status_ajax = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
            $allowed_statuses = ['Apstrādāts', 'Izmeklēšanā', 'Atrisināts', 'Slēgts', 'Dzēsts'];
            if ($report_id_ajax <= 0 || empty($new_status_ajax) || !in_array($new_status_ajax, $allowed_statuses)) {
                $response['message'] = 'Nederīgi dati statusa maiņai.'; break;
            }
            if ($new_status_ajax === 'Dzēsts') {
                 $stmt_update_rep = $savienojums->prepare("DELETE FROM bookswap_issue_reports WHERE report_id = ?");
                 if($stmt_update_rep){
                    $stmt_update_rep->bind_param("i", $report_id_ajax);
                    if ($stmt_update_rep->execute() && $stmt_update_rep->affected_rows > 0) { $response = ['success' => true, 'message' => 'Ziņojums dzēsts.']; } 
                    else { $response['message'] = 'Neizdevās dzēst ziņojumu.'; }
                    $stmt_update_rep->close();
                } else {$response['message'] = 'DB kļūda (dzēst ziņojumu).';}
            } else { 
                $stmt_update_rep = $savienojums->prepare("UPDATE bookswap_issue_reports SET status = ? WHERE report_id = ?");
                if($stmt_update_rep){
                    $stmt_update_rep->bind_param("si", $new_status_ajax, $report_id_ajax);
                    if ($stmt_update_rep->execute() && $stmt_update_rep->affected_rows > 0) { $response = ['success' => true, 'message' => 'Ziņojuma statuss atjaunināts.'];} 
                    else { $response['message'] = 'Neizdevās atjaunināt ziņojuma statusu.'; }
                    $stmt_update_rep->close();
                } else {$response['message'] = 'DB kļūda (atj. ziņ. statusu).';}
            }
            break;
        case 'create_exchange_request':
            if (!isLoggedIn()) { $response['message'] = 'Lūdzu, pieslēdzieties.'; break;}
            $offered_book_id = isset($_POST['offered_book_id']) ? intval($_POST['offered_book_id']) : 0;
            $requested_book_id = isset($_POST['requested_book_id']) ? intval($_POST['requested_book_id']) : 0;
            $book_owner_id_req = isset($_POST['book_owner_id']) ? intval($_POST['book_owner_id']) : 0;
            $message_text = isset($_POST['message_text']) ? trim($_POST['message_text']) : '';
            $initiator_id = $_SESSION['user_id'];

            if ($offered_book_id <= 0 || $requested_book_id <= 0 || $book_owner_id_req <= 0) { $response['message'] = 'Nederīgi grāmatas dati.'; break; }
            if ($initiator_id == $book_owner_id_req) { $response['message'] = 'Jūs nevarat pieprasīt apmaiņu pats ar sevi.'; break; }
            
            $stmt_create_req = $savienojums->prepare("INSERT INTO bookswap_exchange_requests (IniciatorsID, AdresatsID, PiedavatGramataID, VelamaiGramataID, Status, IzveidotsDatums, ApmaijnasTekets) VALUES (?, ?, ?, ?, 'Gaida', NOW(), ?)");
            if ($stmt_create_req) {
                $stmt_create_req->bind_param("iiiis", $initiator_id, $book_owner_id_req, $offered_book_id, $requested_book_id, $message_text);
                if ($stmt_create_req->execute()) { $response = ['success' => true, 'message' => 'Maiņas pieprasījums nosūtīts!']; } 
                else { $response['message'] = 'Kļūda veidojot maiņas pieprasījumu: ' . $stmt_create_req->error; }
                $stmt_create_req->close();
            } else { $response['message'] = 'DB kļūda (izveidot pieprasījumu).'; }
            break;
        case 'handle_exchange_request':
            if (!isLoggedIn()) { $response['message'] = 'Lūdzu, pieslēdzieties.'; break;}
            $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
            $decision = isset($_POST['decision']) ? $_POST['decision'] : '';
            $adresats_id_handle = $_SESSION['user_id'];

            if ($request_id <= 0 || !in_array($decision, ['approve', 'reject'])) { $response['message'] = 'Nederīgi dati.'; break; }
            
            $stmt_get_req = $savienojums->prepare("SELECT IniciatorsID, PiedavatGramataID, VelamaiGramataID FROM bookswap_exchange_requests WHERE PieprasijumaID = ? AND AdresatsID = ? AND Status = 'Gaida'");
            if ($stmt_get_req) {
                $stmt_get_req->bind_param("ii", $request_id, $adresats_id_handle);
                $stmt_get_req->execute();
                $result_req = $stmt_get_req->get_result();
                if ($request_data = $result_req->fetch_assoc()) {
                    $new_status_req = ($decision === 'approve') ? 'Apstiprināts' : 'Noraidīts';
                    $savienojums->begin_transaction();
                    try {
                        $stmt_update_req_status = $savienojums->prepare("UPDATE bookswap_exchange_requests SET Status = ? WHERE PieprasijumaID = ?");
                        $stmt_update_req_status->bind_param("si", $new_status_req, $request_id); $stmt_update_req_status->execute(); $stmt_update_req_status->close();
                        if ($decision === 'approve') {
                            $stmt_update_book1 = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Apmainīta' WHERE GramatasID = ?");
                            $stmt_update_book1->bind_param("i", $request_data['PiedavatGramataID']); $stmt_update_book1->execute(); $stmt_update_book1->close();
                            $stmt_update_book2 = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Apmainīta' WHERE GramatasID = ?");
                            $stmt_update_book2->bind_param("i", $request_data['VelamaiGramataID']); $stmt_update_book2->execute(); $stmt_update_book2->close();
                        }
                        $savienojums->commit();
                        $response = ['success' => true, 'message' => 'Pieprasījums ' . ($decision === 'approve' ? 'apstiprināts' : 'noraidīts') . '.'];
                        if ($decision === 'approve') {
                             $response['initiator_id'] = $request_data['IniciatorsID'];
                             $stmt_get_initiator_name = $savienojums->prepare("SELECT Lietotajvards FROM bookswap_users WHERE LietotajsID = ?");
                             if($stmt_get_initiator_name){
                                $stmt_get_initiator_name->bind_param("i", $request_data['IniciatorsID']); $stmt_get_initiator_name->execute();
                                $res_name = $stmt_get_initiator_name->get_result();
                                if($name_row = $res_name->fetch_assoc()){ $response['initiator_name'] = $name_row['Lietotajvards']; }
                                $stmt_get_initiator_name->close();
                             }
                        }
                    } catch (Exception $e) { $savienojums->rollback(); $response['message'] = 'Kļūda apstrādājot pieprasījumu: ' . $e->getMessage(); }
                } else { $response['message'] = 'Pieprasījums nav atrasts, nav adresēts jums, vai jau apstrādāts.'; }
                $stmt_get_req->close();
            } else { $response['message'] = 'DB kļūda (saņemt pieprasījumu).'; }
            break;
        case 'promote_to_admin':
            if ($userRole !== 'Moderators') { 
                $response['message'] = 'Jums nav tiesību veikt šo darbību.';
                break;
            }
            $target_email_promote = isset($_POST['target_email']) ? trim($_POST['target_email']) : '';
        
            if (empty($target_email_promote) || !filter_var($target_email_promote, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Lūdzu, ievadiet derīgu e-pasta adresi.';
                break;
            }
        
            $stmt_find_user_promote = $savienojums->prepare("SELECT LietotajsID, Loma FROM bookswap_users WHERE E_pasts = ?");
            if (!$stmt_find_user_promote) { $response['message'] = 'DB kļūda (lietotāja meklēšana).'; break; }
            $stmt_find_user_promote->bind_param("s", $target_email_promote);
            $stmt_find_user_promote->execute();
            $result_find_user_promote = $stmt_find_user_promote->get_result();
        
            if ($target_user_data_promote = $result_find_user_promote->fetch_assoc()) {
                $target_user_id_promote = $target_user_data_promote['LietotajsID'];
                $target_user_current_role = $target_user_data_promote['Loma'];
        
                if ($target_user_id_promote == $userId) { 
                    $response['message'] = 'Jūs nevarat paaugstināt pats sevi.';
                } elseif ($target_user_current_role === 'Administrators') {
                    $response['message'] = 'Lietotājs jau ir administrators.';
                } elseif ($target_user_current_role === 'Moderators') { 
                    $response['message'] = 'Moderatori nevar tikt paaugstināti par administratoriem šādā veidā.';
                } else { 
                    $stmt_promote_user = $savienojums->prepare("UPDATE bookswap_users SET Loma = 'Administrators' WHERE LietotajsID = ?");
                    if (!$stmt_promote_user) { $response['message'] = 'DB kļūda (paaugstināšana).'; } 
                    else {
                        $stmt_promote_user->bind_param("i", $target_user_id_promote);
                        if ($stmt_promote_user->execute()) {
                            $response = ['success' => true, 'message' => 'Lietotājs '.htmlspecialchars($target_email_promote).' veiksmīgi paaugstināts par administratoru.'];
                        } else { $response['message'] = 'Neizdevās paaugstināt lietotāju: ' . $stmt_promote_user->error; }
                        $stmt_promote_user->close();
                    }
                }
            } else { $response['message'] = 'Lietotājs ar norādīto e-pastu nav atrasts.'; }
            $stmt_find_user_promote->close();
            break;
        case 'demote_admin':
            if ($userRole !== 'Moderators') {
                $response['message'] = 'Jums nav tiesību veikt šo darbību.';
                break;
            }
            $target_user_id_demote = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;

            if ($target_user_id_demote <= 0) { $response['message'] = 'Nederīgs lietotāja ID.'; break; }
            if ($target_user_id_demote == $userId) { $response['message'] = 'Jūs nevarat pazemināt pats sevi.'; break; }

            $stmt_check_admin = $savienojums->prepare("SELECT Loma FROM bookswap_users WHERE LietotajsID = ?");
            if (!$stmt_check_admin) { $response['message'] = 'DB kļūda (pārbaude pirms pazemināšanas).'; break;}
            $stmt_check_admin->bind_param("i", $target_user_id_demote);
            $stmt_check_admin->execute();
            $result_check_admin = $stmt_check_admin->get_result();
            if ($user_to_demote = $result_check_admin->fetch_assoc()) {
                if ($user_to_demote['Loma'] !== 'Administrators') {
                    $response['message'] = 'Šis lietotājs nav administrators.';
                } else {
                    $stmt_demote = $savienojums->prepare("UPDATE bookswap_users SET Loma = 'Registrēts' WHERE LietotajsID = ? AND Loma = 'Administrators'");
                    if (!$stmt_demote) { $response['message'] = 'DB kļūda (pazemināšana).'; }
                    else {
                        $stmt_demote->bind_param("i", $target_user_id_demote);
                        if ($stmt_demote->execute() && $stmt_demote->affected_rows > 0) {
                            $response = ['success' => true, 'message' => 'Administrators veiksmīgi pazemināts uz "Registrēts".'];
                        } else {
                            $response['message'] = 'Neizdevās pazemināt administratoru: ' . ($stmt_demote->error ?: 'Iespējams, lietotājs vairs nav administrators vai ID ir nepareizs.');
                        }
                        $stmt_demote->close();
                    }
                }
            } else {
                $response['message'] = 'Pazemināmais lietotājs nav atrasts.';
            }
            $stmt_check_admin->close();
            break;
        case 'check_wishlist_status':
            if (!isLoggedIn()) { $response = ['success' => false, 'wishlisted' => false, 'message' => 'Lietotājs nav autorizējies.']; break; }
            if ($book_id_ajax <= 0) { $response = ['success' => false, 'wishlisted' => false, 'message' => 'Nederīgs grāmatas ID.']; break; }
            $current_user_id_wish_check = $_SESSION['user_id']; // Use current user's ID from session
            $stmt_check_wish = $savienojums->prepare("SELECT VelmeID FROM bookswap_wishlist WHERE LietotajsID = ? AND GramatasID = ?");
            if ($stmt_check_wish) {
                $stmt_check_wish->bind_param("ii", $current_user_id_wish_check, $book_id_ajax);
                $stmt_check_wish->execute();
                $stmt_check_wish->store_result();
                $response = ['success' => true, 'wishlisted' => ($stmt_check_wish->num_rows > 0)];
                $stmt_check_wish->close();
            } else { $response = ['success' => false, 'wishlisted' => false, 'message' => 'DB kļūda (pārbaudot vēlmju sarakstu).'];}
            break;
        case 'toggle_wishlist':
            if (!isLoggedIn()) { $response['message'] = 'Lūdzu, pieslēdzieties, lai izmantotu vēlmju sarakstu.'; break; }
            if ($book_id_ajax <= 0) { $response['message'] = 'Nederīgs grāmatas ID.'; break; }
            $current_user_id_wish_toggle = $_SESSION['user_id'];

            $is_wishlisted_toggle = false;
            $stmt_check_toggle_wish = $savienojums->prepare("SELECT VelmeID FROM bookswap_wishlist WHERE LietotajsID = ? AND GramatasID = ?");
            if($stmt_check_toggle_wish) {
                $stmt_check_toggle_wish->bind_param("ii", $current_user_id_wish_toggle, $book_id_ajax);
                $stmt_check_toggle_wish->execute();
                $stmt_check_toggle_wish->store_result();
                $is_wishlisted_toggle = ($stmt_check_toggle_wish->num_rows > 0);
                $stmt_check_toggle_wish->close();
            } else { $response['message'] = 'DB kļūda (pārbaudot pirms pārslēgšanas).'; break; }

            if ($is_wishlisted_toggle) { 
                $stmt_toggle_wish = $savienojums->prepare("DELETE FROM bookswap_wishlist WHERE LietotajsID = ? AND GramatasID = ?");
                if ($stmt_toggle_wish) {
                    $stmt_toggle_wish->bind_param("ii", $current_user_id_wish_toggle, $book_id_ajax);
                    if ($stmt_toggle_wish->execute() && $stmt_toggle_wish->affected_rows > 0) {
                        $response = ['success' => true, 'wishlisted' => false, 'message' => 'Grāmata noņemta no vēlmju saraksta.'];
                    } else { $response['message'] = 'Neizdevās noņemt no vēlmju saraksta.'; }
                    $stmt_toggle_wish->close();
                } else { $response['message'] = 'DB kļūda (noņemot no vēlmju saraksta).';}
            } else { 
                $stmt_toggle_wish = $savienojums->prepare("INSERT INTO bookswap_wishlist (LietotajsID, GramatasID, PievienosanasDatums) VALUES (?, ?, NOW())");
                 if ($stmt_toggle_wish) {
                    $stmt_toggle_wish->bind_param("ii", $current_user_id_wish_toggle, $book_id_ajax);
                    if ($stmt_toggle_wish->execute()) {
                        $response = ['success' => true, 'wishlisted' => true, 'message' => 'Grāmata pievienota vēlmju sarakstam!'];
                    } else { $response['message'] = 'Neizdevās pievienot vēlmju sarakstam. ' . $stmt_toggle_wish->error; }
                    $stmt_toggle_wish->close();
                } else { $response['message'] = 'DB kļūda (pievienojot vēlmju sarakstam).';}
            }
            break;
        case 'submit_user_review':
            if (!isLoggedIn()) { $response['message'] = 'Lūdzu, pieslēdzieties.'; break; }
            
            $exchange_id_review = isset($_POST['exchange_id']) ? intval($_POST['exchange_id']) : 0;
            $reviewed_user_id_review = isset($_POST['reviewed_user_id']) ? intval($_POST['reviewed_user_id']) : 0;
            $rating_review = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            $comment_review = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            $reviewer_id = $_SESSION['user_id'];

            if ($exchange_id_review <= 0 || $reviewed_user_id_review <= 0 || $rating_review < 1 || $rating_review > 5) {
                $response['message'] = 'Nederīgi atsauksmes dati.'; break;
            }
            if ($reviewer_id == $reviewed_user_id_review) {
                $response['message'] = 'Jūs nevarat novērtēt pats sevi.'; break;
            }

            $stmt_check_exchange_participation = $savienojums->prepare("
                SELECT PieprasijumaID FROM bookswap_exchange_requests 
                WHERE PieprasijumaID = ? AND (IniciatorsID = ? OR AdresatsID = ?) AND Status = 'Apstiprināts'
            ");
            if (!$stmt_check_exchange_participation) { $response['message'] = 'DB kļūda pārbaudot maiņu.'; break; }
            $stmt_check_exchange_participation->bind_param("iii", $exchange_id_review, $reviewer_id, $reviewer_id);
            $stmt_check_exchange_participation->execute();
            $stmt_check_exchange_participation->store_result();
            
            if ($stmt_check_exchange_participation->num_rows == 0) {
                $response['message'] = 'Jūs nebijāt daļa no šīs maiņas vai tā nav pabeigta.';
                $stmt_check_exchange_participation->close();
                break;
            }
            $stmt_check_exchange_participation->close();

            $stmt_check_existing_review = $savienojums->prepare("SELECT AtsauksmeID FROM bookswap_user_reviews WHERE ApmaijnaPieprasijumaID = ? AND VertejsID = ? AND VertejamaisLietotajsID = ?");
            if(!$stmt_check_existing_review) { $response['message'] = 'DB kļūda pārbaudot esošo atsauksmi.'; break; }
            $stmt_check_existing_review->bind_param("iii", $exchange_id_review, $reviewer_id, $reviewed_user_id_review);
            $stmt_check_existing_review->execute();
            $stmt_check_existing_review->store_result();

            if ($stmt_check_existing_review->num_rows > 0) {
                $response['message'] = 'Jūs jau esat atstājis atsauksmi par šo lietotāju šajā maiņā.';
                $stmt_check_existing_review->close();
                break;
            }
            $stmt_check_existing_review->close();

            $stmt_insert_review = $savienojums->prepare("INSERT INTO bookswap_user_reviews (VertejamaisLietotajsID, VertejsID, Vertejums, Komentars, PublicesanasDatums, ApmaijnaPieprasijumaID) VALUES (?, ?, ?, ?, NOW(), ?)");
            if (!$stmt_insert_review) { $response['message'] = 'DB kļūda sagatavojot atsauksmi.'; break;}
            $stmt_insert_review->bind_param("iiisi", $reviewed_user_id_review, $reviewer_id, $rating_review, $comment_review, $exchange_id_review);

            if ($stmt_insert_review->execute()) {
                $stmt_avg = $savienojums->prepare("SELECT AVG(Vertejums) as avg_rating FROM bookswap_user_reviews WHERE VertejamaisLietotajsID = ?");
                if ($stmt_avg) {
                    $stmt_avg->bind_param("i", $reviewed_user_id_review);
                    $stmt_avg->execute();
                    $result_avg = $stmt_avg->get_result();
                    $avg_data = $result_avg->fetch_assoc();
                    $new_avg_rating = $avg_data['avg_rating'] ? round($avg_data['avg_rating'], 2) : null;
                    $stmt_avg->close();

                    $stmt_update_avg = $savienojums->prepare("UPDATE bookswap_users SET VidejaisVertejums = ? WHERE LietotajsID = ?");
                    if($stmt_update_avg){
                        $stmt_update_avg->bind_param("di", $new_avg_rating, $reviewed_user_id_review);
                        $stmt_update_avg->execute();
                        $stmt_update_avg->close();
                    }
                }
                $response = ['success' => true, 'message' => 'Atsauksme veiksmīgi iesniegta!'];
            } else {
                $response['message'] = 'Kļūda iesniedzot atsauksmi: ' . $stmt_insert_review->error;
            }
            $stmt_insert_review->close();
            break;
    }
    if ($savienojums && mysqli_ping($savienojums)) $savienojums->close(); 
    echo json_encode($response);
    exit;
}

if ($savienojums && mysqli_ping($savienojums)) {
    $autoDeleteStmt = $savienojums->prepare("UPDATE bookswap_books SET Status = 'Dzēsta', GaidaDzesanuLidz = NULL WHERE Status = 'Gaida dzēšanu' AND GaidaDzesanuLidz IS NOT NULL AND GaidaDzesanuLidz <= NOW() AND LietotajsID = ?");
    if ($autoDeleteStmt) {
        $autoDeleteStmt->bind_param("i", $userId);
        $autoDeleteStmt->execute();
        $autoDeleteStmt->close();
    }
} else { 
    require_once 'connect_db.php';
}

// Izlabots: Pareizi ielādē VidejaisVertejums
$stmt_user_refresh = $savienojums->prepare("SELECT Lietotajvards, E_pasts, ProfilaAttels, Loma, VidejaisVertejums FROM bookswap_users WHERE LietotajsID = ?");
if ($stmt_user_refresh) {
    $stmt_user_refresh->bind_param("i", $userId); $stmt_user_refresh->execute();
    $result_user_refresh = $stmt_user_refresh->get_result();
    if ($user_db_data_refresh = $result_user_refresh->fetch_assoc()) {
        $_SESSION['user_name'] = $user_db_data_refresh['Lietotajvards']; $userName = $user_db_data_refresh['Lietotajvards'];
        $_SESSION['user_email'] = $user_db_data_refresh['E_pasts']; $userEmail = $user_db_data_refresh['E_pasts'];
        $_SESSION['user_profile_photo'] = $user_db_data_refresh['ProfilaAttels']; $profilePhoto = $user_db_data_refresh['ProfilaAttels'];
        $_SESSION['user_role'] = $user_db_data_refresh['Loma']; $userRole = $user_db_data_refresh['Loma'];
        $userAverageRating = $user_db_data_refresh['VidejaisVertejums']; // Saglabājam vidējo vērtējumu
    }
    $stmt_user_refresh->close();
}


$nameParts = explode(' ', $userName, 2);
$firstName = $nameParts[0];
$lastName = isset($nameParts[1]) ? $nameParts[1] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_book') {
            $bookTitle = trim($_POST['bookTitleP'] ?? ''); $bookAuthor = trim($_POST['bookAuthorP'] ?? '');
            $bookGenre = trim($_POST['bookGenreP'] ?? ''); $bookLanguage = trim($_POST['bookLanguageP'] ?? '');
            $bookYear = intval($_POST['bookYearP'] ?? 0); $bookStavoklis = trim($_POST['bookConditionP'] ?? 'Laba'); 
            $bookDescription = trim($_POST['bookDescriptionP'] ?? '');
            if (empty($bookTitle)) $errors[] = 'Nosaukums ir obligāts.'; if (empty($bookAuthor)) $errors[] = 'Autors ir obligāts.';
            if (empty($bookGenre) || !in_array($bookGenre, $genres_list)) $errors[] = 'Lūdzu, izvēlieties derīgu žanru.';
            if (empty($bookLanguage)) $errors[] = 'Valoda ir obligāta.';
            if ($bookYear < 1000 || $bookYear > date("Y")) $errors[] = 'Nederīgs izdošanas gads.';
            if (empty($bookStavoklis) || !in_array($bookStavoklis, $conditions_list)) $errors[] = 'Lūdzu, izvēlieties derīgu stāvokli.';
            $bookImage = '';
            if (empty($errors)) {
                if (isset($_FILES['bookImageP']) && $_FILES['bookImageP']['error'] === UPLOAD_ERR_OK) {
                     $file_book_img = $_FILES['bookImageP']; $allowedTypes_book_img = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($file_book_img['type'], $allowedTypes_book_img)) $errors[] = 'Grāmatas attēlam atbalstītie formāti: JPEG, PNG, GIF';
                    if ($file_book_img['size'] > 2 * 1024 * 1024) $errors[] = 'Grāmatas attēla izmērs nedrīkst pārsniegt 2MB';
                    if (empty($errors)) { 
                        $uploadDir_book_img = 'uploads/book_images/'; if (!is_dir($uploadDir_book_img)) mkdir($uploadDir_book_img, 0755, true);
                        $fileExtension_book_img = strtolower(pathinfo($file_book_img['name'], PATHINFO_EXTENSION));
                        $fileName_book_img = 'book_' . $userId . '_' . time() . '_' . uniqid() . '.' . $fileExtension_book_img;
                        $filePath_book_img = $uploadDir_book_img . $fileName_book_img;
                        if (move_uploaded_file($file_book_img['tmp_name'], $filePath_book_img)) { $bookImage = $filePath_book_img; } 
                        else { $errors[] = 'Kļūda augšupielādējot grāmatas attēlu.'; }
                    }
                }
                if (empty($errors)) {
                    $currentDate = date('Y-m-d H:i:s'); $status = 'Gaida apstiprinājumu'; 
                    $stmt_add_book = $savienojums->prepare("INSERT INTO bookswap_books (Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Apraksts, Attels, PievienosanasDatums, Status, LietotajsID, Stavoklis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if($stmt_add_book) {
                        $stmt_add_book->bind_param("ssssissssis", $bookTitle, $bookAuthor, $bookGenre, $bookLanguage, $bookYear, $bookDescription, $bookImage, $currentDate, $status, $userId, $bookStavoklis);
                        if ($stmt_add_book->execute()) { $_SESSION['success_message'] = 'Grāmata pievienota un gaida apstiprinājumu!'; header('Location: profile.php'); exit(); } 
                        else { $errors[] = 'Kļūda pievienojot grāmatu: ' . $stmt_add_book->error; if (!empty($bookImage) && file_exists($bookImage) && strpos($bookImage, 'http') !== 0) unlink($bookImage); }
                        $stmt_add_book->close();
                    } else { $errors[] = 'DB Kļūda (pievienot grāmatu).'; }
                }
            }
        } elseif ($_POST['action'] === 'update_profile') {
            $firstName_form = trim($_POST['firstName'] ?? ''); $lastName_form = trim($_POST['lastName'] ?? '');
            if (empty($firstName_form)) $errors[] = 'Vārds ir obligāts lauks';
            if (empty($errors)) {
                $fullName = $firstName_form . (!empty($lastName_form) ? ' ' . $lastName_form : '');
                $stmt_upd_prof = $savienojums->prepare("UPDATE bookswap_users SET Lietotajvards = ? WHERE LietotajsID = ?");
                if($stmt_upd_prof){
                    $stmt_upd_prof->bind_param("si", $fullName, $userId);
                    if ($stmt_upd_prof->execute()) { $_SESSION['user_name'] = $fullName; $_SESSION['success_message'] = 'Profils veiksmīgi atjaunināts!'; header('Location: profile.php'); exit();
                    } else { $errors[] = 'Kļūda atjauninot profilu: ' . $savienojums->error; }
                    $stmt_upd_prof->close();
                } else {$errors[] = 'DB Kļūda (profila atj.).';}
            }
        } elseif ($_POST['action'] === 'change_password') { 
            $currentPassword = $_POST['currentPassword'] ?? ''; $newPassword = $_POST['newPassword'] ?? ''; $confirmNewPassword = $_POST['confirmNewPassword'] ?? '';
            if (empty($currentPassword)) $errors[] = 'Pašreizējā parole ir obligāta';
            if (empty($newPassword)) $errors[] = 'Jaunā parole ir obligāta'; elseif (strlen($newPassword) < 8) $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
            if ($newPassword !== $confirmNewPassword) $errors[] = 'Jaunās paroles nesakrīt';
            if (empty($errors)) {
                $stmt_pwd = $savienojums->prepare("SELECT Parole FROM bookswap_users WHERE LietotajsID = ?");
                if($stmt_pwd){
                    $stmt_pwd->bind_param("i", $userId); $stmt_pwd->execute(); $result_pwd = $stmt_pwd->get_result();
                    if ($user_pwd = $result_pwd->fetch_assoc()) {
                        if (password_verify($currentPassword, $user_pwd['Parole'])) {
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $updateStmt_pwd = $savienojums->prepare("UPDATE bookswap_users SET Parole = ? WHERE LietotajsID = ?");
                            if($updateStmt_pwd){
                                $updateStmt_pwd->bind_param("si", $hashedPassword, $userId);
                                if ($updateStmt_pwd->execute()) { $_SESSION['success_message'] = 'Parole veiksmīgi atjaunināta!'; header('Location: profile.php'); exit(); } 
                                else { $errors[] = 'Kļūda atjauninot paroli: ' . $savienojums->error; }
                                $updateStmt_pwd->close();
                            } else {$errors[] = 'DB Kļūda (paroles atj.).';}
                        } else { $errors[] = 'Pašreizējā parole ir nepareiza'; }
                    } else { $errors[] = 'Lietotājs nav atrasts'; }
                    $stmt_pwd->close();
                } else {$errors[] = 'DB Kļūda (paroles pārbaude).';}
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
                        if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false && strpos($profilePhoto, 'http') !== 0) { unlink($profilePhoto); }
                        $stmt_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = ? WHERE LietotajsID = ?");
                        if($stmt_photo){
                            $stmt_photo->bind_param("si", $filePath, $userId);
                            if ($stmt_photo->execute()) { $_SESSION['user_profile_photo'] = $filePath; $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi atjaunināts!'; header('Location: profile.php'); exit();
                            } else { $errors[] = 'Kļūda atjauninot profila foto datubāzē.'; if(file_exists($filePath)) unlink($filePath); }
                            $stmt_photo->close();
                        } else {$errors[] = 'DB Kļūda (foto atj.).';}
                    } else { $errors[] = 'Kļūda augšupielādējot failu.'; }
                }
            } else { $errors[] = 'Lūdzu, izvēlieties failu vai notika kļūda augšupielādes laikā.'; }
        } elseif ($_POST['action'] === 'remove_photo') { 
            $stmt_remove_photo = $savienojums->prepare("UPDATE bookswap_users SET ProfilaAttels = NULL WHERE LietotajsID = ?");
            if($stmt_remove_photo){
                $stmt_remove_photo->bind_param("i", $userId);
                if ($stmt_remove_photo->execute()) {
                    if (!empty($profilePhoto) && file_exists($profilePhoto) && strpos($profilePhoto, 'default') === false && strpos($profilePhoto, 'http') !== 0) { unlink($profilePhoto); }
                    $_SESSION['user_profile_photo'] = ''; $_SESSION['success_message'] = 'Profila fotoattēls veiksmīgi noņemts!'; header('Location: profile.php'); exit();
                } else { $errors[] = 'Kļūda noņemot profila fotoattēlu.';}
                $stmt_remove_photo->close();
            } else {$errors[] = 'DB Kļūda (foto noņemšana).';}
        }
    }
}


if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$reported_issues = [];
if ($userRole === 'Moderators' || $userRole === 'Administrators') {
    if (!$savienojums || $savienojums->connect_errno) { require 'connect_db.php'; }
    $stmt_issues = $savienojums->prepare("SELECT ir.*, u.Lietotajvards AS ReporterName FROM bookswap_issue_reports ir LEFT JOIN bookswap_users u ON ir.reporter_user_id = u.LietotajsID WHERE ir.status = 'Jauns' ORDER BY ir.report_date DESC");
    if ($stmt_issues) {
        $stmt_issues->execute(); $result_issues = $stmt_issues->get_result();
        while ($issue = $result_issues->fetch_assoc()) { $reported_issues[] = $issue; }
        $stmt_issues->close();
    } else { $errors[] = "Kļūda ielādējot ziņojumus par problēmām: " . $savienojums->error; }
}

$displayBooks = [];
if ($userRole === 'Moderators' || $userRole === 'Administrators') {
    if (!$savienojums || $savienojums->connect_errno) { require 'connect_db.php'; }
    $stmt_mod_q = $savienojums->prepare("SELECT b.*, u.Lietotajvards AS IesniedzejsVards FROM bookswap_books b JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID WHERE b.Status = 'Gaida apstiprinājumu' ORDER BY b.PievienosanasDatums ASC");
    if ($stmt_mod_q) {
        $stmt_mod_q->execute(); $result_mod_q = $stmt_mod_q->get_result();
        while ($book_item_mod = $result_mod_q->fetch_assoc()) { $displayBooks[] = $book_item_mod; }
        $stmt_mod_q->close();
    } else { $errors[] = "Kļūda ielādējot moderācijas sarakstu: " . $savienojums->error; }
} else { 
    if (!$savienojums || $savienojums->connect_errno) { require 'connect_db.php'; }
    $stmt_user_b = $savienojums->prepare("SELECT GramatasID, Nosaukums, Autors, Zanrs, Valoda, IzdosanasGads, Attels, Status, Stavoklis, GaidaDzesanuLidz FROM bookswap_books WHERE LietotajsID = ? ORDER BY PievienosanasDatums DESC");
    if ($stmt_user_b) {
        $stmt_user_b->bind_param("i", $userId); $stmt_user_b->execute();
        $result_user_b = $stmt_user_b->get_result();
        while ($book_item_user = $result_user_b->fetch_assoc()) { $displayBooks[] = $book_item_user; }
        $stmt_user_b->close();
    } else { $errors[] = "Kļūda ielādējot jūsu grāmatas: " . $savienojums->error; }
}

$incoming_requests = []; $outgoing_requests = [];
if (!$savienojums || $savienojums->connect_errno) { require 'connect_db.php'; }
if ($savienojums && !is_string($savienojums)) {
    $stmt_incoming = $savienojums->prepare("SELECT er.*, u_initiator.Lietotajvards AS IniciatorsVards, b_offered.Nosaukums AS PiedavataGramataNosaukums, b_offered.Attels AS PiedavataGramataAttels, b_requested.Nosaukums AS VelamaGramataNosaukums, b_requested.Attels AS VelamaGramataAttels FROM bookswap_exchange_requests er JOIN bookswap_users u_initiator ON er.IniciatorsID = u_initiator.LietotajsID JOIN bookswap_books b_offered ON er.PiedavatGramataID = b_offered.GramatasID JOIN bookswap_books b_requested ON er.VelamaiGramataID = b_requested.GramatasID WHERE er.AdresatsID = ? AND er.Status = 'Gaida' ORDER BY er.IzveidotsDatums DESC");
    if($stmt_incoming){ $stmt_incoming->bind_param("i", $userId); $stmt_incoming->execute(); $result_incoming = $stmt_incoming->get_result(); while($row_inc = $result_incoming->fetch_assoc()) { $incoming_requests[] = $row_inc; } $stmt_incoming->close(); }
    $stmt_outgoing = $savienojums->prepare("SELECT er.*, u_adresats.Lietotajvards AS AdresatsVards, b_offered.Nosaukums AS PiedavataGramataNosaukums, b_offered.Attels AS PiedavataGramataAttels, b_requested.Nosaukums AS VelamaGramataNosaukums, b_requested.Attels AS VelamaGramataAttels FROM bookswap_exchange_requests er JOIN bookswap_users u_adresats ON er.AdresatsID = u_adresats.LietotajsID JOIN bookswap_books b_offered ON er.PiedavatGramataID = b_offered.GramatasID JOIN bookswap_books b_requested ON er.VelamaiGramataID = b_requested.GramatasID WHERE er.IniciatorsID = ? AND er.Status = 'Gaida' ORDER BY er.IzveidotsDatums DESC");
    if($stmt_outgoing){ $stmt_outgoing->bind_param("i", $userId); $stmt_outgoing->execute(); $result_outgoing = $stmt_outgoing->get_result(); while($row_out = $result_outgoing->fetch_assoc()) { $outgoing_requests[] = $row_out; } $stmt_outgoing->close(); }
}

// Funkcija zvaigznīšu HTML ģenerēšanai
function generateStarRatingHTML($rating, $maxStars = 5) {
    $html = '<div class="star-rating-display">';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = $maxStars - $fullStars - ($halfStar ? 1 : 0);

    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="star filled">★</span>';
    }
    if ($halfStar) {
        // Jūs varat izmantot daļēji aizpildītu zvaigznīti vai noapaļot
        // Šeit vienkāršības labad izmantosim noapaļotu pilnu vai tukšu
        // Ja vēlaties precīzāku puszvaigzni, būs nepieciešams sarežģītāks CSS vai SVG
        $html .= '<span class="star filled">★</span>'; // Vai tukša, atkarībā no dizaina
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="star">☆</span>'; // Tukša zvaigzne
    }
    $html .= ' <span class="rating-numeric">(' . number_format($rating, 1) . ')</span>';
    $html .= '</div>';
    return $html;
}


if ($savienojums && !is_string($savienojums) && mysqli_ping($savienojums)) $savienojums->close();
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
   
    .star-rating-display { display: inline-block; font-size: 1.1em;  color: var(--color-burgundy); }
    .star-rating-display .star.filled { color: var(--color-burgundy); }
    .star-rating-display .star { color: var(--color-light-gray);  }
    .star-rating-display .rating-numeric { font-size: 0.9em; color: var(--color-gray); margin-left: 5px; vertical-align: middle;}

    .hidden { display: none; }
    .add-book-section, .password-change-section, .moderation-section, .admin-management-section, .completed-exchanges-section { margin-top: 20px; padding: 20px; background-color: var(--color-paper); border-radius: var(--radius-lg); } /* Added completed-exchanges-section */
    .form-section-title { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid var(--color-paper); padding-bottom: 10px; }
    .user-books, .moderation-queue, .issue-reports-list, .exchange-request-grid, .current-admins-list, #wishlistBooksGrid { margin-top: 20px; } 
    .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 20px; margin-top: 20px; }
    .book-card { background-color: var(--color-white); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--color-paper); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); display: flex; flex-direction: column; position:relative; }
    .book-card.pending-deletion { border-left: 5px solid orange; }
    .book-card.pending-approval { border-left: 5px solid dodgerblue; }
    .book-cover-container { height: 200px; overflow: hidden; background-color: var(--color-light-gray); display: flex; align-items: center; justify-content: center; position: relative; }
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
    .modal-content input[type="text"], .modal-content input[type="email"], .modal-content textarea { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid var(--color-paper); border-radius: var(--radius-md); } 
    .modal-footer-buttons { display: flex; justify-content: flex-end; gap: 10px; }
    #confirmDeleteBookBtnModal:disabled, #confirmRejectBookBtnModal:disabled, #confirmPromoteAdminBtnModal:disabled, #confirmDemoteAdminBtnModal:disabled, #submitReviewBtnModal:disabled { background-color: #ccc; cursor: not-allowed; } 
    .issue-reports-section { margin-top: 20px; }
    .issue-reports-list { display: grid; grid-template-columns: 1fr; gap: 20px; }
    .issue-report-card { background-color: var(--color-white); border: 1px solid var(--color-paper); border-left: 5px solid var(--color-burgundy); border-radius: var(--radius-md); padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .issue-report-card h4 { font-family: var(--font-serif); color: var(--color-darkwood); margin-top: 0; margin-bottom: 10px; }
    .issue-report-card p { margin-bottom: 8px; font-size: 0.9rem; line-height: 1.5; }
    .issue-report-card p strong { font-weight: 500; color: var(--color-darkwood); }
    .issue-report-card .status-badge { background-color: var(--color-leather); color: white; font-size: 0.75rem; }
    .issue-report-card .status-badge.apstradats { background-color: #66bb6a; }
    .exchange-requests-section { margin-top: 30px; }
    .exchange-request-grid { display: flex; flex-direction: column; gap: 20px; }
    .exchange-request-card { background-color: var(--color-white); border: 1px solid var(--color-paper); border-radius: var(--radius-lg); padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.07); }
    .exchange-request-card h5 { font-family: var(--font-serif); margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: var(--color-darkwood); border-bottom: 1px solid var(--color-paper); padding-bottom: 8px; }
    .exchange-books-display { display: flex; align-items: flex-start; justify-content: space-between; gap:15px; margin-bottom: 15px; }
    .exchange-book-item { text-align: center; flex-basis: 45%; display: flex; flex-direction: column; align-items: center;}
    .exchange-book-item img, .exchange-book-item .book-cover-fallback { width: 100px; height: 150px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: 8px; border: 1px solid var(--color-light-gray); }
    .exchange-book-item .book-cover-fallback { display: flex; align-items: center; justify-content: center; background-color: var(--color-light-gray); }
    .exchange-book-item .book-cover-fallback svg{ width: 40px; height: 40px; color: var(--color-gray); }
    .exchange-book-item p { font-size: 0.8rem; color: var(--color-gray); margin-top: 3px; line-height: 1.3; }
    .exchange-book-item strong { font-size: 0.9rem; color: var(--color-darkwood); display: block; white-space: normal; overflow-wrap: break-word; margin-bottom: 3px; }
    .exchange-arrow { font-size: 2.5rem; color: var(--color-darkwood); margin: auto 10px; align-self: center; flex-shrink: 0; }
    .exchange-request-info p { margin-bottom: 8px; font-size: 0.9rem; }
    .exchange-request-actions { margin-top: 15px; text-align: right; display: flex; justify-content: flex-end; gap: 10px; }
    .exchange-request-message { background-color: var(--color-cream); padding: 12px; border-radius: var(--radius-sm); margin-top:12px; font-style: italic; font-size: 0.85rem; border: 1px dashed var(--color-paper);}
    .exchange-request-message strong { display:block; margin-bottom: 5px; font-style: normal;}
    .exchange-requests-section > h4 { font-family: var(--font-serif); font-size:1.3rem; margin-bottom:15px; color:var(--color-darkwood); padding-bottom:10px; border-bottom: 1px solid var(--color-paper); }
    .admin-management-section p { margin-bottom: 10px; } 
    .admin-user-card { display: flex; align-items: center; background-color: var(--color-white); padding: 10px; border-radius: var(--radius-md); border: 1px solid var(--color-paper); margin-bottom: 10px; }
    .admin-user-avatar { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; margin-right: 15px; background-color: var(--color-light-gray); display:flex; align-items:center; justify-content:center;}
    .admin-user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .admin-user-avatar .placeholder-initials { font-weight: bold; color: var(--color-darkwood); }
    .admin-user-details { flex-grow: 1; }
    .admin-user-details strong { display: block; color: var(--color-darkwood); }
    .admin-user-details span { font-size: 0.85rem; color: var(--color-gray); }
    .btn-demote-admin { background-color: #f48fb1; color: white; padding: 5px 10px; font-size:0.8rem; border-radius:var(--radius-sm); }
    .btn-demote-admin:hover { background-color: #f06292; }
    .wishlist-button-profile { 
        background-color: hsla(0, 60%, 55%, 0.8); 
        color: white; border: none; padding: var(--spacing-1) var(--spacing-2);
        font-size: 0.8rem; border-radius: var(--radius-md); cursor: pointer;
        transition: background-color 0.2s;
    }
    .wishlist-button-profile:hover { background-color: hsla(0, 60%, 45%, 1); }
    .book-card .wishlist-button { 
        position: absolute; top: var(--spacing-2); right: var(--spacing-2);
        background-color: hsla(0,0%,100%,0.8); border-radius: 50%;
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
        color: var(--color-burgundy); 
    }
    .book-card .wishlist-button svg.filled { fill: var(--color-burgundy); }
  </style>
</head>
<body data-current-user-id="<?php echo htmlspecialchars($userId); ?>">
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
                <button class="toast-close" onclick="this.parentElement.remove()" style="color: #155724; background: transparent; border: none; font-size: 1.5rem; position: absolute; top: 5px; right: 10px;">&times;</button>
            </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="auth-error active" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px; border-radius: .25rem; margin-bottom: 10px; position:relative;">
                <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                 <button class="toast-close" onclick="this.parentElement.remove()" style="background:transparent; border:none; font-size:1.5rem; color: #721c24; position:absolute; top:5px; right:10px;">&times;</button>
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
                        } else { 
                            $activeBookCount = count($displayBooks); 
                        }
                        echo $activeBookCount;
                    ?>
                </span>
                <span class="stat-label">
                    <?php echo ($userRole === 'Moderators' || $userRole === 'Administrators') ? 'Moderējamās grāmatas' : 'Manas grāmatas'; ?>
                </span>
              </div>
                <?php if ($userRole === 'Registrēts'): ?>
                <div class="stat-item">
                    <span class="stat-number" id="wishlistCountProfile"><?php echo count($wishlist_books_for_js); ?></span>
                    <span class="stat-label">Vēlmju sarakstā</span>
                </div>
                 <!-- USER AVERAGE RATING DISPLAY -->
                <div class="stat-item">
                    <div id="userAverageRatingDisplaySidebar" class="star-rating-display">
                        <?php echo generateStarRatingHTML($userAverageRating); ?>
                    </div>
                    <span class="stat-label">Vidējais vērtējums</span>
                </div>
                <?php endif; ?>
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

            <?php if ($userRole === 'Moderators'): ?>
                <div class="form-section-title"><h3>Administratoru pārvaldība</h3></div>
                <div class="admin-management-section"> 
                    <p>Ievadiet lietotāja e-pasta adresi, lai paaugstinātu viņu par Administratoru.</p>
                    <div class="form-group">
                        <label for="promoteEmailInput">Lietotāja e-pasts paaugstināšanai:</label>
                        <input type="email" id="promoteEmailInput" name="promoteEmailInput" class="form-input" placeholder="piemers@example.com">
                    </div>
                    <button type="button" id="initiatePromoteBtn" class="btn-primary">Paaugstināt par Administratoru</button>
                
                    <h4 style="margin-top: 30px; margin-bottom: 15px; border-top: 1px solid var(--color-paper); padding-top:15px;">Esošie Administratori</h4>
                    <div class="current-admins-list">
                        <?php 
                        $other_admins_exist = false;
                        if (!empty($current_admins_list)) {
                            foreach ($current_admins_list as $admin) {
                                if ($admin['LietotajsID'] != $userId) { 
                                    $other_admins_exist = true;
                                    break;
                                }
                            }
                        }
                        $admins_to_display = array_filter($current_admins_list, function($admin) use ($userId) {
                            return $admin['LietotajsID'] != $userId; 
                        });

                        if (empty($admins_to_display)): ?>
                            <p class="no-books-message">Pašlaik nav citu administratoru.</p>
                        <?php else: ?>
                            <?php foreach ($admins_to_display as $admin): ?>
                                <div class="admin-user-card" id="admin-card-<?php echo $admin['LietotajsID']; ?>">
                                    <div class="admin-user-avatar">
                                        <?php if (!empty($admin['ProfilaAttels'])): ?>
                                            <img src="<?php echo htmlspecialchars($admin['ProfilaAttels']); ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($admin['Lietotajvards']); ?>">
                                        <?php else: 
                                            $initial = !empty($admin['Lietotajvards']) ? strtoupper(mb_substr($admin['Lietotajvards'], 0, 1, 'UTF-8')) : 'A';?>
                                            <span class="placeholder-initials"><?php echo $initial; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="admin-user-details">
                                        <strong><?php echo htmlspecialchars($admin['Lietotajvards']); ?></strong>
                                        <span><?php echo htmlspecialchars($admin['E_pasts']); ?></span>
                                    </div>
                                    <button type="button" class="btn btn-demote-admin" onclick="openDemoteAdminModal(<?php echo $admin['LietotajsID']; ?>, '<?php echo htmlspecialchars(addslashes($admin['E_pasts'])); ?>')">Noņemt tiesības</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
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
                <?php if ($userRole === 'Moderators' || $userRole === 'Administrators'): ?>
                <div class="form-section-title"><h3>Ziņojumi par problēmām (Jauni)</h3></div>
                <div class="moderation-section issue-reports-section">
                    <?php if (empty($reported_issues)): ?>
                        <p class="no-books-message">Nav jaunu ziņojumu par problēmām.</p>
                    <?php else: ?>
                        <div class="issue-reports-list">
                            <?php foreach ($reported_issues as $report): ?>
                                <div class="issue-report-card" id="issue-report-<?php echo $report['report_id']; ?>">
                                    <h4>Problēmas veids: <?php echo htmlspecialchars($report['issue_type']); ?></h4>
                                    <p><strong>Ziņotājs:</strong> <?php echo htmlspecialchars($report['ReporterName'] ?? 'Anonīms'); ?> (ID: <?php echo htmlspecialchars($report['reporter_user_id']); ?>)</p>
                                    <p><strong>Datums:</strong> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($report['issue_date']))); ?></p>
                                    <p><strong>Ziņots:</strong> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($report['report_date']))); ?></p>
                                    
                                    <?php if (!empty($report['related_user_text'])): ?>
                                        <p><strong>Saistītais lietotājs:</strong> <?php echo htmlspecialchars($report['related_user_text']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($report['related_book_text'])): ?>
                                        <p><strong>Saistītā grāmata:</strong> <?php echo htmlspecialchars($report['related_book_text']); ?></p>
                                    <?php endif; ?>
                                    
                                    <p><strong>Apraksts:</strong><br><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                    <p><strong>Kontakti:</strong> <?php echo htmlspecialchars($report['contact_details']); ?></p>
                                    <p><strong>Status:</strong> <span class="status-badge"><?php echo htmlspecialchars($report['status']); ?></span></p>
                                    
                                    <div class="report-actions-footer" style="margin-top:15px; padding-top:10px; border-top:1px solid var(--color-paper); text-align:right;">
                                        <button class="btn btn-small-action btn-outline" onclick="markReportAs('apstrādāts', <?php echo $report['report_id']; ?>)">Atzīmēt kā apstrādātu</button>
                                        <button class="btn btn-small-action btn-reject-book" style="background-color: var(--color-burgundy);" onclick="markReportAs('dzēsts', <?php echo $report['report_id']; ?>)">Dzēst ziņojumu</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: // Regular user sections ?>
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
                <?php if ($userRole === 'Registrēts'): ?>
                <div class="form-section-title"><h3>Mans vēlmju saraksts</h3></div>
                <div class="user-wishlist-section">
                    <?php if (empty($wishlist_books_for_js)): ?>
                        <p class="no-books-message">Jūsu vēlmju saraksts ir tukšs.</p>
                    <?php else: ?>
                        <div class="books-grid" id="wishlistBooksGrid">
                            <?php foreach ($wishlist_books_for_js as $wish_book): ?>
                                <div class="book-card" id="wishlist-book-card-<?php echo $wish_book['GramatasID']; ?>" data-bookid="<?php echo $wish_book['GramatasID']; ?>">
                                    <a href="book.php?id=<?php echo $wish_book['GramatasID']; ?>" class="book-cover-container">
                                        <?php if (!empty($wish_book['Attels'])): ?>
                                            <img src="<?php echo htmlspecialchars($wish_book['Attels']); ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($wish_book['Nosaukums']); ?>" class="book-cover">
                                        <?php else: ?>
                                            <div class="book-cover-fallback"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                        <?php endif; ?>
                                    </a>
                                    <div class="book-info">
                                        <a href="book.php?id=<?php echo $wish_book['GramatasID']; ?>"><h3 class="book-title"><?php echo htmlspecialchars($wish_book['Nosaukums']); ?></h3></a>
                                        <p class="book-author"><?php echo htmlspecialchars($wish_book['Autors']); ?></p>
                                        <div class="book-tags">
                                            <span class="book-tag"><?php echo htmlspecialchars($wish_book['Zanrs']); ?></span>
                                            <span class="book-tag"><?php echo htmlspecialchars($wish_book['Stavoklis'] ?? 'N/A'); ?></span>
                                        </div>
                                         <p style="font-size:0.8em; color:var(--color-gray);">Pievienots: <?php echo htmlspecialchars(date('d.m.Y', strtotime($wish_book['PievienosanasDatums']))); ?></p>
                                    </div>
                                    <div class="book-actions-footer">
                                        <button type="button" class="btn btn-small-action wishlist-button-profile" onclick="toggleWishlistInProfile(<?php echo $wish_book['GramatasID']; ?>, this)">Noņemt no vēlmēm</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?> 

            <?php endif; // End regular user sections ?>
            <?php if ($userRole !== 'Moderators' && $userRole !== 'Administrators'): ?>
                        <div class="form-section-title"><h3>Grāmatu maiņas</h3></div>
                        <div class="exchange-requests-section">
                            
                            <h4>Ienākošie pieprasījumi (Gaida jūsu atbildi)</h4>
                            <?php if (empty($incoming_requests)): ?>
                                <p class="no-books-message">Jums nav jaunu ienākošo maiņas pieprasījumu.</p>
                            <?php else: ?>
                                <div class="exchange-request-grid">
                                    <?php foreach ($incoming_requests as $req): ?>
                                        <div class="exchange-request-card" id="incoming-request-<?php echo $req['PieprasijumaID']; ?>">
                                            <div class="exchange-books-display">
                                                <div class="exchange-book-item">
                                                    <strong><?php echo htmlspecialchars($req['PiedavataGramataNosaukums']); ?></strong>
                                                    <?php 
                                                        $offered_cover_path = '';
                                                        if (!empty($req['PiedavataGramataAttels'])) {
                                                            if (filter_var($req['PiedavataGramataAttels'], FILTER_VALIDATE_URL)) $offered_cover_path = htmlspecialchars($req['PiedavataGramataAttels']);
                                                            elseif (file_exists($req['PiedavataGramataAttels'])) $offered_cover_path = htmlspecialchars($req['PiedavataGramataAttels']);
                                                        }
                                                    ?>
                                                    <?php if ($offered_cover_path): ?>
                                                        <img src="<?php echo $offered_cover_path; ?>?t=<?php echo time(); ?>" alt="Offered Book">
                                                    <?php else: ?>
                                                        <div class="book-cover-fallback"><svg width="24" height="24" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                                    <?php endif; ?>
                                                    <p>Piedāvā: <?php echo htmlspecialchars($req['IniciatorsVards']); ?></p>
                                                </div>
                                                <span class="exchange-arrow">⇄</span>
                                                <div class="exchange-book-item">
                                                    <strong><?php echo htmlspecialchars($req['VelamaGramataNosaukums']); ?></strong>
                                                     <?php 
                                                        $requested_cover_path = '';
                                                        if (!empty($req['VelamaGramataAttels'])) {
                                                            if (filter_var($req['VelamaGramataAttels'], FILTER_VALIDATE_URL)) $requested_cover_path = htmlspecialchars($req['VelamaGramataAttels']);
                                                            elseif (file_exists($req['VelamaGramataAttels'])) $requested_cover_path = htmlspecialchars($req['VelamaGramataAttels']);
                                                        }
                                                    ?>
                                                    <?php if ($requested_cover_path): ?>
                                                        <img src="<?php echo $requested_cover_path; ?>?t=<?php echo time(); ?>" alt="Requested Book">
                                                    <?php else: ?>
                                                        <div class="book-cover-fallback"><svg width="24" height="24" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                                    <?php endif; ?>
                                                    <p>Jūsu grāmata</p>
                                                </div>
                                            </div>
                                            <?php if(!empty($req['ApmaijnasTekets'])): ?>
                                                <div class="exchange-request-message">
                                                    <strong>Ziņa no <?php echo htmlspecialchars($req['IniciatorsVards']); ?>:</strong>
                                                    <p><?php echo nl2br(htmlspecialchars($req['ApmaijnasTekets'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <div class="exchange-request-actions">
                                                <button class="btn btn-small-action btn-approve-book" onclick="handleExchangeRequest(<?php echo $req['PieprasijumaID']; ?>, 'approve', <?php echo $req['IniciatorsID']; ?>, '<?php echo htmlspecialchars(addslashes($req['IniciatorsVards'])); ?>')">Apstiprināt</button>
                                                <button class="btn btn-small-action btn-reject-book" onclick="handleExchangeRequest(<?php echo $req['PieprasijumaID']; ?>, 'reject')">Noraidīt</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <h4 style="margin-top: 30px;">Izejošie pieprasījumi (Gaida atbildi)</h4>
                            <?php if (empty($outgoing_requests)): ?>
                                <p class="no-books-message">Jums nav aktīvu izejošo maiņas pieprasījumu.</p>
                            <?php else: ?>
                                 <div class="exchange-request-grid">
                                    <?php foreach ($outgoing_requests as $req): ?>
                                        <div class="exchange-request-card" id="outgoing-request-<?php echo $req['PieprasijumaID']; ?>">
                                            <div class="exchange-books-display">
                                                <div class="exchange-book-item">
                                                    <strong><?php echo htmlspecialchars($req['PiedavataGramataNosaukums']); ?></strong>
                                                     <?php 
                                                        $offered_out_cover_path = '';
                                                        if (!empty($req['PiedavataGramataAttels'])) {
                                                            if (filter_var($req['PiedavataGramataAttels'], FILTER_VALIDATE_URL)) $offered_out_cover_path = htmlspecialchars($req['PiedavataGramataAttels']);
                                                            elseif (file_exists($req['PiedavataGramataAttels'])) $offered_out_cover_path = htmlspecialchars($req['PiedavataGramataAttels']);
                                                        }
                                                    ?>
                                                    <?php if ($offered_out_cover_path): ?>
                                                        <img src="<?php echo $offered_out_cover_path; ?>?t=<?php echo time(); ?>" alt="Offered Book">
                                                    <?php else: ?>
                                                        <div class="book-cover-fallback"><svg width="24" height="24" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                                    <?php endif; ?>
                                                    <p>Jūsu piedāvājums</p>
                                                </div>
                                                <span class="exchange-arrow">⇄</span>
                                                <div class="exchange-book-item">
                                                    <strong><?php echo htmlspecialchars($req['VelamaGramataNosaukums']); ?></strong>
                                                    <?php 
                                                        $requested_out_cover_path = '';
                                                        if (!empty($req['VelamaGramataAttels'])) {
                                                            if (filter_var($req['VelamaGramataAttels'], FILTER_VALIDATE_URL)) $requested_out_cover_path = htmlspecialchars($req['VelamaGramataAttels']);
                                                            elseif (file_exists($req['VelamaGramataAttels'])) $requested_out_cover_path = htmlspecialchars($req['VelamaGramataAttels']);
                                                        }
                                                    ?>
                                                    <?php if ($requested_out_cover_path): ?>
                                                        <img src="<?php echo $requested_out_cover_path; ?>?t=<?php echo time(); ?>" alt="Requested Book">
                                                    <?php else: ?>
                                                        <div class="book-cover-fallback"><svg width="24" height="24" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                                                    <?php endif; ?>
                                                    <p>Pieprasīts no: <?php echo htmlspecialchars($req['AdresatsVards']); ?></p>
                                                </div>
                                            </div>
                                            <div class="exchange-request-info">
                                                <p>Status: <span class="status-badge gaida"><?php echo htmlspecialchars($req['Status']); ?></span></p>
                                                 <?php if(!empty($req['ApmaijnasTekets'])): ?>
                                                    <div class="exchange-request-message">
                                                        <strong>Jūsu ziņa:</strong>
                                                        <p><?php echo nl2br(htmlspecialchars($req['ApmaijnasTekets'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- USER REVIEWS: Completed Exchanges for Review -->
                        <div class="form-section-title"><h3>Pabeigtās Maiņas un Atsauksmes</h3></div>
                        <div class="completed-exchanges-section">
                            <?php if (empty($completed_exchanges_for_review)): ?>
                                <p class="no-books-message">Jums nav pabeigtu maiņu, par kurām varētu atstāt atsauksmi.</p>
                            <?php else: ?>
                                <?php foreach ($completed_exchanges_for_review as $ex): ?>
                                <div class="exchange-review-card" id="exchange-review-card-<?php echo $ex['PieprasijumaID']; ?>">
                                    <p><strong>Maiņa ar:</strong> <?php echo htmlspecialchars($ex['other_user_name']); ?></p>
                                    <p><strong>Jūs atdevāt:</strong> "<?php echo htmlspecialchars($ex['book_you_got_title']); ?>"</p>
                                    <p><strong>Jūs saņēmāt:</strong> "<?php echo htmlspecialchars($ex['book_they_got_title']); ?>"</p>
                                    <div class="review-action-area" id="review-action-<?php echo $ex['PieprasijumaID']; ?>">
                                    <?php if ($ex['can_review_other_user']): ?>
                                        <button class="btn btn-leave-review" 
                                                onclick="openUserReviewModal(<?php echo $ex['PieprasijumaID']; ?>, <?php echo $ex['other_user_id']; ?>, '<?php echo htmlspecialchars(addslashes($ex['other_user_name'])); ?>')">
                                            Atstāt atsauksmi par <?php echo htmlspecialchars($ex['other_user_name']); ?>
                                        </button>
                                    <?php else: ?>
                                        <p class="review-submitted-text">Atsauksme par <?php echo htmlspecialchars($ex['other_user_name']); ?> jau iesniegta.</p>
                                    <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
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
    <div id="promoteAdminModal" class="modal"> 
        <div class="modal-content">
            <span class="close-button-modal" onclick="closePromoteAdminModal()">×</span>
            <h3>Apstiprināt paaugstināšanu par Administratoru</h3>
            <p>Lai apstiprinātu lietotāja "<strong id="modalPromoteUserEmail"></strong>" paaugstināšanu, lūdzu, atkārtoti ievadiet viņa e-pasta adresi:</p>
            <input type="email" id="confirmPromoteEmailInput" placeholder="Atkārtoti ievadiet e-pastu">
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closePromoteAdminModal()">Atcelt</button>
                <button type="button" id="confirmPromoteAdminBtnModal" class="btn-primary" disabled>Apstiprināt Paaugstināšanu</button>
            </div>
        </div>
    </div>
    <div id="demoteAdminModal" class="modal"> 
        <div class="modal-content">
            <span class="close-button-modal" onclick="closeDemoteAdminModal()">×</span>
            <h3>Apstiprināt Administratora tiesību noņemšanu</h3>
            <p>Lai apstiprinātu administratora tiesību noņemšanu lietotājam "<strong id="modalDemoteUserEmail"></strong>", lūdzu, atkārtoti ievadiet viņa e-pasta adresi:</p>
            <input type="email" id="confirmDemoteEmailInput" placeholder="Atkārtoti ievadiet e-pastu">
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closeDemoteAdminModal()">Atcelt</button>
                <button type="button" id="confirmDemoteAdminBtnModal" class="btn-primary" disabled>Apstiprināt Noņemšanu</button>
            </div>
        </div>
    </div>
    <div id="userReviewModal" class="modal">
        <div class="modal-content">
            <span class="close-button-modal" onclick="closeUserReviewModal()">×</span>
            <h3 id="userReviewModalTitle">Novērtēt lietotāju</h3>
            <p>Lūdzu, novērtējiet savu maiņas pieredzi ar <strong id="reviewedUserNameModal"></strong>.</p>
            <div class="form-group">
                <label>Vērtējums (1-5 zvaigznes):</label>
                <div class="star-rating-input">
                    <input type="radio" id="star5" name="rating_modal" value="5" required /><label for="star5" title="5 zvaigznes">★</label>
                    <input type="radio" id="star4" name="rating_modal" value="4" /><label for="star4" title="4 zvaigznes">★</label>
                    <input type="radio" id="star3" name="rating_modal" value="3" /><label for="star3" title="3 zvaigznes">★</label>
                    <input type="radio" id="star2" name="rating_modal" value="2" /><label for="star2" title="2 zvaigznes">★</label>
                    <input type="radio" id="star1" name="rating_modal" value="1" /><label for="star1" title="1 zvaigzne">★</label>
                </div>
            </div>
            <div class="form-group">
                <label for="reviewCommentModal">Komentārs (nav obligāts):</label>
                <textarea id="reviewCommentModal" class="form-textarea" rows="3" placeholder="Jūsu komentārs..."></textarea>
            </div>
            <div class="modal-footer-buttons">
                <button type="button" class="btn btn-outline" onclick="closeUserReviewModal()">Atcelt</button>
                <button type="button" id="submitReviewBtnModal" class="btn-primary">Iesniegt atsauksmi</button>
            </div>
        </div>
    </div>
  
  <footer class="footer"> <!-- ... (kā iepriekš) ... --> </footer>
  <script src="script.js"></script>
  <script src="profile_js.js"></script> 
  <script>
    // ... (visa iepriekšējā JavaScript loģika no profile.php) ...
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
        
        window.markReportAs = function(newStatus, reportId) {
            if (!confirm(`Vai tiešām vēlaties mainīt šī ziņojuma statusu uz "${newStatus}"?`)) { return; }
            const formData = new FormData();
            formData.append('ajax_action', 'update_report_status'); 
            formData.append('report_id', reportId);
            formData.append('new_status', newStatus);
            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    const reportCard = document.getElementById(`issue-report-${reportId}`);
                    if (reportCard) {
                        if (newStatus === 'dzēsts') { reportCard.remove();
                            const reportList = document.querySelector('.issue-reports-list');
                            if (reportList && reportList.children.length === 0) {
                                const noReportsMsg = document.querySelector('.issue-reports-section .no-books-message');
                                if(noReportsMsg) noReportsMsg.style.display = 'block';
                                else { const p = document.createElement('p'); p.className = 'no-books-message'; p.textContent = 'Nav jaunu ziņojumu par problēmām.'; document.querySelector('.issue-reports-section').appendChild(p); }
                            }
                        } else {
                            const statusBadge = reportCard.querySelector('.status-badge');
                            if (statusBadge) { statusBadge.textContent = newStatus; statusBadge.className = `status-badge ${newStatus.toLowerCase().replace(' ', '-')}`; }
                            const actionsFooter = reportCard.querySelector('.report-actions-footer');
                            if(actionsFooter) actionsFooter.innerHTML = '<p><em>Statuss atjaunināts.</em></p>';
                        }
                    }
                } else { showUIMessage(data.message || 'Kļūda atjauninot ziņojuma statusu.', 'error'); }
            }).catch(error => { showUIMessage('Tīkla kļūda, mēģiniet vēlāk.', 'error'); });
        }

        window.handleExchangeRequest = function(requestId, decision, initiatorId = null, initiatorName = null) {
            const actionText = decision === 'approve' ? 'apstiprināt' : 'noraidīt';
            if (!confirm(`Vai tiešām vēlaties ${actionText} šo maiņas pieprasījumu?`)) { return; }
            const formData = new FormData();
            formData.append('ajax_action', 'handle_exchange_request');
            formData.append('request_id', requestId);
            formData.append('decision', decision);
            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    const requestCard = document.getElementById(`incoming-request-${requestId}`);
                    if (requestCard) { requestCard.remove(); 
                        const incomingGrid = document.querySelector('.exchange-requests-section h4:first-of-type + .exchange-request-grid'); 
                        if (incomingGrid && incomingGrid.children.length === 0) {
                             const p = document.createElement('p'); p.className = 'no-books-message'; p.textContent = 'Jums nav jaunu ienākošo maiņas pieprasījumu.';
                             incomingGrid.parentNode.insertBefore(p, incomingGrid.nextSibling); incomingGrid.remove(); 
                        }
                    }
                    if (decision === 'approve' && data.initiator_id && data.initiator_name) {
                        // Reload page to see the "Leave Review" button for the completed exchange
                        location.reload(); 
                        // Optionally, directly call initiateChat:
                        // if (confirm(`Maiņa apstiprināta! Vai vēlaties sākt sarunu ar ${data.initiator_name}?`)) {
                        //     if (typeof window.initiateChatWithUser === 'function') {
                        //         window.initiateChatWithUser(data.initiator_id, data.initiator_name);
                        //     }
                        // }
                    }
                } else { showUIMessage(data.message || 'Kļūda apstrādājot pieprasījumu.', 'error'); }
            }).catch(error => showUIMessage('Tīkla kļūda.', 'error'));
        }

        const promoteAdminModal = document.getElementById('promoteAdminModal');
        const initiatePromoteBtn = document.getElementById('initiatePromoteBtn');
        const confirmPromoteEmailInput = document.getElementById('confirmPromoteEmailInput');
        const confirmPromoteAdminBtnModal = document.getElementById('confirmPromoteAdminBtnModal');
        const modalPromoteUserEmailSpan = document.getElementById('modalPromoteUserEmail');
        const promoteEmailInputGlobal = document.getElementById('promoteEmailInput'); 
        let targetEmailForPromotion = "";

        if (initiatePromoteBtn && promoteEmailInputGlobal && promoteAdminModal) {
            initiatePromoteBtn.addEventListener('click', function() {
                targetEmailForPromotion = promoteEmailInputGlobal.value.trim();
                if (!targetEmailForPromotion) { showUIMessage('Lūdzu, ievadiet e-pasta adresi.', 'error'); return; }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(targetEmailForPromotion)) { showUIMessage('Lūdzu, ievadiet derīgu e-pasta adresi.', 'error'); return; }
                if (modalPromoteUserEmailSpan) modalPromoteUserEmailSpan.textContent = targetEmailForPromotion;
                if (confirmPromoteEmailInput) confirmPromoteEmailInput.value = '';
                if (confirmPromoteAdminBtnModal) confirmPromoteAdminBtnModal.disabled = true;
                promoteAdminModal.style.display = 'block';
            });
        }
        window.closePromoteAdminModal = function() {
            if (promoteAdminModal) promoteAdminModal.style.display = 'none';
            if (promoteEmailInputGlobal) promoteEmailInputGlobal.value = ''; 
        }
        if (confirmPromoteEmailInput && confirmPromoteAdminBtnModal) {
            confirmPromoteEmailInput.addEventListener('input', function() {
                confirmPromoteAdminBtnModal.disabled = this.value.trim().toLowerCase() !== targetEmailForPromotion.toLowerCase();
            });
        }
        if (confirmPromoteAdminBtnModal) {
            confirmPromoteAdminBtnModal.addEventListener('click', function() {
                if (targetEmailForPromotion && confirmPromoteEmailInput.value.trim().toLowerCase() === targetEmailForPromotion.toLowerCase()) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'promote_to_admin');
                    formData.append('target_email', targetEmailForPromotion);
                    confirmPromoteAdminBtnModal.disabled = true; 
                    fetch('profile.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { showUIMessage(data.message, 'success'); if (promoteEmailInputGlobal) promoteEmailInputGlobal.value = ''; location.reload(); } 
                        else { showUIMessage(data.message || 'Kļūda paaugstinot lietotāju.', 'error'); }
                    })
                    .catch(error => { showUIMessage('Tīkla kļūda veicot paaugstināšanu.', 'error'); })
                    .finally(() => { closePromoteAdminModal(); });
                }
            });
        }

        const demoteAdminModal = document.getElementById('demoteAdminModal');
        const confirmDemoteEmailInput = document.getElementById('confirmDemoteEmailInput');
        const confirmDemoteAdminBtnModal = document.getElementById('confirmDemoteAdminBtnModal');
        const modalDemoteUserEmailSpan = document.getElementById('modalDemoteUserEmail');
        let currentAdminIdToDemote = null;
        let currentAdminEmailToDemote = "";

        window.openDemoteAdminModal = function(adminId, adminEmail) {
            currentAdminIdToDemote = adminId; currentAdminEmailToDemote = adminEmail;
            if (modalDemoteUserEmailSpan) modalDemoteUserEmailSpan.textContent = adminEmail;
            if (confirmDemoteEmailInput) confirmDemoteEmailInput.value = '';
            if (confirmDemoteAdminBtnModal) confirmDemoteAdminBtnModal.disabled = true;
            if (demoteAdminModal) demoteAdminModal.style.display = 'block';
        }
        window.closeDemoteAdminModal = function() { if(demoteAdminModal) demoteAdminModal.style.display = 'none'; }

        if (confirmDemoteEmailInput && confirmDemoteAdminBtnModal) {
            confirmDemoteEmailInput.addEventListener('input', function() {
                confirmDemoteAdminBtnModal.disabled = this.value.trim().toLowerCase() !== currentAdminEmailToDemote.toLowerCase();
            });
        }
        if (confirmDemoteAdminBtnModal) {
            confirmDemoteAdminBtnModal.addEventListener('click', function() {
                if (currentAdminIdToDemote && confirmDemoteEmailInput.value.trim().toLowerCase() === currentAdminEmailToDemote.toLowerCase()) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'demote_admin');
                    formData.append('target_user_id', currentAdminIdToDemote);
                    confirmDemoteAdminBtnModal.disabled = true;
                    fetch('profile.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { showUIMessage(data.message, 'success'); removeAdminFromUIList(currentAdminIdToDemote); } 
                        else { showUIMessage(data.message || 'Kļūda pazeminot administratoru.', 'error');}
                    })
                    .catch(error => { showUIMessage('Tīkla kļūda veicot pazemināšanu.', 'error'); })
                    .finally(() => { closeDemoteAdminModal(); });
                }
            });
        }

        function removeAdminFromUIList(adminId) {
            const adminCard = document.getElementById(`admin-card-${adminId}`);
            if (adminCard) {
                adminCard.remove();
                const adminListContainer = document.querySelector('.current-admins-list');
                 if (adminListContainer) {
                    let hasOtherAdmins = false;
                    const remainingAdminCards = adminListContainer.querySelectorAll('.admin-user-card');
                    const currentUserIdPHP = <?php echo json_encode($userId); ?>; 
                    remainingAdminCards.forEach(card => {
                        if (card.id !== `admin-card-${currentUserIdPHP}`) { 
                            hasOtherAdmins = true;
                        }
                    });

                    if (!hasOtherAdmins && remainingAdminCards.length === 0) { 
                         let noAdminsMsg = adminListContainer.querySelector('.no-books-message');
                        if (!noAdminsMsg) {
                            noAdminsMsg = document.createElement('p');
                            noAdminsMsg.className = 'no-books-message';
                            adminListContainer.appendChild(noAdminsMsg);
                        }
                        noAdminsMsg.textContent = 'Pašlaik nav citu administratoru.';
                    }
                }
            }
        }
        
        window.toggleWishlistInProfile = function(bookId, buttonElement) {
            const formData = new FormData();
            formData.append('ajax_action', 'toggle_wishlist');
            formData.append('book_id', bookId);

            fetch('profile.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showUIMessage(data.message, 'success');
                    if (!data.wishlisted) { 
                        const card = document.getElementById(`wishlist-book-card-${bookId}`);
                        if (card) card.remove();
                        const wishlistGrid = document.getElementById('wishlistBooksGrid');
                        const wishlistCountSpan = document.getElementById('wishlistCountProfile');
                        if(wishlistGrid && wishlistGrid.children.length === 0) {
                            wishlistGrid.innerHTML = '<p class="no-books-message">Jūsu vēlmju saraksts ir tukšs.</p>';
                        }
                        if(wishlistCountSpan) {
                            let currentCount = parseInt(wishlistCountSpan.textContent);
                            if (!isNaN(currentCount) && currentCount > 0) {
                                wishlistCountSpan.textContent = currentCount - 1;
                            } else {
                                wishlistCountSpan.textContent = 0;
                            }
                        }
                    }
                } else {
                    showUIMessage(data.message || 'Kļūda ar vēlmju sarakstu.', 'error');
                }
            })
            .catch(error => showUIMessage('Tīkla kļūda ar vēlmju sarakstu.', 'error'));
        }

        // USER REVIEW MODAL LOGIC
        const userReviewModal = document.getElementById('userReviewModal');
        const userReviewModalTitle = document.getElementById('userReviewModalTitle');
        const reviewedUserNameModalSpan = document.getElementById('reviewedUserNameModal');
        const reviewCommentModalTextarea = document.getElementById('reviewCommentModal');
        const submitReviewBtnModal = document.getElementById('submitReviewBtnModal');
        const starRatingInputs = document.querySelectorAll('.star-rating-input input[name="rating_modal"]');
        
        let currentExchangeIdForReview = null;
        let currentReviewedUserIdForReview = null;
        let currentSelectedRating = 0;

        window.openUserReviewModal = function(exchangeId, reviewedUserId, reviewedUserName) {
            currentExchangeIdForReview = exchangeId;
            currentReviewedUserIdForReview = reviewedUserId;
            currentSelectedRating = 0; 
            
            if (userReviewModalTitle) userReviewModalTitle.textContent = `Novērtēt maiņu ar ${reviewedUserName}`;
            if (reviewedUserNameModalSpan) reviewedUserNameModalSpan.textContent = reviewedUserName;
            if (reviewCommentModalTextarea) reviewCommentModalTextarea.value = '';
            starRatingInputs.forEach(radio => radio.checked = false); 
            if (submitReviewBtnModal) submitReviewBtnModal.disabled = true; 
            if (userReviewModal) userReviewModal.style.display = 'block';
        }

        window.closeUserReviewModal = function() {
            if (userReviewModal) userReviewModal.style.display = 'none';
        }

        starRatingInputs.forEach(radio => {
            radio.addEventListener('change', function() {
                currentSelectedRating = parseInt(this.value);
                if (submitReviewBtnModal) submitReviewBtnModal.disabled = false;
            });
        });

        if (submitReviewBtnModal) {
            submitReviewBtnModal.addEventListener('click', function() {
                if (currentSelectedRating === 0) {
                    showUIMessage('Lūdzu, izvēlieties vērtējumu (zvaigznes).', 'error');
                    return;
                }

                const comment = reviewCommentModalTextarea ? reviewCommentModalTextarea.value.trim() : '';
                submitReviewBtnModal.disabled = true;

                const formData = new FormData();
                formData.append('ajax_action', 'submit_user_review');
                formData.append('exchange_id', currentExchangeIdForReview);
                formData.append('reviewed_user_id', currentReviewedUserIdForReview);
                formData.append('rating', currentSelectedRating);
                formData.append('comment', comment);

                fetch('profile.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showUIMessage(data.message, 'success');
                        const reviewActionArea = document.getElementById(`review-action-${currentExchangeIdForReview}`);
                        if(reviewActionArea) {
                            reviewActionArea.innerHTML = '<p class="review-submitted-text">Atsauksme iesniegta.</p>';
                        }
                        // Reload user's average rating in sidebar if it's their own profile page
                        const currentUserIdOnPage = document.body.dataset.currentUserId;
                        if (currentReviewedUserIdForReview.toString() === currentUserIdOnPage.toString()) {
                             // This is tricky without a full page reload or more complex JS.
                             // For now, let's just note that this would be the place to update it.
                             // Or, simply reload the page after successful review:
                             // location.reload();
                        }


                    } else {
                        showUIMessage(data.message || 'Kļūda iesniedzot atsauksmi.', 'error');
                    }
                })
                .catch(error => {
                    showUIMessage('Tīkla kļūda iesniedzot atsauksmi.', 'error');
                })
                .finally(() => {
                    closeUserReviewModal();
                });
            });
        }


        window.addEventListener('click', function(event) {
            if (event.target == promoteAdminModal) { closePromoteAdminModal(); }
            if (event.target == demoteAdminModal) { closeDemoteAdminModal(); } 
            if (event.target == deleteModal) { closeDeleteModal(); }
            if (event.target == rejectModal) { closeRejectModal(); }
            if (event.target == userReviewModal) { closeUserReviewModal(); }
        });


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
                <button class="toast-close" onclick="this.parentElement.remove()" style="background:none; border:none; font-size:1.2rem; line-height:1; color: ${type === 'success' ? '#155724' : '#721c24'}; position:absolute; top:50%; right:15px; transform:translateY(-50%);">&times;</button>`;
            toastDiv.style.padding = '15px'; toastDiv.style.borderRadius = '.25rem'; toastDiv.style.marginBottom = '10px'; toastDiv.style.position = 'relative'; 
            if(toastContainer){ toastContainer.appendChild(toastDiv); } 
            else { toastDiv.style.position = 'fixed'; toastDiv.style.top = '20px'; toastDiv.style.right = '20px'; toastDiv.style.zIndex = '1050'; document.body.appendChild(toastDiv); }
            setTimeout(() => { toastDiv.remove(); }, 5000);
        }

    });
  </script>

 <!-- Chat Widget Start -->
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
                <div class="loading-spinner hidden"><div class="spinner"></div></div>
            </div>
            <div id="chat-message-area" class="hidden">
                <div id="chat-messages-display">
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

<link rel="stylesheet" href="chat.css?v=<?php echo time(); ?>">
<script src="chat.js?v=<?php echo time(); ?>"></script>

</body>
</html>