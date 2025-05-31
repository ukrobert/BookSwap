<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ziņot par problēmu - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <script src="report-issue.js" defer></script>
</head>
<body class="paper-texture" data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
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
        <h1>Ziņot par problēmu</h1>
        <p>Informējiet mūs par jebkurām problēmām, ar kurām esat saskārušies, izmantojot BookSwap</p>
      </div>
    </section>

    <section class="report-section">
      <div class="container">
        <div class="report-container">
          <div class="report-info">
            <h2>Kā ziņot par problēmu</h2>
            <p>Mēs ņemam vērā visus ziņojumus un cenšamies tos ātri atrisināt. Lūdzu, sniedziet pēc iespējas vairāk detaļu, lai palīdzētu mums efektīvi atrisināt jūsu problēmu.</p>
            <div class="issue-types">
              <h3>Izplatītas problēmas, par kurām varat ziņot:</h3>
              <ul class="issue-list">
                <li>
                  <strong>Grāmatas stāvokļa nepareiza attēlošana</strong>
                  <span>Kad grāmatas stāvoklis neatbilst tās aprakstam</span>
                </li>
                <li>
                  <strong>Lietotāju uzvedības bažas</strong>
                  <span>Nepiemērota uzvedība no citu lietotāju puses</span>
                </li>
                <li>
                  <strong>Tehniskās problēmas</strong>
                  <span>Problēmas ar vietnes vai lietotnes funkcionalitāti</span>
                </li>
                <li>
                  <strong>Neizdotās maiņas</strong>
                  <span>Problēmas ar grāmatu maiņām, kas netika pabeigtas pareizi</span>
                </li>
                <li>
                  <strong>Kontu problēmas</strong>
                  <span>Problēmas ar jūsu lietotāja kontu, pieteikšanos vai iestatījumiem</span>
                </li>
              </ul>
            </div>
            <div class="urgent-notice">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
              <div>
                <h4>Steidzamu drošības bažu gadījumā:</h4>
                <p>Ja jums ir tūlītējas drošības bažas, lūdzu, vispirms sazinieties ar vietējām varas iestādēm, tad ziņojiet par incidentu mums.</p>
              </div>
            </div>
          </div>
          <div class="report-form-container">
            <form id="reportForm" class="report-form">
              <div class="form-group">
                <label for="issueType">Problēmas veids*</label>
                <select id="issueType" class="form-select" required>
                  <option value="" disabled selected>Izvēlieties problēmas veidu</option>
                  <option value="book-condition">Grāmatas stāvokļa nepareiza attēlošana</option>
                  <option value="user-conduct">Lietotāju uzvedības bažas</option>
                  <option value="technical">Tehniskās problēmas</option>
                  <option value="failed-trade">Neizdotā maiņa</option>
                  <option value="account">Kontu problēmas</option>
                  <option value="other">Cits</option>
                </select>
                <div id="issueTypeError" class="form-error">Lūdzu, izvēlieties problēmas veidu</div>
              </div>
              <div class="form-group user-related-field">
                <label for="relatedUser">Saistītais lietotājs (ja attiecināms)</label>
                <input type="text" id="relatedUser" class="form-input" placeholder="Iesaistītā lietotāja vārds">
              </div>
              <div class="form-group book-related-field">
                <label for="relatedBook">Grāmatas nosaukums (ja attiecināms)</label>
                <input type="text" id="relatedBook" class="form-input" placeholder="Iesaistītās grāmatas nosaukums">
              </div>
              <div class="form-group">
                <label for="issueDescription">Problēmas apraksts*</label>
                <textarea id="issueDescription" class="form-textarea" rows="5" placeholder="Lūdzu, sniedziet detalizētu informāciju par notikušo" required></textarea>
                <div id="descriptionError" class="form-error">Lūdzu, aprakstiet problēmu</div>
              </div>
              <div class="form-group">
                <label for="issueDate">Kad tas notika?*</label>
                <input type="date" id="issueDate" class="form-input" required>
                <div id="dateError" class="form-error">Lūdzu, norādiet derīgu datumu</div>
              </div>
              <div class="form-group">
                <label for="attachments">Pielikumi (pēc izvēles)</label>
                <input type="file" id="attachments" class="form-file" multiple>
                <small class="form-help">Augšupielādējiet ekrānuzņēmumus vai fotoattēlus, kas saistīti ar jūsu problēmu (maks. 3 faili, 5MB katrs)</small>
              </div>
              <div class="form-group">
                <label for="contactMethod">Vēlamā saziņas metode*</label>
                <select id="contactMethod" class="form-select" required>
                  <option value="" disabled selected>Izvēlieties vēlamo saziņas metodi</option>
                  <option value="email">E-pasts</option>
                  <option value="phone">Telefons</option>
                </select>
                <div id="contactMethodError" class="form-error">Lūdzu, izvēlieties saziņas metodi</div>
              </div>
              <div id="emailField" class="form-group contact-field">
                <label for="emailAddress">E-pasta adrese*</label>
                <input type="email" id="emailAddress" class="form-input" placeholder="Jūsu e-pasta adrese">
                <div id="emailError" class="form-error">Lūdzu, ievadiet derīgu e-pasta adresi</div>
              </div>
              <div id="phoneField" class="form-group contact-field hidden">
                <label for="phoneNumber">Telefona numurs*</label>
                <input type="tel" id="phoneNumber" class="form-input" placeholder="Jūsu telefona numurs">
                <div id="phoneError" class="form-error">Lūdzu, ievadiet derīgu telefona numuru</div>
              </div>
              <div class="form-group">
                <div class="checkbox-group">
                  <input type="checkbox" id="termsAgreement" required>
                  <label for="termsAgreement">Es saprotu, ka nepatiesi ziņojumi var novest pie konta apturēšanas*</label>
                </div>
                <div id="termsError" class="form-error">Jums jāpiekrīt šim apgalvojumam</div>
              </div>
              <div class="form-submit">
                <button type="submit" class="btn btn-primary btn-block">Iesniegt ziņojumu</button>
              </div>
              <div id="formSuccess" class="form-success">
                Jūsu ziņojums ir veiksmīgi iesniegts. Mēs to pārskatīsim un sazināsimies ar jums 24-48 stundu laikā.
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="faq-section bg-bookshelf-paper"> 
      <div class="container"> 
        <h2>Biežāk uzdotie jautājumi</h2> 
        <div class="faq-grid"> 
          <div class="faq-item"> 
            <div class="faq-question"> 
              Cik ilgs laiks būs nepieciešams, lai pārskatītu manu ziņojumu? 
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon"> 
                <polyline points="6 9 12 15 18 9"></polyline> 
              </svg> 
            </div> 
            <div class="faq-answer"> 
              Mēs cenšamies pārskatīt visus ziņojumus 24–48 stundu laikā. Steidzamos drošības gadījumos mēs prioritizējam šos ziņojumus un reaģējam daudz ātrāk. 
            </div> 
          </div> 
          <div class="faq-item"> 
            <div class="faq-question"> 
              Vai otrs lietotājs uzzinās, ka es viņu ziņoju? 
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon"> 
                <polyline points="6 9 12 15 18 9"></polyline> 
              </svg> 
            </div> 
            <div class="faq-answer"> 
              Mēs apstrādājam visus ziņojumus konfidenciāli. Ziņotais lietotājs neuzzinās, kurš viņu ziņojis, taču viņš var tikt informēts, ka par viņa rīcību ir izteiktas bažas. 
            </div> 
          </div> 
          <div class="faq-item"> 
            <div class="faq-question"> 
              Kas notiek pēc tam, kad es iesniedzu ziņojumu? 
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon"> 
                <polyline points="6 9 12 15 18 9"></polyline> 
              </svg> 
            </div> 
            <div class="faq-answer"> 
              Mūsu moderācijas komanda pārskatīs jūsu ziņojumu un izmeklēs situāciju. Mēs sazināsimies ar jums, izmantojot jūsu norādīto saziņas metodi, lai iegūtu papildu informāciju, ja tas būs nepieciešams. Atkarībā no situācijas smaguma var tikt piemērots brīdinājums vai pat konta apturēšana. 
            </div> 
          </div> 
          <div class="faq-item"> 
            <div class="faq-question"> 
              Vai es varu pārbaudīt sava ziņojuma statusu? 
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chevron-icon"> 
                <polyline points="6 9 12 15 18 9"></polyline> 
              </svg> 
            </div> 
            <div class="faq-answer"> 
              Jā! Ja esat pieteicies, varat apskatīt savus ziņotos gadījumus un to statusu savā profila informācijas panelī. Jūs varat arī atbildēt uz mūsu e-pasta ziņojumiem, lai pieprasītu papildu informāciju. 
            </div> 
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