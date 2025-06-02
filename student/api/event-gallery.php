<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

// Get event ID from request
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required'
    ]);
    exit;
}

try {
    // Fetch event gallery images for the specified event
    $stmt = $conn->prepare("
        SELECT image_id, event_id, image_url, upload_date, caption
        FROM event_gallery
        WHERE event_id = :event_id
        ORDER BY upload_date DESC
    ");
    $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fix image paths
    foreach ($images as &$image) {
        // If image_url is a relative path without leading slash
        if (!empty($image['image_url']) && strpos($image['image_url'], 'http') !== 0 && $image['image_url'][0] !== '/') {
            // Make sure path is relative to the root directory
            if (strpos($image['image_url'], '../') === 0) {
                // Already has ../ prefix, leave it alone
            } else if (strpos($image['image_url'], 'assets/') === 0) {
                $image['image_url'] = '../' . $image['image_url'];
            } else {
                $image['image_url'] = '../' . $image['image_url'];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>