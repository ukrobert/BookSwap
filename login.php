<?php
// login.php

/**
 * Lietotāja autentifikācijas skripts BookSwap platformai
 * 
 * Šis fails apstrādā lietotāja pieteikšanās procesu, pārbaudot ievadītos datus
 * un nodrošinot drošu autentifikāciju.
 * 
 * @author Roberto Šķiņķis
 * @version 1.0
 */

// Sesijas inicializācija
session_start();

// Datubāzes savienojuma konfigurācija
// Šeit definējam nepieciešamos parametrus, lai izveidotu savienojumu ar datubāzi
$db_host = "localhost";     // Datubāzes servera adrese
$db_user = "bookswap_user"; // Datubāzes lietotājvārds
$db_pass = "secure_password"; // Datubāzes parole
$db_name = "bookswap_db";   // Datubāzes nosaukums

// Kļūdu ziņojumu mainīgais
$error_message = "";

// Pārbaudām, vai forma ir iesniegta
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Iegūstam un attīrām ievades datus
    // Izmantojam filter_input, lai nodrošinātu drošu ievades apstrādi
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Paroli nefiltrējam, jo tā tiks pārbaudīta ar password_verify
    $remember_me = isset($_POST['rememberMe']) ? true : false;
    
    // Pārbaudām, vai e-pasts ir derīgs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Lūdzu, ievadiet derīgu e-pasta adresi.";
    } else {
        try {
            // Izveidojam savienojumu ar datubāzi
            $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
            
            // Iestatām PDO kļūdu režīmu uz izņēmumiem
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Sagatavojam SQL vaicājumu, lai atrastu lietotāju pēc e-pasta
            $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, is_verified FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            // Pārbaudām, vai lietotājs eksistē
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Pārbaudām, vai lietotāja konts ir verificēts
                if ($user['is_verified'] == 0) {
                    $error_message = "Jūsu konts nav apstiprināts. Lūdzu, pārbaudiet savu e-pastu un apstipriniet reģistrāciju.";
                } 
                // Pārbaudām paroli
                else if (password_verify($password, $user['password'])) {
                    // Parole ir pareiza, izveidojam sesiju
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['logged_in'] = true;
                    
                    // Ja lietotājs izvēlējās "Atceries mani", iestatām sīkdatni
                    if ($remember_me) {
                        // Ģenerējam drošu token
                        $token = bin2hex(random_bytes(32));
                        
                        // Saglabājam token datubāzē
                        $stmt = $conn->prepare("UPDATE users SET remember_token = :token, token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = :user_id");
                        $stmt->bindParam(':token', $token);
                        $stmt->bindParam(':user_id', $user['id']);
                        $stmt->execute();
                        
                        // Iestatām sīkdatni ar 30 dienu derīguma termiņu
                        setcookie('remember_token', $token, time() + (86400 * 30), "/", "", true, true);
                    }
                    
                    // Reģistrējam pieteikšanās notikumu
                    $stmt = $conn->prepare("INSERT INTO login_history (user_id, login_time, ip_address) VALUES (:user_id, NOW(), :ip)");
                    $stmt->bindParam(':user_id', $user['id']);
                    $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                    $stmt->execute();
                    
                    // Novirzām lietotāju uz profila lapu
                    header("Location: profile.html");
                    exit();
                } else {
                    // Nepareiza parole
                    $error_message = "Nepareizs e-pasts vai parole.";
                }
            } else {
                // Lietotājs nav atrasts
                $error_message = "Nepareizs e-pasts vai parole.";
            }
        } catch(PDOException $e) {
            // Datubāzes kļūda
            $error_message = "Pieslēgšanās kļūda: " . $e->getMessage();
            
            // Reālā vidē kļūdas ziņojumu nevajadzētu rādīt lietotājam, bet gan ierakstīt kļūdu žurnālā
            error_log("Datubāzes kļūda: " . $e->getMessage());
        }
        
        // Aizveram datubāzes savienojumu
        $conn = null;
    }
}

// Pārbaudām, vai lietotājs jau ir pieteicies
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: profile.html");
    exit();
}

// Pārbaudām, vai eksistē "Atceries mani" sīkdatne
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        // Izveidojam savienojumu ar datubāzi
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Meklējam lietotāju ar šo token un pārbaudām derīguma termiņu
        $stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE remember_token = :token AND token_expires > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Izveidojam sesiju
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            
            // Atjaunojam token derīguma termiņu
            $stmt = $conn->prepare("UPDATE users SET token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->execute();
            
            // Novirzām lietotāju uz profila lapu
            header("Location: profile.html");
            exit();
        }
    } catch(PDOException $e) {
        error_log("Datubāzes kļūda: " . $e->getMessage());
    }
    
    // Aizveram datubāzes savienojumu
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="auth.css">
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
          
        </div>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
      </div>
      
      <!-- Mobile Menu (Hidden by default) -->
      <div class="mobile-menu" id="mobileMenu">
        <a href="browse.html" class="nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.html" class="nav-link">Kā tas darbojas</a>
        
        <div class="mobile-actions">
          
        </div>
      </div>
    </div>
  </header>

  <main>
    <section class="auth-section">
      <div class="container">
        <div class="auth-container">
          <h1 class="auth-title">Pieslēgties BookSwap</h1>
          <p class="auth-subtitle">Laipni lūdzam atpakaļ! Lūdzu, ievadiet savus datus, lai piekļūtu savam kontam.</p>
          
          <form id="loginForm" class="auth-form" method="post" action="login.php">
            <?php if (!empty($error_message)): ?>
                <div id="loginError" class="auth-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
              <label for="email">E-pasts</label>
              <input type="email" id="email" name="email" class="form-input" placeholder="Ievadiet savu e-pasta adresi" required>
            </div>
            
            <div class="form-group">
              <label for="password">Parole</label>
              <div class="password-input">
                <input type="password" id="password" name="password" class="form-input" placeholder="Ievadiet savu paroli" required>
                <button type="button" class="toggle-password">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            
            <div class="form-options">
              <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Atceries mani</label>
              </div>
              <a href="forgot-password.php" class="forgot-password">Aizmirsāt paroli?</a>
            </div>
            
            <button type="submit" class="btn-primary btn-full">Piesakieties</button>
            
            <div class="auth-divider">
              <span>or</span>
            </div>
            
            <button type="button" class="btn-outline btn-full social-login">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="21.17" y1="8" x2="12" y2="8"></line><line x1="3.95" y1="6.06" x2="8.54" y2="14"></line><line x1="10.88" y1="21.94" x2="15.46" y2="14"></line></svg>
              Turpināt ar Google
            </button>
            
            <p class="auth-footer">
              Jums nav konta? <a href="signup.php" class="auth-link">Sign up</a>
            </p>
          </form>
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
            <li><a href="signup.php">Pievienoties BookSwap</a></li>
            <li><a href="login.php">Pieslēgties</a></li>
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

  <script src="script.js"></script>
  <script src="auth.js"></script>
</body>
</html>
