<?php
// FILENAME: employee/api/update_announcement.php
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

if (empty($data['announcement_id']) || empty($data['title']) || empty($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'ID, title, and content are required.']);
    exit;
}

try {
    $sql = "UPDATE announcements SET title = ?, content = ? WHERE announcement_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['title'], $data['content'], $data['announcement_id']]);

    log_action($pdo, $admin_id, 'ANNOUNCEMENT_UPDATED', "Announcement ID {$data['announcement_id']} updated.");
    echo json_encode(['success' => true, 'message' => 'Announcement updated successfully!']);

} catch (PDOException $e) {
    error_log('Update Announcement Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'ANNOUNCEMENT_ERROR', 'DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
