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

    // Update all unread notifications for the student
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE account_id = :account_id 
        AND is_read = 0
    ");
    
    $stmt->bindParam(':account_id', $loggedInAccountId, PDO::PARAM_INT);
    $stmt->execute();

    $response['success'] = true;
    $response['message'] = 'All notifications marked as read';
    $response['updated_count'] = $stmt->rowCount();

} catch (PDOException $e) {
    error_log("Database error in mark_all_notifications_read.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while updating notifications';
} catch (Exception $e) {
    error_log("General error in mark_all_notifications_read.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred';
}

echo json_encode($response);
?> 