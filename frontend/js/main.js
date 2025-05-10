document.addEventListener("DOMContentLoaded", function () {
  // Initialize form validation listeners (for real-time feedback)
  initFormValidationListeners();

  // Reveal animation on scroll
  window.addEventListener("scroll", revealElements);

  // Initial check for elements to reveal
  revealElements();

  // Initialize other interactive elements (like parallax)
  initElements();
});

// Sets up input/blur listeners for real-time validation feedback
function initFormValidationListeners() {
  const forms = document.querySelectorAll(".validation-form");
  
  if (forms.length === 0) return;
  
  forms.forEach(form => {
    const inputs = form.querySelectorAll("input[data-validation], textarea[data-validation], select[data-validation]");
    const submitBtn = form.querySelector("button[type='submit']");
    
    // Add input/change event listeners to all fields for real-time validation
    inputs.forEach(input => {
      const eventType = input.tagName === 'SELECT' ? 'change' : 'input';
      input.addEventListener(eventType, () => {
        validateField(input);
        checkFormValidity(form, submitBtn);
      });
      
      // Also validate on blur
      input.addEventListener("blur", () => {
        validateField(input);
      });
    });

    // Initial check for form validity on load (e.g., if form is pre-filled)
    checkFormValidity(form, submitBtn);
  });
}

// Validate a single field based on its data-validation attribute
function validateField(input) {
  const validationType = input.getAttribute("data-validation");
  const value = input.value.trim();
  let messageEl = input.nextElementSibling;
  if (!messageEl || !messageEl.classList.contains("validation-error")) {
    messageEl = input.closest('.form-group')?.querySelector('.validation-error');
  }

  if (!messageEl) {
    console.warn("No validation message element found for input:", input);
    return true;
  }
  
  let isValid = true;
  let message = "";
  
  input.classList.remove("is-valid", "is-invalid");
  messageEl.textContent = "";
  
  switch (validationType) {
    case "required":
      isValid = value !== "";
      message = isValid ? "" : "This field is required.";
      break;
      
    case "email":
      isValid = value !== "" && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      message = value === "" ? "Email is required." : 
                isValid ? "" : "Please enter a valid email address.";
      break;
      
    case "password":
      const hasMinLength = value.length >= 8;
      const hasUppercase = /[A-Z]/.test(value);
      const hasNumber = /[0-9]/.test(value);
      const hasSpecial = /[^A-Za-z0-9]/.test(value);
      const isAuthPage = document.body.querySelector('.auth-section') !== null;
      
      if (isAuthPage) {
        isValid = hasMinLength && hasUppercase && hasNumber;
        
        if (value === "") {
          message = "Password is required.";
          isValid = false;
        } else if (!hasMinLength) {
          message = "Password must be at least 8 characters.";
          isValid = false;
        } else if (!hasUppercase) {
          message = "Password must include an uppercase letter.";
          isValid = false;
        } else if (!hasNumber) {
          message = "Password must include a number.";
          isValid = false;
        }
      } else {
        isValid = value.length >= 8;
        message = value === "" ? "Password is required." : 
                  isValid ? "" : "Password must be at least 8 characters long.";
      }
      break;
      
    case "confirm-password":
      const passwordInput = document.getElementById("password") || document.getElementById("admin-password"); 
      const passwordValue = passwordInput ? passwordInput.value : "";
      isValid = value !== "" && value === passwordValue;
      message = value === "" ? "Please confirm your password." : 
                isValid ? "" : "Passwords do not match.";
      break;
      
    default:
      isValid = true; 
      break;
  }
  
  if (isValid) {
    input.classList.add("is-valid");
    messageEl.textContent = ""; 
  } else {
    input.classList.add("is-invalid");
    messageEl.textContent = message;
  }
  
  return isValid;
}

// Check if all required fields in a form are valid and enable/disable submit button
function checkFormValidity(form, submitBtn) {
  if (!submitBtn) return;
  
  const inputs = form.querySelectorAll("input[data-validation][required], textarea[data-validation][required], select[data-validation][required]");
  let allValid = true;
  
  inputs.forEach(input => {
    if (!input.classList.contains("is-valid")) {
      allValid = false;
    }
  });
  
  const termsCheckbox = form.querySelector("#terms[required]");
  if (termsCheckbox && !termsCheckbox.checked) {
    allValid = false;
    const termsError = document.getElementById('terms-error');
    if (termsError) termsError.textContent = 'You must agree to the Terms and Privacy Policy';
  } else {
    const termsError = document.getElementById('terms-error');
    if (termsError) termsError.textContent = '';
  }
  
  submitBtn.disabled = !allValid;
}

// Reveal elements on scroll
function revealElements() {
  const reveals = document.querySelectorAll(".reveal");
  const windowHeight = window.innerHeight;
  const elementVisibleThreshold = 100;

  reveals.forEach(el => {
    const elementTop = el.getBoundingClientRect().top;
    if (elementTop < windowHeight - elementVisibleThreshold) {
      el.classList.add("active");
    }
  });
}

// Initialize other interactive elements (non-validation related)
function initElements() {
  const otherForms = document.querySelectorAll("form:not(.validation-form)");
  otherForms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      console.warn("Unhandled form submission for form:", form.id || form);
      alert("Demo: Form submitted (no specific handler in main.js).");
    });
  });

  initParallax();
}

// Parallax effects initialization
function initParallax() {
  const heroBackground = document.querySelector(".hero-background");
  const waves = document.querySelectorAll(".wave");
  const circles = document.querySelectorAll(".circle");

  if (heroBackground && waves.length > 0 && circles.length > 0) {
    const isDesktop = window.matchMedia("(hover: hover)").matches;

    if (isDesktop) {
      const handleScroll = () => {
        requestAnimationFrame(() => {
          const scrollTop = window.pageYOffset;
          const heroHeight = document.querySelector(".parallax-hero")?.offsetHeight;

          if (heroHeight && scrollTop <= heroHeight) {
            const translateY = scrollTop * 0.4;
            heroBackground.style.transform = `translateY(${translateY}px)`;

            waves.forEach((wave, index) => {
              const waveSpeed = 0.1 + index * 0.1;
              wave.style.transform = `translateY(${scrollTop * waveSpeed}px)`;
            });

            circles.forEach((circle, index) => {
              const direction = index % 2 === 0 ? -1 : 1;
              const circleSpeed = 0.05 + index * 0.05;
              circle.style.transform = `translate(${scrollTop * circleSpeed * direction}px, ${scrollTop * circleSpeed}px)`;
            });
          }
        });
      };
      window.addEventListener("scroll", handleScroll);
      handleScroll(); 
    } else {
      const handleOrientation = (event) => {
        if (event.beta && event.gamma) {
          requestAnimationFrame(() => {
            const tiltY = event.beta / 90;
            const tiltX = event.gamma / 90;

            heroBackground.style.transform = `translate(${tiltX * 10}px, ${tiltY * 10}px)`;

            circles.forEach((circle, index) => {
              const modifier = (index + 1) * 2;
              circle.style.transform = `translate(${tiltX * modifier * 5}px, ${tiltY * modifier * 5}px)`;
            });
          });
        }
      };
      window.addEventListener("deviceorientation", handleOrientation);
    }
  }
}
