<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sīkdatņu politika | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap">
  <style>
    .cookie-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .cookie-header {
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .cookie-header h1 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    
    .cookie-header p {
      color: #666;
      max-width: 700px;
      margin: 0 auto;
    }
    
    .cookie-content {
      background-color: #fff;
      border-radius: 10px;
      padding: 2rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      font-size: 1rem;
      line-height: 1.6;
    }
    
    .cookie-content h2 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    
    .cookie-content h3 {
      font-family: 'Merriweather', serif;
      color: #7d654b;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
      font-size: 1.25rem;
    }
    
    .cookie-content p {
      margin-bottom: 1rem;
    }
    
    .cookie-content ul, .cookie-content ol {
      margin-bottom: 1rem;
      padding-left: 1.5rem;
    }
    
    .cookie-content li {
      margin-bottom: 0.5rem;
    }
    
    .cookie-navigation {
      position: sticky;
      top: 100px;
      background: #fff;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
    }
    
    .cookie-navigation h3 {
      font-family: 'Merriweather', serif;
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: #59321f;
    }
    
    .cookie-nav-links {
      list-style: none;
      padding: 0;
    }
    
    .cookie-nav-links li {
      margin-bottom: 0.5rem;
    }
    
    .cookie-nav-links a {
      color: #7d654b;
      text-decoration: none;
      transition: color 0.3s;
      display: block;
      padding: 0.5rem 0;
      border-bottom: 1px solid #f5f5f5;
    }
    
    .cookie-nav-links a:hover, 
    .cookie-nav-links a.active {
      color: #59321f;
    }
    
    .cookie-table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.5rem 0;
    }
    
    .cookie-table th, .cookie-table td {
      border: 1px solid #e5e5e5;
      padding: 0.75rem;
      text-align: left;
    }
    
    .cookie-table th {
      background-color: #f9f5f1;
      font-weight: 600;
      color: #59321f;
    }
    
    .cookie-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    
    .cookie-preferences {
      background-color: #f9f5f1;
      padding: 1.5rem;
      margin: 2rem 0;
      border-radius: 10px;
      text-align: center;
    }
    
    .cookie-preferences h3 {
      margin-bottom: 1rem;
    }
    
    .cookie-btn {
      display: inline-block;
      background-color: #59321f;
      color: #fff;
      padding: 0.75rem 2rem;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s;
      border: none;
      cursor: pointer;
      margin: 0.5rem;
    }
    
    .cookie-btn:hover {
      background-color: #7d654b;
    }
    
    .cookie-btn.secondary {
      background-color: transparent;
      border: 1px solid #59321f;
      color: #59321f;
    }
    
    .cookie-btn.secondary:hover {
      background-color: #f9f5f1;
    }
    
    .cookie-last-updated {
      font-style: italic;
      margin-top: 2rem;
      text-align: center;
      color: #666;
    }
    
    .cookie-section {
      scroll-margin-top: 120px;
    }
    
    @media (min-width: 768px) {
      .cookie-container {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 2rem;
        align-items: start;
      }
      
      .cookie-header {
        grid-column: 1 / -1;
      }
      
      .cookie-navigation {
        margin-bottom: 0;
      }
      
      .cookie-action {
        grid-column: 1 / -1;
      }
    }
    
    @media (max-width: 767px) {
      .cookie-navigation {
        position: relative;
        top: 0;
      }
      
      .cookie-table {
        display: block;
        overflow-x: auto;
      }
    }

    /* Animation for cookie sections */
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
        <a href="browse.php" class="mobile-nav-link">Pārlūkot grāmatas</a>
        <a href="how-it-works.php" class="mobile-nav-link">Kā tas darbojas</a>
        <div class="mobile-actions">
          <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
          <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="cookie-container">
      <div class="cookie-header">
        <h1>Sīkdatņu politika</h1>
        <p>Šī sīkdatņu politika skaidro, kā BookSwap izmanto sīkdatnes un līdzīgas tehnoloģijas, lai atpazītu tevi, kad apmeklē mūsu vietni, un kā tu vari pārvaldīt savus sīkdatņu iestatījumus.</p>
      </div>
      
      <div class="cookie-navigation">
        <h3>Saturs</h3>
        <ul class="cookie-nav-links">
          <li><a href="#what-are-cookies" class="active">Kas ir sīkdatnes</a></li>
          <li><a href="#cookie-types">Mēs izmantojam šādus sīkdatņu veidus</a></li>
          <li><a href="#cookie-list">Sīkdatņu saraksts</a></li>
          <li><a href="#third-party">Trešo pušu sīkdatnes</a></li>
          <li><a href="#cookie-control">Kā kontrolēt sīkdatnes</a></li>
          <li><a href="#cookie-preferences">Sīkdatņu iestatījumi</a></li>
          <li><a href="#cookie-changes">Izmaiņas šajā politikā</a></li>
          <li><a href="#cookie-contact">Sazinies ar mums</a></li>
        </ul>
      </div>
      
      <div class="cookie-content">
        <section id="what-are-cookies" class="cookie-section fade-in">
          <h2>Kas ir sīkdatnes</h2>
          <p>Sīkdatnes ir mazi teksta faili, kurus ievieto tavā datorā vai mobilajā ierīcē, kad apmeklē vietni. Sīkdatnes plaši izmanto vietņu īpašnieki, lai nodrošinātu to pareizu darbību un apkopotu informāciju par apmeklējumu statistiku.</p>
          <p>Sīkdatnes, kuras iestata vietnes īpašnieks (šajā gadījumā BookSwap), sauc par “pirmās puses sīkdatnēm”. Sīkdatnes, kuras iestata citas puses, sauc par “trešo pušu sīkdatnēm”. Trešo pušu sīkdatnes ļauj piedāvāt funkcionalitāti, piemēram, reklāmas, interaktīvu saturu un analītiku.</p>
        </section>
        
        <section id="cookie-types" class="cookie-section fade-in">
          <h2>Mēs izmantojam šādus sīkdatņu veidus</h2>
          <p>Mēs savā vietnē izmantojam šādus sīkdatņu veidus:</p>
          
          <h3>Necessary Cookies</h3>
          <p>Šīs sīkdatnes ir būtiskas, lai vietne darbotos pareizi. Tās nodrošina pamata funkcijas, piemēram, drošību, tīkla pārvaldību un piekļuvi kontam. Tu nevari atteikties no šo sīkdatņu izmantošanas.</p>
          
          <h3>Preference Cookies</h3>
          <p>Šīs sīkdatnes ļauj vietnei atcerēties informāciju, kas maina vietnes uzvedību vai izskatu, piemēram, tavu izvēlēto valodu vai reģionu.</p>
          
          <h3>Analytics Cookies</h3>
          <p>Šīs sīkdatnes palīdz mums saprast, kā apmeklētāji mijiedarbojas ar mūsu vietni, anonīmi apkopojot un ziņojot informāciju. Tas palīdz mums uzlabot vietnes lietošanas pieredzi nākotnē.</p>
          
          <h3>Marketing Cookies</h3>
          <p>Šīs sīkdatnes seko tavai darbībai tiešsaistē, lai reklāmdevēji varētu rādīt atbilstošākas reklāmas vai ierobežotu reklāmas rādīšanas reižu skaitu. Šīs sīkdatnes var kopīgot šo informāciju ar citām organizācijām vai reklāmdevējiem.</p>
        </section>
        
        <section id="cookie-list" class="cookie-section fade-in">
          <h2>Sīkdatņu saraksts</h2>
          <p>Šeit ir detalizēts saraksts ar sīkdatnēm, ko mēs izmantojam BookSwap:</p>
          
          <table class="cookie-table">
            <thead>
              <tr>
                <th>Sīkdatnes nosaukums</th>
                <th>Tips</th>
                <th>Apraksts</th>
                <th>Ilgums</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>bookswap_session</td>
                <td>Nepieciešams</td>
                <td>Tiek izmantota sesijas stāvokļa uzturēšanai</td>
                <td>Sesija</td>
              </tr>
              <tr>
                <td>bookswap_auth</td>
                <td>Nepieciešams</td>
                <td>Identificē tevi kā pieteikušos lietotāju</td>
                <td>30 dienas</td>
              </tr>
              <tr>
                <td>bookswap_theme</td>
                <td>Priekšrocības</td>
                <td>Atceras izvēlēto gaišā/tumšā režīma iestatījumu</td>
                <td>1 gads</td>
              </tr>
              <tr>
                <td>bookswap_location</td>
                <td>Priekšrocības</td>
                <td>Atceras tavu atrašanās vietas iestatījumus grāmatu meklēšanai</td>
                <td>30 dienas</td>
              </tr>
              <tr>
                <td>_ga</td>
                <td>Analītikas</td>
                <td>Google Analytics sīkdatne, kas tiek izmantota lietotāju atšķiršanai</td>
                <td>2 gadi</td>
              </tr>
              <tr>
                <td>_gid</td>
                <td>Analītikas</td>
                <td>Google Analytics sīkdatne, kas tiek izmantota lietotāju atšķiršanai</td>
                <td>24 stundas</td>
              </tr>
            </tbody>
          </table>
        </section>
        
        <section id="third-party" class="cookie-section fade-in">
          <h2>Trešo pušu sīkdatnes</h2>
          <p>Dažās mūsu lapās tiek attēlots saturs no ārējiem piegādātājiem, piemēram, YouTube, Facebook un Twitter. Lai skatītu šo trešo pušu saturu, vispirms ir jāpieņem to noteikumi un nosacījumi, tostarp sīkdatņu politika, ko mēs nekontrolējam.</p>
          <p>Ja tu neskati šo saturu, trešo pušu sīkdatnes netiek instalētas tavā ierīcē.</p>
          
          <h3>Third-party providers on BookSwap:</h3>
          <ul>
            <li>YouTube</li>
            <li>Google Maps</li>
            <li>Google Analytics</li>
            <li>Facebook</li>
            <li>Twitter</li>
          </ul>
          
          <p>Šie trešo pušu pakalpojumi nav BookSwap kontrolē. Pakalpojumu sniedzēji jebkurā brīdī var mainīt savus noteikumus, mērķus vai sīkdatņu lietošanu.</p>
        </section>
        
        <section id="cookie-control" class="cookie-section fade-in">
          <h2>Kā kontrolēt sīkdatnes</h2>
          <p>Tev ir tiesības izvēlēties, vai pieņemt vai noraidīt sīkdatnes. Tu vari izmantot savas tiesības, iestatot vēlamos sīkdatņu iestatījumus sadaļā 'Sīkdatņu iestatījumi' zemāk.</p>
          <p>Tu vari kontrolēt sīkdatņu izmantošanu arī ar pārlūkprogrammas iestatījumiem. Lielākā daļa pārlūku piedāvā šādu iespēju. To vari izdarīt šādi:</p>
          
          <h3>Chrome</h3>
          <ol>
            <li>Go to Settings > Privacy and security > Cookies and other site data</li>
            <li>Choose your preferred cookie settings</li>
          </ol>
          
          <h3>Firefox</h3>
          <ol>
            <li>Go to Options > Privacy & Security</li>
            <li>Under Enhanced Tracking Protection, choose your preferred settings</li>
          </ol>
          
          <h3>Safari</h3>
          <ol>
            <li>Go to Preferences > Privacy</li>
            <li>Choose your preferred cookie settings</li>
          </ol>
          
          <h3>Microsoft Edge</h3>
          <ol>
            <li>Go to Settings > Cookies and site permissions > Cookies and site data</li>
            <li>Choose your preferred cookie settings</li>
          </ol>
          
          <p>Ņem vērā, ka, ja atsakies no sīkdatnēm, dažas vietnes funkcijas var nebūt pieejamas.</p>
        </section>
        
        <section id="cookie-preferences" class="cookie-section fade-in">
          <h2>Sīkdatņu iestatījumi</h2>
          <div class="cookie-preferences">
            <h3>Manage Your Sīkdatņu iestatījumi</h3>
            <p>Tu vari pielāgot savus sīkdatņu iestatījumus šai vietnei. Nepieciešamās sīkdatnes nevar atslēgt.</p>
            <div class="cookie-buttons">
              <button class="cookie-btn" id="accept-all-cookies">Pieņemt visas sīkdatnes</button>
              <button class="cookie-btn secondary" id="necessary-cookies-only">Tikai nepieciešamās sīkdatnes</button>
              <button class="cookie-btn secondary" id="customize-cookies">Pielāgot iestatījumus</button>
            </div>
          </div>
        </section>
        
        <section id="cookie-changes" class="cookie-section fade-in">
          <h2>Izmaiņas šajā politikā</h2>
          <p>Mēs laiku pa laikam varam atjaunināt šo sīkdatņu politiku. Par izmaiņām paziņosim, publicējot jauno versiju šajā lapā un atjauninot datumu 'Pēdējo reizi atjaunināts'.</p>
          <p>Iesakām periodiski pārskatīt šo politiku, lai sekotu izmaiņām. Izmaiņas stājas spēkā brīdī, kad tās tiek publicētas šajā lapā.</p>
        </section>
        
        <section id="cookie-contact" class="cookie-section fade-in">
          <h2>Sazinies ar mums</h2>
          <p>Ja tev ir jautājumi par mūsu sīkdatņu politiku, sazinies ar mums:</p>
          <p>Email: privacy@bookswap.com</p>
          <p>Postal Address: BookSwap, Inc.<br>123 Book Street<br>Reading, CA 94000<br>United States</p>
        </section>
        
        <div class="cookie-last-updated">
          <p>Pēdējo reizi atjaunināts: 2025. gada 9. aprīlī</p>
        </div>
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

  <script src="js/utils.js"></script>
  <script src="cookies.js"></script>
  <script src="cookieModal.js"></script>
</body>
</html>
