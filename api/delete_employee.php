<?php
// FILENAME: employee/api/delete_employee.php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // NEW: Include logging utility

$admin_id = $_SESSION['user_id']; // For logging

// Get the POST data from the JavaScript fetch
$data = json_decode(file_get_contents('php://input'), true);
$employee_id = $data['id'] ?? null;

if (empty($employee_id)) {
    log_action($pdo, $admin_id, 'EMPLOYEE_DELETE_FAILED', "Attempted delete with no EID provided.");
    echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
    exit;
}

$pdo->beginTransaction();

try {
    // Get employee name for better logging before deletion
    $stmt_name = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $stmt_name->execute([$employee_id]);
    $employee_info = $stmt_name->fetch(PDO::FETCH_ASSOC);
    $employee_name = $employee_info ? "{$employee_info['first_name']} {$employee_info['last_name']}" : "EID {$employee_id}";

    // --- 1. Delete from Related Tables (Foreign Key Cleanup) ---
    
    // List of tables that reference employee_id
    $related_tables = [
        'users',
        'attendance_logs',
        'payroll',
        'leave_requests',
        'leave_balances',
        'ca_transactions',
        'schedules',
        'standard_schedules',
        'dedicated_off_days',
        'employee_pay_history',
        'employee_journal'
    ];

    foreach ($related_tables as $table) {
        // We use a simple DELETE query for each table
        // Note: If a table doesn't exist, this might throw an exception, 
        // but based on the system analysis, these tables should exist.
        $sql_cleanup = "DELETE FROM {$table} WHERE employee_id = ?";
        $stmt_cleanup = $pdo->prepare($sql_cleanup);
        $stmt_cleanup->execute([$employee_id]);
    }

    // --- 2. Handle Audit Logs (Preserve history, detach user) ---
    // Instead of deleting audit logs, we set the employee_id to 0 (System/Deleted)
    // This prevents FK violation if audit_logs references employees
    $sql_audit = "UPDATE audit_logs SET employee_id = 0 WHERE employee_id = ?";
    $stmt_audit = $pdo->prepare($sql_audit);
    $stmt_audit->execute([$employee_id]);

    // --- 3. Delete the Employee Record ---
    $sql_employees = "DELETE FROM employees WHERE employee_id = ?";
    $stmt_employees = $pdo->prepare($sql_employees);
    $stmt_employees->execute([$employee_id]);

    $pdo->commit();

    if ($stmt_employees->rowCount() > 0) {
        // NEW: Log successful deletion
        log_action($pdo, $admin_id, 'EMPLOYEE_DELETED', "Employee {$employee_name} successfully deleted.");
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully!']);
    } else {
        log_action($pdo, $admin_id, 'EMPLOYEE_DELETE_FAILED', "Attempted delete of non-existent employee {$employee_name}.");
        echo json_encode(['success' => false, 'message' => 'Employee not found or already deleted.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Delete Employee Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'EMPLOYEE_DELETE_ERROR', "DB error deleting EID {$employee_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not delete employee.']);
}
?>
