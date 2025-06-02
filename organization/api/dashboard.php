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

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Collect all dashboard data in a single API call
    $response = [
        'success' => true,
        'stats' => getStats($conn, $organizationId),
        'alerts' => getAlerts($conn, $organizationId),
        'upcoming_events' => getUpcomingEvents($conn, $organizationId),
        'pending_registrations' => getPendingRegistrations($conn, $organizationId),
        'chart_data' => [
            'registrations' => getRegistrationTrends($conn, $organizationId),
            'capacity' => getEventCapacityData($conn, $organizationId)
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in dashboard API: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
}

// Function to get dashboard statistics
function getStats($conn, $organizationId) {
    // Total events
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = :organizer_id");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $totalEvents = $stmt->fetchColumn();
    
    // Total registrations
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $totalRegistrations = $stmt->fetchColumn();
    
    // Upcoming events
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM events 
        WHERE organizer_id = :organizer_id AND start_date > NOW()
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $upcomingEvents = $stmt->fetchColumn();
    
    // Active job offers
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM job_offers 
        WHERE organization_id = :organization_id AND expiry_date > NOW() AND status = 'active'
    ");
    $stmt->bindParam(':organization_id', $organizationId);
    $stmt->execute();
    $activeJobs = $stmt->fetchColumn();
    
    return [
        'total_events' => $totalEvents,
        'total_registrations' => $totalRegistrations,
        'upcoming_events' => $upcomingEvents,
        'active_jobs' => $activeJobs
    ];
}

// Function to get alerts for the dashboard
function getAlerts($conn, $organizationId) {
    $alerts = [];
    
    // Check if there are any events that are upcoming in the next 48 hours
    $stmt = $conn->prepare("
        SELECT event_id, title FROM events 
        WHERE organizer_id = :organizer_id 
        AND start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $upcomingSoonEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($upcomingSoonEvents) > 0) {
        foreach ($upcomingSoonEvents as $event) {
            $alerts[] = [
                'type' => 'info',
                'message' => "Your event '{$event['title']}' is starting within the next 48 hours.",
                'action' => [
                    'text' => 'View Event',
                    'url' => "../pages/organization/event-details.html?id={$event['event_id']}"
                ]
            ];
        }
    }
    
    // Check if there are any events approaching capacity limit (90% or more)
    $stmt = $conn->prepare("
        SELECT e.event_id, e.title, e.max_capacity, COUNT(er.registration_id) AS registered
        FROM events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id
        WHERE e.organizer_id = :organizer_id AND e.start_date > NOW()
        GROUP BY e.event_id
        HAVING registered >= (e.max_capacity * 0.9)
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $nearCapacityEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($nearCapacityEvents) > 0) {
        foreach ($nearCapacityEvents as $event) {
            $percentFull = round(($event['registered'] / $event['max_capacity']) * 100);
            $alerts[] = [
                'type' => 'warning',
                'message' => "Your event '{$event['title']}' is at {$percentFull}% capacity ({$event['registered']}/{$event['max_capacity']} registered).",
                'action' => [
                    'text' => 'View Registrations',
                    'url' => "../pages/organization/registrations.html?event_id={$event['event_id']}"
                ]
            ];
        }
    }
    
    // Check if there are pending registrations that need approval
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id AND er.status = 'pending' AND e.requires_approval = 1
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $pendingRegistrations = $stmt->fetchColumn();
    
    if ($pendingRegistrations > 0) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "You have {$pendingRegistrations} pending registration" . ($pendingRegistrations > 1 ? 's' : '') . " waiting for approval.",
            'action' => [
                'text' => 'Review',
                'url' => "../pages/organization/registrations.html?filter=pending"
            ]
        ];
    }
    
    // Check if organization profile is incomplete
    $stmt = $conn->prepare("
        SELECT name, description, address, phone, profile_picture
        FROM organizations
        WHERE organization_id = :organization_id
    ");
    $stmt->bindParam(':organization_id', $organizationId);
    $stmt->execute();
    $orgProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $incompleteFields = [];
    foreach ($orgProfile as $field => $value) {
        if (empty($value) && $field != 'profile_picture') {
            $incompleteFields[] = $field;
        }
    }
    
    if (count($incompleteFields) > 0) {
        $alerts[] = [
            'type' => 'info',
            'message' => "Your organization profile is incomplete. Please add " . implode(', ', $incompleteFields) . ".",
            'action' => [
                'text' => 'Complete Profile',
                'url' => "../pages/organization/profile.html"
            ]
        ];
    }
    
    // Check if any job offers are expiring soon (within 7 days)
    $stmt = $conn->prepare("
        SELECT job_offer_id, title FROM job_offers
        WHERE organization_id = :organization_id 
        AND expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND status = 'active'
    ");
    $stmt->bindParam(':organization_id', $organizationId);
    $stmt->execute();
    $expiringJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($expiringJobs) > 0) {
        foreach ($expiringJobs as $job) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Your job offer '{$job['title']}' is expiring in less than 7 days.",
                'action' => [
                    'text' => 'Extend',
                    'url' => "../pages/organization/job-offers.html?id={$job['job_offer_id']}"
                ]
            ];
        }
    }
    
    return $alerts;
}

