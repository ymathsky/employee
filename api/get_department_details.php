<?php
// FILENAME: employee/api/get_department_details.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

$department_id = $_GET['id'] ?? null;

if (empty($department_id)) {
    echo json_encode(['success' => false, 'message' => 'No department ID provided.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT department_id, department_name, manager_id FROM departments WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $department = $stmt->fetch();

    if ($department) {
        echo json_encode(['success' => true, 'data' => $department]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found.']);
    }

} catch (PDOException $e) {
    error_log('Get Department Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
