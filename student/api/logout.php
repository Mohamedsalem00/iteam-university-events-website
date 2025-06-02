<?php
// Start session
session_start();

// Include auth.php to use the logout function
require_once 'auth.php';

// Use the logout function from auth.php
$result = logout();

// Redirect to home page
header('Location: ../pages/index.html');
exit;
?>