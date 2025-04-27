<?php
// Initialize the session if not already started
session_start();

// Unset all session variables
$_SESSION = [];

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete remember-me cookie if it exists
if (isset($_COOKIE["remember_token"])) {
    setcookie("remember_token", "", time() - 3600, "/", "", true, true);
}

// Return JSON response indicating successful logout
header('Content-Type: applicationjson');
echo json_encode(['success' => true, 'message' => 'User logged out successfully']);
exit;
?>