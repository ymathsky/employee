<?php
// FILENAME: employee/api/get_allowances.php
session_start();
header('Content-Type: application/json');

// Admin/Manager/Employee access
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    // Fetch all allowance types with their assigned employee (if any)
    // Left Join with Employees to get names
    $sql = "
        SELECT 
            a.*,
            e.first_name, 
            e.last_name,
            (SELECT GROUP_CONCAT(employee_id) FROM allowance_exclusions WHERE allowance_id = a.allowance_id) as excluded_employee_ids
        FROM allowance_types a
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        ORDER BY a.name ASC
    ";
    
    $stmt = $pdo->query($sql);
    $allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert excluded_employee_ids string to array
    foreach ($allowances as &$al) {
        $al['excluded_employees'] = !empty($al['excluded_employee_ids']) ? explode(',', $al['excluded_employee_ids']) : [];
        unset($al['excluded_employee_ids']);
    }

    echo json_encode(['success' => true, 'data' => $allowances]);

} catch (PDOException $e) {
    error_log("Database error fetching allowances: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch allowances.']);
}
?>