<?php
require_once '../db/db_connection.php';
header('Content-Type: application/json');

try {
    // Get request parameters
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';
    $organizer = isset($_GET['organizer']) ? $_GET['organizer'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Base query
    $query = "
        SELECT 
            e.event_id,
            e.title,
            e.start_date,
            e.end_date,
            e.location,
            e.event_type,
            e.max_capacity,
            COALESCE(o.name, 'iTeam University') AS organizer_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) AS registered_count
        FROM events e
        LEFT JOIN organizations o ON e.organizer_id = o.organization_id
        WHERE 1=1
    ";
    
    $countQuery = "SELECT COUNT(*) FROM events e WHERE 1=1";
    $params = [];
    
    // Add type filter if not 'all'
    if ($filter !== 'all') {
        $query .= " AND e.event_type = :event_type";
        $countQuery .= " AND e.event_type = :event_type";
        $params[':event_type'] = $filter;
    }
    
    // Add status filter
    $now = date('Y-m-d H:i:s');
    if ($status === 'upcoming') {
        $query .= " AND e.start_date > :now";
        $countQuery .= " AND e.start_date > :now";
        $params[':now'] = $now;
    } else if ($status === 'ongoing') {
        $query .= " AND e.start_date <= :now AND e.end_date >= :now";
        $countQuery .= " AND e.start_date <= :now AND e.end_date >= :now";
        $params[':now'] = $now;
    } else if ($status === 'past') {
        $query .= " AND e.end_date < :now";
        $countQuery .= " AND e.end_date < :now";
        $params[':now'] = $now;
    }
    
    // Add date filter
    if ($dateFilter === 'today') {
        $query .= " AND DATE(e.start_date) = CURDATE()";
        $countQuery .= " AND DATE(e.start_date) = CURDATE()";
    } else if ($dateFilter === 'week') {
        $query .= " AND e.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        $countQuery .= " AND e.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } else if ($dateFilter === 'month') {
        $query .= " AND e.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
        $countQuery .= " AND e.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
    }
    
    // Add organizer filter
    if ($organizer !== 'all') {
        if ($organizer === 'university') {
            $query .= " AND e.organizer_id IS NULL";
            $countQuery .= " AND e.organizer_id IS NULL";
        } else {
            $query .= " AND o.name LIKE :organizer";
            $countQuery .= " AND o.name LIKE :organizer";
            $params[':organizer'] = '%' . $organizer . '%';
        }
    }
    
    // Add search if provided
    if ($search) {
        $searchParam = '%' . $search . '%';
        $query .= " AND (e.title LIKE :search OR e.location LIKE :search OR o.name LIKE :search)";
        $countQuery .= " AND (e.title LIKE :search OR e.location LIKE :search OR o.name LIKE :search)";
        $params[':search'] = $searchParam;
    }
    
    // Add sorting
    $query .= " ORDER BY e.start_date DESC";
    
    // Execute count query
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalResults = $countStmt->fetchColumn();
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    
    // Execute main query
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $events = $stmt->fetchAll();
    
    // Format events and determine status
    $formattedEvents = [];
    $now = new DateTime();
    
    foreach ($events as $event) {
        $startDate = new DateTime($event['start_date']);
        $endDate = new DateTime($event['end_date']);
        
        // Determine event status
        $status = 'upcoming';
        if ($now > $endDate) {
            $status = 'past';
        } else if ($now >= $startDate && $now <= $endDate) {
            $status = 'ongoing';
        }
        
        // Check if event is full
        $isFull = $event['registered_count'] >= $event['max_capacity'];
        if ($isFull) {
            $status = 'full';
        }
        
        $formattedEvents[] = [
            'id' => $event['event_id'],
            'title' => $event['title'],
            'start_date' => $event['start_date'],
            'formatted_date' => $startDate->format('M d, Y'),
            'end_date' => $event['end_date'],
            'location' => $event['location'],
            'type' => $event['event_type'],
            'organizer' => $event['organizer_name'],
            'registered' => $event['registered_count'],
            'capacity' => $event['max_capacity'],
            'status' => $status,
            'is_full' => $isFull
        ];
    }
    
    // Get recent registrations
    $recentRegQuery = "
        SELECT 
            er.registration_id,
            er.status,
            er.registration_date,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name,
            e.title AS event_title,
            e.event_id
        FROM event_registrations er
        JOIN users u ON er.user_id = u.user_id
        JOIN events e ON er.event_id = e.event_id
        ORDER BY er.registration_date DESC
        LIMIT 4
    ";
    $recentRegStmt = $conn->query($recentRegQuery);
    $recentRegistrations = $recentRegStmt->fetchAll();
    
    // Calculate totals for stats
    $statsQuery = "
        SELECT 
            COUNT(*) AS total_events,
            SUM(CASE WHEN start_date > NOW() THEN 1 ELSE 0 END) AS upcoming_events,
            SUM(CASE WHEN start_date <= NOW() AND end_date >= NOW() THEN 1 ELSE 0 END) AS active_events,
            SUM(CASE WHEN end_date < NOW() THEN 1 ELSE 0 END) AS past_events
        FROM events
    ";
    $statsStmt = $conn->query($statsQuery);
    $stats = $statsStmt->fetch();
    
    $regStatsQuery = "SELECT COUNT(*) AS total_registrations FROM event_registrations";
    $regStatsStmt = $conn->query($regStatsQuery);
    $regStats = $regStatsStmt->fetch();
    
    $cancelledQuery = "SELECT COUNT(*) AS cancelled_events FROM event_registrations WHERE status = 'cancelled'";
    $cancelledStmt = $conn->query($cancelledQuery);
    $cancelledStats = $cancelledStmt->fetch();
    
    // Calculate total pages
    $totalPages = ceil($totalResults / $limit);
    
    echo json_encode([
        'success' => true,
        'events' => $formattedEvents,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_results' => $totalResults,
            'results_per_page' => $limit
        ],
        'stats' => [
            'total' => $stats['total_events'],
            'upcoming' => $stats['upcoming_events'],
            'active' => $stats['active_events'],
            'registrations' => $regStats['total_registrations'],
            'cancelled' => $cancelledStats['cancelled_events']
        ],
        'recent_registrations' => $recentRegistrations
    ]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch events list: ' . $e->getMessage()]);
}
?>