<?php
// FILENAME: employee/api/submit_leave_request.php
session_start();
header('Content-Type: application/json');

// Only logged-in employees (or managers/admins on their own behalf) can submit.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // Load constants

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Employee';
$is_admin = ($user_role === 'HR Admin' || $user_role === 'Super Admin');

$data = json_decode(file_get_contents('php://input'), true);

$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$leave_type = $data['leave_type'] ?? null;
$reason = $data['reason'] ?? null;
$target_employee_id = $data['employee_id'] ?? $current_user_id;
$status = 'Pending';

// Check if submitting for someone else
if ($target_employee_id != $current_user_id) {
    if (!$is_admin) {
         echo json_encode(['success' => false, 'message' => 'Unauthorized to submit leave for other employees.']);
         exit;
    }
    // If admin is submitting for someone else, default to Approved unless specified otherwise
    $status = $data['status'] ?? 'Approved';
}

// Validation
if (empty($start_date) || empty($end_date) || empty($leave_type) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Date validation
$start_dt = new DateTime($start_date);
$end_dt = new DateTime($end_date);
$today_dt = new DateTime('today');

// Allow past dates if admin is submitting (maybe they are backfilling)
if (!$is_admin && $start_dt < $today_dt) {
    echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past.']);
    exit;
}
if ($end_dt < $start_dt) {
    echo json_encode(['success' => false, 'message' => 'End date must be on or after the start date.']);
    exit;
}

// Type validation (Using the new constant)
if (!in_array($leave_type, LEAVE_TYPES)) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave type specified.']);
    exit;
}

try {
    $sql = "INSERT INTO leave_requests (employee_id, start_date, end_date, leave_type, reason, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $target_employee_id,
        $start_date,
        $end_date,
        $leave_type,
        $reason,
        $status
    ]);

    echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully!']);

} catch (PDOException $e) {
    error_log('Submit Leave Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not submit request.']);
}
?>
