<?php
require_once 'session_check.php';
require_once 'connect_db.php';

$books_data_for_js = []; 
$all_genres_from_db = [];
$all_conditions_from_db = [];

// WISHLIST: Fetch user's wishlist IDs
$user_wishlist_ids_for_js = [];
if (isLoggedIn()) {
    $current_user_id_browse = $_SESSION['user_id'];
    if ($savienojums && !is_string($savienojums) && mysqli_ping($savienojums)) { // Check if connection is valid
        $stmt_user_wish = $savienojums->prepare("SELECT GramatasID FROM bookswap_wishlist WHERE LietotajsID = ?");
        if ($stmt_user_wish) {
            $stmt_user_wish->bind_param("i", $current_user_id_browse);
            $stmt_user_wish->execute();
            $result_user_wish = $stmt_user_wish->get_result();
            while ($row_wish = $result_user_wish->fetch_assoc()) {
                $user_wishlist_ids_for_js[] = $row_wish['GramatasID'];
            }
            $stmt_user_wish->close();
        }
    } else {
        // Attempt to reconnect if connection was closed or invalid
        require_once 'connect_db.php';
        if ($savienojums && !is_string($savienojums)) {
             $stmt_user_wish = $savienojums->prepare("SELECT GramatasID FROM bookswap_wishlist WHERE LietotajsID = ?");
            if ($stmt_user_wish) {
                $stmt_user_wish->bind_param("i", $current_user_id_browse);
                $stmt_user_wish->execute();
                $result_user_wish = $stmt_user_wish->get_result();
                while ($row_wish = $result_user_wish->fetch_assoc()) {
                    $user_wishlist_ids_for_js[] = $row_wish['GramatasID'];
                }
                $stmt_user_wish->close();
            }
        }
    }
}


