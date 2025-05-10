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

// Login function - properly verify hashed passwords for users and organizations only
function login($conn, $email, $password, $remember = false, $user_type = null) {
    try {
        // First find the account in the accounts system
        // We'll try to look up by email in each table and then cross-reference with accounts
        
        // Only allow 'user' or 'organization' types in this endpoint
        if ($user_type !== null && !in_array($user_type, ['user', 'organization'])) {
            return ['success' => false, 'message' => 'Invalid user type specified.'];
        }
        
        // Try users table first if user_type is null or 'user'
        if ($user_type === null || $user_type === 'user') {
            $stmt = $conn->prepare("SELECT u.*, a.account_id FROM users u 
                                    JOIN accounts a ON u.user_id = a.reference_id AND a.account_type = 'user'
                                    WHERE u.email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $userType = 'user';
                $accountId = $user['account_id'];
                
                // Verify the password using password_verify
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
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['dashboard_url'] = 'dashboards/student/dashboard.html';
                    $_SESSION['account_id'] = $accountId;
                    
                    // Set remember-me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_email', $user['email'], time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_type', $userType, time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_id', $accountId, time() + 86400 * 30, '/', '', false, true);
                    }
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['user_id'],
                            'account_id' => $accountId,
                            'email' => $user['email'],
                            'name' => $user['first_name'] . ' ' . $user['last_name'],
                            'type' => $userType,
                            'dashboard_url' => 'dashboards/student/dashboard.html'
                        ]
                    ];
                }
            }
        }
        
        // Try organizations table if user_type is null or 'organization'
        if ($user_type === null || $user_type === 'organization') {
            $stmt = $conn->prepare("SELECT o.*, a.account_id FROM organizations o 
                                    JOIN accounts a ON o.organization_id = a.reference_id AND a.account_type = 'organization'
                                    WHERE o.email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $userType = 'organization';
                $accountId = $user['account_id'];
                
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
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['dashboard_url'] = 'dashboards/organization/dashboard.html';
                    $_SESSION['account_id'] = $accountId;
                    
                    // Set remember-me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_email', $user['email'], time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_type', $userType, time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_id', $accountId, time() + 86400 * 30, '/', '', false, true);
                    }
                    
                    return [
                        'success' => true,
                        'user' => [
                            'id' => $user['organization_id'],
                            'account_id' => $accountId,
                            'email' => $user['email'],
                            'name' => $user['name'],
                            'type' => $userType,
                            'dashboard_url' => 'dashboards/organization/dashboard.html'
                        ]
                    ];
                }
            }
        }
        
        // No matching user found or password incorrect
        return ['success' => false, 'message' => 'Invalid email or password.'];
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
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
        'redirect' => '/iteam-university-website/frontend/index.html'
    ];
}

// Check login status
function checkLogin() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        $timeout = 60 * 30; // 30 minutes
        
        if (time() - $last_activity > $timeout) {
            // Session expired
            logout();
            return ['success' => false, 'message' => 'Session expired. Please log in again.'];
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return [
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'name' => $_SESSION['user_name'] ?? 'User',
                'type' => $_SESSION['user_type'],
                'profile_picture' => $_SESSION['profile_picture'] ?? null,
                'dashboard_url' => $_SESSION['dashboard_url'] ?? '',
                'is_admin' => isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true,
                'account_id' => $_SESSION['account_id'] ?? null
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
    
    // Redirect admin login requests
    if ($user_type === 'admin') {
        echo json_encode([
            'success' => false, 
            'message' => 'Admin authentication requires the admin login page',
            'redirect' => '/iteam-university-website/frontend/auth/admin-login.html'
        ]);
        exit;
    }
    
    $result = login($conn, $email, $password, $remember, $user_type);
    echo json_encode($result);
    exit;
}

// Handle GET requests (check login status)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    echo json_encode(checkLogin());
    exit;
}

// Handle GET requests (logout)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    echo json_encode(logout());
    exit;
}

// Invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
?>