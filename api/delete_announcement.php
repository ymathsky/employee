<?php
// FILENAME: employee/api/delete_announcement.php
session_start();
header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // For logging

$admin_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['announcement_id'])) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID is required.']);
    exit;
}

try {
    $sql = "DELETE FROM announcements WHERE announcement_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['announcement_id']]);

    log_action($pdo, $admin_id, 'ANNOUNCEMENT_DELETED', "Announcement ID {$data['announcement_id']} deleted.");
    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);

} catch (PDOException $e) {
    error_log('Delete Announcement Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'ANNOUNCEMENT_ERROR', 'DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
