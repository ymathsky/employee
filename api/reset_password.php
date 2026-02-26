<?php
// FILENAME: employee/api/reset_password.php
session_start();
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$employee_id_to_reset = $data['employee_id'] ?? null;
$new_password = $data['new_password'] ?? null;

// Session details
$logged_in_user_id = $_SESSION['user_id'] ?? null;
$logged_in_user_role = $_SESSION['role'] ?? null;
$is_admin = ($logged_in_user_role === 'HR Admin' || $logged_in_user_role === 'Super Admin');

// --- AUTHORIZATION CHECK ---
// 1. Must be logged in.
if (!$logged_in_user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Must be logged in.']);
    exit;
}

// 2. Check permission: Must be an Admin OR resetting their own password.
if (!$is_admin && (int)$employee_id_to_reset !== (int)$logged_in_user_id) {
    // If not admin and the requested EID does not match the session EID
    echo json_encode(['success' => false, 'message' => 'Forbidden: You can only reset your own password.']);
    exit;
}
// --- END AUTHORIZATION CHECK ---


require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // For logging

// Validation
if (empty($employee_id_to_reset) || empty($new_password)) {
    log_action($pdo, $logged_in_user_id, 'PASSWORD_RESET_FAILED', "Attempted password reset with missing EID or password.");
    echo json_encode(['success' => false, 'message' => 'Employee ID and new password are required.']);
    exit;
}

if (strlen($new_password) < 8) {
    log_action($pdo, $logged_in_user_id, 'PASSWORD_RESET_FAILED', "Attempted password reset for EID {$employee_id_to_reset}: Password too short.");
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
    exit;
}

$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Check if the user record exists before updating
    $stmt_check = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id = ?");
    $stmt_check->execute([$employee_id_to_reset]);

    if ($stmt_check->rowCount() === 0) {
        log_action($pdo, $logged_in_user_id, 'PASSWORD_RESET_FAILED', "Failed to reset password for EID {$employee_id_to_reset}: User record not found.");
        echo json_encode(['success' => false, 'message' => 'Error: User account not found.']);
        exit;
    }

    // Update the password hash in the users table
    $sql = "UPDATE users SET password_hash = ? WHERE employee_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$password_hash, $employee_id_to_reset]);

    // --- LOGGING ---
    $log_description = ($is_admin)
        ? "Admin EID {$logged_in_user_id} successfully reset password for EID {$employee_id_to_reset}."
        : "EID {$logged_in_user_id} successfully reset their own password.";

    log_action($pdo, $logged_in_user_id, LOG_ACTION_PASSWORD_RESET, $log_description);
    // --- END LOGGING ---

    echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);

} catch (PDOException $e) {
    error_log('Password Reset Error: ' . $e->getMessage());
    log_action($pdo, $logged_in_user_id, 'PASSWORD_RESET_ERROR', "DB Error resetting password for EID {$employee_id_to_reset}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Could not reset password.']);
}
?>
