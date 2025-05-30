<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

// Check if we're requesting a specific student by ID
if (isset($_GET['student_id'])) {
    $studentId = $_GET['student_id'];
    
    try {
        // Prepare the statement for a single student query
        $stmt = $conn->prepare("
            SELECT 
                u.student_id, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.status, 
                u.profile_picture,
                u.registration_date,
                DATE_FORMAT(a.last_login, '%Y-%m-%d %H:%i:%s') as last_login,
                a.account_type AS role
            FROM students u
            LEFT JOIN accounts a ON a.reference_id = u.student_id AND a.account_type = 'student'
            WHERE u.student_id = :student_id
        ");
        
        $stmt->execute(['student_id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            $response['message'] = "Student with ID {$studentId} not found.";
        } else {
            $response['success'] = true;
            $response['student'] = $student;
            
            // Get student's recent events
            $eventsStmt = $conn->prepare("
                SELECT 
                    e.event_id,
                    e.title,
                    e.start_date,
                    e.end_date,
                    e.location,
                    e.event_type,
                    er.status
                FROM event_registrations er
                JOIN events e ON e.event_id = er.event_id
                WHERE er.student_id = :student_id
                ORDER BY e.start_date DESC
                LIMIT 5
            ");
            
            $eventsStmt->execute(['student_id' => $studentId]);
            $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
            $response['student']['events'] = $events;
            
            // Get student's event count
            $eventCountStmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM event_registrations 
                WHERE student_id = :student_id
            ");
            $eventCountStmt->execute(['student_id' => $studentId]);
            $response['student']['event_count'] = (int)$eventCountStmt->fetchColumn();
            
            // Get student's comment count (if you have a comments table)
            $commentCount = 0; // Default if no comment system exists
            if ($conn->query("SHOW TABLES LIKE 'comments'")->rowCount() > 0) {
                $commentCountStmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM comments 
                    WHERE student_id = :student_id
                ");
                $commentCountStmt->execute(['student_id' => $studentId]);
                $commentCount = (int)$commentCountStmt->fetchColumn();
            }
            $response['student']['comment_count'] = $commentCount;
            
            $response['message'] = 'Student details retrieved successfully.';
        }
        
        echo json_encode($response);
        exit; // Stop execution after returning student data
    } catch (PDOException $e) {
        $response['message'] = 'Failed to fetch student details. ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

// Continue with existing code for listing all students
$response['data'] = [
    'student_list' => [],
    'stats' => [],
    'chart_data' => []
];

// Helper function to calculate trend
function calculate_trend_data($current_period_count, $previous_period_count) {
    $trend = ['percentage' => 0, 'direction' => 'neutral'];
    if ($previous_period_count > 0) {
        $trend['percentage'] = round((($current_period_count - $previous_period_count) / $previous_period_count) * 100, 1);
    } elseif ($current_period_count > 0) {
        $trend['percentage'] = 100;
    }

    if ($trend['percentage'] > 0) {
        $trend['direction'] = 'up';
    } elseif ($trend['percentage'] < 0) {
        $trend['direction'] = 'down';
    }
    return $trend;
}

try {
    // --- Fetch Student List ---
    // This query fetches students who have an account type of 'student'.
    // If you want to list admins or orgs here, this query would need to change significantly.
    $stmt_list = $conn->query("
        SELECT 
            u.student_id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.status, 
            u.registration_date, 
            a.account_type AS role 
        FROM students u
        LEFT JOIN accounts a ON a.reference_id = u.student_id AND a.account_type = 'student' 
        ORDER BY u.registration_date DESC
    ");
    $response['data']['student_list'] = $stmt_list->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- Calculate Student Statistics ---
    $stats = [];
    $current_date_time = new DateTime();
    $current_date_sql = $current_date_time->format('Y-m-d H:i:s');

    $prev_30_days_start_time = (clone $current_date_time)->modify('-30 days');
    $prev_30_days_start_sql = $prev_30_days_start_time->format('Y-m-d H:i:s');

    $prev_60_days_start_time = (clone $current_date_time)->modify('-60 days');
    $prev_60_days_start_sql = $prev_60_days_start_time->format('Y-m-d H:i:s');

    // Total Students (from students table)
    $stmt_total = $conn->query("SELECT COUNT(*) FROM students");
    $stats['total_students'] = $stmt_total->fetchColumn() ?: 0;

    // Active Students
    $stmt_active = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'active'");
    $stats['active_students'] = $stmt_active->fetchColumn() ?: 0;
    
    // Inactive Students
    $stmt_inactive = $conn->query("SELECT COUNT(*) FROM students WHERE status = 'inactive'");
    $stats['inactive_students'] = $stmt_inactive->fetchColumn() ?: 0;

    // New Students (Last 30 Days)
    $stmt_new_30_days = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_date >= :prev_30_days_start AND registration_date < :current_date");
    $stmt_new_30_days->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $stats['new_students_last_30_days'] = $stmt_new_30_days->fetchColumn() ?: 0;

    // --- Trend Calculations ---
    // Total Students Trend (based on new registrations in the students table)
    $current_period_total_new_students = $stats['new_students_last_30_days'];
    $stmt_prev_period_total_new_students = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_date >= :prev_60_days_start AND registration_date < :prev_30_days_start");
    $stmt_prev_period_total_new_students->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_period_total_new_students = $stmt_prev_period_total_new_students->fetchColumn() ?: 0;
    $stats['total_students_trend'] = calculate_trend_data($current_period_total_new_students, $previous_period_total_new_students);

    // Active Students Trend (based on new *active* registrations in the students table)
    $stmt_curr_active_new = $conn->prepare("SELECT COUNT(*) FROM students WHERE status = 'active' AND registration_date >= :prev_30_days_start AND registration_date < :current_date");
    $stmt_curr_active_new->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_period_active_new_students = $stmt_curr_active_new->fetchColumn() ?: 0;

    $stmt_prev_active_new = $conn->prepare("SELECT COUNT(*) FROM students WHERE status = 'active' AND registration_date >= :prev_60_days_start AND registration_date < :prev_30_days_start");
    $stmt_prev_active_new->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_period_active_new_students = $stmt_prev_active_new->fetchColumn() ?: 0;
    $stats['active_students_trend'] = calculate_trend_data($current_period_active_new_students, $previous_period_active_new_students);
    
    $stats['new_students_last_30_days_trend'] = $stats['total_students_trend']; // Same logic

    $response['data']['stats'] = $stats;

    // --- Chart Data Calculations ---
    $chart_data = [];

    // Monthly Student Registrations (Last 12 Months)
    $monthly_registrations = ['labels' => [], 'series_data' => []];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i months"));
        $month_label = date('M Y', strtotime("-$i months"));
        $months[$month_key] = ['label' => $month_label, 'registrations' => 0];
    }
    $studentRegMonthlyStmt = $conn->query("
        SELECT DATE_FORMAT(registration_date, '%Y-%m') AS month, COUNT(*) AS count
        FROM students
        WHERE registration_date >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01') 
        GROUP BY month
        ORDER BY month ASC
    ");
    while ($row = $studentRegMonthlyStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['registrations'] = (int)$row['count'];
        }
    }
    foreach ($months as $month_data_item) {
        $monthly_registrations['labels'][] = $month_data_item['label'];
        $monthly_registrations['series_data'][] = $month_data_item['registrations'];
    }
    $chart_data['monthly_registrations'] = $monthly_registrations;

    // Student Status Distribution
    $status_distribution_data = [];
    if ($stats['active_students'] > 0) {
        $status_distribution_data[] = ['name' => 'Active', 'value' => $stats['active_students']];
    }
    if ($stats['inactive_students'] > 0) {
        $status_distribution_data[] = ['name' => 'Inactive', 'value' => $stats['inactive_students']];
    }
     if (empty($status_distribution_data) && $stats['total_students'] === 0) { // Handle case with no students
        $status_distribution_data[] = ['name' => 'No Students', 'value' => 0];
    }

    $chart_data['status_distribution'] = [
        'legend' => array_column($status_distribution_data, 'name'),
        'data' => $status_distribution_data
    ];
    
    $response['data']['chart_data'] = $chart_data;
    $response['success'] = true;
    $response['message'] = 'Students, stats, and chart data fetched successfully.';

} catch (PDOException $e) {
    error_log("Error in students.php: " . $e->getMessage());
    $response['message'] = 'Failed to fetch student data. ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General Error in students.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. ' . $e->getMessage();
}

echo json_encode($response);
$conn = null;
?>