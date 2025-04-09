document.addEventListener('DOMContentLoaded', function() {
  // Check if user has already made a cookie choice
  const cookiePreferences = localStorage.getItem('bookswap_cookie_preferences');
  
  if (!cookiePreferences) {
    // If no cookie preferences are set, show the modal
    showCookieModal();
  }
  
  function showCookieModal() {
    // Create modal container
    const modal = document.createElement('div');
    modal.className = 'cookie-modal';
    
    // Create modal content
    modal.innerHTML = `
      <div class="cookie-modal-content">
        <div class="cookie-modal-header">
          <h2>Cookie Preferences</h2>
          <button class="cookie-modal-close">&times;</button>
        </div>
        <div class="cookie-modal-body">
          <p>We use cookies to improve your experience, analyze site traffic, and deliver personalized content. By clicking "Accept All", you consent to our use of cookies.</p>
          <p>You can learn more about how we use cookies in our <a href="cookies.html">Cookie Policy</a>.</p>
        </div>
        <div class="cookie-modal-footer">
          <button id="accept-all-cookies-modal" class="cookie-btn">Accept All</button>
          <button id="necessary-cookies-only-modal" class="cookie-btn secondary">Necessary Only</button>
          <button id="customize-cookies-modal" class="cookie-btn secondary">Customize</button>
        </div>
      </div>
    `;
    
    // Add modal to body
    document.body.appendChild(modal);
    
    // Add CSS for modal
    const style = document.createElement('style');
    style.textContent = `
      .cookie-modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
      }
      
      .cookie-modal.active {
        opacity: 1;
      }
      
      .cookie-modal-content {
        background-color: #fff;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        animation: slideUp 0.3s forwards;
      }
      
      @keyframes slideUp {
        from {
          transform: translateY(50px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
      
      .cookie-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
      }
      
      .cookie-modal-header h2 {
        margin: 0;
        font-family: 'Merriweather', serif;
        color: #59321f;
        font-size: 1.5rem;
      }
      
      .cookie-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
      }
      
      .cookie-modal-body {
        padding: 20px;
      }
      
      .cookie-modal-body p {
        margin-bottom: 15px;
        line-height: 1.6;
      }
      
      .cookie-modal-body a {
        color: #59321f;
        text-decoration: underline;
      }
      
      .cookie-modal-footer {
        padding: 15px 20px;
        text-align: right;
        border-top: 1px solid #f0f0f0;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
      }
      
      .cookie-btn {
        display: inline-block;
        background-color: #59321f;
        color: #fff;
        padding: 8px 16px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s;
        border: none;
        cursor: pointer;
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
      
      @media (max-width: 480px) {
        .cookie-modal-footer {
          justify-content: center;
        }
      }
    `;
    
    document.head.appendChild(style);
    
    // Show modal with a slight delay for smoother animation
    setTimeout(() => {
      modal.classList.add('active');
    }, 100);
    
    // Add event listeners
    modal.querySelector('.cookie-modal-close').addEventListener('click', function() {
      closeModal();
    });
    
    modal.querySelector('#accept-all-cookies-modal').addEventListener('click', function() {
      // Set all cookie preferences to true
      const cookiePreferences = {
        necessary: true,
        preferences: true,
        analytics: true,
        marketing: true
      };
      
      // Save cookie preferences
      localStorage.setItem('bookswap_cookie_preferences', JSON.stringify(cookiePreferences));
      closeModal();
    });
    
    modal.querySelector('#necessary-cookies-only-modal').addEventListener('click', function() {
      // Set only necessary cookies to true
      const cookiePreferences = {
        necessary: true,
        preferences: false,
        analytics: false,
        marketing: false
      };
      
      // Save cookie preferences
      localStorage.setItem('bookswap_cookie_preferences', JSON.stringify(cookiePreferences));
      closeModal();
    });
    
    modal.querySelector('#customize-cookies-modal').addEventListener('click', function() {
      // Redirect to cookie policy page with preferences section
      window.location.href = 'cookies.html#cookie-preferences';
      closeModal();
    });
    
    function closeModal() {
      modal.classList.remove('active');
      setTimeout(() => {
        modal.remove();
      }, 300);
    }
  }
});
