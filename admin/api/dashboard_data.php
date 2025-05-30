<?php
require_once 'db_connection.php'; // Ensure this path is correct

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

// Helper function to calculate trend
function calculate_trend_data($current_period_count, $previous_period_count) {
    $trend = ['percentage' => 0, 'direction' => 'neutral'];
    if ($previous_period_count > 0) {
        $trend['percentage'] = round((($current_period_count - $previous_period_count) / $previous_period_count) * 100, 1);
    } elseif ($current_period_count > 0) {
        $trend['percentage'] = 100; // Infinite growth if previous was 0 and current is > 0
    }

    if ($trend['percentage'] > 0) {
        $trend['direction'] = 'up';
    } elseif ($trend['percentage'] < 0) {
        $trend['direction'] = 'down';
    }
    return $trend;
}

try {
    $data = [];
    $current_date_time = new DateTime();
    $current_date_sql = $current_date_time->format('Y-m-d H:i:s');

    $prev_30_days_start_time = (clone $current_date_time)->modify('-30 days');
    $prev_30_days_start_sql = $prev_30_days_start_time->format('Y-m-d H:i:s');

    $prev_60_days_start_time = (clone $current_date_time)->modify('-60 days');
    $prev_60_days_start_sql = $prev_60_days_start_time->format('Y-m-d H:i:s');

    // --- Quick Stats (Dashboard & Events Page) ---
    $stmt = $conn->query("SELECT COUNT(*) AS count FROM events");
    $data['total_events'] = $stmt->fetchColumn() ?: 0;

    $stmt = $conn->query("SELECT COUNT(*) AS count FROM organizations WHERE status = 'active'");
    $data['active_organizations'] = $stmt->fetchColumn() ?: 0;

    $stmt = $conn->query("SELECT COUNT(*) AS count FROM students WHERE status = 'active'");
    $data['registered_students'] = $stmt->fetchColumn() ?: 0;

    $stmt = $conn->query("SELECT COUNT(*) AS count FROM events WHERE start_date > NOW()");
    $data['upcoming_events'] = $stmt->fetchColumn() ?: 0;

    // --- Trend Data Calculations (Dashboard & Events Page) ---

    // 1. New Events Trend (based on created_at)
    $stmt_curr_events = $conn->prepare("SELECT COUNT(*) FROM events WHERE created_at >= :prev_30_days_start AND created_at < :current_date");
    $stmt_curr_events->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_new_events = $stmt_curr_events->fetchColumn() ?: 0;

    $stmt_prev_events = $conn->prepare("SELECT COUNT(*) FROM events WHERE created_at >= :prev_60_days_start AND created_at < :prev_30_days_start");
    $stmt_prev_events->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_new_events = $stmt_prev_events->fetchColumn() ?: 0;
    $data['total_events_trend'] = calculate_trend_data($current_new_events, $previous_new_events);

    // 2. New Active Organizations Trend (Dashboard)
    $stmt_curr_orgs = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE status = 'active' AND registration_date >= :prev_30_days_start AND registration_date < :current_date");
    $stmt_curr_orgs->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_new_active_orgs = $stmt_curr_orgs->fetchColumn() ?: 0;

    $stmt_prev_orgs = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE status = 'active' AND registration_date >= :prev_60_days_start AND registration_date < :prev_30_days_start");
    $stmt_prev_orgs->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_new_active_orgs = $stmt_prev_orgs->fetchColumn() ?: 0;
    $data['active_organizations_trend'] = calculate_trend_data($current_new_active_orgs, $previous_new_active_orgs);

    // 3. New Registered Students Trend (Dashboard)
    $stmt_curr_students_reg = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_date >= :prev_30_days_start AND registration_date < :current_date");
    $stmt_curr_students_reg->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_new_students_registered = $stmt_curr_students_reg->fetchColumn() ?: 0;

    $stmt_prev_students_reg = $conn->prepare("SELECT COUNT(*) FROM students WHERE registration_date >= :prev_60_days_start AND registration_date < :prev_30_days_start");
    $stmt_prev_students_reg->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_new_students_registered = $stmt_prev_students_reg->fetchColumn() ?: 0;
    $data['registered_students_trend'] = calculate_trend_data($current_new_students_registered, $previous_new_students_registered);

    // 4. New Upcoming Events Trend (based on created_at and start_date > NOW())
    $stmt_curr_upcoming = $conn->prepare("SELECT COUNT(*) FROM events WHERE start_date > NOW() AND created_at >= :prev_30_days_start AND created_at < :current_date");
    $stmt_curr_upcoming->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_new_upcoming = $stmt_curr_upcoming->fetchColumn() ?: 0;

    $stmt_prev_upcoming = $conn->prepare("SELECT COUNT(*) FROM events WHERE start_date > NOW() AND created_at >= :prev_60_days_start AND created_at < :prev_30_days_start");
    $stmt_prev_upcoming->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_new_upcoming = $stmt_prev_upcoming->fetchColumn() ?: 0;
    $data['upcoming_events_trend'] = calculate_trend_data($current_new_upcoming, $previous_new_upcoming);

    // --- Event Page Specific Stats & Trends ---
    $stmt_total_attendees = $conn->query("SELECT COUNT(*) FROM event_registrations WHERE status = 'confirmed'");
    $data['total_confirmed_attendees_all_time'] = $stmt_total_attendees->fetchColumn() ?: 0;

    $stmt_curr_new_attendees = $conn->prepare("SELECT COUNT(*) FROM event_registrations WHERE status = 'confirmed' AND registration_date >= :prev_30_days_start AND registration_date < :current_date");
    $stmt_curr_new_attendees->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_new_confirmed_attendees = $stmt_curr_new_attendees->fetchColumn() ?: 0;

    $stmt_prev_new_attendees = $conn->prepare("SELECT COUNT(*) FROM event_registrations WHERE status = 'confirmed' AND registration_date >= :prev_60_days_start AND registration_date < :prev_30_days_start");
    $stmt_prev_new_attendees->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_new_confirmed_attendees = $stmt_prev_new_attendees->fetchColumn() ?: 0;
    $data['total_confirmed_attendees_trend'] = calculate_trend_data($current_new_confirmed_attendees, $previous_new_confirmed_attendees);

    $stmt_avg_att_overall_num = $conn->query("
        SELECT COUNT(er.registration_id) 
        FROM event_registrations er 
        JOIN events e ON er.event_id = e.event_id 
        WHERE er.status = 'confirmed' AND e.end_date < NOW()
    ");
    $total_confirmed_for_completed_events = $stmt_avg_att_overall_num->fetchColumn() ?: 0;

    $stmt_avg_att_overall_den = $conn->query("SELECT COUNT(event_id) FROM events WHERE end_date < NOW()");
    $total_completed_events_count = $stmt_avg_att_overall_den->fetchColumn() ?: 0;
    $data['average_attendance_overall'] = $total_completed_events_count > 0 ? round($total_confirmed_for_completed_events / $total_completed_events_count, 1) : 0;

    $stmt_events_p1 = $conn->prepare("SELECT event_id FROM events WHERE end_date >= :prev_30_days_start AND end_date < :current_date");
    $stmt_events_p1->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $event_ids_p1 = $stmt_events_p1->fetchAll(PDO::FETCH_COLUMN);
    $avg_att_p1 = 0;
    if (count($event_ids_p1) > 0) {
        $placeholders_p1 = implode(',', array_fill(0, count($event_ids_p1), '?'));
        $stmt_attendees_p1 = $conn->prepare("SELECT COUNT(*) FROM event_registrations WHERE status='confirmed' AND event_id IN ($placeholders_p1)");
        $stmt_attendees_p1->execute($event_ids_p1);
        $total_attendees_p1 = $stmt_attendees_p1->fetchColumn() ?: 0;
        $avg_att_p1 = round($total_attendees_p1 / count($event_ids_p1), 1);
    }

    $stmt_events_p2 = $conn->prepare("SELECT event_id FROM events WHERE end_date >= :prev_60_days_start AND end_date < :prev_30_days_start");
    $stmt_events_p2->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $event_ids_p2 = $stmt_events_p2->fetchAll(PDO::FETCH_COLUMN);
    $avg_att_p2 = 0;
    if (count($event_ids_p2) > 0) {
        $placeholders_p2 = implode(',', array_fill(0, count($event_ids_p2), '?'));
        $stmt_attendees_p2 = $conn->prepare("SELECT COUNT(*) FROM event_registrations WHERE status='confirmed' AND event_id IN ($placeholders_p2)");
        $stmt_attendees_p2->execute($event_ids_p2);
        $total_attendees_p2 = $stmt_attendees_p2->fetchColumn() ?: 0;
        $avg_att_p2 = round($total_attendees_p2 / count($event_ids_p2), 1);
    }
    $data['average_attendance_trend'] = calculate_trend_data($avg_att_p1, $avg_att_p2);

    // --- Recent Events (Dashboard) ---
    $stmt_recent_events = $conn->query("
        SELECT 
            e.event_id, 
            e.title, 
            o.name as organizer_name, 
            e.start_date,
            CASE
                WHEN NOW() < e.start_date THEN 'upcoming'
                WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 'active'
                WHEN NOW() > e.end_date THEN 'completed'
                ELSE 'unknown' 
            END AS event_status
        FROM events e
        LEFT JOIN organizations o ON e.organizer_id = o.organization_id
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    $data['recent_events'] = $stmt_recent_events->fetchAll(PDO::FETCH_ASSOC);

    // --- Recent Activity (Dashboard) ---
    $activityQuery = "
        (SELECT 
            'user_registered' AS type, 
            CONCAT(u.first_name, ' ', u.last_name) AS primary_subject, 
            NULL AS secondary_subject, 
            u.registration_date AS activity_date,
            u.student_id as reference_id,
            'ri-user-add-line' as icon,
            'bg-blue-100 dark:bg-blue-900 text-primary' as icon_bg_color
         FROM students u WHERE u.registration_date IS NOT NULL ORDER BY u.registration_date DESC LIMIT 2)
        UNION ALL
        (SELECT 
            'event_created' AS type, 
            e.title AS primary_subject, 
            org.name AS secondary_subject, 
            e.created_at AS activity_date,
            e.event_id as reference_id,
            'ri-calendar-event-line' as icon,
            'bg-green-100 dark:bg-green-900 text-green-600' as icon_bg_color
         FROM events e LEFT JOIN organizations org ON e.organizer_id = org.organization_id WHERE e.created_at IS NOT NULL ORDER BY e.created_at DESC LIMIT 2)
        UNION ALL
        (SELECT 
            'organization_registered' AS type,
            o.name AS primary_subject,
            NULL AS secondary_subject,
            o.registration_date AS activity_date,
            o.organization_id as reference_id,
            'ri-building-line' as icon,
            'bg-purple-100 dark:bg-purple-900 text-purple-600' as icon_bg_color
        FROM organizations o WHERE o.registration_date IS NOT NULL ORDER BY o.registration_date DESC LIMIT 1)
        ORDER BY activity_date DESC LIMIT 5;
    ";
    $stmt_activity = $conn->query($activityQuery);
    $data['recent_activity'] = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);

    // --- Monthly Statistics Data (Dashboard Chart) ---
    $monthlyStatsData = [
        'labels' => [],
        'series' => [
            ['name' => 'Events', 'data' => []],
            ['name' => 'Attendees', 'data' => []],
            ['name' => 'Organizations', 'data' => []]
        ]
    ];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i months"));
        $month_label = date('M Y', strtotime("-$i months")); // Changed to M Y for clarity
        $months[$month_key] = ['label' => $month_label, 'events' => 0, 'attendees' => 0, 'organizations' => 0];
    }

    $eventsMonthlyStmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
        FROM events
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
        GROUP BY month
    ");
    while ($row = $eventsMonthlyStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['events'] = (int)$row['count'];
        }
    }
    
    $attendeesMonthlyStmt = $conn->query("
        SELECT DATE_FORMAT(e.start_date, '%Y-%m') AS month, COUNT(er.registration_id) AS count
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.status = 'confirmed' AND e.start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
    ");
     while ($row = $attendeesMonthlyStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['attendees'] = (int)$row['count'];
        }
    }

    $orgsMonthlyStmt = $conn->query("
        SELECT DATE_FORMAT(registration_date, '%Y-%m') AS month, COUNT(*) AS count
        FROM organizations
        WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
    ");
    while ($row = $orgsMonthlyStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['organizations'] = (int)$row['count'];
        }
    }
    foreach ($months as $month_data_item) {
        $monthlyStatsData['labels'][] = $month_data_item['label'];
        $monthlyStatsData['series'][0]['data'][] = $month_data_item['events'];
        $monthlyStatsData['series'][1]['data'][] = $month_data_item['attendees'];
        $monthlyStatsData['series'][2]['data'][] = $month_data_item['organizations'];
    }
    $data['monthly_statistics'] = $monthlyStatsData;

    // --- Events by Category Data (Dashboard Chart) ---
    $eventsByCategoryData = [
        'legend' => [],
        'data' => []
    ];
    $eventTypeMapping = [
        'workshop' => ['name' => 'Workshop', 'color' => 'rgba(87, 181, 231, 1)'],
        'conference' => ['name' => 'Conference', 'color' => 'rgba(141, 211, 199, 1)'],
        'fair' => ['name' => 'Fair', 'color' => 'rgba(252, 141, 98, 1)'],
        'webinar' => ['name' => 'Webinar', 'color' => 'rgba(166, 216, 84, 1)'],
        'competition' => ['name' => 'Competition', 'color' => 'rgba(251, 191, 114, 1)'],
        'social' => ['name' => 'Social', 'color' => 'rgba(128, 128, 255, 1)'], 
        'sports' => ['name' => 'Sports', 'color' => 'rgba(255, 128, 128, 1)'], 
        'other' => ['name' => 'Other', 'color' => 'rgba(179, 179, 179, 1)']
    ];
    $categoryStmt = $conn->query("
        SELECT event_type, COUNT(*) AS count
        FROM events
        GROUP BY event_type
    ");
    $dbCategoryResults = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    $processedTypes = [];

    foreach ($dbCategoryResults as $row) {
        $dbType = strtolower($row['event_type']); // Ensure consistent casing
        $count = (int)$row['count'];
        
        if (array_key_exists($dbType, $eventTypeMapping)) {
            $map = $eventTypeMapping[$dbType];
            if (!in_array($map['name'], $eventsByCategoryData['legend'])) { // Avoid duplicate legend items
                 $eventsByCategoryData['legend'][] = $map['name'];
            }
            $eventsByCategoryData['data'][] = [
                'value' => $count,
                'name' => $map['name'],
                'itemStyle' => ['color' => $map['color']]
            ];
            $processedTypes[] = $dbType;
        } else { 
            $name = ucfirst($dbType ?: 'Unknown');
             if (!in_array($name, $eventsByCategoryData['legend'])) {
                $eventsByCategoryData['legend'][] = $name;
            }
            $eventsByCategoryData['data'][] = [
                'value' => $count,
                'name' => $name,
                'itemStyle' => ['color' => $eventTypeMapping['other']['color']] 
            ];
            $processedTypes[] = $dbType; // Track it as processed even if it's mapped to 'Other' display
        }
    }
    // Ensure all defined types in mapping appear in legend, even if count is 0
    foreach($eventTypeMapping as $typeKey => $mapDetails) {
        if(!in_array($typeKey, $processedTypes) && !in_array($mapDetails['name'], $eventsByCategoryData['legend'])) {
            $eventsByCategoryData['legend'][] = $mapDetails['name'];
            $eventsByCategoryData['data'][] = [
                'value' => 0,
                'name' => $mapDetails['name'],
                'itemStyle' => ['color' => $mapDetails['color']]
            ];
        }
    }
    $data['events_by_category'] = $eventsByCategoryData;

    // --- NEW: Event Registrations Chart Data (Events Page) ---
    $daily_registrations_labels = [];
    $daily_registrations_series = [];
    $seven_days_ago = (clone $current_date_time)->modify('-6 days')->setTime(0,0,0); // Start of 7 days ago

    for ($i = 0; $i < 7; $i++) {
        $day = (clone $seven_days_ago)->modify("+$i days");
        $daily_registrations_labels[] = $day->format('D, M j'); // e.g., Mon, May 6
        $day_start_sql = $day->format('Y-m-d 00:00:00');
        $day_end_sql = $day->format('Y-m-d 23:59:59');

        $stmt_daily_reg = $conn->prepare("SELECT COUNT(*) FROM event_registrations WHERE registration_date >= :day_start AND registration_date <= :day_end");
        $stmt_daily_reg->execute(['day_start' => $day_start_sql, 'day_end' => $day_end_sql]);
        $daily_registrations_series[] = $stmt_daily_reg->fetchColumn() ?: 0;
    }
    $data['daily_registrations_last_7_days'] = [
        'labels' => $daily_registrations_labels,
        'series_data' => $daily_registrations_series
    ];

    // --- NEW: Attendance by Event Type Chart Data (Events Page) ---
    $attendance_by_type_data = [];
    $attendance_by_type_legend = [];
    // Use the same $eventTypeMapping for colors and names
    $stmt_att_type = $conn->query("
        SELECT e.event_type, COUNT(er.registration_id) as confirmed_attendees
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        WHERE er.status = 'confirmed'
        GROUP BY e.event_type
    ");
    $dbAttendanceResults = $stmt_att_type->fetchAll(PDO::FETCH_ASSOC);
    $processedEventTypesForAttendance = [];

    foreach ($dbAttendanceResults as $row) {
        $dbType = strtolower($row['event_type']);
        $count = (int)$row['confirmed_attendees'];
        $processedEventTypesForAttendance[] = $dbType;

        if (array_key_exists($dbType, $eventTypeMapping)) {
            $map = $eventTypeMapping[$dbType];
            if (!in_array($map['name'], $attendance_by_type_legend)) {
                 $attendance_by_type_legend[] = $map['name'];
            }
            $attendance_by_type_data[] = [
                'value' => $count,
                'name' => $map['name'],
                'itemStyle' => ['color' => $map['color']]
            ];
        } else {
            $name = ucfirst($dbType ?: 'Unknown');
            if (!in_array($name, $attendance_by_type_legend)) {
                $attendance_by_type_legend[] = $name;
            }
            $attendance_by_type_data[] = [
                'value' => $count,
                'name' => $name,
                'itemStyle' => ['color' => $eventTypeMapping['other']['color']]
            ];
        }
    }
    // Ensure all defined types in mapping appear in legend for attendance chart, even if count is 0
    foreach($eventTypeMapping as $typeKey => $mapDetails) {
        if(!in_array($typeKey, $processedEventTypesForAttendance) && !in_array($mapDetails['name'], $attendance_by_type_legend)) {
            $attendance_by_type_legend[] = $mapDetails['name'];
            $attendance_by_type_data[] = [
                'value' => 0,
                'name' => $mapDetails['name'],
                'itemStyle' => ['color' => $mapDetails['color']]
            ];
        }
    }
    $data['attendance_by_event_type'] = [
        'legend' => $attendance_by_type_legend,
        'data' => $attendance_by_type_data
    ];

    $response['success'] = true;
    $response['data'] = $data;

} catch (PDOException $e) {
    error_log("API Error in dashboard_data.php: " . $e->getMessage());
    $response['message'] = 'Failed to fetch dashboard data. ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General Error in dashboard_data.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. ' . $e->getMessage();
}

echo json_encode($response);
?>