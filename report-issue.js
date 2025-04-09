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
  
  // FAQ Accordion functionality
  const faqQuestions = document.querySelectorAll('.faq-question');
  
  faqQuestions.forEach(question => {
    question.addEventListener('click', function() {
      // Toggle active class on the question
      this.classList.toggle('active');
      
      // Get the answer element
      const answer = this.nextElementSibling;
      
      // Toggle the open class on the answer
      answer.classList.toggle('open');
    });
  });
  
  // Handle contact method change
  const contactMethodSelect = document.getElementById('contactMethod');
  const emailField = document.getElementById('emailField');
  const phoneField = document.getElementById('phoneField');
  
  if (contactMethodSelect) {
    contactMethodSelect.addEventListener('change', function() {
      if (this.value === 'email') {
        emailField.classList.remove('hidden');
        phoneField.classList.add('hidden');
        document.getElementById('emailAddress').setAttribute('required', 'required');
        document.getElementById('phoneNumber').removeAttribute('required');
      } else if (this.value === 'phone') {
        phoneField.classList.remove('hidden');
        emailField.classList.add('hidden');
        document.getElementById('phoneNumber').setAttribute('required', 'required');
        document.getElementById('emailAddress').removeAttribute('required');
      }
    });
  }
  
  // Handle issue type change to show/hide relevant fields
  const issueTypeSelect = document.getElementById('issueType');
  const userRelatedField = document.querySelector('.user-related-field');
  const bookRelatedField = document.querySelector('.book-related-field');
  
  if (issueTypeSelect) {
    issueTypeSelect.addEventListener('change', function() {
      const value = this.value;
      
      // Show user field for user conduct issues
      if (value === 'user-conduct') {
        userRelatedField.classList.remove('hidden');
        userRelatedField.querySelector('input').setAttribute('required', 'required');
      } else {
        userRelatedField.classList.add('hidden');
        userRelatedField.querySelector('input').removeAttribute('required');
      }
      
      // Show book field for book condition or failed trade issues
      if (value === 'book-condition' || value === 'failed-trade') {
        bookRelatedField.classList.remove('hidden');
        bookRelatedField.querySelector('input').setAttribute('required', 'required');
      } else {
        bookRelatedField.classList.add('hidden');
        bookRelatedField.querySelector('input').removeAttribute('required');
      }
    });
  }
  
  // Form validation and submission
  const reportForm = document.getElementById('reportForm');
  
  if (reportForm) {
    reportForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Reset error messages
      document.querySelectorAll('.form-error').forEach(error => {
        error.classList.remove('active');
      });
      
      // Validate form
      let isValid = true;
      
      // Validate issue type
      const issueType = document.getElementById('issueType').value;
      if (!issueType) {
        document.getElementById('issueTypeError').classList.add('active');
        isValid = false;
      }
      
      // Validate description
      const description = document.getElementById('issueDescription').value;
      if (!description.trim()) {
        document.getElementById('descriptionError').classList.add('active');
        isValid = false;
      }
      
      // Validate date
      const issueDate = document.getElementById('issueDate').value;
      if (!issueDate) {
        document.getElementById('dateError').classList.add('active');
        isValid = false;
      }
      
      // Validate contact method
      const contactMethod = document.getElementById('contactMethod').value;
      if (!contactMethod) {
        document.getElementById('contactMethodError').classList.add('active');
        isValid = false;
      } else {
        // Validate email or phone depending on selected contact method
        if (contactMethod === 'email') {
          const email = document.getElementById('emailAddress').value;
          if (!email || !validateEmail(email)) {
            document.getElementById('emailError').classList.add('active');
            isValid = false;
          }
        } else if (contactMethod === 'phone') {
          const phone = document.getElementById('phoneNumber').value;
          if (!phone || !validatePhone(phone)) {
            document.getElementById('phoneError').classList.add('active');
            isValid = false;
          }
        }
      }
      
      // Validate terms agreement
      const termsAgreement = document.getElementById('termsAgreement').checked;
      if (!termsAgreement) {
        document.getElementById('termsError').classList.add('active');
        isValid = false;
      }
      
      if (isValid) {
        // Simulate form submission
        submitReport()
          .then(response => {
            // Show success message
            document.getElementById('formSuccess').classList.add('active');
            
            // Reset form
            reportForm.reset();
            
            // Hide specific fields
            userRelatedField.classList.add('hidden');
            bookRelatedField.classList.add('hidden');
            phoneField.classList.add('hidden');
            emailField.classList.add('hidden');
            
            // Hide success message after 8 seconds
            setTimeout(() => {
              document.getElementById('formSuccess').classList.remove('active');
            }, 8000);
          })
          .catch(error => {
            console.error('Error submitting report:', error);
            alert('There was an error submitting your report. Please try again later.');
          });
      }
    });
  }
  
  // Email validation helper
  function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
  
  // Phone validation helper
  function validatePhone(phone) {
    const re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    return re.test(String(phone));
  }
  
  // Simulate report submission (would be replaced with actual API call)
  function submitReport() {
    return new Promise((resolve, reject) => {
      // Simulate network delay
      setTimeout(() => {
        console.log('Report submitted:', {
          issueType: document.getElementById('issueType').value,
          description: document.getElementById('issueDescription').value,
          date: document.getElementById('issueDate').value,
          contactMethod: document.getElementById('contactMethod').value,
        });
        resolve({ success: true });
      }, 1500);
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