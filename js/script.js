// JavaScript Feature 1: Mobile Menu Toggle 
document.addEventListener('DOMContentLoaded', function() {
    function setupMobileMenu() {
        const mainNav = document.querySelector('.main-nav .container');
        const navLinks = document.querySelector('.nav-links');
        
        if (!mainNav || !navLinks) return;
        
        let mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        if (!mobileMenuBtn) {
            mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.className = 'mobile-menu-btn';
            mobileMenuBtn.innerHTML = '☰';
            mobileMenuBtn.setAttribute('aria-label', 'Toggle navigation menu');
            mainNav.prepend(mobileMenuBtn);
        }
        
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.innerHTML = navLinks.classList.contains('active') ? '✕' : '☰';
        });
        
        navLinks.addEventListener('click', function(e) {
            if (e.target.tagName === 'A') {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '☰';
            }
        });
    }
    
    setupMobileMenu();

    const savedSize = localStorage.getItem('textSize');
    if (savedSize) {
        document.body.style.fontSize = savedSize + 'px';
    }
    
    setActiveNav();
});

// JavaScript Feature 2: Form Validation (for contact form)
function validateForm(formData) {
    const errors = [];
    
    if (!formData.get('name')?.trim()) {
        errors.push('Name is required');
    }
    
    if (!formData.get('email')?.trim()) {
        errors.push('Email is required');
    } else if (!isValidEmail(formData.get('email'))) {
        errors.push('Valid email is required');
    }
    
    if (!formData.get('message')?.trim()) {
        errors.push('Message is required');
    }
    
    return errors;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// JavaScript Feature 3: Search Functionality
document.querySelector('.search-bar button').addEventListener('click', function() {
    const searchTerm = document.querySelector('.search-bar input').value.trim();
    if (searchTerm) {
        performSearch(searchTerm);
    }
});

document.querySelector('.search-bar input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const searchTerm = this.value.trim();
        if (searchTerm) {
            performSearch(searchTerm);
        }
    }
});

function performSearch(term) {
    document.body.classList.add('loading');
    
    setTimeout(() => {
        document.body.classList.remove('loading');
        alert(`Searching for: "${term}"\n\nThis would display search results in a real implementation.`);
    }, 1000);
}

// JavaScript Feature 4: Active Navigation Highlight
function setActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (currentPage === linkPage) {
            link.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const savedSize = localStorage.getItem('textSize');
    if (savedSize) {
        document.body.style.fontSize = savedSize + 'px';
    }
    
    setActiveNav();
});

// JavaScript Feature 5: Smooth Scrolling for Anchor Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});




// ------------------------------------------------Homepage Specific JavaScript-------------------------------------------------------------

// Emergency Alert Functionality
function showEmergencyAlert(message) {
    const alertBanner = document.getElementById('emergencyAlert');
    const alertMessage = alertBanner.querySelector('.alert-message');
    alertMessage.textContent = message;
    alertBanner.classList.add('show');
}

function closeAlert() {
    document.getElementById('emergencyAlert').classList.remove('show');
}

// Animated Statistics Counter
function animateStatistics() {
    const stats = [
        { id: 'stat1', target: 247, duration: 2000 },
        { id: 'stat2', target: 18563, duration: 2500 },
        { id: 'stat3', target: 42, duration: 1500 },
        { id: 'stat4', target: 124587, duration: 3000 }
    ];

    stats.forEach(stat => {
        const element = document.getElementById(stat.id);
        const increment = stat.target / (stat.duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= stat.target) {
                element.textContent = stat.target.toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 16);
    });
}

// Service Card Interactions
document.querySelectorAll('.service-btn').forEach(button => {
    button.addEventListener('click', function() {
        const serviceName = this.closest('.service-card').querySelector('h3').textContent;
        alert(`Starting application for: ${serviceName}\n\nThis would redirect to the service application page.`);
    });
});

// News Functions
function viewAllNews() {
    window.location.href = 'news.html';
}

