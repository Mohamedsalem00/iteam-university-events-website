<?php
require_once '../db/db_connection.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // For admin users
    if($_SESSION['user_type'] === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = :admin_id");
        $stmt->bindParam(':admin_id', $userId);
        $stmt->execute();
        $profile = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'username' => $profile['username'],
                'email' => $profile['email'],
                'created_at' => $profile['created_at'],
                'profile_picture' => 'assets/images/avatars/admin-avatar.jpg' // Default admin avatar
            ]
        ]);
        exit;
    }
    
    // For regular users
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // Get user's event registrations
    $stmt = $conn->prepare("
        SELECT er.*, e.title as event_title, e.start_date, e.end_date, e.location 
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.user_id = :user_id
        ORDER BY e.start_date DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $registrations = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'registrations' => $registrations
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error fetching profile data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch profile data.']);
}
?>