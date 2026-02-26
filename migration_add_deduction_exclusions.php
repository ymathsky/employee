<?php
require_once 'api/db_connect.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS `deduction_exclusions` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `deduction_id` int(11) NOT NULL,
      `employee_id` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `deduction_id` (`deduction_id`),
      KEY `employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $pdo->exec($sql);
    echo "Table 'deduction_exclusions' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>