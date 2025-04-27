document.addEventListener("DOMContentLoaded", function () {
  // Load navbar
  fetch("components/navbar.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("navbar").innerHTML = data;
      initNavbar();
      checkAuthPage(); // Check if we're on an auth page
    })
    .catch((error) => console.error("Error loading navbar:", error));

  // Load footer
  fetch("components/footer.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("footer").innerHTML = data;
      checkAuthPage(); // Check if we're on an auth page
    })
    .catch((error) => console.error("Error loading footer:", error));
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

  // Highlight current page in navigation
  const currentPage = window.location.pathname;
  const navLinks2 = document.querySelectorAll(".nav-link");

  navLinks2.forEach((link) => {
    if (link.getAttribute("href") === currentPage) {
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
    
    // Check if we're on the login page and change the auth link text accordingly
    const authNavItem = document.querySelector('.auth-nav-item a');
    if (authNavItem) {
      if (window.location.pathname.includes('/login.html')) {
        authNavItem.href = '/auth/register.html';
        authNavItem.querySelector('.auth-text').textContent = 'Sign Up';
      } else {
        authNavItem.href = '/auth/login.html';
        authNavItem.querySelector('.auth-text').textContent = 'Sign In';
      }
    }
  }
}
