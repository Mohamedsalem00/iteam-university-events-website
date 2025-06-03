<?php

// Include database connection
require_once 'db_connection.php';
session_start();

// Set JSON response headers
header('Content-Type: application/json');

// Check if user is authenticated and is an organization
if (!isset($_SESSION['account_id']) || $_SESSION['account_type'] !== 'organization') {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Get organization ID from session
$organizationId = $_SESSION['organization_id'];

// Get date parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-3 months'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

try {
    // Prepare the response object
    $response = [
        'success' => true,
        'stats' => [],
        'charts' => []
    ];
    
    // Get total events count
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_events 
        FROM events 
        WHERE organizer_id = :organizer_id
        AND start_date BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total_events'];
    
    // Get total registrations count
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_registrations 
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $totalRegistrations = $stmt->fetch(PDO::FETCH_ASSOC)['total_registrations'];
    
    // Calculate attendance rate based on status instead of 'attended' column
    // Using 'confirmed' status as indicating attendance
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN er.status = 'confirmed' THEN 1 ELSE 0 END), 0) AS total_attended,
            COUNT(*) AS total_registered
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $attendanceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attendanceRate = 0;
    if ($attendanceData['total_registered'] > 0) {
        $attendanceRate = round(($attendanceData['total_attended'] / $attendanceData['total_registered']) * 100);
    }
    
    // Calculate job application conversion rate using job_offer_id to link to organizations
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT ja.student_id) AS total_applicants,
            COUNT(DISTINCT er.student_id) AS total_attendees
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        LEFT JOIN job_applications ja ON ja.student_id = er.student_id
        LEFT JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id AND jo.organization_id = e.organizer_id
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
        AND er.status = 'confirmed'
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $applicationData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $applicationRate = 0;
    if ($applicationData['total_attendees'] > 0) {
        $applicationRate = round(($applicationData['total_applicants'] / $applicationData['total_attendees']) * 100);
    }
    
    // Store stats in response
    $response['stats'] = [
        'total_events' => $totalEvents,
        'total_registrations' => $totalRegistrations,
        'attendance_rate' => $attendanceRate,
        'application_rate' => $applicationRate
    ];
    
    // Get registrations over time (for line chart)
    // Using created_at instead of registration_date
    $stmt = $conn->prepare("
        SELECT 
            DATE(er.created_at) AS reg_date,
            COUNT(*) AS reg_count
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id
        AND er.created_at BETWEEN :start_date AND :end_date
        GROUP BY DATE(er.created_at)
        ORDER BY reg_date ASC
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $registrationsOverTime = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart.js
    $regTimeLabels = [];
    $regTimeValues = [];
    foreach ($registrationsOverTime as $reg) {
        $regTimeLabels[] = date('M d', strtotime($reg['reg_date']));
        $regTimeValues[] = intval($reg['reg_count']);
    }
    
    // Get event popularity (for bar chart) - using confirmed status instead of attended=1
    $stmt = $conn->prepare("
        SELECT 
            e.title,
            COUNT(er.registration_id) AS attendee_count
        FROM events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.status = 'confirmed'
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
        GROUP BY e.event_id
        ORDER BY attendee_count DESC
        LIMIT 10
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $eventPopularity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart.js
    $popLabels = [];
    $popValues = [];
    foreach ($eventPopularity as $event) {
        // Limit title length for better display in charts
        $title = (strlen($event['title']) > 15) ? substr($event['title'], 0, 15) . '...' : $event['title'];
        $popLabels[] = $title;
        $popValues[] = intval($event['attendee_count']);
    }
    
    // Get student demographics - use first_name instead of major since major doesn't exist
    // Using a simplified approach since major isn't available
    $stmt = $conn->prepare("
        SELECT 
            SUBSTRING(s.first_name, 1, 1) AS first_initial,
            COUNT(DISTINCT er.student_id) AS student_count
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        JOIN students s ON er.student_id = s.student_id
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
        GROUP BY first_initial
        ORDER BY student_count DESC
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $demographics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart.js
    $demoLabels = [];
    $demoValues = [];
    foreach ($demographics as $demo) {
        $demoLabels[] = 'Group ' . $demo['first_initial'];  // Using first initial as a group
        $demoValues[] = intval($demo['student_count']);
    }
    
    // Get event types distribution (for pie chart)
    $stmt = $conn->prepare("
        SELECT 
            event_type,
            COUNT(*) AS event_count
        FROM events
        WHERE organizer_id = :organizer_id
        AND start_date BETWEEN :start_date AND :end_date
        GROUP BY event_type
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for chart.js
    $typeLabels = [];
    $typeValues = [];
    foreach ($eventTypes as $type) {
        $typeLabels[] = $type['event_type'];
        $typeValues[] = intval($type['event_count']);
    }
    
    // Get top performing events - using status='confirmed' instead of attended=1
    $stmt = $conn->prepare("
        SELECT 
            e.title,
            COUNT(er.registration_id) AS total_registrations,
            SUM(CASE WHEN er.status = 'confirmed' THEN 1 ELSE 0 END) AS attendees,
            CASE 
                WHEN COUNT(er.registration_id) > 0 
                THEN ROUND((SUM(CASE WHEN er.status = 'confirmed' THEN 1 ELSE 0 END) / COUNT(er.registration_id)) * 100)
                ELSE 0
            END AS registration_rate
        FROM events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id
        WHERE e.organizer_id = :organizer_id
        AND e.start_date BETWEEN :start_date AND :end_date
        GROUP BY e.event_id
        ORDER BY attendees DESC, registration_rate DESC
        LIMIT 5
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $topEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Store chart data in response
    $response['charts'] = [
        'registrations_over_time' => [
            'labels' => $regTimeLabels,
            'values' => $regTimeValues
        ],
        'event_popularity' => [
            'labels' => $popLabels,
            'values' => $popValues
        ],
        'demographics' => [
            'labels' => $demoLabels,
            'values' => $demoValues
        ],
        'event_types' => [
            'labels' => $typeLabels,
            'values' => $typeValues
        ],
        'top_events' => $topEvents
    ];
    
    // Return the data
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
}

// Handle no data edge cases
function handleEmptyData() {
    // Create sample data for demonstration when no real data exists
    return [
        'success' => true,
        'stats' => [
            'total_events' => 0,
            'total_registrations' => 0,
            'attendance_rate' => 0,
            'application_rate' => 0
        ],
        'charts' => [
            'registrations_over_time' => [
                'labels' => [],
                'values' => []
            ],
            'event_popularity' => [
                'labels' => [],
                'values' => []
            ],
            'demographics' => [
                'labels' => [],
                'values' => []
            ],
            'event_types' => [
                'labels' => [],
                'values' => []
            ],
            'top_events' => []
        ]
    ];
}
?>
