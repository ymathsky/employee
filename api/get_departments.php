<?php
// FILENAME: employee/api/get_departments.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in (any role can view departments)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Fetch all department names
    $stmt = $pdo->query("SELECT department_name FROM departments ORDER BY department_name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'departments' => $departments]);

} catch (PDOException $e) {
    error_log('Get Departments Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not fetch departments.']);
}
?>