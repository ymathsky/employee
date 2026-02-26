<?php
// FILENAME: employee/api/delete_deduction.php
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
$deduction_id = $data['deduction_id'] ?? null;

if (empty($deduction_id)) {
    log_action($pdo, $admin_id, 'DEDUCTION_DELETE_FAILED', "Admin attempted to delete deduction with missing ID.");
    echo json_encode(['success' => false, 'message' => 'Deduction ID is required.']);
    exit;
}

try {
    // Get info before deleting for logging
    $stmt_info = $pdo->prepare("SELECT name FROM deduction_types WHERE deduction_id = ?");
    $stmt_info->execute([$deduction_id]);
    $name = $stmt_info->fetchColumn();

    $sql = "DELETE FROM deduction_types WHERE deduction_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deduction_id]);

    if ($stmt->rowCount() > 0) {
        // --- LOGGING ---
        log_action($pdo, $admin_id, LOG_ACTION_DEDUCTION_DELETED, "Deleted deduction '{$name}' (ID {$deduction_id}).");
        // --- END LOGGING ---
        echo json_encode(['success' => true, 'message' => 'Deduction deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Deduction not found or already deleted.']);
    }

} catch (PDOException $e) {
    error_log('Delete Deduction Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'DEDUCTION_DELETE_ERROR', "DB Error deleting deduction ID {$deduction_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not delete deduction.']);
}
?>
