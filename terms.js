
document.addEventListener('DOMContentLoaded', function() {
  // Initialize mobile menu toggle
  const mobileMenuButton = document.querySelector('.mobile-menu-button');
  const mobileMenu = document.querySelector('.mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('active');
    });
  }
  
  // Set current year in the footer
  document.getElementById('currentYear').textContent = new Date().getFullYear();
  
  // Smooth scrolling for terms navigation links
  const termsLinks = document.querySelectorAll('.terms-nav-links a');
  
  termsLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('href');
      const targetElement = document.querySelector(targetId);
      
      if (targetElement) {
        // Get the offset of the target element from the top of the document
        const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
        
        // Add some padding to account for the fixed header
        const offsetPosition = targetPosition - 100;
        
        // Scroll to the target position
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
        
        // Update URL hash without scrolling
        history.pushState(null, null, targetId);
        
        // Add active class to the current link
        termsLinks.forEach(link => link.classList.remove('active'));
        this.classList.add('active');
      }
    });
  });
  
  // Highlight current section in the navigation as user scrolls
  const termsSections = document.querySelectorAll('.terms-content .terms-section');
  
  function highlightCurrentSection() {
    let current = '';
    
    termsSections.forEach(section => {
      const sectionTop = section.offsetTop - 120;
      const sectionHeight = section.offsetHeight;
      if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
        current = '#' + section.getAttribute('id');
      }
    });
    
    termsLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href') === current) {
        link.classList.add('active');
      }
    });
  }
  
  // Call the function on scroll
  window.addEventListener('scroll', highlightCurrentSection);
  
  // Initialize profile button in navigation if user is logged in
  updateNavigation();
  
  // Make the terms navigation sticky when scrolling
  const termsNavigation = document.querySelector('.terms-navigation');
  const termsContainer = document.querySelector('.terms-container');
  
  if (termsNavigation && termsContainer) {
    const navHeight = termsNavigation.offsetHeight;
    const containerHeight = termsContainer.offsetHeight;
    
    window.addEventListener('scroll', function() {
      const scrollPosition = window.scrollY;
      const containerTop = termsContainer.offsetTop;
      
      if (scrollPosition > containerTop && scrollPosition < containerTop + containerHeight - navHeight - 100) {
        termsNavigation.classList.add('sticky');
      } else {
        termsNavigation.classList.remove('sticky');
      }
    });
  }
  
  // Add animation for terms sections
  const termsContentSections = document.querySelectorAll('.terms-content .terms-section');
  
  if (termsContentSections.length > 0) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('fade-in');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    
    termsContentSections.forEach(section => {
      observer.observe(section);
    });
  }
});

// Function to update navigation based on authentication status
function updateNavigation() {
  // Check if user is logged in (this is a placeholder - replace with your actual auth check)
  const isLoggedIn = localStorage.getItem('bookswap_user') !== null;
  
  const navActions = document.querySelector('.nav-actions');
  const mobileAuthLinks = document.querySelector('.mobile-auth-links');
  
  if (isLoggedIn && navActions) {
    // Get user info
    const userJson = localStorage.getItem('bookswap_user');
    const user = JSON.parse(userJson);
    
    // Clear existing auth buttons
    navActions.innerHTML = '';
    
    // Create profile button
    const profileButton = document.createElement('a');
    profileButton.href = 'profile.html';
    profileButton.className = 'profile-button';
    
    if (user.profileImage) {
      // If user has profile image
      const img = document.createElement('img');
      img.src = user.profileImage;
      img.alt = 'Profile';
      profileButton.appendChild(img);
    } else {
      // If no profile image, show initials
      const initials = document.createElement('span');
      initials.className = 'profile-initials';
      initials.textContent = getInitials(user.name || user.email || 'User');
      profileButton.appendChild(initials);
    }
    
    navActions.appendChild(profileButton);
    
    // Add mobile menu profile link if it exists
    if (mobileAuthLinks) {
      mobileAuthLinks.innerHTML = `
        <a href="profile.html" class="btn btn-link">My Profile</a>
        <button id="logoutBtn" class="btn btn-outline">Sign Out</button>
      `;
      
      // Add logout functionality
      document.getElementById('logoutBtn').addEventListener('click', function() {
        localStorage.removeItem('bookswap_user');
        window.location.href = 'index.html';
      });
    }
  }
}

// Helper function to get initials from name
function getInitials(name) {
  return name
    .split(' ')
    .map(part => part.charAt(0))
    .join('')
    .toUpperCase()
    .substring(0, 2);
}