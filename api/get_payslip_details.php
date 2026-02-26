<?php
// FILENAME: employee/api/get_payslip_details.php
session_start();
header('Content-Type: application/json');
ob_start(); // Start output buffering

// Authorization check
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Used for logging

$payroll_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$is_admin_or_manager = in_array($user_role, ['HR Admin', 'Super Admin', 'Manager']);

if (empty($payroll_id)) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Payroll ID is required.']); exit;
}

try {
    // Fetch the main payroll record
    $sql = "
         SELECT p.*, e.first_name, e.last_name, e.job_title,
                p.pay_type_used, p.pay_rate_used, p.total_payable_hours, p.total_paid_leave_days
         FROM payroll p JOIN employees e ON p.employee_id = e.employee_id
         WHERE p.payroll_id = ?
     ";
    $stmt = $pdo->prepare($sql); $stmt->execute([$payroll_id]); $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payslip) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Payslip not found.']); exit;
    }

    // Authorization Check
    if ($payslip['employee_id'] != $user_id && !$is_admin_or_manager) {
        ob_end_clean(); log_action($pdo, $user_id, 'PAYSLIP_VIEW_DENIED', "EID {$user_id} denied view PID {$payroll_id}.");
        echo json_encode(['success' => false, 'message' => 'Forbidden: Cannot view this payslip.']); exit;
    }
    log_action($pdo, $user_id, 'PAYSLIP_VIEW_SUCCESS', "EID {$user_id} viewed PID {$payroll_id} for EID {$payslip['employee_id']}.");

    // Safely assign pay type/rate used during the run
    $pay_type = $payslip['pay_type_used'] ?? 'N/A';
    $pay_rate = $payslip['pay_rate_used'] ?? 0.00;
    $payslip['pay_type'] = $pay_type; // Ensure frontend has direct access
    $payslip['pay_rate'] = $pay_rate; // Ensure frontend has direct access

    // --- Fetch Allowances ---
    $active_allowances = []; $allowance_breakdown = []; $total_allowances_calc = 0.00;
    try {
        $stmt_allowances = $pdo->query("SELECT allowance_id, name, type, value, employee_id FROM allowance_types WHERE is_active = TRUE");
        $active_allowances = $stmt_allowances->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }

    // Fetch Allowance Exclusions
    $allowance_exclusions = [];
    try {
        $stmt_al_ex = $pdo->prepare("SELECT allowance_id FROM allowance_exclusions WHERE employee_id = ?");
        $stmt_al_ex->execute([$_SESSION['user_id']]); // Use logged in user? NO! Use payslip employee logic
        // Correction: Use $payslip['employee_id']
    } catch (PDOException $e) {}
    
    // Correctly fetch exclusions for the employee of the payslip
    try {
        $stmt_al_ex = $pdo->prepare("SELECT allowance_id FROM allowance_exclusions WHERE employee_id = ?");
        $stmt_al_ex->execute([$payslip['employee_id']]);
        $allowance_exclusions = $stmt_al_ex->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    // Fetch Active Global Deductions to Calculate Breakdown
    $active_deductions = []; $deduction_breakdown = []; $standard_deductions_total = 0.00;
    try {
        // FIX: Select employee_id to allow filtering + deduction_id for exclusion check
        $stmt_deductions = $pdo->query("SELECT deduction_id, name, type, value, employee_id FROM deduction_types WHERE is_active = TRUE");
        $active_deductions = $stmt_deductions->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* ignore if table missing or column error occurs */ }

    // Fetch Exclusions
    $exclusions = [];
    try {
        $stmt_ex = $pdo->prepare("SELECT deduction_id FROM deduction_exclusions WHERE employee_id = ?");
        $stmt_ex->execute([$payslip['employee_id']]);
        $exclusions = $stmt_ex->fetchAll(PDO::FETCH_COLUMN); // List of deduction_ids excluded for this user
    } catch (PDOException $e) {}

    $gross_pay = (float)$payslip['gross_pay'];
    $stored_allowances = isset($payslip['allowances']) ? (float)$payslip['allowances'] : 0.00;
    $base_pay = $gross_pay - $stored_allowances; // Deduce base pay for calculation
    $employee_id = $payslip['employee_id'];

    foreach ($active_allowances as $allowance_type) {
        $is_global = empty($allowance_type['employee_id']);
        $is_for_me = !$is_global && $allowance_type['employee_id'] == $employee_id;
        $is_excluded = in_array($allowance_type['allowance_id'], $allowance_exclusions);

        if (($is_global || $is_for_me) && !$is_excluded) {
            $val = (float)$allowance_type['value']; $amt = 0.00;
            if ($allowance_type['type'] === 'Fixed') { $amt = $val; }
            elseif ($allowance_type['type'] === 'Percentage') { $amt = $base_pay * ($val / 100); } // Based on Base Pay
            $amt = round($amt, 2);
            $total_allowances_calc += $amt;
            $allowance_breakdown[] = [ 'name' => $allowance_type['name'], 'amount' => $amt, 'value' => $val, 'type' => $allowance_type['type'] ];
        }
    }


    foreach ($active_deductions as $deduction_type) {
        // Filter: Apply if Global (employee_id is NULL) OR Specific to this employee
        $is_global = empty($deduction_type['employee_id']);
        $is_for_me = !$is_global && $deduction_type['employee_id'] == $employee_id;
        
        // Excluded?
        $is_excluded = in_array($deduction_type['deduction_id'], $exclusions);

        if (($is_global || $is_for_me) && !$is_excluded) {
            $deduction_value = (float)$deduction_type['value']; $amount = 0.00;
            if ($deduction_type['type'] === 'Fixed') { $amount = $deduction_value; }
            elseif ($deduction_type['type'] === 'Percentage') { $amount = $gross_pay * ($deduction_value / 100); }
            $amount = round($amount, 2); $standard_deductions_total += $amount;
            $deduction_breakdown[] = [ 'name' => $deduction_type['name'], 'amount' => $amount, 'value' => $deduction_value, 'type' => $deduction_type['type'] ];
        }
    }

    // Inject Dynamic CA/VALE Deduction
    $total_recorded_deductions = (float)$payslip['deductions'];
    $ca_amount_deducted = round($total_recorded_deductions - $standard_deductions_total, 2);
    if ($ca_amount_deducted > 0.005) { // Use a small tolerance for float comparison
        $ca_deduction_name = $_SESSION['settings']['system_ca_deduction_name'] ?? 'Cash Advance Deduction';
        $deduction_breakdown[] = [ 'name' => $ca_deduction_name, 'amount' => $ca_amount_deducted, 'value' => $ca_amount_deducted, 'type' => 'System Fixed' ];
    }

    // Combine data
    $response_data = [ 'payslip' => $payslip, 'deduction_breakdown' => $deduction_breakdown, 'allowance_breakdown' => $allowance_breakdown ];

    ob_end_clean(); // Clean buffer
    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (PDOException $e) {
    ob_end_clean(); // Clean buffer on error
    $error_message = 'Database error: ' . $e->errorInfo[2] ?? $e->getMessage();
    error_log('Get Payslip Details Error: ' . $e->getMessage());
    log_action($pdo, $user_id, 'PAYSLIP_VIEW_ERROR', "DB error PID {$payroll_id}: " . $error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
}
?>
