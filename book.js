document.addEventListener('DOMContentLoaded', function() {
    // Sample book details data
    const bookDetails = {
      id: '1',
      title: 'The Silent Patient',
      author: 'Alex Michaelides',
      coverImage: 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80',
      genre: 'Thriller',
      language: 'English',
      year: 2019,
      condition: 'Like New',
      description: `Alicia Berenson's life is seemingly perfect. A famous painter married to an in-demand fashion photographer, she lives in a grand house with big windows overlooking a park in one of London's most desirable areas. One evening her husband Gabriel returns home late from a fashion shoot, and Alicia shoots him five times in the face, and then never speaks another word.
  
  Alicia's refusal to talk, or give any kind of explanation, turns a domestic tragedy into something far grander, a mystery that captures the public imagination and casts Alicia into notoriety. The price of her art skyrockets, and she, the silent patient, is hidden away from the tabloids and spotlight at the Grove, a secure forensic unit in North London.
  
  Theo Faber is a criminal psychotherapist who has waited a long time for the opportunity to work with Alicia. His determination to get her to talk and unravel the mystery of why she shot her husband takes him down a twisting path into his own motivations—a search for the truth that threatens to consume him...`,
      listedBy: 'Emma W.',
      userId: 'user1',
      userImage: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80',
      listedDate: '2 weeks ago',
      userRating: 4.5,
      tradeCount: 27
    };
  
    // Sample user books for exchange
    const userBooks = [
      { id: 'b1', title: 'To Kill a Mockingbird', author: 'Harper Lee' },
      { id: 'b2', title: '1984', author: 'George Orwell' },
      { id: 'b3', title: 'The Great Gatsby', author: 'F. Scott Fitzgerald' }
    ];
  
    // Sample similar books
    const similarBooks = [
      { 
        id: 's1', 
        title: 'Gone Girl', 
        author: 'Gillian Flynn', 
        coverImage: 'https://images.unsplash.com/photo-1629992101753-56d196c8aabb?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=390&q=80',
        genre: 'Thriller'
      },
      { 
        id: 's2', 
        title: 'The Girl on the Train', 
        author: 'Paula Hawkins', 
        coverImage: 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=876&q=80',
        genre: 'Thriller'
      },
      { 
        id: 's3', 
        title: 'Sharp Objects', 
        author: 'Gillian Flynn', 
        coverImage: 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80',
        genre: 'Mystery'
      }
    ];
  
    // Get DOM elements
    const bookCoverWrapper = document.getElementById('bookCoverWrapper');
    const bookTitle = document.getElementById('bookTitle');
    const bookAuthor = document.getElementById('bookAuthor');
    const bookTags = document.getElementById('bookTags');
    const bookDescription = document.getElementById('bookDescription');
    const ownerCard = document.getElementById('ownerCard');
    const similarBooksContainer = document.getElementById('similarBooks');
    const requestTradeBtn = document.getElementById('requestTradeBtn');
    const wishlistBtn = document.getElementById('wishlistBtn');
    const wishlistBtnText = document.getElementById('wishlistBtnText');
    const tradeModal = document.getElementById('tradeModal');
    const closeTradeModal = document.getElementById('closeTradeModal');
    const tradeBookTitle = document.getElementById('tradeBookTitle');
    const tradeSelect = document.getElementById('tradeSelect');
    const tradeMessage = document.getElementById('tradeMessage');
    const sendTradeRequestBtn = document.getElementById('sendTradeRequestBtn');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastClose = document.getElementById('toastClose');
    
    // Track wishlist state
    let isWishlisted = false;
  
    // Get book ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const bookId = urlParams.get('id');
    
    // In a real app, we would fetch the book details based on the ID
    // For this demo, we'll just use the sample data
  
    // Populate the book details
    function populateBookDetails() {
      // Set the page title
      document.title = `${bookDetails.title} - BookSwap`;
      
      // Book cover
      if (bookDetails.coverImage) {
        const img = document.createElement('img');
        img.src = bookDetails.coverImage;
        img.alt = `Cover of ${bookDetails.title}`;
        img.className = 'book-cover';
        bookCoverWrapper.appendChild(img);
      } else {
        const fallbackCover = document.createElement('div');
        fallbackCover.className = 'book-cover-fallback';
        fallbackCover.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
          </svg>
        `;
        bookCoverWrapper.appendChild(fallbackCover);
      }
      
      // Book title and author
      bookTitle.textContent = bookDetails.title;
      bookAuthor.textContent = `by ${bookDetails.author}`;
      
      // Book tags
      bookTags.innerHTML = '';
      
      // Genre tag
      const genreTag = document.createElement('span');
      genreTag.className = 'book-tag';
      genreTag.textContent = bookDetails.genre;
      bookTags.appendChild(genreTag);
      
      // Language tag
      const languageTag = document.createElement('span');
      languageTag.className = 'book-tag';
      languageTag.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 8l5 5 5-5"></path>
          <path d="M12 16V8"></path>
        </svg>
        ${bookDetails.language}
      `;
      bookTags.appendChild(languageTag);
      
      // Year tag
      const yearTag = document.createElement('span');
      yearTag.className = 'book-tag';
      yearTag.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        ${bookDetails.year}
      `;
      bookTags.appendChild(yearTag);
      
      // Condition tag
      const conditionTag = document.createElement('span');
      conditionTag.className = 'book-tag';
      conditionTag.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        ${bookDetails.condition}
      `;
      bookTags.appendChild(conditionTag);
      
      // Book description
      bookDescription.textContent = bookDetails.description;
      
      // Owner card
      ownerCard.innerHTML = `
        <div class="owner-info">
          <img src="${bookDetails.userImage}" alt="${bookDetails.listedBy}" class="owner-image">
          <div class="owner-details">
            <h3 class="owner-name">${bookDetails.listedBy}</h3>
            <div class="owner-stats">
              <div class="stat">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>${bookDetails.userRating}</span>
              </div>
              <div class="stat">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                  <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span>${bookDetails.tradeCount} trades</span>
              </div>
            </div>
          </div>
          <button class="btn btn-outline owner-message">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            Message
          </button>
        </div>
      `;
      
      // Similar books
      similarBooksContainer.innerHTML = '';
      
      similarBooks.forEach(book => {
        const bookElement = document.createElement('a');
        bookElement.className = 'similar-book';
        bookElement.href = `book.html?id=${book.id}`;
        
        const coverDiv = document.createElement('div');
        coverDiv.className = 'similar-cover';
        
        if (book.coverImage) {
          const img = document.createElement('img');
          img.src = book.coverImage;
          img.alt = `Cover of ${book.title}`;
          coverDiv.appendChild(img);
        } else {
          coverDiv.innerHTML = `
            <div class="book-cover-fallback">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
              </svg>
            </div>
          `;
        }
        
        const infoDiv = document.createElement('div');
        infoDiv.className = 'similar-info';
        infoDiv.innerHTML = `
          <h3 class="similar-title">${book.title}</h3>
          <p class="similar-author">${book.author}</p>
          <span class="similar-genre">${book.genre}</span>
        `;
        
        bookElement.appendChild(coverDiv);
        bookElement.appendChild(infoDiv);
        similarBooksContainer.appendChild(bookElement);
      });
      
      // Set the book title in trade modal
      tradeBookTitle.textContent = bookDetails.title;
      
      // Populate trade select
      tradeSelect.innerHTML = '<option value="">Choose a book</option>';
      userBooks.forEach(book => {
        const option = document.createElement('option');
        option.value = book.id;
        option.textContent = `${book.title} by ${book.author}`;
        tradeSelect.appendChild(option);
      });
    }
    
    // Initialize the page
    populateBookDetails();
    
    // Event listeners
    requestTradeBtn.addEventListener('click', function() {
      tradeModal.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    });
    
    closeTradeModal.addEventListener('click', function() {
      tradeModal.classList.remove('active');
      document.body.style.overflow = 'auto'; // Re-enable scrolling
    });
    
    wishlistBtn.addEventListener('click', function() {
      isWishlisted = !isWishlisted;
      const wishlistIcon = wishlistBtn.querySelector('.wishlist-icon');
      
      if (isWishlisted) {
        wishlistIcon.classList.add('filled');
        wishlistIcon.setAttribute('fill', 'currentColor');
        wishlistBtnText.textContent = 'Wishlisted';
        showToast('Book added to your wishlist');
      } else {
        wishlistIcon.classList.remove('filled');
        wishlistIcon.setAttribute('fill', 'none');
        wishlistBtnText.textContent = 'Add to Wishlist';
        showToast('Book removed from your wishlist');
      }
    });
    
    sendTradeRequestBtn.addEventListener('click', function() {
      const selectedBook = tradeSelect.value;
      
      if (!selectedBook) {
        showToast('Please select a book to trade', 'error');
        return;
      }
      
      // Close the modal
      tradeModal.classList.remove('active');
      document.body.style.overflow = 'auto'; // Re-enable scrolling
      
      // Reset the form
      tradeSelect.value = '';
      tradeMessage.value = '';
      
      // Show success message
      showToast(`Trade request for "${bookDetails.title}" has been sent to ${bookDetails.listedBy}`);
    });
    
    toastClose.addEventListener('click', function() {
      hideToast();
    });
    
    // Toast functionality
    function showToast(message, type = 'success') {
      toastMessage.textContent = message;
      const toastIcon = document.querySelector('.toast-icon');
      
      if (type === 'error') {
        toastIcon.classList.remove('success');
        toastIcon.classList.add('error');
        toastIcon.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        `;
      } else {
        toastIcon.classList.remove('error');
        toastIcon.classList.add('success');
        toastIcon.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        `;
      }
      
      toast.classList.add('show');
      
      // Auto hide after 3 seconds
      setTimeout(function() {
        hideToast();
      }, 3000);
    }
    
    function hideToast() {
      toast.classList.remove('show');
    }
    
    // Set current year in the footer
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    
    // Mobile menu toggle
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuButton && mobileMenu) {
      mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('active');
      });
    }
  });
  