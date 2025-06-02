<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Set headers
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get student data based on the session
$studentId = $_SESSION['student_id'];
$userType = $_SESSION['account_type'];

// Check if the user is a student
if ($userType !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. This API is only for student accounts.'
    ]);
    exit;
}

// Check if this is an update request (POST method) or get request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is an update request
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Process password change if provided
        $passwordChanged = false;
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM students WHERE student_id = :student_id");
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                throw new Exception('User not found');
            }
            
            // Get raw stored hash from database without any processing
            $storedHash = $userData['password'];
            
            // Get raw password input without trimming (trimming can cause issues with passwords that have spaces)
            $providedPassword = $_POST['current_password'];
            
            // Use PHP's built-in verification function directly
            if (!password_verify($providedPassword, $storedHash)) {
                // For debugging purposes only - DO NOT USE IN PRODUCTION
                error_log("Password verification failed - password length: " . strlen($providedPassword));
                error_log("Hash format check: " . (substr($storedHash, 0, 4) === '$2y$' ? "Valid BCrypt" : "Invalid format"));
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Current password is incorrect. Please try again.'
                ]);
                exit;
            }
            
            // If we get here, password verification passed
            // Hash new password with BCrypt
            $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $passwordChanged = true;
        }
        
        // Process profile picture upload if provided
        $profilePicturePath = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/iteam-university-website/frontend/uploads/profile_pictures/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = $studentId . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
            $targetFile = $uploadDir . $filename;
            
            // Check file type
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.'
                ]);
                exit;
            }
            
            // Check file size - limit to 5MB
            if ($_FILES['profile_picture']['size'] > 5000000) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sorry, your file is too large. Maximum size is 5MB.'
                ]);
                exit;
            }
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // File uploaded successfully
                $profilePicturePath = $targetFile;
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error uploading profile picture.'
                ]);
                exit;
            }
        }
        
        // Update student data
        $sql = "UPDATE students SET ";
        $updates = [];
        $params = [];
        
        // Name fields
        if (isset($_POST['first_name'])) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $_POST['first_name'];
        }
        
        if (isset($_POST['last_name'])) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $_POST['last_name'];
        }
        
        // Password update
        if ($passwordChanged) {
            $updates[] = "password = :password";
            $params[':password'] = $newPassword;
        }
        
        // Profile picture update
        if ($profilePicturePath) {
            $updates[] = "profile_picture = :profile_picture";
            $params[':profile_picture'] = $profilePicturePath;
        }
        
        // Update timestamp
        $updates[] = "updated_at = NOW()";
        
        // Only proceed if there are fields to update
        if (!empty($updates)) {
            $sql .= implode(", ", $updates);
            $sql .= " WHERE student_id = :student_id";
            $params[':student_id'] = $studentId;
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return updated profile data
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            // Update the session variables with the new name
            $_SESSION['account_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
            
            $profile = [
                'student_id' => $studentId,
                'account_type' => 'student',
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'profile_picture' => $userData['profile_picture'],
                'initials' => strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)),
                // Add these fields for localStorage update on the client side
                'userType' => 'student',
                'userName' => $userData['first_name'] . ' ' . $userData['last_name']
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => $profile,
                // Also include the data in the top level for easier access
                'student' => [
                    'type' => 'student',
                    'name' => $userData['first_name'] . ' ' . $userData['last_name']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve updated profile'
            ]);
        }
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollBack();
        
        error_log("Profile update error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error updating profile: ' . $e->getMessage()
        ]);
    }
    exit;
}

// GET request - Fetch and display profile information
try {
    // Fetch student details
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = :student_id");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $profile = [
            'student_id' => $studentId,
            'account_type' => 'student',
            'first_name' => $userData['first_name'] ?? 'Student',
            'last_name' => $userData['last_name'] ?? 'student',
            'email' => $userData['email'] ?? $_SESSION['email'] ?? 'student@example.com',
            'profile_picture' => $userData['profile_picture'] ?? null,
            'initials' => strtoupper(substr($userData['first_name'] ?? 'S', 0, 1) . substr($userData['last_name'] ?? 'U', 0, 1))
        ];
        
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    } else {
        // Fallback if no data found in database
        echo json_encode([
            'success' => true,
            'profile' => [
                'student_id' => $studentId,
                'account_type' => 'student',
                'first_name' => 'Student',
                'last_name' => 'student',
                'email' => $_SESSION['email'] ?? 'student@example.com',
                'profile_picture' => null,
                'initials' => 'SU'
            ]
        ]);
    }
} catch (PDOException $e) {
    // Log the error but return a student-friendly message
    error_log("Profile API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
}

// TEMPORARY TEST CODE - REMOVE AFTER DEBUGGING
$testPassword = 'password123';
$testHash = password_hash($testPassword, PASSWORD_BCRYPT);
$verificationResult = password_verify($testPassword, $testHash);
error_log("Test verification result: " . ($verificationResult ? "Success" : "Failure"));
error_log("Test hash: " . substr($testHash, 0, 10) . "...");
// END TEST CODE
?>