// Function to get upcoming events for the dashboard
function getUpcomingEvents($conn, $organizationId) {
    $stmt = $conn->prepare("
        SELECT e.*, 
            (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id) as registered_count
        FROM events e
        WHERE e.organizer_id = :organizer_id AND e.start_date > NOW()
        ORDER BY e.start_date ASC
        LIMIT 5
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get pending registrations that need approval
function getPendingRegistrations($conn, $organizationId) {
    $stmt = $conn->prepare("
        SELECT er.*, e.title as event_title, s.first_name, s.last_name, s.email
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        JOIN students s ON er.student_id = s.student_id
        WHERE e.organizer_id = :organizer_id AND er.status = 'pending' AND e.requires_approval = 1
        ORDER BY er.registration_date DESC
        LIMIT 5
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get registration trends data for the chart
function getRegistrationTrends($conn, $organizationId) {
    // Get data for the last 30 days
    $stmt = $conn->prepare("
        SELECT DATE(er.registration_date) as date, COUNT(*) as count
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE e.organizer_id = :organizer_id 
        AND er.registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(er.registration_date)
        ORDER BY DATE(er.registration_date) ASC
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill in any missing dates with zero counts
    $data = [];
    $endDate = new DateTime();
    $startDate = new DateTime('-30 days');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startDate, $interval, $endDate);
    
    foreach ($period as $date) {
        $formattedDate = $date->format('Y-m-d');
        $data[] = [
            'date' => $formattedDate,
            'count' => 0
        ];
    }
    
    // Override with actual data where available
    foreach ($results as $row) {
        foreach ($data as $key => $value) {
            if ($value['date'] === $row['date']) {
                $data[$key]['count'] = intval($row['count']);
                break;
            }
        }
    }
    
    return $data;
}

// Function to get event capacity data for the chart
function getEventCapacityData($conn, $organizationId) {
    $stmt = $conn->prepare("
        SELECT 
            e.event_id,
            e.title as event_title,
            e.max_capacity,
            COUNT(er.registration_id) as registered_count,
            CASE
                WHEN COUNT(er.registration_id) >= e.max_capacity THEN 'Full'
                ELSE 'Available'
            END as capacity_status,
            ROUND((COUNT(er.registration_id) / e.max_capacity) * 100, 1) as percent_full
        FROM events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id
        WHERE e.organizer_id = :organizer_id AND e.start_date > NOW()
        GROUP BY e.event_id
        ORDER BY e.start_date ASC
        LIMIT 5
    ");
    $stmt->bindParam(':organizer_id', $organizationId);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>