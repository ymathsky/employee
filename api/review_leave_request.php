<?php
// FILENAME: employee/api/review_leave_request.php
session_start();
header('Content-Type: application/json');

// Only Admins and Managers can review.
$user_role = $_SESSION['role'] ?? null;
if (!in_array($user_role, ['HR Admin', 'Super Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // Load constants
require_once __DIR__ . '/../config/utils.php'; // For logging

$manager_id_session = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$request_id = $data['request_id'] ?? null;
$new_status = $data['status'] ?? null;
$manager_id = $data['manager_id'] ?? $manager_id_session; // Use session ID as fallback

// Validation
if (empty($request_id) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Request ID and new status are required.']);
    exit;
}

// Status validation (Using the new constant)
if (!in_array($new_status, LEAVE_STATUSES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status provided.']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Initial check: Get request details and status
    $sql_check = "
        SELECT lr.employee_id, e.department, lr.start_date, lr.end_date, lr.leave_type, lr.status AS current_status
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id
        WHERE lr.request_id = ?
    ";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$request_id]);
    $request = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
        exit;
    }

    // Only process status change if the current status is Pending
    if ($request['current_status'] !== 'Pending') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request is already marked as ' . htmlspecialchars($request['current_status']) . '.']);
        exit;
    }

    // 2. Authorization Check (for Managers)
    if ($user_role === 'Manager') {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id_session]);
        $manager_department = $stmt_dept->fetchColumn();

        if ($manager_department !== $request['department']) {
            $pdo->rollBack();
            log_action($pdo, $manager_id_session, 'LEAVE_REVIEW_DENIED', "Manager tried to review request EID {$request['employee_id']} outside their dept: {$request['department']}.");
            echo json_encode(['success' => false, 'message' => 'You are not authorized to approve leave for this department.']);
            exit;
        }
    }

    // 3. Update the request status
    $sql_update_request = "UPDATE leave_requests SET status = ?, manager_id = ? WHERE request_id = ?";
    $stmt_update_request = $pdo->prepare($sql_update_request);
    $stmt_update_request->execute([$new_status, $manager_id_session, $request_id]);

    // 4. CRITICAL: Update Leave Balance if Approved
    if ($new_status === 'Approved') {
        $leave_type = $request['leave_type'];

        // Only track and deduct standard paid leave types
        if (in_array($leave_type, ['Vacation', 'Sick Leave', 'Personal Day'])) {

            // Calculate number of days
            $start = new DateTime($request['start_date']);
            $end = new DateTime($request['end_date']);
            // +1 day to include the end date
            $interval = $start->diff($end);
            $days_to_deduct = $interval->days + 1;

            // Map leave type to the correct column name
            $column_map = [
                'Vacation' => 'vacation_days_used',
                'Sick Leave' => 'sick_days_used',
                'Personal Day' => 'personal_days_used',
            ];
            $used_column = $column_map[$leave_type];

            // Upsert logic for leave_balances: ensures an entry exists, then updates the used count
            $sql_update_balance = "
                INSERT INTO leave_balances (employee_id, {$used_column})
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE 
                    {$used_column} = {$used_column} + ?
            ";

            $stmt_update_balance = $pdo->prepare($sql_update_balance);
            $stmt_update_balance->execute([
                $request['employee_id'],
                $days_to_deduct,
                $days_to_deduct
            ]);

            $deduction_message = " - Deducted {$days_to_deduct} days from {$leave_type} balance.";
        }
    }

    // 5. Commit transaction and log success
    $pdo->commit();
    log_action($pdo, $manager_id_session, LOG_ACTION_LEAVE_REVIEWED, "Leave Request ID {$request_id} set to '{$new_status}' by EID {$manager_id_session}." . ($deduction_message ?? ''));

    echo json_encode(['success' => true, 'message' => 'Leave request successfully marked as ' . htmlspecialchars($new_status) . '.' . ($deduction_message ?? '')]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Review Leave Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not update request status and balance.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Review Leave General Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error during leave review.']);
}
?>
