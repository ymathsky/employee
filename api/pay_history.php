<?php
// FILENAME: employee/api/pay_history.php
session_start();
header('Content-Type: application/json');
ob_start(); // Start output buffering

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Include for logging AND getEmployeePayRateOnDate

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? null;
$employee_id_query = $_GET['employee_id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

// Helper: Validate shared fields
function validate_pay_data($data) {
    $employee_id = $data['employee_id'] ?? null;
    $pay_type = $data['pay_type'] ?? null;
    $pay_rate = $data['pay_rate'] ?? null;
    $effective_start_date = $data['effective_start_date'] ?? null;
    if (empty($employee_id) || empty($pay_type) || $pay_rate === null || empty($effective_start_date)) { return ['success' => false, 'message' => 'Employee ID, pay type, rate, and effective date are required.']; }
    // MODIFIED: Updated valid pay types
    $valid_pay_types = ['Hourly', 'Daily', 'Fix Rate'];
    if (!in_array($pay_type, $valid_pay_types)) { return ['success' => false, 'message' => 'Invalid pay type. Must be Hourly, Daily, or Fix Rate.']; }
    if (!is_numeric($pay_rate) || $pay_rate < 0) { return ['success' => false, 'message' => 'Invalid pay rate. Must be a non-negative number.']; }
    // Simple date format check
    if (!DateTime::createFromFormat('Y-m-d', $effective_start_date)) { return ['success' => false, 'message' => 'Invalid date format. Must be YYYY-MM-DD.']; }
    return ['success' => true];
}


try {
    switch ($action) {

        // --- NEW ACTION: List Employees with Current Pay ---
        case 'list_employees':
            // Step 1: Get base employee details
            $sql_emp = "SELECT e.employee_id, e.first_name, e.last_name, e.department, e.job_title
                         FROM employees e
                         ORDER BY e.last_name, e.first_name";
            $stmt_emp = $pdo->query($sql_emp);
            $employees = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

            $today = date('Y-m-d');

            // Step 2: Loop through and attach current pay rate from history
            foreach ($employees as &$emp) { // Use reference to modify array directly
                $pay_data = getEmployeePayRateOnDate($pdo, $emp['employee_id'], $today);
                $emp['pay_type'] = $pay_data['pay_type'] ?? 'N/A';
                $emp['pay_rate'] = $pay_data['pay_rate'] ?? 0.00;
            }
            unset($emp); // Unset reference after loop

            ob_end_clean(); // Clean buffer
            echo json_encode(['success' => true, 'data' => $employees]);
            break;
        // --- END NEW ACTION ---

        case 'get':
            // GET: Fetch all pay history for a specific employee
            $employee_id = $employee_id_query;
            if (empty($employee_id)) {
                ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Employee ID required for retrieval.']); exit;
            }
            $sql = "SELECT history_id, pay_type, pay_rate, effective_start_date FROM employee_pay_history WHERE employee_id = ? ORDER BY effective_start_date DESC";
            $stmt = $pdo->prepare($sql); $stmt->execute([$employee_id]); $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ob_end_clean(); echo json_encode(['success' => true, 'data' => $history]);
            break;

        case 'add':
            // POST: Add a new pay history entry
            $validation = validate_pay_data($data); // Uses updated validation
            if (!$validation['success']) { ob_end_clean(); echo json_encode($validation); exit; }
            $sql = "INSERT INTO employee_pay_history (employee_id, pay_type, pay_rate, effective_start_date) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql); $stmt->execute([$data['employee_id'], $data['pay_type'], $data['pay_rate'], $data['effective_start_date']]);
            log_action($pdo, $admin_id, LOG_ACTION_PAY_RATE_UPDATED, "Added new pay rate for EID {$data['employee_id']}: {$data['pay_type']} @ {$data['pay_rate']} effective {$data['effective_start_date']}.");
            ob_end_clean(); echo json_encode(['success' => true, 'message' => 'Pay rate added successfully!']);
            break;

        case 'update':
            // POST: Update an existing pay history entry
            $validation = validate_pay_data($data); // Uses updated validation
            if (!$validation['success']) { ob_end_clean(); echo json_encode($validation); exit; }
            if (empty($data['history_id'])) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'History ID is required for update.']); exit; }
            $sql = "UPDATE employee_pay_history SET pay_type = ?, pay_rate = ?, effective_start_date = ? WHERE history_id = ? AND employee_id = ?";
            $stmt = $pdo->prepare($sql); $stmt->execute([ $data['pay_type'], $data['pay_rate'], $data['effective_start_date'], $data['history_id'], $data['employee_id'] ]);
            log_action($pdo, $admin_id, LOG_ACTION_PAY_RATE_UPDATED, "Updated pay rate (History ID {$data['history_id']}) for EID {$data['employee_id']}: {$data['pay_type']} @ {$data['pay_rate']} effective {$data['effective_start_date']}.");
            ob_end_clean(); echo json_encode(['success' => true, 'message' => 'Pay rate updated successfully!']);
            break;

        case 'delete':
            // POST: Delete a pay history entry
            $history_id = $data['history_id'] ?? null;
            if (empty($history_id)) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'History ID is required for deletion.']); exit; }
            // Get details before deleting for logging
            $stmt_info = $pdo->prepare("SELECT employee_id, pay_rate, effective_start_date FROM employee_pay_history WHERE history_id = ?"); $stmt_info->execute([$history_id]); $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $sql = "DELETE FROM employee_pay_history WHERE history_id = ?";
            $stmt = $pdo->prepare($sql); $stmt->execute([$history_id]);
            if ($stmt->rowCount() > 0) {
                if ($info) { log_action($pdo, $admin_id, LOG_ACTION_PAY_RATE_DELETED, "Deleted pay rate (History ID {$history_id}) for EID {$info['employee_id']}. Rate: {$info['pay_rate']}, Effective: {$info['effective_start_date']}."); }
                ob_end_clean(); echo json_encode(['success' => true, 'message' => 'Pay rate record deleted.']);
            } else { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Record not found or already deleted.']); }
            break;

        default:
            ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Invalid API action.']); break;
    }

} catch (PDOException $e) {
    ob_end_clean(); // Clean buffer on error
    $error_message = 'Database error.';
    $log_details = "Action: {$action}, EID: " . ($data['employee_id'] ?? $employee_id_query ?? 'N/A') . ", HID: " . ($data['history_id'] ?? 'N/A') . ". Error: " . $e->getMessage();
    if ($e->errorInfo[1] == 1062) { $error_message = 'Error: A pay rate for this employee already exists for that effective date.'; log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "Duplicate entry. " . $log_details); }
    else { log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "DB Error. " . $log_details); }
    error_log('Pay History API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $error_message]);
} catch (Exception $e) { // Catch other potential errors
    ob_end_clean(); // Clean buffer on error
    error_log('Pay History API General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'PAY_RATE_ACTION_FAILED', "General Error on {$action}. Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected system error occurred.']);
}
?>

