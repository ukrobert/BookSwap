<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VDAR Atbilstība | BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&display=swap">
  <style>
    .gdpr-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .gdpr-header {
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .gdpr-header h1 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    
    .gdpr-header p {
      color: #666;
      max-width: 700px;
      margin: 0 auto;
    }
    
    .gdpr-content {
      background-color: #fff;
      border-radius: 10px;
      padding: 2rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      font-size: 1rem;
      line-height: 1.6;
    }
    
    .gdpr-content h2 {
      font-family: 'Merriweather', serif;
      color: #59321f;
      margin-top: 2rem;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    
    .gdpr-content h3 {
      font-family: 'Merriweather', serif;
      color: #7d654b;
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
      font-size: 1.25rem;
    }
    
    .gdpr-content p {
      margin-bottom: 1rem;
    }
    
    .gdpr-content ul {
      margin-bottom: 1rem;
      padding-left: 1.5rem;
    }
    
    .gdpr-content li {
      margin-bottom: 0.5rem;
    }
    
    .gdpr-navigation {
      position: sticky;
      top: 100px;
      background: #fff;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
    }
    
    .gdpr-navigation h3 {
      font-family: 'Merriweather', serif;
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: #59321f;
    }
    
    .gdpr-nav-links {
      list-style: none;
      padding: 0;
    }
    
    .gdpr-nav-links li {
      margin-bottom: 0.5rem;
    }
    
    .gdpr-nav-links a {
      color: #7d654b;
      text-decoration: none;
      transition: color 0.3s;
      display: block;
      padding: 0.5rem 0;
      border-bottom: 1px solid #f5f5f5;
    }
    
    .gdpr-nav-links a:hover, 
    .gdpr-nav-links a.active {
      color: #59321f;
    }
    
    .gdpr-card {
      background-color: #f9f5f1;
      border-radius: 10px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border-left: 4px solid #59321f;
    }
    
    .gdpr-card h3 {
      color: #59321f;
      margin-top: 0;
    }
    
    .gdpr-btn {
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
      margin-top: 1rem;
    }
    
    .gdpr-btn:hover {
      background-color: #7d654b;
    }
    
    .gdpr-icon {
      font-size: 1.5rem;
      margin-right: 0.5rem;
      vertical-align: middle;
      color: #59321f;
    }
    
    .gdpr-last-updated {
      font-style: italic;
      margin-top: 2rem;
      text-align: center;
      color: #666;
    }
    
    .gdpr-section {
      scroll-margin-top: 120px;
    }
    
    .gdpr-action {
      margin-top: 3rem;
      text-align: center;
    }
    
    .gdpr-action .btn {
      display: inline-block;
      background-color: #59321f;
      color: #fff;
      padding: 0.75rem 2rem;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: background-color 0.3s;
    }
    
    .gdpr-action .btn:hover {
      background-color: #7d654b;
    }
    
    @media (min-width: 768px) {
      .gdpr-container {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 2rem;
        align-items: start;
      }
      
      .gdpr-header {
        grid-column: 1 / -1;
      }
      
      .gdpr-navigation {
        margin-bottom: 0;
      }
      
      .gdpr-action {
        grid-column: 1 / -1;
      }
    }
    
    @media (max-width: 767px) {
      .gdpr-navigation {
        position: relative;
        top: 0;
      }
    }

    /* Animation for GDPR sections */
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
          <a href="how-it-works.php" class="nav-link">Kā tas strādā</a>
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
        <a href="how-it-works.php" class="mobile-nav-link">Kā tas strādā</a>
        <div class="mobile-actions">
          <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
          <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="gdpr-container">
      <div class="gdpr-header">
        <h1>VDAR Atbilstība</h1>
        <p>Informācija par to, kā BookSwap ievēro Vispārīgo datu aizsardzības regulu (VDAR), un kā jūs varat izmantot savas datu tiesības.</p>
      </div>
      
      <div class="gdpr-navigation">
        <h3>Saturs</h3>
        <ul class="gdpr-nav-links">
          <li><a href="#gdpr-overview" class="active">VDAR Pārskats</a></li>
          <li><a href="#lawful-basis">Likumīga datu apstrādes pamatojums</a></li>
          <li><a href="#data-rights">Jūsu datu tiesības</a></li>
          <li><a href="#exercise-rights">Kā izmantot savas tiesības</a></li>
          <li><a href="#data-transfers">Starptautiskās datu pārsūtīšanas</a></li>
          <li><a href="#data-protection">Datu aizsardzības pasākumi</a></li>
          <li><a href="#dpo">Datu aizsardzības speciālists</a></li>
          <li><a href="#complaints">Sūdzības</a></li>
        </ul>
      </div>
      
      <div class="gdpr-content">
        <section id="gdpr-overview" class="gdpr-section fade-in">
          <h2>VDAR Pārskats</h2>
          <p>Vispārīgā datu aizsardzības regulas (VDAR) ir ES tiesību akti par datu aizsardzību un privātumu visām Eiropas Savienības un Eiropas Ekonomikas zonas personām. Tā regulē personas datu eksportu ārpus ES un EES teritorijas.</p>
          
          <div class="gdpr-card">
            <h3>BookSwap Saistība</h3>
            <p>BookSwap ir apņēmies ievērot VDAR. Mēs cienām jūsu privātumu un esam veltīti jūsu personisko datu aizsardzībai. Mēs esam ieviesti pasākumus, lai nodrošinātu, ka visi personas dati, ko mēs apkopojam, tiek apstrādāti saskaņā ar GDPR principiem.</p>
          </div>
          
          <p>Saskaņā ar VDAR personas dati jāapstrādā:</p>
          <ul>
            <li>Likumīgi, godīgi un caurspīdīgi</li>
            <li>Vākts noteiktiem, skaidriem un likumīgiem mērķiem</li>
            <li>Adekvāti, atbilstoši un ierobežoti līdz nepieciešamajam</li>
            <li>Precīzi un regulāri atjaunināti</li>
            <li>Uzglabāti ne ilgāk kā nepieciešams</li>
            <li>Apstrādāti droši un aizsargāti no nesankcionētas apstrādes, nejaušiem zaudējumiem vai bojājumiem</li>
          </ul>
        </section>
        
        <section id="lawful-basis" class="gdpr-section fade-in">
          <h2>Likumīga datu apstrādes pamatojums</h2>
          <p>Saskaņā ar VDAR mums ir nepieciešams likumīgs pamats, lai apkopotu un izmantotu jūsu personisko informāciju. Lūk, kā mēs apstrādājam jūsu datus un likumīgais pamats tam:</p>
          
          <h3>Līgums</h3>
          <p>Mēs apstrādājam jūsu personas datus, lai izpildītu mūsu līguma saistības, kad jūs reģistrējaties kontam, iekļaujat grāmatas vai piedalāties grāmatu apmaiņā.</p>
          
          <h3>Leģitīmas intereses</h3>
          <p>Mēs apstrādājam jūsu datus mūsu leģitīmām interesēm, piemēram, pakalpojumu uzlabošanai, krāpšanas novēršanai un drošas grāmatu apmaiņas platformas nodrošināšanai.</p>
          
          <h3>Piekrišana</h3>
          <p>Kad jūs pierakstāties mārketinga komunikācijām vai piekrītat izvēles sīkfailiem, mēs apstrādājam jūsu datus, pamatojoties uz jūsu piekrišanu. Jūs varat atsaukt šo piekrišanu jebkurā laikā.</p>
          
          <h3>Juridiska saistība</h3>
          <p>Dažkārt mums ir nepieciešams apstrādāt jūsu datus, lai ievērotu juridiskas saistības, piemēram, atbildot uz likumīgiem valsts iestāžu pieprasījumiem.</p>
        </section>
        
        <section id="data-rights" class="gdpr-section fade-in">
          <h2>Jūsu datu tiesības</h2>
          <p>Saskaņā ar VDAR jums ir vairākas tiesības attiecībā uz jūsu personiskajiem datiem:</p>
          
          <h3>Tiesības uz piekļuvi</h3>
          <p>Jums ir tiesības pieprasīt savu personisko datu kopiju, ko mēs uzglabājam par jums, kā arī informāciju par to, kā mēs tos apstrādājam.</p>
          
          <h3>Tiesības uz labošanu</h3>
          <p>Jums ir tiesības pieprasīt, lai mēs izlabotu jebkūrus neprecīzus personas datus, ko mēs uzglabājam par jums, vai arī papildinātu nepilnīgus datus.</p>
          
          <h3>Tiesības uz dzēšanu (Tiesības būt aizmirstam)</h3>
          <p>Noteiktos apstākļos jūs varat pieprasīt, lai mēs izdzēstu jūsu personas datus no mūsu sistēmām.</p>
          
          <h3>Tiesības ierobežot apstrādi</h3>
          <p>Jums ir tiesības pieprasīt, lai mēs ierobežotu jūsu personisko datu apstrādi noteiktos apstākļos.</p>
          
          <h3>Tiesības uz datu pārnesamību</h3>
          <p>Jums ir tiesības iegūt un atkārtoti izmantot savus personas datus saviem mērķiem dažādās pakalpojumos.</p>
          
          <h3>Tiesības iebilst</h3>
          <p>Jums ir tiesības iebilst pret mūsu jūsu personisko datu apstrādi noteiktos apstākļos, īpaši tiešā mārketinga nolūkos.</p>
          
          <h3>Tiesības saistībā ar automatizētu lēmumu pieņemšanu</h3>
          <p>Jums ir tiesības saistībā ar automatizētu lēmumu pieņemšanu un profilēšanu, kad lēmumi par jums tiek pieņemti bez cilvēka iejaukšanās.</p>
        </section>
        
        <section id="exercise-rights" class="gdpr-section fade-in">
          <h2>Kā izmantot savas tiesības</h2>
          <p>Jūs varat izmantot savas VDAR tiesības šādos veidos:</p>
          
          <div class="gdpr-card">
            <h3>Sazinieties ar mums tieši</h3>
            <p>Nosūtiet e-pastu mūsu Datu aizsardzības speciālistam uz dpo@bookswap.com vai izmantojiet mūsu <a href="contact-us.php">kontaktu formu</a>.</p>
            <a href="contact-us.php" class="gdpr-btn">Kontaktu forma</a>
          </div>
          
          <h3>Caur savu kontu</h3>
          <p>Daudzas no jūsu tiesībām var izmantot tieši caur jūsu BookSwap konta iestatījumiem. Jūs varat:</p>
          <ul>
            <li>Atjaunināt savu personisko informāciju</li>
            <li>Lejupielādēt savus datus</li>
            <li>Pielāgot savu komunikācijas preferences</li>
            <li>Dzēst savu kontu</li>
          </ul>
          
          <h3>Atbildes laiks</h3>
          <p>Mēs atbildēsim uz visiem likumīgiem pieprasījumiem viena mēneša laikā. Reizēm tas var aizņemt ilgāk, ja jūsu pieprasījums ir īpaši sarežģīts vai esat iesniedzis vairākus pieprasījumus. Šajā gadījumā mēs jūs informēsim un turēsim jūs informētus.</p>
        </section>
        
        <section id="data-transfers" class="gdpr-section fade-in">
          <h2>Starptautiskās datu pārsūtīšanas</h2>
          <p>BookSwap darbojas globāli, kas var ietvert jūsu datu pārsūtīšanu un apstrādi ārpus Eiropas Ekonomikas zonas (EEZ). Katru reizi, kad mēs pārsūtām jūsu personas datus ārpus EEZ, mēs nodrošinām līdzīgu aizsardzības līmeni, ieviešot vismaz vienu no šiem aizsardzības pasākumiem:</p>
          
          <ul>
            <li>Eiropas Komisijas apstiprinātas standarta līguma klauzulas</li>
            <li>Eiropas Komisijas atbilstības lēmumi</li>
            <li>Saistošie korporatīvie noteikumi pārsūtījumiem korporatīvās grupas ietvaros</li>
          </ul>
          
          <p>Ja neviens no šiem aizsardzības pasākumiem nav pieejams, mēs lūgsim jūsu skaidru piekrišanu piedāvātajam pārsūtījumam. Jums ir tiesības atsaukt šo piekrišanu jebkurā laikā.</p>
        </section>
        
        <section id="data-protection" class="gdpr-section fade-in">
          <h2>Datu aizsardzības pasākumi</h2>
          <p>Mēs esam ieviesti atbilstošus tehniskos un organizatoriskos pasākumus, lai nodrošinātu drošības līmeni, kas atbilst riskam, tostarp:</p>
          
          <ul>
            <li>Personas datu šifrēšana</li>
            <li>Regulāra mūsu drošības pasākumu efektivitātes pārbaude un novērtēšana</li>
            <li>Spēja atjaunot piekļuvi personiskiem datiem savlaicīgi incidenta gadījumā</li>
            <li>Regulāra darbinieku apmācība par datu aizsardzību un drošību</li>
            <li>Datu aizsardzības ietekmes novērtējumi jauniem procesiem</li>
            <li>Datu minimizācijas principu ieviešana</li>
          </ul>
        </section>
        
        <section id="dpo" class="gdpr-section fade-in">
          <h2>Datu aizsardzības speciālists</h2>
          <p>Mēs esam iecēluši Datu aizsardzības speciālistu (DAS), kurš ir atbildīgs par jautājumiem, kas saistīti ar šo GDPR politiku un mūsu personisko datu apstrādi.</p>
          <p>Ja jums ir kādi jautājumi par šo VDAR politiku, mūsu privātuma praksi vai jūsu personiskajiem datiem, lūdzu, sazinieties ar mūsu DAS:</p>
          
          <div class="gdpr-card">
            <h3>Sazinieties ar mūsu DAS</h3>
            <p>E-pasts: dpo@bookswap.com</p>
            <p>Pasta adrese:<br>
              Datu aizsardzības speciālists<br>
              BookSwap, Inc.<br>
              123 Grāmatu iela<br>
              Reading, CA 94000<br>
              Amerikas Savienotās Valstis
            </p>
          </div>
        </section>
        
        <section id="complaints" class="gdpr-section fade-in">
          <h2>Sūdzības</h2>
          <p>Jums ir tiesības jebkurā laikā iesniegt sūdzību vietējai datu aizsardzības iestādei. Tomēr mēs novērtētu iespēju risināt jūsu problēmas, pirms jūs vērsaties pie iestādes, tāpēc, lūdzu, vispirms sazinieties ar mums.</p>
          
          <p>ES iedzīvotāji var atrast savas vietējās datu aizsardzības iestādes kontaktinformāciju <a href="https://ec.europa.eu/justice/article-29/structure/data-protection-authorities/index_en.htm" target="_blank">Eiropas Komisijas tīmekļa vietnē</a>.</p>
          
          <p>Lielbritānijas iedzīvotājiem datu aizsardzības iestāde ir <a href="https://ico.org.uk/" target="_blank">Informācijas komisāra birojs (IKB)</a>.</p>
        </section>
        
        <div class="gdpr-last-updated">
          <p>Pēdējoreiz atjaunināts: 2025. gada 9. aprīlis</p>
        </div>
      </div>
      
      <div class="gdpr-action">
        <a href="privacy-policy.php" class="btn">Skatīt mūsu Privātuma politiku</a>
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
  
  <script src="js/utils.js"></script>
  <script src="gdpr.js"></script>
</body>
</html>