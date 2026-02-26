<?php
// FILENAME: employee/api/get_employee_details.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

$employee_id = $_GET['id'] ?? null;

if (empty($employee_id)) {
    echo json_encode(['success' => false, 'message' => 'No employee ID provided.']);
    exit;
}

try {
    // *** MODIFIED: Removed transaction, as it's a single query ***

    // Query 1: Get main employee details and role
    $sql_main = "SELECT e.*, u.role
                 FROM employees e
                 JOIN users u ON e.employee_id = u.employee_id
                 WHERE e.employee_id = ?";

    $stmt_main = $pdo->prepare($sql_main);
    $stmt_main->execute([$employee_id]);
    $employee_data = $stmt_main->fetch(PDO::FETCH_ASSOC);

    if (!$employee_data) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        exit;
    }

    // *** REMOVED: Query 2 (Get all leave balances) ***

    // *** REMOVED: Formatting leave balances ***

    // *** REMOVED: Combining leave data ***

    echo json_encode(['success' => true, 'data' => $employee_data]);

} catch (PDOException $e) {
    error_log('Get Employee Details Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error while fetching employee details.']);
}
?>

