<?php
header("Content-Type: application/json");
require_once 'db_connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$employee_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['log_date']) || !isset($data['time_in']) || !isset($data['time_out']) || !isset($data['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$log_date = $data['log_date'];
$time_in = $data['time_in'];
$time_out = $data['time_out'];
$reason = trim($data['reason']);

// Validate date format (YYYY-MM-DD) and Time format (YYYY-MM-DD HH:MM:SS)
// Simple checks
if (strtotime($time_out) <= strtotime($time_in)) {
    echo json_encode(['success' => false, 'message' => 'Time Out must be after Time In']);
    exit;
}

try {
    // Insert into attendance_adjustment_requests
    $stmt = $pdo->prepare("
        INSERT INTO attendance_adjustment_requests 
        (employee_id, log_date, time_in, time_out, reason, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    
    $stmt->execute([
        $employee_id,
        $log_date,
        $time_in,
        $time_out,
        $reason
    ]);

    echo json_encode(['success' => true, 'message' => 'Request submitted successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
