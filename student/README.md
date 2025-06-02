# Student Event Management Platform

## Overview
The Student Event Management Platform is a modern, responsive web application designed for university students to manage their event registrations. The platform features a clean and lightweight interface built with TailwindCSS, ensuring a mobile-first experience.

## Features
- **Home Page**: A welcoming interface with a call-to-action to view upcoming events.
- **Events Page**: A grid layout displaying various events with essential details such as title, date, description, and an option to register.
- **Event Details Page**: Comprehensive information about a specific event, including an option to unregister.
- **My Registrations Page**: A list of events the student is registered for, complete with status tags indicating whether they are upcoming or completed.
- **Dark Mode Support**: The application supports dark mode, enhancing usability in low-light environments.

## Technologies Used
- **HTML**: Structure of the web pages.
- **TailwindCSS**: Utility-first CSS framework for styling.
- **JavaScript**: For any interactive elements, such as the mobile navigation menu.

## Setup Instructions
1. Clone the repository:
   ```
   git clone https://github.com/yourusername/student.git
   ```
2. Navigate to the project directory:
   ```
   cd student
   ```
3. Install dependencies (if any):
   ```
   npm install
   ```
4. Build the TailwindCSS styles:
   ```
   npm run build
   ```
5. Open the `index.html` file in your browser to view the application.

## File Structure
```
student
├── css
│   ├── tailwind.css
│   └── output.css
├── js
│   └── main.js
├── pages
│   ├── index.html
│   ├── events.html
│   ├── event-details.html
│   └── my-registrations.html
├── images
│   ├── placeholder.svg
│   └── favicon.svg
├── tailwind.config.js
├── package.json
└── README.md
```

## Contribution
Contributions are welcome! Please feel free to submit a pull request or open an issue for any suggestions or improvements.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.