<?php
// FILENAME: employee/api/migration_add_remarks.php
require_once 'db_connect.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM attendance_logs LIKE 'remarks'");
    if ($check->rowCount() == 0) {
        // Add the column
        $pdo->exec("ALTER TABLE attendance_logs ADD COLUMN remarks VARCHAR(255) DEFAULT NULL AFTER time_out");
        echo "Migration Successful: Added 'remarks' column to attendance_logs table.\n";
    } else {
        echo "Migration Skipped: 'remarks' column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>
