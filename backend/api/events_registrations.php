<?php
require_once '../db/db_connection.php';
header('Content-Type: application/json');

// Get parameters
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

try {
    if ($action === 'recent') {
        // Get recent registrations for all events
        $query = "
            SELECT 
                er.registration_id,
                er.event_id,
                er.user_id,
                er.status,
                er.registration_date,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                e.title AS event_title
            FROM event_registrations er
            JOIN users u ON er.user_id = u.user_id
            JOIN events e ON er.event_id = e.event_id
            ORDER BY er.registration_date DESC
            LIMIT :limit
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'registrations' => $registrations
        ]);
    } elseif ($action === 'for_event' && $eventId > 0) {
        // Get registrations for a specific event
        $query = "
            SELECT 
                er.registration_id,
                er.event_id,
                er.user_id,
                er.status,
                er.registration_date,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                u.email,
                e.title AS event_title
            FROM event_registrations er
            JOIN users u ON er.user_id = u.user_id
            JOIN events e ON er.event_id = e.event_id
            WHERE er.event_id = :event_id
            ORDER BY er.registration_date DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM event_registrations WHERE event_id = :event_id";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $countStmt->execute();
        $total = $countStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'registrations' => $registrations,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_results' => $total,
                'results_per_page' => $limit
            ]
        ]);
    } else {
        // Invalid request
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request parameters'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch event registrations: ' . $e->getMessage()
    ]);
}
?>