document.addEventListener("DOMContentLoaded", function () {
    const htmlElement = document.documentElement;
    const sidebar = document.getElementById("sidebar");
    const mobileSidebar = document.getElementById("mobileSidebar");
    const menuButton = document.getElementById("menuButton");
    const mobileMenuButton = document.getElementById("mobileMenuButton"); // For opening
    const closeMobileSidebarButton = document.getElementById("closeMobileSidebarButton"); // For closing
    const darkModeToggle = document.getElementById("darkModeToggle");

    // --- Dark Mode ---
    const applyTheme = (theme) => {
        if (theme === "dark") {
            htmlElement.classList.add("dark");
            if (darkModeToggle) darkModeToggle.checked = true;
        } else {
            htmlElement.classList.remove("dark");
            if (darkModeToggle) darkModeToggle.checked = false;
        }
    };

    const savedTheme = localStorage.getItem("theme");
    const systemPrefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (systemPrefersDark) {
        applyTheme("dark");
    } else {
        applyTheme("light");
    }

    if (darkModeToggle) {
        darkModeToggle.addEventListener("change", function () {
            if (this.checked) {
                htmlElement.classList.add("dark");
                localStorage.setItem("theme", "dark");
            } else {
                htmlElement.classList.remove("dark");
                localStorage.setItem("theme", "light");
            }
        });
    }

    // --- Sidebar Toggle Logic ---
    if (menuButton && sidebar) {
        menuButton.addEventListener("click", () => {
            sidebar.classList.toggle("-translate-x-full");
            // Adjust main content margin if needed, or use CSS grid for layout
        });
    }

    if (mobileMenuButton && mobileSidebar) {
        mobileMenuButton.addEventListener("click", (e) => {
            e.stopPropagation();
            mobileSidebar.style.transform = "translateX(0%)";
        });
    }

    if (closeMobileSidebarButton && mobileSidebar) {
        closeMobileSidebarButton.addEventListener("click", (e) => {
            e.stopPropagation();
            mobileSidebar.style.transform = "translateX(-100%)";
        });
    }
    
    // Close mobile sidebar if clicking outside of it
    document.addEventListener('click', function(event) {
        if (mobileSidebar && mobileSidebar.style.transform === "translateX(0%)") {
            if (!mobileSidebar.contains(event.target) && event.target !== mobileMenuButton && !mobileMenuButton.contains(event.target)) {
                mobileSidebar.style.transform = "translateX(-100%)";
            }
        }
    });

    // Register 404 page handler if router exists
    if (window.router && window.router.registerRouteHandler) {
        window.router.registerRouteHandler('404', function(params) {
            console.log('404 handler executed for:', params.attemptedView);
            // You can add analytics tracking here for 404 errors
            
            // Optional: Log 404 errors to server
            if (params && params.attemptedView) {
                const logData = new FormData();
                logData.append('page', params.attemptedView);
                logData.append('referrer', document.referrer);
                
                // Send 404 data to server asynchronously (implement this API endpoint)
                fetch('/iteam-university-website/admin/api/log_404.php', {
                    method: 'POST',
                    body: logData
                }).catch(err => console.error('Failed to log 404 error:', err));
            }
        });
    }
});