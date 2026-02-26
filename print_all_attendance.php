<?php
// FILENAME: employee/print_all_attendance.php
$pageTitle = 'Print All Attendance Logs';
include 'template/header.php'; // Handles session, auth, DB

// --- Role Check ---
if (!in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Unauthorized Access.</div>";
    include 'template/footer.php';
    exit;
}

// --- TIMEZONE FIX AND INITIALIZATION ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

require_once __DIR__ . '/config/utils.php';

// Get Parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$department = $_GET['department'] ?? 'all';

// 1. Fetch Employees based on Department (Only Active)
$sql = "SELECT employee_id, first_name, last_name, department FROM employees WHERE status = 'Active'";
$params = [];
if ($department !== 'all') {
    $sql .= " AND department = ?";
    $params[] = $department;
}
$sql .= " ORDER BY last_name, first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Helper Function (Duplicated/Adapted from view_employee_logs.php) ---
function getEmployeeAttendanceLogs($pdo, $employee_id, $start_date, $end_date) {
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

// Get Grace Period
$late_grace_period = isset($_SESSION['settings']['late_grace_period_minutes']) ? (int)$_SESSION['settings']['late_grace_period_minutes'] : 0;

?>

<style>
    @media print {
        @page { 
            size: A4; 
            margin: 1cm; 
        }
        body { 
            background-color: white; 
            font-family: sans-serif;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
            font-size: 12pt;
        }
        .page-break { 
            page-break-after: always; 
            break-after: page;
            display: block;
            height: 0;
            clear: both;
            visibility: hidden;
        }
        .no-print { display: none !important; }
        
        /* Reset Layout - Force Block Flow */
        main, body, .print-container { 
            display: block !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            overflow: visible !important;
            position: static !important;
        }

        /* Employee Block Wrapper */
        .employee-section {
            display: block !important;
            width: 100% !important;
            clear: both !important;
            margin-bottom: 20px;
            position: relative !important;
        }

        /* Header */
        .employee-header {
            display: block !important;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            page-break-inside: avoid;
        }

        /* Stats Container - Use Inline-Block for safety */
        .print-stats-container {
            display: block !important;
            width: 100%;
            margin-bottom: 20px !important;
            font-size: 0; /* Remove whitespace between inline-blocks */
        }
        .print-stat-box {
            display: inline-block !important;
            width: 24%; /* Adjusted for 4 boxes */
            margin-right: 1%;
            border: 1px solid #ddd !important;
            padding: 10px;
            background-color: #f9fafb !important;
            vertical-align: top;
            box-sizing: border-box;
            font-size: 12pt; /* Reset font size */
        }
        .print-stat-box:last-child {
            margin-right: 0;
        }

        /* Table */
        table.log-table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            margin-top: 20px !important; /* Explicit space */
            clear: both !important;
        }
        table.log-table th, table.log-table td {
            border: 1px solid #ccc !important;
            padding: 6px 8px !important;
            font-size: 10pt;
            background-color: white !important; /* Ensure no transparency */
        }
        table.log-table thead {
            display: table-header-group;
        }
        table.log-table tr {
            page-break-inside: avoid;
        }
    }
    
    /* Screen styles (default) */
    .log-table th, .log-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .log-table th { background-color: #f2f2f2; }
</style>

<div class="bg-white p-8 rounded-xl shadow-xl print-container">
    <div class="flex justify-between items-center mb-6 no-print">
        <h2 class="text-2xl font-semibold text-gray-800">Batch Print Attendance Logs</h2>
        <div class="space-x-2">
            <span class="text-sm text-gray-500">Recommended: A4 Paper, Portrait</span>
            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                <i class="fas fa-print mr-2"></i> Print All
            </button>
        </div>
    </div>

    <?php if (empty($employees)): ?>
        <p class="text-center text-gray-500">No employees found.</p>
    <?php else: ?>
        <?php foreach ($employees as $index => $emp): 
            $employee_id = $emp['employee_id'];
            $employee_name = $emp['first_name'] . ' ' . $emp['last_name'];
            
            // Fetch Data for this employee
            // 1. Standard Schedule
            $stmt_std = $pdo->prepare("SELECT * FROM standard_schedules WHERE employee_id = ?");
            $stmt_std->execute([$employee_id]);
            $standard_schedule = $stmt_std->fetch(PDO::FETCH_ASSOC);

            // 2. Exceptions
            $stmt_ex = $pdo->prepare("SELECT * FROM schedules WHERE employee_id = ? AND work_date BETWEEN ? AND ?");
            $stmt_ex->execute([$employee_id, $start_date, $end_date]);
            $exceptions_raw = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
            $exceptions_map = [];
            foreach ($exceptions_raw as $ex) { $exceptions_map[$ex['work_date']] = $ex; }

            // 3. Logs
            $logs = getEmployeeAttendanceLogs($pdo, $employee_id, $start_date, $end_date);
            $logs_map = [];
            foreach ($logs as $log) { $logs_map[$log['log_date']][] = $log; }

            // 4. Leaves
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

            // 5. Pay Rate (For Deduction Calculation)
            $pay_data = getEmployeePayRateOnDate($pdo, $employee_id, $end_date);
            $pay_rate = (float)($pay_data['pay_rate'] ?? 0);
            $pay_type = $pay_data['pay_type'] ?? 'None';

            // Calculate Totals
            $total_hours = 0;
            $total_lates = 0;
            $total_absences = 0;
            $total_deductions = 0;
            
            // Iterate dates
            $current = new DateTime($end_date); // Descending
            $start = new DateTime($start_date);
            
            $rows = [];
            while ($current >= $start) {
                $date_str = $current->format('Y-m-d');
                $day_of_week = strtolower($current->format('D'));
                
                // Determine Schedule
                $is_rest_day = false;
                $sched_start = null;
                $sched_end = null;
                
                if (isset($exceptions_map[$date_str])) {
                    $sched_start = $exceptions_map[$date_str]['shift_start'];
                    $sched_end = $exceptions_map[$date_str]['shift_end'];
                } elseif ($standard_schedule) {
                    $sched_start = $standard_schedule[$day_of_week . '_start'];
                    $sched_end = $standard_schedule[$day_of_week . '_end'];
                    if (empty($sched_start) || empty($sched_end)) $is_rest_day = true;
                } else {
                    $is_rest_day = true;
                }

                // Get Logs
                $day_logs = $logs_map[$date_str] ?? [];
                $time_in = $day_logs[0]['time_in'] ?? null;
                $time_out = end($day_logs)['time_out'] ?? null;
                
                // Status & Deduction Logic
                $status = '';
                $status_color = 'text-gray-800';
                $late_mins = 0;
                $daily_deduction = 0;

                // Calculate Scheduled Hours & Hourly Rate for this day
                $sched_hours = 0;
                $hourly_rate = 0;
                if ($sched_start && $sched_end) {
                    $s_start_calc = new DateTime($date_str . ' ' . $sched_start);
                    $s_end_calc = new DateTime($date_str . ' ' . $sched_end);
                    if ($s_end_calc <= $s_start_calc) $s_end_calc->modify('+1 day');
                    $diff = $s_start_calc->diff($s_end_calc);
                    $sched_hours = $diff->h + ($diff->i / 60);
                }

                if ($pay_type !== 'Fix Rate' && $pay_rate > 0 && $sched_hours > 0) {
                    if ($pay_type === 'Hourly') {
                        $hourly_rate = $pay_rate;
                    } elseif ($pay_type === 'Daily') {
                        $hourly_rate = $pay_rate / $sched_hours;
                    }
                }
                
                if (isset($leave_map[$date_str])) {
                    $status = "On Leave (" . $leave_map[$date_str] . ")";
                    $status_color = 'text-blue-600';
                } elseif (!empty($day_logs)) {
                    // Calculate Hours
                    if ($time_in && $time_out) {
                        $t_in = new DateTime($time_in);
                        $t_out = new DateTime($time_out);
                        $diff = $t_in->diff($t_out);
                        $hours = $diff->h + ($diff->i / 60);
                        $total_hours += $hours;
                    }
                    
                    // Check Late
                    if ($sched_start && $time_in) {
                        $s_start = new DateTime($date_str . ' ' . $sched_start);
                        $t_in = new DateTime($time_in);
                        if ($t_in > $s_start) {
                            $late_diff = $s_start->diff($t_in);
                            $late_mins = ($late_diff->h * 60) + $late_diff->i;
                            if ($late_mins > $late_grace_period) {
                                $status = 'Late';
                                $status_color = 'text-orange-600';
                                $total_lates++;
                                // Calculate Late Deduction
                                $daily_deduction += ($late_mins / 60) * $hourly_rate;
                            } else {
                                $status = 'Present';
                                $status_color = 'text-green-600';
                            }
                        } else {
                            $status = 'Present';
                            $status_color = 'text-green-600';
                        }
                    } else {
                        $status = 'Present';
                        $status_color = 'text-green-600';
                    }
                } elseif (!$is_rest_day && $current < new DateTime()) {
                    $status = 'Absent';
                    $status_color = 'text-red-600';
                    $total_absences++;
                    // Calculate Absent Deduction
                    $daily_deduction += $sched_hours * $hourly_rate;
                } elseif ($is_rest_day) {
                    $status = 'Rest Day';
                    $status_color = 'text-gray-400';
                }

                $total_deductions += $daily_deduction;

                $rows[] = [
                    'date' => $current->format('M d, Y (D)'),
                    'time_in' => $time_in ? date('h:i A', strtotime($time_in)) : '--:--',
                    'time_out' => $time_out ? date('h:i A', strtotime($time_out)) : '--:--',
                    'status' => $status,
                    'status_color' => $status_color,
                    'deduction' => $daily_deduction > 0 ? number_format($daily_deduction, 2) : '-'
                ];

                $current->modify('-1 day');
            }
        ?>

        <!-- Employee Report Block -->
        <div class="employee-section">
            <div class="employee-header">
                <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($employee_name); ?></h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($emp['department']); ?> | ID: <?php echo htmlspecialchars($employee_id); ?></p>
                <p class="text-sm text-gray-500 mt-1">Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>

            <!-- Stats Grid: Using Inline-Block for Print Safety -->
            <div class="print-stats-container">
                <div class="print-stat-box">
                    <span class="block text-xs text-gray-500 uppercase">Total Hours</span>
                    <span class="block text-lg font-bold text-indigo-600"><?php echo number_format($total_hours, 2); ?></span>
                </div>
                <div class="print-stat-box">
                    <span class="block text-xs text-gray-500 uppercase">Lates</span>
                    <span class="block text-lg font-bold text-orange-600"><?php echo $total_lates; ?></span>
                </div>
                <div class="print-stat-box">
                    <span class="block text-xs text-gray-500 uppercase">Absences</span>
                    <span class="block text-lg font-bold text-red-600"><?php echo $total_absences; ?></span>
                </div>
                <div class="print-stat-box">
                    <span class="block text-xs text-gray-500 uppercase">Est. Deduction</span>
                    <span class="block text-lg font-bold text-red-800"><?php echo number_format($total_deductions, 2); ?></span>
                </div>
            </div>

            <table class="log-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Deduction</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['time_in']; ?></td>
                            <td><?php echo $row['time_out']; ?></td>
                            <td class="font-medium <?php echo $row['status_color']; ?>"><?php echo $row['status']; ?></td>
                            <td class="text-red-600"><?php echo $row['deduction']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($index < count($employees) - 1): ?>
            <div class="page-break"></div>
        <?php endif; ?>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'template/footer.php'; ?>
