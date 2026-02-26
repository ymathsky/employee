<?php
// FILENAME: employee/api/delete_leave_request.php
session_start();
header('Content-Type: application/json');

// Only Admins and Managers can delete
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? null;

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required.']);
    exit;
}

try {
    // Optional: Check ownership if Manager? (Skipping for now for simplicity, assuming trust or UI filter handles visibility)
    
    $sql = "DELETE FROM leave_requests WHERE request_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Leave request deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or already deleted.']);
    }

} catch (PDOException $e) {
    error_log("Delete Leave Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>