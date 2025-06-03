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

switch ($method) {
    case 'GET':
        // Get events (with pagination and filtering)
        getEvents($conn, $organizationId);
        break;
    case 'POST':
        // Create a new event
        createEvent($conn, $organizationId);
        break;
    case 'PUT':
        // Update an existing event
        updateEvent($conn, $organizationId);
        break;
    case 'DELETE':
        // Delete an event
        deleteEvent($conn, $organizationId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

// Function to retrieve events with pagination and filtering
function getEvents($conn, $organizationId) {
    try {
        // Check if we're fetching a single event by ID
        if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
            $eventId = intval($_GET['event_id']);
            
            // Prepare query to fetch single event
            $stmt = $conn->prepare("
                SELECT * FROM events 
                WHERE event_id = :event_id AND organizer_id = :organizer_id
            ");
            
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':organizer_id', $organizationId);
            $stmt->execute();
            
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                echo json_encode(['success' => true, 'event' => $event]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }
            return;
        }
        
        // Get query parameters for listing events
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;
        
        // Base SQL for counting total events
        $countQuery = "SELECT COUNT(*) as total FROM events WHERE organizer_id = :organizer_id";
        
        // Base SQL for retrieving events
        $query = "SELECT e.*, 
                  (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id) as registered_count 
                  FROM events e 
                  WHERE e.organizer_id = :organizer_id";
        
        // Apply filters
        $params = [':organizer_id' => $organizationId];
        
        if ($filter !== 'all') {
            switch ($filter) {
                case 'upcoming':
                    $query .= " AND e.start_date > NOW()";
                    $countQuery .= " AND start_date > NOW()";
                    break;
                case 'past':
                    $query .= " AND e.end_date < NOW()";
                    $countQuery .= " AND end_date < NOW()";
                    break;
                case 'workshop':
                case 'conference':
                case 'webinar':
                case 'fair':
                    $query .= " AND e.event_type = :event_type";
                    $countQuery .= " AND event_type = :event_type";
                    $params[':event_type'] = $filter;
                    break;
            }
        }
        
        // Apply search if provided
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $query .= " AND (e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
            $countQuery .= " AND (title LIKE :search OR description LIKE :search OR location LIKE :search)";
            $params[':search'] = $searchParam;
        }
        
        // Order by start date, newest first
        $query .= " ORDER BY e.start_date DESC LIMIT :limit OFFSET :offset";
        
        // Execute count query to get total number of events
        $stmt = $conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Execute main query
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'events' => $events, 
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getEvents: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to create a new event
function createEvent($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'event_type', 'start_date', 'end_date', 'location', 'max_capacity'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate dates
        $startDate = new DateTime($data['start_date']);
        $endDate = new DateTime($data['end_date']);
        $now = new DateTime();
        
        if ($startDate < $now) {
            echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past']);
            exit();
        }
        
        if ($endDate <= $startDate) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            exit();
        }
        
        // Handle optional fields with default values
        $thumbnail_url = isset($data['thumbnail_url']) && !empty($data['thumbnail_url']) ? 
                         $data['thumbnail_url'] : NULL;
        $requires_approval = isset($data['requires_approval']) ? 
                            (bool)$data['requires_approval'] : false;
        
        // Insert the new event
        $stmt = $conn->prepare("
            INSERT INTO events (
                title, description, start_date, end_date, location, event_type, 
                max_capacity, organizer_id, thumbnail_url, requires_approval, created_at, updated_at
            ) VALUES (
                :title, :description, :start_date, :end_date, :location, :event_type,
                :max_capacity, :organizer_id, :thumbnail_url, :requires_approval, NOW(), NOW()
            )
        ");
        
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':event_type', $data['event_type']);
        $stmt->bindParam(':max_capacity', $data['max_capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':organizer_id', $organizationId, PDO::PARAM_INT);
        $stmt->bindParam(':thumbnail_url', $thumbnail_url);
        $stmt->bindParam(':requires_approval', $requires_approval, PDO::PARAM_BOOL);
        
        $stmt->execute();
        $eventId = $conn->lastInsertId();
        
        echo json_encode(['success' => true, 'message' => 'Event created successfully', 'event_id' => $eventId]);
        
    } catch (PDOException $e) {
        error_log("Database error in createEvent: " . $e->getMessage());
        // Return the actual error message to help with debugging
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to update an existing event
function updateEvent($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if event ID is provided
        if (!isset($data['event_id']) || empty($data['event_id'])) {
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit();
        }
        
        // Verify that the event belongs to this organization
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = :event_id AND organizer_id = :organizer_id");
        $stmt->bindParam(':event_id', $data['event_id']);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Event not found or you do not have permission to update it']);
            exit();
        }
        
        // Validate required fields
        $requiredFields = ['title', 'description', 'event_type', 'start_date', 'end_date', 'location', 'max_capacity'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit();
            }
        }
        
        // Validate dates
        $startDate = new DateTime($data['start_date']);
        $endDate = new DateTime($data['end_date']);
        $now = new DateTime();
        
        if ($endDate <= $startDate) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            exit();
        }
        
        // Handle optional fields with default values
        $thumbnail_url = isset($data['thumbnail_url']) && !empty($data['thumbnail_url']) ? 
                         $data['thumbnail_url'] : NULL;
        $requires_approval = isset($data['requires_approval']) ? 
                            (bool)$data['requires_approval'] : false;
        
        // Update the event
        $stmt = $conn->prepare("
            UPDATE events SET
                title = :title,
                description = :description,
                start_date = :start_date,
                end_date = :end_date,
                location = :location,
                event_type = :event_type,
                max_capacity = :max_capacity,
                thumbnail_url = :thumbnail_url,
                requires_approval = :requires_approval,
                updated_at = NOW()
            WHERE event_id = :event_id AND organizer_id = :organizer_id
        ");
        
        $stmt->bindParam(':event_id', $data['event_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':start_date', $data['start_date']);
        $stmt->bindParam(':end_date', $data['end_date']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':event_type', $data['event_type']);
        $stmt->bindParam(':max_capacity', $data['max_capacity'], PDO::PARAM_INT);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->bindParam(':thumbnail_url', $thumbnail_url);
        $stmt->bindParam(':requires_approval', $requires_approval, PDO::PARAM_BOOL);
        
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in updateEvent: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to delete an event
function deleteEvent($conn, $organizationId) {
    try {
        // Get event ID from the request
        $eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        if ($eventId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
            exit();
        }
        
        // Verify that the event belongs to this organization
        $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = :event_id AND organizer_id = :organizer_id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Event not found or you do not have permission to delete it']);
            exit();
        }
        
        // Begin transaction for safe deletion
        $conn->beginTransaction();
        
        // Delete related records first
        // 1. Delete event registrations
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        
        // 2. Delete event gallery images
        $stmt = $conn->prepare("DELETE FROM event_gallery WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        
        // 3. Delete notifications related to this event
        $stmt = $conn->prepare("DELETE FROM notifications WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        
        // Finally, delete the event itself
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = :event_id AND organizer_id = :organizer_id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Event and related records deleted successfully']);
        
    } catch (PDOException $e) {
        // Roll back the transaction if something failed
        $conn->rollBack();
        error_log("Database error in deleteEvent: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}
?>