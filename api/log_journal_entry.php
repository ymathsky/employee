<?php
// FILENAME: employee/api/log_journal_entry.php
session_start();
header('Content-Type: application/json');

// Only Admins and Managers can log entries.
$logged_by_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
if (!$logged_by_id || !in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // For logging

$data = json_decode(file_get_contents('php://input'), true);

$employee_id = $data['employee_id'] ?? null;
$entry_type = $data['entry_type'] ?? null;
$entry_date = $data['entry_date'] ?? null;
$description = trim($data['description'] ?? '');

// Simple Validation
if (empty($employee_id) || empty($entry_type) || empty($entry_date) || empty($description)) {
    log_action($pdo, $logged_by_id, 'JOURNAL_LOG_FAILED', "Attempted to log journal with missing fields for EID {$employee_id}.");
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$valid_types = ['Positive', 'Coaching', 'Warning'];
if (!in_array($entry_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid entry type.']);
    exit;
}

try {
    $sql = "INSERT INTO employee_journal (employee_id, logged_by_id, entry_type, entry_date, description) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $employee_id,
        $logged_by_id,
        $entry_type,
        $entry_date,
        $description
    ]);

    // --- LOGGING ---
    log_action($pdo, $logged_by_id, 'JOURNAL_ENTRY_LOGGED', "Logged a '{$entry_type}' journal entry for EID {$employee_id}.");
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Journal entry successfully recorded!']);

} catch (PDOException $e) {
    error_log('Journal Entry Error: ' . $e->getMessage());
    log_action($pdo, $logged_by_id, 'JOURNAL_LOG_ERROR', "DB error logging journal entry for EID {$employee_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not save entry.']);
}
