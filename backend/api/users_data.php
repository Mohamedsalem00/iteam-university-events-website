<?php
require_once '../db/db_connection.php';
header('Content-Type: application/json');

try {
    // Get request parameters
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;
    
    // Start building the query
    $query = "
        SELECT 
            u.user_id AS id,
            u.first_name,
            u.last_name,
            u.email,
            'user' AS type,
            u.status,
            u.registration_date
        FROM users u
        WHERE 1=1
    ";
    
    $countQuery = "SELECT COUNT(*) FROM users u WHERE 1=1";
    $params = [];
    
    if ($filter === 'user') {
        // Only users, no need to modify
    } else if ($filter === 'organization') {
        $query = "
            SELECT 
                o.organization_id AS id,
                '' AS first_name,
                o.name AS last_name,
                o.email,
                'organization' AS type,
                o.status,
                o.registration_date
            FROM organizations o
            WHERE 1=1
        ";
        $countQuery = "SELECT COUNT(*) FROM organizations o WHERE 1=1";
    } else if ($filter === 'admin') {
        $query = "
            SELECT 
                a.admin_id AS id,
                a.username AS first_name,
                '' AS last_name,
                a.email,
                'admin' AS type,
                'active' AS status,
                a.created_at AS registration_date
            FROM admins a
            WHERE 1=1
        ";
        $countQuery = "SELECT COUNT(*) FROM admins a WHERE 1=1";
    } else {
        // All users - combine queries
        $query = "
            (SELECT 
                u.user_id AS id,
                u.first_name,
                u.last_name,
                u.email,
                'user' AS type,
                u.status,
                u.registration_date
            FROM users u
            WHERE 1=1)
            UNION
            (SELECT 
                o.organization_id AS id,
                '' AS first_name,
                o.name AS last_name,
                o.email,
                'organization' AS type,
                o.status,
                o.registration_date
            FROM organizations o
            WHERE 1=1)
            UNION
            (SELECT 
                a.admin_id AS id,
                a.username AS first_name,
                '' AS last_name,
                a.email,
                'admin' AS type,
                'active' AS status,
                a.created_at AS registration_date
            FROM admins a
            WHERE 1=1)
        ";
        
        $countQuery = "
            SELECT 
                (SELECT COUNT(*) FROM users) +
                (SELECT COUNT(*) FROM organizations) +
                (SELECT COUNT(*) FROM admins) AS total
        ";
    }
    
    // Add status filter if not 'all'
    if ($status !== 'all' && $filter !== 'admin') {
        if ($filter === 'organization') {
            $query .= " AND o.status = :status";
        } else if ($filter === 'user') {
            $query .= " AND u.status = :status";
        } else {
            // For combined query, we need to add status to each subquery
            $query = str_replace("WHERE 1=1", "WHERE 1=1 AND status = :status", $query);
        }
        $params[':status'] = $status;
    }
    
    // Add date filter
    $dateCondition = '';
    if ($dateFilter === 'today') {
        $dateCondition = "DATE(registration_date) = CURDATE()";
    } else if ($dateFilter === 'week') {
        $dateCondition = "registration_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } else if ($dateFilter === 'month') {
        $dateCondition = "registration_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    } else if ($dateFilter === 'year') {
        $dateCondition = "registration_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    }
    
    if ($dateCondition && $filter !== 'all') {
        if ($filter === 'organization') {
            $query .= " AND $dateCondition";
        } else if ($filter === 'user') {
            $query .= " AND $dateCondition";
        } else if ($filter === 'admin') {
            $query = str_replace("WHERE 1=1", "WHERE 1=1 AND " . str_replace("registration_date", "created_at", $dateCondition), $query);
        }
    } else if ($dateCondition) {
        // For combined query, need to add date condition to each part
        $query = str_replace("WHERE 1=1", "WHERE 1=1 AND $dateCondition", $query);
    }
    
    // Add search if provided
    if ($search) {
        $searchParam = '%' . $search . '%';
        if ($filter === 'organization') {
            $query .= " AND (o.name LIKE :search OR o.email LIKE :search)";
        } else if ($filter === 'user') {
            $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
        } else if ($filter === 'admin') {
            $query .= " AND (a.username LIKE :search OR a.email LIKE :search)";
        } else {
            // For combined query, need to modify each part
            $query = str_replace(
                "WHERE 1=1", 
                "WHERE 1=1 AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)", 
                $query
            );
        }
        $params[':search'] = $searchParam;
    }
    
    // Add sorting
    $orderBy = "registration_date DESC"; // Default newest first
    if ($sortBy === 'oldest') {
        $orderBy = "registration_date ASC";
    } else if ($sortBy === 'name-asc') {
        $orderBy = "last_name ASC, first_name ASC";
    } else if ($sortBy === 'name-desc') {
        $orderBy = "last_name DESC, first_name DESC";
    }
    
    // Finalize the query
    $query .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
    
    // Execute count query
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalResults = $countStmt->fetchColumn();
    
    // Execute main query
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll();
    
    // Format the results
    $formattedUsers = [];
    foreach ($users as $user) {
        $name = '';
        if ($user['type'] === 'user') {
            $name = $user['first_name'] . ' ' . $user['last_name'];
        } else if ($user['type'] === 'organization') {
            $name = $user['last_name']; // organization name stored in last_name field
        } else {
            $name = $user['first_name']; // admin username stored in first_name field
        }
        
        $formattedUsers[] = [
            'id' => $user['id'],
            'name' => $name,
            'email' => $user['email'],
            'type' => $user['type'],
            'status' => $user['status'],
            'registration_date' => $user['registration_date']
        ];
    }
    
    // Calculate total pages
    $totalPages = ceil($totalResults / $limit);
    
    echo json_encode([
        'success' => true,
        'users' => $formattedUsers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_results' => $totalResults,
            'results_per_page' => $limit
        ]
    ]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch users list.']);
}
?>