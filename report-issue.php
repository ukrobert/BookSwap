<?php
require_once 'session_check.php'; 
require_once 'connect_db.php';

$errors_report = [];
$success_report = '';

// Пользователь должен быть залогинен, чтобы отправить отчет
if (!isLoggedIn()) {
    // Можно перенаправить на логин или просто не дать отправить форму
    // Для простоты, если JS не справится, форма просто не будет работать без user_id
    // В идеале, JS должен скрывать/блокировать форму, если не залогинен
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $current_user_id_report = $_SESSION['user_id'];

    $issueType = trim($_POST['issueType'] ?? '');
    $relatedUserText = trim($_POST['relatedUser'] ?? ''); // Текстовое поле для имени пользователя
    $relatedBookText = trim($_POST['relatedBook'] ?? ''); // Текстовое поле для названия книги
    $issueDescription = trim($_POST['issueDescription'] ?? '');
    $issueDate = trim($_POST['issueDate'] ?? '');
    // $attachments = $_FILES['attachments'] ?? null; // Обработку файлов пока опустим для простоты
    $contactMethod = trim($_POST['contactMethod'] ?? '');
    $emailAddress = trim($_POST['emailAddress'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $termsAgreement = isset($_POST['termsAgreement']);

    // Validation
    if (empty($issueType)) $errors_report[] = 'Lūdzu, izvēlieties problēmas veidu.';
    if (empty($issueDescription)) $errors_report[] = 'Lūdzu, aprakstiet problēmu.';
    if (empty($issueDate)) $errors_report[] = 'Lūdzu, norādiet datumu.';
    else {
        // Простая валидация даты
        $d = DateTime::createFromFormat('Y-m-d', $issueDate);
        if (!$d || $d->format('Y-m-d') !== $issueDate) {
            $errors_report[] = 'Lūdzu, norādiet derīgu datumu.';
        }
    }

    if (empty($contactMethod)) $errors_report[] = 'Lūdzu, izvēlieties saziņas metodi.';
    else {
        if ($contactMethod === 'email' && (empty($emailAddress) || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL))) {
            $errors_report[] = 'Lūdzu, ievadiet derīgu e-pasta adresi.';
        }
        if ($contactMethod === 'phone' && empty($phoneNumber)) { // Упрощенная валидация телефона
            $errors_report[] = 'Lūdzu, ievadiet telefona numuru.';
        }
    }
    if (!$termsAgreement) $errors_report[] = 'Jums jāpiekrīt apgalvojumam.';

    // Если нет ошибок, сохраняем отчет в БД
    if (empty($errors_report)) {
        $contact_details = '';
        if ($contactMethod === 'email') $contact_details = "E-pasts: " . $emailAddress;
        elseif ($contactMethod === 'phone') $contact_details = "Tālrunis: " . $phoneNumber;

        // Для relatedUser и relatedBook мы сохраняем текст, т.к. ID может быть неизвестен репортеру
        // Можно добавить логику поиска ID по имени, если нужно строгое связывание

        $stmt_report = $savienojums->prepare("
            INSERT INTO bookswap_issue_reports 
            (reporter_user_id, issue_type, description, issue_date, related_user_text, related_book_text, contact_details, report_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Jauns')
        ");

        if ($stmt_report) {
            $stmt_report->bind_param("issssss", 
                $current_user_id_report, 
                $issueType, 
                $issueDescription, 
                $issueDate, 
                $relatedUserText, 
                $relatedBookText, 
                $contact_details
            );

            if ($stmt_report->execute()) {
                $success_report = 'Jūsu ziņojums ir veiksmīgi iesniegts. Mēs to pārskatīsim un sazināsimies ar jums 24-48 stundu laikā.';
                // Очистка формы (лучше сделать через JS после успешной отправки AJAX, если бы она была)
                $_POST = []; // Сбрасываем POST для предотвращения повторной отправки данных в поля
            } else {
                $errors_report[] = "Kļūda iesniedzot ziņojumu: " . $stmt_report->error;
            }
            $stmt_report->close();
        } else {
            $errors_report[] = "Kļūda sagatavojot vaicājumu: " . $savienojums->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ziņot par problēmu - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <!-- report-issue.js будет обрабатывать только клиентскую часть, как показ/скрытие полей -->
</head>
<body class="paper-texture" data-current-user-id="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_id']) : '0'; ?>">
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
                        $profilePicPath_h = $_SESSION['user_profile_photo'] ?? '';
                        $userNameInitial_h = !empty($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8')) : 'U';
                        ?>
                        <div class="profile-button-header-wrapper">
                            <a href="profile.php" class="profile-button-header" aria-label="User Profile">
                                <div class="profile-button-photo-header">
                                    <?php if (!empty($profilePicPath_h) && (filter_var($profilePicPath_h, FILTER_VALIDATE_URL) || file_exists($profilePicPath_h))): ?>
                                        <img src="<?php echo htmlspecialchars($profilePicPath_h); ?>?t=<?php echo time(); ?>" alt="Profils">
                                    <?php else: ?>
                                        <div class="profile-button-placeholder-header">
                                            <?php echo htmlspecialchars($userNameInitial_h); ?>
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
    <section class="page-header">
      <div class="container">
        <h1>Ziņot par problēmu</h1>
        <p>Informējiet mūs par jebkurām problēmām, ar kurām esat saskārušies, izmantojot BookSwap</p>
      </div>
    </section>

    <section class="report-section">
      <div class="container">
        <?php if (!empty($success_report)): ?>
            <div class="form-success active" style="margin-bottom: 20px; text-align:center;"><?php echo htmlspecialchars($success_report); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors_report)): ?>
            <div class="auth-error active" style="margin-bottom: 20px;">
                <?php foreach($errors_report as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="report-container">
          <div class="report-info">
            <h2>Kā ziņot par problēmu</h2>
            <p>Mēs ņemam vērā visus ziņojumus un cenšamies tos ātri atrisināt. Lūdzu, sniedziet pēc iespējas vairāk detaļu, lai palīdzētu mums efektīvi atrisināt jūsu problēmu.</p>
            <div class="issue-types">
              <h3>Izplatītas problēmas, par kurām varat ziņot:</h3>
              <ul class="issue-list">
                <li><strong>Grāmatas stāvokļa nepareiza attēlošana</strong><span>Kad grāmatas stāvoklis neatbilst tās aprakstam</span></li>
                <li><strong>Lietotāju uzvedības bažas</strong><span>Nepiemērota uzvedība no citu lietotāju puses</span></li>
                <li><strong>Tehniskās problēmas</strong><span>Problēmas ar vietnes vai lietotnes funkcionalitāti</span></li>
                <li><strong>Neizdotās maiņas</strong><span>Problēmas ar grāmatu maiņām, kas netika pabeigtas pareizi</span></li>
                <li><strong>Kontu problēmas</strong><span>Problēmas ar jūsu lietotāja kontu, pieteikšanos vai iestatījumiem</span></li>
              </ul>
            </div>
            <div class="urgent-notice">
              <svg width="24" height="24" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <div>
                <h4>Steidzamu drošības bažu gadījumā:</h4>
                <p>Ja jums ir tūlītējas drošības bažas, lūdzu, vispirms sazinieties ar vietējām varas iestādēm, tad ziņojiet par incidentu mums.</p>
              </div>
            </div>
          </div>
          <div class="report-form-container">
            <?php if (!isLoggedIn()): ?>
                <p style="text-align:center; font-weight:bold; color:var(--color-burgundy);">Lai iesniegtu ziņojumu, lūdzu, <a href="login.php?redirect=report-issue.php" class="auth-link">pieslēdzieties</a>.</p>
            <?php else: ?>
            <form id="reportForm" class="report-form" method="POST" action="report-issue.php" enctype="multipart/form-data">
              <div class="form-group">
                <label for="issueType">Problēmas veids*</label>
                <select id="issueType" name="issueType" class="form-select" required>
                  <option value="" disabled <?php echo empty($_POST['issueType']) ? 'selected' : '';?>>Izvēlieties problēmas veidu</option>
                  <option value="book-condition" <?php echo ($_POST['issueType'] ?? '') === 'book-condition' ? 'selected' : '';?>>Grāmatas stāvokļa nepareiza attēlošana</option>
                  <option value="user-conduct" <?php echo ($_POST['issueType'] ?? '') === 'user-conduct' ? 'selected' : '';?>>Lietotāju uzvedības bažas</option>
                  <option value="technical" <?php echo ($_POST['issueType'] ?? '') === 'technical' ? 'selected' : '';?>>Tehniskās problēmas</option>
                  <option value="failed-trade" <?php echo ($_POST['issueType'] ?? '') === 'failed-trade' ? 'selected' : '';?>>Neizdotā maiņa</option>
                  <option value="account" <?php echo ($_POST['issueType'] ?? '') === 'account' ? 'selected' : '';?>>Kontu problēmas</option>
                  <option value="other" <?php echo ($_POST['issueType'] ?? '') === 'other' ? 'selected' : '';?>>Cits</option>
                </select>
                <div id="issueTypeError" class="form-error"></div>
              </div>
              <div class="form-group user-related-field hidden">
                <label for="relatedUser">Saistītais lietotājs (ja attiecināms)</label>
                <input type="text" id="relatedUser" name="relatedUser" class="form-input" placeholder="Iesaistītā lietotāja vārds vai ID" value="<?php echo htmlspecialchars($_POST['relatedUser'] ?? ''); ?>">
              </div>
              <div class="form-group book-related-field hidden">
                <label for="relatedBook">Grāmatas nosaukums vai ID (ja attiecināms)</label>
                <input type="text" id="relatedBook" name="relatedBook" class="form-input" placeholder="Iesaistītās grāmatas nosaukums vai ID" value="<?php echo htmlspecialchars($_POST['relatedBook'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="issueDescription">Problēmas apraksts*</label>
                <textarea id="issueDescription" name="issueDescription" class="form-textarea" rows="5" placeholder="Lūdzu, sniedziet detalizētu informāciju par notikušo" required><?php echo htmlspecialchars($_POST['issueDescription'] ?? ''); ?></textarea>
                <div id="descriptionError" class="form-error"></div>
              </div>
              <div class="form-group">
                <label for="issueDate">Kad tas notika?*</label>
                <input type="date" id="issueDate" name="issueDate" class="form-input" required value="<?php echo htmlspecialchars($_POST['issueDate'] ?? ''); ?>" max="<?php echo date('Y-m-d'); ?>">
                <div id="dateError" class="form-error"></div>
              </div>
              <!-- <div class="form-group">
                <label for="attachments">Pielikumi (pēc izvēles)</label>
                <input type="file" id="attachments" name="attachments[]" class="form-file" multiple>
                <small class="form-help">Maks. 3 faili, 5MB katrs</small>
              </div> -->
              <div class="form-group">
                <label for="contactMethod">Vēlamā saziņas metode*</label>
                <select id="contactMethod" name="contactMethod" class="form-select" required>
                  <option value="" disabled <?php echo empty($_POST['contactMethod']) ? 'selected' : '';?>>Izvēlieties</option>
                  <option value="email" <?php echo ($_POST['contactMethod'] ?? '') === 'email' ? 'selected' : '';?>>E-pasts</option>
                  <option value="phone" <?php echo ($_POST['contactMethod'] ?? '') === 'phone' ? 'selected' : '';?>>Telefons</option>
                </select>
                <div id="contactMethodError" class="form-error"></div>
              </div>
              <div id="emailField" class="form-group contact-field <?php echo ($_POST['contactMethod'] ?? '') !== 'email' ? 'hidden' : '';?>">
                <label for="emailAddress">E-pasta adrese*</label>
                <input type="email" id="emailAddress" name="emailAddress" class="form-input" placeholder="Jūsu e-pasta adrese" value="<?php echo htmlspecialchars($_POST['emailAddress'] ?? $_SESSION['user_email'] ?? ''); ?>">
                <div id="emailError" class="form-error"></div>
              </div>
              <div id="phoneField" class="form-group contact-field <?php echo ($_POST['contactMethod'] ?? '') !== 'phone' ? 'hidden' : '';?>">
                <label for="phoneNumber">Telefona numurs*</label>
                <input type="tel" id="phoneNumber" name="phoneNumber" class="form-input" placeholder="Jūsu telefona numurs" value="<?php echo htmlspecialchars($_POST['phoneNumber'] ?? ''); ?>">
                <div id="phoneError" class="form-error"></div>
              </div>
              <div class="form-group">
                <div class="checkbox-group">
                  <input type="checkbox" id="termsAgreement" name="termsAgreement" required <?php echo isset($_POST['termsAgreement']) ? 'checked' : '';?>>
                  <label for="termsAgreement">Es saprotu, ka nepatiesi ziņojumi var novest pie konta apturēšanas*</label>
                </div>
                <div id="termsError" class="form-error"></div>
              </div>
              <div class="form-submit">
                <button type="submit" class="btn btn-primary btn-block">Iesniegt ziņojumu</button>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="faq-section bg-bookshelf-paper"> <!-- FAQ section as before --> </section>
  </main>
  <footer class="footer">
        <div class="container">
          <div class="footer-grid">
            <div class="footer-brand">
              <a href="index.php" class="brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="brand-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <h2 class="brand-name">BookSwap</h2>
              </a>
              <p>Sazinieties ar citiem lasītājiem un apmainieties ar grāmatām, kuras mīlat.</p>
              <div class="social-links">
                 <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
                <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
                <a href="#" class="social-link"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
              </div>
            </div>
            <div class="footer-links">
              <h3 class="footer-title">Ātrās saites</h3>
              <ul>
                <li><a href="browse.php">Pārlūkot grāmatas</a></li>
                <li><a href="how-it-works.php">Kā tas darbojas</a></li>
                 <?php if (!isLoggedIn()): ?>
                <li><a href="signup.php">Pievienoties BookSwap</a></li>
                <li><a href="login.php">Pieslēgties</a></li>
                 <?php endif; ?>
              </ul>
            </div>
            <div class="footer-links">
              <h3 class="footer-title">Palīdzība un atbalsts</h3>
              <ul>
                <li><a href="faq.php">BUJ</a></li>
                <li><a href="contact-us.php">Sazināties ar mums</a></li>
                <li><a href="safety-tips.php">Drošības padomi</a></li>
                <li><a href="report-issue.php">Ziņot par problēmu</a></li>
              </ul>
            </div>
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
  <!-- Chat Widget and its JS/CSS links (as before, if needed on this page) -->
  <script src="report-issue.js" defer></script>
  <script src="script.js"></script> 
<link rel="stylesheet" href="chat.css?v=<?php echo time(); // Cache busting ?>">
<script src="chat.js?v=<?php echo time(); // Cache busting ?>"></script>
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
</body>
</html>