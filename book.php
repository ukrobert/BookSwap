<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
        <!-- Back Button -->
        <div class="container">
          <a href="browse.php" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Atpakaļ uz pārlūkošanu
          </a>
        </div>
        
        <!-- Grāmatas detaļas -->
        <div class="container">
          <div class="book-detail-grid">
            <!-- Book Cover -->
            <div class="book-cover-container">
              <div class="book-cover-wrapper" id="bookCoverWrapper">
                <!-- Will be populated by JavaScript -->
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
            
            <!-- Book Info -->
            <div class="book-info-container">
              <h1 class="book-title" id="bookTitle"><!-- Will be populated by JavaScript --></h1>
              <p class="book-author" id="bookAuthor"><!-- Will be populated by JavaScript --></p>
              
              <div class="book-tags" id="bookTags">
                <!-- Will be populated by JavaScript -->
              </div>
              
              <h2 class="section-header">Par šo grāmatu</h2>
              <div class="book-description">
                <p id="bookDescription"><!-- Will be populated by JavaScript --></p>
              </div>
              
              <h2 class="section-header">Pievienoja</h2>
              <div class="owner-card" id="ownerCard">
                <!-- Will be populated by JavaScript -->
              </div>
              
              <h2 class="section-header">Iespējams, tev patiks arī</h2>
              <div class="similar-books" id="similarBooks">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
          </div>
        </div>

        <!-- Trade Request Modal -->
        <div id="tradeModal" class="modal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Pieprasīt grāmatu apmaiņu</h3>
              <button class="close-button" id="closeTradeModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </button>
            </div>
            
            <div class="modal-body">
              <p>Izvēlies vienu no savām grāmatām, ko piedāvāt apmaiņā pret "<span id="tradeBookTitle"></span>".</p>
              
              <div class="form-group">
                <label for="tradeSelect">Select your book to trade:</label>
                <div class="select-wrapper">
                  <select id="tradeSelect" class="form-select">
                    <option value="">Izvēlies grāmatu</option>
                    <!-- Will be populated by JavaScript -->
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label for="tradeMessage">Add a message (optional):</label>
                <textarea id="tradeMessage" class="form-textarea" rows="3" placeholder="I'd love to trade this book with you!"></textarea>
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

      <!-- Footer -->
      <footer class="footer">
        <div class="container">
          <div class="footer-grid">
            <!-- Brand Section -->
            <div class="footer-brand">
              <a href="index.htmphpl" class="brand">
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

    <!-- Toast Notification -->
    <div id="toast" class="toast">
      <div class="toast-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon success"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <div class="toast-message" id="toastMessage"></div>
      </div>
      <button class="toast-close" id="toastClose">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>
    </div>
    
    
    <script src="script.js"></script>
    <script src="book.js"></script>
  </body>
</html>
