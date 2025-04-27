<?php
// Start session
session_start();

// Include database connection
require_once '../db/db_connection.php';

// Set headers
header('Content-Type: application/json');

// Check if user is logged in via session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'type' => $_SESSION['user_type']
        ]
    ]);
    exit;
}

// Check if user has remember-me cookie
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_email']) && isset($_COOKIE['user_type'])) {
    $email = $_COOKIE['user_email'];
    $userType = $_COOKIE['user_type'];
    
    // Here you would validate the remember token against your database
    // For now, we'll just accept it and set up the session
    
    try {
        // Determine which table to check
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
        
        $stmt = $conn->prepare("SELECT * FROM $tableName WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set session variables
            $_SESSION['user_id'] = $user[$idField];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $userType;
            
            // Set user name based on type
            if ($userType === 'user') {
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            } else if ($userType === 'organization') {
                $_SESSION['user_name'] = $user['name'];
            } else { // admin
                $_SESSION['user_name'] = $user['username'];
            }
            
            echo json_encode([
                'loggedIn' => true,
                'user' => [
                    'id' => $user[$idField],
                    'email' => $user['email'],
                    'name' => $_SESSION['user_name'],
                    'type' => $userType
                ]
            ]);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Auth check error: " . $e->getMessage());
    }
}

// User is not logged in
echo json_encode(['loggedIn' => false]);
exit;
?>