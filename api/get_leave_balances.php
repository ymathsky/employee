<?php
// FILENAME: employee/api/get_leave_balances.php
session_start();
header('Content-Type: application/json');

// Only logged-in employees can check their own balances.
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/app_config.php'; // Load defaults
require_once __DIR__ . '/../config/utils.php'; // For getEmployeePayRateOnDate (optional, but good practice)

// Function to calculate and return current leave used
function getLeaveUsed($pdo, $employee_id) {
    try {
        $sql = "
            SELECT 
                leave_type, 
                SUM(DATEDIFF(end_date, start_date) + 1) AS total_days_used
            FROM leave_requests 
            WHERE employee_id = ? AND status = 'Approved'
            GROUP BY leave_type
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $used = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns [leave_type => total_days_used]

        $result = [
            'Vacation' => floatval($used['Vacation'] ?? 0),
            'Sick Leave' => floatval($used['Sick Leave'] ?? 0),
            'Personal Day' => floatval($used['Personal Day'] ?? 0),
            'Annual Leave' => floatval($used['Annual Leave'] ?? 0)
        ];
        // Note: Maternity/Paternity is tracked but not part of the standard balance calculation here.

        return $result;
    } catch (PDOException $e) {
        error_log("Error fetching used leave: " . $e->getMessage());
        return ['Vacation' => 0, 'Sick Leave' => 0, 'Personal Day' => 0, 'Annual Leave' => 0];
    }
}

try {
    // 1. Get the employee's defined annual accrual policy
    $sql_policy = "SELECT * FROM leave_balances WHERE employee_id = ?";
    $stmt_policy = $pdo->prepare($sql_policy);
    $stmt_policy->execute([$employee_id]);
    $policy = $stmt_policy->fetch(PDO::FETCH_ASSOC);

    // Set accrual days based on policy or default constants
    $accrual = [
        'Vacation' => floatval($policy['vacation_days_accrued'] ?? DEFAULT_VACATION_DAYS),
        'Sick Leave' => floatval($policy['sick_days_accrued'] ?? DEFAULT_SICK_DAYS),
        'Personal Day' => floatval($policy['personal_days_accrued'] ?? DEFAULT_PERSONAL_DAYS),
        'Annual Leave' => floatval($policy['annual_days_accrued'] ?? DEFAULT_ANNUAL_DAYS),
    ];

    // 2. Get the total leave days already used
    $used = getLeaveUsed($pdo, $employee_id);

    // 3. Calculate Balances
    $balances = [];
    foreach ($accrual as $type => $days_accrued) {
        $key = $type; // Matches the key from LEAVE_TYPES
        $days_used = $used[$key] ?? 0;
        $balance = $days_accrued - $days_used;

        $balances[$key] = [
            'accrued' => $days_accrued,
            'used' => $days_used,
            'available' => max(0, round($balance, 1)) // Ensure balance is not negative
        ];
    }

    echo json_encode(['success' => true, 'data' => $balances]);

} catch (PDOException $e) {
    error_log('Get Leave Balances Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error retrieving leave balances.']);
}
?>