function subscribeAlerts() {
    const email = prompt('Enter your email to subscribe to alerts:');
    if (email && isValidEmail(email)) {
        alert('Thank you for subscribing! You will receive email alerts.');
    } else if (email) {
        alert('Please enter a valid email address.');
    }
}

// Initialize homepage features when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    // Show emergency alert (demo)
    setTimeout(() => {
        showEmergencyAlert('Weather Advisory: Severe storm warning in effect until 8 PM tonight.');
    }, 1000);

    // Start statistics animation when in viewport
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStatistics();
                observer.unobserve(entry.target);
            }
        });
    });

    observer.observe(document.querySelector('.stats-section'));
});


// -------------------------------------------------------------About Page Specific JavaScript---------------------------------------------------------

// Initialize about page features
document.addEventListener('DOMContentLoaded', function() {
    // Set active navigation
    setActiveNav();
});


// -----------------------------------------------------------Services Page Specific JavaScript---------------------------------------------------------

// Services Page Specific JavaScript
function searchServices() {
    const searchTerm = document.getElementById('servicesSearchInput').value.toLowerCase().trim();
    filterServices(searchTerm);
}

function filterServices(searchTerm = '') {
    const serviceCards = document.querySelectorAll('.service-card');
    let visibleCount = 0;
    
    serviceCards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.card-description').textContent.toLowerCase();
        
        const searchMatch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
        
        if (searchMatch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show message if no results
    showServiceSearchResults(visibleCount, searchTerm);
}

function showServiceSearchResults(count, searchTerm) {
    // Remove existing result message
    const existingMessage = document.querySelector('.service-search-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (count === 0) {
        const message = document.createElement('div');
        message.className = 'service-search-results-message';
        message.innerHTML = `
            <div class="no-results">
                <h3>No services found</h3>
                <p>No services match "${searchTerm}". Try searching with different keywords.</p>
                <button class="btn-secondary" onclick="clearServiceSearch()">Clear Search</button>
            </div>
        `;
        document.querySelector('.services-cards-grid').appendChild(message);
    }
}

function clearServiceSearch() {
    document.getElementById('servicesSearchInput').value = '';
    filterServices();
}

function startApplication(serviceId) {
    const serviceCards = document.querySelectorAll('.service-card');
    let serviceName = '';
    
    serviceCards.forEach(card => {
        const cardServiceId = card.querySelector('h3').textContent.toLowerCase().replace(/\s+/g, '-');
        if (cardServiceId === serviceId) {
            serviceName = card.querySelector('h3').textContent;
        }
    });
    
    alert(`Starting application for: ${serviceName}\n\nThis would redirect to the application form.`);
}

function viewRequirements(serviceId) {
    const serviceCards = document.querySelectorAll('.service-card');
    let serviceName = '';
    let requirements = '';
    
    serviceCards.forEach(card => {
        const cardServiceId = card.querySelector('h3').textContent.toLowerCase().replace(/\s+/g, '-');
        if (cardServiceId === serviceId) {
            serviceName = card.querySelector('h3').textContent;
            const detailItems = card.querySelectorAll('.detail-item');
            requirements = Array.from(detailItems).map(item => {
                const label = item.querySelector('.detail-label').textContent;
                const value = item.querySelector('.detail-value').textContent;
                return `${label} ${value}`;
            }).join('\n');
        }
    });
    
    alert(`Requirements for ${serviceName}:\n\n${requirements}`);
}

function checkStatus() {
    const applicationId = document.getElementById('applicationId').value.trim();
    const statusResult = document.getElementById('statusResult');
    
    if (!applicationId) {
        statusResult.className = 'status-result error';
        statusResult.innerHTML = 'Please enter an Application ID';
        return;
    }
    
    // Simulate status check
    const statuses = ['pending', 'approved', 'rejected'];
    const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
    
    statusResult.className = `status-result ${randomStatus}`;
    
    switch(randomStatus) {
        case 'pending':
            statusResult.innerHTML = `
                <h3>Application #${applicationId}</h3>
                <p><strong>Status:</strong> Under Review</p>
                <p>Your application is currently being processed. Expected completion: 3-5 business days.</p>
            `;
            break;
        case 'approved':
            statusResult.innerHTML = `
                <h3>Application #${applicationId}</h3>
                <p><strong>Status:</strong> Approved</p>
                <p>Your application has been approved! You will receive official documentation within 2 business days.</p>
            `;
            break;
        case 'rejected':
            statusResult.innerHTML = `
                <h3>Application #${applicationId}</h3>
                <p><strong>Status:</strong> Additional Information Required</p>
                <p>Please check your email for details on required documents.</p>
            `;
            break;
    }
}

// Service search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('servicesSearchInput');
    
    // Real-time search
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        filterServices(searchTerm);
    });
    
    // Set active navigation
    setActiveNav();
});


