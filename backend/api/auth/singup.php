<?php
// Start session
session_start();

// Include database connection
require_once '../../db/db_connection.php';

// Set headers
header('Content-Type: application/json');

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate the form data
$errors = [];
$data = [];

// Check user type
if (empty($_POST['user_type']) || !in_array($_POST['user_type'], ['user', 'organization'])) {
    $errors['user_type'] = 'Please select a valid account type';
} else {
    $data['user_type'] = sanitizeInput($_POST['user_type']);
}

// Validate email
if (empty($_POST['email'])) {
    $errors['email'] = 'Email is required';
} else {
    $email = sanitizeInput($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        $data['email'] = $email;
    }
}

// Validate password
if (empty($_POST['password'])) {
    $errors['password'] = 'Password is required';
} else {
    $password = $_POST['password']; // Don't sanitize passwords
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        $errors['password'] = 'Password must include uppercase, lowercase, and numbers';
    } else {
        $data['password'] = $password;
    }
}

// Validate confirm password
if (empty($_POST['confirm_password'])) {
    $errors['confirm_password'] = 'Please confirm your password';
} elseif (($_POST['password'] !== $_POST['confirm_password'])) {
    $errors['confirm_password'] = 'Passwords do not match';
}

// Validate terms
if (empty($_POST['terms'])) {
    $errors['terms'] = 'You must agree to the Terms and Privacy Policy';
}

// User type specific validation
if ($data['user_type'] === 'user') {
    // Validate first name
    if (empty($_POST['first_name'])) {
        $errors['first_name'] = 'First name is required';
    } else {
        $data['first_name'] = sanitizeInput($_POST['first_name']);
    }
    
    // Validate last name
    if (empty($_POST['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    } else {
        $data['last_name'] = sanitizeInput($_POST['last_name']);
    }
} else if ($data['user_type'] === 'organization') {
    // Validate organization name
    if (empty($_POST['org_name'])) {
        $errors['org_name'] = 'Organization name is required';
    } else {
        $data['org_name'] = sanitizeInput($_POST['org_name']);
    }
    
    // Get organization description
    $data['description'] = !empty($_POST['description']) ? sanitizeInput($_POST['description']) : '';
}

// Check for errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please correct the errors in the form',
        'errors' => $errors
    ]);
    exit;
}

// Check if email already exists in students or organizations table
try {
    $checkEmailStudentQuery = "SELECT COUNT(*) as count FROM students WHERE email = :email";
    $checkEmailOrgQuery = "SELECT COUNT(*) as count FROM organizations WHERE email = :email";
    
    $checkStmt = $conn->prepare($checkEmailStudentQuery);
    $checkStmt->bindParam(':email', $data['email']);
    $checkStmt->execute();
    $userCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $checkStmt = $conn->prepare($checkEmailOrgQuery);
    $checkStmt->bindParam(':email', $data['email']);
    $checkStmt->execute();
    $orgCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($userCount > 0 || $orgCount > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'This email address is already registered',
            'errors' => ['email' => 'This email address is already registered']
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred']);
    exit;
}

// Process registration
try {
    // Start transaction
    $conn->beginTransaction();
    
    // Hash the password
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    $referenceId = null;
    
    if ($data['user_type'] === 'user') {
        // Insert student (not user)
        $insertStudentQuery = "INSERT INTO students (first_name, last_name, email, password, status, created_at, updated_at) 
                            VALUES (:first_name, :last_name, :email, :password, 'active', NOW(), NOW())";
        
        $stmt = $conn->prepare($insertStudentQuery);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();
        
        $referenceId = $conn->lastInsertId();
        // Change user_type to student for accounts table
        $data['user_type'] = 'student';
        
    } else if ($data['user_type'] === 'organization') {
        // Insert organization
        $insertOrgQuery = "INSERT INTO organizations (name, email, password, description, status, created_at, updated_at) 
                          VALUES (:name, :email, :password, :description, 'active', NOW(), NOW())";
        
        $stmt = $conn->prepare($insertOrgQuery);
        $stmt->bindParam(':name', $data['org_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':description', $data['description']);
        $stmt->execute();
        
        $referenceId = $conn->lastInsertId();
    }
    
    // Now insert into accounts table
    if ($referenceId) {
        $insertAccountQuery = "INSERT INTO accounts (account_type, reference_id, created_at, updated_at) 
                              VALUES (:account_type, :reference_id, NOW(), NOW())";
                              
        $stmt = $conn->prepare($insertAccountQuery);
        $stmt->bindParam(':account_type', $data['user_type']);
        $stmt->bindParam(':reference_id', $referenceId);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration. Please try again later: ' . $e->getMessage()]);
}
?>