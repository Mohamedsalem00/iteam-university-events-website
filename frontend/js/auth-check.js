/**
 * Authentication check script
 * Handles session verification, redirection, and dashboard protection
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get current page path
    const currentPath = window.location.pathname;
    const baseUrl = '/iteam-university-website/frontend/';
    
    // Check if current page is login or register
    const isLoginPage = currentPath.includes('/auth/login.html');
    const isAdminLoginPage = currentPath.includes('/admin/admin-login.html');
    const isRegisterPage = currentPath.includes('/auth/register.html');
    const isAuthPage = isLoginPage || isRegisterPage || isAdminLoginPage;
    
    // Check if current page is a dashboard or protected area
    const isDashboardPage = 
        currentPath.includes('/dashboards/') || 
        currentPath.includes('/admin/dashboard') || 
        currentPath.includes('/profile/');
    
    // Function to check authentication status
    function checkAuth() {
        return fetch('../backend/api/auth.php?check=1')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .catch(error => {
                console.error('Auth check error:', error);
                return { success: false };
            });
    }
    
    // Check auth status and handle page redirections
    checkAuth().then(data => {
        console.log('Auth check result:', data);
        
        if (data.success && data.user) {
            // User is logged in
            console.log('User is logged in as:', data.user.type);
            
            // Update the navbar to show user menu instead of auth links
            showUserMenu(data.user);
            
            // Prevent going back to login/register pages if already logged in
            if (isAuthPage) {
                console.log('Redirecting from auth page to dashboard');
                // Get the appropriate dashboard
                let dashboardUrl = data.user.dashboard_url;
                if (!dashboardUrl) {
                    // Default dashboards based on user type
                    dashboardUrl = getDashboardUrl(data.user.type);
                }
                
                // Redirect to appropriate dashboard
                setTimeout(() => {
                    window.location.href = baseUrl + dashboardUrl;
                }, 100);
            }
        } else {
            // User is not logged in
            console.log('User is not logged in');
            
            // Show auth links and hide user menu
            showAuthLinks();
            
            // Redirect to login if trying to access protected pages
            if (isDashboardPage) {
                console.log('Redirecting from protected page to login');
                // Determine if this is an admin page
                const isAdminPage = currentPath.includes('/admin/') || 
                                  currentPath.includes('/admin-dashboard');
                
                // Store the attempted URL to redirect back after login
                sessionStorage.setItem('redirectAfterLogin', window.location.href);
                
                // Redirect to the appropriate login page
                if (isAdminPage) {
                    window.location.href = baseUrl + 'admin/admin-login.html';
                } else {
                    window.location.href = baseUrl + 'auth/login.html';
                }
            }
        }
    });
    
    // Function to show auth links (logged out state)
    function showAuthLinks() {
        // Show auth links
        const authLinks = document.querySelector('.auth-links');
        if (authLinks) {
            authLinks.style.display = 'block';
        }
        
        // Hide user menu
        const userMenu = document.getElementById('user-menu');
        if (userMenu) {
            userMenu.style.display = 'none';
        }
        
        // Remove any existing dropdown
        const existingDropdown = document.querySelector('.user-dropdown');
        if (existingDropdown) {
            existingDropdown.remove();
        }
    }
    
    // Function to show user menu (logged in state)
    function showUserMenu(user) {
        // Hide auth links
        const authLinks = document.querySelector('.auth-links');
        if (authLinks) {
            authLinks.style.display = 'none';
        }
        
        // Show and update user menu
        const userMenu = document.getElementById('user-menu');
        if (userMenu) {
            userMenu.style.display = 'flex';
            
            // Clear existing content
            userMenu.innerHTML = '';
            
            // Create user avatar
            let profilePicHtml = '';
            if (user.profile_picture) {
                profilePicHtml = `<img src="${user.profile_picture}" alt="Profile" class="profile-pic">`;
            } else {
                const initials = user.name.split(' ')
                    .map(name => name.charAt(0))
                    .join('')
                    .toUpperCase()
                    .substring(0, 2);
                profilePicHtml = `<div class="profile-initials">${initials}</div>`;
            }
            
            // Create avatar, user info and dropdown icon
            const avatarDiv = document.createElement('div');
            avatarDiv.className = 'user-avatar';
            avatarDiv.innerHTML = profilePicHtml;
            userMenu.appendChild(avatarDiv);
            
            // User info (name, role)
            const userInfoDiv = document.createElement('div');
            userInfoDiv.className = 'user-info';
            userInfoDiv.innerHTML = `
                <span class="user-name">${user.name}</span>
                <span class="user-role">${getUserRoleLabel(user.type)}</span>
            `;
            userMenu.appendChild(userInfoDiv);
            
            // Dropdown icon
            const dropdownIcon = document.createElement('i');
            dropdownIcon.className = 'fas fa-chevron-down dropdown-icon';
            userMenu.appendChild(dropdownIcon);
            
            // Create dropdown menu
            const dropdown = document.createElement('div');
            dropdown.className = 'user-dropdown';
            dropdown.innerHTML = `
                <a href="${user.dashboard_url || getDashboardUrl(user.type)}" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="${baseUrl}profile/${getUserProfilePage(user.type)}" class="dropdown-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="#" id="logout-link" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            `;
            
            // Append dropdown to user menu
            userMenu.appendChild(dropdown);
            
            // Add event listener to toggle dropdown
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
            const logoutLink = document.getElementById('logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logout();
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
        if (userType === 'user') return 'dashboards/student-dashboard.html';
        if (userType === 'organization') return 'dashboards/company-dashboard.html';
        if (userType === 'admin') return 'dashboards/admin-dashboard.html';
        return 'index.html';
    }
    
    // Logout function
    function logout() {
        console.log('Logging out...');
        fetch('../backend/api/auth.php?logout=1')
            .then(response => response.json())
            .then(data => {
                console.log('Logout response:', data);
                if (data.success) {
                    window.location.href = baseUrl + 'index.html';
                } else {
                    console.error('Logout failed:', data.message);
                    alert('Logout failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert('An error occurred during logout. Please try again.');
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