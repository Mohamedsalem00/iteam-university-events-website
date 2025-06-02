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

// Handle different actions based on request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get applications for jobs posted by this organization
        try {
            // Filter by job if specified
            $jobFilter = '';
            $params = [':organization_id' => $organizationId];
            
            if (isset($_GET['job_id'])) {
                $jobFilter = "AND jo.job_offer_id = :job_id";
                $params[':job_id'] = $_GET['job_id'];
            }
            
            // Filter by status if specified
            $statusFilter = '';
            if (isset($_GET['status']) && $_GET['status'] !== 'all') {
                $statusFilter = "AND ja.status = :status";
                $params[':status'] = $_GET['status'];
            }
            
            $query = "SELECT ja.*, 
                      jo.title AS job_title,
                      s.first_name, s.last_name, s.email, s.profile_picture,
                      jo.job_offer_id, jo.title
                      FROM job_applications ja
                      INNER JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
                      INNER JOIN students s ON ja.student_id = s.student_id
                      WHERE jo.organization_id = :organization_id
                      $jobFilter
                      $statusFilter
                      ORDER BY ja.application_date DESC";
                      
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get job offers for this organization (for filtering)
            $jobsQuery = "SELECT job_offer_id, title FROM job_offers 
                         WHERE organization_id = :organization_id 
                         ORDER BY posted_date DESC";
            $jobsStmt = $conn->prepare($jobsQuery);
            $jobsStmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
            $jobsStmt->execute();
            $jobs = $jobsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count applications by status
            $statsQuery = "SELECT ja.status, COUNT(*) AS count 
                          FROM job_applications ja
                          INNER JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
                          WHERE jo.organization_id = :organization_id
                          GROUP BY ja.status";
            $statsStmt = $conn->prepare($statsQuery);
            $statsStmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
            $statsStmt->execute();
            $statsRows = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format stats
            $stats = [
                'total' => 0,
                'pending' => 0,
                'reviewed' => 0,
                'shortlisted' => 0,
                'accepted' => 0,
                'rejected' => 0
            ];
            
            foreach ($statsRows as $row) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            echo json_encode([
                'success' => true,
                'applications' => $applications,
                'jobs' => $jobs,
                'stats' => $stats
            ]);
        } catch (PDOException $e) {
            error_log("Applications API error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error loading applications. Please try again later.'
            ]);
        }
        break;
        
    case 'PUT':
        // Update application status
        try {
            // Get JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!$data || !isset($data['application_id']) || !isset($data['status'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required fields.'
                ]);
                exit;
            }
            
            // Verify the application is for a job posted by this organization
            $checkQuery = "SELECT ja.application_id
                          FROM job_applications ja
                          INNER JOIN job_offers jo ON ja.job_offer_id = jo.job_offer_id
                          WHERE ja.application_id = :application_id
                          AND jo.organization_id = :organization_id";
                          
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':application_id', $data['application_id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':organization_id', $organizationId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if (!$checkStmt->fetch()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to update this application.'
                ]);
                exit;
            }
            
            // Update application status
            $updateQuery = "UPDATE job_applications 
                           SET status = :status,
                               notes = CASE WHEN :notes IS NULL THEN notes ELSE :notes END
                           WHERE application_id = :application_id";
                           
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $updateStmt->bindParam(':notes', $data['notes'], PDO::PARAM_STR);
            $updateStmt->bindParam(':application_id', $data['application_id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Application status updated successfully.'
            ]);
        } catch (PDOException $e) {
            error_log("Application status update error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Error updating application status. Please try again later.'
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