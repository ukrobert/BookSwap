<?php
require_once 'session_check.php';
require_once 'connect_db.php';

$featured_books_for_js = [];

try {
    $sql_featured = "SELECT 
                        b.GramatasID, b.Nosaukums, b.Autors, b.Zanrs, b.Attels, b.Stavoklis,
                        u.LietotajsID AS UserID_DB, u.Lietotajvards 
                    FROM bookswap_books b
                    JOIN bookswap_users u ON b.LietotajsID = u.LietotajsID
                    WHERE b.Status = 'Pieejama'
                    ORDER BY b.PievienosanasDatums DESC
                    LIMIT 4";

    $result_featured = $savienojums->query($sql_featured);

    if ($result_featured) {
        while ($row_f = $result_featured->fetch_assoc()) {
            $cover_path_f = '';
            if (!empty($row_f['Attels'])) {
                if (filter_var($row_f['Attels'], FILTER_VALIDATE_URL)) {
                    $cover_path_f = htmlspecialchars($row_f['Attels']);
                } elseif (file_exists($row_f['Attels'])) {
                    $cover_path_f = htmlspecialchars($row_f['Attels']);
                }
            }
            $featured_books_for_js[] = [
                'id' => $row_f['GramatasID'],
                'title' => $row_f['Nosaukums'],
                'author' => $row_f['Autors'],
                'coverImage' => $cover_path_f,
                'genre' => $row_f['Zanrs'],
                'condition' => $row_f['Stavoklis'], // Используем Stavoklis из БД
                'listedBy' => $row_f['Lietotajvards'],
                'userId' => $row_f['UserID_DB']
            ];
        }
    } else {
        // error_log("Error fetching featured books for index.php: " . $savienojums->error);
    }
    if($savienojums) $savienojums->close();
} catch (Exception $e) {
    error_log("Exception fetching featured books for index.php: " . $e->getMessage());
}
?>
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
                  <input type="text" placeholder="Meklēt pēc nosaukuma, autora vai žanra..." class="search-input" id="heroSearchInput">
                  <button class="search-button" id="heroSearchButton">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  </button>
                </div>
                <div class="popular-searches">
                  <span>Popular:</span>
                  <a href="browse.php?genre=Fantasy" class="popular-search-link">Fantāzija</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Mystery" class="popular-search-link">Detektīvs</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Science%20Fiction" class="popular-search-link">Zinātniskā fantastika</a>
                  <span class="separator">•</span>
                  <a href="browse.php?genre=Romance" class="popular-search-link">Romāns</a>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="featured-books">
          <div class="container">
            <div class="section-header">
              <h2 class="section-title">Jaunākās pievienotās grāmatas</h2>
              <a href="browse.php" class="view-all-link">
                Skatīt visas
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
            <div class="books-grid" id="featuredBooksGrid">
              <!-- Books will be populated by JavaScript -->
            </div>
            <div class="mobile-view-all">
              <a href="browse.php" class="btn btn-outline">
                Skatīt visas grāmatas
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
          </div>
        </section>

        <section class="how-it-works">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Kā darbojas BookSwap</h2>
              <p class="section-description">Apmainīties ar grāmatām ar citiem lasītājiem vēl nekad nav bijis vieglāk. Veiciet šos vienkāršos soļus, lai sāktu apmainīties ar sev mīļām grāmatām.</p>
            </div>
            <div class="steps-grid">
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path><path d="M8 21V5"></path><path d="M12 10v4"></path><path d="M12 14h4"></path></svg>
                </div>
                <h3 class="step-title">Sastādiet savu grāmatu sarakstu</h3>
                <p class="step-description">Pievienojiet grāmatas no savas personīgās bibliotēkas, kuras vēlaties apmainīt ar citām. Norādiet informāciju par stāvokli un žanru.</p>
              </div>
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path><path d="m9 10 2 2 4-4"></path></svg>
                </div>
                <h3 class="step-title">Atrast & pieprasīt</h3>
                <p class="step-description">Pārlūkojiet tirdzniecībai pieejamās grāmatas. Kad atradīsiet sev tīkamu grāmatu, nosūtiet īpašniekam tirdzniecības pieprasījumu.</p>
              </div>
              <div class="step-card">
                <div class="step-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                </div>
                <h3 class="step-title">Saskaņot & apmainīties</h3>
                <p class="step-description">Aprunājieties ar savu apmaiņas partneri, vienojieties par apmaiņas detaļām un pēc tam tiekieties vai sūtiet savas grāmatas, lai pabeigtu apmaiņu.</p>
              </div>
            </div>
            <div class="text-center" style="margin-top: var(--spacing-8);">
              <a href="how-it-works.php" class="btn btn-primary">
                Uzziniet vairāk par BookSwap
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
              </a>
            </div>
          </div>
        </section>

        <section class="genre-browse">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Pārlūkojiet pēc žanra</h2>
              <p class="section-description">Izpētiet mūsu plašo grāmatu kolekciju, kas sakārtota pēc žanra, lai atrastu nākamo lielisko lasāmvielu.</p>
            </div>
            <div class="genres-grid" id="genresGrid">
              <!-- Genres will be populated by JavaScript from script.js (sample data) -->
            </div>
          </div>
        </section>

        <section class="testimonials">
          <div class="container">
            <div class="section-header text-center">
              <h2 class="section-title">Ko saka mūsu kopiena</h2>
              <p class="section-description">Pievienojieties tūkstošiem laimīgu lasītāju, kas jau apmainās ar grāmatām, izmantojot mūsu platformu.</p>
            </div>
            <div class="testimonials-grid" id="testimonialsGrid">
              <!-- Testimonials will be populated by JavaScript from script.js (sample data) -->
            </div>
          </div>
        </section>

        <section class="cta-section">
          <div class="container">
            <div class="cta-content">
              <h2 class="cta-title">Vai esat gatavs sākt tirgoties ar grāmatām?</h2>
              <p class="cta-description">Pievienojieties mūsu kopienai jau šodien un sazinieties ar tūkstošiem grāmatu mīļotāju, kas gatavi apmainīties ar stāstiem un piedzīvojumiem.</p>
              <div class="cta-buttons">
                <a href="signup.php" class="btn btn-white">
                  Reģistrēties tagad
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
                <a href="browse.php" class="btn btn-outline-white">Pārlūkot grāmatas</a>
              </div>
              <p class="cta-signin">Jums jau ir konts? <a href="login.php">Pieslēgties</a></p>
            </div>
          </div>
        </section>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (mobileMenuButton && mobileMenu) {
          mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
          });
        }

        // --- JavaScript for Featured Books (from PHP) ---
        const featuredBooksData = <?php echo json_encode($featured_books_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const featuredBooksContainer = document.getElementById('featuredBooksGrid'); // Changed ID to avoid conflict with browse.php

        function createBookCard(book) {
            const bookElement = document.createElement('div');
            bookElement.className = 'book-card';
            
            const coverDiv = document.createElement('div');
            coverDiv.className = 'book-cover';
             let coverHtml = `<div class="fallback-cover" style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; background-color: var(--color-light-gray);"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg></div>`;

            if (book.coverImage) {
                coverHtml = `<img src="${book.coverImage}?t=${new Date().getTime()}" alt="Cover of ${book.title || 'Book'}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"/>
                             <div class="fallback-cover" style="display:none; align-items:center; justify-content:center; width:100%; height:100%; background-color: var(--color-light-gray);"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-gray)" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg></div>`;
            }
            coverDiv.innerHTML = coverHtml;
          
            const wishlistBtn = document.createElement('button');
            wishlistBtn.className = 'wishlist-button';
            wishlistBtn.setAttribute('aria-label', 'Add to wishlist');
            wishlistBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>`;
            wishlistBtn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                const heartIcon = this.querySelector('svg');
                const isWishlisted = heartIcon.getAttribute('fill') === 'var(--color-burgundy)';
                if (!isWishlisted) heartIcon.setAttribute('fill', 'var(--color-burgundy)');
                else heartIcon.setAttribute('fill', 'none');
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
            if (book.genre) tagsDiv.innerHTML += `<span class="book-tag">${book.genre}</span>`;
            if (book.condition) tagsDiv.innerHTML += `<span class="book-tag">${book.condition}</span>`; // Используем 'condition'
            
            const footerDiv = document.createElement('div');
            footerDiv.className = 'book-footer';
            
            const ownerLink = document.createElement('a');
            ownerLink.className = 'book-owner';
            ownerLink.href = `profile.php?user_id=${book.userId}`;
            ownerLink.textContent = book.listedBy ? `Listed by ${book.listedBy}` : 'Nezināms';
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
            bookElement.addEventListener('click', function() { window.location.href = `book.php?id=${book.id}`; });
            return bookElement;
        }

        if (featuredBooksContainer) {
            featuredBooksContainer.innerHTML = '';
            if (featuredBooksData && featuredBooksData.length > 0) {
                featuredBooksData.forEach(book => {
                    featuredBooksContainer.appendChild(createBookCard(book));
                });
            } else {
                // featuredBooksContainer.innerHTML = '<p style="text-align:center; color:var(--color-gray);">Pašlaik nav jaunu grāmatu.</p>';
            }
        }

        // --- JavaScript for Genres and Testimonials (Sample Data from original script.js) ---
        const genres = [
            { name: "Daiļliteratūra", icon: "📚", count: Math.floor(Math.random()*500+1000), color: "blue" },
            { name: "Detektīvs & Trilleris", icon: "🔍", count: Math.floor(Math.random()*500+500), color: "purple" },
            { name: "Zinātniskā Fantastika", icon: "🚀", count: Math.floor(Math.random()*400+400), color: "green" },
            { name: "Fantāzija", icon: "🧙", count: Math.floor(Math.random()*400+300), color: "amber" },
            { name: "Romāns", icon: "💕", count: Math.floor(Math.random()*300+300), color: "pink" },
            { name: "Populārzinātniskā", icon: "📝", count: Math.floor(Math.random()*500+600), color: "gray" },
            { name: "Biogrāfija", icon: "👤", count: Math.floor(Math.random()*200+200), color: "teal" },
            { name: "Vēsture", icon: "🏛️", count: Math.floor(Math.random()*200+100), color: "red" }
        ];
        const testimonials = [
          { quote: "Esmu atklājusi tik daudz pārsteidzošu grāmatu, kuras citādi nekad nebūtu paņēmusi rokās. Kopiena ir draudzīga, un darījumi vienmēr ir godīgi!", name: "Dženifera K.", title: "Grāmatu mīļotājs no Talsiem", rating: 5, image: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80" },
          { quote: "Tā kā es daudz lasu, bet nevēlos visas grāmatas glabāt mūžīgi, BookSwap ir lielisks risinājums. Esmu ieguvusi draugus un atradusi arī retas grāmatas!", name: "Mihails R.", title: "Aizrautīgs lasītājs", rating: 5, image: "https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80" },
          { quote: "Platforma ir ļoti viegli lietojama, un man patīk, ka varu meklēt konkrētus nosaukumus vai vienkārši pārlūkot pieejamos. Grāmatu apmaiņa ir daudz labāka, nekā katru reizi pirkt jaunas", name: "Sofija L.", title: "Angļu valodas skolotājs", rating: 4, image: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80" }
        ];

        const genresGrid = document.getElementById('genresGrid');
        if (genresGrid) {
          genresGrid.innerHTML = '';
          genres.forEach(genre => {
            const genreCard = createGenreCard(genre);
            genresGrid.appendChild(genreCard);
          });
        }
        
        const testimonialsGrid = document.getElementById('testimonialsGrid');
        if (testimonialsGrid) {
          testimonialsGrid.innerHTML = '';
          testimonials.forEach(testimonial => {
            const testimonialCard = createTestimonialCard(testimonial);
            testimonialsGrid.appendChild(testimonialCard);
          });
        }

        function createGenreCard(genre) {
          const genreElement = document.createElement('a');
          genreElement.className = `genre-card ${genre.color}`;
          genreElement.href = `browse.php?genre=${encodeURIComponent(genre.name)}`;
          genreElement.innerHTML = `
            <div class="genre-content">
              <div class="genre-info">
                <span class="genre-icon">${genre.icon}</span>
                <h3 class="genre-name">${genre.name}</h3>
              </div>
              <span class="genre-count">${genre.count}</span>
            </div>`;
          return genreElement;
        }

        function createTestimonialCard(testimonial) {
          const testimonialElement = document.createElement('div');
          testimonialElement.className = 'testimonial-card';
          let ratingHTML = '<div class="rating">';
          for (let i = 1; i <= 5; i++) {
            ratingHTML += `<span class="star ${i <= testimonial.rating ? 'filled' : ''}"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></span>`;
          }
          ratingHTML += '</div>';
          testimonialElement.innerHTML = `
            ${ratingHTML}
            <p class="testimonial-quote">"${testimonial.quote}"</p>
            <div class="testimonial-author">
              ${testimonial.image ? `<img src="${testimonial.image}" alt="${testimonial.name}" class="author-image">` : ''}
              <div>
                <h4 class="author-name">${testimonial.name}</h4>
                <p class="author-title">${testimonial.title}</p>
              </div>
            </div>`;
          return testimonialElement;
        }

        // Hero search functionality
        const heroSearchInput = document.getElementById('heroSearchInput');
        const heroSearchButton = document.getElementById('heroSearchButton');
        if(heroSearchButton && heroSearchInput){
            heroSearchButton.addEventListener('click', function(){
                const searchTerm = heroSearchInput.value.trim();
                if(searchTerm){
                    window.location.href = `browse.php?search=${encodeURIComponent(searchTerm)}`;
                } else {
                    window.location.href = 'browse.php';
                }
            });
            heroSearchInput.addEventListener('keypress', function(e){
                if(e.key === 'Enter'){
                    heroSearchButton.click();
                }
            });
        }

    });
    </script>
  </body>
</html>