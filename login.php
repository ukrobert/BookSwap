<?php
// Подключаем файл проверки сессии
require_once 'session_check.php';

// Перенаправляем авторизованных пользователей на страницу профиля
redirectIfLoggedIn();

// Подключаем файл с подключением к БД
require_once 'connect_db.php';

// Инициализируем переменные
$email = $password = '';
$errors = [];
$rememberMe = false;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['rememberMe']);
    
    // Валидация данных
    if (empty($email)) {
        $errors[] = 'E-pasts ir obligāts lauks';
    }
    
    if (empty($password)) {
        $errors[] = 'Parole ir obligāts lauks';
    }
    
    // Если нет ошибок, проверяем учетные данные
    if (empty($errors)) {
        // Ищем пользователя по email
        $stmt = $savienojums->prepare("SELECT LietotajsID, Lietotajvards, E_pasts, Parole, ProfilaAttels FROM bookswap_users WHERE E_pasts = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Проверяем пароль
            if (password_verify($password, $user['Parole'])) {
                // Создаем сессию
                $_SESSION['user_id'] = $user['LietotajsID'];
                $_SESSION['user_name'] = $user['Lietotajvards'];
                $_SESSION['user_email'] = $user['E_pasts'];
                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_profile_photo'] = $user['ProfilaAttels']; // Сохраняем путь к фото профиля
                
                // Если выбрано "запомнить меня", устанавливаем cookie
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32)); // Генерируем случайный токен
                    
                    // Хешируем токен для хранения в БД
                    // $hashedToken = password_hash($token, PASSWORD_DEFAULT);
                    
                    // Сохраняем токен в БД (предполагается, что есть таблица для токенов)
                    // В данном случае, для простоты, мы просто установим cookie
                    
                    // Устанавливаем cookie на 30 дней
                    // Пример сохранения токена в БД (вам нужно будет адаптировать это)
                    // $expiryDate = date('Y-m-d H:i:s', time() + 60*60*24*30);
                    // $tokenStmt = $savienojums->prepare("INSERT INTO bookswap_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    // $tokenStmt->bind_param("iss", $user['LietotajsID'], $hashedToken, $expiryDate);
                    // $tokenStmt->execute();
                    // $tokenStmt->close();

                    setcookie('bookswap_remember', $user['LietotajsID'] . ':' . $token, time() + 60*60*24*30, '/', '', false, true);
                }
                
                // Перенаправляем на страницу профиля
                header('Location: profile.php');
                exit();
            } else {
                $errors[] = 'Nepareizs e-pasts vai parole';
            }
        } else {
            $errors[] = 'Nepareizs e-pasts vai parole';
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
  <title>Login | BookSwap</title>
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
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $profilePicPath = $_SESSION['user_profile_photo'] ?? '';
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath) && file_exists($profilePicPath)): ?>
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
    <section class="auth-section">
      <div class="container">
        <div class="auth-container">
          <h1 class="auth-title">Pieslēgties BookSwap</h1>
          <p class="auth-subtitle">Laipni lūdzam atpakaļ! Lūdzu, ievadiet savus datus, lai piekļūtu savam kontam.</p>
          
          <form id="loginForm" class="auth-form" method="POST" action="login.php">
            <?php if (!empty($errors)): ?>
              <div class="auth-error active">
                <?php foreach ($errors as $error): ?>
                  <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            
            <div class="form-group">
              <label for="email">E-pasts</label>
              <input type="email" id="email" name="email" class="form-input" placeholder="Ievadiet savu e-pasta adresi" required value="<?php echo htmlspecialchars($email); ?>">
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
                <input type="checkbox" id="rememberMe" name="rememberMe" <?php echo $rememberMe ? 'checked' : ''; ?>>
                <label for="rememberMe">Atceries mani</label>
              </div>
              <a href="#" class="forgot-password">Aizmirsāt paroli?</a>
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
        <p>© <span id="currentYear"></span> BookSwap. Visas tiesības aizsargātas.</p>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
  <script>
    // Только для визуальных эффектов (показ/скрытие пароля)
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
    });
  </script>
</body>
</html>