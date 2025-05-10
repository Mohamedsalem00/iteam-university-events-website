document.addEventListener("DOMContentLoaded", function() {
  // Load navbar and footer
  const navbarContainer = document.getElementById("navbar");
  const footerContainer = document.getElementById("footer");

  if (navbarContainer) {
    fetch("components/navbar.html")
      .then(response => response.text())
      .then(data => {
        navbarContainer.innerHTML = data;
        initNavbar();
        highlightActiveLink(); // Add this new function call
      })
      .catch(error => {
        console.error("Error loading navbar:", error);
      });
  }

  if (footerContainer) {
    fetch("components/footer.html")
      .then(response => response.text())
      .then(data => {
        footerContainer.innerHTML = data;
      })
      .catch(error => {
        console.error("Error loading footer:", error);
      });
  }

  // Check if we're on an authentication page
  checkAuthPage();
});

function initNavbar() {
  const menuBtn = document.querySelector(".mobile-menu-btn");
  const navLinks = document.querySelector(".nav-links");
  const body = document.body;

  if (menuBtn && navLinks) {
    menuBtn.addEventListener("click", () => {
      menuBtn.classList.toggle("mobile-menu-open");
      navLinks.classList.toggle("active");
      body.classList.toggle("menu-open");
    });

    // Close menu when clicking nav links
    const links = document.querySelectorAll(".nav-link");
    links.forEach(link => {
      link.addEventListener("click", () => {
        menuBtn.classList.remove("mobile-menu-open");
        navLinks.classList.remove("active");
        body.classList.remove("menu-open");
      });
    });

    // Close menu when clicking outside
    document.addEventListener("click", (e) => {
      if (body.classList.contains("menu-open") && 
          !e.target.closest(".nav-links") && 
          !e.target.closest(".mobile-menu-btn")) {
        menuBtn.classList.remove("mobile-menu-open");
        navLinks.classList.remove("active");
        body.classList.remove("menu-open");
      }
    });
  }
}

// New function to highlight the active link properly
function highlightActiveLink() {
  // Get the current path excluding domain and base URL
  let currentPath = window.location.pathname;
  const baseUrl = '/iteam-university-website/frontend/';
  
  // Remove the base URL to get the relative path
  if (currentPath.startsWith(baseUrl)) {
    currentPath = currentPath.substring(baseUrl.length);
  }
  
  // Default to index.html for the root path
  if (currentPath === '' || currentPath === '/' || currentPath === 'index.html') {
    currentPath = 'index.html';
  }
  
  // For dashboard pages, highlight the dashboard link if it exists
  if (currentPath.includes('dashboards/')) {
    currentPath = 'dashboard';
  }
  
  const navLinks = document.querySelectorAll(".nav-link");
  
  navLinks.forEach((link) => {
    // Get href attribute value
    let href = link.getAttribute("href");
    
    // Remove base URL from href if present
    if (href && href.startsWith(baseUrl)) {
      href = href.substring(baseUrl.length);
    }
    
    // Special case for dashboard
    if (currentPath === 'dashboard' && href && href.includes('dashboard')) {
      link.classList.add("active");
      return;
    }
    
    // Match the end of the URL to handle both full paths and relative paths
    if (href && 
        (currentPath.endsWith(href) || 
         href.endsWith(currentPath) || 
         (currentPath.includes('/') && href.endsWith(currentPath.split('/').pop())))) {
      link.classList.add("active");
    }
  });
}

// Update or add this function to properly handle auth pages
function checkAuthPage() {
  const isAuthPage = window.location.pathname.includes('/auth/');
  
  if (isAuthPage) {
    // On auth pages, hide the main navigation links
    const mainNavLinks = document.querySelector('.nav-main-links');
    if (mainNavLinks) {
      mainNavLinks.style.display = 'none';
    }
    
    // On auth pages, hide mobile menu button since there's no menu to show
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
      mobileMenuBtn.style.display = 'none';
    }
    
    // On auth pages, hide the main footer content but keep the copyright
    const mainFooterContent = document.querySelector('.footer-grid');
    if (mainFooterContent) {
      mainFooterContent.style.display = 'none';
    }
    
    // Add class to navbar to adjust its style on auth pages
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      navbar.classList.add('auth-navbar');
    }
    
    // Add class to footer to adjust its style on auth pages
    const footer = document.querySelector('.footer');
    if (footer) {
      footer.classList.add('auth-footer');
    }
  }
}
