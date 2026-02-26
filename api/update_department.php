<?php
// FILENAME: employee/api/update_department.php
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
$dept_name = $data['department_name'] ?? null;
// Handle optional manager_id, set to NULL if empty
$manager_id = !empty($data['manager_id']) ? $data['manager_id'] : null;

if (empty($dept_id) || empty($dept_name)) {
    log_action($pdo, $admin_id, 'DEPT_UPDATE_FAILED', "Admin attempted to update department with missing ID or name.");
    echo json_encode(['success' => false, 'message' => 'Department ID and name are required.']);
    exit;
}

try {
    $sql = "UPDATE departments SET department_name = ?, manager_id = ? WHERE department_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dept_name, $manager_id, $dept_id]);

    if ($stmt->rowCount() > 0) {
        // --- LOGGING ---
        log_action($pdo, $admin_id, LOG_ACTION_DEPT_UPDATED, "Updated department ID {$dept_id} to '{$dept_name}'. New Manager EID: " . ($manager_id ?? 'None') . ".");
        // --- END LOGGING ---
        echo json_encode(['success' => true, 'message' => 'Department updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made or department not found.']);
    }

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
        log_action($pdo, $admin_id, 'DEPT_UPDATE_FAILED', "Failed to update department ID {$dept_id}: Name '{$dept_name}' already exists.");
        echo json_encode(['success' => false, 'message' => 'Error: This department name already exists.']);
    } else {
        error_log('Update Department Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'DEPT_UPDATE_ERROR', "DB Error updating department ID {$dept_id}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not update department.']);
    }
}
?>
