<?php
// FILENAME: employee/api/get_announcements.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in (any role can view)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Check for a limit (e.g., for dashboards)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

    // Join with employees to get author's name
    $sql = "SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) AS author_name
            FROM announcements a
            LEFT JOIN employees e ON a.created_by_id = e.employee_id
            ORDER BY a.created_at DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    error_log('Get Announcements Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
