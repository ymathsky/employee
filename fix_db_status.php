<?php
// FILENAME: fix_db_status.php
// Upload this file to your hosting server in the same folder as index.php
// Then visit it in your browser (e.g., yoursite.com/fix_db_status.php)

require_once 'api/db_connect.php';

echo "<h1>Database Fix Tool</h1>";

try {
    echo "<p>Checking 'employees' table for 'status' column...</p>";
    
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
    
    if ($check->rowCount() == 0) {
        echo "<p>Column 'status' is MISSING. Attempting to add it...</p>";
        
        // Add the column
        $sql = "ALTER TABLE employees ADD COLUMN status ENUM('Active', 'Terminated', 'Resigned', 'Contract Ended') NOT NULL DEFAULT 'Active'";
        $pdo->exec($sql);
        
        echo "<p style='color: green; font-weight: bold;'>SUCCESS: Column 'status' added successfully.</p>";
    } else {
        echo "<p style='color: blue; font-weight: bold;'>INFO: Column 'status' already exists. No action needed.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p>Done.</p>";
?>
