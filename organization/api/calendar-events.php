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

try {
    // Get filter parameters
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n') - 1; // JavaScript months are 0-based
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $studentId = $_SESSION['student_id'];
    
    // Calculate the first and last day of the requested month
    $firstDay = date('Y-m-d', strtotime($year . '-' . ($month + 1) . '-01'));
    $lastDay = date('Y-m-t', strtotime($firstDay));
    
    // Extend the range to include the previous and next month days that appear in the calendar view
    $firstDayOfWeek = date('w', strtotime($firstDay)); // 0 (Sunday) through 6 (Saturday)
    $extendedFirstDay = date('Y-m-d', strtotime($firstDay . ' -' . $firstDayOfWeek . ' days'));
    
    $lastDayOfMonth = date('Y-m-d', strtotime($lastDay));
    $lastDayOfWeek = date('w', strtotime($lastDayOfMonth)); // 0 (Sunday) through 6 (Saturday)
    $daysToAdd = 6 - $lastDayOfWeek;
    $extendedLastDay = date('Y-m-d', strtotime($lastDayOfMonth . ' +' . $daysToAdd . ' days'));
    
    // Query to get all events for the extended month range
    $stmt = $conn->prepare("
        SELECT 
            e.event_id,
            e.title,
            e.location,
            e.event_type,
            e.start_date,
            e.end_date,
            e.max_capacity,
            e.thumbnail_url,
            e.description,
            o.name AS organizer_name,
            CASE 
                WHEN e.end_date < NOW() THEN 'completed' 
                ELSE 'upcoming' 
            END AS status,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status IN ('confirmed', 'pending')) AS current_attendees
        FROM 
            events e
        LEFT JOIN 
            organizations o ON e.organizer_id = o.organization_id
        WHERE 
            (e.start_date BETWEEN :first_day AND :last_day) OR
            (e.end_date BETWEEN :first_day AND :last_day)
        ORDER BY 
            e.start_date ASC
    ");
    
    $stmt->bindParam(':first_day', $extendedFirstDay);
    $stmt->bindParam(':last_day', $extendedLastDay);
    $stmt->execute();
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student's registrations
    $regStmt = $conn->prepare("
        SELECT 
            er.event_id, 
            er.status,
            er.registration_id
        FROM 
            event_registrations er
        WHERE 
            er.student_id = :student_id
    ");
    $regStmt->bindParam(':student_id', $studentId);
    $regStmt->execute();
    
    $studentRegistrations = [];
    while ($reg = $regStmt->fetch()) {
        $studentRegistrations[$reg['event_id']] = [
            'status' => $reg['status'],
            'registration_id' => $reg['registration_id']
        ];
    }
    
    // Mark events that the student is registered for
    foreach ($events as &$event) {
        $event['isRegistered'] = isset($studentRegistrations[$event['event_id']]);
        $event['registrationStatus'] = $event['isRegistered'] ? $studentRegistrations[$event['event_id']]['status'] : null;
        $event['registrationId'] = $event['isRegistered'] ? $studentRegistrations[$event['event_id']]['registration_id'] : null;
        
        // Add is_full property based on capacity
        $event['is_full'] = $event['max_capacity'] && $event['current_attendees'] >= $event['max_capacity'];
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'month' => $month,
        'year' => $year
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Calendar events API error: " . $e->getMessage());
    
    // Return an error message
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
}
?>