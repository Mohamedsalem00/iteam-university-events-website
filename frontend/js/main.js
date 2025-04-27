document.addEventListener("DOMContentLoaded", function () {
  // Form validation for login and registration forms
  initFormValidation();

  // Reveal animation on scroll
  window.addEventListener("scroll", revealElements);

  // Initial check for elements to reveal
  revealElements();

  // Initialize any interactive elements
  initElements();
});

// Form validation for login and registration forms
function initFormValidation() {
  const forms = document.querySelectorAll(".validation-form");
  
  if (forms.length === 0) return;
  
  forms.forEach(form => {
    const inputs = form.querySelectorAll("input[data-validation]");
    const submitBtn = form.querySelector("button[type='submit']");
    
    // Add input event listeners to all fields
    inputs.forEach(input => {
      input.addEventListener("input", () => {
        validateField(input);
        checkFormValidity(form, submitBtn);
      });
      
      input.addEventListener("blur", () => {
        validateField(input);
        checkFormValidity(form, submitBtn);
      });
    });
    
    // Form submit handling
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      
      // Validate all fields on submit
      let isValid = true;
      inputs.forEach(input => {
        if (!validateField(input)) {
          isValid = false;
        }
      });
      
      if (isValid) {
        // Check if this is a login or register form that should be handled by its own JS
        if (form.id === 'login-form' || form.id === 'registration-form') {
          // Do nothing - let the specific form handler take care of it
          return;
        }
        
        // For other forms, we'll collect the data and submit via fetch
        const formData = new FormData(form);
        const formAction = form.getAttribute('data-action') || form.action;
        
        if (formAction) {
          // Submit the form data to the backend
          fetch(formAction, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Show success message if provided
              if (data.message) {
                alert(data.message);
              }
              
              // Reset the form
              form.reset();
              
              // Reset validation state
              inputs.forEach(input => {
                input.classList.remove("is-valid");
                const messageEl = input.nextElementSibling;
                if (messageEl && messageEl.classList.contains("validation-message")) {
                  messageEl.textContent = "";
                  messageEl.classList.remove("error", "success");
                }
              });
              
              // Disable submit button again
              if (submitBtn) submitBtn.disabled = true;
              
              // Redirect if a redirect URL is provided
              if (data.redirect) {
                window.location.href = data.redirect;
              }
            } else {
              // Show error message
              alert(data.message || "An error occurred. Please try again.");
            }
          })
          .catch(error => {
            console.error("Form submission error:", error);
            alert("An error occurred while submitting the form. Please try again later.");
          });
        }
      }
    });
  });
}

// Validate a single field based on its data-validation attribute
function validateField(input) {
  const validationType = input.getAttribute("data-validation");
  const value = input.value.trim();
  const messageEl = input.nextElementSibling;
  
  if (!messageEl || !messageEl.classList.contains("validation-message")) return false;
  
  let isValid = true;
  let message = "";
  
  // Clear previous validation
  input.classList.remove("is-valid", "is-invalid");
  
  // Check validation type
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
      // Enhanced password validation for auth pages
      const hasMinLength = value.length >= 8;
      const hasUppercase = /[A-Z]/.test(value);
      const hasNumber = /[0-9]/.test(value);
      const hasSpecial = /[^A-Za-z0-9]/.test(value);
      
      // Check if we're on an auth page with stricter requirements
      const isAuthPage = document.querySelector('.auth-section') !== null;
      
      if (isAuthPage) {
        isValid = hasMinLength && hasUppercase && hasNumber && hasSpecial;
        
        if (value === "") {
          message = "Password is required.";
        } else if (!hasMinLength) {
          message = "Password must be at least 8 characters.";
        } else if (!hasUppercase) {
          message = "Password must include an uppercase letter.";
        } else if (!hasNumber) {
          message = "Password must include a number.";
        } else if (!hasSpecial) {
          message = "Password must include a special character.";
        }
      } else {
        // Simpler validation for non-auth pages
        isValid = value.length >= 8;
        message = value === "" ? "Password is required." : 
                  isValid ? "" : "Password must be at least 8 characters long.";
      }
      break;
      
    case "confirm-password":
      const passwordInput = document.getElementById("password");
      const passwordValue = passwordInput ? passwordInput.value : "";
      isValid = value !== "" && value === passwordValue;
      message = value === "" ? "Please confirm your password." : 
                isValid ? "" : "Passwords do not match.";
      break;
  }
  
  // Update UI based on validation result
  if (isValid) {
    input.classList.add("is-valid");
    messageEl.classList.remove("error");
    messageEl.classList.add("success");
  } else {
    input.classList.add("is-invalid");
    messageEl.classList.remove("success");
    messageEl.classList.add("error");
  }
  
  messageEl.textContent = message;
  return isValid;
}

// Check if all form fields are valid and enable/disable submit button
function checkFormValidity(form, submitBtn) {
  if (!submitBtn) return;
  
  const inputs = form.querySelectorAll("input[data-validation]");
  let allValid = true;
  
  inputs.forEach(input => {
    // Check if this input is valid
    const isInputEmpty = input.value.trim() === "";
    const isInputValid = input.classList.contains("is-valid");
    
    if (isInputEmpty || !isInputValid) {
      allValid = false;
    }
  });
  
  // Also check if terms checkbox is checked (if it exists)
  const termsCheckbox = form.querySelector("#terms");
  if (termsCheckbox && !termsCheckbox.checked) {
    allValid = false;
  }
  
  submitBtn.disabled = !allValid;
}

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

  // Form validation (for non-validation-form classes)
  // Update this to use fetch for form submissions instead of showing a demo alert
  const oldForms = document.querySelectorAll("form:not(.validation-form)");
  if (oldForms.length > 0) {
    oldForms.forEach((form) => {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        
        const formAction = form.getAttribute('data-action') || form.action;
        if (formAction) {
          const formData = new FormData(form);
          
          fetch(formAction, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              if (data.message) {
                alert(data.message);
              }
              form.reset();
              if (data.redirect) {
                window.location.href = data.redirect;
              }
            } else {
              alert(data.message || "An error occurred. Please try again.");
            }
          })
          .catch(error => {
            console.error("Form submission error:", error);
            alert("An error occurred while submitting the form. Please try again later.");
          });
        } else {
          console.warn("Form has no action specified");
        }
      });
    });
  }
}

// Parallax effects
document.addEventListener("DOMContentLoaded", function () {
  // Elements to apply parallax to
  const heroBackground = document.querySelector(".hero-background");
  const waves = document.querySelectorAll(".wave");
  const circles = document.querySelectorAll(".circle");

  // Check if elements exist and if it's not a touch device
  if (heroBackground && waves.length > 0 && circles.length > 0) {
    const isDesktop = window.matchMedia("(hover: hover)").matches;

    if (isDesktop) {
      // Parallax on scroll
      window.addEventListener("scroll", function () {
        // Use requestAnimationFrame for better performance
        requestAnimationFrame(function () {
          const scrollTop = window.pageYOffset;
          const heroHeight =
            document.querySelector(".parallax-hero")?.offsetHeight;

          // Only apply parallax if scrolled within the hero section
          if (heroHeight && scrollTop <= heroHeight) {
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
  }
});
