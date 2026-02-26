<?php
require 'db_connect.php';
header('Content-Type: text/plain');

echo "--- CHECKING DATABASE STATUS ---\n";

// 1. Check deduction_exclusions table
try {
    $pdo->query("SELECT 1 FROM deduction_exclusions LIMIT 1");
    echo "[OK] Table 'deduction_exclusions' exists.\n";
} catch (PDOException $e) {
    echo "[FAIL] Table 'deduction_exclusions' MISSING. Creating it now...\n";
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
        echo "[FIXED] Table created.\n";
    } catch (Exception $ex) {
        echo "[CRITICAL] Could not create table: " . $ex->getMessage() . "\n";
    }
}

// 2. Check Exclusions Data
echo "\n--- CURRENT EXCLUSIONS ---\n";
try {
    $stmt = $pdo->query("
        SELECT de.deduction_id, dt.name, e.first_name, e.last_name 
        FROM deduction_exclusions de
        JOIN deduction_types dt ON de.deduction_id = dt.deduction_id
        JOIN employees e ON de.employee_id = e.employee_id
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($data) > 0) {
        foreach ($data as $row) {
            echo " - Deduction '{$row['name']}' excludes: {$row['first_name']} {$row['last_name']}\n";
        }
    } else {
        echo "No exclusions found in database.\n";
    }
} catch (Exception $e) {
    echo "Error reading exclusions: " . $e->getMessage();
}

// 3. Check specific employees
echo "\n--- ACTIVE DEDUCTIONS FOR USER ---\n";
// Grab first active employee
$emp = $pdo->query("SELECT employee_id, first_name, last_name FROM employees WHERE status='Active' LIMIT 1")->fetch();
if ($emp) {
    echo "Testing for: {$emp['first_name']} {$emp['last_name']} (ID: {$emp['employee_id']})\n";
    
    // Fetch Exclusions for this user
    $exclusions = $pdo->query("SELECT deduction_id FROM deduction_exclusions WHERE employee_id = {$emp['employee_id']}")->fetchAll(PDO::FETCH_COLUMN);
    echo "Excluded Deduction IDs: " . implode(', ', $exclusions) . "\n";
    
    // Fetch expected deductions
    $active = $pdo->query("SELECT deduction_id, name, employee_id FROM deduction_types WHERE is_active=1")->fetchAll();
    foreach($active as $d) {
        $is_global = empty($d['employee_id']);
        $is_for_me = $d['employee_id'] == $emp['employee_id'];
        $is_excluded = in_array($d['deduction_id'], $exclusions);
        
        $status = "SKIPPED";
        if (($is_global || $is_for_me) && !$is_excluded) {
            $status = "APPLIED";
        } elseif ($is_excluded) {
            $status = "EXCLUDED via List";
        } else {
            $status = "NOT APPLICABLE (Target: " . ($d['employee_id'] ?: 'Global') . ")";
        }
        
        echo " - {$d['name']}: {$status}\n";
    }
}

?>