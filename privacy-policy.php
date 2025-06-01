<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Privātuma politika | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap">
  <style>
    .privacy-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .privacy-header {
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .privacy-header h1 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    
    .privacy-header p {
      color: #666;
      max-width: 700px;
      margin: 0 auto;
    }
    
    .privacy-content {
      background-color: #fff;
      border-radius: 10px;
      padding: 2rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      font-size: 1rem;
      line-height: 1.6;
    }
    
    .privacy-content h2 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    
    .privacy-content h3 {
      font-family: 'Merriweather', serif;
      color: #7d654b;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
      font-size: 1.25rem;
    }
    
    .privacy-content p {
      margin-bottom: 1rem;
    }
    
    .privacy-content ul {
      margin-bottom: 1rem;
      padding-left: 1.5rem;
    }
    
    .privacy-content li {
      margin-bottom: 0.5rem;
    }
    
    .privacy-navigation {
      position: sticky;
      top: 100px;
      background: #fff;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
    }
    
    .privacy-navigation h3 {
      font-family: 'Merriweather', serif;
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: #59321f;
    }
    
    .privacy-nav-links {
      list-style: none;
      padding: 0;
    }
    
    .privacy-nav-links li {
      margin-bottom: 0.5rem;
    }
    
    .privacy-nav-links a {
      color: #7d654b;
      text-decoration: none;
      transition: color 0.3s;
      display: block;
      padding: 0.5rem 0;
      border-bottom: 1px solid #f5f5f5;
    }
    
    .privacy-nav-links a:hover, 
    .privacy-nav-links a.active {
      color: #59321f;
    }
    
    .privacy-last-updated {
      font-style: italic;
      margin-top: 2rem;
      text-align: center;
      color: #666;
    }
    
    .privacy-section {
      scroll-margin-top: 120px;
    }
    
    .privacy-action {
      margin-top: 3rem;
      text-align: center;
    }
    
    .privacy-action .btn {
      display: inline-block;
      background-color: #59321f;
      color: #fff;
      padding: 0.75rem 2rem;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s;
    }
    
    .privacy-action .btn:hover {
      background-color: #7d654b;
    }
    
    @media (min-width: 768px) {
      .privacy-container {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 2rem;
        align-items: start;
      }
      
      .privacy-header {
        grid-column: 1 / -1;
      }
      
      .privacy-navigation {
        margin-bottom: 0;
      }
      
      .privacy-action {
        grid-column: 1 / -1;
      }
    }
    
    @media (max-width: 767px) {
      .privacy-navigation {
        position: relative;
        top: 0;
      }
    }

    /* Animation for privacy sections */
    .fade-in {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.5s ease, transform 0.5s ease;
    }
    
    .fade-in.active {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>
<body data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
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
    <div class="privacy-container">
      <div class="privacy-header">
        <h1>Privātuma politika</h1>
        <p>Uzņēmumā BookSwap mēs nopietni izturamies pret jūsu konfidencialitāti. Šajā konfidencialitātes politikā ir aprakstīts, kādu personisko informāciju mēs apkopojam un kā to izmantojam.</p>
      </div>
      
      <div class="privacy-navigation">
        <h3>Saturs</h3>
        <ul class="privacy-nav-links">
          <li><a href="#information-collection" class="active">Informācijas vākšana</a></li>
          <li><a href="#information-use">Informācijas izmantošana</a></li>
          <li><a href="#information-sharing">Informācijas apmaiņa</a></li>
          <li><a href="#cookies">Sīkfaili</a></li>
          <li><a href="#your-rights">Jūsu tiesības</a></li>
          <li><a href="#data-security">Datu drošība</a></li>
          <li><a href="#children-privacy">Bērnu privātums</a></li>
          <li><a href="#changes">Šīs politikas izmaiņas</a></li>
          <li><a href="#contact-us">Sazinieties ar mums</a></li>
        </ul>
      </div>
      
      <div class="privacy-content">
        <section id="information-collection" class="privacy-section fade-in">
          <h2>Informācijas vākšana</h2>
          <p>Mēs vākam informāciju, lai nodrošinātu labākus pakalpojumus mūsu lietotājiem un uzlabotu jūsu pieredzi BookSwap.</p>
          <h3>Informācija, ko jūs sniedzat mums</h3>
          <p>Mēs vācam informāciju, ko jūs tieši sniedzat mums, tostarp:</p>
          <ul>
            <li>Kontas informācija: Kad jūs izveidojat BookSwap kontu, mēs vācam jūsu vārdu, e-pasta adresi, paroli un opciju profila informāciju.</li>
            <li>Grāmatu sludinājumi: Informācija par grāmatām, kuras jūs piedāvājat maiņai, tostarp nosaukumi, autori, stāvoklis un fotogrāfijas.</li>
            <li>Darījumu informācija: Detalizēta informācija par jūsu grāmatu maiņām, tostarp piegādes adreses un saziņa ar citiem lietotājiem.</li>
            <li>Saziņa: Informācija, ko jūs sniedzat, sazinoties ar mūsu atbalsta komandu vai piedaloties aptaujās.</li>
          </ul>
          <h3>Automātiski vāktā informācija</h3>
          <p>Kad jūs izmantojat mūsu pakalpojumus, mēs automātiski vācam noteiktu informāciju, tostarp:</p>
          <ul>
            <li> Ierīces informācija: IP adrese, pārlūkprogrammas veids un iestatījumi, operētājsistēma un ierīces identifikatori.</li>
            <li>Izmantošanas dati: Apskatītās lapas, izmantotās funkcijas, meklēšanas vaicājumi, klikšķi un mijiedarbība ar mūsu platformu.</li>
            <li>Atrašanās vietas informācija: Vispārēja atrašanās vieta, pamatojoties uz IP adresi, vai precīzāka atrašanās vieta, ja jūs aktivizējat atrašanās vietas pakalpojumus.</li>
          </ul>
        </section>
        
        <section id="information-use" class="privacy-section fade-in">
          <h2>Informācijas izmantošana</h2>
          <p>Mēs izmantojam savākto informāciju, lai:</p>
          <ul>
            <li>Nodrošinātu, uzturētu un uzlabotu mūsu pakalpojumus</li>
            <li>Apstrādātu darījumus un nosūtītu saistītu informāciju</li>
            <li>Izveidotu un uzturētu jūsu kontu</li>
            <li>Savienotu jūs ar citiem lietotājiem grāmatu maiņai</li>
            <li>Nosūtītu paziņojumus, atjauninājumus un atbalsta ziņojumus</li>
            <li>Atklātu, izmeklētu un novērstu krāpnieciskus darījumus un citas nelikumīgas darbības</li>
            <li>Personalizētu jūsu pieredzi un nodrošinātu saturu un funkcijas, kas atbilst jūsu interesēm</li>
            <li>Uzraudzītu un analizētu tendences, izmantošanu un aktivitātes saistībā ar mūsu pakalpojumiem</li>
            <li>Izpildītu juridiskās saistības</li>
          </ul>
        </section>
        
        <section id="information-sharing" class="privacy-section fade-in">
          <h2>Informācijas koplietošana</h2>
          <p>Mēs varam dalīties ar jūsu personisko informāciju šādās situācijās:</p>
          <ul>
            <li><strong>Ar citiem lietotājiem:</strong> Kad jūs izveidojat grāmatu sludinājumus vai piedalāties maiņās, noteikta informācija (piemēram, jūsu vārds, profila attēls, aptuvenā atrašanās vieta un grāmatu sludinājumi) tiek koplietota ar citiem lietotājiem.</li>
            <li><strong>Pakalpojumu sniedzējiem:</strong> Mēs dalāmies ar informāciju ar trešo pušu piegādātājiem, konsultantiem un citiem pakalpojumu sniedzējiem, kuriem nepieciešama piekļuve šādai informācijai, lai veiktu darbu mūsu vārdā.</li>
            <li><strong>Juridiskās prasības:</strong> Mēs varam atklāt informāciju, ja uzskatām, ka atklāšana ir saprātīgi nepieciešama, lai izpildītu likumu, regulējumu, juridisko procesu vai valsts pieprasījumu.</li>
            <li><strong>Uzņēmējdarbības pārdošana:</strong> Saistībā ar jebkuru apvienošanos, uzņēmuma aktīvu pārdošanu, finansēšanu vai visu vai daļas iegādi no mūsu uzņēmuma.</li>
            <li><strong>Ar jūsu piekrišanu:</strong> Mēs varam dalīties ar informāciju ar trešām personām, kad mums ir jūsu piekrišana to darīt.</li>
          </ul>
        </section>
        
        <section id="cookies" class="privacy-section fade-in">
          <h2>Cookies</h2>
          <p>BookSwap izmanto sīkdatnes un līdzīgas tehnoloģijas, lai uzlabotu jūsu pieredzi, vāktu informāciju par lietotājiem un uzlabotu mūsu pakalpojumus. Lai iegūtu vairāk informācijas par mūsu sīkdatņu izmantošanu, lūdzu, skatiet mūsu <a href="cookies.php">Sīkdatņu politiku</a>.</p>
        </section>
        
        <section id="your-rights" class="privacy-section fade-in">
          <h2>Jūsu tiesības</h2>
          <p>Atkarībā no jūsu atrašanās vietas jums var būt noteiktas tiesības attiecībā uz jūsu personisko informāciju, tostarp:</p>
          <ul>
            <li>Piekļuvei, labojumam vai dzēšanai jūsu personiskajai informācijai</li>
            <li>Pretenzijai pret mūsu informācijas apstrādi</li>
            <li>Informācijas apstrādes ierobežošanai</li>
            <li>Datu pārnesamība</li>
            <li>Piekrišanas atsaukšana</li>
          </ul>
          <p>Lai īstenotu šīs tiesības, lūdzu, sazinieties ar mums, izmantojot informāciju, kas sniegta sadaļā "Sazinieties ar mums". Lai iegūtu vairāk informācijas par jūsu tiesībām saskaņā ar GDPR, lūdzu, apmeklējiet mūsu <a href="gdpr.php">GDPR atbilstības</a> lapu.</p>
        </section>
        
        <section id="data-security" class="privacy-section fade-in">
          <h2>Datu drošība</h2>
          <p>Mēs īstenojam atbilstošus tehniskos un organizatoriskos pasākumus, lai aizsargātu jūsu personiskās informācijas drošību. Tomēr neviena sistēma nav pilnīgi droša, un mēs nevaram garantēt jūsu informācijas absolūtu drošību.</p>
          <p>Mēs regulāri pārskatām mūsu drošības procedūras un apsveram jaunas tehnoloģijas un metodes, lai aizsargātu jūsu informāciju. Jūsu konts ir aizsargāts ar paroli, un mēs mudinām jūs izmantot unikālu un spēcīgu paroli, ierobežot piekļuvi jūsu ierīcei un izrakstīties pēc mūsu pakalpojumu izmantošanas.</p>
        </section>
        <section id="children-privacy" class="privacy-section fade-in">
          <h2>Bērnu privātums</h2>
          <p>BookSwap nav paredzēts bērniem, kas jaunāki par 16 gadiem. Mēs apzināti nesavācam personisko informāciju no bērniem, kas ir jaunāki par 16 gadiem. Ja jūs esat vecāks vai aizbildnis un uzskatāt, ka jūsu bērns ir sniedzis mums personisko informāciju, lūdzu, sazinieties ar mums, un mēs dzēsīsim šādu informāciju no mūsu sistēmām.</p>
        </section>
        <section id="changes" class="privacy-section fade-in">
          <h2>Izmaiņas šajā politikā</h2>
          <p>Mēs varam laiku pa laikam atjaunināt šo privātuma politiku. Ja mēs veiksim būtiskas izmaiņas, mēs par to informēsim jūs, izmantojot e-pastu, kas saistīts ar jūsu kontu, vai publicējot paziņojumu mūsu vietnē. Mēs mudinām jūs periodiski pārskatīt šo lapu, lai iegūtu jaunāko informāciju par mūsu privātuma praksēm.</p>
        </section>
        <section id="contact-us" class="privacy-section fade-in">
          <h2>Sazinieties ar mums</h2>
          <p>Ja jums ir kādi jautājumi, bažas vai atsauksmes par šo privātuma politiku vai mūsu privātuma praksēm, lūdzu, sazinieties ar mums šeit:</p>
          <p>E-pasts: bookswap@gmail.com</p>
          <p>Grāmatu iela 5<br>Rīga<br>Latvijas Republika</p>
        </section>
        
      </div>
      
      <div class="privacy-action">
        <a href="contact-us.php" class="btn">Vai jums ir jautājumi? Sazinieties ar mums</a>
      </div>
    </div>
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

  <script src="js/utils.js"></script>
  <script src="privacy-policy.js"></script>

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
</body>
</html>