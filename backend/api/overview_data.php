<?php
require_once '../db/db_connection.php';
header('Content-Type: application/json');

try {
    // Get user stats
    $userStmt = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get user change last week
    $lastWeekUserStmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE registration_date <= NOW() - INTERVAL 7 DAY");
    $lastWeekUsers = $lastWeekUserStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $userChange = $lastWeekUsers > 0 ? round(($totalUsers - $lastWeekUsers) / $lastWeekUsers * 100) : 0;
    
    // Get active events
    $eventStmt = $conn->query("SELECT COUNT(*) AS total FROM events WHERE start_date <= NOW() AND end_date >= NOW()");
    $activeEvents = $eventStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get events change last month
    $lastMonthEventStmt = $conn->query("SELECT COUNT(*) AS total FROM events WHERE created_at <= NOW() - INTERVAL 30 DAY");
    $lastMonthEvents = $lastMonthEventStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $eventChange = $lastMonthEvents > 0 ? round(($activeEvents - $lastMonthEvents) / $lastMonthEvents * 100) : 0;
    
    // Get organizations
    $orgStmt = $conn->query("SELECT COUNT(*) AS total FROM organizations");
    $totalOrgs = $orgStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get org change last month
    $lastMonthOrgStmt = $conn->query("SELECT COUNT(*) AS total FROM organizations WHERE registration_date <= NOW() - INTERVAL 30 DAY");
    $lastMonthOrgs = $lastMonthOrgStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $orgChange = $lastMonthOrgs > 0 ? round(($totalOrgs - $lastMonthOrgs) / $lastMonthOrgs * 100) : 5; // Default to 5% if no previous data
    
    // Get event registrations
    $regStmt = $conn->query("SELECT COUNT(*) AS total FROM event_registrations");
    $totalRegistrations = $regStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get registration change last month
    $lastMonthRegStmt = $conn->query("SELECT COUNT(*) AS total FROM event_registrations WHERE registration_date <= NOW() - INTERVAL 30 DAY");
    $lastMonthRegs = $lastMonthRegStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $regChange = $lastMonthRegs > 0 ? round(($totalRegistrations - $lastMonthRegs) / $lastMonthRegs * 100) : 0;
    
    // Get recent activity - a simpler query to prevent errors
    $recentActivity = [];
    
    // Get recent user registrations
    $userActivityStmt = $conn->query("SELECT 'registration' as type, 'user' as entity_type, 
                                     CONCAT(first_name, ' ', last_name) as name, 
                                     NULL as title, registration_date as date 
                                     FROM users 
                                     ORDER BY registration_date DESC LIMIT 2");
    $userActivity = $userActivityStmt->fetchAll(PDO::FETCH_ASSOC);
    $recentActivity = array_merge($recentActivity, $userActivity);
    
    // Get recent events
    $eventActivityStmt = $conn->query("SELECT 'event_creation' as type, 'event' as entity_type,
                                      NULL as name, title, created_at as date 
                                      FROM events 
                                      ORDER BY created_at DESC LIMIT 2");
    $eventActivity = $eventActivityStmt->fetchAll(PDO::FETCH_ASSOC);
    $recentActivity = array_merge($recentActivity, $eventActivity);
    
    // Sort by date
    usort($recentActivity, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Limit to 4 most recent
    $recentActivity = array_slice($recentActivity, 0, 4);
    
    // Prepare response
    $response = [
        'success' => true,
        'stats' => [
            'users' => [
                'total' => $totalUsers,
                'change' => $userChange,
                'period' => 'week'
            ],
            'events' => [
                'total' => $activeEvents,
                'change' => $eventChange,
                'period' => 'month'
            ],
            'organizations' => [
                'total' => $totalOrgs,
                'change' => $orgChange,
                'period' => 'month'
            ],
            'registrations' => [
                'total' => $totalRegistrations,
                'change' => $regChange,
                'period' => 'month'
            ]
        ],
        'activity' => $recentActivity
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log the error
    error_log("API Error (overview_data.php): " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>