<?php
// Start session
session_start();

// Set security headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database connection
require_once '../db/db_connection.php';

// Check if the user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get the requested action
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_summary':
        echo json_encode(getDashboardSummary($_SESSION['user_id'], $_SESSION['user_type']));
        break;
    
    case 'get_events':
        echo json_encode(getUserEvents($_SESSION['user_id'], $_SESSION['user_type']));
        break;
    
    case 'get_notifications':
        echo json_encode(getUserNotifications($_SESSION['user_id'], $_SESSION['user_type']));
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

/**
 * Get dashboard summary for user
 * @param int $userId User ID
 * @param string $userType User type (user, organization, admin)
 * @return array Dashboard summary
 */
function getDashboardSummary($userId, $userType) {
    global $conn;
    
    try {
        $summary = [
            'success' => true,
            'user_info' => [],
            'stats' => []
        ];
        
        // Get user info
        $userInfo = getUserInfo($userId, $userType);
        if ($userInfo) {
            $summary['user_info'] = $userInfo;
        }
        
        // Get stats based on user type
        switch ($userType) {
            case 'user':
                $summary['stats'] = getUserStats($userId);
                break;
            
            case 'organization':
                $summary['stats'] = getOrganizationStats($userId);
                break;
            
            case 'admin':
                $summary['stats'] = getAdminStats();
                break;
        }
        
        return $summary;
    } catch (PDOException $e) {
        error_log("Dashboard summary error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to get dashboard summary'
        ];
    }
}

/**
 * Get user information
 * @param int $userId User ID
 * @param string $userType User type
 * @return array User information
 */
function getUserInfo($userId, $userType) {
    global $conn;
    
    try {
        $tableName = '';
        $idField = '';
        $fields = '';
        
        switch ($userType) {
            case 'user':
                $tableName = 'users';
                $idField = 'user_id';
                $fields = "user_id, email, first_name, last_name, profile_picture, 
                           CONCAT(first_name, ' ', last_name) AS name";
                break;
            
            case 'organization':
                $tableName = 'organizations';
                $idField = 'organization_id';
                $fields = "organization_id, name, email, profile_picture, website";
                break;
            
            case 'admin':
                $tableName = 'admins';
                $idField = 'admin_id';
                $fields = "admin_id, username AS name, email, profile_picture";
                break;
            
            default:
                return null;
        }
        
        $stmt = $conn->prepare("SELECT $fields FROM $tableName WHERE $idField = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    } catch (PDOException $e) {
        error_log("Get user info error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get stats for student/user dashboard
 * @param int $userId User ID
 * @return array User stats
 */
function getUserStats($userId) {
    global $conn;
    
    try {
        $stats = [];
        
        // Get registration count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM event_registrations WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['registrations'] = $result['total'] ?? 0;
        
        // Get upcoming events count
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.event_id 
            WHERE er.user_id = :user_id AND e.date >= CURDATE()
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['upcoming_events'] = $result['total'] ?? 0;
        
        // Get past events count
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.event_id 
            WHERE er.user_id = :user_id AND e.date < CURDATE()
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['past_events'] = $result['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Get user stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get stats for organization dashboard
 * @param int $orgId Organization ID
 * @return array Organization stats
 */
function getOrganizationStats($orgId) {
    global $conn;
    
    try {
        $stats = [];
        
        // Get created events count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM events WHERE organization_id = :org_id");
        $stmt->bindParam(':org_id', $orgId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['created_events'] = $result['total'] ?? 0;
        
        // Get upcoming events count
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM events 
            WHERE organization_id = :org_id AND date >= CURDATE()
        ");
        $stmt->bindParam(':org_id', $orgId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['upcoming_events'] = $result['total'] ?? 0;
        
        // Get registration count for all events
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.event_id 
            WHERE e.organization_id = :org_id
        ");
        $stmt->bindParam(':org_id', $orgId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_registrations'] = $result['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Get organization stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get stats for admin dashboard
 * @return array Admin stats
 */
function getAdminStats() {
    global $conn;
    
    try {
        $stats = [];
        
        // Get total users count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_users'] = $result['total'] ?? 0;
        
        // Get total organizations count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM organizations");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_organizations'] = $result['total'] ?? 0;
        
        // Get total events count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM events");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_events'] = $result['total'] ?? 0;
        
        // Get total registrations count
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM event_registrations");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_registrations'] = $result['total'] ?? 0;
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Get admin stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's events
 * @param int $userId User ID
 * @param string $userType User type
 * @return array User's events
 */
function getUserEvents($userId, $userType) {
    global $conn;
    
    try {
        $events = [];
        
        switch ($userType) {
            case 'user':
                // Get events the user is registered for
                $stmt = $conn->prepare("
                    SELECT e.*, er.registration_date 
                    FROM events e 
                    JOIN event_registrations er ON e.event_id = er.event_id 
                    WHERE er.user_id = :user_id 
                    ORDER BY e.date ASC
                ");
                $stmt->bindParam(':user_id', $userId);
                break;
            
            case 'organization':
                // Get events created by the organization
                $stmt = $conn->prepare("
                    SELECT e.* 
                    FROM events e 
                    WHERE e.organization_id = :org_id 
                    ORDER BY e.date ASC
                ");
                $stmt->bindParam(':org_id', $userId);
                break;
            
            case 'admin':
                // Get all events
                $stmt = $conn->prepare("
                    SELECT e.* 
                    FROM events e 
                    ORDER BY e.date ASC
                ");
                break;
            
            default:
                return [
                    'success' => false,
                    'message' => 'Invalid user type'
                ];
        }
        
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'events' => $events
        ];
    } catch (PDOException $e) {
        error_log("Get user events error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to get events'
        ];
    }
}

/**
 * Get user notifications
 * @param int $userId User ID
 * @param string $userType User type
 * @return array User notifications
 */
function getUserNotifications($userId, $userType) {
    global $conn;
    
    try {
        $notificationsTable = '';
        
        switch ($userType) {
            case 'user':
                $notificationsTable = 'user_notifications';
                $idField = 'user_id';
                break;
            
            case 'organization':
                $notificationsTable = 'organization_notifications';
                $idField = 'organization_id';
                break;
            
            case 'admin':
                $notificationsTable = 'admin_notifications';
                $idField = 'admin_id';
                break;
            
            default:
                return [
                    'success' => false,
                    'message' => 'Invalid user type'
                ];
        }
        
        // Check if table exists
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = :table_name
            ");
            $stmt->bindParam(':table_name', $notificationsTable);
            $stmt->execute();
            $tableExists = ($stmt->fetchColumn() > 0);
            
            if (!$tableExists) {
                // Return empty notifications if table doesn't exist
                return [
                    'success' => true,
                    'notifications' => []
                ];
            }
        } catch (PDOException $e) {
            error_log("Table check error: " . $e->getMessage());
            // Continue with query anyway
        }
        
        $stmt = $conn->prepare("
            SELECT * 
            FROM $notificationsTable 
            WHERE $idField = :id 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'notifications' => $notifications
        ];
    } catch (PDOException $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to get notifications'
        ];
    }
}