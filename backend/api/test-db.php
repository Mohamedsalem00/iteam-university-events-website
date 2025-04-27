<?php
// filepath: /opt/lampp/htdocs/iteam-university-website/backend/api/test-db.php
// Database connection and tables test script
header('Content-Type: application/json');

try {
    // Include the database connection file
    require_once '../db/db_connection.php';
    
    // Try a simple query to verify connection
    $stmt = $conn->query("SELECT 1 AS connection_test");
    $result = $stmt->fetch();
    
    if ($result['connection_test'] == 1) {
        // Get all tables in the database
        $tablesStmt = $conn->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get database size
        $sizeStmt = $conn->query("SELECT 
            SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = '" . DB_NAME . "'");
        $sizeResult = $sizeStmt->fetch();
        $dbSize = number_format($sizeResult['size_mb'], 2) . ' MB';
        
        // Prepare the tables info array
        $tablesInfo = [];
        
        foreach ($tables as $tableName) {
            // Get table structure
            $columnsStmt = $conn->query("DESCRIBE `$tableName`");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get row count
            $countStmt = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
            $count = $countStmt->fetch();
            
            // Get keys and constraints
            $keysStmt = $conn->query("SHOW KEYS FROM `$tableName`");
            $keys = $keysStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format column information for display
            $formattedColumns = [];
            foreach ($columns as $column) {
                $formattedColumns[] = [
                    'name' => $column['Field'],
                    'type' => $column['Type'],
                    'null' => $column['Null'],
                    'key' => $column['Key'],
                    'default' => $column['Default'],
                    'extra' => $column['Extra']
                ];
            }
            
            // Add table info to the array
            $tablesInfo[] = [
                'name' => $tableName,
                'row_count' => $count['count'],
                'columns' => $formattedColumns,
                'keys' => $keys
            ];
        }
        
        // Get foreign key relationships
        $fkQuery = "SELECT
            TABLE_NAME AS table_name,
            COLUMN_NAME AS column_name,
            REFERENCED_TABLE_NAME AS referenced_table,
            REFERENCED_COLUMN_NAME AS referenced_column
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = '" . DB_NAME . "'
            AND REFERENCED_TABLE_NAME IS NOT NULL";
        
        $fkStmt = $conn->query($fkQuery);
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return the database information
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful!',
            'database' => DB_NAME,
            'connection_info' => $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS),
            'tables' => $tablesInfo,
            'foreign_keys' => $foreignKeys,
            'db_size' => $dbSize,
            'server_version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION)
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database query failed.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Error: ' . $e->getMessage()
    ]);
}
?>