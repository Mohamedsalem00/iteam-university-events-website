/**
 * Components loader for the organization dashboard
 * Handles loading navigation components and setting active state
 */

// Pages configuration with titles
const PAGES = {
    'dashboard': 'Organization Dashboard',
    'events': 'Manage Events',
    'registrations': 'Event Registrations',
    'job-offers': 'Job Offers',
    'analytics': 'Analytics',
    'profile': 'Organization Profile'
};

document.addEventListener('DOMContentLoaded', function() {
    // Get current page from URL
    const currentPath = window.location.pathname;
    const pageName = currentPath.split('/').pop().split('.')[0];
    
    // Load components
    loadComponents(pageName);
    
    // Bind common event handlers
    bindCommonEventHandlers();
    
    // Set page title
    if (PAGES[pageName]) {
        document.title = PAGES[pageName] + ' - Event Platform';
        document.getElementById('page-title').textContent = PAGES[pageName];
    }
});

/**
 * Loads all navigation components and sets active states
 */
function loadComponents(currentPage) {
    // Load sidebar
    fetch('../../components/sidebar.html')
        .then(response => response.text())
        .then(html => {
            document.getElementById('sidebar-container').innerHTML = html;
            setActiveNavItem(currentPage);
            
            // Bind logout handler
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', handleLogout);
            }
        });
    
    // Load mobile sidebar
    fetch('../../components/mobile-sidebar.html')
        .then(response => response.text())
        .then(html => {
            document.getElementById('mobile-sidebar-container').innerHTML = html;
            setActiveMobileNavItem(currentPage);
            
            // Setup mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const closeSidebar = document.getElementById('close-sidebar');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            
            if (sidebarToggle && mobileSidebar) {
                sidebarToggle.addEventListener('click', function() {
                    mobileSidebar.classList.toggle('hidden');
                });
            }
            
            if (closeSidebar && mobileSidebar) {
                closeSidebar.addEventListener('click', function() {
                    mobileSidebar.classList.add('hidden');
                });
            }
            
            // Bind mobile logout handler
            const mobileLogoutBtn = document.querySelector('.mobile-logout-btn');
            if (mobileLogoutBtn) {
                mobileLogoutBtn.addEventListener('click', handleLogout);
            }
        });
    
    // Load top nav
    fetch('../../components/topnav.html')
        .then(response => response.text())
        .then(html => {
            document.getElementById('topnav-container').innerHTML = html;
            
            // Set page title
            if (PAGES[currentPage]) {
                document.getElementById('page-title').textContent = PAGES[currentPage];
            }
            
            setupNotifications();
            
            // Setup dark mode toggle
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    document.documentElement.classList.toggle('dark');
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    localStorage.setItem('darkMode', isDarkMode ? 'dark' : 'light');
                });
            }
        });
    
    // Load organization data
    loadOrgData();
}

/**
 * Sets the active state for main navigation based on current page
 */
function setActiveNavItem(currentPage) {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('data-page');
        
        if (linkPage === currentPage) {
            // Active state
            link.classList.add('bg-theme-primary', 'text-white');
            link.classList.remove('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
            
            // Also set the icon color
            const icon = link.querySelector('svg');
            if (icon) {
                icon.classList.add('text-white');
                icon.classList.remove('text-gray-500', 'dark:text-gray-400');
            }
        } else {
            // Inactive state
            link.classList.remove('bg-theme-primary', 'text-white');
            link.classList.add('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
        }
    });
}

/**
 * Sets the active state for mobile navigation based on current page
 */
