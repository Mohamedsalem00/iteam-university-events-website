<?php
require_once 'db_connection.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Get the search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Initialize response
$response = [
    'success' => false,
    'results' => [],
    'message' => ''
];

// Validate input
if (empty($query)) {
    $response['message'] = 'Search query is required';
    echo json_encode($response);
    exit;
}

if (strlen($query) < 2) {
    $response['message'] = 'Search query must be at least 2 characters';
    echo json_encode($response);
    exit;
}

try {
    // Start transaction for consistent reads
    $conn->beginTransaction();
    
    // Search events - Update the path to match our router patterns
    $eventQuery = "SELECT 
                    e.event_id as id,
                    e.title,
                    'event' as type,
                    CONCAT('events/', e.event_id) as path,
                    e.start_date as date,
                    CASE 
                        WHEN e.start_date > NOW() THEN 'upcoming'
                        WHEN e.end_date < NOW() THEN 'past'
                        ELSE 'active'
                    END as status
                FROM events e
                WHERE (e.title LIKE :search OR e.description LIKE :search)
                ORDER BY e.start_date DESC
                LIMIT :limit";
    
    $stmt = $conn->prepare($eventQuery);
    $searchParam = '%' . $query . '%';
    $stmt->bindParam(':search', $searchParam);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $eventResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search organizations - Update the path
    $orgQuery = "SELECT 
                o.organization_id as id,
                o.name as title,
                'organization' as type,
                CONCAT('organizations/', o.organization_id) as path,
                o.created_at as date,
                o.status
                FROM organizations o
                WHERE (o.name LIKE :search OR o.description LIKE :search)
                ORDER BY o.name ASC
                LIMIT :limit";
    
    $stmt = $conn->prepare($orgQuery);
    $stmt->bindParam(':search', $searchParam);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orgResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search students - Update the path
    $studentQuery = "SELECT 
                u.student_id as id,
                CONCAT(u.first_name, ' ', u.last_name) as title,
                'student' as type,
                CONCAT('students/', u.student_id) as path,
                u.created_at as date,
                u.status
                FROM students u
                WHERE (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)
                ORDER BY u.last_name, u.first_name ASC
                LIMIT :limit";
    
    $stmt = $conn->prepare($studentQuery);
    $stmt->bindParam(':search', $searchParam);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $studentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and format results
    $combinedResults = array_merge($eventResults, $orgResults, $studentResults);
    
    // Sort by relevance (simple implementation - could be enhanced)
    usort($combinedResults, function($a, $b) use ($query) {
        // Exact title matches get highest priority
        $aExactMatch = stripos($a['title'], $query) !== false;
        $bExactMatch = stripos($b['title'], $query) !== false;
        
        if ($aExactMatch && !$bExactMatch) return -1;
        if (!$aExactMatch && $bExactMatch) return 1;
        
        // Then sort by most recent
        if (isset($a['date']) && isset($b['date'])) {
            return strtotime($b['date']) - strtotime($a['date']);
        }
        
        return 0;
    });
    
    // Limit total results
    $combinedResults = array_slice($combinedResults, 0, $limit);
    
    // Format for display
    foreach ($combinedResults as $key => $result) {
        // Format the display data
        switch ($result['type']) {
            case 'event':
                $icon = 'ri-calendar-event-line';
                $typeLabel = 'Event';
                
                // Format date for events
                if (isset($result['date'])) {
                    $eventDate = new DateTime($result['date']);
                    $combinedResults[$key]['formattedDate'] = $eventDate->format('M j, Y');
                }
                break;
                
            case 'organization':
                $icon = 'ri-building-line';
                $typeLabel = 'Organization';
                break;
                
            case 'student':
                $icon = 'ri-user-line';
                $typeLabel = 'Student';
                break;
                
            default:
                $icon = 'ri-file-list-line';
                $typeLabel = ucfirst($result['type']);
        }
        
        $combinedResults[$key]['icon'] = $icon;
        $combinedResults[$key]['typeLabel'] = $typeLabel;
    }
    
    $conn->commit();
    
    $response['success'] = true;
    $response['results'] = $combinedResults;
    
} catch (PDOException $e) {
    $conn->rollBack();
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>