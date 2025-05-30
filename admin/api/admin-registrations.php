<?php
// filepath: /opt/lampp/htdocs/student-event-platform/api/admin-registrations.php
session_start();

// Include database connection
require_once 'db_connection.php';

// Set headers to ensure proper JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in and is an admin
if (!isset($_SESSION['account_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as an admin to perform this action.'
    ]);
    exit;
}

// Handle updating registration status (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    // Validate required data
    if (!isset($data['registration_id']) || !isset($data['action'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Registration ID and action are required'
        ]);
        exit;
    }
    
    $registration_id = intval($data['registration_id']);
    $action = $data['action']; // 'approve' or 'reject'
    
    // Validate action
    if ($action !== 'approve' && $action !== 'reject') {
        echo json_encode([
            'success' => false,
            'message' => 'Action must be either "approve" or "reject"'
        ]);
        exit;
    }
    
    $new_status = ($action === 'approve') ? 'confirmed' : 'rejected';
    
    try {
        // Update the registration status
        $stmt = $conn->prepare("
            UPDATE event_registrations 
            SET status = :status, updated_at = NOW()
            WHERE registration_id = :registration_id
        ");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':registration_id', $registration_id);
        $stmt->execute();
        
        // Check if any row was affected
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Registration not found or already processed'
            ]);
            exit;
        }
        
        // Get the registration details to send notification
        $stmt = $conn->prepare("
            SELECT er.student_id, e.title, u.email, u.name 
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN students u ON er.student_id = u.student_id
            WHERE er.registration_id = :registration_id
        ");
        $stmt->bindParam(':registration_id', $registration_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $reg_details = $stmt->fetch();
            
            // In a real application, you might want to send an email here
            // For now, we'll just log it
            $action_message = ($action === 'approve') ? 'approved' : 'rejected';
            error_log("Registration for event '{$reg_details['title']}' by {$reg_details['name']} ({$reg_details['email']}) has been {$action_message}");
            
            // Optional: Insert notification for the user
            if (isset($reg_details['student_id'])) {
                $notification_message = ($action === 'approve') 
                    ? "Your registration for '{$reg_details['title']}' has been approved!"
                    : "Your registration for '{$reg_details['title']}' has been rejected.";
                
                $stmt = $conn->prepare("
                    INSERT INTO notifications (student_id, message, type, read_status, created_at)
                    VALUES (:student_id, :message, :type, 0, NOW())
                ");
                $stmt->bindParam(':student_id', $reg_details['student_id']);
                $stmt->bindParam(':message', $notification_message);
                $type = ($action === 'approve') ? 'registration_approved' : 'registration_rejected';
                $stmt->bindParam(':type', $type);
                $stmt->execute();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully',
            'status' => $new_status
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration update error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while updating the registration status.'
        ]);
    }
    
    exit;
}

// Handle GET request - fetch pending registrations
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Prepare query to get pending registrations with event and user details
        $query = "
            SELECT 
                er.registration_id, 
                er.event_id,
                er.student_id, 
                er.status, 
                er.created_at as registration_date,
                er.updated_at,
                e.title as event_title, 
                e.description as event_description,
                e.start_date, 
                e.end_date,
                e.location,
                e.max_capacity,
                e.organizer_id,
                e.event_type,
                e.thumbnail_url,
                u.name as user_name,
                u.email as user_email,
                u.profile_picture as user_image
            FROM 
                event_registrations er
            JOIN 
                events e ON er.event_id = e.event_id
            JOIN 
                students u ON er.student_id = u.student_id
            WHERE 
                er.status = 'pending'
            ORDER BY 
                er.created_at DESC
        ";
        
        $stmt = $conn->query($query);
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'registrations' => $registrations
        ]);
        
    } catch (PDOException $e) {
        error_log("Error fetching pending registrations: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while fetching pending registrations.'
        ]);
    }
    
    exit;
}

// If we get here, it's an unsupported request method
echo json_encode([
    'success' => false,
    'message' => 'Unsupported request method'
]);
?>