/**
 * Authentication guard for protecting pages
 * Usage: Add data attributes to the <body> tag:
 * - data-auth-required="true" - Requires student to be logged in
 * - data-roles="student,organization" - Comma-separated list of allowed roles (optional)
 * - data-public-page="true" - For public pages that anyone can access (optional)
 */

(function() {


    // Figure out the base URL for redirects
    function getBaseUrl() {
        // Get current path
        const path = window.location.pathname;
        const projectName = '/iteam-university-website/organization';
        
        // Find the position of the project name in the path
        const projectPos = path.indexOf(projectName);
        
        if (projectPos !== -1) {
            // Build the base URL using the detected project path
            return window.location.origin + path.substring(0, projectPos + projectName.length);
        } else {
            // Fallback if we can't determine the project path
            return window.location.origin;
        }
    }
    
    // Determine if we're in a subfolder (e.g., /admin/, /organization/)
    function getRelativePath() {
        const path = window.location.pathname;
        const pathParts = path.split('/').filter(Boolean);
        
        // Check if we're in a subdirectory
        if (pathParts.includes('pages')) {
            const pagesIndex = pathParts.indexOf('pages');
            if (pagesIndex < pathParts.length - 1) {
                // We're in a subfolder like /pages/admin/ or /pages/organization/
                return '../';
            }
        }
        
        // Default relative path for main pages folder
        return '../';
    }
    
    // Add preloader to document as soon as script executes
    // This will overlay the entire page until auth check completes
    function addPreloader() {
        // Only add preloader if page requires authentication
        if (!document.body) {
            // If body isn't ready yet, wait for it
            document.addEventListener('DOMContentLoaded', addPreloader);
            return;
        }
        
        const body = document.body;
        const authRequired = body.getAttribute('data-auth-required') === 'true';
        const isPublicPage = body.getAttribute('data-public-page') === 'true';
        
        // Check if there's already a valid session in localStorage
        const userType = localStorage.getItem('userType');
        const isLoggedIn = userType !== null;
        
        // Skip preloader if user appears to be logged in or if it's a public page
        if (isLoggedIn || isPublicPage || !authRequired) {
            // Don't hide page content if user is likely already authenticated
            document.documentElement.style.visibility = 'visible';
            return;
        }
        
        // First make the body invisible to prevent content flash
        document.documentElement.style.visibility = 'hidden';
        
        // Create preloader element
        const preloader = document.createElement('div');
        preloader.id = 'auth-preloader';
        preloader.style.position = 'fixed';
        preloader.style.top = '0';
        preloader.style.left = '0';
        preloader.style.width = '100%';
        preloader.style.height = '100%';
        preloader.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
        preloader.style.display = 'flex';
        preloader.style.justifyContent = 'center';
        preloader.style.alignItems = 'center';
        preloader.style.zIndex = '9999';
        preloader.style.visibility = 'visible';
        
        // Add a spinner
        const spinner = document.createElement('div');
        spinner.style.width = '40px';
        spinner.style.height = '40px';
        spinner.style.border = '4px solid #f3f3f3';
        spinner.style.borderTop = '4px solid var(--theme-primary, #4338CA)';
        spinner.style.borderRadius = '50%';
        spinner.style.animation = 'auth-spin 1s linear infinite';
        
        // Add keyframes for spinner animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes auth-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        preloader.appendChild(spinner);
        document.body.appendChild(preloader);
    }
    
    // Run immediately before DOM is ready to prevent content flash
    addPreloader();
    
    // Check if student has access to this page
    function checkPageAccess() {
        if (!document.body) {
            // If body isn't ready yet, wait for it
            document.addEventListener('DOMContentLoaded', checkPageAccess);
            return;
        }
        
        const body = document.body;
        const authRequired = body.getAttribute('data-auth-required') === 'true';
        const allowedRoles = body.getAttribute('data-roles');
        const isPublicPage = body.getAttribute('data-public-page') === 'true';
        
        // Public pages are accessible to everyone, no need for further checks
        if (isPublicPage) {
            console.log('Auth check: This is a public page, accessible to all students');
            showPageContent();
            return;
        }
        
        // If page doesn't require auth, exit early
        if (!authRequired && !allowedRoles) {
            console.log('Auth check: No authentication required for this page');
            showPageContent();
            return;
        }
        
        console.log('Auth check: Authentication required for this page');
        
        // Get stored auth info (will be refreshed later)
        let userType = localStorage.getItem('userType');
        let isLoggedIn = userType !== null;
        
        // Parse allowed roles
        let roles = [];
        if (allowedRoles) {
            roles = allowedRoles.split(',').map(role => role.trim());
            console.log('Auth check: Page restricted to roles:', roles);
        }
        
        // If student isn't logged in, redirect to login
        if (!isLoggedIn) {
            console.log('Auth check: student not logged in, redirecting to login');
            redirectToLogin();
            return;
        }
        
        // Quick check for role before API call
        if (roles.length > 0 && !roles.includes(userType)) {
            console.log('Auth check: student role not allowed:', userType);
            redirectToUnauthorized();
            return;
        }
        
        // Show content right away if we passed the initial checks
        // This makes navigation feel faster, then verify in background
        showPageContent();
        
        // Get the correct relative path for API request
        const relativePath = getRelativePath();
        
        // Do a formal check with the server to validate session
        fetch(relativePath + 'api/auth.php?check=1')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    console.log('Auth check: Invalid session, redirecting to login');
                    // Clear any stored auth info
                    localStorage.removeItem('userType');
                    localStorage.removeItem('userName');
                    localStorage.removeItem('userEmail');
                    redirectToLogin();
                    return;
                }
                
                // Check if role matches allowed roles
                if (roles.length > 0 && !roles.includes(data.student.type)) {
                    console.log('Auth check: student role not allowed:', data.student.type);
                    redirectToUnauthorized();
                    return;
                }
                
                console.log('Auth check: Access granted for', data.student.type);
                
                // Update local storage with current student data
                localStorage.setItem('userType', data.student.type);
                localStorage.setItem('userName', data.student.name || '');
                localStorage.setItem('userEmail', data.student.email || '');
            })
            .catch(error => {
                console.error('Auth check error:', error);
                // On error, assume not authenticated
                redirectToLogin();
            });
    }
    
    // Show page content after authentication
    function showPageContent() {
        // Remove preloader
        const preloader = document.getElementById('auth-preloader');
        if (preloader) {
            preloader.style.display = 'none';
        }
        
        // Make body visible
        document.documentElement.style.visibility = 'visible';
    }
    
    function redirectToLogin() {
        // Store current page URL for redirect after login
        localStorage.setItem('redirectAfterLogin', window.location.href);
        
        // Make sure we don't keep page content hidden if redirecting
        document.documentElement.style.visibility = 'visible';
        
        // Build login URL based on current path
        const baseUrl = getBaseUrl();
        const relativePath = getRelativePath();
        const loginUrl = '/iteam-university-website/auth/login.html';
        
        console.log('Redirecting to:', loginUrl);
        
        // Use timeout to ensure message is logged before redirect
        setTimeout(() => {
            window.location.href = loginUrl;
        }, 100);
    }
    
    function redirectToUnauthorized() {
        // Make sure we don't keep page content hidden if redirecting
        document.documentElement.style.visibility = 'visible';
        
        // Build unauthorized URL based on current path
        const relativePath = getRelativePath();
        const unauthorizedUrl = relativePath + 'pages/unauthorized.html';
        
        console.log('Redirecting to unauthorized page:', unauthorizedUrl);
        
        // Redirect to unauthorized page
        setTimeout(() => {
            window.location.href = unauthorizedUrl;
        }, 100);
    }
    
    // Close any open tooltips or modals when document is clicked
    document.addEventListener('click', function() {
        document.querySelectorAll('.event-tooltip.active').forEach(tooltip => {
            tooltip.classList.remove('active');
        });
        document.querySelectorAll('.tooltip-backdrop.active').forEach(backdrop => {
            backdrop.classList.remove('active');
        });
    });
    
    // Run check when DOM is ready or immediately if already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkPageAccess);
    } else {
        checkPageAccess();
    }
})();