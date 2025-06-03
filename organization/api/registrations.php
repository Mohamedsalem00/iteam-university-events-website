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
        // Get registrations with filtering and pagination
        getRegistrations($conn, $organizationId);
        break;
    case 'POST':
        // Handle registration actions: approve, reject, etc.
        handleRegistrationAction($conn, $organizationId);
        break;
    case 'PUT':
        // Update registration details
        updateRegistration($conn, $organizationId);
        break;
    case 'DELETE':
        // Delete a registration
        deleteRegistration($conn, $organizationId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

// Function to get registrations with filtering and pagination
function getRegistrations($conn, $organizationId) {
    try {
        // Check if we're fetching a single registration by ID
        if (isset($_GET['registration_id']) && !empty($_GET['registration_id'])) {
            $registrationId = intval($_GET['registration_id']);
            
            $stmt = $conn->prepare("
                SELECT er.*, e.title as event_title, e.event_type, e.start_date, e.end_date, 
                       s.first_name, s.last_name, s.email, s.student_id
                FROM event_registrations er
                JOIN events e ON er.event_id = e.event_id
                JOIN students s ON er.student_id = s.student_id
                WHERE er.registration_id = :registration_id 
                AND e.organizer_id = :organizer_id
            ");
            
            $stmt->bindParam(':registration_id', $registrationId);
            $stmt->bindParam(':organizer_id', $organizationId);
            $stmt->execute();
            
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                echo json_encode(['success' => true, 'registration' => $registration]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration not found']);
            }
            return;
        }
        
        // Get query parameters
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;
        
        // Base SQL for counting total registrations
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN students s ON er.student_id = s.student_id
            WHERE e.organizer_id = :organizer_id
        ";
        
        // Base SQL for retrieving registrations
        $query = "
            SELECT er.*, e.title as event_title, e.event_type, e.start_date, e.end_date,
                   s.first_name, s.last_name, s.email, s.profile_picture
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN students s ON er.student_id = s.student_id
            WHERE e.organizer_id = :organizer_id
        ";
        
        // Apply filters
        $params = [':organizer_id' => $organizationId];
        
        if ($eventId) {
            $query .= " AND e.event_id = :event_id";
            $countQuery .= " AND e.event_id = :event_id";
            $params[':event_id'] = $eventId;
        }
        
        if ($status) {
            $query .= " AND er.status = :status";
            $countQuery .= " AND er.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $query .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.email LIKE :search OR e.title LIKE :search)";
            $countQuery .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.email LIKE :search OR e.title LIKE :search)";
            $params[':search'] = $searchParam;
        }
        
        // Order by registration date, newest first
        $query .= " ORDER BY er.registration_date DESC LIMIT :limit OFFSET :offset";
        
        // Execute count query to get total number of registrations
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
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available events for filter dropdown
        $eventsQuery = $conn->prepare("
            SELECT event_id, title 
            FROM events 
            WHERE organizer_id = :organizer_id 
            ORDER BY start_date DESC
        ");
        $eventsQuery->bindParam(':organizer_id', $organizationId);
        $eventsQuery->execute();
        $events = $eventsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'registrations' => $registrations, 
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'filters' => [
                'event_id' => $eventId,
                'status' => $status,
                'search' => $search
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in getRegistrations: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to handle registration actions (approve, reject, etc.)
function handleRegistrationAction($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check required fields
        if (!isset($data['registration_id']) || !isset($data['action'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        $registrationId = $data['registration_id'];
        $action = $data['action'];
        
        // Verify the registration belongs to an event managed by this organization
        $stmt = $conn->prepare("
            SELECT er.registration_id, er.event_id, er.student_id, er.status, e.title as event_title, e.organizer_id,
                   s.first_name, s.last_name, s.email
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN students s ON er.student_id = s.student_id
            WHERE er.registration_id = :registration_id AND e.organizer_id = :organizer_id
        ");
        $stmt->bindParam(':registration_id', $registrationId);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Registration not found or you do not have permission']);
            exit();
        }
        
        // Process action
        $newStatus = '';
        $notificationMessage = '';
        
        switch ($action) {
            case 'approve':
                $newStatus = 'approved';
                $notificationMessage = "Your registration for {$registration['event_title']} has been approved.";
                break;
            case 'reject':
                $newStatus = 'rejected';
                $notificationMessage = "Your registration for {$registration['event_title']} has been rejected.";
                break;
            case 'mark_attended':
                $newStatus = 'attended';
                $notificationMessage = "Thank you for attending {$registration['event_title']}. Hope you enjoyed it!";
                break;
            case 'mark_absent':
                $newStatus = 'absent';
                $notificationMessage = "You were marked as absent for {$registration['event_title']}.";
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
        
        // Update registration status
        $stmt = $conn->prepare("
            UPDATE event_registrations 
            SET status = :status, updated_at = NOW()
            WHERE registration_id = :registration_id
        ");
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':registration_id', $registrationId);
        $stmt->execute();
        
        // Create notification for the student
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (account_id, account_type, event_id, message, send_date, is_read, created_at) 
            VALUES 
            (:account_id, 'student', :event_id, :message, NOW(), 0, NOW())
        ");
        $stmt->bindParam(':account_id', $registration['student_id']);
        $stmt->bindParam(':event_id', $registration['event_id']);
        $stmt->bindParam(':message', $notificationMessage);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => "Registration successfully marked as $newStatus",
            'registration_id' => $registrationId,
            'new_status' => $newStatus
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in handleRegistrationAction: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to update registration details
function updateRegistration($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check required fields
        if (!isset($data['registration_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing registration ID']);
            exit();
        }
        
        $registrationId = $data['registration_id'];
        
        // Verify the registration belongs to an event managed by this organization
        $stmt = $conn->prepare("
            SELECT er.*, e.organizer_id
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.registration_id = :registration_id AND e.organizer_id = :organizer_id
        ");
        $stmt->bindParam(':registration_id', $registrationId);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Registration not found or you do not have permission']);
            exit();
        }
        
        // Fields that can be updated
        $updateFields = [];
        $params = [':registration_id' => $registrationId];
        
        // Only certain fields can be updated
        if (isset($data['status'])) {
            $updateFields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['notes'])) {
            $updateFields[] = "notes = :notes";
            $params[':notes'] = $data['notes'];
        }
        
        if (isset($data['check_in_time']) && $data['check_in_time']) {
            $updateFields[] = "check_in_time = :check_in_time";
            $params[':check_in_time'] = $data['check_in_time'];
        }
        
        if (count($updateFields) === 0) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit();
        }
        
        // Build and execute the update query
        $updateQuery = "UPDATE event_registrations SET " . implode(", ", $updateFields) . ", updated_at = NOW() WHERE registration_id = :registration_id";
        $stmt = $conn->prepare($updateQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Registration updated successfully']);
        
    } catch (PDOException $e) {
        error_log("Database error in updateRegistration: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to delete a registration
function deleteRegistration($conn, $organizationId) {
    try {
        // Get registration ID from the request
        $registrationId = isset($_GET['registration_id']) ? intval($_GET['registration_id']) : 0;
        
        if ($registrationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid registration ID']);
            exit();
        }
        
        // Verify the registration belongs to an event managed by this organization
        $stmt = $conn->prepare("
            SELECT er.registration_id, e.title as event_title, s.first_name, s.last_name, s.email, er.student_id, er.event_id
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN students s ON er.student_id = s.student_id
            WHERE er.registration_id = :registration_id AND e.organizer_id = :organizer_id
        ");
        $stmt->bindParam(':registration_id', $registrationId);
        $stmt->bindParam(':organizer_id', $organizationId);
        $stmt->execute();
        
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Registration not found or you do not have permission']);
            exit();
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete the registration
        $stmt = $conn->prepare("DELETE FROM event_registrations WHERE registration_id = :registration_id");
        $stmt->bindParam(':registration_id', $registrationId);
        $stmt->execute();
        
        // Create notification for the student
        $message = "Your registration for {$registration['event_title']} has been cancelled by the organizer.";
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (account_id, account_type, event_id, message, send_date, is_read, created_at) 
            VALUES 
            (:account_id, 'student', :event_id, :message, NOW(), 0, NOW())
        ");
        $stmt->bindParam(':account_id', $registration['student_id']);
        $stmt->bindParam(':event_id', $registration['event_id']);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
        
    } catch (PDOException $e) {
        // Rollback transaction if something went wrong
        $conn->rollBack();
        error_log("Database error in deleteRegistration: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}
?>