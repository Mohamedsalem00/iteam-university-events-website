<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/admin/api/notifications.php
session_start();
require_once 'db_connection.php'; // Ensures $conn (PDO object) is available

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$response = [
    'success' => false,
    'notifications' => [],
    'message' => '',
    'unread_count' => 0
];

$loggedInAdminId = $_SESSION['admin_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

// Check if user is admin and logged in
if (!$loggedInAdminId || $userType !== 'admin') {
    $response['message'] = 'Admin authentication required.';
    echo json_encode($response);
    exit;
}

try {
    // Get the account_id for the logged-in admin
    $stmt_account = $conn->prepare("SELECT account_id FROM accounts WHERE reference_id = :admin_id AND account_type = 'admin'");
    $stmt_account->bindParam(':admin_id', $loggedInAdminId, PDO::PARAM_INT);
    $stmt_account->execute();
    $account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    if (!$account || !isset($account['account_id'])) {
        $response['message'] = 'Admin account not found or not properly linked.';
        echo json_encode($response);
        exit;
    }
    $adminAccountId = $account['account_id'];

    $formatted_notifications = [];
    $default_avatar = '/iteam-university-website/admin/assets/images/default-avatar.png';

    // Simplified SQL to fetch notifications only for admin
    $sql_notifications = "
        SELECT 
            n.notification_id,
            n.account_id, 
            n.event_id,
            e.title AS event_title,
            n.notification_type,
            n.message,
            n.send_date,
            n.is_read,
            'admin' AS account_type,
            adm.admin_id AS reference_id,
            adm.username AS actor_name,
            NULL AS actor_profile_picture
        FROM 
            notifications n
        JOIN 
            accounts acc ON n.account_id = acc.account_id 
        JOIN 
            admins adm ON acc.reference_id = adm.admin_id AND acc.account_type = 'admin'
        LEFT JOIN 
            events e ON n.event_id = e.event_id
        WHERE 
            n.account_id = :admin_account_id
        ORDER BY 
            n.send_date DESC
        LIMIT 20
    ";
    
    $stmt_notifications = $conn->prepare($sql_notifications);
    $stmt_notifications->bindParam(':admin_account_id', $adminAccountId, PDO::PARAM_INT);
    $stmt_notifications->execute();
    $notifications_data = $stmt_notifications->fetchAll(PDO::FETCH_ASSOC);

    // Simplified processing without student/org-specific logic
    foreach ($notifications_data as $notif) {
        $profile_pic_url = $default_avatar; 
        
        $formatted_notifications[] = [
            'id' => $notif['notification_id'],
            'actor_name' => $notif['actor_name'] ?: 'System', 
            'actor_profile_picture' => $profile_pic_url,
            'event_title' => $notif['event_title'],
            'type' => $notif['notification_type'],
            'message' => $notif['message'],
            'date' => $notif['send_date'],
            'is_read' => (bool)$notif['is_read'] 
        ];
    }

    // Count unread notifications for admin
    $stmt_unread_count = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE account_id = :admin_account_id AND is_read = 0
    ");
    $stmt_unread_count->bindParam(':admin_account_id', $adminAccountId, PDO::PARAM_INT);
    $stmt_unread_count->execute();
    $response['unread_count'] = (int)$stmt_unread_count->fetchColumn();

    $response['success'] = true;
    $response['notifications'] = $formatted_notifications;

} catch (PDOException $e) {
    error_log("Database error in notifications.php: " . $e->getMessage());
    $response['message'] = 'An error occurred while fetching notifications.';
} catch (Exception $e) {
    error_log("General error in notifications.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>
