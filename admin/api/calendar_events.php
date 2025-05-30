<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/admin/api/calendar_events.php
require_once 'db_connection.php';

// Set headers for API responses
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle different HTTP methods
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle GET requests - Retrieve events
if ($requestMethod === 'GET') {
    try {
        // Prepare the query to get events with their status calculated dynamically
        $sql = "SELECT 
                e.event_id,
                e.title,
                e.location,
                e.event_type,
                e.start_date,
                e.end_date,
                e.max_capacity,
                IFNULL(o.name, 'University Admin') AS organizer_name,
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'confirmed') AS total_attendees,
                CASE 
                    WHEN NOW() < e.start_date THEN 'upcoming'
                    WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'active'
                    WHEN NOW() > e.end_date THEN 'completed'
                END AS status
                FROM events e
                LEFT JOIN organizations o ON e.organizer_id = o.organization_id";
        
        // Check if we need to filter by event_id
        $eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
        if ($eventId) {
            $sql .= " WHERE e.event_id = :event_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':event_id', $eventId);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Handle POST requests - Create or update events
else if ($requestMethod === 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['title', 'location', 'event_type', 'start_date', 'end_date', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Get form data
        $eventId = isset($_POST['event_id']) && !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
        $title = htmlspecialchars(trim($_POST['title']));
        $location = htmlspecialchars(trim($_POST['location']));
        $eventType = htmlspecialchars(trim($_POST['event_type']));
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $status = $_POST['status'];
        
        // Validate dates
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        if ($startDateTime >= $endDateTime) {
            throw new Exception("End date must be after start date");
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // If no event_id, create a new event
        if (!$eventId) {
            $sql = "INSERT INTO events (title, location, event_type, start_date, end_date, organizer_id, created_at, updated_at) 
                    VALUES (:title, :location, :event_type, :start_date, :end_date, 1, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_type', $eventType);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            
            if ($stmt->execute()) {
                $eventId = $conn->lastInsertId();
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'event_id' => $eventId
                ]);
            } else {
                throw new Exception("Failed to create event");
            }
        }
        // Update existing event
        else {
            $sql = "UPDATE events SET 
                    title = :title, 
                    location = :location, 
                    event_type = :event_type, 
                    start_date = :start_date, 
                    end_date = :end_date, 
                    updated_at = NOW() 
                    WHERE event_id = :event_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_type', $eventType);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':event_id', $eventId);
            
            if ($stmt->execute()) {
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Event updated successfully'
                ]);
            } else {
                throw new Exception("Failed to update event");
            }
        }
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// Handle DELETE requests - Delete events
else if ($requestMethod === 'DELETE') {
    try {
        // Get request body as JSON
        $data = json_decode(file_get_contents('php://input'), true);
        $eventId = isset($data['event_id']) ? intval($data['event_id']) : null;
        
        if (!$eventId) {
            throw new Exception("Event ID is required");
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Delete the event
        $sql = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':event_id', $eventId);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } else {
            throw new Exception("Event not found or already deleted");
        }
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// Method not allowed
else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>