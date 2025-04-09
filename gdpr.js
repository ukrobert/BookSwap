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
  
  // Add smooth scrolling for GDPR navigation links
  const gdprLinks = document.querySelectorAll('.gdpr-nav-links a');
  
  if (gdprLinks.length > 0) {
    gdprLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 100,
            behavior: 'smooth'
          });
          
          // Update URL without refreshing page
          history.pushState(null, null, targetId);
          
          // Update active link
          gdprLinks.forEach(link => link.classList.remove('active'));
          this.classList.add('active');
        }
      });
    });
  }
  
  // Highlight current section in the navigation as user scrolls
  const gdprSections = document.querySelectorAll('.gdpr-section');
  
  function highlightCurrentSection() {
    let current = '';
    
    gdprSections.forEach(section => {
      const sectionTop = section.offsetTop - 120;
      const sectionHeight = section.offsetHeight;
      if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
        current = '#' + section.getAttribute('id');
      }
    });
    
    gdprLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href') === current) {
        link.classList.add('active');
      }
    });
  }
  
  // Call the function on scroll
  window.addEventListener('scroll', highlightCurrentSection);
  
  // Add animation for GDPR sections
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });
  
  document.querySelectorAll('.fade-in').forEach(section => {
    observer.observe(section);
  });
});