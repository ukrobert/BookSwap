<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Drošības padomi - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <script src="safety-tips.js" defer></script>
</head>
<body class="paper-texture">
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
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) : 'U'; // Используем mb_substr для корректной работы с UTF-8
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath) && file_exists($profilePicPath)): ?>
                                        <img src="<?php echo htmlspecialchars($profilePicPath); ?>" alt="Profils">
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
    <section class="page-header">
      <div class="container">
        <h1>Safety Tips</h1>
        <p>Stay safe when using BookSwap with these important guidelines</p>
      </div>
    </section>

    <section class="safety-content">
      <div class="container">
        <div class="safety-card">
          <div class="safety-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
          </div>
          <div class="safety-info">
            <h2>Grāmatu apmaiņas tikšanās</h2>
            <ul class="safety-list">
              <li>Vienmēr satiecieties publiskās vietās ar daudz cilvēkiem, piemēram, kafejnīcās, bibliotēkās vai grāmatnīcās.</li>
              <li>Plānojiet apmaiņas diennakts gaišajā laikā, kad iespējams.</li>
              <li>Informējiet draugu vai ģimenes locekli, kur un kad dodaties.</li>
              <li>Apsveriet iespēju ņemt līdzi kādu personu drošībai.</li>
            </ul>
          </div>
        </div>
    
        <div class="safety-card">
          <div class="safety-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <div class="safety-info">
            <h2>Personiskās informācijas aizsardzība</h2>
            <ul class="safety-list">
              <li>Nedali savu mājas adresi vai detalizētu personisko informāciju ar citiem lietotājiem.</li>
              <li>Sazinies caur BookSwap ziņojumu sistēmu, nevis personīgo e-pastu vai telefonu sākotnējai saziņai.</li>
              <li>Esi piesardzīgs, daloties ar saviem sociālo mediju profiliem ar nepazīstamiem lietotājiem.</li>
              <li>Ziņo par jebkuru lietotāju, kurš prasa sensitīvu informāciju vai finanšu datus.</li>
            </ul>
          </div>
        </div>
    
        <div class="safety-card">
          <div class="safety-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="safety-info">
            <h2>Aizdomīgas uzvedības atpazīšana</h2>
            <ul class="safety-list">
              <li>Esi uzmanīgs ar lietotājiem, kuri rada steidzamības sajūtu vai spiež tikties ātri.</li>
              <li>Piesargies no lietotājiem ar nepilnīgiem profiliem vai kuri atsakās sniegt skaidras grāmatu fotogrāfijas.</li>
              <li>Pievērs uzmanību lietotājiem, kuri bieži maina informāciju par sevi vai savām grāmatām.</li>
              <li>Uzticies savām sajūtām – ja kaut kas šķiet nepareizi, neturpini apmaiņu.</li>
            </ul>
          </div>
        </div>
    
        <div class="safety-card">
          <div class="safety-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
            </svg>
          </div>
          <div class="safety-info">
            <h2>Apmaiņas laikā</h2>
            <ul class="safety-list">
              <li>Pirms apmaiņas rūpīgi pārbaudi grāmatas stāvokli.</li>
              <li>Nepiekrīti apmaiņai, ja grāmatas stāvoklis neatbilst aprakstam.</li>
              <li>Uzturi draudzīgu, bet uz grāmatu apmaiņu orientētu sarunu.</li>
              <li>Ja jūties neērti, pieklājīgi pārtrauc tikšanos un dodies prom.</li>
            </ul>
          </div>
        </div>
    
        <div class="safety-card">
          <div class="safety-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
              <line x1="4" y1="22" x2="4" y2="15"></line>
            </svg>
          </div>
          <div class="safety-info">
            <h2>Problēmu ziņošana</h2>
            <ul class="safety-list">
              <li>Ziņo BookSwap par jebkādu uztraucošu uzvedību vai drošības pārkāpumiem.</li>
              <li>Izmanto mūsu <a href="report-issue.html" class="text-link">Ziņot par problēmu</a> lapu, lai dokumentētu gadījumus.</li>
              <li>Ārkārtas gadījumos vai tūlītēju draudu gadījumā sazinies ar vietējām varas iestādēm.</li>
              <li>Ja sastopies ar viltotām grāmatām vai nepatiesu informāciju, ziņo par to, lai aizsargātu citus lietotājus.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    

    <section class="cta-section bg-bookshelf-paper">
      <div class="container">
        <div class="cta-wrapper">
          <div class="cta-content">
            <h2>Jums ir bažas par drošību?</h2>
            <p>Mēs ļoti nopietni rūpējamies par savas kopienas drošību. Ja sastopaties ar aizdomīgām darbībām vai jums ir bažas par drošību, lūdzu, nekavējoties ziņojiet par tām.</p>
          </div>
          <div class="cta-buttons">
            <a href="report-issue.html" class="btn btn-primary">Ziņot par problēmu</a>
            <a href="contact-us.html" class="btn btn-outline">Sazinieties ar atbalsta dienestu</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand Section -->
        <div class="footer-brand">
          <a href="index.html" class="brand">
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
            <li><a href="browse.html">Pārlūkot grāmatas</a></li>
            <li><a href="how-it-works.html">Kā tas strādā</a></li>
            <li><a href="signup.html">Pievienoties BookSwap</a></li>
            <li><a href="login.html">Pieslēgties</a></li>
          </ul>
        </div>
        
        <!-- Help & Support -->
        <div class="footer-links">
          <h3 class="footer-title">Palīdzība un atbalsts</h3>
          <ul>
            <li><a href="faq.html">BUJ</a></li>
            <li><a href="contact-us.html">Sazināties ar mums</a></li>
            <li><a href="safety-tips.html">Drošības padomi</a></li>
            <li><a href="report-issue.html">Ziņot par problēmu</a></li>
          </ul>
        </div>
        
        <!-- Legal -->
        <div class="footer-links">
          <h3 class="footer-title">Juridiskā informācija</h3>
          <ul>
            <li><a href="terms.html">Pakalpojumu noteikumi</a></li>
            <li><a href="privacy-policy.html">Privātuma politika</a></li>
            <li><a href="cookies.html">Sīkfailu politika</a></li>
            <li><a href="gdpr.html">VDAR</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
      </div>
    </div>
  </footer>
</body>
</html>