// -----------------------------------------------------Documents Page Specific JavaScript------------------------------------------------------------

function searchDocuments() {
    const searchTerm = document.getElementById('docSearchInput').value.toLowerCase().trim();
    const docType = document.getElementById('documentType').value;
    const docYear = document.getElementById('documentYear').value;
    
    filterDocuments(searchTerm, docType, docYear);
}

function filterDocuments(searchTerm = '', docType = 'all', docYear = 'all') {
    const documentCards = document.querySelectorAll('.document-card');
    let visibleCount = 0;
    
    documentCards.forEach(card => {
        const cardType = card.getAttribute('data-type');
        const cardYear = card.getAttribute('data-year');
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.doc-description').textContent.toLowerCase();
        
        const typeMatch = docType === 'all' || cardType === docType;
        const yearMatch = docYear === 'all' || cardYear === docYear;
        const searchMatch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
        
        if (typeMatch && yearMatch && searchMatch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show message if no results
    showSearchResults(visibleCount, searchTerm, docType, docYear);
}

function showSearchResults(count, searchTerm, docType, docYear) {
    // Remove existing result message
    const existingMessage = document.querySelector('.search-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (count === 0) {
        const message = document.createElement('div');
        message.className = 'search-results-message';
        message.innerHTML = `
            <div class="no-results">
                <h3>No documents found</h3>
                <p>Try adjusting your search criteria or filters.</p>
                <button class="btn-secondary" onclick="clearSearch()">Clear Search</button>
            </div>
        `;
        document.querySelector('.documents-grid').appendChild(message);
    }
}

function clearSearch() {
    document.getElementById('docSearchInput').value = '';
    document.getElementById('documentType').value = 'all';
    document.getElementById('documentYear').value = 'all';
    filterDocuments();
}

function downloadDocument(docId) {
    alert(`Downloading document: ${docId}\n\nThis would initiate a file download.`);
}

function previewDocument(docId) {
    alert(`Opening preview for: ${docId}\n\nThis would open a document preview window.`);
}

function toggleFAQ(button) {
    const faqItem = button.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const toggle = button.querySelector('.faq-toggle');
    
    button.classList.toggle('active');
    answer.classList.toggle('show');
}

function accessOpenData(category) {
    alert(`Accessing ${category} data...\n\nThis would redirect to the open data portal.`);
}

// Document filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const docTypeFilter = document.getElementById('documentType');
    const docYearFilter = document.getElementById('documentYear');
    const searchInput = document.getElementById('docSearchInput');
    
    // Real-time search
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const docType = docTypeFilter.value;
        const docYear = docYearFilter.value;
        filterDocuments(searchTerm, docType, docYear);
    });
    
    docTypeFilter.addEventListener('change', function() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const docType = this.value;
        const docYear = docYearFilter.value;
        filterDocuments(searchTerm, docType, docYear);
    });
    
    docYearFilter.addEventListener('change', function() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const docType = docTypeFilter.value;
        const docYear = this.value;
        filterDocuments(searchTerm, docType, docYear);
    });
    
    // Set active navigation
    setActiveNav();
});

