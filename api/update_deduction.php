<?php
// FILENAME: employee/api/update_deduction.php
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

$deduction_id = $data['deduction_id'] ?? null;
$name = trim($data['name'] ?? '');
$type = $data['type'] ?? '';
$value = floatval($data['value'] ?? 0);
// Check if the key exists, even if it's explicitly set to false
$is_active = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
// Allow updating employee_id (optional, usually stays same)
$employee_id = array_key_exists('employee_id', $data) ? (!empty($data['employee_id']) ? intval($data['employee_id']) : null) : 'NO_CHANGE';

// Validation
if (empty($deduction_id) || empty($name) || empty($type) || $value <= 0) {
    log_action($pdo, $admin_id, 'DEDUCTION_UPDATE_FAILED', "Admin attempted to update deduction ID {$deduction_id} with missing fields.");
    echo json_encode(['success' => false, 'message' => 'All required fields must be provided and value must be positive.']);
    exit;
}

if (!in_array($type, ['Fixed', 'Percentage'])) {
    log_action($pdo, $admin_id, 'DEDUCTION_UPDATE_FAILED', "Admin attempted to update deduction ID {$deduction_id}: Invalid type '{$type}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid deduction type. Must be Fixed or Percentage.']);
    exit;
}

if ($type === 'Percentage' && ($value > 100 || $value < 0.01)) {
    log_action($pdo, $admin_id, 'DEDUCTION_UPDATE_FAILED', "Admin attempted to update deduction ID {$deduction_id}: Invalid percentage value '{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Percentage value must be between 0.01 and 100.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($employee_id !== 'NO_CHANGE') {
        $sql = "UPDATE deduction_types SET name = ?, type = ?, value = ?, is_active = ?, employee_id = ? WHERE deduction_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $type, $value, $is_active, $employee_id, $deduction_id]);
    } else {
        $sql = "UPDATE deduction_types SET name = ?, type = ?, value = ?, is_active = ? WHERE deduction_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $type, $value, $is_active, $deduction_id]);
        
        // Retrieve current employee_id to know if we can have exclusions
        $stmt_curr = $pdo->prepare("SELECT employee_id FROM deduction_types WHERE deduction_id = ?");
        $stmt_curr->execute([$deduction_id]);
        $employee_id = $stmt_curr->fetchColumn(); 
    }
    
    // Manage Exclusions
    // Always clear existing exclusions first (simplest sync strategy)
    $pdo->prepare("DELETE FROM deduction_exclusions WHERE deduction_id = ?")->execute([$deduction_id]);
    
    // If Global (employee_id is NULL), add new exclusions
    // DEBUG LOGGING
    // error_log("Update Exclusions Debug: EmpID=" . var_export($employee_id, true) . ", Data=" . var_export($data['excluded_employees'], true));
    
    // PHP's fetchColumn might return FALSE if null? 
    // No, returns NULL. But check loose equality just in case.
    if (($employee_id === null || $employee_id === false || $employee_id === '') && !empty($data['excluded_employees']) && is_array($data['excluded_employees'])) {
        $sql_ex = "INSERT INTO deduction_exclusions (deduction_id, employee_id) VALUES (?, ?)";
        $stmt_ex = $pdo->prepare($sql_ex);
        foreach ($data['excluded_employees'] as $ex_emp_id) {
             if (intval($ex_emp_id) > 0) {
                $stmt_ex->execute([$deduction_id, intval($ex_emp_id)]);
             }
        }
    }

    $pdo->commit();

    // --- LOGGING ---
    log_action($pdo, $admin_id, 'DEDUCTION_UPDATED', "Updated deduction ID {$deduction_id} to '{$name}'. Type: {$type}, Value: {$value}, Active: " . ($is_active ? 'Yes' : 'No') . ".");
    // --- END LOGGING ---
    echo json_encode(['success' => true, 'message' => 'Deduction updated successfully!']);

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
        log_action($pdo, $admin_id, 'DEDUCTION_UPDATE_FAILED', "Failed to update deduction ID {$deduction_id}: Name '{$name}' already exists.");
        echo json_encode(['success' => false, 'message' => 'Error: A deduction with this name already exists.']);
    } else {
        error_log('Update Deduction Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'DEDUCTION_UPDATE_ERROR', "DB Error updating deduction ID {$deduction_id}: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not update deduction.']);
    }
}
?>
