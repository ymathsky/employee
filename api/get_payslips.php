<?php
// FILENAME: employee/api/get_payslips.php
session_start();
// Start output buffering
ob_start();
header('Content-Type: application/json');

// Only logged-in employees (or managers/admins) can access payslips.
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Needed for logging
require_once __DIR__ . '/../config/app_config.php'; // Needed for default leave constants if recalculating

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Employee';
$is_admin = ($user_role === 'HR Admin' || $user_role === 'Super Admin');
$is_manager = ($user_role === 'Manager');

// NEW: Get parameters for specific run details
$filter_start_date = $_GET['pay_period_start'] ?? null;
$filter_end_date = $_GET['pay_period_end'] ?? null;
$fetch_for_run_details = !empty($filter_start_date) && !empty($filter_end_date);


$manager_department = null;
if ($is_manager) {
    try {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$user_id]);
        $manager_department = $stmt_dept->fetchColumn();
    } catch (PDOException $e) {
        error_log('Error fetching manager department: ' . $e->getMessage());
    }
}

// Base SQL Query - Fetch necessary fields including employee details
$sql = "SELECT
            p.payroll_id,
            p.employee_id,
            p.pay_period_start,
            p.pay_period_end,
            p.gross_pay,
            p.allowances,
            p.attendance_deductions,
            p.deductions,
            p.net_pay,
            p.status,
            p.created_at,
            e.first_name,
            e.last_name,
            e.department,
            e.job_title,
            p.pay_type_used,
            p.pay_rate_used,
            p.total_payable_hours,
            p.total_paid_leave_days
        FROM payroll p
        LEFT JOIN employees e ON p.employee_id = e.employee_id ";

$params = [];
$where_clauses = [];

// Apply Security Filters (based on role)
if (!$is_admin) {
    if ($is_manager && $manager_department) {
        $where_clauses[] = "(e.department = ? OR p.employee_id = ?)";
        $params[] = $manager_department;
        $params[] = $user_id;
    } else {
        $where_clauses[] = "p.employee_id = ?";
        $params[] = $user_id;
    }
}

// NEW: Apply Pay Period Filter if requested for run details
if ($fetch_for_run_details) {
    $where_clauses[] = "p.pay_period_start = ?";
    $params[] = $filter_start_date;
    $where_clauses[] = "p.pay_period_end = ?";
    $params[] = $filter_end_date;
}

// Combine WHERE clauses
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Order appropriately
$sql .= " ORDER BY p.pay_period_end DESC, e.last_name ASC, e.first_name ASC";


try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- OPTIONAL BUT RECOMMENDED FOR 'Run Details': Recalculate deduction breakdown ---
    // This adds overhead but provides the detailed breakdown needed for the modal.
    // If performance is critical and breakdown isn't needed here, skip this block.
    /*
    if ($fetch_for_run_details && !empty($payslips)) {
        // Fetch active deductions once
        $active_deductions_config = [];
        try {
            $stmt_deductions = $pdo->query("SELECT * FROM deduction_types WHERE is_active = TRUE");
            $active_deductions_config = $stmt_deductions->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { // Ignore if table missing
             error_log('Could not fetch deduction config for run details: ' . $e->getMessage());
        }
        $ca_deduction_type_name = $_SESSION['settings']['system_ca_deduction_name'] ?? 'Cash Advance';


        foreach ($payslips as &$slip) {
            $gross_pay = (float)$slip['gross_pay'];
            $total_recorded_deductions = (float)$slip['deductions'];
            $standard_deductions_total = 0.00;
            $standard_deductions_breakdown = [];

            if ($gross_pay > 0 && !empty($active_deductions_config)) {
                foreach ($active_deductions_config as $deduction_type) {
                    $deduction_value = (float)$deduction_type['value'];
                    $amount = 0.00;
                    if ($deduction_type['type'] === 'Fixed') {
                        $amount = $deduction_value;
                    } elseif ($deduction_type['type'] === 'Percentage') {
                        $amount = ($gross_pay * ($deduction_value / 100));
                    }
                    $amount = round($amount, 2);
                    $standard_deductions_total += $amount;
                    $standard_deductions_breakdown[] = [
                        'name' => $deduction_type['name'],
                        'amount' => $amount
                    ];
                }
            }

            $ca_amount_deducted = max(0, round($total_recorded_deductions - $standard_deductions_total, 2));

            // Add calculated breakdown to the payslip data being returned
            $slip['standard_deductions_breakdown'] = $standard_deductions_breakdown;
            $slip['ca_deducted'] = $ca_amount_deducted;
        }
        unset($slip); // Unset reference
    }
    */
    // --- End Optional Deduction Breakdown Recalculation ---

    ob_end_clean(); // Clean buffer before output
    echo json_encode(['success' => true, 'data' => $payslips]);

} catch (PDOException $e) {
    error_log('Get Payslips Error: ' . $e->getMessage());
    ob_end_clean(); // Clean buffer on error
    echo json_encode(['success' => false, 'message' => 'Database query error during payslip retrieval.']);
} catch (Exception $e) {
    error_log('Get Payslips General Error: ' . $e->getMessage());
    ob_end_clean(); // Clean buffer on error
    echo json_encode(['success' => false, 'message' => 'An unexpected system error occurred.']);
}
?>
