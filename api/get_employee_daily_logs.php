<?php
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Include DB connection
require_once __DIR__ . '/db_connect.php';

// Include Utils
require_once __DIR__ . '/../config/utils.php';

// --- Basic Auth / Session Check (Mock for now or rely on existing session if same domain) ---
// real app should use token. For simplicity, we assume public or basic check.
// In a real scenario, you'd check headers for authorization token.

// Get Parameters
$employee_id = $_GET['employee_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);
    exit;
}

// Fetch Global Settings for Grace Period
// (Assuming session might not be active if called from mobile app directly, fetch from DB)
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
$settings = [];
while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$late_grace_period = isset($settings['late_grace_period_minutes']) ? (int)$settings['late_grace_period_minutes'] : 0;
// Set timezone from settings if available
if (!empty($settings['timezone'])) {
    date_default_timezone_set($settings['timezone']);
}

// Fetch Employee Details
$stmt_emp = $pdo->prepare("SELECT first_name, last_name, department FROM employees WHERE employee_id = ?");
$stmt_emp->execute([$employee_id]);
$employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// --- Logic from view_employee_logs.php ---

function getEmployeeAttendanceLogsLocal($pdo, $employee_id, $start_date, $end_date) {
    try {
        $sql = "SELECT log_id, time_in, time_out, log_date, scheduled_start_time, remarks
                FROM attendance_logs
                WHERE employee_id = ? AND log_date BETWEEN ? AND ?
                ORDER BY log_date DESC, time_in ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id, $start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// 1. Fetch Standard Schedule
$stmt_std = $pdo->prepare("SELECT * FROM standard_schedules WHERE employee_id = ?");
$stmt_std->execute([$employee_id]);
$standard_schedule = $stmt_std->fetch(PDO::FETCH_ASSOC);

// 2. Fetch Schedule Exceptions
$stmt_ex = $pdo->prepare("SELECT * FROM schedules WHERE employee_id = ? AND work_date BETWEEN ? AND ?");
$stmt_ex->execute([$employee_id, $start_date, $end_date]);
$exceptions_raw = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
$exceptions_map = [];
foreach ($exceptions_raw as $ex) {
    $exceptions_map[$ex['work_date']] = $ex;
}

// 3. Fetch Logs
$logs = getEmployeeAttendanceLogsLocal($pdo, $employee_id, $start_date, $end_date);
$logs_map = [];
foreach ($logs as $log) {
    $logs_map[$log['log_date']][] = $log;
}

// 4. Fetch Approved Leaves
$stmt_leave = $pdo->prepare("SELECT start_date, end_date, leave_type FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND start_date <= ? AND end_date >= ?");
$stmt_leave->execute([$employee_id, $end_date, $start_date]);
$leaves = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);

$leave_map = [];
foreach ($leaves as $leave) {
    $l_start = new DateTime($leave['start_date']);
    $l_end = new DateTime($leave['end_date']);
    $eff_start = max($l_start, new DateTime($start_date));
    $eff_end = min($l_end, new DateTime($end_date));
    
    while ($eff_start <= $eff_end) {
        $leave_map[$eff_start->format('Y-m-d')] = $leave['leave_type'];
        $eff_start->modify('+1 day');
    }
}

// 5. Generate Full Date Range & Merge
$daily_aggregated_logs = [];
$current = new DateTime($start_date);
$end = new DateTime($end_date);

$standard_schedules_map = [];
if ($standard_schedule) {
    $standard_schedules_map[$employee_id] = $standard_schedule;
}

while ($current <= $end) {
    $date_str = $current->format('Y-m-d');
    
    $day_data = [
        'log_date' => $date_str,
        'segments' => [],
        'status' => '',
        'is_rest_day' => false,
        'daily_rate' => 0.00,
        'deduction_amount' => 0.00,
        'total_hours' => 0.00
    ];

    // Fetch Pay Rate
    $pay_info = getEmployeePayRateOnDate($pdo, $employee_id, $date_str); // From config/utils.php
    $pay_rate = $pay_info ? (float)$pay_info['pay_rate'] : 0.00;
    $pay_type = $pay_info ? $pay_info['pay_type'] : 'N/A';
    
    if ($pay_type === 'Daily') {
        $day_data['daily_rate'] = $pay_rate;
    } elseif ($pay_type === 'Hourly') {
        $day_data['daily_rate'] = $pay_rate * 8; 
    } elseif ($pay_type === 'Fix Rate') {
        $day_data['daily_rate'] = $pay_rate / 22; 
    }

    $expected_pay = 0.00;
    $actual_pay = 0.00;
    
    $sched_details = getScheduleDetails($employee_id, $date_str, $standard_schedules_map); // From config/utils.php
    
    if (isset($exceptions_map[$date_str])) {
        $ex = $exceptions_map[$date_str];
        if (!empty($ex['shift_start']) && !empty($ex['shift_end'])) {
            $sched_start = new DateTime("$date_str " . $ex['shift_start']);
            $sched_end = new DateTime("$date_str " . $ex['shift_end']);
            if ($sched_end <= $sched_start) $sched_end->modify('+1 day');
            
            $diff = $sched_start->diff($sched_end);
            $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
            
            $sched_details = [
                'hours' => round($hours, 2),
                'start' => $sched_start,
                'end' => $sched_end
            ];
        } else {
             $sched_details['hours'] = 0; 
        }
    }

    $standard_hours_today = $sched_details['hours'];
    $sched_start = $sched_details['start'];
    $sched_end = $sched_details['end'];

    if ($standard_hours_today > 0) {
        if ($pay_type === 'Daily') {
            $expected_pay = $pay_rate;
        } elseif ($pay_type === 'Hourly') {
            $expected_pay = $standard_hours_today * $pay_rate;
        }
    }

    $total_hours_worked = 0.00;

    if ($standard_hours_today > 0 && $sched_start && $sched_end && isset($logs_map[$date_str])) {
        $payable_hours_today = 0.00;
        foreach ($logs_map[$date_str] as $log) {
            if ($log['time_in'] && $log['time_out']) {
                $actual_in = new DateTime($log['time_in']);
                $actual_out = new DateTime($log['time_out']);
                
                // Track total hours irrespective of schedule
                $diff = $actual_out->getTimestamp() - $actual_in->getTimestamp();
                $total_hours_worked += ($diff / 3600);

                // Grace Period
                if ($late_grace_period > 0 && $actual_in > $sched_start) {
                    $diff_minutes = ($actual_in->getTimestamp() - $sched_start->getTimestamp()) / 60;
                    if ($diff_minutes <= $late_grace_period) {
                        $actual_in = clone $sched_start;
                    }
                }

                $overlap_start = max($sched_start, $actual_in);
                $overlap_end = min($sched_end, $actual_out);
                
                if ($overlap_start < $overlap_end) {
                    $payable_seconds = $overlap_end->getTimestamp() - $overlap_start->getTimestamp();
                    $payable_hours_today += round($payable_seconds / 3600, 2);
                }
            }
        }

        if ($pay_type === 'Daily') {
            $hourly_rate = $pay_rate / $standard_hours_today;
            $actual_pay = $payable_hours_today * $hourly_rate;
        } elseif ($pay_type === 'Hourly') {
            $actual_pay = $payable_hours_today * $pay_rate;
        }
    }

    if ($pay_type !== 'Fix Rate') {
        $deduction = max(0, $expected_pay - $actual_pay);
        $day_data['deduction_amount'] = round($deduction, 2);
    }

    $has_schedule = ($standard_hours_today > 0);

    if (isset($logs_map[$date_str])) {
        $day_data['segments'] = $logs_map[$date_str];
        $day_data['status'] = 'Present'; 
    } elseif (isset($leave_map[$date_str])) {
        $day_data['status'] = $leave_map[$date_str];
        if (in_array($leave_map[$date_str], ['Vacation', 'Sick Leave', 'Personal Day'])) {
             $day_data['deduction_amount'] = 0.00;
        }
    } else {
        if ($has_schedule) {
            $day_data['status'] = 'Absent';
            if ($pay_type !== 'Fix Rate') {
                $day_data['deduction_amount'] = round($expected_pay, 2);
            }
        } else {
            $day_data['status'] = 'Rest Day';
            $day_data['is_rest_day'] = true;
        }
    }
    
    $day_data['total_hours'] = round($total_hours_worked, 2);

    $daily_aggregated_logs[] = $day_data;
    $current->modify('+1 day');
}

// Sort descending
usort($daily_aggregated_logs, function($a, $b) {
    return strtotime($b['log_date']) - strtotime($a['log_date']);
});

echo json_encode([
    'success' => true,
    'employee' => $employee,
    'logs' => $daily_aggregated_logs
]);
