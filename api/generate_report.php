<?php
// FILENAME: employee/api/generate_report.php
session_start();
// Start output buffering to catch stray output/errors
ob_start();
header('Content-Type: application/json');

// Admin only access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['HR Admin', 'Super Admin'])) {
    ob_end_clean(); // Clean buffer before outputting error
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';
require_once __DIR__ . '/../config/app_config.php'; // <--- ADDED THIS LINE

$admin_id = $_SESSION['user_id'];

// Get parameters
$report_type = $_GET['report_type'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$department_filter = $_GET['department'] ?? 'all';

// --- Validation ---
if (empty($report_type)) {
    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => false, 'message' => 'Report type is required.']);
    exit;
}
if (($report_type === 'payroll_summary' || $report_type === 'attendance_summary' || $report_type === 'deduction_report') && (empty($start_date) || empty($end_date))) {
    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required for this report type.']);
    exit;
}

// Ensure dates are valid if provided
try {
    if ($start_date) $dt_start = new DateTime($start_date);
    if ($end_date) $dt_end = new DateTime($end_date);
    if ($start_date && $end_date && isset($dt_end) && isset($dt_start) && $dt_end < $dt_start) {
        ob_end_clean(); // Clean buffer
        echo json_encode(['success' => false, 'message' => 'End date cannot be before start date.']);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => false, 'message' => 'Invalid date format provided.']);
    exit;
}


// --- Report Logic ---
$report_title = '';
$headers = [];
$data = [];
$sql_params = [];
$where_clauses = [];

