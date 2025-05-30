/**
 * Modern router using History API for the Event Management Dashboard
 * Handles dynamic routes with path parameters
 */
class Router {
  constructor(options = {}) {
    // Existing constructor properties
    this.contentContainer = document.getElementById(options.contentContainerId || "content");
    this.basePath = options.basePath || "/iteam-university-website/admin/";
    this.defaultView = options.defaultView || "dashboard";
    this.viewsPath = options.viewsPath || "views/";
    this.fileExtension = options.fileExtension || ".html";
    this.activeClass = options.activeClass || "active";
    this.activeBackgroundClass = options.activeBackgroundClass || "bg-primary/10";
    this.activeTextClass = options.activeTextClass || "text-primary";
    this.hoverBgClass = options.hoverBgClass || "hover:bg-gray-100";
    this.darkHoverBgClass = options.darkHoverBgClass || "dark:hover:bg-gray-700";
    
    // New authentication properties
    this.loginPath = options.loginPath || "/iteam-university-website/admin/auth/admin-login.html";
    this.authCheckPath = options.authCheckPath || "/iteam-university-website/admin/api/adminlogin.php?check=1";
    this.defaultRole = options.defaultRole || "admin"; // Default required role
    
    // Define routes with handlers
    this.routes = {
      static: {
        // Static routes (exact match)
        "dashboard": () => this.loadViewContent("dashboard.html"),
        "events": () => this.loadViewContent("events.html"),
        "calendar": () => this.loadViewContent("calendar.html"),
        "students": () => this.loadViewContent("students.html"),
        "organizations": () => this.loadViewContent("organizations.html"),
        "gallery": () => this.loadViewContent("gallery.html"),
        "manage-images": () => this.loadViewContent("manage-images.html"),
        "profile": () => this.loadViewContent("profile.html"),
        "settings": () => this.loadViewContent("settings.html"),
        "create-event": () => this.loadViewContent("create-event.html"),
      },
      dynamic: [
        // Dynamic routes with parameters
        { 
          pattern: /^events\/(\d+)\/?$/, 
          handler: (matches) => this.showEventDetails(matches[1])
        },
        { 
          pattern: /^students\/(\d+)\/?$/, 
          handler: (matches) => this.showStudentDetails(matches[1])
        },
        { 
          pattern: /^organizations\/(\d+)\/?$/, 
          handler: (matches) => this.showOrganizationDetails(matches[1])
        }
      ]
    };
    
    // Store custom route handlers
    this.customHandlers = {};
    
    // Initialize user authentication state
    this.isAuthenticated = false;
    this.userRole = null;
    this.userId = null;
    this.userName = null;
    
    this.initialize();
  }

  async initialize() {
    // Ensure content container exists
    if (!this.contentContainer) {
      console.error("Router: Content container not found");
      return;
    }

    // Check authentication status first
    await this.checkAuthStatus();

    // Handle click events for navigation
    document.body.addEventListener('click', this.handleLinkClick.bind(this));
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', this.handlePopState.bind(this));
    
    // Handle initial page load route
    this.handleInitialRoute();
    
    console.log("Router initialized with auth status:", this.isAuthenticated ? "Authenticated" : "Not authenticated");
  }
  
