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

    // Add smooth scrolling for cookie navigation links
    const cookieLinks = document.querySelectorAll('.cookie-nav-links a');

    if (cookieLinks.length > 0) {
        cookieLinks.forEach(link => {
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
                    cookieLinks.forEach(link => link.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
    }

    // Highlight current section in the navigation as user scrolls
    const cookieSections = document.querySelectorAll('.cookie-section');

    function highlightCurrentSection() {
        let current = '';

        cookieSections.forEach(section => {
            const sectionTop = section.offsetTop - 120;
            const sectionHeight = section.offsetHeight;
            if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                current = '#' + section.getAttribute('id');
            }
        });

        cookieLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === current) {
                link.classList.add('active');
            }
        });
    }

    // Call the function on scroll
    window.addEventListener('scroll', highlightCurrentSection);

    // Add animation for cookie sections
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

    // Cookie consent functionality
    const acceptAllCookiesBtn = document.getElementById('accept-all-cookies');
    const necessaryCookiesOnlyBtn = document.getElementById('necessary-cookies-only');
    const customizeCookiesBtn = document.getElementById('customize-cookies');

    if (acceptAllCookiesBtn) {
        acceptAllCookiesBtn.addEventListener('click', function() {
            // Set all cookie preferences to true
            const cookiePreferences = {
                necessary: true,
                preferences: true,
                analytics: true,
                marketing: true
            };

            // Save cookie preferences
            localStorage.setItem('bookswap_cookie_preferences', JSON.stringify(cookiePreferences));
            alert('All cookies have been accepted.');
        });
    }

    if (necessaryCookiesOnlyBtn) {
        necessaryCookiesOnlyBtn.addEventListener('click', function() {
            // Set only necessary cookies to true
            const cookiePreferences = {
                necessary: true,
                preferences: false,
                analytics: false,
                marketing: false
            };

            // Save cookie preferences
            localStorage.setItem('bookswap_cookie_preferences', JSON.stringify(cookiePreferences));
            alert('Only necessary cookies will be used.');
        });
    }

    if (customizeCookiesBtn) {
        customizeCookiesBtn.addEventListener('click', function() {
            // For now, just alert - in production this would open a modal
            alert('This would open a modal to customize cookie settings. Feature coming soon!');
        });
    }
});
