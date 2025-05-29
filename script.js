document.addEventListener('DOMContentLoaded', function() {
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
    
    // Sample data for books
    const featuredBooks = [
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
      }
    ];
    
    // Sample data for genres
    const genres = [
      { name: "Fiction", icon: "📚", count: 1245, color: "blue" },
      { name: "Mystery & Thriller", icon: "🔍", count: 856, color: "purple" },
      { name: "Science Fiction", icon: "🚀", count: 742, color: "green" },
      { name: "Fantasy", icon: "🧙", count: 693, color: "amber" },
      { name: "Romance", icon: "💕", count: 578, color: "pink" },
      { name: "Non-fiction", icon: "📝", count: 934, color: "gray" },
      { name: "Biography", icon: "👤", count: 412, color: "teal" },
      { name: "History", icon: "🏛️", count: 325, color: "red" }
    ];
    
    // Sample data for testimonials
    const testimonials = [
      {
        quote: "Esmu atklājusi tik daudz pārsteidzošu grāmatu, kuras citādi nekad nebūtu paņēmusi rokās. Kopiena ir draudzīga, un darījumi vienmēr ir godīgi!",
        name: "Dženifera K.",
        title: "Grāmatu mīļotājs no Talsiem",
        rating: 5,
        image: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80"
      },
      {
        quote: "Tā kā es daudz lasu, bet nevēlos visas grāmatas glabāt mūžīgi, BookSwap ir lielisks risinājums. Esmu ieguvusi draugus un atradusi arī retas grāmatas!",
        name: "Mihails R.",
        title: "Aizrautīgs lasītājs",
        rating: 5,
        image: "https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80"
      },
      {
        quote: "Platforma ir ļoti viegli lietojama, un man patīk, ka varu meklēt konkrētus nosaukumus vai vienkārši pārlūkot pieejamos. Grāmatu apmaiņa ir daudz labāka, nekā katru reizi pirkt jaunas",
        name: "Sofija L.",
        title: "Angļu valodas skolotājs",
        rating: 4,
        image: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80"
      }
    ];
  
    // Populate featured books
    const featuredBooksContainer = document.getElementById('featuredBooks');
    if (featuredBooksContainer) {
      featuredBooksContainer.innerHTML = '';
      
      featuredBooks.forEach(book => {
        const bookCard = createBookCard(book);
        featuredBooksContainer.appendChild(bookCard);
      });
    }
    
    // Populate genre cards
    const genresGrid = document.getElementById('genresGrid');
    if (genresGrid) {
      genresGrid.innerHTML = '';
      
      genres.forEach(genre => {
        const genreCard = createGenreCard(genre);
        genresGrid.appendChild(genreCard);
      });
    }
    
    // Populate testimonials
    const testimonialsGrid = document.getElementById('testimonialsGrid');
    if (testimonialsGrid) {
      testimonialsGrid.innerHTML = '';
      
      testimonials.forEach(testimonial => {
        const testimonialCard = createTestimonialCard(testimonial);
        testimonialsGrid.appendChild(testimonialCard);
      });
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
    
    // Create a genre card element
    function createGenreCard(genre) {
      const genreElement = document.createElement('a');
      genreElement.className = `genre-card ${genre.color}`;
      genreElement.href = `browse.html?genre=${encodeURIComponent(genre.name)}`;
      
      const contentDiv = document.createElement('div');
      contentDiv.className = 'genre-content';
      
      const infoDiv = document.createElement('div');
      infoDiv.className = 'genre-info';
      
      const iconSpan = document.createElement('span');
      iconSpan.className = 'genre-icon';
      iconSpan.textContent = genre.icon;
      
      const nameH3 = document.createElement('h3');
      nameH3.className = 'genre-name';
      nameH3.textContent = genre.name;
      
      infoDiv.appendChild(iconSpan);
      infoDiv.appendChild(nameH3);
      
      const countSpan = document.createElement('span');
      countSpan.className = 'genre-count';
      countSpan.textContent = genre.count;
      
      contentDiv.appendChild(infoDiv);
      contentDiv.appendChild(countSpan);
      
      genreElement.appendChild(contentDiv);
      
      return genreElement;
    }
    
    // Create a testimonial card element
    function createTestimonialCard(testimonial) {
      const testimonialElement = document.createElement('div');
      testimonialElement.className = 'testimonial-card';
      
      // Rating stars
      const ratingDiv = document.createElement('div');
      ratingDiv.className = 'rating';
      
      for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = i <= testimonial.rating ? 'star filled' : 'star';
        star.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
        `;
        ratingDiv.appendChild(star);
      }
      
      // Quote
      const quoteP = document.createElement('p');
      quoteP.className = 'testimonial-quote';
      quoteP.textContent = `"${testimonial.quote}"`;
      
      // Author info
      const authorDiv = document.createElement('div');
      authorDiv.className = 'testimonial-author';
      
      if (testimonial.image) {
        const img = document.createElement('img');
        img.src = testimonial.image;
        img.alt = testimonial.name;
        img.className = 'author-image';
        authorDiv.appendChild(img);
      }
      
      const authorInfoDiv = document.createElement('div');
      
      const nameH4 = document.createElement('h4');
      nameH4.className = 'author-name';
      nameH4.textContent = testimonial.name;
      
      const titleP = document.createElement('p');
      titleP.className = 'author-title';
      titleP.textContent = testimonial.title;
      
      authorInfoDiv.appendChild(nameH4);
      authorInfoDiv.appendChild(titleP);
      
      authorDiv.appendChild(authorInfoDiv);
      
      // Assemble the card
      testimonialElement.appendChild(ratingDiv);
      testimonialElement.appendChild(quoteP);
      testimonialElement.appendChild(authorDiv);
      
      return testimonialElement;
    }

    
  });
  