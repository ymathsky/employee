<?php
// FILENAME: employee/api/update_payroll.php
session_start();
header('Content-Type: application/json');

// Admin-only access (or Manager, check scope if needed later)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';
$admin_id = $_SESSION['user_id']; // Use ID of logged-in user for logging

$data = json_decode(file_get_contents('php://input'), true);

$employee_id = $data['employee_id'] ?? null;
$pay_type = $data['pay_type'] ?? null;
$pay_rate = $data['pay_rate'] ?? null;
$effective_start_date = date('Y-m-d'); // Effective date is always today when using this API

if (empty($employee_id) || empty($pay_type) || $pay_rate === null) {
    log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Attempt to set rate for EID {$employee_id}: Missing fields.");
    echo json_encode(['success' => false, 'message' => 'Employee ID, pay type, and pay rate are required.']);
    exit;
}

// MODIFIED: Updated validation for pay types
$valid_pay_types = ['Hourly', 'Daily', 'Fix Rate'];
if (!in_array($pay_type, $valid_pay_types)) {
    log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Attempt to set rate for EID {$employee_id}: Invalid type '{$pay_type}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid pay type. Must be Hourly, Daily, or Fix Rate.']);
    exit;
}

if (!is_numeric($pay_rate) || $pay_rate < 0) {
    log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Attempt to set rate for EID {$employee_id}: Invalid rate '{$pay_rate}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid pay rate. Must be a non-negative number.']);
    exit;
}

// --- OPTIONAL: Manager Scope Check ---
// If you want to restrict managers to only update their own team:
if ($_SESSION['role'] === 'Manager') {
    try {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$admin_id]);
        $manager_department = $stmt_dept->fetchColumn();

        $stmt_target_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_target_dept->execute([$employee_id]);
        $target_department = $stmt_target_dept->fetchColumn();

        if (empty($manager_department) || $manager_department !== $target_department) {
            log_action($pdo, $admin_id, 'PAY_RATE_ACTION_DENIED', "Manager tried to set rate for EID {$employee_id} outside their dept.");
            echo json_encode(['success' => false, 'message' => 'Forbidden: You can only set rates for employees in your department.']);
            exit;
        }
    } catch (PDOException $e) {
        log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "DB Error during manager scope check for EID {$employee_id}: " . $e->getMessage());
        error_log('Manager Scope Check Error (update_payroll): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error during permission check.']);
        exit;
    }
}
// --- End Optional Manager Scope Check ---


try {
    // Insert into employee_pay_history table
    $sql = "INSERT INTO employee_pay_history (employee_id, pay_type, pay_rate, effective_start_date)
             VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $pay_type, $pay_rate, $effective_start_date]);

    if ($stmt->rowCount() > 0) {
        log_action($pdo, $admin_id, LOG_ACTION_PAY_RATE_UPDATED, "Set new rate for EID {$employee_id}: {$pay_type} @ {$pay_rate} effective {$effective_start_date}.");
        echo json_encode(['success' => true, 'message' => "Payroll details updated successfully, effective {$effective_start_date}!"]);
    } else {
        // This case might not be reachable if using INSERT without ON DUPLICATE KEY
        log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Failed to insert rate for EID {$employee_id} (rowCount=0).");
        echo json_encode(['success' => false, 'message' => 'Failed to record pay history. No rows affected.']);
    }

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Handle unique constraint (employee_id, effective_start_date)
        log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Failed to set rate for EID {$employee_id}: Rate already exists for today ({$effective_start_date}).");
        echo json_encode(['success' => false, 'message' => 'Pay rate for this employee has already been set for today. Edit the existing record in Pay History Management if needed.']);
    } else {
        log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "DB Error setting rate for EID {$employee_id}: " . $e->getMessage());
        error_log('Update Pay History Error (from update_payroll.php): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not record pay history.']);
    }
}
?>

