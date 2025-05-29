<?php
require_once 'session_check.php';
require_once 'connect_db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Nav autorizēts.', 'error_code' => 'UNAUTHORIZED']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? ''; // Используем $_REQUEST для GET или POST

$response = ['success' => false, 'message' => 'Nederīga darbība.'];

if (!$savienojums) {
    $response['message'] = 'Nevarēja izveidot savienojumu ar datu bāzi.';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'get_conversations':
            // Получаем список уникальных пользователей, с которыми были переписки, и последнее сообщение
            $stmt_conv = $savienojums->prepare("
                SELECT 
                    u.LietotajsID, 
                    u.Lietotajvards, 
                    u.ProfilaAttels,
                    (SELECT m.Teksts 
                     FROM bookswap_messages m 
                     WHERE (m.SutitajsID = u.LietotajsID AND m.SanemejsID = ?) OR (m.SutitajsID = ? AND m.SanemejsID = u.LietotajsID) 
                     ORDER BY m.NosutisanasLaiks DESC LIMIT 1) AS lastMessageText,
                    (SELECT m.NosutisanasLaiks 
                     FROM bookswap_messages m 
                     WHERE (m.SutitajsID = u.LietotajsID AND m.SanemejsID = ?) OR (m.SutitajsID = ? AND m.SanemejsID = u.LietotajsID) 
                     ORDER BY m.NosutisanasLaiks DESC LIMIT 1) AS lastMessageTimestamp,
                    (SELECT COUNT(*) 
                     FROM bookswap_messages m_unread 
                     WHERE m_unread.SanemejsID = ? AND m_unread.SutitajsID = u.LietotajsID AND m_unread.Status = 'Neizlasīts') AS unreadCount
                FROM bookswap_users u
                WHERE u.LietotajsID IN (
                    SELECT DISTINCT SutitajsID FROM bookswap_messages WHERE SanemejsID = ?
                    UNION
                    SELECT DISTINCT SanemejsID FROM bookswap_messages WHERE SutitajsID = ?
                ) AND u.LietotajsID != ?
                ORDER BY lastMessageTimestamp DESC
            ");
            if (!$stmt_conv) throw new Exception("Prepare failed (get_conversations): " . $savienojums->error);
            
            $stmt_conv->bind_param("iiiiiiii", 
                $current_user_id, $current_user_id, 
                $current_user_id, $current_user_id,
                $current_user_id, 
                $current_user_id, $current_user_id, $current_user_id
            );
            $stmt_conv->execute();
            $result_conv = $stmt_conv->get_result();
            $conversations = [];
            while ($row_conv = $result_conv->fetch_assoc()) {
                $profile_pic = '';
                if (!empty($row_conv['ProfilaAttels'])) {
                    if (filter_var($row_conv['ProfilaAttels'], FILTER_VALIDATE_URL)) {
                        $profile_pic = htmlspecialchars($row_conv['ProfilaAttels']);
                    } elseif (file_exists($row_conv['ProfilaAttels'])) {
                        $profile_pic = htmlspecialchars($row_conv['ProfilaAttels']);
                    }
                }
                $row_conv['ProfilaAttels'] = $profile_pic;
                // Convert timestamp to a more usable format or relative time for JS
                if ($row_conv['lastMessageTimestamp']) {
                     $dt = new DateTime($row_conv['lastMessageTimestamp']);
                     // $row_conv['lastMessageTimeFormatted'] = $dt->format('H:i'); // Example formatting
                }
                $conversations[] = $row_conv;
            }
            $stmt_conv->close();
            $response = ['success' => true, 'conversations' => $conversations];
            break;

        case 'get_messages':
            $other_user_id = isset($_GET['with_user_id']) ? intval($_GET['with_user_id']) : 0;
            if ($other_user_id <= 0) {
                $response['message'] = 'Nederīgs sarunu biedra ID.';
                break;
            }

            // Помечаем сообщения как прочитанные
            $stmt_update_status = $savienojums->prepare("UPDATE bookswap_messages SET Status = 'Izlasīts' WHERE SutitajsID = ? AND SanemejsID = ? AND Status = 'Neizlasīts'");
            if (!$stmt_update_status) throw new Exception("Prepare failed (update_status): " . $savienojums->error);
            $stmt_update_status->bind_param("ii", $other_user_id, $current_user_id);
            $stmt_update_status->execute();
            $stmt_update_status->close();

            // Получаем сообщения
            $stmt_msg = $savienojums->prepare("
                SELECT ZinojumaID, SutitajsID, SanemejsID, Teksts, NosutisanasLaiks, Status 
                FROM bookswap_messages 
                WHERE (SutitajsID = ? AND SanemejsID = ?) OR (SutitajsID = ? AND SanemejsID = ?)
                ORDER BY NosutisanasLaiks ASC
            ");
            if (!$stmt_msg) throw new Exception("Prepare failed (get_messages): " . $savienojums->error);
            $stmt_msg->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
            $stmt_msg->execute();
            $result_msg = $stmt_msg->get_result();
            $messages = [];
            while ($row_msg = $result_msg->fetch_assoc()) {
                $dt = new DateTime($row_msg['NosutisanasLaiks']);
                $row_msg['NosutisanasLaiksFormatted'] = $dt->format('d.m.Y H:i'); // Example
                $messages[] = $row_msg;
            }
            $stmt_msg->close();
            $response = ['success' => true, 'messages' => $messages];
            break;

        case 'send_message':
            $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
            $text = isset($_POST['text']) ? trim($_POST['text']) : '';

            if ($receiver_id <= 0) {
                $response['message'] = 'Nederīgs saņēmēja ID.';
                break;
            }
            if (empty($text)) {
                $response['message'] = 'Ziņojuma teksts nevar būt tukšs.';
                break;
            }
            if (mb_strlen($text, 'UTF-8') > 1000) { // Ограничение на длину сообщения
                $response['message'] = 'Ziņojums ir pārāk garš (maks. 1000 simboli).';
                break;
            }


            $stmt_send = $savienojums->prepare("INSERT INTO bookswap_messages (SutitajsID, SanemejsID, Teksts, NosutisanasLaiks, Status) VALUES (?, ?, ?, NOW(), 'Neizlasīts')");
            if (!$stmt_send) throw new Exception("Prepare failed (send_message): " . $savienojums->error);
            
            $sanitized_text = htmlspecialchars($text); // Базовая санация перед вставкой
            $stmt_send->bind_param("iis", $current_user_id, $receiver_id, $sanitized_text);
            
            if ($stmt_send->execute()) {
                $newMessageId = $stmt_send->insert_id;
                $response = [
                    'success' => true, 
                    'message' => 'Ziņa nosūtīta.',
                    'sent_message' => [ // Отправляем обратно данные о сообщении для отображения
                        'ZinojumaID' => $newMessageId,
                        'SutitajsID' => $current_user_id,
                        'SanemejsID' => $receiver_id,
                        'Teksts' => $sanitized_text,
                        'NosutisanasLaiks' => date('Y-m-d H:i:s'), // Текущее время
                        'NosutisanasLaiksFormatted' => date('d.m.Y H:i'),
                        'Status' => 'Neizlasīts' // Хотя мы его отправили, для получателя он 'Neizlasīts'
                    ]
                ];
            } else {
                $response['message'] = 'Kļūda sūtot ziņu: ' . $stmt_send->error;
            }
            $stmt_send->close();
            break;
        
        case 'check_new_data': // For polling
            $last_known_message_id = isset($_GET['last_message_id']) && $_GET['last_message_id'] !== 'null' ? intval($_GET['last_message_id']) : 0;
            $active_chat_partner_id = isset($_GET['active_chat_partner_id']) && $_GET['active_chat_partner_id'] !== 'null' ? intval($_GET['active_chat_partner_id']) : null;
            
            $new_messages_for_active_chat = [];
            $unread_counts_per_user = [];

            // 1. Get new messages for the currently active chat (if any)
            if ($active_chat_partner_id) {
                $stmt_new = $savienojums->prepare("
                    SELECT ZinojumaID, SutitajsID, SanemejsID, Teksts, NosutisanasLaiks, Status 
                    FROM bookswap_messages 
                    WHERE ((SutitajsID = ? AND SanemejsID = ?) OR (SutitajsID = ? AND SanemejsID = ?))
                    AND ZinojumaID > ?
                    ORDER BY NosutisanasLaiks ASC
                ");
                if (!$stmt_new) throw new Exception("Prepare failed (check_new_data active): " . $savienojums->error);
                $stmt_new->bind_param("iiiii", $current_user_id, $active_chat_partner_id, $active_chat_partner_id, $current_user_id, $last_known_message_id);
                $stmt_new->execute();
                $result_new = $stmt_new->get_result();
                while ($row_new = $result_new->fetch_assoc()) {
                    $dt = new DateTime($row_new['NosutisanasLaiks']);
                    $row_new['NosutisanasLaiksFormatted'] = $dt->format('d.m.Y H:i');
                    $new_messages_for_active_chat[] = $row_new;
                }
                $stmt_new->close();

                // Mark these new messages as read
                if (!empty($new_messages_for_active_chat)) {
                    $ids_to_mark_read = array_map(function($msg) { return $msg['ZinojumaID']; }, $new_messages_for_active_chat);
                    if (!empty($ids_to_mark_read)) {
                        $ids_placeholder = implode(',', array_fill(0, count($ids_to_mark_read), '?'));
                        $types = str_repeat('i', count($ids_to_mark_read));
                        
                        $stmt_mark_read_poll = $savienojums->prepare("UPDATE bookswap_messages SET Status = 'Izlasīts' WHERE ZinojumaID IN ($ids_placeholder) AND SanemejsID = ?");
                        if ($stmt_mark_read_poll) {
                             $params_mark_read = array_merge($ids_to_mark_read, [$current_user_id]);
                             $stmt_mark_read_poll->bind_param($types . 'i', ...$params_mark_read);
                             $stmt_mark_read_poll->execute();
                             $stmt_mark_read_poll->close();
                        }
                    }
                }
            }

            // 2. Get unread message counts for all conversations to update badges
            $stmt_unread_counts = $savienojums->prepare("
                SELECT SutitajsID, COUNT(*) as unread_count 
                FROM bookswap_messages 
                WHERE SanemejsID = ? AND Status = 'Neizlasīts' 
                GROUP BY SutitajsID
            ");
            if (!$stmt_unread_counts) throw new Exception("Prepare failed (check_new_data unread_counts): " . $savienojums->error);
            $stmt_unread_counts->bind_param("i", $current_user_id);
            $stmt_unread_counts->execute();
            $result_unread_counts = $stmt_unread_counts->get_result();
            while ($row_uc = $result_unread_counts->fetch_assoc()) {
                $unread_counts_per_user[$row_uc['SutitajsID']] = $row_uc['unread_count'];
            }
            $stmt_unread_counts->close();

            $response = [
                'success' => true,
                'new_messages_active_chat' => $new_messages_for_active_chat,
                'unread_counts' => $unread_counts_per_user
            ];
            break;

        default:
            $response['message'] = 'Nezināma darbība.';
            break;
    }
} catch (Exception $e) {
    error_log("Chat API Exception: " . $e->getMessage() . " (Trace: " . $e->getTraceAsString() . ")");
    $response['message'] = 'Servera kļūda: ' . $e->getMessage();
    // В реальном приложении не стоит выводить $e->getMessage() напрямую пользователю без дополнительной фильтрации
}

if ($savienojums) {
    $savienojums->close();
}

echo json_encode($response, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
exit;
?>