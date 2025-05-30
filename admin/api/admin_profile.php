<?php
require_once 'db_connection.php';
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $adminId = $_SESSION['admin_id'];
    $query = "SELECT * FROM admins WHERE admin_id = :admin_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':admin_id', $adminId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $adminData = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'profile' => [
                'username' => $adminData['username'],
                'email' => $adminData['email'],
                'created_at' => $adminData['created_at'],
                'profile_picture' => $adminData['profile_picture'],
                'updated_at' => $adminData['updated_at'],
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin profile not found']);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch admin profile']);
}
?>