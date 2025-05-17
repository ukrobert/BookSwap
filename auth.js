document.addEventListener('DOMContentLoaded', function() {
  // Check for current page
  const currentPath = window.location.pathname;
  const isLoginPage = currentPath.includes('login.html');
  const isSignupPage = currentPath.includes('signup.html');
  
  // Check auth status and redirect if needed
  checkAuthStatus();

  // Add profile button if user is logged in
  updateNavigation();

  // Form validation and submission
  if (isLoginPage && document.getElementById('loginForm')) {
    setupLoginForm();
  }
  
  if (isSignupPage && document.getElementById('signupForm')) {
    setupSignupForm();
    setupPasswordStrengthMeter();
  }
  
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

// Update navigation to show profile button if logged in
function updateNavigation() {
  const isLoggedIn = localStorage.getItem('bookswap_auth');
  const navLinks = document.querySelector('.nav-links');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (isLoggedIn && navLinks) {
    // Get user data
    const userData = JSON.parse(isLoggedIn);
    const userInitial = userData.name ? userData.name.charAt(0).toUpperCase() : 'U';
    const profilePhotoUrl = localStorage.getItem('bookswap_profile_photo');
    
    // Remove any existing login/signup links
    const loginLink = navLinks.querySelector('a[href="login.html"]');
    const signupLink = navLinks.querySelector('a[href="signup.html"]');
    
    if (loginLink) loginLink.remove();
    if (signupLink) signupLink.remove();
    
    // Create profile button if it doesn't exist
    if (!navLinks.querySelector('.profile-button')) {
      // Create profile button
      const profileButton = document.createElement('a');
      profileButton.href = 'profile.html';
      profileButton.className = 'nav-link profile-button';
      
      // Create photo container
      const photoContainer = document.createElement('div');
      photoContainer.className = 'profile-button-photo';
      
      if (profilePhotoUrl) {
        photoContainer.innerHTML = `<img src="${profilePhotoUrl}" alt="${userData.name}">`;
      } else {
        photoContainer.innerHTML = `
          <div class="profile-button-placeholder">
            ${userInitial}
          </div>
        `;
      }
      
      // Add photo and text to button
      profileButton.appendChild(photoContainer);
      
      const userName = document.createElement('span');
      userName.className = 'hidden sm:inline';
      userName.textContent = userData.name || 'Profile';
      profileButton.appendChild(userName);
      
      // Add button to navigation
      navLinks.appendChild(profileButton);
    }
    
    // Update mobile menu as well
    if (mobileMenu) {
      // Remove login/signup links
      const mobileLoginLink = mobileMenu.querySelector('a[href="login.html"]');
      const mobileSignupLink = mobileMenu.querySelector('a[href="signup.html"]');
      
      if (mobileLoginLink) mobileLoginLink.remove();
      if (mobileSignupLink) mobileSignupLink.remove();
      
      // Add profile link if it doesn't exist
      if (!mobileMenu.querySelector('a[href="profile.html"]')) {
        const profileLink = document.createElement('a');
        profileLink.href = 'profile.html';
        profileLink.textContent = 'Your Profile';
        mobileMenu.appendChild(profileLink);
      }
    }
  }
}

// Check if user is authenticated
function checkAuthStatus() {
  const isLoggedIn = localStorage.getItem('bookswap_auth');
  const currentPath = window.location.pathname;
  
  // Protected pages require login
  const protectedPages = ['profile.html'];
  const isProtectedPage = protectedPages.some(page => currentPath.includes(page));
  
  // Login/signup pages should redirect to profile if already logged in
  const authPages = ['login.html', 'signup.html'];
  const isAuthPage = authPages.some(page => currentPath.includes(page));
  
  if (isProtectedPage && !isLoggedIn) {
    // Redirect to login if trying to access protected page without being logged in
    window.location.href = 'login.html';
  } else if (isAuthPage && isLoggedIn) {
    // Redirect to profile if already logged in and trying to access login/signup
    window.location.href = 'profile.html';
  }
}

// Setup login form validation and submission
function setupLoginForm() {
  const loginForm = document.getElementById('loginForm');
  const loginError = document.getElementById('loginError');
  
  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // Simple validation
    if (!email || !password) {
      showError(loginError, 'Please enter both email and password');
      return;
    }
    
    // Simulate login API call
    simulateLogin(email, password)
      .then(response => {
        // Save auth data in localStorage
        localStorage.setItem('bookswap_auth', JSON.stringify({
          email: email,
          token: response.token,
          name: response.name
        }));
        
        // Redirect to profile page
        window.location.href = 'profile.html';
      })
      .catch(error => {
        showError(loginError, error);
      });
  });
}

