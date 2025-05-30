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
$organization_id = $_POST['organization_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$organization_id || !in_array($new_status, ['active', 'inactive'])) {
    $response['message'] = 'Invalid organization ID or status';
    echo json_encode($response);
    exit;
}

try {
    // Update organization status
    $stmt = $conn->prepare("UPDATE organizations SET status = :status WHERE organization_id = :organization_id");
    $result = $stmt->execute([
        'status' => $new_status,
        'organization_id' => $organization_id
    ]);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Organization status updated successfully';
    } else {
        $response['message'] = 'Failed to update organization status';
    }
} catch (PDOException $e) {
    error_log("Error updating organization status: " . $e->getMessage());
    $response['message'] = 'Database error occurred';
}

echo json_encode($response);
$conn = null;
?> 