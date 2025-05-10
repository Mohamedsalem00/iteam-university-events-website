<?php
require_once '../../db/db_connection.php';
header('Content-Type: application/json');

try {
    // Fetch total users
    $userStmt = $conn->query("SELECT COUNT(*) AS total FROM users");
    $totalUsers = $userStmt->fetch()['total'];
    
    // Fetch total organizations
    $orgStmt = $conn->query("SELECT COUNT(*) AS total FROM organizations");
    $totalOrgs = $orgStmt->fetch()['total'];
    
    // Fetch active events (current date between start_date and end_date)
    $eventStmt = $conn->query("SELECT COUNT(*) AS total FROM events WHERE start_date <= NOW() AND end_date >= NOW()");
    $activeEvents = $eventStmt->fetch()['total'];
    
    // Fetch total event registrations
    $regStmt = $conn->query("SELECT COUNT(*) AS total FROM event_registrations");
    $totalRegistrations = $regStmt->fetch()['total'];
    
    // Fetch recent activity
    $activityStmt = $conn->query("
        (SELECT 'registration' as type, CONCAT(u.first_name, ' ', u.last_name) as name, NULL as title, 
            u.registration_date as date, 'user' as entity_type
         FROM users u ORDER BY u.registration_date DESC LIMIT 2)
        UNION
        (SELECT 'registration' as type, o.name, NULL as title, o.registration_date as date, 'organization' as entity_type
         FROM organizations o ORDER BY o.registration_date DESC LIMIT 2)
        UNION
        (SELECT 'event_registration' as type, CONCAT(u.first_name, ' ', u.last_name) as name, e.title,
            er.registration_date as date, 'event' as entity_type
         FROM event_registrations er 
         JOIN users u ON er.user_id = u.user_id 
         JOIN events e ON er.event_id = e.event_id
         ORDER BY er.registration_date DESC LIMIT 4)
        ORDER BY date DESC LIMIT 4
    ");
    $recentActivity = $activityStmt->fetchAll();
    
    // Calculate percentage changes
    $lastWeekUserStmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE registration_date <= NOW() - INTERVAL 7 DAY");
    $lastWeekUsers = $lastWeekUserStmt->fetch()['total'];
    $userChange = $lastWeekUsers > 0 ? round(($totalUsers - $lastWeekUsers) / $lastWeekUsers * 100) : 0;
    
    $lastMonthEventStmt = $conn->query("SELECT COUNT(*) AS total FROM events WHERE start_date <= NOW() - INTERVAL 30 DAY AND end_date >= NOW() - INTERVAL 30 DAY");
    $lastMonthEvents = $lastMonthEventStmt->fetch()['total'];
    $eventChange = $lastMonthEvents > 0 ? round(($activeEvents - $lastMonthEvents) / $lastMonthEvents * 100) : 0;
    
    $lastMonthOrgStmt = $conn->query("SELECT COUNT(*) AS total FROM organizations WHERE registration_date <= NOW() - INTERVAL 30 DAY");
    $lastMonthOrgs = $lastMonthOrgStmt->fetch()['total'];
    $orgChange = $lastMonthOrgs > 0 ? round(($totalOrgs - $lastMonthOrgs) / $lastMonthOrgs * 100) : 0;
    
    $lastMonthRegStmt = $conn->query("SELECT COUNT(*) AS total FROM event_registrations WHERE registration_date <= NOW() - INTERVAL 30 DAY");
    $lastMonthRegs = $lastMonthRegStmt->fetch()['total'];
    $regChange = $lastMonthRegs > 0 ? round(($totalRegistrations - $lastMonthRegs) / $lastMonthRegs * 100) : 0;
    
    echo json_encode([
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
    ]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch admin stats.']);
}
?>