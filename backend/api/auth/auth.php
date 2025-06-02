<?php
// Start session
session_start();

// Include database connection
require_once '../../db/db_connection.php';

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

// Login function - properly verify hashed passwords for students and organizations only
function login($conn, $email, $password, $remember = false, $account_type = null) {
    try {
        // First find the account in the accounts system
        // We'll try to look up by email in each table and then cross-reference with accounts
        
        // Only allow 'student', 'student', or 'organization' types in this endpoint
        if ($account_type !== null && !in_array($account_type, ['student', 'student', 'organization'])) {
            return ['success' => false, 'message' => 'Invalid student type specified.'];
        }
        
        // Map 'student' to 'student' for compatibility
        if ($account_type === 'student') {
            $account_type = 'student';
        }
        
        // Try students table first if account_type is null or 'student'
        if ($account_type === null || $account_type === 'student') {
            $stmt = $conn->prepare("SELECT s.*, a.account_id FROM students s 
                                    JOIN accounts a ON s.student_id = a.reference_id AND a.account_type = 'student'
                                    WHERE s.email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                $userType = 'student';
                $accountId = $student['account_id'];
                
                // Verify the password using password_verify
                $isPasswordCorrect = password_verify($password, $student['password']);
                
                // Fallback for sample accounts with hardcoded passwords
                if (!$isPasswordCorrect && $password === "password123" && 
                    $student['password'] === '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm') {
                    $isPasswordCorrect = true;
                }
                
                if ($isPasswordCorrect) {
                    // Check if student is active
                    if ($student['status'] !== 'active') {
                        return ['success' => false, 'message' => 'Your account is inactive. Please contact support.'];
                    }
                    
                    // Set session variables for student
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['email'] = $student['email'];
                    $_SESSION['account_type'] = $userType;
                    $_SESSION['account_name'] = $student['first_name'] . ' ' . $student['last_name'];
                    $_SESSION['profile_picture'] = $student['profile_picture'] ?? null;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['dashboard_url'] = '/iteam-university-website/student/pages/index.html';
                    $_SESSION['account_id'] = $accountId;
                    
                    // Set remember-me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_email', $student['email'], time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_type', $userType, time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_id', $accountId, time() + 86400 * 30, '/', '', false, true);
                    }
                    
                    // Update last login time
                    $updateStmt = $conn->prepare("UPDATE accounts SET last_login = CURRENT_TIMESTAMP WHERE account_id = :account_id");
                    $updateStmt->bindParam(':account_id', $accountId);
                    $updateStmt->execute();
                    
                    return [
                        'success' => true,
                        'student' => [
                            'id' => $student['student_id'],
                            'account_id' => $accountId,
                            'email' => $student['email'],
                            'name' => $student['first_name'] . ' ' . $student['last_name'],
                            'type' => $userType,
                            'dashboard_url' => '../pages/index.html'
                        ]
                    ];
                }
            }
        }
        
        // Try organizations table if account_type is null or 'organization'
        if ($account_type === null || $account_type === 'organization') {
            $stmt = $conn->prepare("SELECT o.*, a.account_id FROM organizations o 
                                    JOIN accounts a ON o.organization_id = a.reference_id AND a.account_type = 'organization'
                                    WHERE o.email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                $userType = 'organization';
                $accountId = $student['account_id'];
                
                // Verify the password using password_verify
                $isPasswordCorrect = password_verify($password, $student['password']);
                
                // Fallback for sample accounts with hardcoded passwords
                if (!$isPasswordCorrect && $password === "password123" && 
                    $student['password'] === '$2y$10$eIgMrFf3Mo/tTdFj3.Z3d.CPCKzFGe9ieJJkOhyU9A6TP.3Uc3HCm') {
                    $isPasswordCorrect = true;
                }
                
                if ($isPasswordCorrect) {
                    // Check if organization is active
                    if ($student['status'] !== 'active') {
                        return ['success' => false, 'message' => 'Your organization account is inactive. Please contact support.'];
                    }
                    
                    // Set session variables for organization
                    $_SESSION['organization_id'] = $student['organization_id'];
                    $_SESSION['email'] = $student['email'];
                    $_SESSION['account_type'] = $userType;
                    $_SESSION['account_name'] = $student['name'];
                    $_SESSION['profile_picture'] = $student['profile_picture'] ?? null;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['dashboard_url'] = '../organization/pages/dashboard.html';
                    $_SESSION['account_id'] = $accountId;
                    
                    // Set remember-me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', false, true);
                        setcookie('user_email', $student['email'], time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_type', $userType, time() + 86400 * 30, '/', '', false, true);
                        setcookie('account_id', $accountId, time() + 86400 * 30, '/', '', false, true);
                    }
                    
                    // Update last login time
                    $updateStmt = $conn->prepare("UPDATE accounts SET last_login = CURRENT_TIMESTAMP WHERE account_id = :account_id");
                    $updateStmt->bindParam(':account_id', $accountId);
                    $updateStmt->execute();
                    
                    return [
                        'success' => true,
                        'student' => [
                            'id' => $student['organization_id'],
                            'account_id' => $accountId,
                            'email' => $student['email'],
                            'name' => $student['name'],
                            'type' => $userType,
                            'dashboard_url' => '../pages/organization/dashboard.html'
                        ]
                    ];
                }
            }
        }
        
        // No matching student found or password incorrect
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
    setcookie('account_type', '', time() - 3600, '/');
    setcookie('account_id', '', time() - 3600, '/');
    
    // Destroy the session
    session_destroy();
    
    return [
        'success' => true, 
        'message' => 'Successfully logged out.',
        'redirect' => '../pages/index.html'
    ];
}

