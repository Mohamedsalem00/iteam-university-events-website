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

// Get query parameters for filtering and pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$timeframe = isset($_GET['timeframe']) ? sanitizeInput($_GET['timeframe']) : 'upcoming';

// Calculate offset for pagination
$offset = ($page - 1) * $perPage;

// Start building the query
$query = "
    SELECT 
        e.*, 
        o.name AS organizer_name,
        o.profile_picture AS organizer_image,
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) AS registration_count
    FROM 
        events e
    LEFT JOIN 
        organizations o ON e.organizer_id = o.organization_id
    WHERE 1=1
";
$countQuery = "SELECT COUNT(*) as total FROM events e WHERE 1=1";
$params = [];

// Add search condition if provided
if (!empty($search)) {
    $searchCondition = " AND (e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $params[':search'] = "%$search%";
}

// Add category condition if provided
if (!empty($category) && $category !== 'All Categories') {
    $query .= " AND e.event_type = :category";
    $countQuery .= " AND e.event_type = :category";
    $params[':category'] = $category;
}

// Add timeframe condition
$now = date('Y-m-d H:i:s');
switch ($timeframe) {
    case 'upcoming':
        $query .= " AND e.end_date >= :now";
        $countQuery .= " AND e.end_date >= :now";
        $params[':now'] = $now;
        $query .= " ORDER BY e.start_date ASC";
        break;
    
    case 'this_week':
        $query .= " AND e.start_date BETWEEN :now AND DATE_ADD(:now_week, INTERVAL 7 DAY)";
        $countQuery .= " AND e.start_date BETWEEN :now AND DATE_ADD(:now_week, INTERVAL 7 DAY)";
        $params[':now'] = $now;
        $params[':now_week'] = $now;
        $query .= " ORDER BY e.start_date ASC";
        break;
    
    case 'this_month':
        $query .= " AND e.start_date BETWEEN :now AND DATE_ADD(:now_month, INTERVAL 1 MONTH)";
        $countQuery .= " AND e.start_date BETWEEN :now AND DATE_ADD(:now_month, INTERVAL 1 MONTH)";
        $params[':now'] = $now;
        $params[':now_month'] = $now;
        $query .= " ORDER BY e.start_date ASC";
        break;
    
    case 'past':
        $query .= " AND e.end_date < :now";
        $countQuery .= " AND e.end_date < :now";
        $params[':now'] = $now;
        $query .= " ORDER BY e.start_date DESC";
        break;
    
    default:
        $query .= " ORDER BY e.start_date ASC";
        break;
}

// Check if we're specifically requesting featured events
$isFeaturedRequest = isset($_GET['featured']) && $_GET['featured'] === 'true';

if ($isFeaturedRequest) {
    // For featured events:
    // 1. Prioritize upcoming events with the most registrations
    // 2. Use the next 30 days for relevance
    
    $query = "
        SELECT 
            e.*, 
            o.name AS organizer_name,
            o.profile_picture AS organizer_image,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) AS registration_count
        FROM 
            events e
        LEFT JOIN 
            organizations o ON e.organizer_id = o.organization_id
        WHERE 
            e.end_date >= :now
        ORDER BY 
            registration_count DESC, 
            e.start_date ASC
    ";
    
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM events e 
        WHERE e.end_date >= :now
    ";
    
    $params = [':now' => $now];
    
    // Add pagination after defining the featured query
    $query .= " LIMIT :offset, :perPage";
} else {
    // For non-featured queries, add pagination here
    $query .= " LIMIT :offset, :perPage";
}

try {
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch()['total'];
    
    // Get the events
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if the student is logged in to determine registration status
    $studentRegistrations = [];
    $studentId = null;
    
    if (isset($_SESSION['student_id']) && $_SESSION['account_type'] === 'student') {
        $studentId = $_SESSION['student_id'];
        
        // Get all registrations for this student
        $regStmt = $conn->prepare("
            SELECT event_id, status 
            FROM event_registrations 
            WHERE student_id = :student_id
        ");
        $regStmt->bindParam(':student_id', $studentId);
        $regStmt->execute();
        
        while ($row = $regStmt->fetch()) {
            $studentRegistrations[$row['event_id']] = $row['status'];
        }
    }
    
    // Format the events and add registration status
    $formattedEvents = [];
    foreach ($events as $event) {
        $registrationStatus = isset($studentRegistrations[$event['event_id']]) ? $studentRegistrations[$event['event_id']] : null;
        
        $formattedEvents[] = [
            'event_id' => $event['event_id'], // Changed 'id' to 'event_id' for consistency
            'title' => $event['title'],
            'description' => $event['description'],
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'],
            'location' => $event['location'],
            'event_type' => $event['event_type'],
            'max_capacity' => $event['max_capacity'],
            'thumbnail_url' => $event['thumbnail_url'] ? $event['thumbnail_url'] : 'frontend/assets/images/gallery/placeholder_event.png',
            'organizer_name' => $event['organizer_name'],
            'organizer_image' => $event['organizer_image'],
            'registration_count' => $event['registration_count'],
            'registration_status' => $registrationStatus,
            'is_full' => $event['max_capacity'] && $event['registration_count'] >= $event['max_capacity']
        ];
    }
    
    // Calculate total pages
    $totalPages = ceil($totalCount / $perPage);
    
    // Return success response - ensure we always return an array for events
    echo json_encode([
        'success' => true,
        'events' => $formattedEvents, // This will be an empty array if no events were found
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_items' => (int)$totalCount,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'student_id' => $studentId
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Add these JSON options
    
} catch (PDOException $e) {
    // Log the error
    error_log("Events API error: " . $e->getMessage());
    
    // Return an error message with empty events array
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'events' => [] // Always include an empty events array
    ]);
}
?>