document.addEventListener('DOMContentLoaded', function() {
  // Setup password change section toggle
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
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
  
  // Setup add book section toggle
  const addBookBtn = document.getElementById('addBookBtn');
  const cancelBookBtn = document.getElementById('cancelBookBtn');
  const addBookSection = document.getElementById('addBookSection');
  
  if (addBookBtn && addBookSection) {
    addBookBtn.addEventListener('click', function() {
      addBookSection.classList.remove('hidden');
      this.classList.add('hidden');
    });
  }
  
  if (cancelBookBtn && addBookSection && addBookBtn) {
    cancelBookBtn.addEventListener('click', function() {
      addBookSection.classList.add('hidden');
      addBookBtn.classList.remove('hidden');
      
      // Reset book form fields
      document.getElementById('bookTitle').value = '';
      document.getElementById('bookAuthor').value = '';
      document.getElementById('bookGenre').value = '';
      document.getElementById('bookLanguage').value = '';
      document.getElementById('bookYear').value = '';
      document.getElementById('bookDescription').value = '';
      document.getElementById('bookImage').value = '';
    });
  }
  
  // Auto-hide success message after 3 seconds
  const toast = document.querySelector('.toast.show');
  if (toast) {
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }
});
