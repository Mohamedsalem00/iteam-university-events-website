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
        // Get organization data
        getOrganizationData($conn, $organizationId);
        break;
    case 'PUT':
        // Update organization data
        updateOrganizationData($conn, $organizationId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

// Function to retrieve organization data
function getOrganizationData($conn, $organizationId) {
    try {
        // Prepare query to fetch organization data
        $stmt = $conn->prepare("
            SELECT o.*, 
                  (SELECT COUNT(*) FROM events e WHERE e.organizer_id = o.organization_id) as total_events,
                  (SELECT COUNT(*) FROM event_registrations er 
                   JOIN events e ON er.event_id = e.event_id 
                   WHERE e.organizer_id = o.organization_id) as total_registrations
            FROM organizations o
            WHERE o.organization_id = :organization_id
        ");
        
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        $organization = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($organization) {
            // Remove sensitive information before sending
            unset($organization['password']);
            
            // Add additional profile info from session if available
            $organization['profile_picture'] = $_SESSION['profile_picture'] ?? null;
            
            echo json_encode(['success' => true, 'organization' => $organization]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Organization not found']);
        }
    } catch (PDOException $e) {
        error_log("Database error in getOrganizationData: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to update organization data
function updateOrganizationData($conn, $organizationId) {
    try {
        // Get JSON data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check required fields
        if (!isset($data['name']) || empty($data['name']) || 
            !isset($data['email']) || empty($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
            exit();
        }
        
        // Start with basic fields that don't need validation
        $updateFields = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'website' => $data['website'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'] ?? '',
            'industry' => $data['industry'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Check if email already exists (but not for this organization)
        if (isset($data['email'])) {
            $stmt = $conn->prepare("SELECT organization_id FROM organizations WHERE email = :email AND organization_id != :organization_id");
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':organization_id', $organizationId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already in use by another organization']);
                exit();
            }
            
            $updateFields['email'] = $data['email'];
        }
        
        // Build UPDATE query
        $sql = "UPDATE organizations SET ";
        $updates = [];
        $params = [':organization_id' => $organizationId];
        
        foreach ($updateFields as $field => $value) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        
        $sql .= implode(', ', $updates);
        $sql .= " WHERE organization_id = :organization_id";
        
        // Execute update
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Update session data
        $_SESSION['account_name'] = $data['name'];
        $_SESSION['email'] = $data['email'];
        
        // If profile picture was updated, update session
        if (isset($data['profile_picture'])) {
            $_SESSION['profile_picture'] = $data['profile_picture'];
        }
        
        // Return updated organization data
        getOrganizationData($conn, $organizationId);
    } catch (PDOException $e) {
        error_log("Database error in updateOrganizationData: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
    }
}

// Function to update organization password - can be added as a separate endpoint
function updateOrganizationPassword($conn, $organizationId, $currentPassword, $newPassword) {
    try {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM organizations WHERE organization_id = :organization_id");
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        $organization = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$organization || !password_verify($currentPassword, $organization['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE organizations SET password = :password, updated_at = NOW() WHERE organization_id = :organization_id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':organization_id', $organizationId);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Password updated successfully'];
    } catch (PDOException $e) {
        error_log("Database error in updateOrganizationPassword: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()];
    }
}
?>