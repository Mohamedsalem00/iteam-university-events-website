# iTeam University Website

Welcome to the iTeam University website project! This project is dedicated to event management and planning for iTeam University, featuring user authentication, event registration, organization profiles, and an admin dashboard. Below you will find an overview of the project's structure, setup instructions, and other relevant information.

## Project Structure

```
iteam-university-website/
├── frontend/
│   ├── auth/
│   │   ├── login.html          # Login page for users, organizations, and admins
│   │   └── register.html       # Registration page with user/organization options
│   ├── admin/
│   │   ├── dashboard.html      # Admin dashboard overview
│   │   ├── events.html         # Admin event management
│   │   ├── users.html          # User and organization management
│   │   └── admin-login.html    # Separate login page for admin
│   ├── dashboards/
│   │   ├── student-dashboard.html   # Dashboard for students
│   │   ├── company-dashboard.html   # Dashboard for companies
│   │   └── admin-dashboard.html     # Dashboard for admins
│   ├── events/
│   │   ├── index.html          # Browse all events
│   │   ├── details.html        # Event details
│   │   └── register.html       # Event registration form
│   ├── profile/
│   │   ├── user.html           # User profile management
│   │   └── organization.html   # Organization profile management
│   ├── components/
│   │   ├── navbar.html         # Navigation bar component
│   │   └── footer.html         # Footer component
│   ├── assets/
│   │   ├── images/             # Website images and icons
│   │   ├── css/                # Stylesheet files
│   │   │   ├── style.css       # Main styles
│   │   │   └── responsive.css  # Responsive design styles
│   │   └── fonts/              # Custom font files
│   ├── js/
│   │   ├── main.js             # Main JavaScript functionality
│   │   ├── theme-switcher.js   # Light/dark theme functionality
│   │   ├── nav-loader.js       # Dynamically loads navigation components
│   │   └── auth-check.js       # Authentication status verification
│   ├── db-test.html            # Database testing tool
│   └── index.html              # Main entry point of the website
├── backend/
│   ├── api/
│   │   ├── auth.php            # Authentication API (login, logout)
│   │   ├── register.php        # Registration API for users and organizations
│   │   ├── events.php          # Events API (list, create, update, delete)
│   │   ├── users.php           # User management API
│   │   └── test-db.php         # Database connectivity test API
│   ├── db/
│   │   ├── db_connection.php   # Database connection configuration
│   │   ├── init_db.sql         # Initial database schema and sample data
│   │   └── setup_database.php  # Database setup and initialization script
│   └── uploads/                # Folder for uploaded files (images, etc.)
├── index.html                  # Redirect to frontend/index.html
├── README.md                   # Project documentation
└── TASKS.md                    # Project task list
```

## Setup Instructions

1. **Server Requirements**: 
   - XAMPP, WAMP, LAMP or similar web server with PHP 7.4+ and MySQL/MariaDB
   - Web server configured to serve from `/opt/lampp/htdocs/` or your equivalent web root

2. **Clone the Repository**: 
   Clone this repository to your web server's document root:
   ```
   git clone <repository-url> /opt/lampp/htdocs/iteam-university-website
   ```

3. **Database Setup**: 
   - Start your MySQL server
   - Access the database setup page at: `http://localhost/iteam-university-website/backend/db/setup_database.php`
   - Follow the on-screen instructions to create the necessary tables and sample data

4. **Test the Database Connection**:
   - Navigate to `http://localhost/iteam-university-website/frontend/db-test.html`
   - Click "Test Database Connection" to verify everything is working correctly

5. **Access the Website**:
   - Open `http://localhost/iteam-university-website/` in your web browser
   - You can log in with the following sample accounts:
     - **User**: john.doe@example.com / password123
     - **Organization**: contact@techinnovators.com / password123
     - **Admin**: admin@iteamuniversity.com / adminpass123

6. **Development**: 
   - Edit files directly in the `/opt/lampp/htdocs/iteam-university-website/` directory
   - Use your preferred code editor for development

## Features

- **Authentication System**: Multi-user login with role-based access (users, organizations, admin)
- **User Registration**: Separate registration flows for individual users and organizations
- **Responsive Design**: All pages are responsive and optimized for desktop and mobile
- **Role-specific Dashboards**: Customized dashboards for students, companies, and administrators
- **Dynamic Navigation**: Navigation components loaded dynamically based on user role
- **Light/Dark Theme**: Users can choose between light and dark display modes
- **Event Management**: Browse, register for, and manage events
- **Admin Dashboard**: Administrative tools for managing users, events, and site content
- **Database Testing Tool**: Built-in utility for verifying database connectivity and structure

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **CSS Framework**: Custom responsive framework with CSS variables for theming
- **Authentication**: Custom PHP authentication system with session management
- **Password Security**: PHP's `password_hash()` and `password_verify()` for secure password handling

## Contributing

Contributions are welcome! Please follow these steps:

1. Review the task list in TASKS.md to understand current project priorities
2. Create an issue describing the feature or bug you want to address
3. Create a feature branch (`feature/issue-4-auth`)
4. Make your changes and test thoroughly
5. Submit a pull request with a clear description of your changes

## License

This project is open-source and available under the [MIT License](LICENSE).
