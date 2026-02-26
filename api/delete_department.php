<?php
// FILENAME: employee/api/delete_department.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Include for logging

$admin_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$dept_id = $data['department_id'] ?? null;

if (empty($dept_id)) {
    log_action($pdo, $admin_id, 'DEPT_DELETE_FAILED', "Admin attempted to delete department with missing ID.");
    echo json_encode(['success' => false, 'message' => 'Department ID is required.']);
    exit;
}

$pdo->beginTransaction();

try {
    // Get info before deleting for logging
    $stmt_find = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
    $stmt_find->execute([$dept_id]);
    $department = $stmt_find->fetch();

    if ($department) {
        $dept_name = $department['department_name'];

        // Step 1: Unassign employees from this department.
        $sql_update_employees = "UPDATE employees SET department = NULL WHERE department = ?";
        $stmt_update_employees = $pdo->prepare($sql_update_employees);
        $stmt_update_employees->execute([$dept_name]);
        $unassigned_count = $stmt_update_employees->rowCount();

        // Step 2: Delete the department.
        $sql_delete = "DELETE FROM departments WHERE department_id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$dept_id]);
        $deleted_count = $stmt_delete->rowCount();

        $pdo->commit();

        if ($deleted_count > 0) {
            // --- LOGGING ---
            log_action($pdo, $admin_id, LOG_ACTION_DEPT_DELETED, "Deleted department '{$dept_name}' (ID {$dept_id}). Unassigned {$unassigned_count} employees.");
            // --- END LOGGING ---
            echo json_encode(['success' => true, 'message' => 'Department deleted and employees unassigned.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Department not found or already deleted.']);
        }
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Department not found.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Delete Department Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'DEPT_DELETE_ERROR', "DB Error deleting department ID {$dept_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not delete department.']);
}
?>
