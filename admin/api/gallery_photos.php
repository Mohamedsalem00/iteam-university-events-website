<?php
require_once 'db_connection.php'; // This now provides a PDO $conn object

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'albums' => [],
    'message' => ''
];

try {
    // Check if $conn is a valid PDO object
    if (!$conn || !($conn instanceof PDO)) { // Changed check to PDO
        throw new Exception("Database connection (PDO) is not valid or not established.");
    }

    $eventTypeFilter = $_GET['eventType'] ?? '';
    $sortByDate = $_GET['sortByDate'] ?? 'newest_event'; 
    $sortByEventName = $_GET['sortByEventName'] ?? 'asc';

    $sql = "SELECT 
                e.event_id as event_id,
                e.title as event_name,
                e.event_type,
                e.start_date as event_date,
                e.thumbnail_url as album_thumbnail_url,
                e.description as description,
                e.location as location,
                eg.image_id as photo_id,
                eg.image_url as url,
                eg.caption as photo_title,
                eg.upload_date as photo_date_added
            FROM 
                events e
            JOIN 
                event_gallery eg ON e.event_id = eg.event_id
            WHERE 1=1";
    
    $params = []; // For PDO, parameters are typically bound directly in execute()

    if (!empty($eventTypeFilter)) {
        $sql .= " AND e.event_type = :eventType"; // Use named placeholder
        $params[':eventType'] = $eventTypeFilter;
    }

    // Album sorting (by event_date or event_name) is done in PHP after fetching.
    // The SQL ORDER BY here is for photos within each potential album group.
    $sql .= " ORDER BY e.event_id ASC, eg.upload_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // PDO throws exceptions on error if ATTR_ERRMODE is PDO::ERRMODE_EXCEPTION (which it is)
        // So, this specific check might be redundant but doesn't hurt.
        throw new Exception("Failed to prepare statement."); 
    }

    // Execute statement with parameters
    if (!$stmt->execute($params)) { // Pass params array to execute
        throw new Exception("Failed to execute statement.");
    }
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results
    // $stmt->closeCursor(); // Good practice for some drivers, though not always strictly necessary

    if (empty($results)) {
        $response['success'] = true;
        $response['message'] = 'No photos found to create albums.';
        echo json_encode($response);
        // $conn = null; // PDO connections are closed when the object is destroyed or set to null
        exit;
    }

    $albumsData = [];
    foreach ($results as $row) {
        $eventId = $row['event_id'];
        if (!isset($albumsData[$eventId])) {
            $albumsData[$eventId] = [
                'id' => $eventId,
                'event_name' => $row['event_name'] ?? 'Untitled Event',
                'event_type' => $row['event_type'] ?? 'N/A',
                'event_date' => $row['event_date'],
                'album_thumbnail_url' => $row['album_thumbnail_url'] ?? $row['url'],
                'description' => $row['description'] ?? 'No description available',
                'location' => $row['location'] ?? 'N/A',
                'photos' => [],
                'photo_count' => 0
            ];
        }
        $albumsData[$eventId]['photos'][] = [
            'id' => $row['photo_id'],
            'url' => $row['url'] ?? 'assets/images/gallery/placeholder_event.png',
            'title' => $row['photo_title'] ?? 'Untitled Photo',
            'thumbnailUrl' => $row['url'] ?? 'assets/images/gallery/placeholder_event.png',
            'date_added' => $row['photo_date_added'],
            'event_name' => $row['event_name'] ?? 'Untitled Event',
            'event_type' => $row['event_type'] ?? 'N/A'
        ];
        $albumsData[$eventId]['photo_count']++;
    }

    $finalAlbums = array_values($albumsData);

    // Sort albums by event name
    if (!empty($finalAlbums)) {
        usort($finalAlbums, function($a, $b) use ($sortByEventName) {
            $nameA = $a['event_name'] ?? '';
            $nameB = $b['event_name'] ?? '';
            if ($sortByEventName === 'desc') {
                return strcasecmp($nameB, $nameA);
            }
            return strcasecmp($nameA, $nameB);
        });
    }

    // Sort albums by event date
    if (!empty($finalAlbums)) {
        usort($finalAlbums, function($a, $b) use ($sortByDate) {
            $dateStrA = $a['event_date'] ?? null;
            $dateStrB = $b['event_date'] ?? null;

            $timeA = ($dateStrA && $dateStrA !== '0000-00-00 00:00:00' && $dateStrA !== '0000-00-00') ? strtotime($dateStrA) : false;
            $timeB = ($dateStrB && $dateStrB !== '0000-00-00 00:00:00' && $dateStrB !== '0000-00-00') ? strtotime($dateStrB) : false;

            if ($timeA === false && $timeB === false) return 0;
            if ($timeA === false) return 1; 
            if ($timeB === false) return -1;

            if ($sortByDate === 'newest_event') {
                return $timeB - $timeA;
            } elseif ($sortByDate === 'oldest_event') {
                return $timeA - $timeB;
            }
            return 0;
        });
    }

    $response['success'] = true;
    $response['albums'] = $finalAlbums;
    $response['message'] = 'Albums fetched successfully.';

} catch (PDOException $e) { // Catch PDOException specifically for database errors
    error_log("API PDO Error in gallery_photos.php: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    $response['message'] = 'Database error processing your request. Please try again later.';
    $response['dev_message'] = $e->getMessage(); // For development
} catch (Exception $e) {
    error_log("API General Error in gallery_photos.php: " . $e->getMessage() . " on line " . $e->getLine());
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    $response['dev_message'] = $e->getMessage(); // For development
}

// With PDO, the connection is typically closed when the $conn object goes out of scope or is set to null.
// Explicitly setting to null can ensure it, if needed before script end.
// $conn = null; 

echo json_encode($response);
?>
