document.addEventListener("DOMContentLoaded", function () {
  // Reveal animation on scroll
  window.addEventListener("scroll", revealElements);

  // Initial check for elements to reveal
  revealElements();

  // Initialize any interactive elements
  initElements();
});

// Reveal elements on scroll
function revealElements() {
  const reveals = document.querySelectorAll(".reveal");

  for (let i = 0; i < reveals.length; i++) {
    const windowHeight = window.innerHeight;
    const elementTop = reveals[i].getBoundingClientRect().top;
    const elementVisible = 150;

    if (elementTop < windowHeight - elementVisible) {
      reveals[i].classList.add("active");
    }
  }
}

// Initialize interactive elements
function initElements() {
  // Gallery lightbox (if on gallery page)
  const galleryItems = document.querySelectorAll(".gallery-item");
  if (galleryItems.length > 0) {
    galleryItems.forEach((item) => {
      item.addEventListener("click", () => {
        // Simple lightbox effect could be added here
        console.log("Gallery item clicked");
      });
    });
  }

  // Form validation (if on forms)
  const forms = document.querySelectorAll("form");
  if (forms.length > 0) {
    forms.forEach((form) => {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        // Basic validation could go here
        alert("Form submitted! (This is a demo - no data was actually sent)");
        form.reset();
      });
    });
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Elements to apply parallax to
  const heroBackground = document.querySelector(".hero-background");
  const waves = document.querySelectorAll(".wave");
  const circles = document.querySelectorAll(".circle");

  // Check if it's not a touch device (mobile fallback)
  const isDesktop = window.matchMedia("(hover: hover)").matches;

  if (isDesktop) {
    // Parallax on scroll
    window.addEventListener("scroll", function () {
      // Use requestAnimationFrame for better performance
      requestAnimationFrame(function () {
        const scrollTop = window.pageYOffset;
        const heroHeight =
          document.querySelector(".parallax-hero").offsetHeight;

        // Only apply parallax if scrolled within the hero section
        if (scrollTop <= heroHeight) {
          // Move background slower than scroll
          const translateY = scrollTop * 0.4;
          heroBackground.style.transform = `translateY(${translateY}px)`;

          // Move each wave at different speeds
          waves.forEach((wave, index) => {
            const waveSpeed = 0.1 + index * 0.1;
            wave.style.transform = `translateY(${scrollTop * waveSpeed}px)`;
          });

          // Move each circle at different speeds
          circles.forEach((circle, index) => {
            const direction = index % 2 === 0 ? -1 : 1;
            const circleSpeed = 0.05 + index * 0.05;
            circle.style.transform = `translate(${
              scrollTop * circleSpeed * direction
            }px, ${scrollTop * circleSpeed}px)`;
          });
        }
      });
    });

    // Initial call to set positions
    window.dispatchEvent(new Event("scroll"));
  } else {
    // For mobile, still have a subtle effect
    // This makes the background slightly responsive to device orientation
    window.addEventListener("deviceorientation", function (event) {
      if (event.beta && event.gamma) {
        requestAnimationFrame(function () {
          const tiltY = event.beta / 90; // -1 to 1
          const tiltX = event.gamma / 90; // -1 to 1

          // Subtle tilt effect
          heroBackground.style.transform = `translate(${tiltX * 10}px, ${
            tiltY * 10
          }px)`;

          // Move circles slightly based on device orientation
          circles.forEach((circle, index) => {
            const modifier = (index + 1) * 2;
            circle.style.transform = `translate(${tiltX * modifier * 5}px, ${
              tiltY * modifier * 5
            }px)`;
          });
        });
      }
    });
  }
});
