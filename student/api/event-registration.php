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

// Check if student is logged in and is a student
if (!isset($_SESSION['student_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as a student to register for events.'
    ]);
    exit;
}

// Handle POST request for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!isset($data['event_id']) || empty($data['event_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Event ID is required'
        ]);
        exit;
    }
    
    $event_id = intval($data['event_id']);
    $student_id = $_SESSION['student_id'];
    
    // Check if this is a cancellation request
    $is_cancellation = isset($data['action']) && $data['action'] === 'cancel';
    
    try {
        // If it's a cancellation request, handle it differently
        if ($is_cancellation) {
            // Check if student is registered for this event
            $stmt = $conn->prepare("
                SELECT * FROM event_registrations 
                WHERE event_id = :event_id AND student_id = :student_id
            ");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'You are not registered for this event'
                ]);
                exit;
            }
            
            // DELETE registration instead of updating status
            $stmt = $conn->prepare("
                DELETE FROM event_registrations 
                WHERE event_id = :event_id AND student_id = :student_id
            ");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration cancelled successfully'
            ]);
            exit;
        }
        
        // First check if the event exists and is not in the past
        // Also retrieve the requires_approval field
        $stmt = $conn->prepare("
            SELECT *, requires_approval FROM events 
            WHERE event_id = :event_id AND end_date >= NOW()
        ");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Event not found or has already ended'
            ]);
            exit;
        }
        
        $event = $stmt->fetch();
        
        // Determine if approval is required
        $requires_approval = (bool)$event['requires_approval'];
        
        // Set registration status based on whether approval is required
        $registration_status = $requires_approval ? 'pending' : 'confirmed';
        
        // Check if the event has reached its maximum capacity
        if ($event['max_capacity']) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as registration_count 
                FROM event_registrations 
                WHERE event_id = :event_id AND status IN ('confirmed', 'pending')
            ");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->execute();
            $count = $stmt->fetch();
            
            if ($count['registration_count'] >= $event['max_capacity']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sorry, this event has reached its maximum capacity'
                ]);
                exit;
            }
        }
        
        // Check if student is already registered for this event
        $stmt = $conn->prepare("
            SELECT * FROM event_registrations 
            WHERE event_id = :event_id AND student_id = :student_id
        ");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $registration = $stmt->fetch();
            
            // Already registered
            echo json_encode([
                'success' => false,
                'message' => 'You are already registered for this event',
                'registration_id' => $registration['registration_id'],
                'status' => $registration['status']
            ]);
            exit;
        }
        
        // Register the student for the event with appropriate status
        $stmt = $conn->prepare("
            INSERT INTO event_registrations (event_id, student_id, status, created_at, updated_at)
            VALUES (:event_id, :student_id, :registration_status, NOW(), NOW())
        ");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':registration_status', $registration_status);
        $stmt->execute();
        
        $registration_id = $conn->lastInsertId();
        
        // Prepare appropriate message based on registration status
        $message = $requires_approval 
            ? 'Registration successful! Your registration is pending approval.' 
            : 'Registration successful! You are confirmed for this event.';
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'registration_id' => $registration_id,
            'status' => $registration_status,
            'requires_approval' => $requires_approval
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during registration. Please try again later.'
        ]);
    }
    
    exit;
}

// Handle GET request (e.g., checking registration status)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Event ID is required'
        ]);
        exit;
    }
    
    $event_id = intval($_GET['event_id']);
    $student_id = $_SESSION['student_id'];
    
    try {
        // Check if student is registered for this event
        $stmt = $conn->prepare("
            SELECT er.*, e.requires_approval 
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.event_id = :event_id AND er.student_id = :student_id
        ");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $registration = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'is_registered' => true,
                'registration_id' => $registration['registration_id'],
                'status' => $registration['status'],
                'registration_date' => $registration['registration_date'],
                'requires_approval' => (bool)$registration['requires_approval']
            ]);
        } else {
            // If not registered, check if the event requires approval
            $stmt = $conn->prepare("
                SELECT requires_approval 
                FROM events 
                WHERE event_id = :event_id
            ");
            $stmt->bindParam(':event_id', $event_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $event = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'is_registered' => false,
                    'requires_approval' => (bool)$event['requires_approval']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'is_registered' => false,
                    'requires_approval' => false
                ]);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Registration status check error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while checking registration status.'
        ]);
    }
    
    exit;
}

// If we get here, it's an invalid request method
echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
?>