<?php
require_once 'api/db_connect.php';
try {
    $stmt = $pdo->query("DESCRIBE ca_deductions_history");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
} catch (Exception $e) {
    echo "Table not found or error: " . $e->getMessage();
}
