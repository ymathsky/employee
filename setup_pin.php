<?php
require_once 'api/db_connect.php';

try {
    // 1. Add pin_secret column if not exists
    $check = $pdo->query("SHOW COLUMNS FROM employees LIKE 'pin_secret'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN pin_secret VARCHAR(64) NULL AFTER email");
        echo "Added 'pin_secret' column to employees table.<br>";
    } else {
        echo "'pin_secret' column already exists.<br>";
    }

    // 2. Generate secrets for employees who don't have one
    $stmt = $pdo->query("SELECT employee_id FROM employees WHERE pin_secret IS NULL OR pin_secret = ''");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($employees as $emp) {
        // Generate a random 32-char hex secret
        $secret = bin2hex(random_bytes(16));
        
        $update = $pdo->prepare("UPDATE employees SET pin_secret = ? WHERE employee_id = ?");
        $update->execute([$secret, $emp['employee_id']]);
        $count++;
    }

    echo "Generated secrets for $count employees.<br>";
    echo "Setup Complete.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
