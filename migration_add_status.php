<?php
require_once 'api/db_connect.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
    if ($check->rowCount() == 0) {
        // Add the column
        $sql = "ALTER TABLE employees ADD COLUMN status ENUM('Active', 'Terminated', 'Resigned', 'Contract Ended') NOT NULL DEFAULT 'Active'";
        $pdo->exec($sql);
        echo "Column 'status' added successfully.\n";
    } else {
        echo "Column 'status' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