// Check login status
function checkLogin() {
    if (isset($_SESSION['student_id']) && isset($_SESSION['account_type']) && isset($_SESSION['account_id'])) {
        $last_activity = $_SESSION['last_activity'] ?? 0;
        $timeout = 60 * 30; // 30 minutes
        
        if (time() - $last_activity > $timeout) {
            // Session expired
            logout();
            return ['success' => false, 'message' => 'Session expired. Please log in again.'];
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Determine appropriate dashboard URL based on student type
        $dashboardUrl = '../pages/index.html'; // Default
        if ($_SESSION['account_type'] === 'organization') {
            $dashboardUrl = '../pages/organization/dashboard.html';
        } else if ($_SESSION['account_type'] === 'admin') {
            $dashboardUrl = '../pages/admin/dashboard.html';
        }
        
        return [
            'success' => true,
            'student' => [
                'id' => $_SESSION['student_id'],
                'email' => $_SESSION['email'],
                'name' => $_SESSION['account_name'] ?? 'student',
                'type' => $_SESSION['account_type'],
                'profile_picture' => $_SESSION['profile_picture'] ?? null,
                'account_id' => $_SESSION['account_id'],
                'dashboard_url' => $dashboardUrl,
                'initials' => strtoupper(substr($_SESSION['account_name'] ?? 'U', 0, 1) . substr(strstr($_SESSION['account_name'] ?? 'U', ' ', false) ?: 'S', 1, 1))
            ]
        ];
    }
    
    // Try to check if there's a remember-me cookie and restore session
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_email']) && isset($_COOKIE['account_type'])) {
        // In a production environment, you would validate the token against a database
        // This is a simplified version
        $_SESSION['email'] = $_COOKIE['user_email'];
        $_SESSION['account_type'] = $_COOKIE['account_type'];
        $_SESSION['last_activity'] = time();
        
        // You would typically fetch student data from DB based on email
        // This is just a placeholder for demonstration
        return [
            'success' => true,
            'student' => [
                'email' => $_SESSION['email'],
                'type' => $_SESSION['account_type'],
                'restored_from_cookie' => true
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
    $account_type = isset($_POST['account_type']) ? sanitizeInput($_POST['account_type']) : null;
    
    $result = login($conn, $email, $password, $remember, $account_type);
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