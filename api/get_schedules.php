<?php
// FILENAME: employee/api/get_schedules.php
session_start();
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

$start_date_str = $_GET['start_date'] ?? null;
$end_date_str = $_GET['end_date'] ?? null;

if (empty($start_date_str) || empty($end_date_str)) {
    echo json_encode(['error' => 'Start and end dates are required.']);
    exit;
}

try {
    // --- FIX: Removed redundant setAttribute line that was causing the error ---
    // Your db_connect.php file already sets the default fetch mode.

    // --- FIX: Use prepare/execute instead of query() ---
    // This is more robust and ensures PDOExceptions are thrown on failure,
    // which prevents the HTML/JSON parse error.

    // 1. Get all employees
    $stmt_employees = $pdo->prepare("SELECT employee_id FROM employees");
    $stmt_employees->execute();
    $employees = $stmt_employees->fetchAll();

    // 2. Get all standard schedules, keyed by employee_id
    $stmt_std = $pdo->prepare("SELECT * FROM standard_schedules");
    $stmt_std->execute();
    $standard_schedules_raw = $stmt_std->fetchAll();
    // --- End Fix ---

    $standard_map = [];
    foreach ($standard_schedules_raw as $ss) {
        $standard_map[$ss['employee_id']] = $ss;
    }

    // 3. Get all exceptions for the date range, keyed by 'employee_id_date'
    $stmt_exceptions = $pdo->prepare("SELECT * FROM schedules WHERE work_date BETWEEN ? AND ?");
    $stmt_exceptions->execute([$start_date_str, $end_date_str]);
    $exceptions_raw = $stmt_exceptions->fetchAll();
    $exception_map = [];
    foreach ($exceptions_raw as $ex) {
        $exception_map[$ex['employee_id'] . '_' . $ex['work_date']] = $ex;
    }

    // --- Logic to build the merged schedule ---
    $merged_schedules = [];
    $current_date = new DateTime($start_date_str);
    $end_date = new DateTime($end_date_str);

    while ($current_date <= $end_date) {
        $date_str = $current_date->format('Y-m-d');
        // Get day of week, e.g., 'mon', 'tue'
        $day_of_week = strtolower($current_date->format('D')); // 'Mon', 'Tue' -> 'mon', 'tue'

        foreach ($employees as $emp) {
            $emp_id = $emp['employee_id'];
            $schedule_entry = [
                'employee_id' => $emp_id,
                'work_date' => $date_str,
                'shift_start' => null,
                'shift_end' => null,
                'schedule_id' => null, // This is the *exception* id
                'type' => 'off'
            ];

            // Check for an EXCEPTION first
            if (isset($exception_map[$emp_id . '_' . $date_str])) {
                $exception = $exception_map[$emp_id . '_' . $date_str];
                $schedule_entry['shift_start'] = $exception['shift_start'];
                $schedule_entry['shift_end'] = $exception['shift_end'];
                $schedule_entry['schedule_id'] = $exception['schedule_id'];
                $schedule_entry['type'] = 'exception';

            }
            // If no exception, check for a STANDARD shift
            else if (isset($standard_map[$emp_id]) && !empty($standard_map[$emp_id][$day_of_week . '_start'])) {
                $standard_shift = $standard_map[$emp_id];
                $schedule_entry['shift_start'] = $standard_shift[$day_of_week . '_start'];
                $schedule_entry['shift_end'] = $standard_shift[$day_of_week . '_end'];
                $schedule_entry['type'] = 'standard';
            }

            // If no exception and no standard shift, it remains 'off'
            $merged_schedules[] = $schedule_entry;
        }

        $current_date->modify('+1 day');
    }

    echo json_encode($merged_schedules);

} catch (PDOException $e) {
    // This will now correctly catch errors from prepare/execute
    error_log('Get Schedules Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

