<?php
require_once 'api/db_connect.php';

try {
    $sql = "INSERT IGNORE INTO global_settings (setting_key, setting_value) VALUES ('late_grace_period_minutes', '0')";
    $pdo->exec($sql);
    echo "Successfully inserted 'late_grace_period_minutes' setting.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>