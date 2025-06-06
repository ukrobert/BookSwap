<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BUJ - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .faq-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .faq-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .faq-item {
      margin-bottom: 1.5rem;
      border: 1px solid var(--color-paper);
      border-radius: var(--radius-lg);
      overflow: hidden;
      background-color: var(--color-white);
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .faq-question {
      padding: 1rem 1.5rem;
      background-color: var(--color-white);
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .faq-question:hover {
      background-color: var(--color-light-gray);
    }
    
    .faq-question.active {
      border-bottom: 1px solid var(--color-paper);
    }
    
    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
      padding: 0 1.5rem;
    }
    
    .faq-answer.open {
      max-height: 500px;
      padding: 1rem 1.5rem;
    }
    
    .faq-question::after {
      content: "+";
      font-size: 1.5rem;
      transition: transform 0.3s;
    }
    
    .faq-question.active::after {
      content: "-";
    }
    
    .faq-categories {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      justify-content: center;
      margin-bottom: 2rem;
    }
    
    .faq-category {
      background-color: var(--color-cream);
      border: 1px solid var(--color-paper);
      padding: 0.5rem 1rem;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .faq-category:hover, .faq-category.active {
      background-color: var(--color-burgundy);
      color: var(--color-white);
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
    <section class="faq-container">
      <div class="faq-header">
        <h1 class="section-title">Biežāk uzdotie jautājumi</h1>
        <p class="section-description">Atrodi atbildes uz biežāk uzdotajiem jautājumiem par BookSwap</p>
      </div>
      
      <div class="faq-categories">
        <div class="faq-category active" data-category="all">Visi jautājumi</div>
        <div class="faq-category" data-category="general">Vispārīgi</div>
        <div class="faq-category" data-category="account">Konts</div>
        <div class="faq-category" data-category="books">Grāmatu apmaiņa</div>
        <div class="faq-category" data-category="safety">Drošība un privātums</div>
      </div>
      
      <div class="faq-list">
        <!-- Vispārīgi Questions -->
        <div class="faq-item" data-category="general">
          <div class="faq-question">Kas ir BookSwap?</div>
          <div class="faq-answer">
            <p>BookSwap ir platforma, kas savieno grāmatu mīļotājus, kuri vēlas apmainīt izlasītas grāmatas pret jaunām. Tu vari pievienot grāmatas apmaiņai, pārlūkot citu lietotāju grāmatas un vienoties par apmaiņu klātienē vai pa pastu.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="general">
          <div class="faq-question">Cik maksā BookSwap?</div>
          <div class="faq-answer">
            <p>BookSwap lietošana ir pilnībā bez maksas! Nav abonēšanas maksas vai slēptu izmaksu. Vienīgie iespējamie izdevumi ir saistīti ar sūtīšanu, ja izvēlies apmaiņu pa pastu, nevis klātienē.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="general">
          <div class="faq-question">Kur ir pieejams BookSwap?</div>
          <div class="faq-answer">
            <p>BookSwap ir pieejams visā pasaulē. Tomēr, lai samazinātu piegādes izmaksas un laiku, lielākā daļa apmaiņu notiek vienas valsts vai reģiona ietvaros.</p>
          </div>
        </div>
        
        <!-- Konts Questions -->
        <div class="faq-item" data-category="account">
          <div class="faq-question">Kā izveidot kontu?</div>
          <div class="faq-answer">
            <p>Kontu izveidot ir vienkārši! Klikšķini uz pogas "Reģistrēties" lapas augšējā labajā stūrī, aizpildi informāciju, apstiprini savu e-pastu un sāc grāmatu apmaiņu!</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="account">
          <div class="faq-question">Vai es varu dzēst savu kontu?</div>
          <div class="faq-answer">
            <p>Yes, you can delete your account at any time by going to your profile settings and selecting "Delete Konts". Please note that this action is permanent and all your data will be removed from our system.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="account">
          <div class="faq-question">Kā es varu atiestatīt savu paroli?</div>
          <div class="faq-answer">
            <p>Ja esi aizmirsis paroli, klikšķini uz pogas "Pieslēgties" un tad uz "Aizmirsi paroli?". Mēs nosūtīsim e-pastu ar norādījumiem paroles atjaunošanai. Ja nesaņem e-pastu, pārbaudi surogātpastu.</p>
          </div>
        </div>
        
        <!-- Grāmatu apmaiņa Questions -->
        <div class="faq-item" data-category="books">
          <div class="faq-question">Kā pievienot grāmatu apmaiņai?</div>
          <div class="faq-answer">
            <p>Pieslēdzoties, dodies uz "Manas grāmatas" un klikšķini "Pievienot jaunu grāmatu". Aizpildi informāciju, pievieno grāmatas vāka attēlu (nav obligāti, bet ieteicams), norādi stāvokli un klikšķini "Piedāvāt apmaiņai".</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="books">
          <div class="faq-question">Kā notiek grāmatu apmaiņa?</div>
          <div class="faq-answer">
            <p>Kad atrod grāmatu, kuru vēlies, nosūti apmaiņas pieprasījumu īpašniekam. Ja viņš piekrīt, abi saņemsiet viens otra kontaktinformāciju, lai vienotos par apmaiņu. Varat satikties klātienē vai izmantot pastu. Pēc apmaiņas abas puses to apstiprina BookSwap.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="books">
          <div class="faq-question">Ko darīt, ja saņemtā grāmata neatbilst aprakstam?</div>
          <div class="faq-answer">
            <p>Ja grāmatas stāvoklis neatbilst aprakstītajam, sazinies ar sūtītāju, lai atrisinātu situāciju. Ja neizdodas, vari ziņot BookSwap atbalstam, un mēs palīdzēsim atrisināt problēmu. Tāpēc iesakām apmainīties klātienē, kad iespējams.</p>
          </div>
        </div>
        
        <!-- Drošība un privātums -->
        <div class="faq-item" data-category="safety">
          <div class="faq-question">Vai ir droši satikt svešiniekus grāmatu apmaiņai?</div>
          <div class="faq-answer">
            <p>Iesakām satikties publiskās vietās, piemēram, kafejnīcās, bibliotēkās vai grāmatnīcās dienas laikā. Nekad nesatiecies mājās vai citās privātās vietās. Ja jūties neērti, izvēlies apmaiņu pa pastu.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="safety">
          <div class="faq-question">Kā tiek aizsargāta mana personīgā informācija?</div>
          <div class="faq-answer">
            <p>BookSwap rūp tavu privātumu. Mēs kopīgojam tavu kontaktinformāciju tikai pēc abpusējas vienošanās par apmaiņu. Tava adrese nekad nav redzama citiem lietotājiem, ja vien tu to pats neizvēlies. Vairāk lasi mūsu Privātuma politikā.</p>
          </div>
        </div>
        
        <div class="faq-item" data-category="safety">
          <div class="faq-question">Vai varu bloķēt vai ziņot par citiem lietotājiem?</div>
          <div class="faq-answer">
            <p>Jā, tu vari bloķēt lietotāju savā profila lapā, noklikšķinot uz pogas "Bloķēt lietotāju". Lai ziņotu par pārkāpumiem, izmanto pogu "Ziņot" profila vai grāmatas skatā vai sazinies ar atbalsta komandu.</p>
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
            <li><a href="signup.php">Pievienojies BookSwap</a></li>
            <li><a href="login.php">Pieslēgties</a></li>
          </ul>
        </div>
        
        <!-- Palīdzība un atbalsts -->
        <div class="footer-links">
          <h3 class="footer-title">Palīdzība un atbalsts</h3>
          <ul>
            <li><a href="faq.php">BUJ</a></li>
            <li><a href="contact-us.php">Sazinies ar mums</a></li>
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
        <p>&copy; <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
  <script src="auth.js"></script>
  <script src="faq.js"></script>
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