<?php
// FILENAME: employee/api/update_standard_schedule.php
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

// Handle both JSON (from raw body) and Form Data (from standard POST)
$input_data = json_decode(file_get_contents('php://input'), true);
$data = $input_data ?: $_POST;

$employee_id = $data['employee_id'] ?? null;
if (empty($employee_id)) {
    log_action($pdo, $admin_id, 'SCHEDULE_UPDATE_FAILED', "Admin attempted to update standard schedule with missing EID.");
    echo json_encode(['success' => false, 'message' => 'Employee ID is required.']);
    exit;
}

$daysOfWeek = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
$params = ['employee_id' => $employee_id];
$sql_cols = 'employee_id';
$sql_vals = '?';
$sql_update = '';
$has_error = false;

foreach ($daysOfWeek as $day) {
    $start = !empty($data[$day.'_start']) ? $data[$day.'_start'] : null;
    $end = !empty($data[$day.'_end']) ? $data[$day.'_end'] : null;

    // Basic validation: if one is set, the other must be too
    if (($start && !$end) || (!$start && $end)) {
        log_action($pdo, $admin_id, 'SCHEDULE_UPDATE_FAILED', "Admin attempted to update standard schedule for EID {$employee_id}: Missing start/end time for {$day}.");
        echo json_encode(['success' => false, 'message' => 'Start and End time must both be set (or both be blank) for ' . ucfirst($day) . '.']);
        exit;
    }
    // Basic validation: end must be after start
    if ($start && $end && strtotime($end) <= strtotime($start)) {
        log_action($pdo, $admin_id, 'SCHEDULE_UPDATE_FAILED', "Admin attempted to update standard schedule for EID {$employee_id}: End time before start time for {$day}.");
        echo json_encode(['success' => false, 'message' => 'End time must be after start time for ' . ucfirst($day) . '.']);
        exit;
    }

    $params[$day.'_start'] = $start;
    $params[$day.'_end'] = $end;

    $sql_cols .= ", {$day}_start, {$day}_end";
    $sql_vals .= ", ?, ?";
    $sql_update .= "{$day}_start = VALUES({$day}_start), {$day}_end = VALUES({$day}_end), ";
}

// Remove trailing comma
$sql_update = rtrim($sql_update, ', ');

try {
    $sql = "INSERT INTO standard_schedules ($sql_cols) 
            VALUES ($sql_vals)
            ON DUPLICATE KEY UPDATE $sql_update";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($params));

    // --- SYNC WITH DEDICATED OFF DAYS ---
    // 1. Remove existing "Standard Schedule Default" rules for this employee to prevent duplicates/outdated rules
    $stmt_del = $pdo->prepare("DELETE FROM dedicated_off_days WHERE employee_id = ? AND reason = 'Standard Schedule Default'");
    $stmt_del->execute([$employee_id]);

    // 2. Insert new rules for current Rest Days
    $stmt_ins = $pdo->prepare("INSERT INTO dedicated_off_days (employee_id, day_of_week, effective_date, reason) VALUES (?, ?, CURDATE(), 'Standard Schedule Default')");
    
    foreach ($daysOfWeek as $day) {
        // If start or end is empty, it's a rest day
        if (empty($params[$day.'_start']) || empty($params[$day.'_end'])) {
            // Convert 'mon' to 'Mon' for consistency
            $dayCap = ucfirst($day);
            $stmt_ins->execute([$employee_id, $dayCap]);
        }
    }
    // --- END SYNC ---

    // --- LOGGING ---
    log_action($pdo, $admin_id, LOG_ACTION_SCHEDULE_STANDARD_UPDATED, "Updated standard schedule for EID {$employee_id}.");
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Standard schedule updated successfully!']);

} catch (PDOException $e) {
    error_log('Update Standard Schedule Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'SCHEDULE_UPDATE_ERROR', "DB Error updating standard schedule for EID {$employee_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not update schedule.']);
}
?>
