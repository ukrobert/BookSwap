<?php
require_once 'session_check.php'; // Для работы шапки и определения залогиненного пользователя
require_once 'connect_db.php';   // Для подключения к БД

$book_id_from_url = null;
$book_details_for_js = null;
$similar_books_for_js = [];
$user_books_for_trade_js = []; // Книги залогиненного пользователя для модального окна обмена

// 1. Получаем ID книги из URL
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $book_id_from_url = (int)$_GET['id'];
} else {
    // Если ID невалидный или отсутствует, перенаправляем или показываем ошибку
    header("Location: browse.php?error=invalid_book_id");
    exit;
}

try {
    // 2. Извлекаем основную информацию о книге и ее владельце
    $sql_book = "SELECT 
                    b.GramatasID, b.Nosaukums, b.Autors, b.Zanrs, b.Valoda, 
                    b.IzdosanasGads, b.Apraksts, b.Attels, b.Status AS BookStatusDB, 
                    b.PievienosanasDatums,
                    u.LietotajsID as OwnerLietotajsID, u.Lietotajvards as OwnerLietotajvards, 
                    u.ProfilaAttels as OwnerProfilaAttels, u.VidejaisVertejums as OwnerVidejaisVertejums
                 FROM bookswap_books b
                 JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
                 WHERE b.GramatasID = ? AND b.Status = 'Pieejama'"; // Показываем детали только для доступных книг

    $stmt_book = $savienojums->prepare($sql_book);
    if (!$stmt_book) throw new Exception("Prepare failed (book): " . $savienojums->error);
    $stmt_book->bind_param("i", $book_id_from_url);
    $stmt_book->execute();
    $result_book = $stmt_book->get_result();

    if ($result_book->num_rows > 0) {
        $db_book_row = $result_book->fetch_assoc();

        // Подготовка данных для JavaScript объекта bookDetails
        $coverImage = '';
        if (!empty($db_book_row['Attels'])) {
            if (filter_var($db_book_row['Attels'], FILTER_VALIDATE_URL)) {
                $coverImage = htmlspecialchars($db_book_row['Attels']);
            } elseif (file_exists($db_book_row['Attels'])) {
                $coverImage = htmlspecialchars($db_book_row['Attels']);
            }
        }

        $ownerImage = '';
        if (!empty($db_book_row['OwnerProfilaAttels'])) {
           if (filter_var($db_book_row['OwnerProfilaAttels'], FILTER_VALIDATE_URL)) {
                $ownerImage = htmlspecialchars($db_book_row['OwnerProfilaAttels']);
            } elseif (file_exists($db_book_row['OwnerProfilaAttels'])) {
                $ownerImage = htmlspecialchars($db_book_row['OwnerProfilaAttels']);
            }
        }
        
        // Относительная дата добавления
        $listedDateStr = "nesen";
        if ($db_book_row['PievienosanasDatums']) {
            $listedTimestamp = strtotime($db_book_row['PievienosanasDatums']);
            $now = time();
            $diff = $now - $listedTimestamp;
            if ($diff < 60) $listedDateStr = $diff . " sek. atpakaļ";
            elseif ($diff < 3600) $listedDateStr = floor($diff/60) . " min. atpakaļ";
            elseif ($diff < 86400) $listedDateStr = floor($diff/3600) . " st. atpakaļ";
            elseif ($diff < 2 * 86400) $listedDateStr = "vakar"; // Добавим "вчера"
            elseif ($diff < 7 * 86400) $listedDateStr = floor($diff/86400) . " d. atpakaļ";
            else $listedDateStr = date("d.m.Y", $listedTimestamp);
        }

        // Предполагаем, что у вас есть поле "Stavoklis" в таблице bookswap_books
        // Если нет, то нужно его добавить или использовать BookStatusDB
        $book_condition = $db_book_row['BookStatusDB']; // Пока используем статус. Замените на реальное поле состояния.
        // Пример: 'Kā jauna', 'Laba', 'Pieņemama'

        // Подсчет количества обменов для владельца (пример)
        $owner_trade_count = 0;
        $stmt_trade_cnt = $savienojums->prepare("SELECT COUNT(*) as count FROM bookswap_exchange_requests WHERE (IniciatorsID = ? OR AdresatsID = ?) AND Status = 'Pabeigts'");
        if($stmt_trade_cnt){
            $stmt_trade_cnt->bind_param("ii", $db_book_row['OwnerLietotajsID'], $db_book_row['OwnerLietotajsID']);
            $stmt_trade_cnt->execute();
            $res_trade_cnt = $stmt_trade_cnt->get_result();
            if($row_cnt = $res_trade_cnt->fetch_assoc()){
                $owner_trade_count = $row_cnt['count'];
            }
            $stmt_trade_cnt->close();
        }


        $book_details_for_js = [
            'id' => $db_book_row['GramatasID'],
            'title' => $db_book_row['Nosaukums'],
            'author' => $db_book_row['Autors'],
            'coverImage' => $coverImage,
            'genre' => $db_book_row['Zanrs'],
            'language' => $db_book_row['Valoda'],
            'year' => $db_book_row['IzdosanasGads'],
            'condition' => $book_condition, // ЗАМЕНИТЕ НА РЕАЛЬНОЕ ПОЛЕ СОСТОЯНИЯ КНИГИ
            'description' => nl2br(htmlspecialchars($db_book_row['Apraksts'])), // nl2br для переносов строк, htmlspecialchars для безопасности
            'listedBy' => $db_book_row['OwnerLietotajvards'],
            'userId' => $db_book_row['OwnerLietotajsID'],
            'userImage' => $ownerImage,
            'listedDate' => $listedDateStr,
            'userRating' => $db_book_row['OwnerVidejaisVertejums'] ? floatval($db_book_row['OwnerVidejaisVertejums']) : 0.0,
            'tradeCount' => $owner_trade_count 
        ];

        // 3. Извлекаем похожие книги
        $current_book_genre = $db_book_row['Zanrs'];
        $current_book_author = $db_book_row['Autors'];
        $sql_similar = "SELECT GramatasID, Nosaukums, Autors, Attels, Zanrs 
                        FROM bookswap_books 
                        WHERE Status = 'Pieejama' AND GramatasID != ? 
                              AND (Zanrs = ? OR Autors = ?)
                        ORDER BY RAND() 
                        LIMIT 3";
        $stmt_similar = $savienojums->prepare($sql_similar);
        if (!$stmt_similar) throw new Exception("Prepare failed (similar): " . $savienojums->error);
        $stmt_similar->bind_param("iss", $book_id_from_url, $current_book_genre, $current_book_author);
        $stmt_similar->execute();
        $result_similar = $stmt_similar->get_result();
        while ($row_similar = $result_similar->fetch_assoc()) {
            $simCoverImage = '';
            if (!empty($row_similar['Attels'])) {
                 if (filter_var($row_similar['Attels'], FILTER_VALIDATE_URL)) {
                    $simCoverImage = htmlspecialchars($row_similar['Attels']);
                } elseif (file_exists($row_similar['Attels'])) {
                    $simCoverImage = htmlspecialchars($row_similar['Attels']);
                }
            }
            $similar_books_for_js[] = [
                'id' => $row_similar['GramatasID'],
                'title' => $row_similar['Nosaukums'],
                'author' => $row_similar['Autors'],
                'coverImage' => $simCoverImage,
                'genre' => $row_similar['Zanrs']
            ];
        }
        $stmt_similar->close();

    } else {
        // Книга не найдена или недоступна
        header("Location: browse.php?error=book_not_found");
        exit;
    }
    $stmt_book->close();

    // 4. Извлекаем книги залогиненного пользователя для модального окна обмена
    if (isLoggedIn()) {
        $logged_in_user_id = $_SESSION['user_id'];
        // Убедимся, что не предлагаем обменять книгу саму на себя, и что книга принадлежит текущему пользователю
        $sql_user_books = "SELECT GramatasID, Nosaukums, Autors 
                           FROM bookswap_books 
                           WHERE LietotajsID = ? AND Status = 'Pieejama' AND GramatasID != ?";
        $stmt_user_books = $savienojums->prepare($sql_user_books);
        if (!$stmt_user_books) throw new Exception("Prepare failed (user_books): " . $savienojums->error);
        $stmt_user_books->bind_param("ii", $logged_in_user_id, $book_id_from_url);
        $stmt_user_books->execute();
        $result_user_books = $stmt_user_books->get_result();
        while ($row_user_book = $result_user_books->fetch_assoc()) {
            $user_books_for_trade_js[] = [
                'id' => $row_user_book['GramatasID'],
                'title' => $row_user_book['Nosaukums'],
                'author' => $row_user_book['Autors']
            ];
        }
        $stmt_user_books->close();
    }

    $savienojums->close();

} catch (Exception $e) {
    // error_log("Error on book.php: " . $e->getMessage());
    // Можно установить $book_details_for_js в null или пустой массив,
    // чтобы JS мог корректно обработать отсутствие данных
    $book_details_for_js = null; 
    // Для пользователя можно показать общую страницу ошибки или перенаправить
    // header("Location: error_page.php?message=" . urlencode($e->getMessage()));
    // exit;
}

