<?php
// Подключаем файл проверки сессии
require_once 'session_check.php';

// Перенаправляем авторизованных пользователей на страницу профиля
redirectIfLoggedIn();

// Подключаем файл с подключением к БД
require_once 'connect_db.php';

// Инициализируем переменные
$firstName = $lastName = $email = $password = $confirmPassword = '';
$errors = [];
$success = false;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $agreeTerms = isset($_POST['agreeTerms']);
    
    // Валидация данных
    if (empty($firstName)) {
        $errors[] = 'Vārds ir obligāts lauks';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Uzvārds ir obligāts lauks';
    }
    
    if (empty($email)) {
        $errors[] = 'E-pasts ir obligāts lauks';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Lūdzu, ievadiet derīgu e-pasta adresi';
    }
    
    if (empty($password)) {
        $errors[] = 'Parole ir obligāts lauks';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Parolei jābūt vismaz 8 rakstzīmēm';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Paroles nesakrīt';
    }
    
    if (!$agreeTerms) {
        $errors[] = 'Jums jāpiekrīt lietošanas noteikumiem un privātuma politikai';
    }
    
    // Проверяем, не существует ли пользователь с таким email
    if (empty($errors)) {
        $stmt = $savienojums->prepare("SELECT LietotajsID FROM bookswap_users WHERE E_pasts = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Lietotājs ar šādu e-pasta adresi jau eksistē';
        }
        $stmt->close();
    }
    
    // Если нет ошибок, регистрируем пользователя
    if (empty($errors)) {
        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Полное имя пользователя
        $fullName = $firstName . ' ' . $lastName;
        
        // Текущая дата и время
        $registrationDate = date('Y-m-d H:i:s');
        
        // Роль по умолчанию
        $role = 'Registrēts';
        
        // Вставляем данные в БД
        $stmt = $savienojums->prepare("INSERT INTO bookswap_users (Lietotajvards, E_pasts, Parole, RegistracijasDatums, Loma) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullName, $email, $hashedPassword, $registrationDate, $role);
        
        if ($stmt->execute()) {
            // Получаем ID нового пользователя
            $userId = $savienojums->insert_id;
            
            // Создаем сессию
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['is_logged_in'] = true;
            
            // Перенаправляем на страницу профиля
            header('Location: profile.php');
            exit();
        } else {
            $errors[] = 'Reģistrācijas laikā radās kļūda: ' . $savienojums->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reģistrācija | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="auth.css">
</head>
<body>
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
          
        </div>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
      </div>
      
      <!-- Mobile Menu (Hidden by default) -->
      <div class="mobile-menu" id="mobileMenu">
        <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
        <div class="mobile-actions">
          <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
        </div>
      </div>
    </div>
  </header>

  <main>
    <section class="auth-section">
      <div class="container">
        <div class="auth-container">
          <h1 class="auth-title">Izveido savu kontu</h1>
          <p class="auth-subtitle">Pievienojies mūsu grāmatu mīļotāju kopienai un sāc dalīties ar savu kolekciju jau šodien.</p>
          
          <form id="signupForm" class="auth-form" method="POST" action="signup.php">
            <?php if (!empty($errors)): ?>
              <div class="auth-error active">
                <?php foreach ($errors as $error): ?>
                  <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">Vārds</label>
                <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Ievadi savu vārdu" required value="<?php echo htmlspecialchars($firstName); ?>">
              </div>
              
              <div class="form-group">
                <label for="lastName">Uzvārds</label>
                <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Ievadi savu uzvārdu" required value="<?php echo htmlspecialchars($lastName); ?>">
              </div>
            </div>
            
            <div class="form-group">
              <label for="email">E-pasts</label>
              <input type="email" id="email" name="email" class="form-input" placeholder="Ievadi savu e-pastu" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
              <label for="password">Parole</label>
              <div class="password-input">
                <input type="password" id="password" name="password" class="form-input" placeholder="Izveido paroli" required>
                <button type="button" class="toggle-password">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
              <div class="password-strength-meter">
                <div class="strength-bar"></div>
              </div>
              <p class="password-requirements">Parolei jābūt vismaz 8 rakstzīmēm ar burtu, ciparu un simbolu kombināciju</p>
            </div>
            
            <div class="form-group">
              <label for="confirmPassword">Apstiprini paroli</label>
              <div class="password-input">
                <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Apstiprini savu paroli" required>
                <button type="button" class="toggle-password">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            
            <div class="form-group terms">
              <input type="checkbox" id="agreeTerms" name="agreeTerms" required <?php echo isset($_POST['agreeTerms']) ? 'checked' : ''; ?>>
              <label for="agreeTerms">Es piekrītu <a href="terms.php" class="auth-link">Lietošanas noteikumiem</a> un <a href="privacy-policy.php" class="auth-link">Privātuma politikai</a></label>
            </div>
            
            <button type="submit" class="btn-primary btn-full">Izveidot kontu</button>
            
            <div class="auth-divider">
              <span>vai</span>
            </div>
            
            <button type="button" class="btn-outline btn-full social-login">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="21.17" y1="8" x2="12" y2="8"></line><line x1="3.95" y1="6.06" x2="8.54" y2="14"></line><line x1="10.88" y1="21.94" x2="15.46" y2="14"></line></svg>
              Reģistrēties ar Google
            </button>
            
            <p class="auth-footer">
              Jau ir konts? <a href="login.php" class="auth-link">Pieslēgties</a>
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

  <script src="script.js"></script>
  <script>
    // Только для визуальных эффектов (показ/скрытие пароля и индикатор силы пароля)
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle password visibility
      document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
          const passwordInput = this.parentElement.querySelector('input');
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          
          // Change eye icon
          const eyeIcon = this.querySelector('svg');
          if (type === 'text') {
            eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
          } else {
            eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
          }
        });
      });
      
      // Password strength meter
      const passwordInput = document.getElementById('password');
      const strengthBar = document.querySelector('.strength-bar');
      
      if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
          const password = this.value;
          let strength = 0;
          
          if (password.length > 6) strength++;
          if (password.length > 10) strength++;
          if (/[0-9]/.test(password)) strength++;
          if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
          if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
          
          strength = Math.min(4, strength);
          
          // Update the strength bar
          strengthBar.style.width = `${(strength / 4) * 100}%`;
          
          // Change color based on strength
          if (strength === 0) {
            strengthBar.style.backgroundColor = '#ef4444'; // Red
          } else if (strength === 1) {
            strengthBar.style.backgroundColor = '#f97316'; // Orange
          } else if (strength === 2) {
            strengthBar.style.backgroundColor = '#eab308'; // Yellow
          } else if (strength === 3) {
            strengthBar.style.backgroundColor = '#84cc16'; // Light green
          } else {
            strengthBar.style.backgroundColor = '#22c55e'; // Green
          }
        });
      }
    });
  </script>
</body>
</html>
