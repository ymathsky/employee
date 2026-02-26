<?php
// FILENAME: employee/api/get_deductions.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Join with employees table to get names for specific deductions
    $sql = "
        SELECT d.*, e.first_name, e.last_name 
        FROM deduction_types d
        LEFT JOIN employees e ON d.employee_id = e.employee_id
        ORDER BY d.employee_id ASC, d.name ASC
    ";
    $stmt = $pdo->query($sql);
    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch exclusions for each deduction
    foreach ($deductions as &$deduction) {
        $deduction['excluded_employees'] = [];
        if (empty($deduction['employee_id'])) { // Only global deductions have exclusions
            $stmt_ex = $pdo->prepare("SELECT employee_id FROM deduction_exclusions WHERE deduction_id = ?");
            $stmt_ex->execute([$deduction['deduction_id']]);
            $deduction['excluded_employees'] = $stmt_ex->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    echo json_encode(['success' => true, 'data' => $deductions]);

} catch (PDOException $e) {
    // Log the error specifically for a missing table, which is likely if not run the schema change
    if ($e->getCode() == '42S02') { // 42S02 is common for 'base table or view not found'
        error_log('Get Deductions Error: Deduction types table missing. ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System error: Deduction types table not found.']);
    } else {
        error_log('Get Deductions Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}
?>
