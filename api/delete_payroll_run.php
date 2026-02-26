<?php
// FILENAME: employee/api/delete_payroll_run.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

$admin_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$admin_password = $data['admin_password'] ?? null;

// --- Password Verification (Unchanged) ---
if (empty($admin_password)) {
    echo json_encode(['success' => false, 'message' => 'Your password is required to delete the payroll run.']);
    exit;
}
try {
    $stmt_pass = $pdo->prepare("SELECT password_hash FROM users WHERE employee_id = ?");
    $stmt_pass->execute([$admin_id]);
    $admin_hash = $stmt_pass->fetchColumn();
    if (!$admin_hash || !password_verify($admin_password, $admin_hash)) {
        log_action($pdo, $admin_id, 'PAYROLL_AUTH_FAILED', "Incorrect password during payroll run deletion.");
        echo json_encode(['success' => false, 'message' => 'Password incorrect. Deletion cancelled.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Delete Run Auth DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during authentication.']);
    exit;
}
// --- END Password Verification ---


if (empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Find all Payroll IDs for the specified period
    $sql_find_pids = "SELECT payroll_id FROM payroll WHERE pay_period_start = ? AND pay_period_end = ?";
    $stmt_find_pids = $pdo->prepare($sql_find_pids);
    $stmt_find_pids->execute([$start_date, $end_date]);
    $pids = $stmt_find_pids->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pids)) {
        $pdo->rollBack();
        log_action($pdo, $admin_id, 'PAYROLL_RUN_DELETE_FAILED', "No payroll records found for period {$start_date} to {$end_date}.");
        echo json_encode(['success' => false, 'message' => 'No payroll records found for this period.']);
        exit;
    }

    $in_clause = implode(',', array_fill(0, count($pids), '?'));
    $reset_count = 0;

    // 2. CRITICAL FIX: Reset CA Transactions associated with these PIDs
    // Set deducted_in_payroll = FALSE, payroll_id = NULL, and RESTORE pending_amount to original_amount.
    $sql_reset_ca = "
        UPDATE ca_transactions 
        SET 
            deducted_in_payroll = FALSE, 
            payroll_id = NULL,
            pending_amount = original_amount
        WHERE payroll_id IN ({$in_clause})
    ";
    $stmt_reset_ca = $pdo->prepare($sql_reset_ca);
    $stmt_reset_ca->execute($pids);
    $reset_count = $stmt_reset_ca->rowCount();

    // 3. Delete the Payroll records
    $sql_delete_payroll = "DELETE FROM payroll WHERE payroll_id IN ({$in_clause})";
    $stmt_delete_payroll = $pdo->prepare($sql_delete_payroll);
    $stmt_delete_payroll->execute($pids);
    $deleted_count = $stmt_delete_payroll->rowCount();

    $pdo->commit();

    log_action($pdo, $admin_id, 'PAYROLL_RUN_DELETED', "Deleted payroll run for period {$start_date} to {$end_date}. Removed {$deleted_count} payslip records. Reset {$reset_count} CA/VALE transaction statuses.");

    echo json_encode([
        'success' => true,
        'message' => "Payroll run successfully deleted. {$deleted_count} payslips removed. {$reset_count} CA/VALE transactions reset for recovery."
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Delete Payroll Run DB Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'PAYROLL_DB_ERROR', "DB Error deleting run {$start_date} to {$end_date}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not delete run.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Delete Payroll Run General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'PAYROLL_GENERAL_ERROR', "General Error deleting run {$start_date} to {$end_date}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error during run deletion.']);
}
?>