// Если книга не найдена после всех проверок, перенаправляем
if ($book_details_for_js === null && $book_id_from_url) {
    header("Location: browse.php?error=book_data_unavailable");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Title будет установлен через JS -->
    <title>Grāmatas detaļas - BookSwap</title> 
    <meta name="description" content="View book details and request a trade." />
    <meta name="author" content="BookSwap" />

    <meta property="og:title" content="Grāmatas detaļas - BookSwap" />
    <meta property="og:description" content="View book details and request a trade on BookSwap." />
    <meta property="og:type" content="website" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@bookswap" />
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="book.css">
  </head>

  <body>
    <div id="root">
      <!-- Navigation (Универсальная шапка) -->
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
                        $profilePicPath = $_SESSION['user_profile_photo'] ?? '';
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath) && (filter_var($profilePicPath, FILTER_VALIDATE_URL) || file_exists($profilePicPath))): ?>
                                        <img src="<?php echo htmlspecialchars($profilePicPath); ?>?t=<?php echo time(); ?>" alt="Profils">
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
        <div class="container">
          <a href="browse.php" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Atpakaļ uz pārlūkošanu
          </a>
        </div>
        
        <div class="container">
          <div class="book-detail-grid">
            <div class="book-cover-container">
              <div class="book-cover-wrapper" id="bookCoverWrapper">
                <!-- Populated by JavaScript -->
              </div>
              <div class="book-actions">
                <button class="btn btn-primary btn-full" id="requestTradeBtn">
                  Pieprasīt apmaiņu
                </button>
                <button class="btn btn-outline btn-full" id="wishlistBtn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wishlist-icon">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                  </svg>
                  <span id="wishlistBtnText">Pievienot vēlmju sarakstam</span>
                </button>
              </div>
            </div>
            
            <div class="book-info-container">
              <h1 class="book-title" id="bookTitle"><!-- Populated by JavaScript --></h1>
              <p class="book-author" id="bookAuthor"><!-- Populated by JavaScript --></p>
              <div class="book-tags" id="bookTags"><!-- Populated by JavaScript --></div>
              <h2 class="section-header">Par šo grāmatu</h2>
              <div class="book-description">
                <p id="bookDescription"><!-- Populated by JavaScript --></p>
              </div>
              <h2 class="section-header">Pievienoja</h2>
              <div class="owner-card" id="ownerCard"><!-- Populated by JavaScript --></div>
              <h2 class="section-header">Iespējams, tev patiks arī</h2>
              <div class="similar-books" id="similarBooks"><!-- Populated by JavaScript --></div>
            </div>
          </div>
        </div>

        <div id="tradeModal" class="modal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Pieprasīt grāmatu apmaiņu</h3>
              <button class="close-button" id="closeTradeModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
            <div class="modal-body">
              <p>Izvēlies vienu no savām grāmatām, ko piedāvāt apmaiņā pret "<strong id="tradeBookTitle"></strong>".</p>
              <div class="form-group">
                <label for="tradeSelect">Jūsu grāmata apmaiņai:</label>
                <div class="select-wrapper">
                  <select id="tradeSelect" class="form-select">
                    <option value="">Izvēlies grāmatu</option>
                    <!-- Populated by JavaScript -->
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="tradeMessage">Pievienot ziņu (neobligāti):</label>
                <textarea id="tradeMessage" class="form-textarea" rows="3" placeholder="Es labprāt apmainītu šo grāmatu ar jums!"></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary btn-full" id="sendTradeRequestBtn">
                Nosūtīt apmaiņas pieprasījumu
              </button>
            </div>
          </div>
        </div>
      </main>

      <footer class="footer">
        <div class="container">
          <div class="footer-grid">
            <div class="footer-brand">
              <a href="index.php" class="brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <h2 class="brand-name">BookSwap</h2>
              </a>
              <p>Sazinieties ar citiem lasītājiem un apmainieties ar grāmatām, kuras mīlat.</p>
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
                <li><a href="how-it-works.php">Kā tas darbojas</a></li>
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
            <p>© <span id="currentYearFooter"></span> BookSwap. Visas tiesības aizsargātas.</p>
          </div>
        </div>
      </footer>
    </div>

    <div id="toast" class="toast">
      <div class="toast-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon success"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <div class="toast-message" id="toastMessage"></div>
      </div>
      <button class="toast-close" id="toastClose">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    <script>
        // Передаем данные из PHP в JavaScript
        const bookDetailsFromServer = <?php echo json_encode($book_details_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const userBooksForTradeFromServer = <?php echo json_encode($user_books_for_trade_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const similarBooksFromServer = <?php echo json_encode($similar_books_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="script.js"></script> <!-- Общий скрипт, если есть (например, для мобильного меню) -->
    <script> // Логика из book.js теперь здесь или в отдельном book_dynamic.js
    document.addEventListener('DOMContentLoaded', function() {
        // Используем данные, переданные с сервера
        const bookDetails = bookDetailsFromServer;
        const userBooks = userBooksForTradeFromServer; // Для модального окна
        const similarBooks = similarBooksFromServer; // Для похожих книг

        const bookCoverWrapper = document.getElementById('bookCoverWrapper');
        const bookTitleEl = document.getElementById('bookTitle');
        const bookAuthorEl = document.getElementById('bookAuthor');
        const bookTagsEl = document.getElementById('bookTags');
        const bookDescriptionEl = document.getElementById('bookDescription');
        const ownerCardEl = document.getElementById('ownerCard');
        const similarBooksContainerEl = document.getElementById('similarBooks');
        const requestTradeBtn = document.getElementById('requestTradeBtn');
        const wishlistBtn = document.getElementById('wishlistBtn');
        const wishlistBtnText = document.getElementById('wishlistBtnText');
        const tradeModal = document.getElementById('tradeModal');
        const closeTradeModal = document.getElementById('closeTradeModal');
        const tradeBookTitleEl = document.getElementById('tradeBookTitle');
        const tradeSelectEl = document.getElementById('tradeSelect');
        const tradeMessageEl = document.getElementById('tradeMessage');
        const sendTradeRequestBtn = document.getElementById('sendTradeRequestBtn');
        const toastEl = document.getElementById('toast');
        const toastMessageEl = document.getElementById('toastMessage');
        const toastCloseEl = document.getElementById('toastClose');
        const currentYearFooterEl = document.getElementById('currentYearFooter');
         if(currentYearFooterEl) currentYearFooterEl.textContent = new Date().getFullYear();

        let isWishlisted = false; // Это состояние нужно будет синхронизировать с БД

        function svgIconForBook(size = "64") {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>`;
        }
        function svgIconForUser(size = "24"){
             return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
        }


        function populateBookDetails() {
            if (!bookDetails || Object.keys(bookDetails).length === 0) {
                // Показать сообщение, что книга не найдена или недоступна
                if(document.querySelector('.book-detail-grid')) {
                    document.querySelector('.book-detail-grid').innerHTML = '<p style="text-align:center; padding: 2rem;">Grāmata nav atrasta vai nav pieejama.</p>';
                }
                return;
            }

            document.title = `${bookDetails.title || 'Grāmata'} - BookSwap`;
            
            if (bookCoverWrapper) {
                if (bookDetails.coverImage) {
                    const img = document.createElement('img');
                    img.src = bookDetails.coverImage + '?t=' + new Date().getTime();
                    img.alt = `Cover of ${bookDetails.title || 'Book'}`;
                    img.className = 'book-cover';
                    img.onerror = function() {
                        this.style.display = 'none';
                        const fallback = document.createElement('div');
                        fallback.className = 'book-cover-fallback';
                        fallback.innerHTML = svgIconForBook();
                        bookCoverWrapper.innerHTML = ''; 
                        bookCoverWrapper.appendChild(fallback);
                    };
                    bookCoverWrapper.appendChild(img);
                } else {
                    const fallbackCover = document.createElement('div');
                    fallbackCover.className = 'book-cover-fallback';
                    fallbackCover.innerHTML = svgIconForBook();
                    bookCoverWrapper.appendChild(fallbackCover);
                }
            }
            
            if (bookTitleEl) bookTitleEl.textContent = bookDetails.title || 'Nav nosaukuma';
            if (bookAuthorEl) bookAuthorEl.textContent = bookDetails.author ? `by ${bookDetails.author}` : 'Nezināms autors';
            
            if (bookTagsEl) {
                bookTagsEl.innerHTML = '';
                const tags = ['genre', 'language', 'year', 'condition'];
                const icons = {
                    genre: '', // No icon for genre text
                    language: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8l5 5 5-5"></path><path d="M12 16V8"></path></svg>',
                    year: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                    condition: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>'
                };
                tags.forEach(tagKey => {
                    if (bookDetails[tagKey]) {
                        const tagEl = document.createElement('span');
                        tagEl.className = 'book-tag';
                        tagEl.innerHTML = `${icons[tagKey] || ''} ${bookDetails[tagKey]}`;
                        bookTagsEl.appendChild(tagEl);
                    }
                });
            }
            
            if (bookDescriptionEl) bookDescriptionEl.innerHTML = bookDetails.description || 'Apraksts nav pieejams.';
            
            if (ownerCardEl) {
                const ownerInitial = bookDetails.listedBy ? bookDetails.listedBy.charAt(0).toUpperCase() : 'U';
                ownerCardEl.innerHTML = `
                    <div class="owner-info">
                        ${bookDetails.userImage ? `<img src="${bookDetails.userImage}?t=${new Date().getTime()}" alt="${bookDetails.listedBy || 'Owner'}" class="owner-image">` : `<div class="owner-image-placeholder" style="width:48px; height:48px; background-color:var(--color-light-gray); border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right: var(--spacing-3); font-weight:bold;">${ownerInitial}</div>`}
                        <div class="owner-details">
                            <h3 class="owner-name">${bookDetails.listedBy || 'Nezināms lietotājs'}</h3>
                            <div class="owner-stats">
                                <div class="stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="var(--color-burgundy)" stroke="var(--color-burgundy)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <span>${bookDetails.userRating !== undefined && bookDetails.userRating !== null ? bookDetails.userRating.toFixed(1) : 'N/A'}</span>
                                </div>
                                <div class="stat">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                                    <span>${bookDetails.tradeCount || 0} maiņas</span>
                                </div>
                                <span class="stat" style="font-size: 0.7rem; color: var(--color-gray); margin-left: auto;">Pievienots: ${bookDetails.listedDate || ''}</span>
                            </div>
                        </div>
                        <button class="btn btn-outline owner-message" onclick="window.location.href='messages.php?recipient_id=${bookDetails.userId}'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            Ziņa
                        </button>
                    </div>`;
            }
            
            if (similarBooksContainerEl && similarBooks && similarBooks.length > 0) {
                similarBooksContainerEl.innerHTML = '';
                similarBooks.forEach(book => {
                    const bookElement = document.createElement('a');
                    bookElement.className = 'similar-book';
                    bookElement.href = `book.php?id=${book.id}`;
                    
                    const coverDiv = document.createElement('div');
                    coverDiv.className = 'similar-cover';
                    if (book.coverImage) {
                        const img = document.createElement('img');
                        img.src = book.coverImage + '?t=' + new Date().getTime();
                        img.alt = `Cover of ${book.title || 'Book'}`;
                        img.onerror = function() { 
                            this.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.className = 'book-cover-fallback';
                            fallback.style="height:100%; display:flex; align-items:center; justify-content:center; background-color: var(--color-paper); color: var(--color-leather);"
                            fallback.innerHTML = svgIconForBook('32');
                            this.parentElement.innerHTML = '';
                            this.parentElement.appendChild(fallback);
                        };
                        coverDiv.appendChild(img);
                    } else {
                        coverDiv.innerHTML = `<div class="book-cover-fallback" style="height:100%; display:flex; align-items:center; justify-content:center; background-color: var(--color-paper); color: var(--color-leather);">${svgIconForBook('32')}</div>`;
                    }
                    
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'similar-info';
                    infoDiv.innerHTML = `
                        <h3 class="similar-title">${book.title || 'Nav nosaukuma'}</h3>
                        <p class="similar-author">${book.author ? book.author : 'Nezināms autors'}</p>
                        <span class="similar-genre">${book.genre || ''}</span>`;
                    
                    bookElement.appendChild(coverDiv);
                    bookElement.appendChild(infoDiv);
                    similarBooksContainerEl.appendChild(bookElement);
                });
            } else if (similarBooksContainerEl) {
                similarBooksContainerEl.innerHTML = '<p>Līdzīgas grāmatas nav atrastas.</p>';
            }

            if (tradeBookTitleEl) tradeBookTitleEl.textContent = bookDetails.title || '';
            
            if (tradeSelectEl && userBooks && userBooks.length > 0) {
                tradeSelectEl.innerHTML = '<option value="">Izvēlies grāmatu</option>';
                userBooks.forEach(book => {
                    const option = document.createElement('option');
                    option.value = book.id;
                    option.textContent = `${book.title || 'Nezināma grāmata'} by ${book.author || 'Nezināms autors'}`;
                    tradeSelectEl.appendChild(option);
                });
            } else if (tradeSelectEl) {
                 tradeSelectEl.innerHTML = '<option value="">Jums nav pieejamu grāmatu maiņai</option>';
                 if(requestTradeBtn) requestTradeBtn.disabled = true; // Блокируем кнопку, если нет книг для обмена
            }
        }
        
        populateBookDetails();
        
        if(requestTradeBtn && tradeModal) {
            requestTradeBtn.addEventListener('click', function() {
                if (userBooks && userBooks.length > 0) { // Открывать модалку только если есть книги для обмена
                    tradeModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                } else {
                    showToast('Jums nav pieejamu grāmatu, ko piedāvāt maiņai.', 'error');
                }
            });
        }
        
        if(closeTradeModal && tradeModal) {
            closeTradeModal.addEventListener('click', function() {
                tradeModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        }
        
        if(wishlistBtn) {
            // TODO: Check initial wishlist status from server
            wishlistBtn.addEventListener('click', function() {
                isWishlisted = !isWishlisted;
                const wishlistIcon = wishlistBtn.querySelector('.wishlist-icon');
                if (isWishlisted) {
                    wishlistIcon.classList.add('filled');
                    wishlistIcon.setAttribute('fill', 'var(--color-burgundy)');
                    if(wishlistBtnText) wishlistBtnText.textContent = 'Vēlmju sarakstā';
                    showToast('Grāmata pievienota vēlmju sarakstam');
                    // AJAX call to add to wishlist
                } else {
                    wishlistIcon.classList.remove('filled');
                    wishlistIcon.setAttribute('fill', 'none');
                     if(wishlistBtnText) wishlistBtnText.textContent = 'Pievienot vēlmju sarakstam';
                    showToast('Grāmata noņemta no vēlmju saraksta');
                    // AJAX call to remove from wishlist
                }
            });
        }
        
        if(sendTradeRequestBtn && tradeModal && tradeSelectEl && tradeMessageEl && bookDetails) {
            sendTradeRequestBtn.addEventListener('click', function() {
                const selectedBookId = tradeSelectEl.value;
                const messageContent = tradeMessageEl.value;
                
                if (!selectedBookId) {
                    showToast('Lūdzu, izvēlieties grāmatu maiņai', 'error');
                    return;
                }
                
                // TODO: AJAX call to send trade request
                // Endpoint: submit_trade_request.php (пример)
                // Data: { offered_book_id: selectedBookId, wanted_book_id: bookDetails.id, message: messageContent, recipient_id: bookDetails.userId }

                console.log('Sending trade request:', {
                    offeredBookId: selectedBookId,
                    wantedBookId: bookDetails.id,
                    message: messageContent,
                    recipientId: bookDetails.userId 
                });

                tradeModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                tradeSelectEl.value = '';
                tradeMessageEl.value = '';
                showToast(`Maiņas pieprasījums par "${bookDetails.title || 'grāmatu'}" nosūtīts ${bookDetails.listedBy || 'lietotājam'}`);
            });
        }
        
        if(toastCloseEl && toastEl) {
            toastCloseEl.addEventListener('click', function() {
                hideToast();
            });
        }
        
        function showToast(message, type = 'success') {
            if (!toastEl || !toastMessageEl) return;
            toastMessageEl.textContent = message;
            const toastIcon = toastEl.querySelector('.toast-icon');
            
            toastEl.classList.remove('success', 'error'); // Remove previous types
            if (type === 'error') {
                toastEl.classList.add('error');
                if(toastIcon) toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
            } else {
                toastEl.classList.add('success');
                 if(toastIcon) toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
            }
            toastEl.classList.add('show');
            setTimeout(hideToast, 3000);
        }
        
        function hideToast() {
            if(toastEl) toastEl.classList.remove('show');
        }

    });
    </script>
  </body>
</html>