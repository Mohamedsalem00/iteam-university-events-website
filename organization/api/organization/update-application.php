<?php
// Start session
session_start();

// Include database connection
require_once '../../db_connection.php';

// Set headers
header('Content-Type: application/json');

// Check if user is logged in as organization
if (!isset($_SESSION['organization_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as an organization to perform this action.'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['application_id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields.'
    ]);
    exit;
}

$applicationId = intval($data['application_id']);
$status = $data['status'];
$notes = isset($data['notes']) ? trim($data['notes']) : null;
$organizationId = $_SESSION['organization_id'];

// Validate status
$validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'accepted'];
if (!in_array($status, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value.'
    ]);
    exit;
}

try {
    // Verify the application belongs to a job posting from this organization
    $verifyQuery = "SELECT ja.application_id 
                    FROM job_applications ja
                    INNER JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
                    WHERE ja.application_id = :application_id 
                    AND jo.organization_id = :organization_id";
    
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $verifyStmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
    $verifyStmt->execute();
    
    if ($verifyStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to update this application.'
        ]);
        exit;
    }
    
    // Update the application
    $updateQuery = "UPDATE job_applications
                    SET status = :status, notes = :notes, updated_at = NOW()
                    WHERE application_id = :application_id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
    $updateStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
    $updateStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Application status updated successfully.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);
    error_log("Error updating application: " . $e->getMessage());
}
?>