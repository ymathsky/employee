<?php
require_once 'api/db_connect.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM leave_balances LIKE 'annual_days_accrued'");
    if ($check->rowCount() == 0) {
        // Add the column
        $sql = "ALTER TABLE leave_balances ADD COLUMN annual_days_accrued DECIMAL(5,2) DEFAULT 0.00 AFTER personal_days_accrued";
        $pdo->exec($sql);
        echo "Column 'annual_days_accrued' added successfully.<br>";
    } else {
        echo "Column 'annual_days_accrued' already exists.<br>";
    }

    echo "Migration completed.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>