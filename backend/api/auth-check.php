<?php
// Start session
session_start();

// Include database connection
require_once '../db/db_connection.php';

// Set headers
header('Content-Type: application/json');

// Check if user is logged in via session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && isset($_SESSION['account_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'name' => $_SESSION['user_name'],
            'type' => $_SESSION['user_type'],
            'account_id' => $_SESSION['account_id']
        ]
    ]);
    exit;
}

// Check if user has remember-me cookie
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_email']) && isset($_COOKIE['user_type']) && isset($_COOKIE['account_id'])) {
    $email = $_COOKIE['user_email'];
    $userType = $_COOKIE['user_type'];
    $accountId = $_COOKIE['account_id'];
    
    // Here you would validate the remember token against your database
    // For now, we'll just accept it and set up the session
    
    try {
        // Get user information via the accounts table
        $stmt = $conn->prepare("SELECT a.account_id, a.account_type, a.reference_id 
                               FROM accounts a WHERE a.account_id = :account_id");
        $stmt->bindParam(':account_id', $accountId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if account type matches cookie
            if ($account['account_type'] !== $userType) {
                echo json_encode(['loggedIn' => false, 'message' => 'Invalid session.']);
                exit;
            }
            
            // Get the specific user data based on account type
            $tableName = '';
            $idField = '';
            
            switch($userType) {
                case 'user':
                    $tableName = 'users';
                    $idField = 'user_id';
                    break;
                case 'organization':
                    $tableName = 'organizations';
                    $idField = 'organization_id';
                    break;
                case 'admin':
                    $tableName = 'admins';
                    $idField = 'admin_id';
                    break;
                default:
                    echo json_encode(['loggedIn' => false]);
                    exit;
            }
            
            $userStmt = $conn->prepare("SELECT * FROM {$tableName} WHERE {$idField} = :reference_id");
            $userStmt->bindParam(':reference_id', $account['reference_id']);
            $userStmt->execute();
            
            if ($userStmt->rowCount() > 0) {
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                // Set session variables
                $_SESSION['user_id'] = $user[$idField];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['account_id'] = $accountId;
                
                // Set user name based on type
                if ($userType === 'user') {
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                } else if ($userType === 'organization') {
                    $_SESSION['user_name'] = $user['name'];
                } else { // admin
                    $_SESSION['user_name'] = $user['username'];
                    $_SESSION['is_admin'] = true;
                }
                
                echo json_encode([
                    'loggedIn' => true,
                    'user' => [
                        'id' => $user[$idField],
                        'email' => $user['email'],
                        'name' => $_SESSION['user_name'],
                        'type' => $userType,
                        'account_id' => $accountId
                    ]
                ]);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Auth check error: " . $e->getMessage());
    }
}

// User is not logged in
echo json_encode(['loggedIn' => false]);
exit;
?>