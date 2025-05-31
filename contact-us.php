<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sazinies ar mums - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .contact-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .contact-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .contact-methods {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      margin-bottom: 3rem;
    }
    
    @media (min-width: 640px) {
      .contact-methods {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (min-width: 768px) {
      .contact-methods {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    
    .contact-method {
      background-color: var(--color-white);
      border: 1px solid var(--color-paper);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .contact-method:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    }
    
    .contact-icon {
      width: 50px;
      height: 50px;
      background-color: var(--color-paper);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: var(--color-burgundy);
    }
    
    .contact-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .contact-link {
      color: var(--color-burgundy);
    }
    
    .contact-link:hover {
      text-decoration: underline;
    }
    
    .contact-form-section {
      background-color: var(--color-white);
      border: 1px solid var(--color-paper);
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .form-group {
      margin-bottom: 1.5rem;
    }
    
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }
    
    .form-input,
    .form-textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--color-paper);
      border-radius: var(--radius-md);
      font-family: inherit;
      transition: border-color 0.3s;
    }
    
    .form-input:focus,
    .form-textarea:focus {
      outline: none;
      border-color: var(--color-burgundy);
    }
    
    .form-textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    .form-error {
      color: #ef4444;
      font-size: 0.875rem;
      margin-top: 0.25rem;
      display: none;
    }
    
    .form-error.active {
      display: block;
    }
    
    .form-success {
      background-color: #ecfdf5;
      color: #047857;
      padding: 1rem;
      border-radius: var(--radius-md);
      margin-bottom: 1rem;
      display: none;
    }
    
    .form-success.active {
      display: block;
    }
    
    .form-group.required .form-label::after {
      content: "*";
      color: #ef4444;
      margin-left: 0.25rem;
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
    <section class="contact-container">
      <div class="contact-header">
        <h1 class="section-title">Sazinies ar mums</h1>
        <p class="section-description">Vai tev ir jautājums vai nepieciešama palīdzība? Mēs esam šeit, lai palīdzētu!</p>
      </div>
      
      <div class="contact-methods">
        <div class="contact-method">
          <div class="contact-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
          </div>
          <h3 class="contact-title">Tālrunis</h3>
          <p class="text-muted-foreground text-sm">Zvaniet mūsu klientu atbalstam</p>
          <a href="tel:+18001234567" class="contact-link">(800) 123-4567</a>
        </div>
        
        <div class="contact-method">
          <div class="contact-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
          </div>
          <h3 class="contact-title">E-pasts</h3>
          <p class="text-muted-foreground text-sm">Sūti mums e-pastu jebkurā laikā</p>
          <a href="mailto:support@bookswap.com" class="contact-link">support@bookswap.com</a>
        </div>
        
        <div class="contact-method">
          <div class="contact-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </div>
          <h3 class="contact-title">Birojs</h3>
          <p class="text-muted-foreground text-sm">Apmeklē mūsu galveno biroju</p>
          <address class="not-italic text-sm">
            123 Book Street<br>
            Library District<br>
            Booktown, BT 12345
          </address>
        </div>
      </div>
      
      <div class="contact-form-section">
        <h2 class="text-xl font-serif font-semibold mb-4">Sūti mums ziņu</h2>
        
        <div id="formSuccess" class="form-success">
          Paldies par ziņojumu! Mēs sazināsimies ar jums, cik drīz vien iespējams.
        </div>
        
        <form id="contactForm">
          <div class="form-group required">
            <label for="name" class="form-label">Vārds</label>
            <input type="text" id="name" name="name" class="form-input" required>
            <div id="nameError" class="form-error">Lūdzu, ievadiet savu vārdu</div>
          </div>
          
          <div class="form-group required">
            <label for="email" class="form-label">E-pasts Address</label>
            <input type="email" id="email" name="email" class="form-input" required>
            <div id="emailError" class="form-error">Lūdzu, ievadiet derīgu e-pasta adresi</div>
          </div>
          
          <div class="form-group">
            <label for="subject" class="form-label">Temats</label>
            <input type="text" id="subject" name="subject" class="form-input">
          </div>
          
          <div class="form-group required">
            <label for="message" class="form-label">Ziņa</label>
            <textarea id="message" name="message" class="form-textarea" required></textarea>
            <div id="messageError" class="form-error">Lūdzu, ievadiet savu ziņu</div>
          </div>
          
          <div class="form-group">
            <button type="submit" class="btn btn-primary">Nosutit ziņu</button>
          </div>
        </form>
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
          <p>Sazinies ar citiem lasītājiem un apmainies ar grāmatām, kuras mīli.</p>
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
        
        <!-- Juridiska informācija -->
        <div class="footer-links">
          <h3 class="footer-title">Juridiska informācija</h3>
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
  <script src="contact-us.js"></script>
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
