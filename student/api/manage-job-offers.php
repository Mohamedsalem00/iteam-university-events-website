<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Set headers
header('Content-Type: application/json');

// Check if organization is logged in
if (!isset($_SESSION['student_id']) || $_SESSION['account_type'] !== 'organization') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. This API is only for organization accounts.'
    ]);
    exit;
}

$organizationId = $_SESSION['student_id'];

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get organization's job offers
        try {
            $query = "SELECT * FROM job_offers 
                      WHERE organization_id = :organization_id 
                      ORDER BY posted_date DESC";
                      
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
            $stmt->execute();
            $jobOffers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'job_offers' => $jobOffers
            ]);
        } catch (PDOException $e) {
            error_log("Job offers API error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error loading job offers. Please try again later.'
            ]);
        }
        break;
        
    case 'POST':
        // Create new job offer
        try {
            // Get JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !isset($data['title']) || !isset($data['description'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields.'
                ]);
                exit;
            }
            
            // Insert new job offer
            $query = "INSERT INTO job_offers (organization_id, title, description) 
                      VALUES (:organization_id, :title, :description)";
                      
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Job offer created successfully.',
                'job_id' => $conn->lastInsertId()
            ]);
        } catch (PDOException $e) {
            error_log("Job creation API error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error creating job offer. Please try again later.'
            ]);
        }
        break;
        
    case 'PUT':
        // Update job offer
        try {
            // Get JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !isset($data['job_id']) || !isset($data['title']) || !isset($data['description'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields.'
                ]);
                exit;
            }
            
            // Verify that this job offer belongs to this organization
            $checkQuery = "SELECT organization_id FROM job_offers 
                          WHERE job_offer_id = :job_id";
                          
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':job_id', $data['job_id'], PDO::PARAM_INT);
            $checkStmt->execute();
            $jobData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$jobData || $jobData['organization_id'] != $organizationId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to edit this job offer.'
                ]);
                exit;
            }
            
            // Update job offer
            $updateQuery = "UPDATE job_offers 
                           SET title = :title, description = :description, updated_at = NOW() 
                           WHERE job_offer_id = :job_id";
                           
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $updateStmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $updateStmt->bindParam(':job_id', $data['job_id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Job offer updated successfully.'
            ]);
        } catch (PDOException $e) {
            error_log("Job update API error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error updating job offer. Please try again later.'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete job offer
        try {
            // Get job ID from query parameter
            if (!isset($_GET['job_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing job ID.'
                ]);
                exit;
            }
            
            $jobId = $_GET['job_id'];
            
            // Verify that this job offer belongs to this organization
            $checkQuery = "SELECT organization_id FROM job_offers 
                          WHERE job_offer_id = :job_id";
                          
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
            $checkStmt->execute();
            $jobData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$jobData || $jobData['organization_id'] != $organizationId) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to delete this job offer.'
                ]);
                exit;
            }
            
            // Delete job offer
            $deleteQuery = "DELETE FROM job_offers WHERE job_offer_id = :job_id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Job offer deleted successfully.'
            ]);
        } catch (PDOException $e) {
            error_log("Job deletion API error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting job offer. Please try again later.'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>