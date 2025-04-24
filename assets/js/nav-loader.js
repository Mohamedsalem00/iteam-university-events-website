document.addEventListener("DOMContentLoaded", function () {
  // Load navbar
  fetch("/components/navbar.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("navbar").innerHTML = data;
      initNavbar();
    })
    .catch((error) => console.error("Error loading navbar:", error));

  // Load footer
  fetch("/components/footer.html")
    .then((response) => response.text())
    .then((data) => {
      document.getElementById("footer").innerHTML = data;
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
