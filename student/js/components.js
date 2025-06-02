document.addEventListener('DOMContentLoaded', function() {
    // Check if CONFIG is available
    if (typeof CONFIG === 'undefined') {
        console.error('CONFIG is not defined. Make sure to include config.js before components.js');
        
        // Fallback configuration
        window.CONFIG = {
            BASE_URL: '/iteam-university-website',
            STYDENT_PATH: '/iteam-university-website/student',
            ORGANIZATION_PATH: '/iteam-university-website/organization',
            ADMIN_PATH: '/iteam-university-website/admin',
            FRONTEND_PATH: '/iteam-university-website/frontend',
            AUTH_PATH: '/iteam-university-website/auth',
            ASSETS_PATH: '/iteam-university-website/frontend/assets',
            ADMIN_API_PATH: '/iteam-university-website/admin/api',
            STUDENT_API_PATH: '/iteam-university-website/student/api',
            ORGANIZATION_API_PATH: '/iteam-university-website/organization/api',
            INDEX_PATH: '/iteam-university-website/index.html'
        };
    }
    
    // Check student login status
    checkLoginStatus();
    
    // Load header component
    loadHeaderComponent();
    
    // Load footer component
    loadFooterComponent();
});

// IIFE to check and apply user role class from localStorage
(function() {
    const userType = localStorage.getItem('userType');
    if (userType) {
        document.body.classList.remove('is-student');
        if (userType === 'student') {
            document.body.classList.add('is-student');
        }
    }
})();

// Function to check if student is logged in
// Update the checkLoginStatus function to use absolute paths
function checkLoginStatus() {
    // Use cached data first
    const userType = localStorage.getItem('userType');
    const userName = localStorage.getItem('userName');
    const userEmail = localStorage.getItem('userEmail');
    
    if (userType === 'student') {
        // Temporarily use cached data to update UI immediately
        window.userLoggedIn = true;
        window.userData = {
            type: 'student',
            name: userName || 'Student',
            email: userEmail || 'student@example.com'
        };
        
        // Update header state if it's already loaded
        updateLoginState(true, window.userData);
    }
    
    // Then verify with server - use absolute path with CONFIG
    fetch(`${CONFIG.STUDENT_API_PATH}/auth.php?check=1`)
        .then(response => response.json())
        .then(data => {
            window.userLoggedIn = data.success;
            window.userData = data.student;
            
            if (data.success && data.student.type === 'student') {
                // Update localStorage with fresh data
                localStorage.setItem('userType', 'student');
                localStorage.setItem('userName', data.student.name || '');
                localStorage.setItem('userEmail', data.student.email || '');
            } else {
                // Clear localStorage if session is invalid
                localStorage.removeItem('userType');
                localStorage.removeItem('userName');
                localStorage.removeItem('userEmail');
            }
            
            // Update header state
            updateLoginState(data.success, data.student);
        })
        .catch(error => {
            console.error('Auth check error:', error);
            // If there's an error, assume not logged in
            localStorage.removeItem('userType');
            localStorage.removeItem('userName');
            localStorage.removeItem('userEmail');
            updateLoginState(false, null);
        });
}

