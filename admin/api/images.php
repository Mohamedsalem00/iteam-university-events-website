<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'images' => []];

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['user_type'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

try {
    // Get filter parameters
    $event_id = isset($_GET['event_id']) ? filter_var($_GET['event_id'], FILTER_VALIDATE_INT) : null;
    $status = isset($_GET['status']) ? filter_var($_GET['status'], FILTER_SANITIZE_STRING) : null;

    // Build query
    $query = "SELECT i.*, e.title as event_title, u.full_name as submitted_by 
              FROM images i 
              LEFT JOIN events e ON i.event_id = e.id 
              LEFT JOIN students u ON i.student_id = u.id 
              WHERE 1=1";
    $params = [];
    $types = "";

    if ($event_id) {
        $query .= " AND i.event_id = ?";
        $params[] = $event_id;
        $types .= "i";
    }

    if ($status) {
        $query .= " AND i.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $query .= " ORDER BY i.created_at DESC";

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Process results
    while ($row = $result->fetch_assoc()) {
        $row['url'] = '/iteam-university-website/admin/uploads/images/' . $row['filename'];
        $row['thumbnail_url'] = '/iteam-university-website/admin/uploads/thumbnails/' . $row['thumbnail_filename'];
        $response['images'][] = $row;
    }

    $response['success'] = true;
    $response['message'] = 'Images fetched successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response); 