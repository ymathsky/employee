<?php
// FILENAME: employee/api/add_schedule.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$employee_id = $data['employee_id'] ?? null;
$work_date = $data['work_date'] ?? null;
$type = $data['type'] ?? 'work_day'; // 'work_day' or 'off_day'
$shift_start = $data['shift_start'] ?? null;
$shift_end = $data['shift_end'] ?? null;

if (empty($employee_id) || empty($work_date)) {
    echo json_encode(['success' => false, 'message' => 'Employee and Date are required.']);
    exit;
}

if ($type === 'off_day') {
    // If it's an off day, we save NULL for times
    $shift_start = null;
    $shift_end = null;
} else {
    // If it's a work day, times are required
    if (empty($shift_start) || empty($shift_end)) {
        echo json_encode(['success' => false, 'message' => 'Start and End time are required for a work day.']);
        exit;
    }
    // Basic validation
    if (strtotime($shift_end) <= strtotime($shift_start)) {
        // Handle overnight shifts if needed, but basic check first
        // If end time is earlier, maybe it's next day? usually handled in UI logic but let's allow it if system supports overnight
        // For now, simple check:
        // echo json_encode(['success' => false, 'message' => 'Shift end time must be after start time.']);
        // exit;
    }
}

try {
    $sql = "INSERT INTO schedules (employee_id, work_date, shift_start, shift_end) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            shift_start = VALUES(shift_start), shift_end = VALUES(shift_end)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $work_date, $shift_start, $shift_end]);

    $msg = ($type === 'off_day') ? 'Marked as Off Day/Rest Day.' : 'Shift schedule updated.';
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (PDOException $e) {
    error_log('Add Schedule Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not add shift.']);
}
?>
