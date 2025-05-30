<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

// Check if student is logged in
$loggedInStudentId = $_SESSION['student_id'] ?? null;
$loggedInStudentType = $_SESSION['user_type'] ?? null;

if (!$loggedInStudentId || !$loggedInStudentType) {
    $response['message'] = 'Authentication required';
    echo json_encode($response);
    exit;
}

// Get the request body
$requestBody = json_decode(file_get_contents('php://input'), true);
$notificationId = $requestBody['notification_id'] ?? null;

if (!$notificationId) {
    $response['message'] = 'Notification ID is required';
    echo json_encode($response);
    exit;
}

try {
    // Get the account_id for the logged-in student
    $stmt_account = $conn->prepare("SELECT account_id FROM accounts WHERE reference_id = :ref_id AND account_type = :acc_type");
    $stmt_account->bindParam(':ref_id', $loggedInStudentId, PDO::PARAM_INT);
    $stmt_account->bindParam(':acc_type', $loggedInStudentType, PDO::PARAM_STR);
    $stmt_account->execute();
    $account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    if (!$account || !isset($account['account_id'])) {
        $response['message'] = 'Student account not found';
        echo json_encode($response);
        exit;
    }
    $loggedInAccountId = $account['account_id'];

    // Update the notification
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE notification_id = :notification_id 
        AND account_id = :account_id
    ");
    
    $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
    $stmt->bindParam(':account_id', $loggedInAccountId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Notification marked as read';
    } else {
        $response['message'] = 'Notification not found or already marked as read';
    }

} catch (PDOException $e) {
    error_log("Database error in mark_notification_read.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while updating the notification';
} catch (Exception $e) {
    error_log("General error in mark_notification_read.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred';
}

echo json_encode($response);
?> 