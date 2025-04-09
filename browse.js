document.addEventListener('DOMContentLoaded', function() {
    // Sample data for books
    const books = [
      {
        id: '1',
        title: 'The Silent Patient',
        author: 'Alex Michaelides',
        coverImage: 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80',
        genre: 'Thriller',
        condition: 'Like New',
        listedBy: 'Emma W.',
        userId: 'user1'
      },
      {
        id: '2',
        title: 'Circe',
        author: 'Madeline Miller',
        coverImage: 'https://images.unsplash.com/photo-1641154748135-8032a61a3f4a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=415&q=80',
        genre: 'Fantasy',
        condition: 'Good',
        listedBy: 'Marcus T.',
        userId: 'user2'
      },
      {
        id: '3',
        title: 'Educated',
        author: 'Tara Westover',
        coverImage: 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=512&q=80',
        genre: 'Memoir',
        condition: 'Very Good',
        listedBy: 'Sarah L.',
        userId: 'user3'
      },
      {
        id: '4',
        title: 'The Dutch House',
        author: 'Ann Patchett',
        coverImage: 'https://images.unsplash.com/photo-1531072901881-d644216d4bf9?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80',
        genre: 'Fiction',
        condition: 'Good',
        listedBy: 'James R.',
        userId: 'user4'
      },
      {
        id: '5',
        title: 'Normal People',
        author: 'Sally Rooney',
        coverImage: 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80',
        genre: 'Romance',
        condition: 'Very Good',
        listedBy: 'Thomas K.',
        userId: 'user5'
      },
      {
        id: '6',
        title: 'Where the Crawdads Sing',
        author: 'Delia Owens',
        coverImage: 'https://images.unsplash.com/photo-1629992101753-56d196c8aabb?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=390&q=80',
        genre: 'Fiction',
        condition: 'Good',
        listedBy: 'Alice B.',
        userId: 'user6'
      },
      {
        id: '7',
        title: 'Atomic Habits',
        author: 'James Clear',
        coverImage: 'https://images.unsplash.com/photo-1605153864431-a2795e1aa532?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=391&q=80',
        genre: 'Self-Help',
        condition: 'Like New',
        listedBy: 'Daniel M.',
        userId: 'user7'
      },
      {
        id: '8',
        title: 'The Song of Achilles',
        author: 'Madeline Miller',
        coverImage: 'https://images.unsplash.com/photo-1633477189729-9290b3261d0a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=722&q=80',
        genre: 'Fantasy',
        condition: 'Good',
        listedBy: 'Samantha P.',
        userId: 'user8'
      }
    ];
  
    // Get DOM elements
    const booksGrid = document.getElementById('booksGrid');
    const noBooks = document.getElementById('noBooks');
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const genreSelect = document.getElementById('genreSelect');
    const conditionSelect = document.getElementById('conditionSelect');
    const genreSelectMobile = document.getElementById('genreSelectMobile');
    const conditionSelectMobile = document.getElementById('conditionSelectMobile');
    const filterButton = document.getElementById('filterButton');
    const filterModal = document.getElementById('filterModal');
    const closeFilterModal = document.getElementById('closeFilterModal');
    const applyFiltersButton = document.getElementById('applyFiltersButton');
    const resetFiltersButton = document.getElementById('resetFiltersButton');
    const resetAllButton = document.getElementById('resetAllButton');
    
    // Current filter state
    let currentSearchTerm = '';
    let currentGenre = 'all';
    let currentCondition = 'all';
    
    // Check URL parameters for pre-selected genre
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('genre')) {
      const genreParam = urlParams.get('genre');
      currentGenre = genreParam;
      genreSelect.value = genreParam;
      genreSelectMobile.value = genreParam;
    }
    
    // Initialize books display
    displayBooks();
    
    // Event listeners
    searchInput.addEventListener('input', function() {
      currentSearchTerm = this.value.toLowerCase();
      displayBooks();
    });
    
    searchInputMobile.addEventListener('input', function() {
      currentSearchTerm = this.value.toLowerCase();
      displayBooks();
    });
    
    genreSelect.addEventListener('change', function() {
      currentGenre = this.value;
      displayBooks();
    });
    
    conditionSelect.addEventListener('change', function() {
      currentCondition = this.value;
      displayBooks();
    });
    
    // Mobile filter modal
    filterButton.addEventListener('click', function() {
      filterModal.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    });
    
    closeFilterModal.addEventListener('click', function() {
      filterModal.classList.remove('active');
      document.body.style.overflow = 'auto'; // Re-enable scrolling
    });
    
    applyFiltersButton.addEventListener('click', function() {
      currentGenre = genreSelectMobile.value;
      currentCondition = conditionSelectMobile.value;
      filterModal.classList.remove('active');
      document.body.style.overflow = 'auto'; // Re-enable scrolling
      
      // Sync desktop selects
      genreSelect.value = currentGenre;
      conditionSelect.value = currentCondition;
      
      displayBooks();
    });
    
    resetFiltersButton.addEventListener('click', function() {
      genreSelectMobile.value = 'all';
      conditionSelectMobile.value = 'all';
    });
    
    resetAllButton.addEventListener('click', function() {
      currentSearchTerm = '';
      currentGenre = 'all';
      currentCondition = 'all';
      
      searchInput.value = '';
      searchInputMobile.value = '';
      genreSelect.value = 'all';
      conditionSelect.value = 'all';
      genreSelectMobile.value = 'all';
      conditionSelectMobile.value = 'all';
      
      displayBooks();
    });
    
    // Display books based on current filter
    function displayBooks() {
      // Filter books
      const filteredBooks = books.filter(book => {
        const matchesSearch = 
          book.title.toLowerCase().includes(currentSearchTerm) || 
          book.author.toLowerCase().includes(currentSearchTerm);
        
        const matchesGenre = currentGenre === 'all' || book.genre === currentGenre;
        const matchesCondition = currentCondition === 'all' || book.condition === currentCondition;
        
        return matchesSearch && matchesGenre && matchesCondition;
      });
      
      // Clear the current books
      booksGrid.innerHTML = '';
      
      // Show books or no results message
      if (filteredBooks.length > 0) {
        filteredBooks.forEach(book => {
          const bookCard = createBookCard(book);
          booksGrid.appendChild(bookCard);
        });
        booksGrid.style.display = 'grid';
        noBooks.style.display = 'none';
      } else {
        booksGrid.style.display = 'none';
        noBooks.style.display = 'block';
      }
    }
    
    // Create a book card element
    function createBookCard(book) {
      const bookElement = document.createElement('div');
      bookElement.className = 'book-card';
      
      // Create the cover with wishlist button
      const coverDiv = document.createElement('div');
      coverDiv.className = 'book-cover';
      
      if (book.coverImage) {
        const img = document.createElement('img');
        img.src = book.coverImage;
        img.alt = `Cover of ${book.title}`;
        coverDiv.appendChild(img);
      } else {
        const fallbackCover = document.createElement('div');
        fallbackCover.className = 'fallback-cover';
        fallbackCover.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
          </svg>
        `;
        coverDiv.appendChild(fallbackCover);
      }
      
      const wishlistBtn = document.createElement('button');
      wishlistBtn.className = 'wishlist-button';
      wishlistBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
      `;
      wishlistBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const heartIcon = this.querySelector('svg');
        if (heartIcon.getAttribute('fill') === 'none') {
          heartIcon.setAttribute('fill', 'currentColor');
          heartIcon.setAttribute('stroke', 'currentColor');
          heartIcon.style.color = 'var(--color-burgundy)';
        } else {
          heartIcon.setAttribute('fill', 'none');
          heartIcon.setAttribute('stroke', 'currentColor');
          heartIcon.style.color = '';
        }
      });
      
      coverDiv.appendChild(wishlistBtn);
      
      // Create book info
      const infoDiv = document.createElement('div');
      infoDiv.className = 'book-info';
      
      // Title and author
      const titleH3 = document.createElement('h3');
      titleH3.className = 'book-title';
      titleH3.textContent = book.title;
      
      const authorP = document.createElement('p');
      authorP.className = 'book-author';
      authorP.textContent = `by ${book.author}`;
      
      // Tags
      const tagsDiv = document.createElement('div');
      tagsDiv.className = 'book-tags';
      
      const genreSpan = document.createElement('span');
      genreSpan.className = 'book-tag';
      genreSpan.textContent = book.genre;
      
      const conditionSpan = document.createElement('span');
      conditionSpan.className = 'book-tag';
      conditionSpan.textContent = book.condition;
      
      tagsDiv.appendChild(genreSpan);
      tagsDiv.appendChild(conditionSpan);
      
      // Footer with owner and trade button
      const footerDiv = document.createElement('div');
      footerDiv.className = 'book-footer';
      
      const ownerLink = document.createElement('a');
      ownerLink.className = 'book-owner';
      ownerLink.href = `user.html?id=${book.userId}`;
      ownerLink.textContent = `Listed by ${book.listedBy}`;
      
      const tradeBtn = document.createElement('a');
      tradeBtn.className = 'btn btn-primary';
      tradeBtn.href = `book.html?id=${book.id}`;
      tradeBtn.textContent = 'Request Trade';
      tradeBtn.style.fontSize = '0.75rem';
      
      footerDiv.appendChild(ownerLink);
      footerDiv.appendChild(tradeBtn);
      
      // Assemble the card
      infoDiv.appendChild(titleH3);
      infoDiv.appendChild(authorP);
      infoDiv.appendChild(tagsDiv);
      infoDiv.appendChild(footerDiv);
      
      bookElement.appendChild(coverDiv);
      bookElement.appendChild(infoDiv);
      
      // Make the whole card clickable
      bookElement.addEventListener('click', function() {
        window.location.href = `book.html?id=${book.id}`;
      });
      
      return bookElement;
    }
  });
  