document.addEventListener('DOMContentLoaded', function() {
  // Check if user is authenticated
  const authData = localStorage.getItem('bookswap_auth');
  
  if (!authData) {
    // If not logged in, redirect to login page
    window.location.href = 'login.php';
    return;
  }
  
  const userData = JSON.parse(authData);
  
  // Setup logout functionality
  setupLogout();
  
  // Load user profile data
  loadUserProfile();
  
  // Setup profile form
  setupProfileForm();
  
  // Setup profile picture upload
  setupProfilePhotoUpload();
  
  // Setup password change section toggle
  setupPasswordSection();
  
  // Setup toast functionality
  setupToast();
});

// Setup logout functionality
function setupLogout() {
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutMobileBtn = document.getElementById('logoutMobileBtn');
  
  const logoutHandler = function(e) {
    e.preventDefault();
    
    // Clear auth data from localStorage
    localStorage.removeItem('bookswap_auth');
    
    // Redirect to login page
    window.location.href = 'login.php';
  };
  
  if (logoutBtn) logoutBtn.addEventListener('click', logoutHandler);
  if (logoutMobileBtn) logoutMobileBtn.addEventListener('click', logoutHandler);
}

// Load user profile data from localStorage or mock data
function loadUserProfile() {
  // In a real app, this would fetch data from an API
  // For demo purposes, we'll use mock data and localStorage
  
  const authData = JSON.parse(localStorage.getItem('bookswap_auth'));
  
  // Get saved profile data or use defaults
  const profileData = JSON.parse(localStorage.getItem('bookswap_profile')) || {
    firstName: authData.name ? authData.name.split(' ')[0] : '',
    lastName: authData.name ? authData.name.split(' ')[1] || '' : '',
    email: authData.email || '',
    location: 'San Francisco, CA',
    bio: 'Book enthusiast with a passion for mystery novels and classic literature.',
    profilePhoto: localStorage.getItem('bookswap_profile_photo') || ''
  };
  
  // Populate form fields
  const firstNameInput = document.getElementById('firstName');
  const lastNameInput = document.getElementById('lastName');
  const emailInput = document.getElementById('email');
  const locationInput = document.getElementById('location');
  const bioInput = document.getElementById('bio');
  
  if (firstNameInput) firstNameInput.value = profileData.firstName;
  if (lastNameInput) lastNameInput.value = profileData.lastName;
  if (emailInput) emailInput.value = profileData.email;
  if (locationInput) locationInput.value = profileData.location;
  if (bioInput) bioInput.value = profileData.bio;
  
  // Load profile photo if exists
  if (profileData.profilePhoto) {
    displayProfilePhoto(profileData.profilePhoto);
  }
}

// Setup profile form submission
function setupProfileForm() {
  const saveProfileBtn = document.getElementById('saveProfileBtn');
  const profileForm = document.getElementById('profileForm');
  
  if (saveProfileBtn && profileForm) {
    saveProfileBtn.addEventListener('click', function() {
      // Get form data
      const firstName = document.getElementById('firstName').value;
      const lastName = document.getElementById('lastName').value;
      const email = document.getElementById('email').value;
      const location = document.getElementById('location').value;
      const bio = document.getElementById('bio').value;
      
      // Simple validation
      if (!firstName || !lastName) {
        showToast('Please fill out all required fields', 'error');
        return;
      }
      
      // Save to localStorage (in a real app, this would be an API call)
      const profileData = {
        firstName,
        lastName,
        email,
        location,
        bio,
        profilePhoto: localStorage.getItem('bookswap_profile_photo') || ''
      };
      
      localStorage.setItem('bookswap_profile', JSON.stringify(profileData));
      
      // Update auth data with new name
      const authData = JSON.parse(localStorage.getItem('bookswap_auth'));
      authData.name = `${firstName} ${lastName}`;
      localStorage.setItem('bookswap_auth', JSON.stringify(authData));
      
      // Show success message
      showToast('Profile updated successfully');
    });
  }
}

