document.addEventListener("DOMContentLoaded", function () {
  // Check for saved theme preference or use preferred color scheme
  const darkThemeMq = window.matchMedia("(prefers-color-scheme: dark)");
  const darkThemeSelected =
    localStorage.getItem("dark-mode") === "true" ||
    (localStorage.getItem("dark-mode") === null && darkThemeMq.matches);

  // Apply theme on page load
  if (darkThemeSelected) {
    document.body.classList.add("dark-mode");
  }

  const themeInterval = setInterval(() => {
    const themeBtn = document.getElementById("theme-switch");
    if (themeBtn) {
      themeBtn.addEventListener("click", toggleTheme);
      clearInterval(themeInterval);
    }
  }, 100);
});

function toggleTheme() {
  // Toggle dark mode class
  document.body.classList.toggle("dark-mode");

  // Save preference to localStorage
  const isDarkMode = document.body.classList.contains("dark-mode");
  localStorage.setItem("dark-mode", isDarkMode);
}
