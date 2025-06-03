<?php

// Start session
session_start();

// Include database connection - CORRECT PATH NEEDED
require_once 'db_connection.php'; // Changed from 'db_connection.php' to '../db_connection.php'

// Set response header
header('Content-Type: application/json');

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Job ID is required'
    ]);
    exit;
}

$jobId = intval($_GET['id']);

try {
    // Query to get job offer details
    $query = "SELECT jo.*, o.name as organization_name, o.profile_picture as organization_logo, o.email as organization_email
              FROM job_offers jo 
              INNER JOIN organizations o ON jo.organization_id = o.organization_id
              WHERE jo.job_offer_id = :job_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        echo json_encode([
            'success' => false,
            'message' => 'Job not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job
    ]);
    
} catch (PDOException $e) {
    // Log error and return error message
    error_log('Database error in job-detail.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load job details. Please try again later.'
    ]);
}
?>