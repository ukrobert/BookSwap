<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BookSwap - Grāmatu apmaiņa ar citiem lasītājiem</title>
    <meta name="description" content="BookSwap is a community-driven platform for book lovers to trade and exchange books with others." />
    <meta name="author" content="BookSwap" />

    <meta property="og:title" content="BookSwap - Exchange Books with Fellow Readers" />
    <meta property="og:description" content="Connect with fellow readers, list your books, and trade for new great reads. Join our book exchange community today." />
    <meta property="og:type" content="website" />
   

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@bookswap" />
    
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
        <!-- Hero Section -->
        <section class="hero">
          <div class="hero-background"></div>
          <div class="container">
            <div class="hero-content">
              <h1 class="hero-title">Atrodiet savu nākamo lielisko lasīšanu, izmantojot apmaiņu</h1>
              <p class="hero-description">Pievienojieties mūsu grāmatu mīļotāju kopienai, kas dalās ar savām bibliotēkām un atklāj jaunas mīļākās.</p>
              
              <div class="hero-buttons">
                <a href="signup.php" class="btn btn-primary btn-lg">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                  Sākt apmainīšanu
                </a>
                <a href="how-it-works.php" class="btn btn-outline btn-lg">
                  Kā tas darbojas
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
              </div>
              
              <div class="search-container">
                <div class="search-box">
                  <input type="text" placeholder="Meklēt pēc nosaukuma, autora vai žanra..." class="search-input">
                  <button class="search-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  </button>
                </div>
                <div class="popular-searches">
                  <span>Popular:</span>
                  <a href="browse.php?genre=Fantasy" class="popular-search-link">Fantāzija</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Mystery" class="popular-search-link">Noslēpums</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Science%20Fiction" class="popular-search-link">Zinātniskā fantastika</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Romance" class="popular-search-link">Romance</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Historical%20Fiction" class="popular-search-link">Vēsturiskā daiļliteratūra</a>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Featured Books -->
        <section class="featured-books">
          <div class="container">
            <div class="section-header">
              <h2 class="section-title">Jaunākās pievienotās grāmatas</h2>
              <a href="browse.php" class="view-all-link">
                View All
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
            
            <div class="books-grid" id="featuredBooks">
              <!-- Books will be populated by JavaScript -->
            </div>
            
            <div class="mobile-view-all">
              <a href="browse.php" class="btn btn-outline">
                View All Books
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
          </div>
        </section>

        <!-- How It Works -->
        <section class="how-it-works">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Kā darbojas BookSwap</h2>
              <p class="section-description">Apmainīties ar grāmatām ar citiem lasītājiem vēl nekad nav bijis vieglāk. Veiciet šos vienkāršos soļus, lai sāktu apmainīties ar sev mīļām grāmatām.</p>
            </div>
            
            <div class="steps-grid">
              <!-- Step 1 -->
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><path d="M8 21V5"></path><path d="M12 10v4"></path><path d="M12 14h4"></path></svg>
                </div>
                <h3 class="step-title">Sastādiet savu grāmatu sarakstu</h3>
                <p class="step-description">Pievienojiet grāmatas no savas personīgās bibliotēkas, kuras vēlaties apmainīt ar citām. Norādiet informāciju par stāvokli un žanru.</p>
                <ul class="step-features">
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Ātra un vienkārša grāmatas ievadīšana</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Vāka attēlu augšupielāde</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Norādiet grāmatas stāvokli</span>
                  </li>
                </ul>
              </div>
              
              <!-- Step 2 -->
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path><path d="m9 10 2 2 4-4"></path></svg>
                </div>
                <h3 class="step-title">Atrast & pieprasīt</h3>
                <p class="step-description">Pārlūkojiet tirdzniecībai pieejamās grāmatas. Kad atradīsiet sev tīkamu grāmatu, nosūtiet īpašniekam tirdzniecības pieprasījumu.</p>
                <ul class="step-features">
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Paplašinātās meklēšanas filtri</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Grāmatu ieteikumi</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Viegli tirdzniecības pieprasījumi</span>
                  </li>
                </ul>
              </div>
              
              <!-- Step 3 -->
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                </div>
                <h3 class="step-title">Saskaņot & apmainīties</h3>
                <p class="step-description">Aprunājieties ar savu apmaiņas partneri, vienojieties par apmaiņas detaļām un pēc tam tiekieties vai sūtiet savas grāmatas, lai pabeigtu apmaiņu.</p>
                <ul class="step-features">
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Droša ziņojumapmaiņas sistēma</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Tirdzniecības statusa izsekošana</span>
                  </li>
                  <li class="step-feature">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span>Apmaiņas apstiprinājums</span>
                  </li>
                </ul>
              </div>
            </div>
            
            <div class="text-center">
              <a href="how-it-works.php" class="btn btn-primary">
                Uzziniet vairāk par BookSwap
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
          </div>
        </section>

        <!-- Genre Browse -->
        <section class="genre-browse">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Pārlūkojiet pēc žanra</h2>
              <p class="section-description">Izpētiet mūsu plašo grāmatu kolekciju, kas sakārtota pēc žanra, lai atrastu nākamo lielisko lasāmvielu.</p>
            </div>
            
            <div class="genres-grid" id="genresGrid">
              <!-- Genres will be populated by JavaScript -->
            </div>
          </div>
        </section>

        <!-- Testimonials -->
        <section class="testimonials">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Ko saka mūsu kopiena</h2>
              <p class="section-description">Pievienojieties tūkstošiem laimīgu lasītāju, kas jau apmainās ar grāmatām, izmantojot mūsu platformu.</p>
            </div>
            
            <div class="testimonials-grid" id="testimonialsGrid">
              <!-- Testimonials will be populated by JavaScript -->
            </div>
          </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
          <div class="container">
            <div class="cta-content">
              <h2 class="cta-title">Vai esat gatavs sākt tirgoties ar grāmatām?</h2>
              <p class="cta-description">Pievienojieties mūsu kopienai jau šodien un sazinieties ar tūkstošiem grāmatu mīļotāju, kas gatavi apmainīties ar stāstiem un piedzīvojumiem.</p>
              <div class="cta-buttons">
                <a href="signup.php" class="btn btn-white">
                  Sign Up Now
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <a href="browse.php" class="btn btn-outline-white">Pārlūkot grāmatas</a>
              </div>
              <p class="cta-signin">Jums jau ir konts? <a href="login.php">Pieslēgties</a></p>
            </div>
          </div>
        </section>
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
    </div>

    
    <script src="script.js"></script>
  </body>
</html>
