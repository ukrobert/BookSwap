<?php
require_once 'session_check.php'; // Убедитесь, что этот файл подключен для работы шапки
require_once 'connect_db.php'; // Подключение к вашей базе данных

$books_data_for_js = []; // Массив для передачи в JavaScript

try {
    $sql = "SELECT 
                b.GramatasID, 
                b.Nosaukums, 
                b.Autors, 
                b.Zanrs, 
                b.Valoda,
                b.IzdosanasGads,
                b.Apraksts,
                b.Attels, 
                b.Status,
                u.LietotajsID AS UserID_DB, 
                u.Lietotajvards 
            FROM bookswap_books b
            JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
            WHERE b.Status = 'Pieejama' -- Выбираем только доступные книги
            ORDER BY b.PievienosanasDatums DESC";

    $result = $savienojums->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $coverImagePath = '';
            if (!empty($row['Attels'])) {
                // Проверяем, является ли путь абсолютным URL или локальным путем
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
                // ВАЖНО: Заглушка для 'condition'. Добавьте поле Stavoklis в БД.
                'condition' => 'Laba', 
                'listedBy' => $row['Lietotajvards'],
                'userId' => $row['UserID_DB']
            ];
        }
    } else {
        // error_log("Error fetching books: " . $savienojums->error);
    }
    $savienojums->close();
} catch (Exception $e) {
    // error_log("Exception fetching books: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pārlūkot grāmatas - BookSwap</title>
    <meta name="description" content="Browse books available for trade on BookSwap." />
    <meta name="author" content="BookSwap" />

    <meta property="og:title" content="Pārlūkot grāmatas - BookSwap" />
    <meta property="og:description" content="Discover books available for trade in our community." />
    <meta property="og:type" content="website" />
    

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@bookswap" />
    
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="browse.css">
  </head>

  <body data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
    <div id="root">
      <!-- Navigation -->
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
        <!-- Browse Header -->
        <div class="browse-header">
          <div class="container">
            <h1 class="browse-title">Pārlūkot pieejamās grāmatas</h1>
            <p class="browse-description">Atrodiet nākamo lielisko lasāmvielu mūsu kopienas koplietošanas bibliotēkā.</p>
            
            <!-- Search and Filter - Desktop -->
            <div class="search-filter-desktop">
              <div class="search-container-browse">
                <input type="text" id="searchInput" placeholder="Meklēt pēc nosaukuma, autora..." class="search-input-browse">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              </div>
              
              <div class="filter-selects">
                <div class="select-wrapper">
                  <select id="genreSelect" class="filter-select">
                    <option value="all">Visi žanri</option>
                    <option value="Fiction">Daiļliteratūra</option>
                    <option value="Fantasy">Fantāzija</option>
                    <option value="Science Fiction">Zinātniskā fantastika</option>
                    <option value="Mystery">Detektīvs</option>
                    <option value="Thriller">Trilleris</option>
                    <option value="Romance">Romāns</option>
                    <option value="Horror">Šausmas</option>
                    <option value="Biography">Biogrāfija</option>
                    <option value="History">Vēsture</option>
                    <option value="Self-Help">Pašpalīdzība</option>
                    <option value="Memoir">Atmiņu stāsts</option>
                    <option value="Poetry">Dzeja</option>
                    <!-- Динамически добавить жанры из БД, если нужно -->
                  </select>
                </div>
                
                <div class="select-wrapper">
                  <select id="conditionSelect" class="filter-select">
                    <option value="all">Visi stāvokļi</option>
                    <option value="Kā jauna">Kā jauna</option>
                    <option value="Ļoti laba">Ļoti laba</option>
                    <option value="Laba">Laba</option>
                    <option value="Pieņemama">Pieņemama</option>
                     <!-- Эти значения должны соответствовать тем, что вы будете использовать для 'condition' -->
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Search and Filter - Mobile -->
            <div class="search-filter-mobile">
              <div class="search-container-browse">
                <input type="text" id="searchInputMobile" placeholder="Meklēt..." class="search-input-browse">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              </div>
              
              <button id="filterButton" class="filter-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Filter Modal (Hidden by default) -->
        <div id="filterModal" class="filter-modal">
          <div class="filter-modal-content">
            <div class="filter-modal-header">
              <h3>Filtrēt grāmatas</h3>
              <button class="close-button" id="closeFilterModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
            
            <div class="filter-modal-body">
              <div class="filter-group">
                <label for="genreSelectMobile">Žanrs</label>
                <select id="genreSelectMobile" class="filter-select">
                  <option value="all">Visi žanri</option>
                    <option value="Fiction">Daiļliteratūra</option>
                    <option value="Fantasy">Fantāzija</option>
                    <option value="Science Fiction">Zinātniskā fantastika</option>
                    <option value="Mystery">Detektīvs</option>
                    <option value="Thriller">Trilleris</option>
                    <option value="Romance">Romāns</option>
                    <option value="Horror">Šausmas</option>
                    <option value="Biography">Biogrāfija</option>
                    <option value="History">Vēsture</option>
                    <option value="Self-Help">Pašpalīdzība</option>
                    <option value="Memoir">Atmiņu stāsts</option>
                    <option value="Poetry">Dzeja</option>
                </select>
              </div>
              
              <div class="filter-group">
                <label for="conditionSelectMobile">Stāvoklis</label>
                <select id="conditionSelectMobile" class="filter-select">
                  <option value="all">Visi stāvokļi</option>
                  <option value="Kā jauna">Kā jauna</option>
                  <option value="Ļoti laba">Ļoti laba</option>
                  <option value="Laba">Laba</option>
                  <option value="Pieņemama">Pieņemama</option>
                </select>
              </div>
            </div>
            
            <div class="filter-modal-footer">
              <button class="btn btn-outline" id="resetFiltersButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                Atiestatīt filtrus
              </button>
              <button class="btn btn-primary" id="applyFiltersButton">Pielietot filtrus</button>
            </div>
          </div>
        </div>
        
        <!-- Books Grid -->
        <div class="container browse-container">
          <div id="booksGrid" class="books-grid">
            <!-- Books will be populated by JavaScript -->
          </div>
          
          <!-- Nav atrasta neviena grāmata State -->
          <div id="noBooks" class="no-books-found" style="display: none;">
            <h3 class="no-books-title">Nav atrasta neviena grāmata</h3>
            <p class="no-books-message">Mēģiniet pielāgot meklēšanas vai filtrēšanas iestatījumus, lai atrastu meklēto.</p>
            <button class="btn btn-outline" id="resetAllButton">Atiestatīt visus filtrus</button>
          </div>
        </div>
      </main>

      <!-- Footer -->
      <footer class="footer">
        <div class="container">
          <div class="footer-grid">
            <!-- Brand Section -->
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
    
    
    <script src="script.js"></script> 
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get book data from PHP
        const books = <?php echo json_encode($books_data_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

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
          currentGenre = genreParam;
          if(genreSelect) genreSelect.value = genreParam;
          if(genreSelectMobile) genreSelectMobile.value = genreParam;
        }
        
        function displayBooks() {
          if (!booksGrid) return; 

          const filteredBooks = books.filter(book => {
            const titleMatch = book.title ? book.title.toLowerCase().includes(currentSearchTerm) : false;
            const authorMatch = book.author ? book.author.toLowerCase().includes(currentSearchTerm) : false;
            const matchesSearch = titleMatch || authorMatch;
            
            const matchesGenre = currentGenre === 'all' || (book.genre && book.genre === currentGenre);
            const matchesCondition = currentCondition === 'all' || (book.condition && book.condition === currentCondition);
            
            return matchesSearch && matchesGenre && matchesCondition;
          });
          
          booksGrid.innerHTML = '';
          
          if (filteredBooks.length > 0) {
            filteredBooks.forEach(book => {
              const bookCard = createBookCard(book);
              booksGrid.appendChild(bookCard);
            });
            booksGrid.style.display = 'grid';
            if(noBooks) noBooks.style.display = 'none';
          } else {
            booksGrid.style.display = 'none';
            if(noBooks) noBooks.style.display = 'block';
          }
        }
        
        function createBookCard(book) {
            const bookElement = document.createElement('div');
            bookElement.className = 'book-card';
            
            const coverDiv = document.createElement('div');
            coverDiv.className = 'book-cover'; // Этот класс должен иметь стили для высоты и overflow:hidden
            
            if (book.coverImage) {
                const img = document.createElement('img');
                img.src = book.coverImage + '?t=' + new Date().getTime();
                img.alt = `Cover of ${book.title || 'Book'}`;
                img.onerror = function() {
                    this.style.display = 'none'; // Скрыть сломанное изображение
                    const fallback = this.parentElement.querySelector('.fallback-cover-js');
                    if(fallback) fallback.style.display = 'flex'; // Показать заглушку
                };
                coverDiv.appendChild(img);
                // Создаем заглушку, но скрываем ее, если есть изображение
                const fallbackCover = document.createElement('div');
                fallbackCover.className = 'fallback-cover-js'; // Используем другой класс, чтобы не конфликтовать
                fallbackCover.style.display = 'none'; // Изначально скрыта
                fallbackCover.style.alignItems = 'center';
                fallbackCover.style.justifyContent = 'center';
                fallbackCover.style.width = '100%';
                fallbackCover.style.height = '100%';
                fallbackCover.style.backgroundColor = 'var(--color-light-gray)';
                fallbackCover.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>`;
                coverDiv.appendChild(fallbackCover);

            } else {
                 const fallbackCover = document.createElement('div');
                fallbackCover.className = 'fallback-cover-js';
                fallbackCover.style.display = 'flex';
                fallbackCover.style.alignItems = 'center';
                fallbackCover.style.justifyContent = 'center';
                fallbackCover.style.width = '100%';
                fallbackCover.style.height = '100%';
                fallbackCover.style.backgroundColor = 'var(--color-light-gray)';
                fallbackCover.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>`;
                coverDiv.appendChild(fallbackCover);
            }
          
            const wishlistBtn = document.createElement('button');
            wishlistBtn.className = 'wishlist-button';
            wishlistBtn.setAttribute('aria-label', 'Add to wishlist');
            wishlistBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            `;
            wishlistBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const heartIcon = this.querySelector('svg');
                const isWishlisted = heartIcon.getAttribute('fill') === 'var(--color-burgundy)';
                if (!isWishlisted) {
                    heartIcon.setAttribute('fill', 'var(--color-burgundy)');
                // AJAX to add to wishlist
                } else {
                    heartIcon.setAttribute('fill', 'none');
                // AJAX to remove from wishlist
                }
            });
            coverDiv.appendChild(wishlistBtn);
          
            const infoDiv = document.createElement('div');
            infoDiv.className = 'book-info';
            
            const titleH3 = document.createElement('h3');
            titleH3.className = 'book-title';
            titleH3.textContent = book.title || 'Nav nosaukuma';
            
            const authorP = document.createElement('p');
            authorP.className = 'book-author';
            authorP.textContent = book.author ? `by ${book.author}` : 'Nezināms autors';
            
            const tagsDiv = document.createElement('div');
            tagsDiv.className = 'book-tags';
            
            if (book.genre) {
                const genreSpan = document.createElement('span');
                genreSpan.className = 'book-tag';
                genreSpan.textContent = book.genre;
                tagsDiv.appendChild(genreSpan);
            }
            
            if (book.condition) {
                const conditionSpan = document.createElement('span');
                conditionSpan.className = 'book-tag';
                conditionSpan.textContent = book.condition;
                tagsDiv.appendChild(conditionSpan);
            }
            
            const footerDiv = document.createElement('div');
            footerDiv.className = 'book-footer';
            
            const ownerLink = document.createElement('a');
            ownerLink.className = 'book-owner';
            ownerLink.href = `profile.php?user_id=${book.userId}`; // Убедитесь, что profile.php может обрабатывать user_id
            ownerLink.textContent = book.listedBy ? `Listed by ${book.listedBy}` : 'Nezināms lietotājs';
            ownerLink.addEventListener('click', function(e){ e.stopPropagation(); }); 
            
            const tradeBtn = document.createElement('a');
            tradeBtn.className = 'btn btn-primary';
            tradeBtn.href = `book.php?id=${book.id}`;
            tradeBtn.textContent = 'Pieprasīt maiņu';
            tradeBtn.style.fontSize = '0.75rem';
            tradeBtn.addEventListener('click', function(e){ e.stopPropagation(); });

            footerDiv.appendChild(ownerLink);
            footerDiv.appendChild(tradeBtn);
            
            infoDiv.appendChild(titleH3);
            infoDiv.appendChild(authorP);
            infoDiv.appendChild(tagsDiv);
            infoDiv.appendChild(footerDiv);
            
            bookElement.appendChild(coverDiv);
            bookElement.appendChild(infoDiv);
            
            bookElement.addEventListener('click', function() {
                window.location.href = `book.php?id=${book.id}`;
            });
            
            return bookElement;
        }

        if(searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearchTerm = this.value.toLowerCase();
                displayBooks();
            });
        }

        if(searchInputMobile) {
            searchInputMobile.addEventListener('input', function() {
                currentSearchTerm = this.value.toLowerCase();
                displayBooks();
            });
        }
        
        if(genreSelect) {
            genreSelect.addEventListener('change', function() {
                currentGenre = this.value;
                displayBooks();
            });
        }

        if(conditionSelect) {
            conditionSelect.addEventListener('change', function() {
                currentCondition = this.value;
                displayBooks();
            });
        }
        
        if(filterButton && filterModal && closeFilterModal && applyFiltersButton && resetFiltersButton) {
            filterButton.addEventListener('click', function() {
                filterModal.classList.add('active');
                document.body.style.overflow = 'hidden'; 
            });
            
            closeFilterModal.addEventListener('click', function() {
                filterModal.classList.remove('active');
                document.body.style.overflow = 'auto'; 
            });
            
            applyFiltersButton.addEventListener('click', function() {
                if(genreSelectMobile) currentGenre = genreSelectMobile.value;
                if(conditionSelectMobile) currentCondition = conditionSelectMobile.value;
                filterModal.classList.remove('active');
                document.body.style.overflow = 'auto';
                
                if(genreSelect) genreSelect.value = currentGenre;
                if(conditionSelect) conditionSelect.value = currentCondition;
                
                displayBooks();
            });
            
            resetFiltersButton.addEventListener('click', function() {
                if(genreSelectMobile) genreSelectMobile.value = 'all';
                if(conditionSelectMobile) conditionSelectMobile.value = 'all';
            });
        }

        if(resetAllButton) {
            resetAllButton.addEventListener('click', function() {
                currentSearchTerm = '';
                currentGenre = 'all';
                currentCondition = 'all';
                
                if(searchInput) searchInput.value = '';
                if(searchInputMobile) searchInputMobile.value = '';
                if(genreSelect) genreSelect.value = 'all';
                if(conditionSelect) conditionSelect.value = 'all';
                if(genreSelectMobile) genreSelectMobile.value = 'all';
                if(conditionSelectMobile) conditionSelectMobile.value = 'all';
                
                displayBooks();
            });
        }
        
        if (booksGrid) { // Убедимся, что booksGrid существует перед вызовом displayBooks
           displayBooks();
        }
    });
    </script>
    <script src="chat.js?v=<?php echo time(); // Cache busting ?>"></script>
  </body>
</html>