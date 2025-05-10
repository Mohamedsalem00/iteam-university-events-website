<?php
// Start session
session_start();

// Set security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Securely log out user by clearing session and cookies
 * @return array Result of logout operation
 */


// Process logout
function logout() {
    // Clear all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Clear all cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_email', '', time() - 3600, '/');
    setcookie('user_type', '', time() - 3600, '/');
    setcookie('admin_token', '', time() - 3600, '/');
    setcookie('admin_email', '', time() - 3600, '/');
    setcookie('account_id', '', time() - 3600, '/');
    setcookie('admin_auth', '', time() - 3600, '/');
    
    // Destroy the session
    session_destroy();
    
    return [
        'success' => true, 
        'message' => 'Successfully logged out.',
        'redirect' => '/iteam-university-website/frontend/auth/admin-login.html'
    ];
}

// Process logout and return response
echo json_encode(secure_logout());
?>