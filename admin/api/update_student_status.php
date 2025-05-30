<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get and validate input
$student_id = $_POST['student_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$student_id || !in_array($new_status, ['active', 'inactive'])) {
    $response['message'] = 'Invalid student ID or status';
    echo json_encode($response);
    exit;
}

try {
    // Update student status
    $stmt = $conn->prepare("UPDATE students SET status = :status WHERE student_id = :student_id");
    $result = $stmt->execute([
        'status' => $new_status,
        'student_id' => $student_id
    ]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Student status updated successfully';
    } else {
        $response['message'] = 'Failed to update student status';
    }
} catch (PDOException $e) {
    error_log("Error updating student status: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

echo json_encode($response);
$conn = null;
?> 