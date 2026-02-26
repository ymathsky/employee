<?php
header("Content-Type: application/json");
require_once 'db_connect.php';

session_start();

$user_role = $_SESSION['role'] ?? '';
if (!in_array($user_role, ['Super Admin', 'HR Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['request_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$request_id = $data['request_id'];
$status = $data['status']; // 'Approved' or 'Rejected'

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 0. Check if already processed
    $stmt_check = $pdo->prepare("SELECT status FROM attendance_adjustment_requests WHERE request_id = ? FOR UPDATE");
    $stmt_check->execute([$request_id]);
    $current_status = $stmt_check->fetchColumn();

    if ($current_status !== 'Pending') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request already processed']);
        exit;
    }

    // 1. Update the request status
    $stmt = $pdo->prepare("UPDATE attendance_adjustment_requests SET status = ?, updated_at = NOW(), admin_remarks = ? WHERE request_id = ?");
    $admin_remarks = "Reviewed by " . ($_SESSION['username'] ?? 'Admin');
    $stmt->execute([$status, $admin_remarks, $request_id]);

    // 2. If Approved, insert into attendance_logs
    if ($status === 'Approved') {
        // Fetch the request details
        $stmt_fetch = $pdo->prepare("SELECT * FROM attendance_adjustment_requests WHERE request_id = ?");
        $stmt_fetch->execute([$request_id]);
        $request = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            // Insert log
            $stmt_log = $pdo->prepare("
                INSERT INTO attendance_logs (employee_id, log_date, time_in, time_out, method, remarks)
                VALUES (?, ?, ?, ?, 'Manual Adjustment', ?)
            ");
            
            // Format remarks: "Reason: X (Approved Request #Y)"
            $log_remarks = "Adjustment: " . $request['reason'];

            $stmt_log->execute([
                $request['employee_id'],
                $request['log_date'],
                $request['time_in'],
                $request['time_out'],
                $log_remarks
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Request processed successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
