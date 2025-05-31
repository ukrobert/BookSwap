<?php
require_once 'session_check.php';
require_once 'connect_db.php';

$book_id = null;
$book_details_for_js = null;
$similar_books_for_js = [];
$user_books_for_trade_js = [];
$book_not_found_error = false;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = intval($_GET['id']);
} else {
    $book_not_found_error = true;
}

if ($book_id && !$book_not_found_error) {
    try {
        $stmt_book = $savienojums->prepare("
            SELECT 
                b.GramatasID, b.Nosaukums, b.Autors, b.Zanrs, b.Valoda, 
                b.IzdosanasGads, b.Apraksts, b.Attels AS GramataAttels, 
                b.Status AS GramataStatus, b.Stavoklis, b.PievienosanasDatums,
                u.LietotajsID AS OwnerLietotajsID, u.Lietotajvards AS OwnerLietotajvards, 
                u.ProfilaAttels AS OwnerProfilaAttels, u.VidejaisVertejums AS OwnerVidejaisVertejums
            FROM bookswap_books b
            JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
            WHERE b.GramatasID = ? 
        "); 
        
        if (!$stmt_book) {
            throw new Exception("Prepare failed (book): " . $savienojums->error);
        }
        $stmt_book->bind_param("i", $book_id);
        $stmt_book->execute();
        $result_book = $stmt_book->get_result();

        if ($book_data = $result_book->fetch_assoc()) {
            $book_cover_path = '';
            if (!empty($book_data['GramataAttels'])) {
                if (filter_var($book_data['GramataAttels'], FILTER_VALIDATE_URL)) {
                    $book_cover_path = htmlspecialchars($book_data['GramataAttels']);
                } elseif (file_exists($book_data['GramataAttels'])) {
                    $book_cover_path = htmlspecialchars($book_data['GramataAttels']);
                }
            }
            
            $owner_profile_pic_path = '';
             if (!empty($book_data['OwnerProfilaAttels'])) {
                if (filter_var($book_data['OwnerProfilaAttels'], FILTER_VALIDATE_URL)) {
                    $owner_profile_pic_path = htmlspecialchars($book_data['OwnerProfilaAttels']);
                } elseif (file_exists($book_data['OwnerProfilaAttels'])) {
                    $owner_profile_pic_path = htmlspecialchars($book_data['OwnerProfilaAttels']);
                }
            }

            $owner_trade_count = 0; 
            $stmt_trade_count = $savienojums->prepare("
                SELECT COUNT(*) as trade_count 
                FROM bookswap_exchange_requests 
                WHERE (IniciatorsID = ? OR AdresatsID = ?) AND Status = 'Pabeigts'
            ");
            if($stmt_trade_count){
                $stmt_trade_count->bind_param("ii", $book_data['OwnerLietotajsID'], $book_data['OwnerLietotajsID']);
                $stmt_trade_count->execute();
                $result_trade_count = $stmt_trade_count->get_result();
                if($row_trade_count = $result_trade_count->fetch_assoc()){
                    $owner_trade_count = $row_trade_count['trade_count'];
                }
                $stmt_trade_count->close();
            }

            $date_added = new DateTime($book_data['PievienosanasDatums']);
            $now = new DateTime();
            $interval = $now->diff($date_added);
            $listed_date_text = "Nesen";
            if ($interval->y > 0) $listed_date_text = $interval->y . " g. atpakaļ";
            else if ($interval->m > 0) $listed_date_text = $interval->m . " mēn. atpakaļ";
            else if ($interval->d > 0) $listed_date_text = $interval->d . " d. atpakaļ";
            else if ($interval->h > 0) $listed_date_text = $interval->h . " st. atpakaļ";
            else if ($interval->i > 0) $listed_date_text = $interval->i . " min. atpakaļ";

            $book_details_for_js = [
                'id' => $book_data['GramatasID'],
                'title' => $book_data['Nosaukums'],
                'author' => $book_data['Autors'],
                'coverImage' => $book_cover_path,
                'genre' => $book_data['Zanrs'],
                'language' => $book_data['Valoda'],
                'year' => $book_data['IzdosanasGads'],
                'condition' => $book_data['Stavoklis'], 
                'description' => $book_data['Apraksts'],
                'listedBy' => $book_data['OwnerLietotajvards'],
                'userId' => $book_data['OwnerLietotajsID'], 
                'userImage' => $owner_profile_pic_path,
                'listedDate' => $listed_date_text, 
                'userRating' => $book_data['OwnerVidejaisVertejums'] ?? 0.0, 
                'tradeCount' => $owner_trade_count 
            ];

            $current_book_genre = $book_data['Zanrs'];
            $stmt_similar = $savienojums->prepare("
                SELECT GramatasID, Nosaukums, Autors, Attels, Zanrs 
                FROM bookswap_books 
                WHERE Zanrs = ? AND GramatasID != ? AND Status = 'Pieejama'
                LIMIT 3
            ");
            if (!$stmt_similar) {
                throw new Exception("Prepare failed (similar): " . $savienojums->error);
            }
            $stmt_similar->bind_param("si", $current_book_genre, $book_id);
            $stmt_similar->execute();
            $result_similar = $stmt_similar->get_result();
            while ($similar_row = $result_similar->fetch_assoc()) {
                 $similar_cover_path = '';
                if (!empty($similar_row['Attels'])) {
                    if (filter_var($similar_row['Attels'], FILTER_VALIDATE_URL)) {
                        $similar_cover_path = htmlspecialchars($similar_row['Attels']);
                    } elseif (file_exists($similar_row['Attels'])) {
                        $similar_cover_path = htmlspecialchars($similar_row['Attels']);
                    }
                }
                $similar_books_for_js[] = [
                    'id' => $similar_row['GramatasID'],
                    'title' => $similar_row['Nosaukums'],
                    'author' => $similar_row['Autors'],
                    'coverImage' => $similar_cover_path,
                    'genre' => $similar_row['Zanrs']
                ];
            }
            $stmt_similar->close();
        } else {
            $book_not_found_error = true;
        }
        $stmt_book->close();

        if (isLoggedIn() && !$book_not_found_error) {
            $current_user_id_php = $_SESSION['user_id']; // Используем другую переменную, чтобы не конфликтовать с bookDetails.userId
            $stmt_user_books = $savienojums->prepare("
                SELECT GramatasID, Nosaukums, Autors 
                FROM bookswap_books 
                WHERE LietotajsID = ? AND Status = 'Pieejama' AND GramatasID != ?
            ");
            if (!$stmt_user_books) {
                 throw new Exception("Prepare failed (user_books): " . $savienojums->error);
            }
            $stmt_user_books->bind_param("ii", $current_user_id_php, $book_id);
            $stmt_user_books->execute();
            $result_user_books = $stmt_user_books->get_result();
            while ($user_book_row = $result_user_books->fetch_assoc()) {
                $user_books_for_trade_js[] = [
                    'id' => $user_book_row['GramatasID'],
                    'title' => $user_book_row['Nosaukums'],
                    'author' => $user_book_row['Autors']
                ];
            }
            $stmt_user_books->close();
        }
    } catch (Exception $e) {
        error_log("Error in book.php: " . $e->getMessage());
        $book_not_found_error = true; 
        $book_details_for_js = null;
    }
    if ($savienojums) {
        $savienojums->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $book_details_for_js ? htmlspecialchars($book_details_for_js['title']) . ' - BookSwap' : 'Grāmata nav atrasta - BookSwap'; ?></title>
    <meta name="description" content="View book details and request a trade." />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="book.css">
  </head>
  <body data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
    <div id="root">
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
        
        <?php if ($book_not_found_error || !$book_details_for_js): ?>
            <div class="container" style="text-align: center; padding: 40px;">
                <h2>Grāmata nav atrasta</h2>
                <p>Meklētā grāmata nav pieejama vai neeksistē.</p>
                <a href="browse.php" class="btn btn-primary" style="margin-top: 20px;">Atpakaļ uz pārlūkošanu</a>
            </div>
        <?php else: ?>
            <div class="container">
              <div class="book-detail-grid">
                <div class="book-cover-container">
                  <div class="book-cover-wrapper" id="bookCoverWrapper"></div>
                  <div class="book-actions">
                    <button class="btn btn-primary btn-full" id="requestTradeBtn">Pieprasīt apmaiņu</button>
                    <button class="btn btn-outline btn-full" id="wishlistBtn">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wishlist-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                      <span id="wishlistBtnText">Pievienot vēlmju sarakstam</span>
                    </button>
                  </div>
                </div>
                <div class="book-info-container">
                  <h1 class="book-title" id="bookTitle"></h1>
                  <p class="book-author" id="bookAuthor"></p>
                  <div class="book-tags" id="bookTags"></div>
                  <h2 class="section-header">Par šo grāmatu</h2>
                  <div class="book-description"><p id="bookDescription"></p></div>
                  <h2 class="section-header">Pievienoja</h2>
                  <div class="owner-card" id="ownerCard"></div>
                  <h2 class="section-header">Iespējams, tev patiks arī</h2>
                  <div class="similar-books" id="similarBooks"></div>
                </div>
              </div>
            </div>
            <div id="tradeModal" class="modal">
              <div class="modal-content">
                <div class="modal-header">
                  <h3>Pieprasīt grāmatu apmaiņu</h3>
                  <button class="close-button" id="closeTradeModal">×</button>
                </div>
                <div class="modal-body">
                  <p>Izvēlies vienu no savām grāmatām, ko piedāvāt apmaiņā pret "<span id="tradeBookTitle"></span>".</p>
                  <div class="form-group">
                    <label for="tradeSelect">Izvēlies savu grāmatu apmaiņai:</label>
                    <div class="select-wrapper"><select id="tradeSelect" class="form-select"><option value="">Izvēlies grāmatu</option></select></div>
                  </div>
                  <div class="form-group">
                    <label for="tradeMessage">Pievienot ziņu (pēc izvēles):</label>
                    <textarea id="tradeMessage" class="form-textarea" rows="3" placeholder="Es labprāt apmainītu šo grāmatu ar jums!"></textarea>
                  </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary btn-full" id="sendTradeRequestBtn">Nosūtīt apmaiņas pieprasījumu</button></div>
              </div>
            </div>
        <?php endif; ?>
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
            <p>© <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
          </div>
        </div>
      </footer>
    </div>

    <div id="toast" class="toast">
      <div class="toast-content">
        <svg class="toast-icon success" width="20" height="20" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <div class="toast-message" id="toastMessage"></div>
      </div>
      <button class="toast-close" id="toastClose">×</button>
    </div>
    
    <script>
    // Этот скрипт теперь должен быть встроен в book.php
    document.addEventListener('DOMContentLoaded', function() {
        const bookDetails = <?php echo $book_details_for_js ? json_encode($book_details_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null'; ?>;
        const userBooksForTrade = <?php echo json_encode($user_books_for_trade_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const similarBooksData = <?php echo json_encode($similar_books_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const currentLoggedInUserId = <?php echo isLoggedIn() ? json_encode($_SESSION['user_id']) : 'null'; ?>;


        const bookCoverWrapper = document.getElementById('bookCoverWrapper');
        const bookTitleEl = document.getElementById('bookTitle');
        const bookAuthorEl = document.getElementById('bookAuthor');
        const bookTagsEl = document.getElementById('bookTags');
        const bookDescriptionEl = document.getElementById('bookDescription');
        const ownerCardEl = document.getElementById('ownerCard');
        const similarBooksContainerEl = document.getElementById('similarBooks');
        const requestTradeBtnEl = document.getElementById('requestTradeBtn');
        const wishlistBtnEl = document.getElementById('wishlistBtn');
        const wishlistBtnTextEl = document.getElementById('wishlistBtnText');
        const tradeModalEl = document.getElementById('tradeModal');
        const closeTradeModalBtn = document.getElementById('closeTradeModal');
        const tradeBookTitleEl = document.getElementById('tradeBookTitle');
        const tradeSelectEl = document.getElementById('tradeSelect');
        const tradeMessageEl = document.getElementById('tradeMessage');
        const sendTradeRequestBtnEl = document.getElementById('sendTradeRequestBtn');
        const toastEl = document.getElementById('toast');
        const toastMessageSpan = document.getElementById('toastMessage');
        const toastCloseBtn = document.getElementById('toastClose');
        
        let isWishlisted = false; // TODO: Fetch from DB

        function escapeJsString(str) {
            if (str === null || typeof str === 'undefined') return '';
            return str.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '\\r');
        }

        window.initiateChatFromPage = function(userId, userName) {
            if (typeof window.initiateChatWithUser === 'function') {
                window.initiateChatWithUser(userId, userName);
            } else {
                console.error("Chat function 'initiateChatWithUser' not found.");
                showToast('Čata funkcionalitāte pašlaik nav pieejama.', 'error');
            }
        }

        if (!bookDetails) {
            const grid = document.querySelector('.book-detail-grid');
            if(grid) grid.style.display = 'none';
            return; 
        }

        function populateBookDetails() {
            document.title = `${bookDetails.title || 'Grāmata'} - BookSwap`;
            
            if (bookCoverWrapper) {
                bookCoverWrapper.innerHTML = ''; // Clear
                if (bookDetails.coverImage) {
                    const img = document.createElement('img');
                    img.src = bookDetails.coverImage + '?t=' + new Date().getTime();
                    img.alt = `Cover of ${bookDetails.title}`;
                    img.className = 'book-cover';
                    img.onerror = function() { this.parentElement.innerHTML = fallbackCoverHTML(64); };
                    bookCoverWrapper.appendChild(img);
                } else {
                    bookCoverWrapper.innerHTML = fallbackCoverHTML(64);
                }
            }
            
            if (bookTitleEl) bookTitleEl.textContent = bookDetails.title || 'Nav nosaukuma';
            if (bookAuthorEl) bookAuthorEl.textContent = bookDetails.author ? `by ${bookDetails.author}` : 'Nezināms autors';
            
            if (bookTagsEl) {
                bookTagsEl.innerHTML = '';
                if(bookDetails.genre) bookTagsEl.innerHTML += `<span class="book-tag">${bookDetails.genre}</span>`;
                if(bookDetails.language) bookTagsEl.innerHTML += `<span class="book-tag"><svg width="14" height="14" viewBox="0 0 24 24"><path d="M5 8l5 5 5-5"></path><path d="M12 16V8"></path></svg> ${bookDetails.language}</span>`;
                if(bookDetails.year) bookTagsEl.innerHTML += `<span class="book-tag"><svg width="14" height="14" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> ${bookDetails.year}</span>`;
                if(bookDetails.condition) bookTagsEl.innerHTML += `<span class="book-tag"><svg width="14" height="14" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> ${bookDetails.condition}</span>`;
            }
            
            if (bookDescriptionEl) bookDescriptionEl.textContent = bookDetails.description || 'Apraksts nav pieejams.';
            
            if (ownerCardEl) {
                let ownerImgHtml = fallbackOwnerAvatarHTML();
                if (bookDetails.userImage) {
                    ownerImgHtml = `<img src="${bookDetails.userImage}?t=${new Date().getTime()}" alt="${bookDetails.listedBy || 'Lietotājs'}" class="owner-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"/>
                                    ${fallbackOwnerAvatarHTML(true)}`;
                }
                const messageButtonOnClick = currentLoggedInUserId === bookDetails.userId ? 
                    `alert('Jūs nevarat sūtīt ziņu sev.'); return false;` : 
                    `initiateChatFromPage(${bookDetails.userId}, '${escapeJsString(bookDetails.listedBy || 'Nezināms lietotājs')}')`;

                ownerCardEl.innerHTML = `
                    <div class="owner-info">
                        ${ownerImgHtml}
                        <div class="owner-details">
                        <h3 class="owner-name">${bookDetails.listedBy || 'Nezināms lietotājs'}</h3>
                        <div class="owner-stats">
                            <div class="stat"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> <span>${parseFloat(bookDetails.userRating).toFixed(1) || 'N/A'}</span></div>
                            <div class="stat"><svg width="14" height="14" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg> <span>${bookDetails.tradeCount || 0} maiņas</span></div>
                        </div></div>
                        <button type="button" class="btn btn-outline owner-message" onclick="${messageButtonOnClick}" ${currentLoggedInUserId === bookDetails.userId ? 'disabled' : ''}>
                        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> Ziņa
                        </button>
                    </div>`;
            }
            
            if(similarBooksContainerEl && similarBooksData) {
                similarBooksContainerEl.innerHTML = '';
                similarBooksData.forEach(book => {
                    const bookElement = document.createElement('a');
                    bookElement.className = 'similar-book';
                    bookElement.href = `book.php?id=${book.id}`;
                    let coverHtml = fallbackCoverHTML(32, '128px');
                    if (book.coverImage) {
                        coverHtml = `<img src="${book.coverImage}?t=${new Date().getTime()}" alt="Cover of ${book.title}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"/> ${fallbackCoverHTML(32, '128px', true)}`;
                    }
                    bookElement.innerHTML = `<div class="similar-cover">${coverHtml}</div><div class="similar-info"><h3 class="similar-title">${book.title || ''}</h3><p class="similar-author">${book.author || ''}</p><span class="similar-genre">${book.genre || ''}</span></div>`;
                    similarBooksContainerEl.appendChild(bookElement);
                });
            }
            
            if (tradeBookTitleEl) tradeBookTitleEl.textContent = bookDetails.title || '';
            
            if (tradeSelectEl && userBooksForTrade) {
                tradeSelectEl.innerHTML = '<option value="">Izvēlies grāmatu</option>';
                if (userBooksForTrade.length > 0) {
                    userBooksForTrade.forEach(book => {
                        const option = document.createElement('option');
                        option.value = book.id;
                        option.textContent = `${book.title || ''} by ${book.author || ''}`;
                        tradeSelectEl.appendChild(option);
                    });
                } else {
                    tradeSelectEl.innerHTML = '<option value="" disabled>Jums nav grāmatu maiņai</option>';
                     if (requestTradeBtnEl) requestTradeBtnEl.disabled = true;
                }
            }

            if (requestTradeBtnEl) {
                 if (currentLoggedInUserId === bookDetails.userId) {
                    requestTradeBtnEl.disabled = true;
                    requestTradeBtnEl.textContent = 'Tā ir jūsu grāmata';
                } else if (!currentLoggedInUserId) {
                    requestTradeBtnEl.disabled = true;
                    requestTradeBtnEl.textContent = 'Pieslēdzieties, lai pieprasītu';
                    requestTradeBtnEl.onclick = function() { window.location.href = 'login.php'; };
                } else if (userBooksForTrade.length === 0) {
                    requestTradeBtnEl.disabled = true;
                    requestTradeBtnEl.textContent = 'Nav grāmatu maiņai';
                }
            }
        }

        function fallbackCoverHTML(size = 64, height = '100%', initiallyHidden = false) {
            return `<div class="book-cover-fallback" style="display:${initiallyHidden ? 'none' : 'flex'}; align-items:center; justify-content:center; width:100%; height:${height}; background-color: var(--color-light-gray);"><svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg></div>`;
        }
        function fallbackOwnerAvatarHTML(initiallyHidden = false) {
             return `<div style="width:48px; height:48px; border-radius:50%; background-color:var(--color-paper); display:${initiallyHidden ? 'none' : 'flex'}; align-items:center; justify-content:center; margin-right:var(--spacing-3);"><svg width="24" height="24" viewBox="0 0 24 24" fill="var(--color-gray)"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>`;
        }
        
        if (bookDetails) populateBookDetails();

        if(requestTradeBtnEl && tradeModalEl) {
            requestTradeBtnEl.addEventListener('click', function() {
                if (currentLoggedInUserId) {
                     if (userBooksForTrade && userBooksForTrade.length > 0) {
                        tradeModalEl.classList.add('active');
                        document.body.style.overflow = 'hidden';
                     } else if (bookDetails && currentLoggedInUserId !== bookDetails.userId) {
                        showToast('Jums nav pieejamu grāmatu, ko piedāvāt maiņai.', 'error');
                     }
                } else {
                    showToast('Lūdzu, pieslēdzieties, lai pieprasītu maiņu.', 'error');
                    setTimeout(() => { window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href); }, 2000);
                }
            });
        }
        
        if(closeTradeModalBtn && tradeModalEl) {
            closeTradeModalBtn.addEventListener('click', function() {
                tradeModalEl.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        }
        
        if(wishlistBtnEl) {
            // TODO: Check actual wishlist status from DB
            wishlistBtnEl.addEventListener('click', function() {
                if (!currentLoggedInUserId) {
                    showToast('Lūdzu, pieslēdzieties, lai pievienotu vēlmju sarakstam.', 'error');
                    setTimeout(() => { window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href); }, 2000);
                    return;
                }
                isWishlisted = !isWishlisted;
                const wishlistIcon = wishlistBtnEl.querySelector('.wishlist-icon');
                if (isWishlisted) {
                    wishlistIcon.classList.add('filled');
                    wishlistIcon.setAttribute('fill', 'var(--color-burgundy)');
                    if(wishlistBtnTextEl) wishlistBtnTextEl.textContent = 'Vēlmju sarakstā';
                    showToast('Grāmata pievienota vēlmju sarakstam');
                } else {
                    wishlistIcon.classList.remove('filled');
                    wishlistIcon.setAttribute('fill', 'none');
                    if(wishlistBtnTextEl) wishlistBtnTextEl.textContent = 'Pievienot vēlmju sarakstam';
                    showToast('Grāmata noņemta no vēlmju saraksta');
                }
            });
        }
        
        if(sendTradeRequestBtnEl && tradeModalEl && tradeSelectEl && bookDetails) {
            sendTradeRequestBtnEl.addEventListener('click', function() {
                const selectedBookId = tradeSelectEl.value;
                const messageContent = tradeMessageEl ? tradeMessageEl.value : '';
                if (!selectedBookId) {
                    showToast('Lūdzu, izvēlieties grāmatu, ko piedāvāt maiņai.', 'error'); return;
                }
                // TODO: AJAX call to send trade request
                console.log("Sending trade request:", {
                    offered_book_id: selectedBookId,
                    requested_book_id: bookDetails.id,
                    message: messageContent,
                    receiver_id: bookDetails.userId // ID of the book owner
                });
                tradeModalEl.classList.remove('active');
                document.body.style.overflow = 'auto';
                tradeSelectEl.value = '';
                if(tradeMessageEl) tradeMessageEl.value = '';
                showToast(`Maiņas pieprasījums par "${bookDetails.title}" nosūtīts ${bookDetails.listedBy}`);
            });
        }
        
        if(toastCloseBtn && toastEl) {
            toastCloseBtn.addEventListener('click', hideToast);
        }
        
        function showToast(message, type = 'success') {
            if (!toastEl || !toastMessageSpan) return;
            toastMessageSpan.textContent = message;
            const toastIcon = toastEl.querySelector('.toast-icon');
            toastEl.classList.remove('success', 'error', 'show'); // Reset classes
            toastEl.classList.add(type); // Add current type
            if(toastIcon) { /* Update icon based on type if needed */ }
            toastEl.classList.add('show');
            setTimeout(hideToast, 3000);
        }
        function hideToast() { if(toastEl) toastEl.classList.remove('show'); }
        
        const currentYearSpan = document.getElementById('currentYear');
        if(currentYearSpan) currentYearSpan.textContent = new Date().getFullYear();
        
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('active'));
        }
    });
    </script>
    
    <!-- Chat Widget HTML and JS (as provided in the previous step) -->
    <!-- Include chat_widget.php or paste HTML here -->
    <link rel="stylesheet" href="chat.css?v=<?php echo time(); ?>">
    <script src="chat.js?v=<?php echo time(); ?>"></script>

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
  </body>
</html>