// Function to load header component without reloading on every navigation
// Update loadHeaderComponent function to use absolute paths from CONFIG
function loadHeaderComponent() {
    const headerPlaceholder = document.getElementById('header-placeholder');
    
    if (!headerPlaceholder) return;
    
    // Try to load the header instantly from cache
    const cachedHeader = localStorage.getItem('cached-header');
    
    if (cachedHeader) {
        headerPlaceholder.innerHTML = cachedHeader;
        
        // After rendering cached version, initialize functionality
        setTimeout(() => {
            initializeHeaderComponents();
            
            // Fix auth links with the path from config
            fixAuthLinks();
            
            // Check login status and update UI
            if (window.userLoggedIn) {
                updateLoginState(window.userLoggedIn, window.userData);
            }
        }, 0);
    }
    
    // Use absolute path with CONFIG.STUDENT_PATH (ensure it's defined)
    const studentPath = CONFIG.STUDENT_PATH || CONFIG.STYDENT_PATH || '/iteam-university-website/student';
    
    fetch(`${studentPath}/components/header.html`)
        .then(response => response.text())
        .then(html => {
            // Only update DOM if content is different or we had no cache
            if (!cachedHeader || html !== cachedHeader) {
                headerPlaceholder.innerHTML = html;
                localStorage.setItem('cached-header', html);
                
                initializeHeaderComponents();
                
                // Fix auth links with the path from config
                fixAuthLinks();
                
                if (window.userLoggedIn) {
                    updateLoginState(window.userLoggedIn, window.userData);
                }
            }
        })
        .catch(error => {
            console.error('Error loading header:', error);
            if (!cachedHeader) {
                headerPlaceholder.innerHTML = '<p class="text-red-500 p-4">Error loading header component</p>';
            }
        });
}

// Update the fixAuthLinks function
function fixAuthLinks() {
    // Fix login links in both desktop and mobile menus
    const loginLinks = document.querySelectorAll('a[href="login.html"]');
    loginLinks.forEach(link => {
        // Make sure CONFIG.AUTH_PATH is defined before using it
        if (CONFIG && CONFIG.AUTH_PATH) {
            link.href = `${CONFIG.AUTH_PATH}/login.html`;
            console.log('Updated login link to:', link.href);
        } else {
            // Fallback to absolute path if CONFIG.AUTH_PATH is undefined
            link.href = '/iteam-university-website/auth/login.html';
            console.error('CONFIG.AUTH_PATH is undefined, using fallback path');
        }
    });
    
    // Fix signup links (correcting the misspelled "singup" in the header)
    const signupLinks = document.querySelectorAll('a[href="signup.html"], a[href="singup.html"]');
    signupLinks.forEach(link => {
        // Make sure CONFIG.AUTH_PATH is defined before using it
        if (CONFIG && CONFIG.AUTH_PATH) {
            link.href = `${CONFIG.AUTH_PATH}/signup.html`;
            console.log('Updated signup link to:', link.href);
        } else {
            // Fallback to absolute path if CONFIG.AUTH_PATH is undefined
            link.href = '/iteam-university-website/auth/signup.html';
            console.error('CONFIG.AUTH_PATH is undefined, using fallback path');
        }
    });
    
    // Fix home/index links to point to the main website index
    const homeLinks = document.querySelectorAll('a[href="index.html"], a[href="./"], a[href="/"]');
    homeLinks.forEach(link => {
        // Only update links that have the text "Home" 
        if (link.textContent.trim() === "Home") {
            if (CONFIG && CONFIG.BASE_URL) {
                link.href = CONFIG.BASE_URL;
                console.log('Updated home link to:', link.href);
            } else {
                // Fallback to absolute path if CONFIG.BASE_URL is undefined
                link.href = '/iteam-university-website/';
                console.error('CONFIG.BASE_URL is undefined, using fallback path');
            }
        }
    });
    
    // NEW CODE: Fix navigation links to student pages
    const isOnIndexPage = window.location.pathname === '/iteam-university-website/' || 
                         window.location.pathname === '/iteam-university-website/index.html';
    
    if (isOnIndexPage) {
        // On main index page: fix all relative links to student pages
        const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            // Skip links that are already absolute or point to the home page
            if (href.startsWith('http') || href.startsWith('/') || 
                href === 'index.html' || href === './' || link.textContent.trim() === "Home") {
                return;
            }
            
            // Update student page links to use student path
            link.href = `${CONFIG.STUDENT_PATH}/pages/${href}`;
            console.log(`Updated nav link from ${href} to ${link.href}`);
        });
    }
    
    console.log('Auth and navigation links updated to use paths from config');
}

