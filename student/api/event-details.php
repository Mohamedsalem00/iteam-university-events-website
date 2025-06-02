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

// Get event ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required'
    ]);
    exit;
}

$event_id = (int)sanitizeInput($_GET['id']);

try {
    // Fetch event details
    $stmt = $conn->prepare("
        SELECT 
            e.*, 
            o.name AS organizer_name,
            o.profile_picture AS organizer_image,
            o.email AS organizer_email,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) AS registration_count
        FROM 
            events e
        LEFT JOIN 
            organizations o ON e.organizer_id = o.organization_id
        WHERE 
            e.event_id = :event_id
    ");
    $stmt->bindParam(':event_id', $event_id);
    $stmt->execute();
    
    // Check if event exists
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Event not found'
        ]);
        exit;
    }
    
    // Get event data
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get student registration status if logged in as student
    $registration_status = null;
    
    if (isset($_SESSION['student_id']) && $_SESSION['account_type'] === 'student') {
        $studentId = $_SESSION['student_id'];
        
        // Check if student is registered for this event
        $regStmt = $conn->prepare("
            SELECT status 
            FROM event_registrations 
            WHERE event_id = :event_id AND student_id = :student_id
        ");
        $regStmt->bindParam(':event_id', $event_id);
        $regStmt->bindParam(':student_id', $studentId);
        $regStmt->execute();
        
        if ($regStmt->rowCount() > 0) {
            $registration_status = $regStmt->fetch(PDO::FETCH_ASSOC)['status'];
        }
    }
    
    // If the thumbnail_url is null or empty, set a default
    if (empty($event['thumbnail_url'])) {
        $event['thumbnail_url'] = 'frontend/assets/images/gallery/placeholder_event.png';
    }
    
    // Return event details
    echo json_encode([
        'success' => true,
        'event' => $event,
        'registration_status' => $registration_status
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Event details API error: " . $e->getMessage());
    
    // Return an error message
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching event details. Please try again later.'
    ]);
}
?>