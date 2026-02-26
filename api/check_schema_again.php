<?php
require 'db_connect.php';
try {
    $stmt = $pdo->query("DESCRIBE leave_balances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in leave_balances:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>