// Function to update UI based on login state
function updateLoginState(isLoggedIn, userData) {
    const guestLinks = document.getElementById('guest-links');
    const userLinks = document.getElementById('student-links');
    const mobileGuestLinks = document.getElementById('mobile-guest-links');
    const mobileUserLinks = document.getElementById('mobile-student-links');
    const profileToggle = document.getElementById('profile-toggle');
    
    // First, remove all role-specific classes from body
    document.body.classList.remove('is-student');
    
    // Also update footer visibility based on login state
    const studentFooterContent = document.querySelectorAll('.is-student-content');
    const guestFooterContent = document.querySelectorAll('.guest-content');
    
    if (isLoggedIn && userData && userData.type === 'student') {
        // Add appropriate class to body
        document.body.classList.add('is-student');
        
        // Show student footer content
        studentFooterContent.forEach(el => el.classList.remove('hidden'));
        guestFooterContent.forEach(el => el.classList.add('hidden'));
        
        // Show student links, hide guest links
        if (guestLinks) guestLinks.classList.add('hidden');
        if (userLinks) userLinks.classList.remove('hidden');
        if (mobileGuestLinks) mobileGuestLinks.classList.add('hidden');
        if (mobileUserLinks) mobileUserLinks.classList.remove('hidden');
        
        // Update profile information
        if (profileToggle) {
            const profileInitials = profileToggle.querySelector('span');
            if (profileInitials) {
                // Generate initials from name or use default
                let initials = 'ST';
                if (userData.name) {
                    const nameParts = userData.name.split(' ');
                    if (nameParts.length >= 2) {
                        initials = nameParts[0].charAt(0) + nameParts[1].charAt(0);
                    } else {
                        initials = nameParts[0].substr(0, 2);
                    }
                }
                profileInitials.textContent = initials.toUpperCase();
            }
        }
        
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileDropdown) {
            const nameElement = profileDropdown.querySelector('.student-name');
            const emailElement = profileDropdown.querySelector('.student-email');
            const userTypeElement = profileDropdown.querySelector('.student-type-badge');
            
            if (nameElement) nameElement.textContent = userData.name || 'Student';
            if (emailElement) emailElement.textContent = userData.email || 'student@example.com';
            
            // Add student type badge
            if (userTypeElement) {
                userTypeElement.textContent = 'Student';
                
                // Clear previous classes
                userTypeElement.className = 'student-type-badge mt-1 inline-block text-xs px-2 py-0.5 rounded-full';
                
                // Add styling
                userTypeElement.classList.add('bg-blue-100', 'text-blue-800', 'dark:bg-blue-900/30', 'dark:text-blue-400');
            }
        }
    } else {
        // Guest user - show guest links, hide student links
        if (guestLinks) guestLinks.classList.remove('hidden');
        if (userLinks) userLinks.classList.add('hidden');
        if (mobileGuestLinks) mobileGuestLinks.classList.remove('hidden');
        if (mobileUserLinks) mobileUserLinks.classList.add('hidden');
        
        // Show guest footer content
        studentFooterContent.forEach(el => el.classList.add('hidden'));
        guestFooterContent.forEach(el => el.classList.remove('hidden'));
    }
}

// Function to load footer component without reloading on every navigation
// Update loadFooterComponent function to use absolute paths from CONFIG
function loadFooterComponent() {
    const footerPlaceholder = document.getElementById('footer-placeholder');
    
    if (!footerPlaceholder) return;
    
    // Try to load the footer instantly from cache
    const cachedFooter = localStorage.getItem('cached-footer');
    
    if (cachedFooter) {
        footerPlaceholder.innerHTML = cachedFooter;
        setTimeout(initializeFooterComponents, 0);
    }
    
    // Use absolute path with CONFIG.STUDENT_PATH (ensure it's defined)
    const studentPath = CONFIG.STUDENT_PATH || CONFIG.STYDENT_PATH || '/iteam-university-website/student';
    
    fetch(`${studentPath}/components/footer.html`)
        .then(response => response.text())
        .then(html => {
            if (!cachedFooter || html !== cachedFooter) {
                footerPlaceholder.innerHTML = html;
                localStorage.setItem('cached-footer', html);
                initializeFooterComponents();
            }
        })
        .catch(error => {
            console.error('Error loading footer:', error);
            if (!cachedFooter) {
                footerPlaceholder.innerHTML = '<p class="text-red-500 p-4">Error loading footer component</p>';
            }
        });
}

