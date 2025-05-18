<?php
// contact-us.php

/**
 * Kontaktu formas apstrādes skripts BookSwap platformai
 * 
 * Šis fails apstrādā lietotāju iesūtītos kontaktu formas ziņojumus,
 * veic datu validāciju un saglabā ziņojumus datubāzē.
 * 
 * @author Roberto Šķiņķis
 * @version 1.0
 */

// Sesijas inicializācija
session_start();

// Datubāzes savienojuma konfigurācija
$db_host = "localhost";     // Datubāzes servera adrese
$db_user = "bookswap_user"; // Datubāzes lietotājvārds
$db_pass = "secure_password"; // Datubāzes parole
$db_name = "bookswap_db";   // Datubāzes nosaukums

// Kļūdu ziņojumu mainīgais
$error_message = "";
$success_message = "";

// Pārbaudām, vai forma ir iesniegta
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Iegūstam un attīrām ievades datus
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validācija
    $is_valid = true;
    $errors = array();
    
    // Pārbaudām, vai obligātie lauki ir aizpildīti
    if (empty($name)) {
        $is_valid = false;
        $errors['name'] = "Lūdzu, ievadiet savu vārdu";
    }
    
    if (empty($email)) {
        $is_valid = false;
        $errors['email'] = "Lūdzu, ievadiet savu e-pasta adresi";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $is_valid = false;
        $errors['email'] = "Lūdzu, ievadiet derīgu e-pasta adresi";
    }
    
    if (empty($message)) {
        $is_valid = false;
        $errors['message'] = "Lūdzu, ievadiet ziņojuma tekstu";
    }
    
    // Ja temats nav norādīts, izmantojam noklusējuma vērtību
    if (empty($subject)) {
        $subject = "Ziņojums no kontaktu formas";
    }
    
    // Ja visi dati ir derīgi, saglabājam ziņojumu
    if ($is_valid) {
        try {
            // Izveidojam savienojumu ar datubāzi
            $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Sagatvojam un izpildām SQL vaicājumu
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address, created_at) 
                                   VALUES (:name, :email, :subject, :message, :ip, NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $stmt->execute();
            
            // Nosūtām paziņojumu administratoram
            $admin_email = "admin@bookswap.example.com";
            $admin_subject = "Jauns kontakta ziņojums: " . $subject;
            $admin_message = "Saņemts jauns ziņojums no kontaktu formas:\n\n";
            $admin_message .= "Vārds: " . $name . "\n";
            $admin_message .= "E-pasts: " . $email . "\n";
            $admin_message .= "Temats: " . $subject . "\n";
            $admin_message .= "Ziņojums: " . $message . "\n";
            $admin_message .= "IP adrese: " . $_SERVER['REMOTE_ADDR'] . "\n";
            $admin_message .= "Datums: " . date('Y-m-d H:i:s');
            
            $headers = "From: " . $email;
            
            // Reālā vidē šeit būtu jāizmanto e-pasta sūtīšanas bibliotēka, piemēram, PHPMailer
            // Šeit vienkāršības labad izmantojam mail() funkciju
            // mail($admin_email, $admin_subject, $admin_message, $headers);
            
            // Iestatām veiksmīga ziņojuma tekstu
            $success_message = "Paldies par ziņojumu! Mēs sazināsimies ar jums, cik drīz vien iespējams.";
            
            // Notīrām formas laukus
            $name = $email = $subject = $message = "";
            
        } catch(PDOException $e) {
            // Datubāzes kļūda
            $error_message = "Kļūda, nosūtot ziņojumu: " . $e->getMessage();
            error_log("Datubāzes kļūda kontaktu formā: " . $e->getMessage());
        }
        
        // Aizveram datubāzes savienojumu
        $conn = null;
    } else {
        // Ja ir validācijas kļūdas, saglabājam tās JSON formātā
        $error_message = json_encode($errors);
    }
}
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
<body>
  <header class="navigation">
    <div class="container">
      <div class="nav-wrapper">
        <!-- Logo & Brand -->
        <a href="index.html" class="brand">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
          <h1 class="brand-name">BookSwap</h1>
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
          <a href="browse.html" class="nav-link">Pārlūkot grāmatas</a>
          <a href="how-it-works.html" class="nav-link">Kā tas darbojas</a>
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
        <a href="browse.html" class="mobile-nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.html" class="mobile-nav-link">Kā tas darbojas</a>
        <div class="mobile-actions">
          <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
          <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
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
        
        <?php if (!empty($success_message)): ?>
            <div id="formSuccess" class="form-success active">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form id="contactForm" method="post" action="contact-us.php">
          <div class="form-group required">
            <label for="name" class="form-label">Vārds</label>
            <input type="text" id="name" name="name" class="form-input" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div id="nameError" class="form-error active"><?php echo $errors['name']; ?></div>
            <?php else: ?>
                <div id="nameError" class="form-error">Lūdzu, ievadiet savu vārdu</div>
            <?php endif; ?>
          </div>
          
          <div class="form-group required">
            <label for="email" class="form-label">E-pasts Address</label>
            <input type="email" id="email" name="email" class="form-input" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div id="emailError" class="form-error active"><?php echo $errors['email']; ?></div>
            <?php else: ?>
                <div id="emailError" class="form-error">Lūdzu, ievadiet derīgu e-pasta adresi</div>
            <?php endif; ?>
          </div>
          
          <div class="form-group">
            <label for="subject" class="form-label">Temats</label>
            <input type="text" id="subject" name="subject" class="form-input" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
          </div>
          
          <div class="form-group required">
            <label for="message" class="form-label">Ziņa</label>
            <textarea id="message" name="message" class="form-textarea" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
            <?php if (isset($errors['message'])): ?>
                <div id="messageError" class="form-error active"><?php echo $errors['message']; ?></div>
            <?php else: ?>
                <div id="messageError" class="form-error">Lūdzu, ievadiet savu ziņu</div>
            <?php endif; ?>
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
          <a href="index.html" class="brand">
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
            <li><a href="browse.html">Pārlūkot grāmatas</a></li>
            <li><a href="how-it-works.html">Kā tas darbojas</a></li>
            <li><a href="signup.php">Pievienojies BookSwap</a></li>
            <li><a href="login.php">Pieslēgties</a></li>
          </ul>
        </div>
        
        <!-- Palīdzība un atbalsts -->
        <div class="footer-links">
          <h3 class="footer-title">Palīdzība un atbalsts</h3>
          <ul>
            <li><a href="faq.html">BUJ</a></li>
            <li><a href="contact-us.php">Sazinies ar mums</a></li>
            <li><a href="safety-tips.html">Drošības padomi</a></li>
            <li><a href="report-issue.php">Ziņot par problēmu</a></li>
          </ul>
        </div>
        
        <!-- Juridiska informācija -->
        <div class="footer-links">
          <h3 class="footer-title">Juridiska informācija</h3>
          <ul>
            <li><a href="terms.html">Lietošanas noteikumi</a></li>
            <li><a href="privacy-policy.html">Privātuma politika</a></li>
            <li><a href="cookies.html">Sīkdatņu politika</a></li>
            <li><a href="gdpr.html">VDAR</a></li>
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
</body>
</html>
