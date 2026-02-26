<?php
// FILENAME: employee/api/employee_analytics.php
session_start();
header('Content-Type: application/json');
ob_start(); // Start output buffering

// Only logged-in employees can check their own analytics.
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // Load defaults
require_once __DIR__ . '/../config/utils.php'; // For logging

// --- TIMEZONE FIX ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

try {
    $analytics = [];

    // --- Metric 1: Last Clock-In ---
    $sql_last_log = "SELECT time_in FROM attendance_logs WHERE employee_id = ? ORDER BY time_in DESC LIMIT 1";
    $stmt_last_log = $pdo->prepare($sql_last_log);
    $stmt_last_log->execute([$employee_id]);
    $last_time_in = $stmt_last_log->fetchColumn();

    if ($last_time_in) {
        $analytics['last_clock_in'] = date('M j, Y \a\t h:i A', strtotime($last_time_in));
    } else {
        $analytics['last_clock_in'] = 'N/A';
    }

    // --- Metric 2: Pending Leave Requests ---
    $sql_pending_leave = "SELECT COUNT(request_id) FROM leave_requests WHERE employee_id = ? AND status = 'Pending'";
    $stmt_pending_leave = $pdo->prepare($sql_pending_leave);
    $stmt_pending_leave->execute([$employee_id]);
    $analytics['pending_leave_count'] = (int)$stmt_pending_leave->fetchColumn();

    // --- Metric 3: Leave Balances (Re-using logic from get_leave_balances.php) ---

    // 3a. Get defined annual accrual policy
    $sql_policy = "SELECT * FROM leave_balances WHERE employee_id = ?";
    $stmt_policy = $pdo->prepare($sql_policy);
    $stmt_policy->execute([$employee_id]);
    $policy = $stmt_policy->fetch(PDO::FETCH_ASSOC);

    $accrual = [
        'Vacation' => floatval($policy['vacation_days_accrued'] ?? DEFAULT_VACATION_DAYS),
        'Sick Leave' => floatval($policy['sick_days_accrued'] ?? DEFAULT_SICK_DAYS),
        'Personal Day' => floatval($policy['personal_days_accrued'] ?? DEFAULT_PERSONAL_DAYS),
    ];

    // 3b. Get total leave days already used
    $sql_used = "
        SELECT 
            leave_type, 
            SUM(DATEDIFF(end_date, start_date) + 1) AS total_days_used
        FROM leave_requests 
        WHERE employee_id = ? AND status = 'Approved'
        GROUP BY leave_type
    ";
    $stmt_used = $pdo->prepare($sql_used);
    $stmt_used->execute([$employee_id]);
    $used_raw = $stmt_used->fetchAll(PDO::FETCH_KEY_PAIR); // Returns [leave_type => total_days_used]

    $used = [
        'Vacation' => floatval($used_raw['Vacation'] ?? 0),
        'Sick Leave' => floatval($used_raw['Sick Leave'] ?? 0),
        'Personal Day' => floatval($used_raw['Personal Day'] ?? 0)
    ];

    // 3c. Calculate Balances
    $analytics['vacation_available'] = max(0, round($accrual['Vacation'] - $used['Vacation'], 1));
    $analytics['sick_available'] = max(0, round($accrual['Sick Leave'] - $used['Sick Leave'], 1));
    $analytics['personal_available'] = max(0, round($accrual['Personal Day'] - $used['Personal Day'], 1));

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $analytics]);

} catch (PDOException $e) {
    ob_end_clean();
    error_log('Employee Analytics Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error retrieving analytics.']);
}
?>