// // Load AI Assistant on every page
// function loadAIAssistant() {
//     const currentPath = window.location.pathname;
    
//     // Get the correct path to the script
//     let scriptPath;
//     if (currentPath.includes('/pages/') || currentPath.includes('/dashboard/')) {
//         scriptPath = '../js/ai-assistant.js';
//     } else {
//         scriptPath = 'js/ai-assistant.js';
//     }
    
//     // Create a script element
//     const aiScript = document.createElement('script');
//     aiScript.src = scriptPath;
    
//     // Add the script to the document
//     document.body.appendChild(aiScript);
// }

// Initialize header-specific JavaScript
function initializeHeaderComponents() {
    console.log('Initializing header components...');
    
    // Identify current page
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop() || 'index.html';
    
    // Dark mode toggle (moved from main.js to ensure it works after component loads)
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        // Remove any existing event listener before adding a new one
        darkModeToggle.removeEventListener('click', handleDarkModeToggle);
        darkModeToggle.addEventListener('click', handleDarkModeToggle);
        console.log('Dark mode toggle initialized');
    }
    
    // Notification dropdown toggle
    const notificationToggle = document.getElementById('notification-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    if (notificationToggle && notificationDropdown) {
        // Remove any existing event listener before adding a new one
        notificationToggle.removeEventListener('click', handleNotificationToggle);
        notificationToggle.addEventListener('click', handleNotificationToggle);
        console.log('Notification toggle initialized');
    }
    
    // Profile dropdown toggle
    const profileToggle = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (profileToggle && profileDropdown) {
        // Remove any existing event listener before adding a new one
        profileToggle.removeEventListener('click', handleProfileToggle);
        profileToggle.addEventListener('click', handleProfileToggle);
        console.log('Profile toggle initialized');
    }
    
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (menuToggle && mobileMenu) {
        // Remove any existing event listener before adding a new one
        menuToggle.removeEventListener('click', handleMenuToggle);
        menuToggle.addEventListener('click', handleMenuToggle);
        console.log('Mobile menu toggle initialized');
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.removeEventListener('click', handleLogout);
        logoutBtn.addEventListener('click', handleLogout);
        console.log('Logout button initialized');
    }
    
    // Mobile logout button
    const mobileLogoutBtn = document.getElementById('mobile-logout-btn');
    if (mobileLogoutBtn) {
        mobileLogoutBtn.removeEventListener('click', handleLogout);
        mobileLogoutBtn.addEventListener('click', handleLogout);
        console.log('Mobile logout button initialized');
    }
    
    // Close dropdowns when clicking outside
    document.removeEventListener('click', handleOutsideClick);
    document.addEventListener('click', handleOutsideClick);
    
    // Highlight active navigation link
    highlightActivePage(currentPage);
}

