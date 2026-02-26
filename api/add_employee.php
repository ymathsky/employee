<?php
// FILENAME: employee/api/add_employee.php
session_start();
header('Content-Type: application/json');
ob_start(); // Start output buffering

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    ob_end_clean(); // Clean buffer before output
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // For APP_ROLES
require_once __DIR__ . '/../config/utils.php'; // For logging

$admin_id = $_SESSION['user_id']; // For logging

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// --- Comprehensive Validation ---
$required_fields = ['firstName', 'lastName', 'email', 'jobTitle', 'department', 'role', 'password', 'confirmPassword'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        ob_end_clean(); // Clean buffer before output
        log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Attempted creation with missing field: {$field}.");
        echo json_encode(['success' => false, 'message' => "Field '{$field}' is required."]);
        exit;
    }
}

// Server-side password validation
if ($data['password'] !== $data['confirmPassword']) {
    ob_end_clean(); // Clean buffer before output
    log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Password mismatch for {$data['firstName']} {$data['lastName']}.");
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}
if (strlen($data['password']) < 8) {
    ob_end_clean(); // Clean buffer before output
    log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Password too short for {$data['firstName']} {$data['lastName']}.");
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// Validate Role against APP_ROLES
if (!in_array($data['role'], APP_ROLES)) {
    ob_end_clean(); // Clean buffer before output
    log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Invalid role '{$data['role']}'.");
    echo json_encode(['success' => false, 'message' => 'Invalid system role provided.']);
    exit;
}

// Validate Department existence
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
            log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Invalid department '{$dept}'.");
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
    // Single string case (Legacy)
    $department = $departments_input;
    try {
        $stmt_dept = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_name = ?");
        $stmt_dept->execute([$department]);
        if ($stmt_dept->fetchColumn() === 0) {
            ob_end_clean(); // Clean buffer before output
            log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Invalid department '{$department}'.");
            echo json_encode(['success' => false, 'message' => 'Error: Department "' . htmlspecialchars($department) . '" does not exist.']);
            exit;
        }
        $final_department_string = $department;
    } catch (PDOException $e) {
        ob_end_clean(); // Clean buffer before output
        error_log('Department validation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error during department validation.']);
        exit;
    }
}
// --- END VALIDATION ---

// Generate a smart username (firstname.lastname) and ensure uniqueness
$clean_first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['firstName']));
$clean_last = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['lastName']));
$base_username = $clean_first . '.' . $clean_last;

// Fallback if names are empty (unlikely due to validation)
if (empty($base_username)) {
    $base_username = 'user' . time();
}

$username = $base_username;
$counter = 1;

// Check for duplicates and append number if necessary
while (true) {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt_check->execute([$username]);
    if ($stmt_check->fetchColumn() == 0) {
        break; // Username is unique
    }
    $username = $base_username . $counter;
    $counter++;
}

$pdo->beginTransaction();

try {
    // Step 1: Insert into employees table
    // Ensure email is stored in lowercase
    $email_lower = strtolower(trim($data['email']));
    $status = $data['status'] ?? 'Active'; // Default to Active
    $hired_date = !empty($data['hiredDate']) ? $data['hiredDate'] : null;
    // New Flexible Schedule Field
    $is_flexible = !empty($data['isFlexible']) ? 1 : 0;

    $sql_employee = "INSERT INTO employees (first_name, last_name, email, job_title, department, status, hired_date, is_flexible_schedule) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_employee = $pdo->prepare($sql_employee);
    // Note: Re-using logic from above where $final_department_string was calculated
    $stmt_employee->execute([ 
        $data['firstName'], 
        $data['lastName'], 
        $email_lower, 
        $data['jobTitle'], 
        $final_department_string, 
        $status, 
        $hired_date,
        $is_flexible 
    ]);
    $new_employee_id = $pdo->lastInsertId();

    // Step 2: Insert into users table
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $role = $data['role'];
    $sql_user = "INSERT INTO users (employee_id, username, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([ $new_employee_id, $username, $password_hash, $role ]);

    // *** REMOVED: Step 3 (Provision default leave balances) ***

    $pdo->commit();

    log_action($pdo, $admin_id, 'EMPLOYEE_CREATED', "New employee {$data['firstName']} {$data['lastName']} (EID: {$new_employee_id}, Role: {$role}) created by Admin EID {$admin_id}.");

    ob_end_clean();
    // *** MODIFIED: Updated success message ***
    echo json_encode(['success' => true, 'message' => 'Employee added successfully! (Username: ' . $username . ')']);

} catch (PDOException $e) {
    $pdo->rollBack();
    ob_end_clean();

    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        $error_msg = 'Error: A duplicate entry already exists.';
        if (strpos($e->getMessage(), 'users.username') !== false) { $error_msg = 'Error: Username (' . $username . ') already exists. Try changing name slightly.'; }
        elseif (strpos($e->getMessage(), 'employees.email') !== false) { $error_msg = 'Error: Email address already exists.'; }
        log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_FAILED', "Duplicate: {$error_msg}");
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        error_log('Add Employee Error: ' . $e->getMessage());
        log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_ERROR', "DB error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not add employee.']);
    }
} catch (Exception $e) { // Catch any other exceptions
    if($pdo->inTransaction()){ $pdo->rollBack(); }
    ob_end_clean();
    error_log('Add Employee General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'EMPLOYEE_CREATE_ERROR', "General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected system error occurred.']);
}
?>

