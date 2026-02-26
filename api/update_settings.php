<?php
// FILENAME: employee/api/update_settings.php
session_start();
header('Content-Type: application/json');

// Super Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Include for logging

$admin_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    log_action($pdo, $admin_id, 'SETTINGS_UPDATE_FAILED', "Super Admin attempted to update settings with no data provided.");
    echo json_encode(['success' => false, 'message' => 'No data provided.']);
    exit;
}

$pdo->beginTransaction();
$changes = [];

try {
    $sql = "UPDATE global_settings SET setting_value = ? WHERE setting_key = ?";
    $stmt = $pdo->prepare($sql);
    $rows_affected = 0;

    // Loop through the data and update each setting
    foreach ($data as $key => $value) {
        // Optional: Fetch old value for better logging if necessary, but requires extra query
        $stmt->execute([$value, $key]);
        $rows_affected += $stmt->rowCount();
        $changes[] = "{$key}: '{$value}'";
    }

    $pdo->commit();

    // --- LOGGING ---
    if ($rows_affected > 0) {
        log_action($pdo, $admin_id, LOG_ACTION_GLOBAL_SETTINGS_UPDATED, "Global settings updated. Changes: " . implode(', ', $changes));
    }
    // --- END LOGGING ---

    // --- IMPORTANT ---
    // Update the settings in the session immediately
    unset($_SESSION['settings']);

    // Re-fetch from DB to ensure session is 100% in sync
    $stmt_fetch = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
    $settings_raw = $stmt_fetch->fetchAll();
    $settings = [];
    foreach ($settings_raw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $_SESSION['settings'] = $settings;
    // --- End Session Update ---

    echo json_encode(['success' => true, 'message' => 'Settings updated successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Update Settings Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'SETTINGS_UPDATE_ERROR', "DB Error updating settings: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