// Update the initializeFooterComponents function
function initializeFooterComponents() {
    console.log('Initializing footer components...');
    
    // Get user type from localStorage
    const userType = localStorage.getItem('userType');
    
    // Get content elements
    const studentContent = document.querySelectorAll('.is-student-content');
    const guestContent = document.querySelectorAll('.guest-content');
    
    // Hide all student content by default
    studentContent.forEach(el => el.classList.add('hidden'));
    
    // Show relevant content based on user type
    if (userType === 'student') {
        studentContent.forEach(el => el.classList.remove('hidden'));
        guestContent.forEach(el => el.classList.add('hidden'));
        console.log('Footer: Showing student content');
    } else {
        // For guests, show guest content
        guestContent.forEach(el => el.classList.remove('hidden'));
        console.log('Footer: Showing guest content');
    }
    
    // Correct the paths in footer links if we're in a subdirectory
    const currentPath = window.location.pathname;
    
    // If we're in a subdirectory (pages/), we need to fix links
    if (currentPath.includes('/pages/')) {
        const footerLinks = document.querySelectorAll('#footer-placeholder a[href]:not([href^="http"]):not([href^="#"]):not([href^="/"])');
        
        footerLinks.forEach(link => {
            // Only update relative links that don't already start with ../
            if (!link.getAttribute('href').startsWith('../')) {
                link.setAttribute('href', '../pages/' + link.getAttribute('href'));
            }
        });
        
        console.log('Footer: Fixed relative links in subdirectory');
    }
}

// Event handler for dark mode toggle
function handleDarkModeToggle() {
    document.documentElement.classList.toggle('dark');
    
    // Save preference to localStorage
    const isDarkMode = document.documentElement.classList.contains('dark');
    localStorage.setItem('darkMode', isDarkMode ? 'dark' : 'light');
    
    console.log('Dark mode toggled:', isDarkMode ? 'dark' : 'light');
}

// Event handler for notification dropdown
function handleNotificationToggle(e) {
    e.stopPropagation();
    const notificationDropdown = document.getElementById('notification-dropdown');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    notificationDropdown.classList.toggle('hidden');
    
    // Hide profile dropdown if it's open
    if (profileDropdown && !profileDropdown.classList.contains('hidden')) {
        profileDropdown.classList.add('hidden');
    }
    
    console.log('Notification dropdown toggled');
}

// Event handler for profile dropdown
function handleProfileToggle(e) {
    e.stopPropagation();
    const profileDropdown = document.getElementById('profile-dropdown');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    profileDropdown.classList.toggle('hidden');
    
    // Hide notification dropdown if it's open
    if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
        notificationDropdown.classList.add('hidden');
    }
    
    console.log('Profile dropdown toggled');
}

// Event handler for mobile menu toggle
function handleMenuToggle() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
    console.log('Mobile menu toggled');
}

// Event handler for logout
function handleLogout(e) {
    e.preventDefault();
    
    // Use the confirmation modal instead of logging out immediately
    showConfirmModal(
        'Confirm Logout',
        'Are you sure you want to log out?',
        () => {
            // This code executes when the user confirms the logout
            fetch('../api/auth.php?action=logout')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear localStorage items
                        localStorage.removeItem('userType');
                        localStorage.removeItem('userName');
                        localStorage.removeItem('userEmail');
                        
                        // Clear cached user data
                        window.userLoggedIn = false;
                        window.userData = null;
                        
                        // Redirect to homepage or login page
                        window.location.href = data.redirect || 'index.html';
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Fallback redirect
                    window.location.href = 'index.html';
                });
        }
    );
}

