<?php
// FILENAME: employee/api/delete_allowance.php
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
$allowance_id = $data['allowance_id'] ?? null;

if (empty($allowance_id)) {
    log_action($pdo, $admin_id, 'ALLOWANCE_DELETE_FAILED', "Admin attempted to delete allowance with missing ID.");
    echo json_encode(['success' => false, 'message' => 'Allowance ID is required.']);
    exit;
}

try {
    // Get info before deleting for logging
    $stmt_info = $pdo->prepare("SELECT name FROM allowance_types WHERE allowance_id = ?");
    $stmt_info->execute([$allowance_id]);
    $name = $stmt_info->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM allowance_types WHERE allowance_id = ?");
    $stmt->execute([$allowance_id]);

    if ($stmt->rowCount() > 0) {
        log_action($pdo, $admin_id, 'ALLOWANCE_DELETE_SUCCESS', "Deleted allowance: '{$name}' (ID: {$allowance_id}).");
        echo json_encode(['success' => true, 'message' => 'Allowance deleted successfully.']);
    } else {
        log_action($pdo, $admin_id, 'ALLOWANCE_DELETE_FAILED', "Attempt to delete allowance ID {$allowance_id} failed. Not found.");
        echo json_encode(['success' => false, 'message' => 'Allowance not found.']);
    }
} catch (PDOException $e) {
    log_action($pdo, $admin_id, 'ALLOWANCE_DELETE_ERROR', "DB Error deleting allowance ID {$allowance_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>