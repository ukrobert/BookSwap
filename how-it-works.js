document.addEventListener('DOMContentLoaded', function() {
  // Initialize mobile menu toggle
  const mobileMenuButton = document.querySelector('.mobile-menu-button');
  const mobileMenu = document.querySelector('.mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('active');
    });
  }
  
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
  
  // Initialize profile button in navigation if user is logged in
  updateNavigation();
});
