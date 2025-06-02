<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Set headers
header('Content-Type: application/json');

// Get job offers
try {
    $query = "SELECT j.*, o.name AS organization_name, o.profile_picture AS organization_logo
              FROM job_offers j
              JOIN organizations o ON j.organization_id = o.organization_id
              ORDER BY j.posted_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jobOffers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'job_offers' => $jobOffers
    ]);
} catch (PDOException $e) {
    // Log error and return error response
    error_log("Job offers API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error loading job offers. Please try again later.'
    ]);
}
?>