  // New method to check authentication status
  async checkAuthStatus() {
    try {
      // First check local storage for cached auth data
      const cachedAuth = localStorage.getItem('dashboardAuth');
      if (cachedAuth) {
        const authData = JSON.parse(cachedAuth);
        
        // Check if the stored data is still valid (not expired)
        if (authData.expires && new Date(authData.expires) > new Date()) {
          this.isAuthenticated = true;
          this.userRole = authData.role || "admin";
          this.userId = authData.id;
          this.userName = authData.name;
          console.log("Using cached authentication data");
          return;
        } else {
          // Clear expired auth data
          localStorage.removeItem('dashboardAuth');
        }
      }
      
      // If no valid cached data, check with the server
      const response = await fetch(this.authCheckPath, {
        credentials: 'include' // Include cookies for session-based auth
      });
      
      if (!response.ok) {
        throw new Error(`Auth check failed: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.success && data.admin) {
        // User is authenticated as admin
        this.isAuthenticated = true;
        this.userRole = "admin"; // Always admin for this endpoint
        this.userId = data.admin.id;
        this.userName = data.admin.name;
        
        // Store auth info in localStorage with expiry (24 hours)
        const expiryTime = new Date();
        expiryTime.setHours(expiryTime.getHours() + 24);
        
        localStorage.setItem('dashboardAuth', JSON.stringify({
          authenticated: true,
          role: "admin",
          id: data.admin.id,
          name: data.admin.name,
          expires: expiryTime.toISOString()
        }));
        
        console.log("Authentication verified with server");
      } else {
        // Not authenticated or not admin
        this.isAuthenticated = false;
        this.userRole = null;
        console.log("Not authenticated as admin");
      }
    } catch (error) {
      console.error("Auth check error:", error);
      this.isAuthenticated = false;
      this.userRole = null;
    }
  }
  
  // Method to check if user has required role
  hasRequiredRole(requiredRoles) {
    if (!this.isAuthenticated || !this.userRole) {
      return false;
    }
    
    if (!requiredRoles || requiredRoles.length === 0) {
      return true; // No specific roles required
    }
    
    // Convert comma-separated string to array if needed
    const rolesArray = typeof requiredRoles === 'string' 
                      ? requiredRoles.split(',').map(r => r.trim()) 
                      : requiredRoles;
                      
    return rolesArray.includes(this.userRole);
  }

  handleInitialRoute() {
    // Process the current URL on page load
    const path = window.location.pathname;
    
    // Check if we're at base path, root, or explicit index.html
    if (path === this.basePath || 
        path === this.basePath.slice(0, -1) ||
        path === this.basePath + 'index.html') {
      this.navigateTo(this.defaultView, {}, false);
      return;
    }
    
    // Extract path after base
    let relativePath = path.replace(this.basePath, '');
    
    // Remove trailing slash if present
    if (relativePath.endsWith('/')) {
      relativePath = relativePath.slice(0, -1);
    }
    
    // Get query parameters
    const queryParams = {};
    if (window.location.search) {
      const searchParams = new URLSearchParams(window.location.search);
      searchParams.forEach((value, key) => {
        queryParams[key] = value;
      });
    }
    
    console.log(`Processing initial route: ${relativePath}`);
    
    // Route to the appropriate handler
    this.routeToPath(relativePath, queryParams, false);
  }
  
  routeToPath(path, params = {}, pushState = true) {
    // Check for matches against dynamic routes
    for (const route of this.routes.dynamic) {
      const matches = path.match(route.pattern);
      if (matches) {
        console.log(`Matched dynamic route: ${path} with pattern: ${route.pattern}`);
        route.handler(matches, params);
        return true;
      }
    }
    
    // Check for static routes
    const staticRoute = this.routes.static[path];
    if (staticRoute) {
      console.log(`Matched static route: ${path}`);
      staticRoute(params);
      return true;
    }
    
    // Try with file extension for backward compatibility
    const pathWithExt = path + this.fileExtension;
    const staticRouteWithExt = this.routes.static[path];
    if (staticRouteWithExt) {
      console.log(`Matched static route with extension: ${pathWithExt}`);
      staticRouteWithExt(params);
      return true;
    }
    
    // Check for custom handlers
    if (this.customHandlers[path]) {
      console.log(`Executing custom handler for: ${path}`);
      this.customHandlers[path](params);
      return true;
    }
    
    // If no matches, try direct view loading as fallback
    console.log(`No route matches, attempting to load view directly: ${path}`);
    return this.loadViewContent(`${path}${path.endsWith(this.fileExtension) ? '' : this.fileExtension}`, params, pushState);
  }

  handleLinkClick(event) {
    // Find closest nav-link ancestor (if any)
    const linkElement = event.target.closest('.nav-link');
    if (!linkElement) return;

    // Skip special links
    if (linkElement.id === "logoutLink" || linkElement.id === "mobileLogoutLink") {
      return; // Let dash.js handle logout
    }

    // Get the href or data-view attribute
    const href = linkElement.getAttribute('href') || linkElement.dataset.view;
    if (!href || href === "#" || href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:')) {
      if (href === "#") {
        event.preventDefault(); // Prevent jumping for placeholder links
      }
      return;
    }

    // Prevent default navigation
    event.preventDefault();

    // Extract view name and query parameters
    let path = href;
    let queryParams = {};
    
    const queryIndex = href.indexOf('?');
    if (queryIndex !== -1) {
      path = href.substring(0, queryIndex);
      const queryString = href.substring(queryIndex + 1);
      const urlParams = new URLSearchParams(queryString);
      urlParams.forEach((value, key) => {
        queryParams[key] = value;
      });
    }
    
    // Navigate using our router
    this.navigateTo(path, queryParams);

    // Close mobile sidebar if open
    const mobileSidebar = document.getElementById("mobileSidebar");
    if (mobileSidebar && mobileSidebar.style.display === "block") {
      mobileSidebar.style.display = "none";
    }
  }

  handlePopState(event) {
    // Called when browser history changes (back/forward buttons)
    const state = event.state || {};
    
    if (state.path) {
      // If we have a saved path in state, use that
      this.routeToPath(state.path, state.params || {}, false);
    } else {
      // Otherwise process current path
      this.handleInitialRoute();
    }
  }

  navigateTo(path, params = {}, pushState = true) {
    console.log(`Navigating to: ${path} with params:`, params);
    
    // Remove trailing slash and file extension if they exist
    if (path.endsWith('/')) {
      path = path.slice(0, -1);
    }
    
    if (path.endsWith(this.fileExtension)) {
      path = path.slice(0, -this.fileExtension.length);
    }
    
    // Route to the path
    const success = this.routeToPath(path, params, pushState);
    
    if (!success) {
      console.error(`Failed to route to path: ${path}`);
      this.show404Page(path);
    }
  }

  async loadViewContent(viewName, params = {}, pushState = true) {
    if (!this.contentContainer) {
      console.error("Router: Content container not found");
      return false;
    }
    
    // Build URL for history API
    let urlPath = this.basePath + (viewName.replace(this.fileExtension, ''));
    
    // Add query parameters if any
    let queryString = '';
    if (Object.keys(params).length > 0) {
      const searchParams = new URLSearchParams();
      for (const [key, value] of Object.entries(params)) {
        searchParams.append(key, value);
      }
      queryString = '?' + searchParams.toString();
    }
    
    // Full URL path with query string
    const fullUrlPath = urlPath + queryString;
    
    // Path to fetch the view HTML
    const fetchPath = `${this.viewsPath}${viewName}`;
    
    console.log(`Loading view from: ${fetchPath} with params:`, params);
    
    // Show loading spinner
    this.contentContainer.innerHTML = `
      <div class="flex justify-center items-center h-64">
        <i class="ri-loader-4-line ri-spin text-3xl text-primary"></i>
      </div>`;

    try {
      // Update browser history if needed
      if (pushState) {
        window.history.pushState(
          { path: viewName.replace(this.fileExtension, ''), params },
          '', 
          fullUrlPath
        );
      }

      // Fetch the view HTML
      const response = await fetch(fetchPath);
      if (!response.ok) {
        throw new Error(`Failed to load view ${viewName}: ${response.status} ${response.statusText}`);
      }
      
      // Get HTML content
      const html = await response.text();
      
      // Create a temporary DOM element to parse the HTML
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = html;
      
      // Check if the view requires authentication
      const authRequired = tempDiv.querySelector('[data-auth-required]') !== null;
      const requiredRoles = tempDiv.querySelector('[data-roles]')?.dataset?.roles || this.defaultRole;
      
      console.log(`View auth check - Required: ${authRequired}, Roles: ${requiredRoles}`);
      
      // Check authentication status if required
      if (authRequired && !this.isAuthenticated) {
        console.warn("Authentication required but user is not authenticated");
        
        // Save the intended destination for post-login redirect
        localStorage.setItem('dashboardIntendedDestination', fullUrlPath);
        
        // Redirect to login
        window.location.href = this.loginPath;
        return false;
      }
      
      // Check role if authentication is required
      if (authRequired && !this.hasRequiredRole(requiredRoles)) {
        console.warn(`Required role ${requiredRoles} not found. User has role: ${this.userRole}`);
        this.showAccessDeniedPage(viewName, requiredRoles);
        return false;
      }
      
      // If we pass auth checks, update the container
      this.contentContainer.innerHTML = html;
      
      // Activate scripts in the loaded HTML
      this.activateScripts();

      // Update active navigation link
      this.updateActiveLink(viewName); 
      
      // Update page title
      const pageName = viewName.split('.')[0];
      document.title = `${pageName.charAt(0).toUpperCase() + pageName.slice(1)} | University Event Management`;
      
      return true;
    } catch (error) {
      console.error("Error loading view:", error);
      await this.show404Page(viewName);
      return false;
    }
  }
  
  // New method to show access denied page
  async showAccessDeniedPage(attemptedPath, requiredRole) {
    try {
      // Try to load a dedicated access-denied page
      const response = await fetch(`${this.viewsPath}access-denied.html`);
      
      if (response.ok) {
        const html = await response.text();
        this.contentContainer.innerHTML = html;
        
        // Update page title
        document.title = "Access Denied | University Event Management";
        
        // Remove active state from all nav links
        this.clearActiveLinks();
        
        // Store what was attempted for logging/analytics
        this.contentContainer.setAttribute('data-attempted-path', attemptedPath);
        this.contentContainer.setAttribute('data-required-role', requiredRole);
        
        // Activate scripts in the loaded HTML
        this.activateScripts();
      } else {
        // Fallback to inline access denied message
        this.contentContainer.innerHTML = `<div class="p-6 text-center">
          <i class="ri-lock-2-line text-5xl text-red-500 mb-4"></i>
          <p class="text-xl text-red-600 dark:text-red-400">Access Denied</p>
          <p class="text-gray-600 dark:text-gray-400 mt-2">You don't have permission to access "${attemptedPath}".</p>
          <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Required role: ${requiredRole}</p>
          <button onclick="window.router.navigateTo('dashboard')" class="mt-6 px-6 py-2 bg-primary text-white rounded-button">Go to Dashboard</button>
        </div>`;
      }
    } catch (error) {
      console.error("Error showing access denied page:", error);
      this.contentContainer.innerHTML = `<div class="p-6 text-center">
        <i class="ri-lock-2-line text-5xl text-red-500 mb-4"></i>
        <p class="text-xl text-red-600 dark:text-red-400">Access Denied</p>
        <p class="text-gray-600 dark:text-gray-400 mt-2">You don't have permission to access this page.</p>
        <button onclick="window.router.navigateTo('dashboard')" class="mt-6 px-6 py-2 bg-primary text-white rounded-button">Go to Dashboard</button>
      </div>`;
    }
  }
  
  activateScripts() {
    // Re-activate scripts in the loaded content
    Array.from(this.contentContainer.querySelectorAll("script")).forEach(oldScript => {
      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach(attr => {
        newScript.setAttribute(attr.name, attr.value);
      });
      newScript.appendChild(document.createTextNode(oldScript.innerHTML));
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  // Dynamic route handlers
  async showEventDetails(eventId) {
    console.log(`Showing event details for ID: ${eventId}`);
    
    // Build URL for history API
    const urlPath = `${this.basePath}events/${eventId}`;
    
    // Show loading spinner
    this.contentContainer.innerHTML = `
      <div class="flex justify-center items-center h-64">
        <i class="ri-loader-4-line ri-spin text-3xl text-primary"></i>
      </div>`;
      
    try {
      // Update browser history if we weren't called from handleInitialRoute
      if (window.location.pathname !== urlPath) {
        window.history.pushState(
          { path: `events/${eventId}`, params: { id: eventId } },
          '', 
          urlPath
        );
      }
      
      // Load the event-details view - FIXED: Corrected file name with proper dash
      const response = await fetch(`${this.viewsPath}event-details.html`);
      if (!response.ok) {
        throw new Error(`Failed to load event details view: ${response.status}`);
      }
      
      // Set the HTML content
      const html = await response.text();
      this.contentContainer.innerHTML = html;
      
      // Activate scripts in the loaded HTML
      this.activateScripts();
      
      // Update the page title
      document.title = `Event Details | University Event Management`;
      
      return true;
    } catch (error) {
      console.error("Error loading event details:", error);
      await this.show404Page(`events/${eventId}`);
      return false;
    }
  }

  async showStudentDetails(studentId) {
    console.log(`Showing student details for ID: ${studentId}`);
    
    // Build URL for history API (don't change current URL)
    const urlPath = `${this.basePath}students/${studentId}`;
    
    // Show loading spinner
    this.contentContainer.innerHTML = `
      <div class="flex justify-center items-center h-64">
        <i class="ri-loader-4-line ri-spin text-3xl text-primary"></i>
      </div>`;
      
    try {
      // Update browser history if we weren't called from handleInitialRoute
      if (window.location.pathname !== urlPath) {
        window.history.pushState(
          { path: `students/${studentId}`, params: { id: studentId } },
          '', 
          urlPath
        );
      }
      
      // Load the student-details view
      const response = await fetch(`${this.viewsPath}student-details.html`);
      if (!response.ok) {
        throw new Error(`Failed to load student details view: ${response.status}`);
      }
      
      // Set the HTML content
      const html = await response.text();
      this.contentContainer.innerHTML = html;
      
      // Activate scripts in the loaded HTML
      this.activateScripts();
      
      // Update the page title
      document.title = `Student Details | University Event Management`;
      
      return true;
    } catch (error) {
      console.error("Error loading student details:", error);
      await this.show404Page(`students/${studentId}`);
      return false;
    }
  }
  
  async showOrganizationDetails(orgId) {
    console.log(`Showing organization details for ID: ${orgId}`);
    
    // Build URL for history API (don't change current URL)
    const urlPath = `${this.basePath}organizations/${orgId}`;
    
    // Show loading spinner
    this.contentContainer.innerHTML = `
      <div class="flex justify-center items-center h-64">
        <i class="ri-loader-4-line ri-spin text-3xl text-primary"></i>
      </div>`;
      
    try {
      // Update browser history if we weren't called from handleInitialRoute
      if (window.location.pathname !== urlPath) {
        window.history.pushState(
          { path: `organizations/${orgId}`, params: { id: orgId } },
          '', 
          urlPath
        );
      }
      
      // Load the organization-details view
      const response = await fetch(`${this.viewsPath}organization-details.html`);
      if (!response.ok) {
        throw new Error(`Failed to load organization details view: ${response.status}`);
      }
      
      // Set the HTML content
      const html = await response.text();
      this.contentContainer.innerHTML = html;
      
      // Activate scripts in the loaded HTML
      this.activateScripts();
      
      // Update the page title
      document.title = `Organization Details | University Event Management`;
      
      return true;
    } catch (error) {
      console.error("Error loading organization details:", error);
      await this.show404Page(`organizations/${orgId}`);
      return false;
    }
  }

  // Show 404 page
  async show404Page(attemptedPath) {
    try {
      const notFoundResponse = await fetch(`${this.viewsPath}404.html`);
      if (notFoundResponse.ok) {
        const notFoundHtml = await notFoundResponse.text();
        this.contentContainer.innerHTML = notFoundHtml;
        
        // Update page title for 404 page
        document.title = "Page Not Found | University Event Management";
        
        // Remove active state from all nav links
        this.clearActiveLinks();
        
        // Store what page was attempted
        this.contentContainer.setAttribute('data-attempted-path', attemptedPath);
        
        // If we have a 404 handler, execute it
        if (this.customHandlers['404']) {
          this.customHandlers['404']({ 
            attemptedPath, 
            fullPath: window.location.pathname,
            attemptedAt: new Date().toISOString() 
          });
        }
        
        // Activate scripts in the 404 page
        this.activateScripts();
      } else {
        // If 404.html can't be loaded, show inline error
        this.contentContainer.innerHTML = `<div class="p-6 text-center">
          <i class="ri-error-warning-line text-5xl text-red-500 mb-4"></i>
          <p class="text-xl text-red-600 dark:text-red-400">Page Not Found</p>
          <p class="text-gray-600 dark:text-gray-400 mt-2">The page "${attemptedPath}" could not be found.</p>
          <button onclick="window.router.navigateTo('dashboard')" class="mt-6 px-6 py-2 bg-primary text-white rounded-button">Go to Dashboard</button>
        </div>`;
      }
    } catch (error) {
      console.error("Error showing 404 page:", error);
      this.contentContainer.innerHTML = `<div class="p-6 text-center">
        <i class="ri-error-warning-line text-5xl text-red-500 mb-4"></i>
        <p class="text-xl text-red-600 dark:text-red-400">Page Not Found</p>
        <p class="text-gray-600 dark:text-gray-400 mt-2">The requested page could not be found.</p>
        <button onclick="window.router.navigateTo('dashboard')" class="mt-6 px-6 py-2 bg-primary text-white rounded-button">Go to Dashboard</button>
      </div>`;
    }
  }

  updateActiveLink(viewName) {
    const path = viewName.replace(this.fileExtension, '');
    this.clearActiveLinks();
    
    // Find links matching this path
    const sidebarNavLinks = document.querySelectorAll("aside .nav-link, #mobileSidebar .nav-link");
    
    sidebarNavLinks.forEach(link => {
      const linkHref = link.getAttribute("href")?.replace(this.fileExtension, '') || '';
      const dataView = link.dataset.view?.replace(this.fileExtension, '') || '';
      
      // Check for exact match or if this is a detail view based on pathname
      const isExactMatch = linkHref === path || dataView === path;
      const isParentPath = 
        (path.startsWith('events/') && linkHref === 'events') ||
        (path.startsWith('students/') && linkHref === 'students') ||
        (path.startsWith('organizations/') && linkHref === 'organizations');
        
      if (isExactMatch || isParentPath) {
        // Add active classes
        link.classList.add(
          this.activeClass, 
          this.activeBackgroundClass, 
          this.activeTextClass
        );
        
        // Remove hover classes
        link.classList.remove(
          this.hoverBgClass, 
          this.darkHoverBgClass
        );
      }
    });
  }
  
  clearActiveLinks() {
    // Remove active state from all nav links
    document.querySelectorAll("aside .nav-link, #mobileSidebar .nav-link").forEach(link => {
      link.classList.remove(
        this.activeClass, 
        this.activeBackgroundClass, 
        this.activeTextClass
      );
      
      // Add hover classes
      link.classList.add(
        this.hoverBgClass, 
        this.darkHoverBgClass
      );
    });
  }

  // Register a custom route handler
  registerRouteHandler(path, handler) {
    if (path.endsWith(this.fileExtension)) {
      path = path.slice(0, -this.fileExtension.length);
    }
    
    this.customHandlers[path] = handler;
    console.log(`Registered custom handler for path: ${path}`);
  }
}

// Create and export router instance
window.router = new Router({
  contentContainerId: "content",
  basePath: "/iteam-university-website/admin/",
  defaultView: "dashboard",
  viewsPath: "views/",
  loginPath: "/iteam-university-website/admin/auth/admin-login.html",
  authCheckPath: "/iteam-university-website/admin/api/adminlogin.php?check=1"
});

// Legacy support for existing code that uses loadView
window.loadView = function(viewNameWithParams) {
  // Extract view name and parameters
  const [viewName, queryString] = viewNameWithParams.split('?');
  
  // Parse parameters
  const params = {};
  if (queryString) {
    const urlParams = new URLSearchParams(queryString);
    urlParams.forEach((value, key) => {
      params[key] = value;
    });
  }
  
  // Use the new router
  window.router.navigateTo(viewName, params);
};

// Add a logout function that clears auth data
window.logoutAdmin = function() {
  // Clear local storage auth data
  localStorage.removeItem('dashboardAuth');
  
  // Redirect to login page
  window.location.href = "/iteam-university-website/admin/auth/admin-login.html";
};