<?php
require_once 'api/db_connect.php';
try {
    $stmt = $pdo->query("DESCRIBE deduction_types");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>