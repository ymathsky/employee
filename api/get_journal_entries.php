<?php
// FILENAME: employee/api/get_journal_entries.php
session_start();
header('Content-Type: application/json');

// --- Access Control ---
$user_role = $_SESSION['role'] ?? null;
if (!in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

// --- Build Query with Filters ---
$sql = "SELECT 
            ej.*,
            e_subject.first_name AS subject_first_name,
            e_subject.last_name AS subject_last_name,
            e_logger.first_name AS logger_first_name,
            e_logger.last_name AS logger_last_name
        FROM employee_journal ej
        JOIN employees e_subject ON ej.employee_id = e_subject.employee_id
        JOIN employees e_logger ON ej.logged_by_id = e_logger.employee_id
        WHERE 1=1";

$params = [];

// --- Apply Filters from GET parameters ---
if (!empty($_GET['employee_id'])) {
    $sql .= " AND ej.employee_id = ?";
    $params[] = $_GET['employee_id'];
}

if (!empty($_GET['entry_type'])) {
    $sql .= " AND ej.entry_type = ?";
    $params[] = $_GET['entry_type'];
}

if (!empty($_GET['start_date'])) {
    $sql .= " AND ej.entry_date >= ?";
    $params[] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
    $sql .= " AND ej.entry_date <= ?";
    $params[] = $_GET['end_date'];
}

$sql .= " ORDER BY ej.entry_date DESC, ej.created_at DESC";

// --- Execute Query ---
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $entries]);

} catch (PDOException $e) {
    error_log('Error fetching journal entries: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not fetch entries.']);
}
?>