// Setup profile photo upload
function setupProfilePhotoUpload() {
  const profilePhotoInput = document.getElementById('profilePhotoInput');
  const removePhotoBtn = document.getElementById('removePhotoBtn');
  
  if (profilePhotoInput) {
    profilePhotoInput.addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          const photoDataUrl = e.target.result;
          displayProfilePhoto(photoDataUrl);
          
          // Save to localStorage
          localStorage.setItem('bookswap_profile_photo', photoDataUrl);
          
          // Update profile data
          updateProfilePhotoInData(photoDataUrl);
        };
        
        reader.readAsDataURL(this.files[0]);
      }
    });
  }
  
  if (removePhotoBtn) {
    removePhotoBtn.addEventListener('click', function() {
      // Remove the profile photo
      const photoPreview = document.getElementById('profilePhotoPreview');
      
      if (photoPreview) {
        photoPreview.innerHTML = `
          <div class="profile-photo-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
        `;
      }
      
      // Clear from localStorage
      localStorage.removeItem('bookswap_profile_photo');
      
      // Update profile data
      updateProfilePhotoInData('');
    });
  }
}

// Display profile photo in the UI
function displayProfilePhoto(photoUrl) {
  const photoPreview = document.getElementById('profilePhotoPreview');
  
  if (photoPreview) {
    photoPreview.innerHTML = `<img src="${photoUrl}" alt="Profile Photo">`;
  }
}

// Update profile photo in profile data
function updateProfilePhotoInData(photoUrl) {
  const profileData = JSON.parse(localStorage.getItem('bookswap_profile')) || {};
  profileData.profilePhoto = photoUrl;
  localStorage.setItem('bookswap_profile', JSON.stringify(profileData));
}

// Setup password change section
function setupPasswordSection() {
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
  const savePasswordBtn = document.getElementById('savePasswordBtn');
  const passwordChangeSection = document.getElementById('passwordChangeSection');
  
  if (changePasswordBtn && passwordChangeSection) {
    changePasswordBtn.addEventListener('click', function() {
      passwordChangeSection.classList.remove('hidden');
      this.classList.add('hidden');
    });
  }
  
  if (cancelPasswordBtn && passwordChangeSection && changePasswordBtn) {
    cancelPasswordBtn.addEventListener('click', function() {
      passwordChangeSection.classList.add('hidden');
      changePasswordBtn.classList.remove('hidden');
      
      // Reset password fields
      document.getElementById('currentPassword').value = '';
      document.getElementById('newPassword').value = '';
      document.getElementById('confirmNewPassword').value = '';
    });
  }
  
  if (savePasswordBtn) {
    savePasswordBtn.addEventListener('click', function() {
      const currentPassword = document.getElementById('currentPassword').value;
      const newPassword = document.getElementById('newPassword').value;
      const confirmNewPassword = document.getElementById('confirmNewPassword').value;
      
      // Simple validation
      if (!currentPassword || !newPassword || !confirmNewPassword) {
        showToast('Please fill out all password fields', 'error');
        return;
      }
      
      if (newPassword !== confirmNewPassword) {
        showToast('New passwords do not match', 'error');
        return;
      }
      
      // In a real app, this would call an API to change the password
      // For demo purposes, we'll just show success
      
      // Hide password change section and show button
      passwordChangeSection.classList.add('hidden');
      changePasswordBtn.classList.remove('hidden');
      
      // Reset password fields
      document.getElementById('currentPassword').value = '';
      document.getElementById('newPassword').value = '';
      document.getElementById('confirmNewPassword').value = '';
      
      // Show success message
      showToast('Password changed successfully');
    });
  }
}

// Setup toast notification
function setupToast() {
  const toast = document.getElementById('successToast');
  const closeToast = document.getElementById('closeToast');
  
  if (closeToast && toast) {
    closeToast.addEventListener('click', function() {
      toast.classList.remove('show');
    });
  }
}

// Show toast notification
function showToast(message, type = 'success') {
  const toast = document.getElementById('successToast');
  const toastMessage = document.getElementById('toastMessage');
  const toastIcon = document.querySelector('.toast-icon');
  
  if (toast && toastMessage) {
    // Set message
    toastMessage.textContent = message;
    
    // Set icon based on type
    if (toastIcon) {
      if (type === 'error') {
        toastIcon.classList.remove('success');
        toastIcon.classList.add('error');
        toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
      } else {
        toastIcon.classList.remove('error');
        toastIcon.classList.add('success');
        toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
      }
    }
    
    // Show toast
    toast.classList.add('show');
    
    // Hide toast after 3 seconds
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }
}
