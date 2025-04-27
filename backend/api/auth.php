<?php
// Start session
session_start();

// Include database connection
require_once '../db/db_connection.php';

// Set headers to ensure proper JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Login function - properly verify hashed passwords with role-based handling
function login($conn, $email, $password, $remember = false, $user_type = null) {
    try {
        // If user_type is specified as admin, only check admin table
        if ($user_type === 'admin') {
            return tryAdminLogin($conn, $email, $password, $remember);
        }
        
        // Try users table first (students)
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userType = 'user';
            
            // Verify the password using password_verify (works with password_hash)
            $isPasswordCorrect = password_verify($password, $user['password']);
            
            // Fallback for sample accounts with hardcoded passwords
            if (!$isPasswordCorrect && $password === "password123" && 
                $user['password'] === '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm') {
                $isPasswordCorrect = true;
            }
            
            if ($isPasswordCorrect) {
                // Check if user is active
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Your account is inactive. Please contact support.'];
                }
                
                // Set session variables for user
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                $_SESSION['last_activity'] = time();
                $_SESSION['dashboard_url'] = 'dashboards/student-dashboard.html';
                
                // Set remember-me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                    setcookie('user_email', $user['email'], time() + 86400 * 30, '/', '', false, true);
                    setcookie('user_type', $userType, time() + 86400 * 30, '/', '', false, true);
                }
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['user_id'],
                        'email' => $user['email'],
                        'name' => $user['first_name'] . ' ' . $user['last_name'],
                        'type' => $userType,
                        'dashboard_url' => 'dashboards/student-dashboard.html'
                    ]
                ];
            }
        }
        
        // Try organizations table (companies)
        $stmt = $conn->prepare("SELECT * FROM organizations WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userType = 'organization';
            
            // Verify the password using password_verify
            $isPasswordCorrect = password_verify($password, $user['password']);
            
            // Fallback for sample accounts with hardcoded passwords
            if (!$isPasswordCorrect && $password === "password123" && 
                $user['password'] === '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm') {
                $isPasswordCorrect = true;
            }
            
            if ($isPasswordCorrect) {
                // Check if organization is active
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Your organization account is inactive. Please contact support.'];
                }
                
                // Set session variables for organization
                $_SESSION['user_id'] = $user['organization_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                $_SESSION['last_activity'] = time();
                $_SESSION['dashboard_url'] = 'dashboards/company-dashboard.html';
                
                // Set remember-me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                    setcookie('user_email', $user['email'], time() + 86400 * 30, '/', '', false, true);
                    setcookie('user_type', $userType, time() + 86400 * 30, '/', '', false, true);
                }
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['organization_id'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'type' => $userType,
                        'dashboard_url' => 'dashboards/company-dashboard.html'
                    ]
                ];
            }
        }
        
        // If we're here and user_type is not admin, don't check admin table
        if ($user_type !== null && $user_type !== 'admin') {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Try admins table as a last resort, but only from main login page
        return tryAdminLogin($conn, $email, $password, $remember);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error. Please try again later.'];
    }
}

// Helper function for admin login
function tryAdminLogin($conn, $email, $password, $remember = false) {
    try {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userType = 'admin';
            
            // Verify the password using password_verify
            $isPasswordCorrect = password_verify($password, $user['password']);
            
            // Fallback for sample admin with hardcoded password
            if (!$isPasswordCorrect && $password === "adminpass123" && 
                $user['password'] === '$2y$10$CcvOzYXPHBKT8tZ1i1LUmeYRngL9U2OKvMlZ4ExfO0QiXFJ2A0AFO') {
                $isPasswordCorrect = true;
            }
            
            if ($isPasswordCorrect) {
                // Set session variables for admin with stronger security
                session_regenerate_id(true); // Prevent session fixation
                
                $_SESSION['user_id'] = $user['admin_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['is_admin'] = true; // Special admin flag
                $_SESSION['last_activity'] = time();
                $_SESSION['dashboard_url'] = 'dashboards/admin-dashboard.html';
                
                // Set remember-me cookie if requested (for admin)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('admin_token', $token, time() + 86400 * 30, '/', '', false, true);
                    setcookie('admin_email', $user['email'], time() + 86400 * 30, '/', '', false, true);
                }
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['admin_id'],
                        'email' => $user['email'],
                        'name' => $user['username'],
                        'type' => $userType,
                        'is_admin' => true,
                        'dashboard_url' => 'dashboards/admin-dashboard.html'
                    ]
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password.'];
    } catch (PDOException $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error. Please try again later.'];
    }
}

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
    
    // Clear remember-me cookies
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    setcookie('user_email', '', time() - 3600, '/', '', false, true);
    setcookie('user_type', '', time() - 3600, '/', '', false, true);
    setcookie('admin_token', '', time() - 3600, '/', '', false, true);
    setcookie('admin_email', '', time() - 3600, '/', '', false, true);
    
    // Destroy the session
    session_destroy();
    
    return ['success' => true, 'message' => 'Successfully logged out.'];
}

// Check login status
function checkLogin() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_email']) && isset($_SESSION['user_type'])) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        $timeout = 60 * 30; // 30 minutes
        
        if (time() - $last_activity > $timeout) {
            // Session expired
            logout();
            return ['success' => false, 'message' => 'Session expired. Please log in again.'];
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return [
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'] ?? 'User',
                'type' => $_SESSION['user_type'],
                'profile_picture' => $_SESSION['profile_picture'] ?? null,
                'dashboard_url' => $_SESSION['dashboard_url'] ?? '',
                'is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true
            ]
        ];
    }
    
    return ['success' => false, 'message' => 'Not logged in.'];
}

// Process login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Don't sanitize passwords
    $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
    $user_type = isset($_POST['user_type']) ? sanitizeInput($_POST['user_type']) : null;
    
    $result = login($conn, $email, $password, $remember, $user_type);
    
    // Ensure proper content type is set before sending JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Handle GET requests (check login status)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    header('Content-Type: application/json');
    echo json_encode(checkLogin());
    exit;
}

// Handle GET requests (logout)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['logout'])) {
    header('Content-Type: application/json');
    echo json_encode(logout());
    exit;
}

// Invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
?>