// Event handler for clicking outside dropdowns
function handleOutsideClick(e) {
    const notificationToggle = document.getElementById('notification-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const profileToggle = document.getElementById('profile-toggle');
    const profileDropdown = document.getElementById('profile-dropdown');
    
    if (notificationDropdown && !notificationDropdown.contains(e.target) && 
        notificationToggle && !notificationToggle.contains(e.target)) {
        notificationDropdown.classList.add('hidden');
    }
    
    if (profileDropdown && !profileDropdown.contains(e.target) && 
        profileToggle && !profileToggle.contains(e.target)) {
        profileDropdown.classList.add('hidden');
    }
}

// Function to highlight the current active page in navigation
function highlightActivePage(currentPage) {
    console.log("Highlighting current page:", currentPage);
    
    // Default to index.html if no page is specified
    if (!currentPage || currentPage === '') {
        currentPage = 'index.html';
    }
    
    // Get all navigation links in desktop and mobile menu
    const desktopLinks = document.querySelectorAll('.nav-link');
    const mobileLinks = document.querySelectorAll('.mobile-nav-link');
    
    // Helper function to set active state
    function setActiveLink(links) {
        links.forEach(link => {
            const href = link.getAttribute('href');
            
            // Remove active class from all links
            link.classList.remove('text-theme-primary', 'font-medium');
            link.classList.remove('bg-gray-50', 'dark:bg-gray-700');
            
            // Special case for index page
            if ((currentPage === 'index.html' && (href === 'index.html' || href === './' || href === '/')) ||
                (href === currentPage)) {
                
                // Add active class to current page link
                link.classList.add('text-theme-primary', 'font-medium');
                
                // For mobile menu
                if (link.classList.contains('mobile-nav-link')) {
                    link.classList.add('bg-gray-50', 'dark:bg-gray-700');
                }
                
                console.log("Active link:", href);
            }
        });
    }
    
    // Apply to both desktop and mobile menus
    setActiveLink(desktopLinks);
    setActiveLink(mobileLinks);
}

// Add this function at the end of the file
function showConfirmModal(title, message, onConfirm, onCancel) {
    // Get modal elements
    const modal = document.getElementById('confirm-modal');
    const titleEl = document.getElementById('confirm-modal-title');
    const messageEl = document.getElementById('confirm-modal-message');
    const confirmBtn = document.getElementById('confirm-modal-confirm');
    const cancelBtn = document.getElementById('confirm-modal-cancel');
    const backdrop = document.getElementById('confirm-modal-backdrop');
    
    // If modal doesn't exist yet (header might not be fully loaded), create it dynamically
    if (!modal) {
        // Create the modal elements
        const newModal = document.createElement('div');
        newModal.id = 'confirm-modal';
        newModal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
        
        const modalHTML = `
            <div id="confirm-modal-backdrop" class="absolute inset-0 bg-black opacity-30"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
                <div class="p-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-full p-3">
                            <svg class="w-8 h-8 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-center mb-2" id="confirm-modal-title">Confirm Action</h3>
                    <p class="text-center text-gray-600 dark:text-gray-300 mb-6" id="confirm-modal-message">Are you sure you want to perform this action?</p>
                    <div class="flex justify-center space-x-4">
                        <button id="confirm-modal-cancel" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg px-6 py-2 font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Cancel
                        </button>
                        <button id="confirm-modal-confirm" class="bg-theme-primary text-white rounded-lg px-6 py-2 font-medium hover:bg-theme-primary-hover transition">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        newModal.innerHTML = modalHTML;
        document.body.appendChild(newModal);
        
        // Get the newly created elements
        modal = newModal;
        titleEl = document.getElementById('confirm-modal-title');
        messageEl = document.getElementById('confirm-modal-message');
        confirmBtn = document.getElementById('confirm-modal-confirm');
        cancelBtn = document.getElementById('confirm-modal-cancel');
        backdrop = document.getElementById('confirm-modal-backdrop');
    }
    
    // Set content
    titleEl.textContent = title || 'Confirm Action';
    messageEl.textContent = message || 'Are you sure you want to perform this action?';
    
    // Clean up previous event listeners if any
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    const newBackdrop = backdrop.cloneNode(true);
    
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    backdrop.parentNode.replaceChild(newBackdrop, backdrop);
    
    // Show the modal
    modal.classList.remove('hidden');
    
    // Setup event listeners
    newConfirmBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        if (onConfirm && typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    newCancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        if (onCancel && typeof onCancel === 'function') {
            onCancel();
        }
    });
    
    newBackdrop.addEventListener('click', () => {
        modal.classList.add('hidden');
        if (onCancel && typeof onCancel === 'function') {
            onCancel();
        }
    });
}