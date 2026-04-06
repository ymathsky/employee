<?php
// FILENAME: employee/api/admin_analytics.php
session_start();
header('Content-Type: application/json');

// Start output buffering to catch any stray output or PHP warnings
ob_start();

// Admin only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    ob_end_clean(); // Discard buffer contents
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

// --- Safe Table Existence Check Function ---
/**
 * Checks if a table exists in the database without throwing an exception if not found.
 * @param PDO $pdo
 * @param string $table
 * @return bool
 * @throws PDOException if any error other than "table not found" occurs.
 */
function tableExists($pdo, $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (PDOException $e) {
        // SQLSTATE[42S02]: Base table or view not found (or another common table-not-found code)
        if ($e->getCode() === '42S02' || strpos($e->getMessage(), 'no such table') !== false) {
            return false;
        }
        // Re-throw other errors
        throw $e;
    }
    return true;
}
// --- End Safe Table Existence Check Function ---

try {
    // --- Metric 1: Total Employee Count ---
    $sql_count = "SELECT COUNT(employee_id) FROM employees";
    $stmt_count = $pdo->query($sql_count);
    $total_employees = (int)$stmt_count->fetchColumn();

    // --- Metric 2: Total Departments ---
    $sql_dept = "SELECT COUNT(department_id) FROM departments";
    $stmt_dept = $pdo->query($sql_dept);
    $total_departments = (int)$stmt_dept->fetchColumn();

    // --- Metric 3: Total Pending Leave Requests (Company-wide) ---
    $sql_leave = "SELECT COUNT(request_id) FROM leave_requests WHERE status = 'Pending'";
    $stmt_leave = $pdo->query($sql_leave);
    $pending_leave_count = (int)$stmt_leave->fetchColumn();

    // --- Metric 4: Average Days of Sick Leave Used (Last 12 Months) ---
    $one_year_ago = date('Y-m-d', strtotime('-1 year'));
    // SUM(DATEDIFF) is already used correctly to count days in the pay/leave logic.
    $sql_sick_days = "
        SELECT 
            SUM(DATEDIFF(end_date, start_date) + 1)
        FROM leave_requests
        WHERE leave_type = 'Sick Leave' AND status = 'Approved'
          AND start_date >= ?
    ";
    $stmt_sick_days = $pdo->prepare($sql_sick_days);
    $stmt_sick_days->execute([$one_year_ago]);
    $total_sick_leave_used = $stmt_sick_days->fetchColumn() ?? 0;

    $avg_sick_leave_per_employee = ($total_employees > 0)
        ? round((float)$total_sick_leave_used / $total_employees, 1)
        : 0;

    // --- Metric 5: Total Gross Payroll (Last Month) ---
    // Calculate payroll for the pay periods that *ended* in the last full month (approximation)
    $last_month_start = date('Y-m-01', strtotime('last month'));
    $this_month_start = date('Y-m-01');

    $sql_gross_payroll = "
        SELECT SUM(gross_pay) 
        FROM payroll 
        WHERE pay_period_end >= ? AND pay_period_end < ?
    ";
    $stmt_payroll = $pdo->prepare($sql_gross_payroll);
    $stmt_payroll->execute([$last_month_start, $this_month_start]);
    $total_monthly_gross_pay = $stmt_payroll->fetchColumn() ?? 0;

    // --- Metric 6: Present Today ---
    $sql_present = "SELECT COUNT(DISTINCT employee_id) FROM attendance_logs WHERE DATE(time_in) = CURDATE()";
    $present_today = (int)$pdo->query($sql_present)->fetchColumn();

    // --- Metric 7: On Leave Today ---
    $sql_on_leave = "SELECT COUNT(DISTINCT employee_id) FROM leave_requests WHERE status = 'Approved' AND CURDATE() BETWEEN start_date AND end_date";
    $on_leave_today = (int)$pdo->query($sql_on_leave)->fetchColumn();

    // --- Metric 8: Unpaid Payrolls ---
    $sql_unpaid = "SELECT COUNT(*) FROM payroll WHERE status = 'unpaid'";
    $unpaid_payrolls = (int)$pdo->query($sql_unpaid)->fetchColumn();

    // --- Metric 9: Latest Announcements ---
    $sql_anno = "SELECT announcement_id, title, message, created_at FROM announcements ORDER BY created_at DESC LIMIT 5";
    $announcements = $pdo->query($sql_anno)->fetchAll(PDO::FETCH_ASSOC);

    // --- Metric 10: Total Pending Mandatory Training (Safe Query) ---
    // Safely check for tables before running the query
    $pending_training_count = 0;
    if (tableExists($pdo, 'employee_training') && tableExists($pdo, 'training_courses')) {
        $sql_training = "
            SELECT COUNT(et.training_id) 
            FROM employee_training et 
            JOIN training_courses tc ON et.course_id = tc.course_id
            WHERE et.completion_status = 'Pending' AND tc.is_mandatory = TRUE
        ";
        $stmt_training = $pdo->query($sql_training);
        $pending_training_count = (int)$stmt_training->fetchColumn();
    }


    // Compile analytics data
    $analytics = [
        'total_employees' => $total_employees,
        'total_departments' => $total_departments,
        'pending_leave_count' => $pending_leave_count,
        'avg_sick_leave_per_employee' => (float)$avg_sick_leave_per_employee,
        'total_monthly_gross_pay' => round((float)$total_monthly_gross_pay, 2),
        // Display the period used for the payroll calculation
        'payroll_period' => date('F Y', strtotime('last month')),
        'pending_training_count' => $pending_training_count,
        'present_today'           => $present_today,
        'on_leave_today'          => $on_leave_today,
        'unpaid_payrolls'         => $unpaid_payrolls,
        'pending_leave'           => $pending_leave_count,
        'announcements'           => $announcements,
    ];

    log_action($pdo, $_SESSION['user_id'], 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.');

    // Clean and end the buffer before outputting JSON
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $analytics]);

} catch (PDOException $e) {
    // Log the error and return a safe, explicit error JSON object
    error_log('Admin Analytics DB Error: ' . $e->getMessage());
    ob_end_clean(); // Clean and end the buffer
    echo json_encode(['success' => false, 'message' => 'Database error retrieving analytics data: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Catch any other fatal PHP errors
    error_log('Admin Analytics General Error: ' . $e->getMessage());
    ob_end_clean(); // Clean and end the buffer
    echo json_encode(['success' => false, 'message' => 'System error during analytics processing: ' . $e->getMessage()]);
}
?>
