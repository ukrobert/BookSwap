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

  <body>
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
              <a href="login.php" class="btn btn-outline">Pieslēgties</a>
              <a href="signup.php" class="btn btn-primary">Reģistrēties</a>
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
              <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
              <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
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
                  </select>
                </div>
                
                <div class="select-wrapper">
                  <select id="conditionSelect" class="filter-select">
                    <option value="all">Visi stāvokļi</option>
                    <option value="Like New">Kā jauna</option>
                    <option value="Very Good">Ļoti laba</option>
                    <option value="Good">Laba</option>
                    <option value="Acceptable">Pieņemama</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Search and Filter - Mobile -->
            <div class="search-filter-mobile">
              <div class="search-container-browse">
                <input type="text" id="searchInputMobile" placeholder="Search..." class="search-input-browse">
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
                  <option value="Like New">Kā jauna</option>
                  <option value="Very Good">Ļoti laba</option>
                  <option value="Good">Laba</option>
                  <option value="Acceptable">Pieņemama</option>
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
            <p class="no-books-message">Try adjusting your search or filters to find what you're looking for.</p>
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
              <p>Connect with fellow readers and exchange books you love.</p>
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
            
            <!-- Ātrās saites -->
            <div class="footer-links">
              <h3 class="footer-title">Ātrās saites</h3>
              <ul>
                <li><a href="browse.php">Pārlūkot grāmatas</a></li>
                <li><a href="how-it-works.php">Kā tas darbojas</a></li>
                <li><a href="signup.php">Pievienoties BookSwap</a></li>
                <li><a href="login.php">Pieslēgties</a></li>
              </ul>
            </div>
            
            <!-- Palīdzība un atbalsts -->
            <div class="footer-links">
              <h3 class="footer-title">Palīdzība un atbalsts</h3>
              <ul>
                <li><a href="faq.php">BUJ</a></li>
                <li><a href="contact-us.php">Sazinieties ar mums</a></li>
                <li><a href="safety-tips.php">Drošības padomi</a></li>
                <li><a href="report-issue.php">Ziņot par problēmu</a></li>
              </ul>
            </div>
            
            <!-- Juridiskā informācija -->
            <div class="footer-links">
              <h3 class="footer-title">Juridiskā informācija</h3>
              <ul>
                <li><a href="terms.php">Lietošanas noteikumi</a></li>
                <li><a href="privacy-policy.php">Privātuma politika</a></li>
                <li><a href="cookies.php">Sīkdatņu politika</a></li>
                <li><a href="gdpr.php">VDAR</a></li>
              </ul>
            </div>
          </div>
          
          <div class="footer-bottom">
            <p>&copy; <span id="currentYear"></span> BookSwap. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
    
    
    <script src="script.js"></script>
    <script src="browse.js"></script>
  </body>
</html>
