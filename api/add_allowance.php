<?php
// FILENAME: employee/api/add_allowance.php
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
    log_action($pdo, $admin_id, 'ALLOWANCE_ADD_FAILED', "Admin attempted to add allowance with missing fields: Name='{$name}', Type='{$type}', Value='{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Name, type, and a positive value are required.']);
    exit;
}

if (!in_array($type, ['Fixed', 'Percentage'])) {
    log_action($pdo, $admin_id, 'ALLOWANCE_ADD_FAILED', "Admin attempted to add allowance '{$name}': Invalid type '{$type}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid allowance type. Must be Fixed or Percentage.']);
    exit;
}

if ($type === 'Percentage' && ($value > 100 || $value < 0.01)) {
    log_action($pdo, $admin_id, 'ALLOWANCE_ADD_FAILED', "Admin attempted to add allowance '{$name}': Invalid percentage value '{$value}'.");
    echo json_encode(['success' => false, 'message' => 'Percentage value must be between 0.01 and 100.']);
    exit;
}

// Check for duplicates (Same Name AND Same Target)
try {
    $sql_check = "SELECT COUNT(*) FROM allowance_types WHERE name = ? AND (employee_id = ? OR (employee_id IS NULL AND ? IS NULL))";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$name, $employee_id, $employee_id]);
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'An allowance with this name already exists for this target.']);
        exit;
    }

    $pdo->beginTransaction();

    $sql = "INSERT INTO allowance_types (name, type, value, is_active, employee_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $type, $value, $is_active, $employee_id]);
    $new_allowance_id = $pdo->lastInsertId();

    // Handle Exclusions
    if (($employee_id === null) && !empty($data['excluded_employees']) && is_array($data['excluded_employees'])) {
        $sql_ex = "INSERT INTO allowance_exclusions (allowance_id, employee_id) VALUES (?, ?)";
        $stmt_ex = $pdo->prepare($sql_ex);
        foreach ($data['excluded_employees'] as $ex_emp_id) {
             if (intval($ex_emp_id) > 0) {
                $stmt_ex->execute([$new_allowance_id, intval($ex_emp_id)]);
             }
        }
    }

    $pdo->commit();

    log_action($pdo, $admin_id, 'ALLOWANCE_ADDED', "Added allowance '{$name}' (ID: {$new_allowance_id}). Type: {$type}, Value: {$value}.");
    echo json_encode(['success' => true, 'message' => 'Allowance added successfully.']);

} catch (PDOException $e) {
    $pdo->rollBack(); 
    log_action($pdo, $admin_id, 'ALLOWANCE_ADD_ERROR', "DB Error adding allowance '{$name}': " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>