<?php
// Start session
session_start();

// Include database connection
require_once 'db_connection.php';

// Set headers to ensure proper JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Get distinct event categories and counts
    $stmt = $conn->prepare("
        SELECT 
            event_type as category,
            COUNT(*) as count,
            MAX(thumbnail_url) as sample_image
        FROM 
            events
        WHERE 
            end_date >= NOW()
        GROUP BY 
            event_type
        ORDER BY 
            count DESC
    ");
    $stmt->execute();
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Assign emoji based on category name
        $emoji = '🎉'; // Default emoji
        
        switch (strtolower($row['category'])) {
            case 'workshop':
                $emoji = '🛠️';
                break;
                
            case 'conference':
                $emoji = '🗣️';
                break;
                
            case 'fair':
                $emoji = '💼';
                break;
                
            case 'webinar':
                $emoji = '💻';
                break;
                
            default:
                $emoji = '📅';
                break;
        }
        
        $categories[] = [
            'name' => $row['category'],
            'count' => (int)$row['count'],
            'emoji' => $emoji,
            'image' => $row['sample_image']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Categories API error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching categories',
        'categories' => []
    ]);
}
?>