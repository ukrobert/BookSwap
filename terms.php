<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pakalpojumu noteikumi - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <script src="terms.js" defer></script>
</head>
<body class="paper-texture" body data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
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
        <h1>Pakalpojumu noteikumi</h1>
        <p>Pēdējo reizi atjaunināts: Aprīlis 8, 2025</p>
      </div>
    </section>

    <section class="terms-section">
      <div class="container">
        <div class="terms-container">
          <div class="terms-sidebar">
            <div class="terms-navigation">
              <h3>Saturs</h3>
              <ul class="terms-nav-links">
                <li><a href="#introduction">1. Ievads</a></li>
                <li><a href="#account">2. Konta noteikumi</a></li>
                <li><a href="#service-terms">3. Pakalpojuma noteikumi</a></li>
                <li><a href="#book-exchange">4. Grāmatu apmaiņas politika</a></li>
                <li><a href="#user-conduct">5. Lietotāja uzvedība</a></li>
                <li><a href="#intellectual-property">6. Intelektuālais īpašums</a></li>
                <li><a href="#limitation-liability">7. Atbildības ierobežojums</a></li>
                <li><a href="#modifications">8. Noteikumu izmaiņas</a></li>
                <li><a href="#termination">9. Izbeigšana</a></li>
                <li><a href="#governing-law">10. Piemērojamie likumi</a></li>
              </ul>
            </div>
    
            <div class="terms-contact">
              <h3>Jautājumi?</h3>
              <p>Ja jums ir jautājumi par mūsu Lietošanas noteikumiem, lūdzu, sazinieties ar mums:</p>
              <a href="contact-us.php" class="btn btn-outline btn-sm">Sazinieties ar mums</a>
            </div>
          </div>
    
          <div class="terms-content">
            <section id="introduction" class="terms-section">
              <h2>1. Ievads</h2>
              <p>Laipni lūdzam BookSwap ("mēs", "mūsu", "mūs"). Izmantojot BookSwap vietni, mobilo lietotni vai jebkādus citus ar to saistītus pakalpojumus (kopā — "Pakalpojums"), jūs piekrītat šiem Lietošanas noteikumiem ("Noteikumi").</p>
              <p>Lūdzu, rūpīgi izlasiet šos Noteikumus. Ja nepiekrītat tiem, nedrīkstat izmantot Pakalpojumu. Izmantojot Pakalpojumu, jūs apliecināt, ka esat izlasījis, sapratis un piekritis šiem Noteikumiem.</p>
            </section>
    
            <section id="account" class="terms-section">
              <h2>2. Konta noteikumi</h2>
              <p>2.1 <strong>Konta izveide</strong>: Lai izmantotu noteiktas Pakalpojuma funkcijas, jums jāizveido konts. Jūs piekrītat sniegt precīzu un aktuālu informāciju reģistrācijas laikā un to atjaunināt, lai tā vienmēr būtu pareiza.</p>
              <p>2.2 <strong>Atbildība par kontu</strong>: Jūs esat atbildīgs par sava paroles drošību un visām darbībām, kas tiek veiktas no jūsu konta. Jums nekavējoties jāinformē mūs par jebkādu nesankcionētu konta lietošanu.</p>
              <p>2.3 <strong>Vecuma ierobežojums</strong>: Jums jābūt vismaz 13 gadus vecam, lai izmantotu Pakalpojumu. Ja esat jaunāks par 18 gadiem, jūs apliecināt, ka jums ir vecāku vai aizbildņa atļauja un viņi ir iepazinušies ar šiem Noteikumiem.</p>
              <p>2.4 <strong>Viens konts</strong>: Jums nav atļauts izveidot vairākus kontus vai nodot savu kontu citai personai.</p>
            </section>
    
            <section id="service-terms" class="terms-section">
              <h2>3. Pakalpojuma noteikumi</h2>
              <p>3.1 <strong>Pakalpojuma apraksts</strong>: BookSwap ir platforma, kas ļauj lietotājiem ievietot grāmatas un vienoties par apmaiņu ar citiem lietotājiem.</p>
              <p>3.2 <strong>Lietotāju piemērotība</strong>: Pakalpojumu var izmantot tikai personas, kuras ir juridiski spējīgas slēgt saistošus līgumus.</p>
              <p>3.3 <strong>Pakalpojuma pieejamība</strong>: Mēs paturam tiesības jebkurā laikā mainīt, apturēt vai pārtraukt Pakalpojumu vai tā daļu bez iepriekšēja brīdinājuma.</p>
              <p>3.4 <strong>Maksas</strong>: Pamata Pakalpojums šobrīd ir pieejams bez maksas. Tomēr mēs paturam tiesības nākotnē ieviest maksu par noteiktām funkcijām, par ko jūs iepriekš informēsim.</p>
            </section>
    
            <section id="book-exchange" class="terms-section">
              <h2>4. Grāmatu apmaiņas politika</h2>
              <p>4.1 <strong>Grāmatu ievietošana</strong>: Ievietojot grāmatu, jums jānorāda precīza informācija par tās nosaukumu, autoru, stāvokli un citiem svarīgiem datiem.</p>
              <p>4.2 <strong>Īpašumtiesības</strong>: Ievietojot grāmatu, jūs apliecināt, ka esat tās likumīgais īpašnieks un jums ir tiesības to apmainīt.</p>
              <p>4.3 <strong>Apmaiņas kārtība</strong>: BookSwap nodrošina platformu lietotāju savienošanai, bet neiesaistās fiziskajā grāmatu apmaiņas procesā.</p>
              <p>4.4 <strong>Lietotāju atbildība</strong>: Lietotāji paši ir atbildīgi par grāmatu apmaiņas plānošanu un izpildi — tikšanās laiku, vietu un citām detaļām.</p>
              <p>4.5 <strong>Aizliegtie priekšmeti</strong>: Jums nav atļauts ievietot vai apmainīt materiālus, kas ir nelegāli, aizskaroši vai pārkāpj trešo personu tiesības.</p>
            </section>
    
            <section id="user-conduct" class="terms-section">
              <h2>5. Lietotāja uzvedība</h2>
              <p>5.1 <strong>Vispārīga uzvedība</strong>: Jūs piekrītat neizmantot Pakalpojumu, lai:</p>
              <ul>
                <li>Pārkāptu jebkādus likumus vai noteikumus</li>
                <li>Pārkāptu citu personu tiesības, tostarp intelektuālā īpašuma tiesības</li>
                <li>Sūtītu surogātpastu vai nevēlamus ziņojumus</li>
                <li>Izplatītu vīrusus vai kaitīgu kodu</li>
                <li>Vāktu lietotāju informāciju bez viņu piekrišanas</li>
                <li>Uztvertos par citu personu vai organizāciju</li>
                <li>Traucētu Pakalpojuma darbību</li>
              </ul>
              <p>5.2 <strong>Kopienas vadlīnijas</strong>: Jums jāievēro mūsu Kopienas vadlīnijas, kuras var tikt periodiski atjauninātas.</p>
              <p>5.3 <strong>Ziņošana</strong>: Ja pamanāt saturu vai uzvedību, kas pārkāpj šos Noteikumus, lūdzu, ziņojiet par to mums nekavējoties.</p>
            </section>
    
            <section id="intellectual-property" class="terms-section">
              <h2>6. Intelektuālais īpašums</h2>
              <p>6.1 <strong>Pakalpojuma saturs</strong>: Viss saturs Pakalpojumā, tostarp teksts, grafika, logotipi un programmatūra, pieder BookSwap vai tā licencētājiem un ir aizsargāts ar autortiesību un preču zīmju likumiem.</p>
              <p>6.2 <strong>Ierobežota licence</strong>: Jums tiek piešķirta ierobežota, neekskluzīva, nepārvedama un atceļama licence izmantot Pakalpojumu personiskiem, nekomerciāliem nolūkiem.</p>
              <p>6.3 <strong>Lietotāju saturs</strong>: Jūs saglabājat īpašumtiesības uz jebkādu saturu, ko iesniedzat Pakalpojumā. Iesniedzot saturu, jūs piešķirat BookSwap vispasaules, neekskluzīvu, bezatlīdzības licenci šī satura izmantošanai Pakalpojuma nodrošināšanas nolūkos.</p>
              <p>6.4 <strong>Atsauksmes</strong>: Jebkādas atsauksmes, ieteikumi vai idejas, ko iesniedzat, var tikt izmantotas bez pienākuma jums maksāt.</p>
            </section>
    
            <section id="limitation-liability" class="terms-section">
              <h2>7. Atbildības ierobežojums</h2>
              <p>7.1 <strong>Garantiju atteikums</strong>: PAKALPOJUMS TIEK NODROŠINĀTS "TĀDS, KĀDS TAS IR", BEZ JEBKĀDĀM IZTEIKTĀM VAI NETIEŠĀM GARANTIJĀM.</p>
              <p>7.2 <strong>Atbildības ierobežojums</strong>: CIK TĀLU TO PIEĻAUJ PIEMĒROJAMIE LIKUMI, BOOKSWAP NEBŪS ATBILDĪGS PAR JEBKĀDIEM NETIEŠIEM, NEJAUŠIEM VAI SPECIĀLIEM ZAUDĒJUMIEM, IESKAITOT PEĻŅAS ZUDUMU, DATU ZUDUMU, LABAS GRIBAS ZAUDĒJUMU, VAI CITIEM NEMATERIĀLIEM ZAUDĒJUMIEM, KO IZRAISA:</p>
              <ul>
                <li>PIEKĻUVE VAI NESPĒJA PIEKĻŪT PAKALPOJUMAM</li>
                <li>TREŠO PUŠU RĪCĪBA VAI SATURS PAKALPOJUMĀ</li>
                <li>SATURS, KO IEGŪSTAT CAUR PAKALPOJUMU</li>
                <li>NESANKCIONĒTA PIEKĻUVE JŪSU DATIEM</li>
              </ul>
              <p>7.3 <strong>Apmaiņas drošība</strong>: BOOKSWAP NAV ATBILDĪGS PAR LIETOTĀJU RĪCĪBU APMAIŅAS LAIKĀ. LIETOTĀJI PIEKRĪT VEIKT SAPRĀTĪGUS DROŠĪBAS PASĀKUMUS.</p>
            </section>
    
            <section id="modifications" class="terms-section">
              <h2>8. Noteikumu izmaiņas</h2>
              <p>8.1 <strong>Atjauninājumi</strong>: Mēs varam mainīt šos Noteikumus laiku pa laikam. Būtisku izmaiņu gadījumā mēs jūs informēsim caur Pakalpojumu vai e-pastu.</p>
              <p>8.2 <strong>Turpmāka izmantošana</strong>: Turpinot izmantot Pakalpojumu pēc izmaiņām, jūs piekrītat jaunajiem Noteikumiem.</p>
            </section>
    
            <section id="termination" class="terms-section">
              <h2>9. Izbeigšana</h2>
              <p>9.1 <strong>Izbeigšana no jūsu puses</strong>: Jūs jebkurā laikā varat izbeigt savu kontu, sekojot instrukcijām Pakalpojumā.</p>
              <p>9.2 <strong>Izbeigšana no BookSwap puses</strong>: Mēs varam izbeigt vai apturēt jūsu kontu jebkurā laikā, ja uzskatām, ka esat pārkāpis Noteikumus.</p>
              <p>9.3 <strong>Izbeigšanas sekas</strong>: Pēc konta izbeigšanas jūsu tiesības izmantot Pakalpojumu nekavējoties beidzas. Noteikumi, kuru būtība paredz ilgāku spēkā esamību, paliks spēkā.</p>
            </section>
    
            <section id="governing-law" class="terms-section">
              <h2>10. Piemērojamie likumi</h2>
              <p>10.1 <strong>Piemērojamais likums</strong>: Šie Noteikumi tiek regulēti un interpretēti saskaņā ar [Jurisdikcija] tiesību aktiem.</p>
              <p>10.2 <strong>Strīdu izšķiršana</strong>: Visi strīdi tiks izšķirti šķīrējtiesā [Pilsēta, Valsts], izmantojot angļu valodu, saskaņā ar [Šķīrējtiesas institūcija] noteikumiem.</p>
              <p>10.3 <strong>Kolektīvās prasības atteikums</strong>: Jūs piekrītat, ka strīdi tiks risināti tikai individuāli, nevis kolektīvā vai grupas prasībā.</p>
            </section>
    
            <div class="terms-final">
              <p>Izmantojot BookSwap, jūs apliecināt, ka esat izlasījis, sapratis un piekritis šiem Lietošanas noteikumiem.</p>
              <p>Ja jums ir kādi jautājumi, sazinieties ar mums pa e-pastu <a href="mailto:legal@bookswap.example.com">legal@bookswap.example.com</a> vai izmantojot <a href="contact-us.php">saziņas formu</a>.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    

    <section class="cta-section bg-bookshelf-paper">
      <div class="container">
        <div class="cta-content">
          <h2 class="cta-title">Gatavs sākt grāmatu apmaiņu?</h2>
          <p class="cta-description">Pievienojies mūsu kopienai jau šodien un satiec tūkstošiem grāmatu mīļotāju, kuri ir gatavi apmainīties ar stāstiem un piedzīvojumiem.</p>
          <div class="cta-buttons">
            <a href="signup.php" class="btn btn-white">
              Reģistrēties tagad
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
            <a href="browse.php" class="btn btn-outline-white">Pārlūkot grāmatas</a>
          </div>
          <p class="cta-signin">Jau ir konts? <a href="login.php">Pieslēgties</a></p>
        </div>
      </div>
    </section>    
  </main>

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
</body>

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
<!-- Chat Widget End -->

<!-- Подключаем CSS и JS для чата -->
<link rel="stylesheet" href="chat.css?v=<?php echo time(); // Cache busting ?>">
<script src="chat.js?v=<?php echo time(); // Cache busting ?>"></script>
</html>