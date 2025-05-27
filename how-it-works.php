<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kā tas darbojas - BookSwap</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .how-it-works-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .how-it-works-header {
      text-align: center;
      margin-bottom: 3rem;
    }
    
    .steps-section {
      margin-bottom: 4rem;
    }
    
    .steps-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    
    @media (min-width: 768px) {
      .steps-container {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    
    .step {
      text-align: center;
      padding: 2rem;
      background-color: var(--color-white);
      border-radius: var(--radius-lg);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      border: 1px solid var(--color-paper);
    }
    
    .step-number {
      background-color: var(--color-burgundy);
      color: var(--color-white);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-weight: 600;
    }
    
    .step-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1rem;
      color: var(--color-burgundy);
    }
    
    .step-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    
    .features-section {
      margin-bottom: 4rem;
      padding: 3rem 0;
      background-color: var(--color-paper);
    }
    
    .features-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    
    @media (min-width: 640px) {
      .features-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    
    @media (min-width: 1024px) {
      .features-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }
    
    .feature {
      background-color: var(--color-white);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .feature-icon {
      color: var(--color-burgundy);
      margin-bottom: 1rem;
    }
    
    .feature-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .feature-description {
      color: var(--color-gray);
      font-size: 0.875rem;
    }
    
    .detailed-section {
      margin-bottom: 4rem;
    }
    
    .detail {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      margin-bottom: 3rem;
      align-items: center;
    }
    
    @media (min-width: 768px) {
      .detail {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .detail:nth-child(even) .detail-content {
        order: 1;
      }
      
      .detail:nth-child(even) .detail-image {
        order: 2;
      }
    }
    
    .detail-image img {
      width: 100%;
      height: auto;
      border-radius: var(--radius-lg);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .detail-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    
    .detail-description {
      margin-bottom: 1.5rem;
      color: var(--color-gray);
    }
    
    .detail-features {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .detail-feature {
      display: flex;
      align-items: flex-start;
    }
    
    .detail-feature svg {
      color: var(--color-burgundy);
      margin-right: 0.75rem;
      flex-shrink: 0;
      margin-top: 0.25rem;
    }
    
    .faq-section {
      background-color: var(--color-cream);
      padding: 3rem 0;
      border-radius: var(--radius-lg);
    }
    
    .faq-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .faq-items {
      max-width: 800px;
      margin: 0 auto;
    }
    
    .cta-section {
      text-align: center;
      padding: 3rem 0;
    }
    
    .cta-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    
    .cta-description {
      max-width: 600px;
      margin: 0 auto 2rem;
      color: var(--color-gray);
    }
    
    .cta-buttons {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      justify-content: center;
      max-width: 400px;
      margin: 0 auto;
    }
    
    @media (min-width: 640px) {
      .cta-buttons {
        flex-direction: row;
      }
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
        <a href="how-it-works.php" class="mobile-nav-link">Kā tas darbojas</a>
        <div class="mobile-actions">
          <a href="login.php" class="btn btn-outline mobile-btn">Pieslēgties</a>
          <a href="signup.php" class="btn btn-primary mobile-btn">Reģistrēties</a>
        </div>
      </div>
    </div>
  </header>

  <main>
    <section class="how-it-works-container">
      <div class="how-it-works-header">
        <h1 class="section-title">Kā darbojas BookSwap</h1>
        <p class="section-description">Apmainīties ar grāmatām ar citiem lasītājiem vēl nekad nav bijis vieglāk. Veiciet šos vienkāršos soļus, lai sāktu apmainīties ar sev mīļām grāmatām.</p>
      </div>
      
      <!-- Steps Overview Section -->
      <div class="steps-section">
        <div class="steps-container">
          <!-- Step 1 -->
          <div class="step">
            <div class="step-number">1</div>
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <h3 class="step-title">Sastādiet savu grāmatu sarakstu</h3>
            <p>Pievienojiet grāmatas no savas personīgās bibliotēkas, kuras vēlaties apmainīt ar citām. Norādiet informāciju par stāvokli un žanru.</p>
          </div>
          
          <!-- Step 2 -->
          <div class="step">
            <div class="step-number">2</div>
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </div>
            <h3 class="step-title">Atrast & Pieprasīt</h3>
            <p>Pārlūkojiet tirdzniecībai pieejamās grāmatas. Kad atradīsiet sev tīkamu grāmatu, nosūtiet īpašniekam tirdzniecības pieprasījumu.</p>
          </div>
          
          <!-- Step 3 -->
          <div class="step">
            <div class="step-number">3</div>
            <div class="step-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
              </svg>
            </div>
            <h3 class="step-title">Saskaņot & apmainīties</h3>
            <p>Aprunājieties ar savu apmaiņas partneri, vienojieties par apmaiņas detaļām un pēc tam tiekieties vai sūtiet savas grāmatas, lai pabeigtu apmaiņu.</p>
          </div>
        </div>
      </div>
      
      <!-- Features Section -->
      <div class="features-section">
        <div class="container">
          <h2 class="section-title text-center mb-8">Kāpēc izmantot BookSwap?</h2>
          
          <div class="features-grid">
            <!-- Feature 1 -->
            <div class="feature">
              <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
              </svg>
              <h3 class="feature-title">Videi draudzīgs</h3>
              <p class="feature-description">Dodiet grāmatām otru dzīvi un samaziniet atkritumu daudzumu, piedaloties koplietošanas ekonomikā.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="feature">
              <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
              <h3 class="feature-title">Kopiena</h3>
              <p class="feature-description">Sazinieties ar citiem grāmatu mīļotājiem savā reģionā un veidojiet nozīmīgus kontaktus.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="feature">
              <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
              <h3 class="feature-title">Ietaupiet naudu</h3>
              <p class="feature-description">Lasiet vairāk, tērējot mazāk. Tā vietā, lai nepārtraukti pirktu jaunas grāmatas, tirgojieties ar tām.</p>
            </div>
            
            <!-- Feature 4 -->
            <div class="feature">
              <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <h3 class="feature-title">Atklājiet</h3>
              <p class="feature-description">Atrodiet jaunus autorus un žanrus, ar kuriem citādi, iespējams, nekad nebūtu saskāries.</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Detailed Steps Section -->
      <div class="detailed-section">
        <h2 class="section-title text-center mb-8">Detalizēts BookSwap ceļvedis</h2>
        
        <!-- Step 1 Detail -->
        <div class="detail">
          <div class="detail-content">
            <h3 class="detail-title">1. Izveidojiet savu bibliotēku</h3>
            <p class="detail-description">Sāciet, pievienojot grāmatas, kuras esat gatavs apmainīt. Jo vairāk grāmatu pievienosiet, jo lielākas būs jūsu iespējas atrast tirdzniecības partnerus.</p>
            
            <div class="detail-features">
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Pievienojiet grāmatas manuāli vai skenējiet svītrkodus</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Augšupielādējiet grāmatas vāka attēlus</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Norādiet katras grāmatas stāvokli</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Pievienot personiskas piezīmes vai pārskatus</span>
              </div>
            </div>
          </div>
          <div class="detail-image">
            <img src="https://placehold.co/600x400?text=Adding+Books" alt="Adding Books to Your Library">
          </div>
        </div>
        
        <!-- Step 2 Detail -->
        <div class="detail">
          <div class="detail-image">
            <img src="https://placehold.co/600x400?text=Browsing+Books" alt="Browsing Books">
          </div>
          <div class="detail-content">
            <h3 class="detail-title">2. Meklēšana un atklāšana</h3>
            <p class="detail-description">Izmantojiet mūsu viedās meklēšanas un filtrēšanas rīkus, lai atrastu grāmatas, par kurām vēlaties tirgoties. Jūs varat filtrēt pēc atrašanās vietas, žanra, autora un daudz kā cita.</p>
            
            <div class="detail-features">
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Meklēt pēc nosaukuma, autora vai ISBN</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Atlasīt pēc attāluma, žanra, stāvokļa</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Saglabāt iecienītākos meklējumus</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Saņemiet personalizētus ieteikumus</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Step 3 Detail -->
        <div class="detail">
          <div class="detail-content">
            <h3 class="detail-title">3. Sākt tirdzniecības pieprasījumu</h3>
            <p class="detail-description">Kad esat atradis vēlamo grāmatu, iesniedziet tirdzniecības pieprasījumu. Varat piedāvāt vienu no savām grāmatām apmaiņā vai piedāvāt vairākus variantus, lai palielinātu pieņemšanas iespēju.</p>
            
            <div class="detail-features">
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Tirdzniecības pieprasījumu nosūtīšana dažu sekunžu laikā</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Piedāvājiet vairākas grāmatas izskatīšanai</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Pievienojiet personisku ziņu</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Pieprasījuma statusa izsekošana reāllaikā</span>
              </div>
            </div>
          </div>
          <div class="detail-image">
            <img src="https://placehold.co/600x400?text=Trade+Request" alt="Sending a Trade Request">
          </div>
        </div>
        
        <!-- Step 4 Detail -->
        <div class="detail">
          <div class="detail-image">
            <img src="https://placehold.co/600x400?text=Message+Exchange" alt="Messaging Between Users">
          </div>
          <div class="detail-content">
            <h3 class="detail-title">4. Saziņa un organizēšana</h3>
            <p class="detail-description">Kad maiņas darījums ir apstiprināts, izmantojiet mūsu drošo ziņojumapmaiņas sistēmu, lai vienotos par apmaiņas detaļām.</p>
            
            <div class="detail-features">
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>No gala līdz galam šifrēti ziņojumi</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Ieteikt tikšanās vietas</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Piegādes adreses maiņa (ja sūtīšana notiek pa pastu)</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Grafiku koordinēšanas rīki</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Step 5 Detail -->
        <div class="detail">
          <div class="detail-content">
            <h3 class="detail-title">5. Pabeidziet apmaiņu</h3>
            <p class="detail-description">Tiekieties klātienē publiskā vietā vai nosūtiet grāmatas viens otram pa pastu. Pēc tam apstipriniet pabeigto apmaiņu BookSwap.</p>
            
            <div class="detail-features">
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Atzīmēt darījumus kā pabeigtus</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Novērtējiet savu tirdzniecības pieredzi</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Atstājiet atsauksmes par saņemtajām grāmatām</span>
              </div>
              <div class="detail-feature">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Veidojiet savu tirdzniecības reputāciju</span>
              </div>
            </div>
          </div>
          <div class="detail-image">
            <img src="https://placehold.co/600x400?text=Completing+Exchange" alt="Completing the Exchange">
          </div>
        </div>
      </div>
      
      <!-- FAQ Section -->
      <div class="faq-section">
        <div class="container">
          <div class="faq-header">
            <h2 class="section-title">Biežāk uzdotie jautājumi</h2>
            <p class="section-description">Vai jums ir vēl jautājumi? Apskatiet mūsu visaptverošo <a href="faq.php" class="text-burgundy" style="cursor: pointer; text-decoration: underline;">BUJ lapa</a>.</p>
          </div>
          
          <div class="faq-items">
            <div class="faq-item">
              <div class="faq-question">Vai BookSwap lietošana ir bezmaksas?</div>
              <div class="faq-answer">
                <p>Jā, pakalpojuma BookSwap lietošana ir pilnīgi bez maksas. Nav abonēšanas maksas vai slēptas maksas. Vienīgās izmaksas, kas jums var rasties, ir saistītas ar grāmatu nosūtīšanu, ja izvēlaties tās sūtīt pa pastu, nevis apmainīties personīgi.</p>
              </div>
            </div>
            
            <div class="faq-item">
              <div class="faq-question">Kā es varu zināt, vai grāmata ir labā stāvoklī?</div>
              <div class="faq-answer">
                <p>Visiem lietotājiem, iekļaujot grāmatas sarakstā, ir precīzi jāapraksta to stāvoklis. Mēs izmantojam standartizētu stāvokļa vērtēšanas sistēmu (kā jauns, ļoti labs, labs, pieņemams utt.) un aicinām lietotājus augšupielādēt reālo grāmatu fotogrāfijas. Ja saņemat grāmatu, kas neatbilst aprakstītajam stāvoklim, varat par to ziņot.</p>
              </div>
            </div>
            
            <div class="faq-item">
              <div class="faq-question">Vai ir droši tikties ar svešiniekiem, lai apmainītos ar grāmatām?</div>
              <div class="faq-answer">
                <p>Mēs iesakām tikties publiskās vietās, piemēram, kafejnīcās, bibliotēkās vai grāmatnīcās dienas gaišajā laikā. Nekad nesatiekieties savās mājās vai citās privātās vietās. Ja kādreiz jūtaties neērti, vienmēr varat izvēlēties apmainīties ar grāmatām pa pastu.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- CTA Section -->
      <div class="cta-section">
        <h2 class="cta-title">Vai esat gatavs sākt maiņu?</h2>
        <p class="cta-description">Pievienojieties tūkstošiem grāmatu mīļotāju, kuri jau apmainās ar grāmatām un atklāj jaunus lasījumus, izmantojot BookSwap.</p>
        
        <div class="cta-buttons">
          <a href="signup.php" class="btn btn-primary">Reģistrējieties bez maksas</a>
          <a href="#" class="btn btn-outline">Pārlūkot grāmatas</a>
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
  <script src="auth.js"></script>
  <script src="how-it-works.js"></script>
</body>
</html>