try {
    switch ($report_type) {
        // --- Payroll Summary Report ---
        case 'payroll_summary':
            $report_title = "Payroll Summary (" . date('M j, Y', strtotime($start_date)) . " - " . date('M j, Y', strtotime($end_date)) . ")";
            $headers = ['Department', 'Total Payslips', 'Total Gross Pay', 'Total Deductions', 'Total Net Pay'];

            $sql = "SELECT
                        IFNULL(e.department, 'N/A') as department,
                        COUNT(p.payroll_id) as total_payslips,
                        SUM(p.gross_pay) as total_gross_pay,
                        SUM(p.deductions) as total_deductions,
                        SUM(p.net_pay) as total_net_pay
                    FROM payroll p
                    LEFT JOIN employees e ON p.employee_id = e.employee_id
                    WHERE p.pay_period_end BETWEEN ? AND ?
                    AND e.status = 'Active'";
            $sql_params[] = $start_date;
            $sql_params[] = $end_date;

            if ($department_filter !== 'all') {
                $sql .= " AND e.department LIKE ?";
                $sql_params[] = "%" . $department_filter . "%";
                $report_title .= " - Dept: " . htmlspecialchars($department_filter);
            }

            $sql .= " GROUP BY e.department ORDER BY e.department";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($sql_params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        // --- Deduction Report ---
        case 'deduction_report':
            $report_title = "Deduction Report (" . date('M j, Y', strtotime($start_date)) . " - " . date('M j, Y', strtotime($end_date)) . ")";
            $headers = ['Employee Name', 'Pay Period', 'Department', 'Standard Deductions (Breakdown)', 'Attendance Ded.', 'CA/Other Ded.', 'Total Deductions'];

            $sql = "SELECT 
                        CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                        CONCAT(DATE_FORMAT(p.pay_period_start, '%b %d'), ' - ', DATE_FORMAT(p.pay_period_end, '%b %d')) as pay_period,
                        e.department,
                        
                        -- Subquery for detailed logs
                        (SELECT GROUP_CONCAT(CONCAT(deduction_name, ': ', amount) SEPARATOR ', ') 
                         FROM payroll_deduction_logs 
                         WHERE payroll_id = p.payroll_id) as breakdown,
                         
                        -- Subquery for sum of logged deductions
                        (SELECT SUM(amount) 
                         FROM payroll_deduction_logs 
                         WHERE payroll_id = p.payroll_id) as total_standard_logged,
                         
                        p.attendance_deductions,
                        p.deductions as total_deductions
                    FROM payroll p
                    JOIN employees e ON p.employee_id = e.employee_id
                    WHERE p.pay_period_end BETWEEN ? AND ?
                    AND e.status = 'Active'";
            
            $sql_params = [$start_date, $end_date];

            if ($department_filter !== 'all') {
                $sql .= " AND e.department = ?";
                $sql_params[] = $department_filter;
                $report_title .= " - Dept: " . htmlspecialchars($department_filter);
            }

            $sql .= " ORDER BY p.pay_period_end DESC, e.last_name";


            $stmt = $pdo->prepare($sql);
            $stmt->execute($sql_params);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process data for display (calculate CA)
            foreach ($raw_data as $row) {
                $std_total = isset($row['total_standard_logged']) ? (float)$row['total_standard_logged'] : 0;
                $att_ded = isset($row['attendance_deductions']) ? (float)$row['attendance_deductions'] : 0;
                $total_payroll_ded = isset($row['total_deductions']) ? (float)$row['total_deductions'] : 0;
                
                $is_legacy = is_null($row['breakdown']);
                $ca_other_val = 0;
                $std_display = '';
                $ca_display = '';

                // Handling Old Records (No breakdown available)
                if ($is_legacy) {
                     if ($total_payroll_ded > 0) {
                        $std_display = "Unclassified Total: " . number_format($total_payroll_ded, 2);
                        $ca_display = "N/A (Legacy)";
                     } else {
                        $std_display = "None";
                        $ca_display = "0.00";
                     }
                } else {
                     $std_display = $row['breakdown'];
                     // CA / Other is the remainder: Total Deductions (Standard + CA) - Standard = CA
                     $ca_other_val = $total_payroll_ded - $std_total;
                     $ca_display = number_format($ca_other_val, 2);
                }
                
                $data[] = [
                    'Employee Name' => $row['employee_name'],
                    'Pay Period' => $row['pay_period'],
                    'Department' => $row['department'],
                    'Standard Deductions (Breakdown)' => $std_display,
                    'Attendance Ded.' => number_format($att_ded, 2),
                    'CA/Other Ded.' => $ca_display,
                    'Total Deductions' => number_format($total_payroll_ded + $att_ded, 2) 
                ];
            }
            break;

        // --- Attendance Summary Report ---
        case 'attendance_summary':
            $report_title = "Attendance Summary (" . date('M j, Y', strtotime($start_date)) . " - " . date('M j, Y', strtotime($end_date)) . ")";
            $headers = ['Employee Name', 'Department', 'Total Logs', 'Total Recorded Hours', 'Total Leave Days', 'Average Hours Per Log'];

            // 1. Get all relevant employees first (Active only)
            $sql_emp = "SELECT employee_id, first_name, last_name, department FROM employees WHERE status = 'Active'";
            $emp_params = [];
            if ($department_filter !== 'all') {
                $sql_emp .= " AND department LIKE ?";
                $emp_params[] = "%" . $department_filter . "%";
                $report_title .= " - Dept: " . htmlspecialchars($department_filter);
            }
            $sql_emp .= " ORDER BY department, last_name, first_name";
            
            $stmt_emp = $pdo->prepare($sql_emp);
            $stmt_emp->execute($emp_params);
            $employees = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

            foreach ($employees as $emp) {
                $eid = $emp['employee_id'];
                
                // 2. Get Attendance Stats
                $sql_logs = "SELECT 
                                COUNT(log_id) as total_logs, 
                                IFNULL(SUM(TIMESTAMPDIFF(SECOND, time_in, time_out)), 0) / 3600 as total_hours 
                             FROM attendance_logs 
                             WHERE employee_id = ? 
                             AND log_date BETWEEN ? AND ? 
                             AND time_out IS NOT NULL";
                $stmt_logs = $pdo->prepare($sql_logs);
                $stmt_logs->execute([$eid, $start_date, $end_date]);
                $log_stats = $stmt_logs->fetch(PDO::FETCH_ASSOC);
                
                // 3. Get Leave Stats
                // We need to calculate days falling within the range
                $sql_leave = "SELECT start_date, end_date 
                              FROM leave_requests 
                              WHERE employee_id = ? 
                              AND status = 'Approved' 
                              AND start_date <= ? 
                              AND end_date >= ?";
                $stmt_leave = $pdo->prepare($sql_leave);
                $stmt_leave->execute([$eid, $end_date, $start_date]);
                $leaves = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);
                
                $leave_days = 0;
                $dt_start_report = new DateTime($start_date);
                $dt_end_report = new DateTime($end_date);
                
                foreach ($leaves as $leave) {
                    $l_start = new DateTime($leave['start_date']);
                    $l_end = new DateTime($leave['end_date']);
                    
                    $effective_start = max($l_start, $dt_start_report);
                    $effective_end = min($l_end, $dt_end_report);
                    
                    if ($effective_start <= $effective_end) {
                        $leave_days += $effective_start->diff($effective_end)->days + 1;
                    }
                }

                $total_hours = (float)$log_stats['total_hours'];
                $total_logs = (int)$log_stats['total_logs'];
                $avg_hours = ($total_logs > 0) ? round($total_hours / $total_logs, 2) : 0;

                $data[] = [
                    'employee_id' => $eid,
                    'employee_name' => $emp['first_name'] . ' ' . $emp['last_name'],
                    'department' => $emp['department'] ?? 'N/A',
                    'total_logs' => $total_logs,
                    'total_recorded_hours' => round($total_hours, 2),
                    'total_leave_days' => $leave_days,
                    'average_hours_per_log' => $avg_hours
                ];
            }
            break;

        // --- Leave Balance Report ---
        case 'leave_balance':
            $report_title = "Leave Balance Report (Current)";
            $headers = ['Employee Name', 'Department', 'Vacation Accrued', 'Vacation Used', 'Vacation Available', 'Sick Accrued', 'Sick Used', 'Sick Available', 'Personal Accrued', 'Personal Used', 'Personal Available'];

            // Base query to get employee and their policy (or defaults)
            $sql = "SELECT
                        e.employee_id,
                        CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                        IFNULL(e.department, 'N/A') as department,
                        COALESCE(lb.vacation_days_accrued, " . DEFAULT_VACATION_DAYS . ") as vacation_accrued,
                        COALESCE(lb.sick_days_accrued, " . DEFAULT_SICK_DAYS . ") as sick_accrued,
                        COALESCE(lb.personal_days_accrued, " . DEFAULT_PERSONAL_DAYS . ") as personal_accrued
                    FROM employees e
                    LEFT JOIN leave_balances lb ON e.employee_id = lb.employee_id
                    WHERE e.status = 'Active'";

            if ($department_filter !== 'all') {
                $sql .= " AND e.department LIKE ?";
                $sql_params[] = "%" . $department_filter . "%";
                $report_title .= " - Dept: " . htmlspecialchars($department_filter);
            }
            $sql .= " ORDER BY e.department, e.last_name, e.first_name";

            $stmt_employees = $pdo->prepare($sql);
            $stmt_employees->execute($sql_params);
            $employees_data = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

            // Fetch total used days for all relevant employees in one go
            $employee_ids = array_column($employees_data, 'employee_id');
            $used_leave_map = [];
            if (!empty($employee_ids)) {
                $in_clause = implode(',', array_fill(0, count($employee_ids), '?'));
                $sql_used = "
                    SELECT
                        employee_id,
                        leave_type,
                        SUM(DATEDIFF(end_date, start_date) + 1) as total_days_used
                    FROM leave_requests
                    WHERE employee_id IN ({$in_clause})
                      AND status = 'Approved'
                      AND leave_type IN ('Vacation', 'Sick Leave', 'Personal Day')
                    GROUP BY employee_id, leave_type
                ";
                $stmt_used = $pdo->prepare($sql_used);
                $stmt_used->execute($employee_ids);
                $used_leave_raw = $stmt_used->fetchAll(PDO::FETCH_ASSOC);

                // Reorganize used leave data for easy access
                foreach($used_leave_raw as $used) {
                    $used_leave_map[$used['employee_id']][$used['leave_type']] = (float)$used['total_days_used'];
                }
            }


            // Calculate balances for each employee
            foreach($employees_data as $emp) {
                $emp_id = $emp['employee_id'];
                $vac_used = $used_leave_map[$emp_id]['Vacation'] ?? 0.0;
                $sick_used = $used_leave_map[$emp_id]['Sick Leave'] ?? 0.0;
                $pers_used = $used_leave_map[$emp_id]['Personal Day'] ?? 0.0;

                $data[] = [
                    'employee_name' => $emp['employee_name'],
                    'department' => $emp['department'],
                    'vacation_accrued' => (float)$emp['vacation_accrued'],
                    'vacation_used' => $vac_used,
                    'vacation_available' => max(0, round((float)$emp['vacation_accrued'] - $vac_used, 1)), // Round available balance
                    'sick_accrued' => (float)$emp['sick_accrued'],
                    'sick_used' => $sick_used,
                    'sick_available' => max(0, round((float)$emp['sick_accrued'] - $sick_used, 1)), // Round available balance
                    'personal_accrued' => (float)$emp['personal_accrued'],
                    'personal_used' => $pers_used,
                    'personal_available' => max(0, round((float)$emp['personal_accrued'] - $pers_used, 1)), // Round available balance
                ];
            }
            break;

        default:
            ob_end_clean(); // Clean buffer
            echo json_encode(['success' => false, 'message' => 'Invalid report type specified.']);
            exit;
    }

    // Log the action
    $filter_desc = "Type={$report_type}";
    if($start_date) $filter_desc .= ", Start={$start_date}";
    if($end_date) $filter_desc .= ", End={$end_date}";
    if($department_filter) $filter_desc .= ", Dept={$department_filter}";
    log_action($pdo, $admin_id, 'REPORT_GENERATED', "Generated {$report_title}. Filters: {$filter_desc}");

    // Clean buffer before sending final JSON
    ob_end_clean();
    // Return JSON response
    echo json_encode([
        'success' => true,
        'report_type' => $report_type, // Send back report type for formatting hints
        'report_title' => $report_title,
        'headers' => $headers,
        'data' => $data
    ]);

} catch (PDOException $e) {
    error_log("Report Generation Error ({$report_type}): " . $e->getMessage());
    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => false, 'message' => 'Database error generating report: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Report Generation Error ({$report_type}): " . $e->getMessage());
    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
?>

