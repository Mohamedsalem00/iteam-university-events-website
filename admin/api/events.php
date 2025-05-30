<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/admin/api/events.php
require_once 'db_connection.php'; 

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'DELETE') {
    // --- Handle Delete Event ---
    $data = json_decode(file_get_contents('php://input'), true);
    $eventIdToDelete = isset($data['event_id']) ? (int)$data['event_id'] : null;

    if (!$eventIdToDelete) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Event ID not provided for deletion.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        $deleteSql = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bindParam(':event_id', $eventIdToDelete, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
            } else {
                $conn->rollBack();
                http_response_code(404); // Not Found
                echo json_encode(['success' => false, 'message' => 'Event not found or already deleted.']);
            }
        } else {
            $conn->rollBack();
            throw new PDOException("Execute failed for delete operation.");
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        error_log("API PDO Error (delete event): " . $e->getMessage());
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
             echo json_encode(['success' => false, 'message' => 'Cannot delete event. It may have active registrations or other dependencies that prevent deletion. Please resolve these first.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error deleting event. ' . $e->getMessage()]);
        }
    }
    $conn = null;
    exit;
}

$eventIdParam = ($requestMethod === 'GET' && (isset($_GET['event_id']) || isset($_GET['id']))) 
                ? (isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)$_GET['id']) 
                : null;

if ($requestMethod === 'GET' && $eventIdParam) {
    // --- Fetch Single Event Details (GET request) ---
    try {
        $sql = "
            SELECT
                e.event_id,
                e.title,
                e.description,
                e.location,
                e.event_type,
                e.start_date,
                e.end_date,
                e.max_capacity,
                e.created_at, 
                e.thumbnail_url,
                o.name AS organizer_name,
                o.organization_id,
                (SELECT COUNT(er.registration_id) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status = 'confirmed') AS total_attendees,
                CASE
                    WHEN NOW() < e.start_date THEN 'upcoming'
                    WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'active'
                    WHEN NOW() > e.end_date THEN 'completed'
                    ELSE 'unknown'
                END AS status
            FROM
                events e
            LEFT JOIN
                organizations o ON e.organizer_id = o.organization_id
            WHERE
                e.event_id = :event_id
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':event_id', $eventIdParam, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $attendeesSql = "SELECT u.student_id, u.first_name, u.last_name, u.email 
                             FROM event_registrations er
                             JOIN students u ON er.student_id = u.student_id
                             WHERE er.event_id = :event_id AND er.status = 'confirmed'
                             ORDER BY u.last_name, u.first_name
                             LIMIT 50";
            $attendeesStmt = $conn->prepare($attendeesSql);
            $attendeesStmt->bindParam(':event_id', $eventIdParam, PDO::PARAM_INT);
            $attendeesStmt->execute();
            $event['attendees'] = $attendeesStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'event' => $event]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("API PDO Error (single event): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error fetching event details.']);
    }
    $conn = null;
    exit;
}

if ($requestMethod === 'GET') {
    // --- Fetch List of Events ---
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
    $categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
    $organizerNameFilter = isset($_GET['organizer_name']) ? trim($_GET['organizer_name']) : '';
    $dateStartFilter = isset($_GET['date_start']) ? trim($_GET['date_start']) : '';
    $dateEndFilter = isset($_GET['date_end']) ? trim($_GET['date_end']) : '';

    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;

    if ($currentPage < 1) $currentPage = 1;
    if ($itemsPerPage < 1) $itemsPerPage = 10;
    if ($itemsPerPage > 100 && !(isset($_GET['limit']) && (int)$_GET['limit'] > 100) ) $itemsPerPage = 10; 
    if ($itemsPerPage > 10000) $itemsPerPage = 10000;

    $offset = ($currentPage - 1) * $itemsPerPage;

    $baseSql = "
        FROM
            events e
        LEFT JOIN
            organizations o ON e.organizer_id = o.organization_id
    ";

    $whereClauses = [];
    $queryParams = [];

    if (!empty($statusFilter)) {
        if ($statusFilter === 'active') { 
            $whereClauses[] = "((NOW() BETWEEN e.start_date AND e.end_date) OR (NOW() < e.start_date))";
        } elseif ($statusFilter === 'upcoming') {
            $whereClauses[] = "(NOW() < e.start_date)";
        } elseif ($statusFilter === 'completed') {
            $whereClauses[] = "(NOW() > e.end_date)";
        }
    }

    if (!empty($categoryFilter)) {
        $whereClauses[] = "e.event_type = :category";
        $queryParams[':category'] = $categoryFilter;
    }

    if (!empty($organizerNameFilter)) {
        $whereClauses[] = "o.name LIKE :organizer_name";
        $queryParams[':organizer_name'] = '%' . $organizerNameFilter . '%';
    }

    if (!empty($dateStartFilter)) {
        $whereClauses[] = "DATE(e.start_date) >= :date_start";
        $queryParams[':date_start'] = $dateStartFilter;
    }

    if (!empty($dateEndFilter)) {
        $whereClauses[] = "DATE(e.start_date) <= :date_end";
        $queryParams[':date_end'] = $dateEndFilter;
    }

    $sqlWhere = "";
    if (!empty($whereClauses)) {
        $sqlWhere = " WHERE " . implode(" AND ", $whereClauses);
    }

    $countSql = "SELECT COUNT(e.event_id) as total " . $baseSql . $sqlWhere;
    try {
        $stmtCount = $conn->prepare($countSql);
        $stmtCount->execute($queryParams);
        $totalItems = (int)$stmtCount->fetchColumn();
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Database Error (count list): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to count events.']);
        $conn = null; exit;
    }

    $totalPages = $itemsPerPage > 0 ? ceil($totalItems / $itemsPerPage) : 0;
    if ($currentPage > $totalPages && $totalItems > 0) {
        $currentPage = $totalPages; 
        $offset = ($currentPage - 1) * $itemsPerPage;
    }

    $selectSql = "
        SELECT
            e.event_id,
            e.title,
            e.location,
            e.event_type,
            e.start_date,
            e.end_date,
            e.thumbnail_url,
            o.name AS organizer_name,
            (SELECT COUNT(er.registration_id) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status = 'confirmed') AS total_attendees,
            CASE
                WHEN NOW() < e.start_date THEN 'upcoming'
                WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'active'
                WHEN NOW() > e.end_date THEN 'completed'
                ELSE 'unknown'
            END AS status
    ";

    $sql = $selectSql . $baseSql . $sqlWhere . " ORDER BY e.start_date DESC LIMIT :limit OFFSET :offset";

    try {
        $stmt = $conn->prepare($sql);
        foreach ($queryParams as $key => $value) {
           $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $eventsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $startItem = $totalItems > 0 ? $offset + 1 : 0;
        $endItem = $totalItems > 0 ? min($offset + $itemsPerPage, $totalItems) : 0;

        echo json_encode([
            'success' => true,
            'events' => $eventsList, 
            'pagination' => [
                'total_items' => $totalItems,
                'current_page' => $currentPage,
                'items_per_page' => $itemsPerPage,
                'total_pages' => $totalPages,
                'start_item' => $startItem,
                'end_item' => $endItem
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Database Error (fetch list): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch event list. ' . $e->getMessage(), 'events' => [], 'pagination' => null]);
    }

    $conn = null;
    exit;
}

http_response_code(405); // Method Not Allowed
echo json_encode(['success' => false, 'message' => 'Invalid request method or endpoint.']);
$conn = null;

?>