<?php
// FILENAME: employee/api/update_allowance.php
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

$allowance_id = $data['allowance_id'] ?? null;
$name = trim($data['name'] ?? '');
$type = $data['type'] ?? '';
$value = floatval($data['value'] ?? 0);
// Check if the key exists, even if it's explicitly set to false
$is_active = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
// Allow updating employee_id (optional, usually stays same)
$employee_id = array_key_exists('employee_id', $data) ? (!empty($data['employee_id']) ? intval($data['employee_id']) : null) : 'NO_CHANGE';

// Validation
if (empty($allowance_id) || empty($name) || empty($type) || $value <= 0) {
    log_action($pdo, $admin_id, 'ALLOWANCE_UPDATE_FAILED', "Admin attempted to update allowance ID {$allowance_id} with missing fields.");
    echo json_encode(['success' => false, 'message' => 'All required fields must be provided and value must be positive.']);
    exit;
}

if (!in_array($type, ['Fixed', 'Percentage'])) {
    log_action($pdo, $admin_id, 'ALLOWANCE_UPDATE_FAILED', "Admin attempted to update allowance ID {$allowance_id}: Invalid type '{$type}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid allowance type. Must be Fixed or Percentage.']);
    exit;
}

if ($type === 'Percentage' && ($value > 100 || $value < 0.01)) {
    log_action($pdo, $admin_id, 'ALLOWANCE_UPDATE_FAILED', "Admin attempted to update allowance ID {$allowance_id}: Invalid percentage value '{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Percentage value must be between 0.01 and 100.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($employee_id !== 'NO_CHANGE') {
        $sql = "UPDATE allowance_types SET name = ?, type = ?, value = ?, is_active = ?, employee_id = ? WHERE allowance_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $type, $value, $is_active, $employee_id, $allowance_id]);
    } else {
        $sql = "UPDATE allowance_types SET name = ?, type = ?, value = ?, is_active = ? WHERE allowance_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $type, $value, $is_active, $allowance_id]);
        
        // Retrieve current employee_id to know if we can have exclusions
        $stmt_curr = $pdo->prepare("SELECT employee_id FROM allowance_types WHERE allowance_id = ?");
        $stmt_curr->execute([$allowance_id]);
        $employee_id = $stmt_curr->fetchColumn(); 
    }
    
    // Manage Exclusions
    // Always clear existing exclusions first (simplest sync strategy)
    $pdo->prepare("DELETE FROM allowance_exclusions WHERE allowance_id = ?")->execute([$allowance_id]);
    
    // If Global (employee_id is NULL), add new exclusions
    if (($employee_id === null || $employee_id === false || $employee_id === '') && !empty($data['excluded_employees']) && is_array($data['excluded_employees'])) {
        $sql_ex = "INSERT INTO allowance_exclusions (allowance_id, employee_id) VALUES (?, ?)";
        $stmt_ex = $pdo->prepare($sql_ex);
        foreach ($data['excluded_employees'] as $ex_emp_id) {
             if (intval($ex_emp_id) > 0) {
                $stmt_ex->execute([$allowance_id, intval($ex_emp_id)]);
             }
        }
    }

    $pdo->commit();

    log_action($pdo, $admin_id, 'ALLOWANCE_UPDATE_SUCCESS', "Updated allowance ID {$allowance_id}. Name: '{$name}'.");
    echo json_encode(['success' => true, 'message' => 'Allowance updated successfully.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    log_action($pdo, $admin_id, 'ALLOWANCE_UPDATE_ERROR', "DB Error updating allowance ID {$allowance_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>