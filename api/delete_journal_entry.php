<?php
// FILENAME: employee/api/delete_journal_entry.php
session_start();
header('Content-Type: application/json');

// --- Access Control ---
$logged_by_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
if (!$logged_by_id || !in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // For logging

$data = json_decode(file_get_contents('php://input'), true);
$journal_id = $data['journal_id'] ?? null;

if (empty($journal_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Journal ID.']);
    exit;
}

try {
    // Optional: Check if the entry exists before deleting
    $stmt_check = $pdo->prepare("SELECT 1 FROM employee_journal WHERE journal_id = ?");
    $stmt_check->execute([$journal_id]);
    if ($stmt_check->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Entry not found.']);
        exit;
    }

    // Delete the entry
    $sql = "DELETE FROM employee_journal WHERE journal_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$journal_id]);

    // --- LOGGING ---
    log_action($pdo, $logged_by_id, 'JOURNAL_ENTRY_DELETED', "Deleted journal entry with ID {$journal_id}.");
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Journal entry successfully deleted!']);

} catch (PDOException $e) {
    error_log('Journal Entry Delete Error: ' . $e->getMessage());
    log_action($pdo, $logged_by_id, 'JOURNAL_DELETE_ERROR', "DB error deleting journal entry ID {$journal_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not delete entry.']);
}
?>
