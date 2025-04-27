<?php
// filepath: /home/mohamedsalem/Documents/my projects/iteam-university-website/includes/db.php

// Database credentials - keep these secure
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Change to your MySQL username
define('DB_PASSWORD', ''); // Change to your MySQL password
define('DB_NAME', 'event_management');



try {
    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Use UTF-8 encoding
    $conn->exec("set names utf8");
    
} catch(PDOException $e) {
    // For production, show a generic error message to users
    echo "Database connection failed. Please try again later.";
    
    // Log the actual error for administrators
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Terminate script execution
    die();
}
?>