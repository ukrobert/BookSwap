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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['rememberMe']);
    
    if (empty($email)) $errors[] = 'E-pasts ir obligāts lauks';
    if (empty($password)) $errors[] = 'Parole ir obligāts lauks';
    
    if (empty($errors)) {
        // ИЗМЕНЕНО: Добавлено поле Loma в SELECT
        $stmt = $savienojums->prepare("SELECT LietotajsID, Lietotajvards, E_pasts, Parole, ProfilaAttels, Loma FROM bookswap_users WHERE E_pasts = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['Parole'])) {
                $_SESSION['user_id'] = $user['LietotajsID'];
                $_SESSION['user_name'] = $user['Lietotajvards'];
                $_SESSION['user_email'] = $user['E_pasts'];
                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_profile_photo'] = $user['ProfilaAttels'];
                $_SESSION['user_role'] = $user['Loma']; // ИЗМЕНЕНО: Сохраняем роль пользователя

                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('bookswap_remember', $user['LietotajsID'] . ':' . $token, time() + 60*60*24*30, '/', '', false, true);
                    // Здесь может быть логика сохранения токена в БД
                }
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
                <a href="index.php" class="brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                    <h1 class="brand-name">BookSwap</h1>
                </a>
                <nav class="desktop-nav">
                    <a href="browse.php" class="nav-link">Pārlūkot grāmatas</a>
                    <a href="how-it-works.php" class="nav-link">Kā tas darbojas</a>
                </nav>
                <div class="desktop-actions">
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $profilePicPath = $_SESSION['user_profile_photo'] ?? '';
                        $userNameInitial = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath) && (filter_var($profilePicPath, FILTER_VALIDATE_URL) || file_exists($profilePicPath))): ?>
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
                <button class="mobile-menu-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
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
            <div class="auth-divider"><span>or</span></div>
            <button type="button" class="btn-outline btn-full social-login">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><line x1="21.17" y1="8" x2="12" y2="8" fill="none" stroke="currentColor" stroke-width="2"/><line x1="3.95" y1="6.06" x2="8.54" y2="14" fill="none" stroke="currentColor" stroke-width="2"/><line x1="10.88" y1="21.94" x2="15.46" y2="14" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              Turpināt ar Google
            </button>
            <p class="auth-footer">Jums nav konta? <a href="signup.php" class="auth-link">Sign up</a></p>
          </form>
        </div>
      </div>
    </section>
  </main>
  
  <footer class="footer"> <!-- Footer code as before --> </footer>
  <script src="script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
          const passwordInput = this.parentElement.querySelector('input');
          const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
          passwordInput.setAttribute('type', type);
          const eyeIcon = this.querySelector('svg');
          if (type === 'text') {
            eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
          } else {
            eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
          }
        });
      });
       // Auto-hide error message after 5 seconds
        const authError = document.querySelector('.auth-error.active');
        if (authError) {
            setTimeout(() => {
                authError.classList.remove('active');
            }, 5000);
        }
        const currentYearSpan = document.getElementById('currentYear');
        if(currentYearSpan) currentYearSpan.textContent = new Date().getFullYear();
    });
  </script>
</body>
</html>