// --------------------------------------------------------News Page Specific JavaScript--------------------------------------------------------------
function searchNews() {
    const searchTerm = document.getElementById('newsSearchInput').value.toLowerCase().trim();
    const newsCategory = document.getElementById('newsCategory').value;
    const newsMonth = document.getElementById('newsMonth').value;
    
    filterNews(searchTerm, newsCategory, newsMonth);
}

function filterNews(searchTerm = '', newsCategory = 'all', newsMonth = 'all') {
    const newsArticles = document.querySelectorAll('.news-article');
    let visibleCount = 0;
    
    newsArticles.forEach(article => {
        const articleCategory = article.getAttribute('data-category');
        const articleDate = article.getAttribute('data-date');
        const title = article.querySelector('h3').textContent.toLowerCase();
        const excerpt = article.querySelector('.article-excerpt').textContent.toLowerCase();
        
        const categoryMatch = newsCategory === 'all' || articleCategory === newsCategory;
        const monthMatch = newsMonth === 'all' || articleDate.startsWith(newsMonth);
        const searchMatch = !searchTerm || title.includes(searchTerm) || excerpt.includes(searchTerm);
        
        if (categoryMatch && monthMatch && searchMatch) {
            article.style.display = 'flex';
            visibleCount++;
        } else {
            article.style.display = 'none';
        }
    });
    
    showNewsSearchResults(visibleCount, searchTerm, newsCategory, newsMonth);
}

function showNewsSearchResults(count, searchTerm, newsCategory, newsMonth) {
    const existingMessage = document.querySelector('.news-search-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (count === 0) {
        const message = document.createElement('div');
        message.className = 'news-search-results-message';
        message.innerHTML = `
            <div class="no-results">
                <h3>No news articles found</h3>
                <p>Try adjusting your search criteria or filters.</p>
                <button class="btn-secondary" onclick="clearNewsSearch()">Clear Search</button>
            </div>
        `;
        document.querySelector('.news-articles-grid').appendChild(message);
    }
}

function clearNewsSearch() {
    document.getElementById('newsSearchInput').value = '';
    document.getElementById('newsCategory').value = 'all';
    document.getElementById('newsMonth').value = 'all';
    filterNews();
}

function readFullArticle(articleId) {
    alert(`Opening full article: ${articleId}\n\nThis would display the complete news article.`);
}

function shareArticle(articleId) {
    alert(`Sharing article: ${articleId}\n\nThis would open sharing options.`);
}

function downloadBudget() {
    alert('Downloading budget proposal PDF...');
}

function downloadTender() {
    alert('Downloading tender document...');
}

function viewHearingDetails() {
    alert('Showing public hearing details...');
}

function downloadSchedule() {
    alert('Downloading holiday schedule...');
}

function subscribeNews() {
    const email = document.getElementById('subscriptionEmail').value.trim();
    if (email && isValidEmail(email)) {
        const checkboxes = document.querySelectorAll('.subscription-options input[type="checkbox"]:checked');
        const categories = Array.from(checkboxes).map(cb => cb.parentElement.textContent.trim());
        
        alert(`Subscribed successfully!\n\nEmail: ${email}\nCategories: ${categories.join(', ')}`);
        document.getElementById('subscriptionEmail').value = '';
    } else {
        alert('Please enter a valid email address.');
    }
}

function viewArchive(year) {
    alert(`Loading news archive for ${year}...\n\nThis would show news articles from the selected year.`);
}

