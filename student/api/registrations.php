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

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['account_type'])) {
    echo json_encode([
        'success' => false,
        'message' => 'student not authenticated'
    ]);
    exit;
}

// Make sure the student is a student
if ($_SESSION['account_type'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Only students can view registrations'
    ]);
    exit;
}

// Get student ID from session - this is the actual student_id from students table
$student_id = $_SESSION['student_id'];

// If student_id was passed in request, validate it matches the session
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $requested_id = sanitizeInput($_GET['student_id']);
    
    // If the requested student_id is an email address, look up the actual student_id
    if (filter_var($requested_id, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $conn->prepare("SELECT student_id FROM students WHERE email = :email");
            $stmt->bindParam(':email', $requested_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $requested_id = $result['student_id'];
            }
        } catch (PDOException $e) {
            // Log error but continue with session student_id
            error_log("Error looking up student by email: " . $e->getMessage());
        }
    }
    
    // Security check: only allow accessing own registrations
    // You could add admin override here if needed
    if ($requested_id != $student_id) {
        error_log("student ID mismatch: Session={$student_id}, Requested={$requested_id}");
        // Ignore the requested ID, just use the session ID for security
    }
}

try {
    // Query to get all event registrations for the student
    $stmt = $conn->prepare("
        SELECT 
            er.registration_id,
            er.event_id,
            er.registration_date,
            er.status,
            e.title,
            e.description,
            e.start_date,
            e.end_date,
            e.location,
            e.event_type,
            e.thumbnail_url,
            o.name AS organizer_name, 
            o.profile_picture AS organizer_image,
            o.description AS organizer_description
        FROM 
            event_registrations er
        JOIN 
            events e ON er.event_id = e.event_id
        LEFT JOIN 
            organizations o ON e.organizer_id = o.organization_id
        WHERE 
            er.student_id = :student_id
        ORDER BY 
            e.start_date ASC
    ");
    
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    // Fetch all registrations
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the registrations
    echo json_encode([
        'success' => true,
        'registrations' => $registrations,
        'student_id' => $student_id
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Registration API error: " . $e->getMessage());
    
    // Return an error message
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
}

// Handle POST requests (like cancellation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // Check if this is a cancellation request
    if (isset($data['action']) && $data['action'] === 'cancel' && isset($data['registration_id'])) {
        // Sanitize the registration ID
        $registration_id = sanitizeInput($data['registration_id']);
        
        try {
            // First verify this is the student's own registration
            $stmt = $conn->prepare("
                SELECT * FROM event_registrations 
                WHERE registration_id = :registration_id AND student_id = :student_id
            ");
            $stmt->bindParam(':registration_id', $registration_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                // Not found or not the student's registration
                echo json_encode([
                    'success' => false,
                    'message' => 'Registration not found or you do not have permission to cancel it.'
                ]);
                exit;
            }
            
            // Update the registration status to cancelled (student-initiated)
            $stmt = $conn->prepare("
                UPDATE event_registrations 
                SET status = 'cancelled', 
                    updated_at = NOW(),
                    cancellation_reason = 'Cancelled by student'
                WHERE registration_id = :registration_id AND student_id = :student_id
            ");
            $stmt->bindParam(':registration_id', $registration_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            // Success
            echo json_encode([
                'success' => true,
                'message' => 'Registration cancelled successfully.'
            ]);
            exit;
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Registration cancellation error: " . $e->getMessage());
            
            // Return an error message
            echo json_encode([
                'success' => false,
                'message' => 'A database error occurred while trying to cancel your registration.'
            ]);
            exit;
        }
    }
    
    // If we got here, the request was invalid
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}
?>