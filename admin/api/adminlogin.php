<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

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

// Admin login function with enhanced security
function adminLogin($conn, $email, $password, $remember = false) {
    try {
        // DEBUG: Log the email being queried
        error_log("Admin Login Attempt - Email for query: " . $email);

        // Query admin with JOIN to accounts table
        $stmt = $conn->prepare("SELECT a.*, ac.account_id FROM admins a 
                                LEFT JOIN accounts ac ON a.admin_id = ac.reference_id AND ac.account_type = 'admin'
                                WHERE a.email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            $accountId = $admin['account_id'];

            // DEBUG: Log fetched admin data and the password being verified
            error_log("Admin Login Attempt - Admin data fetched: " . print_r($admin, true));
            error_log("Admin Login Attempt - Password from form: " . $password);
            error_log("Admin Login Attempt - Hashed password from DB: " . $admin['password']);
            
            // Create account entry if not exists
            if (!$accountId) {
                $insertAccountQuery = "INSERT INTO accounts (account_type, reference_id, created_at, updated_at) 
                                     VALUES ('admin', :admin_id, NOW(), NOW())";
                $insertStmt = $conn->prepare($insertAccountQuery);
                $insertStmt->bindParam(':admin_id', $admin['admin_id']);
                $insertStmt->execute();
                $accountId = $conn->lastInsertId();
            }
            
            // Verify the password using password_verify
            $isPasswordCorrect = password_verify($password, $admin['password']);

            // DEBUG: Log password_verify result
            error_log("Admin Login Attempt - password_verify result: " . ($isPasswordCorrect ? 'true' : 'false'));
            
            if ($isPasswordCorrect) {
                // Enhance security for admin sessions
                session_regenerate_id(true); // Prevent session fixation attacks
                
                // Set session variables for admin with stronger security
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_name'] = $admin['username'];
                $_SESSION['is_admin'] = true; // Special admin flag
                $_SESSION['last_activity'] = time();
                $_SESSION['dashboard_url'] = 'admin/index.html';
                $_SESSION['account_id'] = $accountId;
                
                // Add admin login audit log (optional enhancement)
                $ip = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                
                try {
                    // Check if last_login column exists before updating
                    $checkColumn = $conn->query("SHOW COLUMNS FROM admins LIKE 'last_login'");
                    if ($checkColumn->rowCount() > 0) {
                        $logQuery = "UPDATE admins SET last_login = NOW() WHERE admin_id = :admin_id";
                        $logStmt = $conn->prepare($logQuery);
                        $logStmt->bindParam(':admin_id', $admin['admin_id']);
                        $logStmt->execute();
                    }
                } catch (PDOException $e) {
                    // Just log the error but don't interrupt the login process
                    error_log("Failed to update admin last_login: " . $e->getMessage());
                }
                
                // Set remember-me cookie if requested (secure implementation)
                if ($remember) {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    
                    // Define cookie parameters
                    $cookie_path = '/';
                    $cookie_domain = ''; // Leave empty for current host
                    // For production over HTTPS, set $cookie_secure to true. 
                    // For local HTTP development, it can be false.
                    // Consider making this configurable or detecting HTTPS.
                    $cookie_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'; // True if HTTPS
                    $cookie_httponly = true; // Always true for security

                    // Store admin cookies
                    setcookie('admin_token', $token, time() + 86400 * 30, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
                    setcookie('admin_email', $admin['email'], time() + 86400 * 30, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
                    setcookie('account_id', (string)$accountId, time() + 86400 * 30, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
                }
                
                return [
                    'success' => true,
                    'admin' => [
                        'id' => $admin['admin_id'],
                        'account_id' => $accountId,
                        'email' => $admin['email'],
                        'name' => $admin['username'],
                        'type' => 'admin',
                        'is_admin' => true,
                        // Ensure dashboard_url is root-relative
                        'dashboard_url' => 'admin/index.html'
                    ]
                ];
            }
        }
        
        // Admin not found or password incorrect
        return ['success' => false, 'message' => 'Invalid administrator credentials.'];
    } catch (PDOException $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error. Please try again later.'];
    }
}

// --- Request Routing ---

// Process admin login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize passwords
    $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
    
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email and password are required.'
        ]);
        exit;
    }
    
    $result = adminLogin($conn, $email, $password, $remember);
    echo json_encode($result);
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle admin authentication status check
    if (isset($_GET['check'])) {
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        
        if ($isAdmin) {
            echo json_encode([
                'success' => true,
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'email' => $_SESSION['email'],
                    'name' => $_SESSION['user_name'],
                    'type' => 'admin',
                    'is_admin' => true,
                    'dashboard_url' => $_SESSION['dashboard_url'],
                    'account_id' => $_SESSION['account_id']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not logged in as administrator']);
        }
        exit;
    }
}


// Invalid request if none of the above conditions are met
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['check'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>