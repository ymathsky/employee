<?php
// FILENAME: employee/api/manager_analytics.php
session_start();
header('Content-Type: application/json');

// Only Managers, HR Admins, and Super Admins can view manager-level analytics.
$user_role = $_SESSION['role'] ?? null;
if (!in_array($user_role, ['HR Admin', 'Super Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

$manager_id = $_SESSION['user_id'];

try {
    // 1. Get the department name managed by the current user
    // We check if the user is a manager of a department in the 'departments' table.
    $stmt_dept = $pdo->prepare("SELECT department_name FROM departments WHERE manager_id = ?");
    $stmt_dept->execute([$manager_id]);
    $managed_department = $stmt_dept->fetchColumn();

    if (!$managed_department) {
        // If the user is a manager but not assigned to a department, we fall back to their own department
        // Or if they are a manager who is an employee in a department
        $stmt_emp_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_emp_dept->execute([$manager_id]);
        $managed_department = $stmt_emp_dept->fetchColumn();
    }

    if (empty($managed_department)) {
        // If still no department is found, exit with a controlled message
        log_action($pdo, $manager_id, LOG_ACTION_MANAGER_ANALYTICS_FAILED, 'Manager attempted to load dashboard but is not assigned to a department.');
        echo json_encode(['success' => false, 'message' => 'Not assigned to a department.']);
        exit;
    }

    // 2. Fetch all required metrics in a single query block for efficiency and atomicity

    // --- Metric 1: Employee Count ---
    $sql_count = "SELECT COUNT(employee_id) FROM employees WHERE department = ?";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$managed_department]);
    $employee_count = $stmt_count->fetchColumn();

    // --- Metric 2: Pending Leave Requests ---
    $sql_leave = "
        SELECT COUNT(lr.request_id)
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        WHERE e.department = ? AND lr.status = 'Pending'";
    $stmt_leave = $pdo->prepare($sql_leave);
    $stmt_leave->execute([$managed_department]);
    $pending_leave_count = $stmt_leave->fetchColumn();

    // --- Metric 3: Average Clock Times (for the last 7 days) ---
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
    // CRITICAL FIX: Ensure non-NULL check for time_in/time_out to prevent AVG/TIME_TO_SEC errors
    $sql_avg_time = "
        SELECT 
            TIME_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(a.time_in))), '%H:%i') AS avg_time_in,
            TIME_FORMAT(SEC_TO_TIME(AVG(TIME_TO_SEC(a.time_out))), '%H:%i') AS avg_time_out
        FROM attendance_logs a
        JOIN employees e ON a.employee_id = e.employee_id
        WHERE e.department = ? 
          AND a.log_date >= ?
          AND a.time_out IS NOT NULL
          AND a.time_in IS NOT NULL
          AND TIME_TO_SEC(a.time_in) IS NOT NULL 
          AND TIME_TO_SEC(a.time_out) IS NOT NULL";
    $stmt_avg_time = $pdo->prepare($sql_avg_time);
    $stmt_avg_time->execute([$managed_department, $seven_days_ago]);
    $avg_times = $stmt_avg_time->fetch(PDO::FETCH_ASSOC);

    // Prepare final response data
    $analytics = [
        'department' => $managed_department,
        'employee_count' => (int)$employee_count,
        'pending_leave_count' => (int)$pending_leave_count,
        'avg_time_in' => $avg_times['avg_time_in'] ? date('h:i A', strtotime($avg_times['avg_time_in'])) : 'N/A',
        'avg_time_out' => $avg_times['avg_time_out'] ? date('h:i A', strtotime($avg_times['avg_time_out'])) : 'N/A',
    ];

    log_action($pdo, $manager_id, LOG_ACTION_MANAGER_ANALYTICS_VIEWED, 'Manager viewed analytics for department: ' . $managed_department);
    echo json_encode(['success' => true, 'data' => $analytics]);

} catch (PDOException $e) {
    // Log the error and return a safe JSON response for the front-end to handle
    log_action($pdo, $manager_id, LOG_ACTION_MANAGER_ANALYTICS_ERROR, 'Database connection/query error: ' . $e->getMessage());
    error_log('Manager Analytics DB Error: ' . $e->getMessage());
    // Return a controlled JSON error message
    echo json_encode(['success' => false, 'message' => 'Database error. Could not retrieve analytics.']);
}
?>
