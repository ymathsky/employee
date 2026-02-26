<?php
// FILENAME: employee/api/get_audit_logs.php
session_start();
header('Content-Type: application/json');

// --- Role Check: Super Admin Only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Select log details, employee name, and handle the case where employee_id = 0 (system/unauthenticated)
    $sql = "
        SELECT 
            al.log_id,
            al.log_timestamp,
            al.action,
            al.description,
            al.employee_id AS performer_id,
            IFNULL(CONCAT(e.first_name, ' ', e.last_name), 'System/Unauthenticated') AS performer_name
        FROM audit_logs al
        LEFT JOIN employees e ON al.employee_id = e.employee_id
        ORDER BY al.log_timestamp DESC
        LIMIT 500 -- Limit results to prevent excessive load
    ";
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $logs]);

} catch (PDOException $e) {
    error_log('Get Audit Logs Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching logs. Ensure the audit_logs table exists.']);
}
?>
