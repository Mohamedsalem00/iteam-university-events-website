<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/admin/api/logout.php
// Start session
session_start();

// Set security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Securely log out admin by clearing session and cookies
 * @return array Result of logout operation
 */
function secure_admin_logout() {
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
    
    // Define cookie parameters for secure deletion
    $cookie_path = '/';
    $cookie_domain = '';
    $cookie_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookie_httponly = true;

    // Clear admin-specific cookies
    setcookie('admin_token', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    setcookie('admin_email', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    setcookie('account_id', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);

    // Clear any other general authentication cookies
    setcookie('remember_token', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    setcookie('user_email', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    setcookie('user_type', '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
    
    // Destroy the session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Add login_required parameter to prevent auto-redirect
    return [
        'success' => true,
        'message' => 'Successfully logged out.',
        'redirect_url' => '/iteam-university-website/admin/auth/admin-login.html?login_required=1'
    ];
}

// Process logout and return response
echo json_encode(secure_admin_logout());
?>