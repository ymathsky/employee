<?php
header("Content-Type: application/json");
require_once 'db_connect.php';

session_start();

$user_role = $_SESSION['role'] ?? '';
if (!in_array($user_role, ['Super Admin', 'HR Admin', 'Manager', 'Leave Manager'])) {
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
    // Ensure method column exists
    $pdo->exec("ALTER TABLE attendance_logs ADD COLUMN IF NOT EXISTS method VARCHAR(50) DEFAULT NULL");

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
    $stmt = $pdo->prepare("UPDATE attendance_adjustment_requests SET status = ?, updated_at = NOW(), admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?");
    $admin_remarks = $data['admin_remarks'] ?? ('Reviewed by ' . ($_SESSION['username'] ?? 'Admin'));
    $stmt->execute([$status, $admin_remarks, $_SESSION['user_id'] ?? null, $request_id]);

    // 2. If Approved, UPDATE the existing attendance_log row (or insert if no log_id)
    if ($status === 'Approved') {
        $stmt_fetch = $pdo->prepare("SELECT * FROM attendance_adjustment_requests WHERE request_id = ?");
        $stmt_fetch->execute([$request_id]);
        $request = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            $log_remarks = 'Adjustment approved: ' . $request['reason'];

            // Detect request type from the prefixed reason string
            $raw_type = '';
            if (preg_match('/^\[([a-z_]+)\]/', $request['reason'], $m)) {
                $raw_type = $m[1]; // e.g. remove_time_in
            }

            if (!empty($request['log_id'])) {
                if ($raw_type === 'remove_time_in') {
                    $stmt_log = $pdo->prepare("
                        UPDATE attendance_logs
                        SET time_in = NULL,
                            remarks = CONCAT(IFNULL(remarks,''), ' | ', ?),
                            method = 'Manual Adjustment'
                        WHERE log_id = ? AND employee_id = ?
                    ");
                    $stmt_log->execute([$log_remarks, $request['log_id'], $request['employee_id']]);

                } elseif ($raw_type === 'remove_time_out') {
                    $stmt_log = $pdo->prepare("
                        UPDATE attendance_logs
                        SET time_out = NULL,
                            remarks = CONCAT(IFNULL(remarks,''), ' | ', ?),
                            method = 'Manual Adjustment'
                        WHERE log_id = ? AND employee_id = ?
                    ");
                    $stmt_log->execute([$log_remarks, $request['log_id'], $request['employee_id']]);

                } elseif ($raw_type === 'update_time_out') {
                    // Only update time_out, leave time_in untouched
                    $stmt_log = $pdo->prepare("
                        UPDATE attendance_logs
                        SET time_out = ?,
                            remarks = CONCAT(IFNULL(remarks,''), ' | ', ?),
                            method = 'Manual Adjustment'
                        WHERE log_id = ? AND employee_id = ?
                    ");
                    $stmt_log->execute([$request['time_out'], $log_remarks, $request['log_id'], $request['employee_id']]);

                } else {
                    // update_time_in (default) ? update time_in; update time_out if provided
                    $stmt_log = $pdo->prepare("
                        UPDATE attendance_logs
                        SET time_in  = ?,
                            time_out = COALESCE(?, time_out),
                            remarks  = CONCAT(IFNULL(remarks,''), ' | ', ?),
                            method   = 'Manual Adjustment'
                        WHERE log_id = ? AND employee_id = ?
                    ");
                    $stmt_log->execute([
                        $request['time_in'],
                        $request['time_out'] ?: null,
                        $log_remarks,
                        $request['log_id'],
                        $request['employee_id'],
                    ]);
                }
            } else {
                // Fallback: insert new row if no log_id was linked
                $stmt_log = $pdo->prepare("
                    INSERT INTO attendance_logs (employee_id, log_date, time_in, time_out, method, remarks)
                    VALUES (?, ?, ?, ?, 'Manual Adjustment', ?)
                ");
                $stmt_log->execute([
                    $request['employee_id'],
                    $request['log_date'],
                    $request['time_in'],
                    $request['time_out'],
                    $log_remarks,
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Request processed successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
