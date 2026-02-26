<?php
// FILENAME: employee/api/delete_schedule.php
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
$schedule_id = $data['schedule_id'] ?? null;

if (empty($schedule_id)) {
    log_action($pdo, $admin_id, 'SCHEDULE_DELETE_FAILED', "Admin attempted to delete schedule with missing ID.");
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit;
}

try {
    // Get info before deleting for logging
    $stmt_info = $pdo->prepare("SELECT employee_id, work_date FROM schedules WHERE schedule_id = ?");
    $stmt_info->execute([$schedule_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $sql = "DELETE FROM schedules WHERE schedule_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id]);

    if ($stmt->rowCount() > 0) {
        // --- LOGGING ---
        if ($info) {
            log_action($pdo, $admin_id, LOG_ACTION_SCHEDULE_EXCEPTION_DELETED, "Deleted schedule exception (ID {$schedule_id}) for EID {$info['employee_id']} on {$info['work_date']}.");
        }
        // --- END LOGGING ---

        echo json_encode(['success' => true, 'message' => 'Shift deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Shift not found or already deleted.']);
    }

} catch (PDOException $e) {
    error_log('Delete Schedule Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'SCHEDULE_DELETE_ERROR', "DB Error deleting schedule ID {$schedule_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
