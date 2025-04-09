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
    
    // Initialize profile button in navigation if user is logged in
    updateNavigation();
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
  