// News filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const newsCategoryFilter = document.getElementById('newsCategory');
    const newsMonthFilter = document.getElementById('newsMonth');
    const searchInput = document.getElementById('newsSearchInput');
    
    // Real-time search
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const newsCategory = newsCategoryFilter.value;
        const newsMonth = newsMonthFilter.value;
        filterNews(searchTerm, newsCategory, newsMonth);
    });
    
    newsCategoryFilter.addEventListener('change', function() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const newsCategory = this.value;
        const newsMonth = newsMonthFilter.value;
        filterNews(searchTerm, newsCategory, newsMonth);
    });
    
    newsMonthFilter.addEventListener('change', function() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const newsCategory = newsCategoryFilter.value;
        const newsMonth = this.value;
        filterNews(searchTerm, newsCategory, newsMonth);
    });
    
    // Set active navigation
    setActiveNav();
});


// -------------------------------------------------------------------Contact Page Specific JavaScript----------------------------------------------------------------------
function submitContactForm(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const errors = validateContactForm(formData);
    
    if (errors.length === 0) {
        // Simulate form submission
        showFormSuccess();
        event.target.reset();
    } else {
        showFormErrors(errors);
    }
}

function validateContactForm(formData) {
    const errors = [];
    clearErrors();
    
    // Name validation
    const name = formData.get('fullName').trim();
    if (!name) {
        errors.push({ field: 'name', message: 'Full name is required' });
    } else if (name.length < 2) {
        errors.push({ field: 'name', message: 'Name must be at least 2 characters' });
    }
    
    // Email validation
    const email = formData.get('email').trim();
    if (!email) {
        errors.push({ field: 'email', message: 'Email is required' });
    } else if (!isValidEmail(email)) {
        errors.push({ field: 'email', message: 'Please enter a valid email address' });
    }
    
    // Phone validation (optional)
    const phone = formData.get('phone').trim();
    if (phone && !isValidPhone(phone)) {
        errors.push({ field: 'phone', message: 'Please enter a valid phone number' });
    }
    
    // Subject validation
    const subject = formData.get('subject').trim();
    if (!subject) {
        errors.push({ field: 'subject', message: 'Subject is required' });
    }
    
    // Message validation
    const message = formData.get('message').trim();
    if (!message) {
        errors.push({ field: 'message', message: 'Message is required' });
    } else if (message.length < 10) {
        errors.push({ field: 'message', message: 'Message must be at least 10 characters' });
    }
    
    return errors;
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
    });
}

function showFormErrors(errors) {
    errors.forEach(error => {
        const errorElement = document.getElementById(`${error.field}Error`);
        if (errorElement) {
            errorElement.textContent = error.message;
        }
    });
}

function showFormSuccess() {
    alert('Thank you for your message! We will get back to you within 24-48 hours.\n\nA confirmation email has been sent to your inbox.');
}

function searchDirectory() {
    const searchTerm = document.getElementById('directorySearch').value.toLowerCase().trim();
    const departmentCards = document.querySelectorAll('.department-card');
    
    departmentCards.forEach(card => {
        const departmentName = card.querySelector('h3').textContent.toLowerCase();
        const departmentInfo = card.querySelector('.department-info').textContent.toLowerCase();
        
        if (departmentName.includes(searchTerm) || departmentInfo.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function contactDepartment(department) {
    const departments = {
        'services': 'Services Department',
        'records': 'Public Records',
        'tech': 'Technical Support',
        'hr': 'Human Resources'
    };
    
    alert(`Contacting ${departments[department]}...\n\nThis would open a pre-filled contact form for the selected department.`);
}

function openMap() {
    alert('Opening interactive map...\n\nThis would display a full-sized map with directions.');
}

function submitFeedback(type) {
    const feedbackTypes = {
        'positive': 'Positive Experience',
        'negative': 'Needs Improvement',
        'suggestion': 'Have a Suggestion'
    };
    
    alert(`Thank you for your ${feedbackTypes[type].toLowerCase()} feedback!\n\nThis would open a detailed feedback form.`);
}

// Contact page initialization
document.addEventListener('DOMContentLoaded', function() {
    // Real-time directory search
    const directorySearch = document.getElementById('directorySearch');
    directorySearch.addEventListener('input', function() {
        searchDirectory();
    });
    
    // Set active navigation
    setActiveNav();
});