// Setup signup form validation and submission
function setupSignupForm() {
  const signupForm = document.getElementById('signupForm');
  const signupError = document.getElementById('signupError');
  
  signupForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const agreeTerms = document.getElementById('agreeTerms').checked;
    
    // Simple validation
    if (!firstName || !lastName || !email || !password) {
      showError(signupError, 'Please fill out all required fields');
      return;
    }
    
    if (password !== confirmPassword) {
      showError(signupError, 'Passwords do not match');
      return;
    }
    
    if (!agreeTerms) {
      showError(signupError, 'You must agree to the Terms of Service and Privacy Policy');
      return;
    }
    
    // Check password strength
    const passwordStrength = evaluatePasswordStrength(password);
    if (passwordStrength < 2) {
      showError(signupError, 'Password is too weak. Please use a stronger password.');
      return;
    }
    
    // Simulate signup API call
    simulateSignup(firstName, lastName, email, password)
      .then(response => {
        // Save auth data in localStorage
        localStorage.setItem('bookswap_auth', JSON.stringify({
          email: email,
          token: response.token,
          name: `${firstName} ${lastName}`
        }));
        
        // Redirect to profile page
        window.location.href = 'profile.html';
      })
      .catch(error => {
        showError(signupError, error);
      });
  });
}

// Setup password strength meter
function setupPasswordStrengthMeter() {
  const passwordInput = document.getElementById('password');
  const strengthBar = document.querySelector('.strength-bar');
  
  passwordInput.addEventListener('input', function() {
    const password = this.value;
    const strength = evaluatePasswordStrength(password);
    
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

// Show error message
function showError(element, message) {
  element.textContent = message;
  element.classList.add('active');
  
  // Hide error after 5 seconds
  setTimeout(() => {
    element.classList.remove('active');
  }, 5000);
}

// Evaluate password strength (0-4)
function evaluatePasswordStrength(password) {
  // Initialize score
  let score = 0;
  
  // If password is empty, return score of 0
  if (password.length === 0) return score;
  
  // Award points for password length
  if (password.length > 6) score++;
  if (password.length > 10) score++;
  
  // Award points for character variety
  if (/[0-9]/.test(password)) score++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score++;
  
  // Cap the score at 4
  return Math.min(4, score);
}

// Simulate login API call (this would be replaced with actual API calls in a real app)
function simulateLogin(email, password) {
  return new Promise((resolve, reject) => {
    // Simulate network delay
    setTimeout(() => {
      // Very basic simulation - in a real app this would check with a backend service
      if (email === 'user@example.com' && password === 'password123') {
        resolve({
          token: 'simulated-jwt-token',
          name: 'John Doe'
        });
      } else {
        // For demo purposes, let's accept any login with password length >= 6
        if (password.length >= 6) {
          resolve({
            token: 'simulated-jwt-token',
            name: email.split('@')[0]
          });
        } else {
          reject('Invalid email or password');
        }
      }
    }, 800);
  });
}

// Simulate signup API call (this would be replaced with actual API calls in a real app)
function simulateSignup(firstName, lastName, email, password) {
  return new Promise((resolve, reject) => {
    // Simulate network delay
    setTimeout(() => {
      // In a real app, this would send data to your backend
      resolve({
        token: 'simulated-jwt-token',
        message: 'Account created successfully'
      });
    }, 800);
  });
}
