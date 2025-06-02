document.addEventListener('DOMContentLoaded', function() {
    // Main.js is now just responsible for page-specific functionality
    // Common functionality has been moved to components.js
    console.log('Main script initialized');
    
    // Initialize page-specific functionality here
    initPageSpecific();
});

function initPageSpecific() {
    // Check which page we're on and initialize specific functionality
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop() || 'index.html';
    
    switch(currentPage) {
        case 'index.html':
            // Homepage specific initialization
            break;
            
        case 'events.html':
            // Events page specific initialization
            break;
            
        case 'event-details.html':
            // Event details page specific initialization
            break;
            
        case 'my-registrations.html':
            // My registrations page specific initialization
            break;
            
        default:
            // Default initialization
            break;
    }
}




