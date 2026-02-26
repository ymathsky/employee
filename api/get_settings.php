<?php
// FILENAME: employee/api/get_settings.php
session_start();
header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Fetch settings as key-value pairs
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
    $settings_raw = $stmt->fetchAll();

    // Convert from [{'key': 'k', 'value': 'v'}] to {'k': 'v'}
    $settings = [];
    foreach ($settings_raw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    echo json_encode(['success' => true, 'data' => $settings]);

} catch (PDOException $e) {
    error_log('Get Settings Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>
