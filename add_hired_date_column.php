<?php
require_once 'api/db_connect.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM employees LIKE 'hired_date'");
    if ($check->rowCount() == 0) {
        // Add the column
        $sql = "ALTER TABLE employees ADD COLUMN hired_date DATE NULL AFTER department";
        $pdo->exec($sql);
        echo "Column 'hired_date' added successfully.<br>";
        
        // Initialize hired_date with created_at for existing records as a fallback
        $pdo->exec("UPDATE employees SET hired_date = DATE(created_at) WHERE hired_date IS NULL");
        echo "Initialized 'hired_date' with 'created_at' values.<br>";
    } else {
        echo "Column 'hired_date' already exists.<br>";
    }

    echo "Migration completed.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>