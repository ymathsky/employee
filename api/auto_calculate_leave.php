<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed: ' . $e->getMessage()]);
    exit;
}

// Only Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch all active employees with hired_date
    $sql = "SELECT employee_id, hired_date, first_name, last_name FROM employees WHERE status = 'Active' AND hired_date IS NOT NULL";
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated_count = 0;
    $logs = [];

    foreach ($employees as $emp) {
        $hired_date = new DateTime($emp['hired_date']);
        $today = new DateTime();
        $interval = $hired_date->diff($today);
        $years_of_service = $interval->y;

        // --- CALCULATION RULE ---
        // Modify this logic to change the policy
        
        // Rule: 
        // < 1 Year: 0 days
        // 1 Year: 5 days (Standard Service Incentive Leave)
        // Every year after 1st year: +1 day
        // Max: 15 days
        
        $entitlement = 0;
        
        if ($years_of_service >= 1) {
            $entitlement = 5 + ($years_of_service - 1);
        }
        
        // Cap at 15 (or whatever max you prefer)
        $entitlement = min($entitlement, 15);
        
        // ------------------------

        // Update the database
        // We use INSERT ... ON DUPLICATE KEY UPDATE to handle cases where the row might not exist
        // But since we are updating an existing column, we should check if the row exists first or use the update logic.
        // The leave_balances table usually has rows for all employees.
        
        // Check if balance row exists
        $check = $pdo->prepare("SELECT employee_id FROM leave_balances WHERE employee_id = ?");
        $check->execute([$emp['employee_id']]);
        
        if ($check->fetch()) {
            $update = $pdo->prepare("UPDATE leave_balances SET annual_days_accrued = ? WHERE employee_id = ?");
            $update->execute([$entitlement, $emp['employee_id']]);
        } else {
            // Create default row with calculated annual leave
            $insert = $pdo->prepare("INSERT INTO leave_balances (employee_id, vacation_days_accrued, sick_days_accrued, personal_days_accrued, annual_days_accrued) VALUES (?, 15, 5, 2, ?)");
            $insert->execute([$emp['employee_id'], $entitlement]);
        }

        $updated_count++;
        $logs[] = "{$emp['first_name']} {$emp['last_name']} ({$years_of_service} yrs): {$entitlement} days";
    }

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Updated {$updated_count} employees.",
        'details' => $logs
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Auto Calculate Leave Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>