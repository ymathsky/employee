<?php
require_once 'api/db_connect.php';

try {
    $keys = ['late_grace_period_minutes', 'allow_manual_attendance_edit'];
    foreach ($keys as $key) {
        $stmt = $pdo->prepare("SELECT * FROM global_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if (!$stmt->fetch()) {
            echo "Setting '$key' missing. Inserting default...\n";
            $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, '0')")->execute([$key]);
        } else {
            echo "Setting '$key' exists.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>