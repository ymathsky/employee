<?php
// FILENAME: employee/api/add_deduction.php
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

$name = trim($data['name'] ?? '');
$type = $data['type'] ?? '';
$value = floatval($data['value'] ?? 0);
$employee_id = !empty($data['employee_id']) ? intval($data['employee_id']) : null;
$is_active = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

// Validation
if (empty($name) || empty($type) || $value <= 0) {
    log_action($pdo, $admin_id, 'DEDUCTION_ADD_FAILED', "Admin attempted to add deduction with missing fields: Name='{$name}', Type='{$type}', Value='{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Name, type, and a positive value are required.']);
    exit;
}

if (!in_array($type, ['Fixed', 'Percentage'])) {
    log_action($pdo, $admin_id, 'DEDUCTION_ADD_FAILED', "Admin attempted to add deduction '{$name}': Invalid type '{$type}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid deduction type. Must be Fixed or Percentage.']);
    exit;
}

if ($type === 'Percentage' && ($value > 100 || $value < 0.01)) {
    log_action($pdo, $admin_id, 'DEDUCTION_ADD_FAILED', "Admin attempted to add deduction '{$name}': Invalid percentage value '{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Percentage value must be between 0.01 and 100.']);
    exit;
}

// Check for duplicates (Same Name AND Same Target)
try {
    $sql_check = "SELECT COUNT(*) FROM deduction_types WHERE name = ? AND (employee_id = ? OR (employee_id IS NULL AND ? IS NULL))";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$name, $employee_id, $employee_id]);
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'A deduction with this name already exists for this target.']);
        exit;
    }
} catch (PDOException $e) {
    // Ignore check error, proceed to insert attempt
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO deduction_types (name, type, value, is_active, employee_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $type, $value, $is_active, $employee_id]);
    $deduction_id = $pdo->lastInsertId();

    // Handle Exclusions (Only if Global)
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
    $target = $employee_id ? "Employee ID {$employee_id}" : "Global";
    log_action($pdo, $admin_id, 'DEDUCTION_ADDED', "Added new deduction '{$name}' for {$target}. Type: {$type}, Value: {$value}.");
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Deduction added successfully!']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) { // 1062 = Duplicate entry
        log_action($pdo, $admin_id, 'DEDUCTION_ADD_FAILED', "Failed to add deduction '{$name}': Name already exists.");
        echo json_encode(['success' => false, 'message' => 'Error: A deduction with this name already exists.']);
    } else {
        error_log('Add Deduction Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'DEDUCTION_ADD_ERROR', "DB Error adding deduction '{$name}': " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not add deduction.']);
    }
}
?>
