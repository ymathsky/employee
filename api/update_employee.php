<?php
// FILENAME: employee/api/update_employee.php
session_start();
header('Content-Type: application/json');
ob_start(); // Start output buffering

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // For APP_ROLES
require_once __DIR__ . '/../config/utils.php'; // For logging

$admin_id = $_SESSION['user_id'];

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// --- Validation ---
if (empty($data['employee_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Employee ID is missing.']);
    exit;
}

$employee_id = $data['employee_id'];

// Validate Role
if (empty($data['role']) || !in_array($data['role'], APP_ROLES)) {
    ob_end_clean();
    log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_FAILED', "Invalid role '{$data['role']}' for EID {$employee_id}.");
    echo json_encode(['success' => false, 'message' => 'Invalid system role provided.']);
    exit;
}

// Validate Department existence (Multi-select support)
$departments_input = $data['department'];
$final_department_string = '';

if (is_array($departments_input)) {
    // Multi-select case
    $valid_departments = [];
    foreach ($departments_input as $dept) {
        if (empty($dept)) continue;
        // Check each department
        $stmt_dept = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_name = ?");
        $stmt_dept->execute([$dept]);
        if ($stmt_dept->fetchColumn() > 0) {
            $valid_departments[] = $dept;
        } else {
            // Invalid department found
            ob_end_clean();
            log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_FAILED', "Invalid department '{$dept}' for EID {$employee_id}.");
            echo json_encode(['success' => false, 'message' => 'Error: Department "' . htmlspecialchars($dept) . '" does not exist.']);
            exit;
        }
    }
    if (empty($valid_departments)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'At least one valid department is required.']);
        exit;
    }
    $final_department_string = implode(', ', $valid_departments);

} else {
    // Single string case (Legacy/Fallback)
    $department = $departments_input;
    try {
        $stmt_dept = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_name = ?");
        $stmt_dept->execute([$department]);
        if ($stmt_dept->fetchColumn() === 0) {
            ob_end_clean();
            log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_FAILED', "Invalid department '{$department}' for EID {$employee_id}.");
            echo json_encode(['success' => false, 'message' => 'Error: Department "' . htmlspecialchars($department) . '" does not exist.']);
            exit;
        }
        $final_department_string = $department;
    } catch (PDOException $e) {
        ob_end_clean();
        error_log('Department validation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error during department validation.']);
        exit;
    }
}
// --- End Validation ---

$pdo->beginTransaction();

try {
    // Step 1: Update employees table
    // Ensure email is stored in lowercase
    $email_lower = strtolower(trim($data['email']));
    $status = $data['status'] ?? 'Active';
    $hired_date = !empty($data['hiredDate']) ? $data['hiredDate'] : null;

    $is_flexible = !empty($data['isFlexible']) ? 1 : 0;

    $sql_employee = "UPDATE employees SET first_name = ?, last_name = ?, email = ?, job_title = ?, department = ?, status = ?, hired_date = ?, is_flexible_schedule = ? WHERE employee_id = ?";
    $stmt_employee = $pdo->prepare($sql_employee);
    $stmt_employee->execute([
        $data['firstName'],
        $data['lastName'],
        $email_lower,
        $data['jobTitle'],
        $final_department_string,
        $status,
        $hired_date,
        $is_flexible,
        $employee_id
    ]);

    // Step 2: Update users table
    $sql_user = "UPDATE users SET role = ? WHERE employee_id = ?";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$data['role'], $employee_id]);

    // *** REMOVED: Step 4 (Update leave balances) ***

    // Commit all changes
    $pdo->commit();

    log_action($pdo, $admin_id, 'EMPLOYEE_UPDATED', "Employee details updated for EID {$employee_id} by Admin EID {$admin_id}.");

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Employee details updated successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    ob_end_clean();

    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_FAILED', "Duplicate entry (e.g., email) for EID {$employee_id}.");
        echo json_encode(['success' => false, 'message' => 'Error: A duplicate entry already exists (e.g., email).']);
    } else {
        error_log('Update Employee Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_ERROR', "DB error for EID {$employee_id}: " . $e->getMessage());
        // Return actual error for debugging
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) { // Catch any other exceptions
    if($pdo->inTransaction()){ $pdo->rollBack(); }
    ob_end_clean();
    error_log('Update Employee General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'EMPLOYEE_UPDATE_ERROR', "General error for EID {$employee_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected system error occurred.']);
}
?>

