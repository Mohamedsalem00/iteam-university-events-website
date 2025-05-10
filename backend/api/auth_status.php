<?php
// Start session
session_start();

// Set security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database connection
require_once '../db/db_connection.php';

/**
 * Check if user is authenticated via session or remember-me cookie
 * @return array Authentication status and user data
 */
function checkAuthStatus() {
    // Check if user is logged in via session
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        $timeout = 60 * 30; // 30 minutes
        
        if (time() - $last_activity > $timeout) {
            // Session expired
            logout();
            return [
                'authenticated' => false, 
                'message' => 'Session expired. Please log in again.'
            ];
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return [
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? '',
                'name' => $_SESSION['user_name'] ?? 'User',
                'role' => $_SESSION['user_type'],
                'profile_picture' => $_SESSION['profile_picture'] ?? null,
                'dashboard_url' => $_SESSION['dashboard_url'] ?? getDashboardUrl($_SESSION['user_type']),
                'is_admin' => ($_SESSION['user_type'] === 'admin')
            ]
        ];
    }
    
    // Check for remember-me cookies
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_email']) && isset($_COOKIE['user_type'])) {
        $token = $_COOKIE['remember_token'];
        $email = $_COOKIE['user_email'];
        $userType = $_COOKIE['user_type'];
        
        // Here we would normally validate the token against a database table
        // For now, we'll recreate the session based on cookie information
        
        try {
            global $conn;
            
            // Determine which table to query based on user type
            $tableName = '';
            $idField = '';
            $nameFields = '';
            
            switch($userType) {
                case 'user':
                    $tableName = 'users';
                    $idField = 'user_id';
                    $nameFields = "CONCAT(first_name, ' ', last_name) as name";
                    break;
                case 'organization':
                    $tableName = 'organizations';
                    $idField = 'organization_id';
                    $nameFields = "name";
                    break;
                case 'admin':
                    $tableName = 'admins';
                    $idField = 'admin_id';
                    $nameFields = "username as name";
                    break;
                default:
                    return ['authenticated' => false];
            }
            
            // Query the appropriate table
            $stmt = $conn->prepare("SELECT $idField, email, $nameFields, profile_picture FROM $tableName WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user[$idField];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                $_SESSION['last_activity'] = time();
                $_SESSION['dashboard_url'] = getDashboardUrl($userType);
                
                return [
                    'authenticated' => true,
                    'user' => [
                        'id' => $user[$idField],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'role' => $userType,
                        'profile_picture' => $user['profile_picture'] ?? null,
                        'dashboard_url' => $_SESSION['dashboard_url'],
                        'is_admin' => ($userType === 'admin')
                    ]
                ];
            }
        } catch (PDOException $e) {
            error_log("Auth status check error: " . $e->getMessage());
        }
    }
    
    // Not authenticated
    return ['authenticated' => false];
}

/**
 * Get dashboard URL based on user type
 * @param string $userType Type of user (user, organization, admin)
 * @return string Dashboard URL
 */
function getDashboardUrl($userType) {
    switch ($userType) {
        case 'user':
            return 'dashboards/student/dashboard.html';
        case 'organization':
            return 'dashboards/organization/dashboard.html';
        case 'admin':
            return 'dashboards/admin/dashboard.html';
        default:
            return 'index.html';
    }
}

/**
 * Log out user by clearing session and cookies
 */
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
    
    // Clear remember-me cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_email', '', time() - 3600, '/');
    setcookie('user_type', '', time() - 3600, '/');
    
    // Destroy the session
    session_destroy();
}

// Return authentication status
echo json_encode(checkAuthStatus());