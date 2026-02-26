<?php
require_once 'api/db_connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM global_settings WHERE setting_key = 'late_grace_period_minutes'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "Setting exists: " . print_r($row, true);
    } else {
        echo "Setting 'late_grace_period_minutes' does NOT exist.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>