function setActiveMobileNavItem(currentPage) {
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
    
    mobileNavLinks.forEach(link => {
        const linkPage = link.getAttribute('data-page');
        
        if (linkPage === currentPage) {
            // Active state
            link.classList.add('bg-theme-primary', 'text-white');
            link.classList.remove('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
            
            // Also set the icon color
            const icon = link.querySelector('svg');
            if (icon) {
                icon.classList.add('text-white');
                icon.classList.remove('text-gray-500', 'dark:text-gray-400');
            }
        } else {
            // Inactive state
            link.classList.remove('bg-theme-primary', 'text-white');
            link.classList.add('text-gray-700', 'dark:text-gray-300', 'hover:bg-gray-100', 'dark:hover:bg-gray-700');
        }
    });
}

/**
 * Load organization data and update UI elements
 */
function loadOrgData() {
    fetch('../../api/organization.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const org = data.organization;
                
                // Set organization name and initial
                document.getElementById('org-name').textContent = org.name;
                document.querySelector('.mobile-org-name').textContent = org.name;
                
                const initial = org.name.charAt(0).toUpperCase();
                document.getElementById('org-avatar').textContent = initial;
                document.querySelector('.mobile-org-avatar').textContent = initial;
                
                // Save org ID for other functions
                window.orgId = org.organization_id;
            }
        })
        .catch(error => {
            console.error('Error loading organization data:', error);
        });
}

/**
 * Set up notifications system
 */
function setupNotifications() {
    // Notification dropdown functionality
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function() {
            notificationDropdown.classList.toggle('hidden');
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!notificationBtn.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }

    // Mark all notifications as read
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            // API call to mark all as read
            fetch('../../api/notifications.php?action=mark_all_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('notification-badge').classList.add('hidden');
                    const notificationItems = document.querySelectorAll('.notification-item');
                    notificationItems.forEach(item => {
                        item.classList.remove('bg-blue-50', 'dark:bg-blue-900');
                        item.classList.add('bg-white', 'dark:bg-gray-800');
                    });
                }
            });
        });
    }

    // Load notifications
    loadNotifications();
}

/**
 * Load notifications from API
 */
function loadNotifications() {
    fetch('../../api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationList = document.getElementById('notification-list');
                const notificationBadge = document.getElementById('notification-badge');
                
                if (!notificationList || !notificationBadge) return;
                
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 p-4 text-center">No notifications</p>';
                    notificationBadge.classList.add('hidden');
                    return;
                }
                
                // Count unread notifications
                const unreadCount = data.notifications.filter(notif => !notif.is_read).length;
                
                if (unreadCount > 0) {
                    notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    notificationBadge.classList.remove('hidden');
                } else {
                    notificationBadge.classList.add('hidden');
                }
                
                // Build notification list
                let notificationsHtml = '';
                
                data.notifications.forEach(notif => {
                    const notifDate = new Date(notif.send_date);
                    const timeAgo = getTimeAgo(notifDate);
                    
                    notificationsHtml += `
                        <div class="px-4 py-3 ${!notif.is_read ? 'bg-blue-50 dark:bg-blue-900' : 'bg-white dark:bg-gray-800'} notification-item" data-notif-id="${notif.notification_id}">
                            <p class="text-sm text-gray-900 dark:text-white">
                                ${notif.message}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ${timeAgo}
                            </p>
                        </div>
                    `;
                });
                
                notificationList.innerHTML = notificationsHtml;
                
                // Add click event to mark as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const notifId = this.getAttribute('data-notif-id');
                        
                        // API call to mark as read
                        fetch(`../../api/notifications.php?action=mark_as_read`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ notification_id: notifId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Mark the notification as read in the UI
                                this.classList.remove('bg-blue-50', 'dark:bg-blue-900');
                                this.classList.add('bg-white', 'dark:bg-gray-800');
                                
                                // Decrease the badge count or hide it
                                const badge = document.getElementById('notification-badge');
                                const currentCount = parseInt(badge.textContent);
                                if (currentCount > 1) {
                                    badge.textContent = currentCount - 1;
                                } else {
                                    badge.classList.add('hidden');
                                }
                            }
                        });
                    });
                });
            }
        });
}

/**
 * Handle user logout
 */
function handleLogout() {
    // API call to logout
    fetch('../../api/logout.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear any stored user data
            localStorage.removeItem('userType');
            
            // Redirect to login page
            window.location.href = '../login.html';
        }
    });
}

/**
 * Bind common event handlers for all pages
 */
function bindCommonEventHandlers() {
    // Any common handlers that should be applied to all pages
}

/**
 * Format relative time (time ago)
 */
function getTimeAgo(date) {
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (seconds < 60) return `${seconds} sec ago`;
    if (minutes < 60) return `${minutes} min ago`;
    if (hours < 24) return `${hours} hr ago`;
    return `${days} day ago`;
}