<?php
// Start session
session_start();

// Include database connection
require_once '../db_connection.php';

// Set response header
header('Content-Type: application/json');

// Check if job ID is provided
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Job ID is required'
    ]);
    exit;
}

$jobId = intval($_GET['job_id']);
$orgId = isset($_GET['org_id']) ? intval($_GET['org_id']) : 0;

try {
    // Query to get similar job offers
    // We look for jobs from the same organization or with similar titles
    $query = "SELECT jo.*, o.name as organization_name, o.profile_picture as organization_logo
              FROM job_offers jo 
              INNER JOIN organizations o ON jo.organization_id = o.organization_id
              WHERE jo.job_offer_id != :job_id
              AND jo.status = 'active'
              AND (jo.organization_id = :org_id OR jo.title LIKE 
                  (SELECT CONCAT('%', SUBSTRING(title, 1, 5), '%') FROM job_offers WHERE job_offer_id = :job_id2))
              ORDER BY 
                CASE WHEN jo.organization_id = :org_id3 THEN 0 ELSE 1 END,
                jo.posted_date DESC
              LIMIT 4";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
    $stmt->bindParam(':job_id2', $jobId, PDO::PARAM_INT);
    $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
    $stmt->bindParam(':org_id3', $orgId, PDO::PARAM_INT);
    $stmt->execute();
    
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs
    ]);
    
} catch (PDOException $e) {
    // Log error and return error message
    error_log('Database error in similar-jobs.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load similar jobs. Please try again later.'
    ]);
}
?>