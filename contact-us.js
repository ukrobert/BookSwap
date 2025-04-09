document.addEventListener('DOMContentLoaded', function() {
  // Initialize mobile menu toggle
  const mobileMenuButton = document.querySelector('.mobile-menu-button');
  const mobileMenu = document.querySelector('.mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('active');
    });
  }

  // Contact form validation and submission
  const contactForm = document.getElementById('contactForm');
  
  if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Get form inputs
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const subject = document.getElementById('subject').value;
      const message = document.getElementById('message').value;
      
      // Reset error messages
      document.querySelectorAll('.form-error').forEach(error => {
        error.classList.remove('active');
      });
      
      // Validate form
      let isValid = true;
      
      if (!name.trim()) {
        document.getElementById('nameError').classList.add('active');
        isValid = false;
      }
      
      if (!email.trim() || !validateEmail(email)) {
        document.getElementById('emailError').classList.add('active');
        isValid = false;
      }
      
      if (!message.trim()) {
        document.getElementById('messageError').classList.add('active');
        isValid = false;
      }
      
      if (isValid) {
        // Simulate form submission
        submitContactForm(name, email, subject, message)
          .then(response => {
            // Show success message
            document.getElementById('formSuccess').classList.add('active');
            
            // Reset form
            contactForm.reset();
            
            // Hide success message after 5 seconds
            setTimeout(() => {
              document.getElementById('formSuccess').classList.remove('active');
            }, 5000);
          })
          .catch(error => {
            console.error('Error submitting form:', error);
            alert('There was an error submitting your message. Please try again later.');
          });
      }
    });
  }
  
  // Email validation helper
  function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
  
  // Simulate form submission (would be replaced with actual API call)
  function submitContactForm(name, email, subject, message) {
    return new Promise((resolve, reject) => {
      // Simulate network delay
      setTimeout(() => {
        console.log('Form submitted:', { name, email, subject, message });
        resolve({ success: true });
      }, 1000);
    });
  }
});
