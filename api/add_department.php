<?php
// FILENAME: employee/api/add_department.php
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

$dept_name = $data['department_name'] ?? null;
// Handle optional manager_id, set to NULL if empty
$manager_id = !empty($data['manager_id']) ? $data['manager_id'] : null;

if (empty($dept_name)) {
    log_action($pdo, $admin_id, 'DEPT_ADD_FAILED', "Admin attempted to add department with missing name.");
    echo json_encode(['success' => false, 'message' => 'Department name is required.']);
    exit;
}

try {
    $sql = "INSERT INTO departments (department_name, manager_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dept_name, $manager_id]);

    // --- LOGGING ---
    log_action($pdo, $admin_id, LOG_ACTION_DEPT_ADDED, "Added new department: '{$dept_name}'. Manager EID: " . ($manager_id ?? 'None') . ".");
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Department added successfully!']);

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
        log_action($pdo, $admin_id, 'DEPT_ADD_FAILED', "Failed to add department '{$dept_name}': Name already exists.");
        echo json_encode(['success' => false, 'message' => 'Error: This department name already exists.']);
    } else {
        error_log('Add Department Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'DEPT_ADD_ERROR', "DB Error adding department '{$dept_name}': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not add department.']);
    }
}
?>
