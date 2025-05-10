api.php
<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>API Endpoint Test</h1>
    
    <div class="section">
        <h2>Testing overview_data.php API</h2>
        <?php
        // Test database connection
        try {
            require_once '../db/db_connection.php';
            echo "<p class='success'>✓ Database connection successful!</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
        }
        
        // Test API response
        try {
            // Direct database queries to verify data
            echo "<h3>Direct Database Queries:</h3>";
            
            // Query users
            $userStmt = $conn->query("SELECT COUNT(*) AS total FROM users");
            $totalUsers = $userStmt->fetch()['total'];
            echo "<p>Total Users: $totalUsers</p>";
            
            // Query active events
            $eventStmt = $conn->query("SELECT COUNT(*) AS total FROM events WHERE start_date <= NOW() AND end_date >= NOW()");
            $activeEvents = $eventStmt->fetch()['total'];
            echo "<p>Active Events: $activeEvents</p>";
            
            // Query organizations
            $orgStmt = $conn->query("SELECT COUNT(*) AS total FROM organizations");
            $totalOrgs = $orgStmt->fetch()['total'];
            echo "<p>Organizations: $totalOrgs</p>";
            
            // Query registrations
            $regStmt = $conn->query("SELECT COUNT(*) AS total FROM event_registrations");
            $totalRegistrations = $regStmt->fetch()['total'];
            echo "<p>Event Registrations: $totalRegistrations</p>";
            
            // Get API response using file_get_contents
            echo "<h3>API Response:</h3>";
            $apiUrl = 'http://localhost/iteam-university-website/backend/api/events_data.php';
            $apiResponse = @file_get_contents($apiUrl);
            
            if ($apiResponse === false) {
                echo "<p class='error'>✗ Failed to get API response. Error: " . error_get_last()['message'] . "</p>";
            } else {
                echo "<p class='success'>✓ API response received!</p>";
                $decodedResponse = json_decode($apiResponse, true);
                echo "<pre>" . htmlspecialchars(json_encode($decodedResponse, JSON_PRETTY_PRINT)) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error testing API: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html>