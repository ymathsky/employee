<?php
// FILENAME: employee/api/team_attendance_overview.php
session_start();
header('Content-Type: application/json');

// Start output buffering to catch stray output
ob_start();

// --- Authorization Check ---
$manager_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$is_admin = in_array($user_role, ['HR Admin', 'Super Admin']);

if (!$manager_id || !in_array($user_role, ['HR Admin', 'Super Admin', 'Manager'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

// --- TIMEZONE FIX ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$day_of_week = date('D'); // e.g., Mon

try {
    // 1. Determine the scope (department)
    $scope_department = null;
    if (!$is_admin) {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id]);
        $scope_department = $stmt_dept->fetchColumn();
        if (empty($scope_department)) {
            ob_end_clean();
            log_action($pdo, $manager_id, 'TEAM_ATTENDANCE_FAILED', 'Manager not assigned a department.');
            echo json_encode(['success' => false, 'message' => 'You are not assigned to a department to view team attendance.']);
            exit;
        }
    }

    // 2. Fetch all team members in scope (excluding the manager if they are in the result set and not admin)
    $sql_employees = "
        SELECT e.employee_id, e.first_name, e.last_name, e.job_title, e.department
        FROM employees e
        WHERE 1=1
    ";
    $params = [];
    if (!$is_admin) {
        $sql_employees .= " AND e.department = ? AND e.employee_id != ?"; // Filter by department and exclude self
        $params[] = $scope_department;
        $params[] = $manager_id;
    }

    $sql_employees .= " ORDER BY e.last_name";
    $stmt_employees = $pdo->prepare($sql_employees);
    $stmt_employees->execute($params);
    $team_members = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

    if (empty($team_members)) {
        ob_end_clean();
        echo json_encode(['success' => true, 'data' => [], 'scope' => $scope_department]);
        exit;
    }

    $employee_ids = array_column($team_members, 'employee_id');
    $in_clause = implode(',', array_fill(0, count($employee_ids), '?'));

    // 3. Fetch today's attendance logs for all team members
    $sql_logs = "
        SELECT employee_id, time_in, time_out
        FROM attendance_logs
        WHERE employee_id IN ({$in_clause}) AND log_date = ?
    ";
    $stmt_logs = $pdo->prepare($sql_logs);
    $stmt_logs->execute(array_merge($employee_ids, [$current_date]));
    $today_logs = $stmt_logs->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Group by employee_id

    // 4. Fetch standard schedules (mon_start, mon_end, etc.)
    $sql_schedules = "SELECT employee_id, ".strtolower($day_of_week)."_start, ".strtolower($day_of_week)."_end FROM standard_schedules WHERE employee_id IN ({$in_clause})";
    $stmt_schedules = $pdo->prepare($sql_schedules);
    $stmt_schedules->execute($employee_ids);
    $schedules = $stmt_schedules->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_ASSOC); // Keyed by employee_id

    // 5. Merge and Determine Status
    $results = [];
    $day_start_col = strtolower($day_of_week) . '_start';
    $day_end_col = strtolower($day_of_week) . '_end';

    foreach ($team_members as $member) {
        $status = 'Off-Duty';
        $time_display = 'N/A';
        $log_data = $today_logs[$member['employee_id']][0] ?? null; // Assume one log per day

        // Check Schedule
        $schedule = $schedules[$member['employee_id']] ?? null;
        $scheduled_start = $schedule[$day_start_col] ?? null;
        $scheduled_end = $schedule[$day_end_col] ?? null;

        if (!empty($scheduled_start) && !empty($scheduled_end)) {
            $status = 'Scheduled Off'; // Assume scheduled off unless proven otherwise

            // Check if the current time falls outside the shift window
            // Since the shift can span midnight, we use date comparison.
            $dt_scheduled_start = new DateTime("{$current_date} {$scheduled_start}", new DateTimeZone($timezone));
            $dt_scheduled_end = new DateTime("{$current_date} {$scheduled_end}", new DateTimeZone($timezone));
            $dt_current_time = new DateTime('now', new DateTimeZone($timezone));

            // Handle overnight shifts for comparison
            if ($dt_scheduled_end <= $dt_scheduled_start) {
                // If end time is before or equal to start time, it spans to the next day
                if ($dt_current_time < $dt_scheduled_start) {
                    // If current time is before the start of the shift, the end time must be tomorrow
                    $dt_scheduled_start->modify('-1 day');
                } else {
                    // If current time is after the start of the shift, the end time must be today's date + 1 day
                    $dt_scheduled_end->modify('+1 day');
                }
            }

            if ($dt_current_time >= $dt_scheduled_start && $dt_current_time <= $dt_scheduled_end) {
                $status = 'Scheduled'; // Within shift window
            }

        } else {
            $status = 'Off-Duty (No Standard Schedule)';
        }


        // Override status based on log data
        if ($log_data) {
            if (empty($log_data['time_out'])) {
                // Currently Clocked In
                $status = 'Clocked In';
                $time_in_dt = new DateTime($log_data['time_in']);
                $time_display = 'Since ' . $time_in_dt->format('h:i A');
            } else {
                // Clocked Out
                $status = 'Clocked Out';
                $time_out_dt = new DateTime($log_data['time_out']);
                $time_display = 'At ' . $time_out_dt->format('h:i A');
            }
        }

        $results[] = array_merge($member, [
            'status' => $status,
            'time_display' => $time_display
        ]);
    }

    log_action($pdo, $manager_id, 'TEAM_ATTENDANCE_VIEWED', "Viewed real-time attendance for department: " . ($scope_department ?? 'All'));

    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $results, 'scope' => $scope_department]);

} catch (PDOException $e) {
    ob_end_clean();
    error_log('Team Attendance Overview DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error retrieving team data.']);
} catch (Exception $e) {
    ob_end_clean();
    error_log('Team Attendance Overview General Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error.']);
}
?>
