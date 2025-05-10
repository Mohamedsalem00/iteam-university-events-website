/**
 * Authentication check script
 * Handles session verification, redirection, and dashboard protection
 * Using external HTML templates for sidebar and user navigation
 * Updated to support unified accounts system with separate admin login
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get current page path
    const currentPath = window.location.pathname;
    const baseUrl = '/iteam-university-website/frontend/';
    
    // Check if current page is login or register
    const isLoginPage = currentPath.includes('/auth/login.html');
    const isAdminLoginPage = currentPath.includes('/auth/admin-login.html');
    const isRegisterPage = currentPath.includes('/auth/register.html');
    const isAuthPage = isLoginPage || isRegisterPage || isAdminLoginPage;
    
    // Check if current page is a dashboard or protected area
    const isDashboardPage = 
        currentPath.includes('/dashboards/') || 
        currentPath.includes('/admin/dashboard') || 
        currentPath.includes('/profile/');
    
    // Check if this is an admin page
    const isAdminPage = currentPath.includes('/admin/') || 
                        currentPath.includes('/dashboards/admin/');
    
    // Load user navigation and sidebar templates first
    loadUserNavigationAndSidebar().then(() => {
        // Check authentication after templates are loaded
        if (isAdminPage || isAdminLoginPage) { // Check admin status on admin pages AND admin login page
            checkAdminAuthStatus();
        } else {
            checkAuthStatus();
        }
    });
    
    /**
     * Load user navigation and sidebar templates
     */
    function loadUserNavigationAndSidebar() {
        return fetch('components/usernaveandslidbar.html')
            .then(response => response.text())
            .then(html => {
                // Create a temporary container to parse the HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Extract and append relevant parts to the document
                const userMenuTemplate = tempDiv.querySelector('#user-menu-template');
                const adminSidebar = tempDiv.querySelector('#admin-sidebar');
                const orgSidebar = tempDiv.querySelector('#organization-sidebar');
                const userSidebar = tempDiv.querySelector('#user-sidebar');
                const styles = tempDiv.querySelector('style');
                
                if (userMenuTemplate) document.body.appendChild(userMenuTemplate);
                if (adminSidebar) document.body.appendChild(adminSidebar);
                if (orgSidebar) document.body.appendChild(orgSidebar);
                if (userSidebar) document.body.appendChild(userSidebar);
                if (styles) document.head.appendChild(styles);
            })
            .catch(error => {
                console.error('Error loading navigation templates:', error);
            });
    }
    
    /**
     * Check authentication status via API for regular users and organizations
     */
    function checkAuthStatus() {
        fetch('../backend/api/auth.php?check=1')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Auth check result:', data);
                
                if (data.success && data.user) {
                    // User is logged in
                    console.log('User is logged in as:', data.user.type);
                    console.log('Account ID:', data.user.account_id);
                    
                    // If logged in as admin, this shouldn't happen on non-admin pages, but redirect just in case
                    if (data.user.is_admin) {
                         console.log('Admin detected on non-admin page, redirecting to admin dashboard');
                         window.location.href = baseUrl + 'dashboards/admin/dashboard.html';
                         return;
                    }
                    
                    // Update the navbar to show user menu instead of auth links
                    showUserMenu(data.user);
                    
                    // Show appropriate sidebar for dashboard pages
                    if (isDashboardPage) {
                        showSidebar(data.user.type);
                    }
                    
                    // Prevent going back to login/register pages if already logged in
                    if (isAuthPage && !isAdminLoginPage) { // Don't redirect if on admin login page
                        console.log('Redirecting from auth page to dashboard');
                        let dashboardUrl = data.user.dashboard_url || getDashboardUrl(data.user.type);
                        setTimeout(() => {
                            window.location.href = baseUrl + dashboardUrl;
                        }, 100);
                    }
                } else {
                    // User is not logged in
                    console.log('User is not logged in');
                    
                    // Show auth links and hide user menu
                    showAuthLinks();
                    
                    // Redirect to login if trying to access protected pages (non-admin)
                    if (isDashboardPage && !isAdminPage) {
                        console.log('Redirecting from protected page to login');
                        sessionStorage.setItem('redirectAfterLogin', window.location.href);
                        window.location.href = baseUrl + 'auth/login.html';
                    }
                }
            })
            .catch(error => {
                console.error('Auth check error:', error);
                showAuthLinks(); // Fallback
            });
    }
    
    /**
     * Check admin authentication status via separate API
     */
    function checkAdminAuthStatus() {
        fetch('../backend/api/admin/adminlogin.php?check=1') 
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Admin auth check result:', data);
                
                if (data.success && data.user && data.user.is_admin) {
                    // Admin is logged in
                    console.log('Admin is logged in:', data.user.name);
                    
                    // Update the navbar to show user menu instead of auth links
                    showUserMenu(data.user);
                    
                    // Show admin sidebar for dashboard pages
                    if (isDashboardPage) {
                        showSidebar('admin');
                    }
                    
                    // Prevent going back to admin login page if already logged in
                    if (isAdminLoginPage) {
                        console.log('Redirecting from admin login to admin dashboard');
                        window.location.href = baseUrl + 'dashboards/admin/dashboard.html';
                    }
                } else {
                    // Not logged in as admin
                    console.log('Not logged in as admin');
                    
                    // Show auth links and hide user menu
                    showAuthLinks();
                    
                    // Redirect to admin login if trying to access admin pages (but not already on login)
                    if (isAdminPage && !isAdminLoginPage) {
                        console.log('Redirecting from admin page to admin login');
                        sessionStorage.setItem('redirectAfterLogin', window.location.href);
                        window.location.href = baseUrl + 'auth/admin-login.html';
                    }
                }
            })
            .catch(error => {
                console.error('Admin auth check error:', error);
                showAuthLinks(); // Fallback
                // Redirect to admin login if on admin page and check failed
                if (isAdminPage && !isAdminLoginPage) {
                    window.location.href = baseUrl + 'auth/admin-login.html';
                }
            });
    }
    
    /**
     * Display authentication links for guests
     */
    function showAuthLinks() {
        // Show auth links
        const authLinks = document.querySelector('.auth-links');
        if (authLinks) {
            authLinks.style.display = 'block';
        }
        
        // Show main navigation links when logged out
        const mainNavLinks = document.querySelector('.nav-links.nav-main-links');
        if (mainNavLinks) {
            mainNavLinks.style.display = 'block';
        }
        
        // Hide user menu
        const userMenu = document.getElementById('user-menu');
        if (userMenu) {
            userMenu.style.display = 'none';
        }
        
        // Hide all sidebars
        const sidebars = document.querySelectorAll('.dashboard-sidebar');
        sidebars.forEach(sidebar => {
            sidebar.classList.remove('active');
        });
    }
    
    /**
     * Display user menu for authenticated users
     * @param {Object} user User information
     */
    function showUserMenu(user) {
        // Hide auth links
        const authLinks = document.querySelector('.auth-links');
        if (authLinks) {
            authLinks.style.display = 'none';
        }
        
        // Hide main navigation links when user is logged in
        const mainNavLinks = document.querySelector('.nav-links.nav-main-links');
        if (mainNavLinks) {
            mainNavLinks.style.display = 'none';
        }
        
        // Get template and create actual user menu
        const template = document.getElementById('user-menu-template');
        let userMenu = document.getElementById('user-menu');
        
        // If user menu doesn't exist, create it from template
        if (!userMenu) {
            const menuContainer = document.querySelector('.navbar-right');
            if (menuContainer && template) {
                // Clone the template
                userMenu = template.cloneNode(true);
                userMenu.id = 'user-menu';
                userMenu.style.display = 'flex';
                
                // Add account ID as a data attribute for potential future use
                userMenu.dataset.accountId = user.account_id;
                
                // Append to navbar
                menuContainer.appendChild(userMenu);
            }
        } else {
            userMenu.style.display = 'flex';
            // Update account ID
            userMenu.dataset.accountId = user.account_id;
        }
        
        // If we have a user menu now, populate it
        if (userMenu) {
            // Find the avatar placeholder
            const avatar = userMenu.querySelector('.user-avatar');
            if (avatar) {
                // Create user avatar
                if (user.profile_picture) {
                    avatar.innerHTML = `<img src="${user.profile_picture}" alt="Profile" class="profile-pic">`;
                } else {
                    const initials = user.name.split(' ')
                        .map(name => name.charAt(0))
                        .join('')
                        .toUpperCase()
                        .substring(0, 2);
                    avatar.innerHTML = `<div class="profile-initials">${initials}</div>`;
                }
            }
            
            // Set user name and role
            const userName = userMenu.querySelector('.user-name');
            const userRole = userMenu.querySelector('.user-role');
            
            if (userName) userName.textContent = user.name;
            if (userRole) userRole.textContent = getUserRoleLabel(user.type);
            
            // Update account type in data attribute
            userMenu.dataset.accountType = user.type;
            
            // Update dashboard link
            const dashboardLink = userMenu.querySelector('.dashboard-link');
            if (dashboardLink) {
                dashboardLink.href = user.dashboard_url || baseUrl + getDashboardUrl(user.type);
            }
            
            // Update profile link
            const profileLink = userMenu.querySelector('.profile-link');
            if (profileLink) {
                profileLink.href = baseUrl + 'profile/' + getUserProfilePage(user.type);
            }
            
            // Add dropdown toggle functionality
            const dropdown = userMenu.querySelector('.user-dropdown');
            userMenu.addEventListener('click', function(e) {
                // Toggle dropdown visibility
                dropdown.classList.toggle('show');
                e.stopPropagation();
            });
            
            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Add logout functionality
            const logoutLink = userMenu.querySelector('#logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logout(user.is_admin); // Pass admin status to logout function
                });
            }
        }
    }
    
    /**
     * Display appropriate sidebar based on user role
     * @param {string} userType User role/type
     */
    function showSidebar(userType) {
        // Hide all sidebars first
        const sidebars = document.querySelectorAll('.dashboard-sidebar');
        sidebars.forEach(sidebar => {
            sidebar.classList.remove('active');
        });
        
        // Show appropriate sidebar
        let sidebarId = 'user-sidebar'; // Default
        
        if (userType === 'admin') {
            sidebarId = 'admin-sidebar';
        } else if (userType === 'organization') {
            sidebarId = 'organization-sidebar';
        }
        
        const sidebar = document.getElementById(sidebarId);
        if (sidebar) {
            sidebar.classList.add('active');
            
            // Add close functionality
            const closeBtn = sidebar.querySelector('.close-sidebar');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                });
            }
        }
    }
    
    // Helper functions
    function getUserProfilePage(userType) {
        if (userType === 'user') return 'user.html';
        if (userType === 'organization') return 'organization.html';
        if (userType === 'admin') return 'admin.html';
        return 'user.html';
    }
    
    function getUserRoleLabel(userType) {
        if (userType === 'user') return 'Student';
        if (userType === 'organization') return 'Company';
        if (userType === 'admin') return 'Administrator';
        return 'User';
    }
    
    function getDashboardUrl(userType) {
        if (userType === 'user') return 'dashboards/student/dashboard.html';
        if (userType === 'organization') return 'dashboards/organization/dashboard.html';
        if (userType === 'admin') return 'dashboards/admin/dashboard.html';
        return 'index.html';
    }
    
    /**
     * Logout function - updated to handle account_id cookie and separate admin authentication
     */
    function logout(isAdmin = false) {
        console.log('Logging out...');
        
        // Choose the appropriate logout endpoint
        const logoutUrl = isAdmin ? 
            '../backend/api/admin/adminlogin.php?logout=1' : 
            '../backend/api/auth.php?action=logout';
        
        fetch(logoutUrl)
            .then(response => response.json())
            .then(data => {
                console.log('Logout response:', data);
                if (data.success) {
                    // Clear any account-related data from localStorage if used
                    localStorage.removeItem('account_id');
                    localStorage.removeItem('account_type');
                    
                    // Redirect based on admin status or API response
                    let redirectTarget = data.redirect || 'index.html';
                    if (isAdmin && !data.redirect) {
                        redirectTarget = 'auth/admin-login.html'; // Default admin logout redirect
                    }
                    window.location.href = redirectTarget;
                } else {
                    console.error('Logout failed:', data.message);
                    alert('Logout failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert('An error occurred during logout. Please try again.');
                // Fallback redirect
                window.location.href = isAdmin ? 'auth/admin-login.html' : 'index.html';
            });
    }
    
    // Handle mobile menu 
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('mobile-menu-open');
            document.querySelector('.nav-links').classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
    }
});