try {
    if (!$savienojums || $savienojums->connect_errno) { require 'connect_db.php'; }

    $genres_query = $savienojums->query("SELECT DISTINCT Zanrs FROM bookswap_books WHERE Status = 'Pieejama' AND Zanrs IS NOT NULL AND Zanrs != '' ORDER BY Zanrs ASC");
    if($genres_query) {
        while($genre_row = $genres_query->fetch_assoc()){
            $all_genres_from_db[] = $genre_row['Zanrs'];
        }
    }

    $conditions_query = $savienojums->query("SELECT DISTINCT Stavoklis FROM bookswap_books WHERE Status = 'Pieejama' AND Stavoklis IS NOT NULL AND Stavoklis != '' ORDER BY FIELD(Stavoklis, 'Kā jauna', 'Ļoti laba', 'Laba', 'Pieņemama')");
    if($conditions_query) {
        while($condition_row = $conditions_query->fetch_assoc()){
            $all_conditions_from_db[] = $condition_row['Stavoklis'];
        }
    }

    $sql = "SELECT 
                b.GramatasID, b.Nosaukums, b.Autors, b.Zanrs, b.Valoda,
                b.IzdosanasGads, b.Apraksts, b.Attels, b.Status, b.Stavoklis,
                u.LietotajsID AS UserID_DB, u.Lietotajvards 
            FROM bookswap_books b
            JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
            WHERE b.Status = 'Pieejama'
            ORDER BY b.PievienosanasDatums DESC";
    $result = $savienojums->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $coverImagePath = '';
            if (!empty($row['Attels'])) {
                if (filter_var($row['Attels'], FILTER_VALIDATE_URL)) {
                    $coverImagePath = htmlspecialchars($row['Attels']);
                } elseif (file_exists($row['Attels'])) {
                    $coverImagePath = htmlspecialchars($row['Attels']);
                }
            }
            $books_data_for_js[] = [
                'id' => $row['GramatasID'],
                'title' => $row['Nosaukums'],
                'author' => $row['Autors'],
                'coverImage' => $coverImagePath,
                'genre' => $row['Zanrs'],
                'condition' => $row['Stavoklis'],
                'listedBy' => $row['Lietotajvards'],
                'userId' => $row['UserID_DB']
            ];
        }
    }
    if($savienojums && !is_string($savienojums) && mysqli_ping($savienojums)) $savienojums->close();
} catch (Exception $e) {
    // error_log("Exception fetching books for browse.php: " . $e->getMessage());
     if($savienojums && !is_string($savienojums) && mysqli_ping($savienojums)) $savienojums->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pārlūkot grāmatas - BookSwap</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Ensure styles.css has .wishlist-button and .filled styles -->
    <link rel="stylesheet" href="browse.css">
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
        <div class="browse-header">
          <div class="container">
            <h1 class="browse-title">Pārlūkot pieejamās grāmatas</h1>
            <p class="browse-description">Atrodiet nākamo lielisko lasāmvielu mūsu kopienas koplietošanas bibliotēkā.</p>
            <div class="search-filter-desktop">
              <div class="search-container-browse">
                <input type="text" id="searchInput" placeholder="Meklēt pēc nosaukuma, autora..." class="search-input-browse">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" fill="none" stroke="currentColor" stroke-width="2"></circle><line x1="21" y1="21" x2="16.65" y2="16.65" fill="none" stroke="currentColor" stroke-width="2"></line></svg>
              </div>
              <div class="filter-selects">
                <div class="select-wrapper">
                  <select id="genreSelect" class="filter-select">
                    <option value="all">Visi žanri</option>
                    <?php foreach ($all_genres_from_db as $genre_item): ?>
                        <option value="<?php echo htmlspecialchars($genre_item); ?>"><?php echo htmlspecialchars($genre_item); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="select-wrapper">
                  <select id="conditionSelect" class="filter-select">
                    <option value="all">Visi stāvokļi</option>
                    <?php foreach ($all_conditions_from_db as $condition_item): ?>
                        <option value="<?php echo htmlspecialchars($condition_item); ?>"><?php echo htmlspecialchars($condition_item); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="search-filter-mobile">
              <div class="search-container-browse">
                <input type="text" id="searchInputMobile" placeholder="Meklēt..." class="search-input-browse">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" fill="none" stroke="currentColor" stroke-width="2"></circle><line x1="21" y1="21" x2="16.65" y2="16.65" fill="none" stroke="currentColor" stroke-width="2"></line></svg>
              </div>
              <button id="filterButton" class="filter-button">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
              </button>
            </div>
          </div>
        </div>
        
        <div id="filterModal" class="filter-modal">
          <div class="filter-modal-content">
            <div class="filter-modal-header">
              <h3>Filtrēt grāmatas</h3>
              <button class="close-button" id="closeFilterModal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
            <div class="filter-modal-body">
              <div class="filter-group">
                <label for="genreSelectMobile">Žanrs</label>
                <select id="genreSelectMobile" class="filter-select">
                  <option value="all">Visi žanri</option>
                    <?php foreach ($all_genres_from_db as $genre_item): ?>
                        <option value="<?php echo htmlspecialchars($genre_item); ?>"><?php echo htmlspecialchars($genre_item); ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
              <div class="filter-group">
                <label for="conditionSelectMobile">Stāvoklis</label>
                <select id="conditionSelectMobile" class="filter-select">
                  <option value="all">Visi stāvokļi</option>
                    <?php foreach ($all_conditions_from_db as $condition_item): ?>
                        <option value="<?php echo htmlspecialchars($condition_item); ?>"><?php echo htmlspecialchars($condition_item); ?></option>
                    <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="filter-modal-footer">
              <button class="btn btn-outline" id="resetFiltersButton">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Atiestatīt
              </button>
              <button class="btn btn-primary" id="applyFiltersButton">Pielietot</button>
            </div>
          </div>
        </div>
        
        <div class="container browse-container">
          <div id="booksGrid" class="books-grid"></div>
          <div id="noBooks" class="no-books-found" style="display: none;">
            <h3 class="no-books-title">Nav atrasta neviena grāmata</h3>
            <p class="no-books-message">Mēģiniet pielāgot meklēšanas vai filtrēšanas iestatījumus.</p>
            <button class="btn btn-outline" id="resetAllButton">Atiestatīt visus filtrus</button>
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
            <p>© <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
          </div>
        </div>
      </footer>
    </div>
    
    <!-- Toast Container for browse.php (if needed, or integrate with a global one) -->
    <div id="toast-container-browse" style="position: fixed; bottom: 70px; right: 20px; z-index: 1055;"></div>


    <script src="script.js"></script> 
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const books = <?php echo json_encode($books_data_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const userWishlistedBookIds = <?php echo json_encode($user_wishlist_ids_for_js); ?>;
        const currentLoggedInUserIdBrowse = <?php echo isLoggedIn() ? json_encode($_SESSION['user_id']) : 'null'; ?>;

        const booksGrid = document.getElementById('booksGrid');
        const noBooks = document.getElementById('noBooks');
        const searchInput = document.getElementById('searchInput');
        const searchInputMobile = document.getElementById('searchInputMobile');
        const genreSelect = document.getElementById('genreSelect');
        const conditionSelect = document.getElementById('conditionSelect');
        const genreSelectMobile = document.getElementById('genreSelectMobile');
        const conditionSelectMobile = document.getElementById('conditionSelectMobile');
        const filterButton = document.getElementById('filterButton');
        const filterModal = document.getElementById('filterModal');
        const closeFilterModal = document.getElementById('closeFilterModal');
        const applyFiltersButton = document.getElementById('applyFiltersButton');
        const resetFiltersButton = document.getElementById('resetFiltersButton');
        const resetAllButton = document.getElementById('resetAllButton');
        
        let currentSearchTerm = '';
        let currentGenre = 'all';
        let currentCondition = 'all';
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('genre')) {
          const genreParam = urlParams.get('genre');
          if (genreParam) { 
            currentGenre = genreParam;
            if(genreSelect) genreSelect.value = genreParam;
            if(genreSelectMobile) genreSelectMobile.value = genreParam;
          }
        }
        if (urlParams.has('search')) { 
            const searchTermParam = urlParams.get('search');
            if(searchTermParam) {
                currentSearchTerm = searchTermParam.toLowerCase();
                if(searchInput) searchInput.value = searchTermParam;
                if(searchInputMobile) searchInputMobile.value = searchTermParam;
            }
        }
        
        function displayBooks() {
          if (!booksGrid) return; 
          const filteredBooks = books.filter(book => {
            const titleMatch = book.title ? book.title.toLowerCase().includes(currentSearchTerm) : false;
            const authorMatch = book.author ? book.author.toLowerCase().includes(currentSearchTerm) : false;
            const matchesSearch = currentSearchTerm === '' ? true : (titleMatch || authorMatch);
            
            const matchesGenre = currentGenre === 'all' || (book.genre && book.genre.toLowerCase() === currentGenre.toLowerCase());
            const matchesCondition = currentCondition === 'all' || (book.condition && book.condition.toLowerCase() === currentCondition.toLowerCase());
            
            return matchesSearch && matchesGenre && matchesCondition;
          });
          
          booksGrid.innerHTML = '';
          if (filteredBooks.length > 0) {
            filteredBooks.forEach(book => { booksGrid.appendChild(createBookCard(book)); });
            booksGrid.style.display = 'grid';
            if(noBooks) noBooks.style.display = 'none';
          } else {
            booksGrid.style.display = 'none';
            if(noBooks) noBooks.style.display = 'block';
          }
        }
        
        // This is your original createBookCard function structure
        function createBookCard(book) {
            const bookElement = document.createElement('div');
            bookElement.className = 'book-card';
            bookElement.dataset.bookId = book.id; // Store book ID

            const coverDiv = document.createElement('div');
            coverDiv.className = 'book-cover'; // styles.css should handle position:relative for this
             let coverHtml = `<div class="fallback-cover" style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; background-color: var(--color-light-gray);"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg></div>`;
            if (book.coverImage) {
                 coverHtml = `<img src="${book.coverImage}?t=${new Date().getTime()}" alt="Cover of ${book.title || 'Grāmata'}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <div class="fallback-cover" style="display:none; align-items:center; justify-content:center; width:100%; height:100%; background-color: var(--color-light-gray);"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>`;
            }
            coverDiv.innerHTML = coverHtml;

            const wishlistBtn = document.createElement('button');
            wishlistBtn.className = 'wishlist-button'; // General class from styles.css
            wishlistBtn.setAttribute('aria-label', 'Pievienot vēlmēm');
            const heartIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>`;
            wishlistBtn.innerHTML = heartIconSvg;
            const heartIcon = wishlistBtn.querySelector('svg');

            if (currentLoggedInUserIdBrowse && userWishlistedBookIds && userWishlistedBookIds.includes(parseInt(book.id))) {
                heartIcon.classList.add('filled'); 
                heartIcon.setAttribute('fill', 'var(--color-burgundy)');
            } else {
                heartIcon.setAttribute('fill', 'none');
            }
            
            if (!currentLoggedInUserIdBrowse) {
                wishlistBtn.disabled = true;
                wishlistBtn.title = "Pieslēdzieties, lai pievienotu vēlmēm";
            }

            wishlistBtn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                if (!currentLoggedInUserIdBrowse) {
                    showToastBrowse('Lūdzu, pieslēdzieties, lai izmantotu vēlmju sarakstu.', 'error');
                    return;
                }
                
                const currentBookId = this.closest('.book-card').dataset.bookId;
                wishlistBtn.disabled = true;

                const formData = new FormData();
                formData.append('ajax_action', 'toggle_wishlist');
                formData.append('book_id', currentBookId);

                fetch('profile.php', { method: 'POST', body: formData }) // Assuming profile.php handles wishlist
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToastBrowse(data.message, 'success');
                        const localHeartIcon = this.querySelector('svg');
                        if (data.wishlisted) {
                            localHeartIcon.classList.add('filled');
                            localHeartIcon.setAttribute('fill', 'var(--color-burgundy)');
                            if(userWishlistedBookIds && !userWishlistedBookIds.includes(parseInt(currentBookId))) userWishlistedBookIds.push(parseInt(currentBookId));
                        } else {
                            localHeartIcon.classList.remove('filled');
                            localHeartIcon.setAttribute('fill', 'none');
                            if(userWishlistedBookIds){
                                const index = userWishlistedBookIds.indexOf(parseInt(currentBookId));
                                if (index > -1) userWishlistedBookIds.splice(index, 1);
                            }
                        }
                    } else { showToastBrowse(data.message || 'Kļūda ar vēlmju sarakstu.', 'error'); }
                })
                .catch(error => showToastBrowse('Tīkla kļūda ar vēlmju sarakstu.', 'error'))
                .finally(() => { wishlistBtn.disabled = false; });
            });
            coverDiv.appendChild(wishlistBtn);
            
            const infoDiv = document.createElement('div'); infoDiv.className = 'book-info';
            const titleH3 = document.createElement('h3'); titleH3.className = 'book-title'; titleH3.textContent = book.title || 'Nav nosaukuma';
            const authorP = document.createElement('p'); authorP.className = 'book-author'; authorP.textContent = book.author ? `by ${book.author}` : 'Nezināms autors';
            const tagsDiv = document.createElement('div'); tagsDiv.className = 'book-tags';
            if (book.genre) tagsDiv.innerHTML += `<span class="book-tag">${book.genre}</span>`;
            if (book.condition) tagsDiv.innerHTML += `<span class="book-tag">${book.condition}</span>`;
            const footerDiv = document.createElement('div'); footerDiv.className = 'book-footer';
            const ownerLink = document.createElement('a'); ownerLink.className = 'book-owner'; ownerLink.href = `profile.php?user_id=${book.userId}`; ownerLink.textContent = book.listedBy ? `Pievienoja ${book.listedBy}` : 'Nezināms';
            ownerLink.addEventListener('click', function(e){ e.stopPropagation(); });
            const tradeBtn = document.createElement('a'); tradeBtn.className = 'btn btn-primary'; tradeBtn.href = `book.php?id=${book.id}`; tradeBtn.textContent = 'Pieprasīt maiņu'; tradeBtn.style.fontSize = '0.75rem';
            tradeBtn.addEventListener('click', function(e){ e.stopPropagation(); });
            footerDiv.appendChild(ownerLink); footerDiv.appendChild(tradeBtn);
            infoDiv.appendChild(titleH3); infoDiv.appendChild(authorP); infoDiv.appendChild(tagsDiv); infoDiv.appendChild(footerDiv);
            bookElement.appendChild(coverDiv); bookElement.appendChild(infoDiv);
            bookElement.addEventListener('click', function() { window.location.href = `book.php?id=${book.id}`; });
            return bookElement;
        }


        if(searchInput) searchInput.addEventListener('input', function() { currentSearchTerm = this.value.toLowerCase(); displayBooks(); });
        if(searchInputMobile) searchInputMobile.addEventListener('input', function() { currentSearchTerm = this.value.toLowerCase(); displayBooks(); });
        if(genreSelect) genreSelect.addEventListener('change', function() { currentGenre = this.value; displayBooks(); });
        if(conditionSelect) conditionSelect.addEventListener('change', function() { currentCondition = this.value; displayBooks(); });
        
        if(filterButton && filterModal && closeFilterModal && applyFiltersButton && resetFiltersButton) {
            filterButton.addEventListener('click', () => { filterModal.classList.add('active'); document.body.style.overflow = 'hidden'; });
            closeFilterModal.addEventListener('click', () => { filterModal.classList.remove('active'); document.body.style.overflow = 'auto'; });
            applyFiltersButton.addEventListener('click', () => {
                if(genreSelectMobile) currentGenre = genreSelectMobile.value;
                if(conditionSelectMobile) currentCondition = conditionSelectMobile.value;
                filterModal.classList.remove('active'); document.body.style.overflow = 'auto';
                if(genreSelect) genreSelect.value = currentGenre;
                if(conditionSelect) conditionSelect.value = currentCondition;
                displayBooks();
            });
            resetFiltersButton.addEventListener('click', () => {
                if(genreSelectMobile) genreSelectMobile.value = 'all';
                if(conditionSelectMobile) conditionSelectMobile.value = 'all';
            });
        }
        if(resetAllButton) {
            resetAllButton.addEventListener('click', () => {
                currentSearchTerm = ''; currentGenre = 'all'; currentCondition = 'all';
                if(searchInput) searchInput.value = ''; if(searchInputMobile) searchInputMobile.value = '';
                if(genreSelect) genreSelect.value = 'all'; if(conditionSelect) conditionSelect.value = 'all';
                if(genreSelectMobile) genreSelectMobile.value = 'all'; if(conditionSelectMobile) conditionSelectMobile.value = 'all';
                displayBooks();
            });
        }
        if (booksGrid) displayBooks(); // Initial display

        function showToastBrowse(message, type = 'success') {
            const container = document.getElementById('toast-container-browse');
            if (!container) { console.warn("Toast container for browse not found"); return; }

            const toastDiv = document.createElement('div');
            toastDiv.className = `toast show ${type === 'error' ? 'auth-error active' : ''}`;
            toastDiv.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            toastDiv.style.color = type === 'success' ? '#155724' : '#721c24';
            toastDiv.style.borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            toastDiv.style.padding = '15px'; 
            toastDiv.style.borderRadius = '.25rem'; 
            toastDiv.style.marginBottom = '10px';
            toastDiv.style.position = 'relative'; // Relative to its container
            
            let iconSvg = type === 'success' ? 
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' :
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            
            toastDiv.innerHTML = `
                <div class="toast-content" style="display:flex; align-items:center;">
                    <div class="toast-icon" style="margin-right:10px; color: ${type === 'success' ? '#155724' : '#721c24'};">${iconSvg}</div>
                    <p class="toast-message" style="margin:0;">${message}</p>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()" style="background:none; border:none; font-size:1.2rem; line-height:1; color: ${type === 'success' ? '#155724' : '#721c24'}; position:absolute; top:50%; right:15px; transform:translateY(-50%);">×</button>`;
            
            container.appendChild(toastDiv); 
            setTimeout(() => { toastDiv.remove(); }, 3000);
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
<link rel="stylesheet" href="chat.css?v=<?php echo time(); ?>">
  <script src="chat.js?v=<?php echo time(); ?>"></script>
  </body>
</html>