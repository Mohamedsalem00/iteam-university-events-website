<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => [
        'organization_list' => [],
        'stats' => [],
        'chart_data' => []
    ],
    'message' => ''
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
    // --- Fetch Organization List ---
    $stmt_list = $conn->query("
        SELECT 
            o.organization_id, 
            o.name, 
            o.email, 
            o.status, 
            o.created_at, 
            o.profile_picture,
            o.description,
            a.account_type AS role
        FROM organizations o
        INNER JOIN accounts a ON a.reference_id = o.organization_id AND a.account_type = 'organization'
        ORDER BY o.created_at DESC
    ");
    $response['data']['organization_list'] = $stmt_list->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // --- Calculate Organization Statistics ---
    $stats = [];
    $current_date_time = new DateTime();
    $current_date_sql = $current_date_time->format('Y-m-d H:i:s');

    $prev_30_days_start_time = (clone $current_date_time)->modify('-30 days');
    $prev_30_days_start_sql = $prev_30_days_start_time->format('Y-m-d H:i:s');

    $prev_60_days_start_time = (clone $current_date_time)->modify('-60 days');
    $prev_60_days_start_sql = $prev_60_days_start_time->format('Y-m-d H:i:s');

    // Total Organizations
    $stmt_total = $conn->query("SELECT COUNT(*) FROM organizations");
    $stats['total_organizations'] = $stmt_total->fetchColumn() ?: 0;

    // Active Organizations
    $stmt_active = $conn->query("SELECT COUNT(*) FROM organizations WHERE status = 'active'");
    $stats['active_organizations'] = $stmt_active->fetchColumn() ?: 0;
    
    // Inactive Organizations
    $stmt_inactive = $conn->query("SELECT COUNT(*) FROM organizations WHERE status = 'inactive'");
    $stats['inactive_organizations'] = $stmt_inactive->fetchColumn() ?: 0;

    // New Organizations (Last 30 Days)
    $stmt_new_30_days = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE created_at >= :prev_30_days_start AND created_at < :current_date");
    $stmt_new_30_days->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $stats['new_organizations_last_30_days'] = $stmt_new_30_days->fetchColumn() ?: 0;

    // --- Trend Calculations ---
    // Total Organizations Trend
    $current_period_total_new_orgs = $stats['new_organizations_last_30_days'];
    $stmt_prev_period_total_new_orgs = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE created_at >= :prev_60_days_start AND created_at < :prev_30_days_start");
    $stmt_prev_period_total_new_orgs->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_period_total_new_orgs = $stmt_prev_period_total_new_orgs->fetchColumn() ?: 0;
    $stats['total_organizations_trend'] = calculate_trend_data($current_period_total_new_orgs, $previous_period_total_new_orgs);

    // Active Organizations Trend
    $stmt_curr_active_new = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE status = 'active' AND created_at >= :prev_30_days_start AND created_at < :current_date");
    $stmt_curr_active_new->execute(['prev_30_days_start' => $prev_30_days_start_sql, 'current_date' => $current_date_sql]);
    $current_period_active_new_orgs = $stmt_curr_active_new->fetchColumn() ?: 0;

    $stmt_prev_active_new = $conn->prepare("SELECT COUNT(*) FROM organizations WHERE status = 'active' AND created_at >= :prev_60_days_start AND created_at < :prev_30_days_start");
    $stmt_prev_active_new->execute(['prev_60_days_start' => $prev_60_days_start_sql, 'prev_30_days_start' => $prev_30_days_start_sql]);
    $previous_period_active_new_orgs = $stmt_prev_active_new->fetchColumn() ?: 0;
    $stats['active_organizations_trend'] = calculate_trend_data($current_period_active_new_orgs, $previous_period_active_new_orgs);
    
    $stats['new_organizations_last_30_days_trend'] = $stats['total_organizations_trend'];

    $response['data']['stats'] = $stats;

    // --- Chart Data Calculations ---
    $chart_data = [];

    // Monthly Organization Registrations (Last 12 Months)
    $monthly_registrations = ['labels' => [], 'series_data' => []];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $month_key = date('Y-m', strtotime("-$i months"));
        $month_label = date('M Y', strtotime("-$i months"));
        $months[$month_key] = ['label' => $month_label, 'registrations' => 0];
    }
    $orgRegMonthlyStmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
        FROM organizations
        WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01') 
        GROUP BY month
        ORDER BY month ASC
    ");
    while ($row = $orgRegMonthlyStmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($months[$row['month']])) {
            $months[$row['month']]['registrations'] = (int)$row['count'];
        }
    }
    foreach ($months as $month_data_item) {
        $monthly_registrations['labels'][] = $month_data_item['label'];
        $monthly_registrations['series_data'][] = $month_data_item['registrations'];
    }
    $chart_data['monthly_registrations'] = $monthly_registrations;

    // Organization Status Distribution
    $status_distribution_data = [];
    if ($stats['active_organizations'] > 0) {
        $status_distribution_data[] = ['name' => 'Active', 'value' => $stats['active_organizations']];
    }
    if ($stats['inactive_organizations'] > 0) {
        $status_distribution_data[] = ['name' => 'Inactive', 'value' => $stats['inactive_organizations']];
    }
    if (empty($status_distribution_data) && $stats['total_organizations'] === 0) {
        $status_distribution_data[] = ['name' => 'No Organizations', 'value' => 0];
    }

    $chart_data['status_distribution'] = [
        'legend' => array_column($status_distribution_data, 'name'),
        'data' => $status_distribution_data
    ];
    
    $response['data']['chart_data'] = $chart_data;
    $response['success'] = true;
    $response['message'] = 'Organizations, stats, and chart data fetched successfully.';

} catch (PDOException $e) {
    error_log("Error in organizations.php: " . $e->getMessage());
    $response['message'] = 'Failed to fetch organization data. ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General Error in organizations.php: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. ' . $e->getMessage();
}

echo json_encode($response);
$conn = null;
?>