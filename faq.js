document.addEventListener('DOMContentLoaded', function() {
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
  
  // FAQ Category filtering
  const categories = document.querySelectorAll('.faq-category');
  const faqItems = document.querySelectorAll('.faq-item');
  
  categories.forEach(category => {
    category.addEventListener('click', function() {
      // Remove active class from all categories
      categories.forEach(cat => cat.classList.remove('active'));
      
      // Add active class to clicked category
      this.classList.add('active');
      
      // Get the selected category
      const selectedCategory = this.getAttribute('data-category');
      
      // Show or hide FAQ items based on category
      faqItems.forEach(item => {
        if (selectedCategory === 'all' || item.getAttribute('data-category') === selectedCategory) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
  
  // Initialize mobile menu toggle
  const mobileMenuButton = document.querySelector('.mobile-menu-button');
  const mobileMenu = document.querySelector('.mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('active');
    });
  }
});
