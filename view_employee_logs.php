<?php
// FILENAME: employee/view_employee_logs.php
$pageTitle = 'Employee Attendance Logs';
include 'template/header.php'; // Handles session, auth, DB

// --- Role Check ---
if (!in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Unauthorized Access.</div>";
    include 'template/footer.php';
    exit;
}

$is_manager = ($_SESSION['role'] === 'Manager');

// --- TIMEZONE FIX AND INITIALIZATION ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

// --- NEW: Include Global Utility Functions ---
require_once __DIR__ . '/config/utils.php';

// Get Grace Period from Settings
$late_grace_period = isset($_SESSION['settings']['late_grace_period_minutes']) ? (int)$_SESSION['settings']['late_grace_period_minutes'] : 0;

// Get Parameters
$employee_id = $_GET['employee_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: No employee specified.</div>";
    include 'template/footer.php';
    exit;
}

// Fetch Employee Details
$stmt_emp = $pdo->prepare("SELECT first_name, last_name, department FROM employees WHERE employee_id = ?");
$stmt_emp->execute([$employee_id]);
$employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Employee not found.</div>";
    include 'template/footer.php';
    exit;
}

$employee_name = $employee['first_name'] . ' ' . $employee['last_name'];

// --- Page-Specific PHP Logic: Fetch all segments for the employee within range ---
function getEmployeeAttendanceLogs($pdo, $employee_id, $start_date, $end_date) {
    try {
        // Select all logs for the employee within the date range
        $sql = "SELECT log_id, time_in, time_out, log_date, scheduled_start_time, remarks
                FROM attendance_logs
                WHERE employee_id = ? AND log_date BETWEEN ? AND ?
                ORDER BY log_date DESC, time_in ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id, $start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employee attendance logs: " . $e->getMessage());
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
$logs = getEmployeeAttendanceLogs($pdo, $employee_id, $start_date, $end_date);
$logs_map = [];
foreach ($logs as $log) {
    $logs_map[$log['log_date']][] = $log;
}

// 4. Fetch Approved Leaves
$stmt_leave = $pdo->prepare("SELECT start_date, end_date, leave_type FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND start_date <= ? AND end_date >= ?");
$stmt_leave->execute([$employee_id, $end_date, $start_date]);
$leaves = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);

// Create a map of dates covered by leave
$leave_map = [];
foreach ($leaves as $leave) {
    $l_start = new DateTime($leave['start_date']);
    $l_end = new DateTime($leave['end_date']);
    // Clamp to the requested range for iteration safety, though logic handles it
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

// Prepare standard schedules map for getScheduleDetails
$standard_schedules_map = [];
if ($standard_schedule) {
    $standard_schedules_map[$employee_id] = $standard_schedule;
}

while ($current <= $end) {
    $date_str = $current->format('Y-m-d');
    $day_of_week = strtolower($current->format('D')); // mon, tue...

    $day_data = [
        'log_date' => $date_str,
        'segments' => [],
        'status' => '',
        'is_rest_day' => false,
        'daily_rate' => 0.00,
        'deduction_amount' => 0.00
    ];

    // --- Fetch Pay Rate for this day ---
    $pay_info = getEmployeePayRateOnDate($pdo, $employee_id, $date_str);
    $pay_rate = $pay_info ? (float)$pay_info['pay_rate'] : 0.00;
    $pay_type = $pay_info ? $pay_info['pay_type'] : 'N/A';
    
    // Calculate Daily Rate for display
    if ($pay_type === 'Daily') {
        $day_data['daily_rate'] = $pay_rate;
    } elseif ($pay_type === 'Hourly') {
        $day_data['daily_rate'] = $pay_rate * 8; // Estimate
    } elseif ($pay_type === 'Fix Rate') {
        // Estimate daily rate from monthly (approx 22 days or 30 days depending on policy, using 22 for work days)
        $day_data['daily_rate'] = $pay_rate / 22; 
    }

    // --- Calculate Deduction (Strict Logic) ---
    $expected_pay = 0.00;
    $actual_pay = 0.00;
    
    // Get Schedule Details
    $sched_details = getScheduleDetails($employee_id, $date_str, $standard_schedules_map);
    
    // Override with Exception if exists
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
             $sched_details['hours'] = 0; // Exception set to OFF
        }
    }

    $standard_hours_today = $sched_details['hours'];
    $sched_start = $sched_details['start'];
    $sched_end = $sched_details['end'];

    // Calculate Expected Pay
    if ($standard_hours_today > 0) {
        if ($pay_type === 'Daily') {
            $expected_pay = $pay_rate;
        } elseif ($pay_type === 'Hourly') {
            $expected_pay = $standard_hours_today * $pay_rate;
        }
    }

    // Calculate Actual Pay (Overlap)
    if ($standard_hours_today > 0 && $sched_start && $sched_end && isset($logs_map[$date_str])) {
        $payable_hours_today = 0.00;
        foreach ($logs_map[$date_str] as $log) {
            if ($log['time_in'] && $log['time_out']) {
                $actual_in = new DateTime($log['time_in']);
                $actual_out = new DateTime($log['time_out']);
                
                // --- GRACE PERIOD LOGIC ---
                // If actual_in is after sched_start but within grace period, treat as on time
                if ($late_grace_period > 0 && $actual_in > $sched_start) {
                    $diff_minutes = ($actual_in->getTimestamp() - $sched_start->getTimestamp()) / 60;
                    if ($diff_minutes <= $late_grace_period) {
                        $actual_in = clone $sched_start; // Forgive lateness
                    }
                }
                // --------------------------

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

    // Calculate Deduction
    if ($pay_type !== 'Fix Rate') {
        $deduction = max(0, $expected_pay - $actual_pay);
        $day_data['deduction_amount'] = round($deduction, 2);
    }

    // Check Schedule (Is it a work day?)
    $has_schedule = ($standard_hours_today > 0);

    // Check Logs
    if (isset($logs_map[$date_str])) {
        $day_data['segments'] = $logs_map[$date_str];
        $day_data['status'] = 'Present'; 
    } elseif (isset($leave_map[$date_str])) {
        // Approved Leave
        $day_data['status'] = $leave_map[$date_str]; // e.g., "Vacation"
        // If it's a paid leave type, remove the deduction
        if (in_array($leave_map[$date_str], ['Vacation', 'Sick Leave', 'Personal Day'])) {
             $day_data['deduction_amount'] = 0.00;
        }
    } else {
        // No logs and No Leave
        if ($has_schedule) {
            $day_data['status'] = 'Absent';
            // Full deduction for absence
            if ($pay_type !== 'Fix Rate') {
                $day_data['deduction_amount'] = round($expected_pay, 2);
            }
        } else {
            $day_data['status'] = 'Rest Day';
            $day_data['is_rest_day'] = true;
        }
    }

    $daily_aggregated_logs[] = $day_data;
    $current->modify('+1 day');
}

// Sort descending (Newest first)
usort($daily_aggregated_logs, function($a, $b) {
    return strtotime($b['log_date']) - strtotime($a['log_date']);
});

// Convert to an indexed array for easier JavaScript iteration
$json_logs = json_encode(array_values($daily_aggregated_logs));

?>

<div class="bg-white p-8 rounded-xl shadow-xl print-container">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">Attendance Logs</h2>
            <p class="text-gray-600 mt-1">Employee: <span class="font-bold text-indigo-600"><?php echo htmlspecialchars($employee_name); ?></span></p>
            <p class="text-sm text-gray-500">Period: <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
        </div>
        <div class="print-hide">
            <button onclick="window.print()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm">
                <i class="fas fa-print mr-2"></i> Print Logs
            </button>
            <button onclick="window.close()" class="ml-2 px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 shadow-sm">
                Close
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 border">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    First Clock-In
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Clock-Out
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total Hours
                </th>
                <?php if (!$is_manager): ?>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Daily Rate
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deduction
                </th>
                <?php endif; ?>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Break Time
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Remarks
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider print-hide">
                    Details
                </th>
            </tr>
            </thead>
            <tbody id="log-body" class="bg-white divide-y divide-gray-200">
            <!-- Content populated by JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- Print Footer -->
    <div class="mt-8 pt-4 border-t text-center text-xs text-gray-500 hidden print-block">
        Generated on <?php echo date('M j, Y H:i'); ?>
    </div>
</div>

<style>
    @media print {
        .print-hide { display: none !important; }
        .print-block { display: block !important; }
        .print-container { box-shadow: none !important; padding: 0 !important; }
        body { background-color: white !important; }
        /* Hide sidebar and header if they exist in template */
        nav, aside, header { display: none !important; }
        /* Ensure table fits */
        table { width: 100% !important; font-size: 10pt; }
        td, th { padding: 4px 8px !important; }
    }
</style>

<!-- Edit Modal -->
<div id="editModal" class="fixed z-30 inset-0 overflow-y-auto hidden print-hide" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="editModalTitle">
                            Edit Logs
                        </h3>
                        <div class="mt-4">
                            <div id="editSegmentsContainer" class="max-h-96 overflow-y-auto pr-2">
                                <!-- Segments will be injected here -->
                            </div>
                            
                            <div class="mt-4 flex justify-between items-center border-t pt-4">
                                <div class="text-sm font-medium text-gray-700">
                                    Total Duration: <span id="editTotalDuration" class="text-indigo-700 font-bold text-lg ml-1">0h 0m</span>
                                </div>
                                <button onclick="addSegment()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i> Add Segment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="saveChanges()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const allLogsData = <?php echo $json_logs; ?>;
    const logBody = document.getElementById('log-body');
    const timezone = '<?php echo htmlspecialchars($timezone); ?>';
    const isManager = <?php echo json_encode($is_manager); ?>;

    // Utility to format time to 'h:i:s A'
    function formatTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        try {
            const dt = new Date(dateTimeString);
            return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        } catch (e) {
            return 'Error';
        }
    }

    // Utility to format a duration in seconds to hours/minutes
    function formatDuration(totalSeconds) {
        if (isNaN(totalSeconds) || totalSeconds <= 0) return '0 hrs';
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.round((totalSeconds % 3600) / 60);

        if (hours > 0) {
            return `${hours} hrs ${minutes} min`;
        } else {
            return `${minutes} min`;
        }
    }

    // Main calculation and rendering function
    function renderLogs() {
        logBody.innerHTML = '';
        const colSpan = isManager ? 5 : 7; // Adjust colspan based on visible columns

        if (allLogsData.length === 0) {
            logBody.innerHTML = `<tr><td colspan="${colSpan}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No attendance logs found for this period.</td></tr>`;
            return;
        }

        let grandTotalSeconds = 0;

        allLogsData.forEach((dayLog, index) => {
            const segments = dayLog.segments;
            
            // --- Handle Absent / Rest Day (No Segments) ---
            if (!segments || segments.length === 0) {
                let statusColor = 'text-gray-500 italic';
                let rowClass = '';

                if (dayLog.status === 'Absent') {
                    statusColor = 'text-red-600 font-bold';
                    rowClass = 'bg-red-50';
                } else if (dayLog.status === 'Rest Day') {
                    statusColor = 'text-gray-400 italic';
                } else {
                    // Assume Leave (Vacation, Sick Leave, etc.)
                    statusColor = 'text-green-600 font-bold';
                    rowClass = 'bg-green-50';
                }

                const row = document.createElement('tr');
                row.className = rowClass;
                
                // Format currency
                const dailyRate = parseFloat(dayLog.daily_rate).toFixed(2);
                const deduction = parseFloat(dayLog.deduction_amount).toFixed(2);
                const deductionClass = dayLog.deduction_amount > 0 ? 'text-red-600 font-bold' : 'text-gray-400';

                let rateAndDeductionCells = '';
                if (!isManager) {
                    rateAndDeductionCells = `
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">${dailyRate > 0 ? dailyRate : '-'}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm ${deductionClass}">${deduction > 0 ? deduction : '-'}</td>
                    `;
                }

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${dayLog.log_date}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-400">-</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-400">-</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-400">-</td>
                    ${rateAndDeductionCells}
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-400">-</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm ${statusColor}">
                        ${dayLog.status}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium print-hide">
                         <!-- Allow adding a log for absent days? For now just empty or maybe an Add button later -->
                         <button onclick='openEditModal(${index})' class="text-blue-600 hover:text-blue-900 mr-2" title="Add/Edit Log">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick='openActionModal("${dayLog.log_date}")' class="text-green-600 hover:text-green-900" title="Manage Schedule / Leave">
                             <i class="fas fa-calendar-plus"></i>
                        </button>
                    </td>
                `;
                logBody.appendChild(row);
                return; // Skip the rest of the loop for this day
            }

            // --- Handle Present Days ---
            let totalSecondsWorked = 0;
            let totalBreakSeconds = 0;
            let firstClockIn = null;
            let lastClockOut = null;
            let currentClockOutTime = null;

            // 1. Calculate Worked Time and Break Time
            segments.forEach((segment, index) => {
                const timeIn = segment.time_in;
                const timeOut = segment.time_out;

                if (timeIn) {
                    if (!firstClockIn) firstClockIn = new Date(timeIn);

                    if (timeOut) {
                        const timeInDt = new Date(timeIn);
                        let timeOutDt = new Date(timeOut);

                        // Handle midnight crossover for shift duration calculation
                        if (timeOutDt < timeInDt) {
                            timeOutDt.setDate(timeOutDt.getDate() + 1);
                        }

                        const segmentDurationSeconds = (timeOutDt.getTime() - timeInDt.getTime()) / 1000;
                        totalSecondsWorked += segmentDurationSeconds;

                        // Calculate break time: break is the gap between this segment's clock-out and the next segment's clock-in
                        if (index < segments.length - 1) {
                            const nextTimeIn = segments[index + 1].time_in;
                            if (nextTimeIn) {
                                const breakStart = timeOutDt;
                                const breakEnd = new Date(nextTimeIn);
                                if (breakEnd > breakStart) { // Only count forward breaks
                                    const breakDurationSeconds = (breakEnd.getTime() - breakStart.getTime()) / 1000;
                                    totalBreakSeconds += breakDurationSeconds;
                                }
                            }
                        }

                        lastClockOut = timeOutDt;
                        currentClockOutTime = timeOut; // Keep the string for the last display column
                    } else {
                        // Employee is currently clocked in (unclosed segment)
                        lastClockOut = '<span class="text-indigo-600 font-semibold">ACTIVE</span>';
                        currentClockOutTime = null;
                    }
                }
            });

            grandTotalSeconds += totalSecondsWorked;

            // 2. Build Details for Modal/Tooltip
            let segmentDetails = segments.map((segment, index) => {
                if (!segment.time_in) return '';

                const segmentTimeIn = formatTime(segment.time_in);
                const segmentTimeOut = segment.time_out ? formatTime(segment.time_out) : '<span class="text-indigo-600">ACTIVE</span>';

                let status = 'Completed';
                let duration = '';

                if (segment.time_in && segment.time_out) {
                    const durationMs = new Date(segment.time_out).getTime() - new Date(segment.time_in).getTime();
                    duration = ` (${formatDuration(durationMs / 1000)})`;
                } else {
                    status = 'Active';
                }

                // If this is not the last segment, include the following break time
                let breakLine = '';
                if (index < segments.length - 1 && segments[index].time_out && segments[index + 1].time_in) {
                    const breakStart = new Date(segments[index].time_out);
                    const breakEnd = new Date(segments[index + 1].time_in);
                    const breakDurationSeconds = (breakEnd.getTime() - breakStart.getTime()) / 1000;

                    if (breakDurationSeconds > 0) {
                        breakLine = `<div class="text-xs text-red-500 ml-4 border-l pl-2">Break: ${formatDuration(breakDurationSeconds)}</div>`;
                    }
                }


                return `
                    <div class="mb-2 p-2 rounded-md ${status === 'Active' ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50'}">
                        <strong>Segment ${index + 1}:</strong> ${segmentTimeIn} to ${segmentTimeOut} ${duration}
                    </div>
                    ${breakLine}
                `;
            }).join('');

            const totalDurationMinutes = totalSecondsWorked / 60;
            const totalBreakMinutes = totalBreakSeconds / 60;

            // Collect remarks from all segments
            const remarksList = segments.map(s => s.remarks).filter(r => r).join(', ');

            // Format currency
            const dailyRate = parseFloat(dayLog.daily_rate).toFixed(2);
            const deduction = parseFloat(dayLog.deduction_amount).toFixed(2);
            const deductionClass = dayLog.deduction_amount > 0 ? 'text-red-600 font-bold' : 'text-green-600';

            let rateAndDeductionCells = '';
            if (!isManager) {
                rateAndDeductionCells = `
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">${dailyRate > 0 ? dailyRate : '-'}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm ${deductionClass}">${deduction > 0 ? deduction : '0.00'}</td>
                `;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${dayLog.log_date}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${formatTime(segments[0].time_in)}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm ${currentClockOutTime ? 'text-gray-500' : 'text-indigo-600 font-semibold'}">
                    ${currentClockOutTime ? formatTime(currentClockOutTime) : lastClockOut}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="${totalDurationMinutes < 420 ? 'text-red-600 font-medium' : 'text-green-600 font-medium'}">
                        ${(totalSecondsWorked / 3600).toFixed(2)} hrs
                    </span>
                </td>
                ${rateAndDeductionCells}
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="${totalBreakMinutes > 0 ? 'text-orange-600 font-medium' : 'text-gray-500'}">
                        ${formatDuration(totalBreakSeconds)}
                    </span>
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="text-xs font-semibold text-gray-600">
                        ${dayLog.status}
                    </span>
                    ${remarksList ? '<span class="text-xs font-bold text-red-600 ml-1">(' + remarksList + ')</span>' : ''}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium print-hide">
                    <button onclick='showDetailsModal("${dayLog.log_date}", \`${segmentDetails}\`)' class="text-indigo-600 hover:text-indigo-900 mr-2" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick='openEditModal(${index})' class="text-blue-600 hover:text-blue-900 mr-2" title="Edit Logs">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick='openActionModal("${dayLog.log_date}")' class="text-green-600 hover:text-green-900 mr-2" title="Manage Schedule / Leave">
                        <i class="fas fa-calendar-plus"></i>
                    </button>
                    <button onclick='deleteDayLogs(${index})' class="text-red-600 hover:text-red-900" title="Delete All Logs for Day">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            logBody.appendChild(row);
        });

        // Add Total Row
        const totalRow = document.createElement('tr');
        totalRow.className = 'bg-gray-100 font-bold border-t-2 border-gray-300';
        totalRow.innerHTML = `
            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right uppercase">
                Total Hours:
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-700">
                ${(grandTotalSeconds / 3600).toFixed(2)} hrs
            </td>
            <td colspan="${isManager ? 3 : 5}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"></td>
        `;
        logBody.appendChild(totalRow);
    }

    // --- Dynamic Modal for Details ---
    function showDetailsModal(date, detailsHtml) {
        let modal = document.getElementById('detailsModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'detailsModal';
            modal.className = 'fixed z-20 inset-0 overflow-y-auto hidden print-hide';
            modal.innerHTML = `
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeDetailsModal()" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-3" id="detailsModalTitle">
                                Log Segments for ${date}
                            </h3>
                            <div id="detailsModalBody" class="text-sm text-gray-700 max-h-80 overflow-y-auto">
                                <!-- Details inserted here -->
                </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeDetailsModal()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                Close
                </button>
                </div>
                </div>
                </div>
                `;
            document.body.appendChild(modal);
        }

        document.getElementById('detailsModalTitle').textContent = `Log Segments for ${date}`;
        document.getElementById('detailsModalBody').innerHTML = detailsHtml;
        modal.classList.remove('hidden');
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    // --- EDIT & DELETE FUNCTIONS ---
    let currentEditingIndex = null;
    let currentEditingSegments = [];
    let originalSegments = [];

    function openEditModal(index) {
        currentEditingIndex = index;
        const dayLog = allLogsData[index];
        // Deep copy segments to avoid modifying original data until saved
        originalSegments = JSON.parse(JSON.stringify(dayLog.segments || []));
        currentEditingSegments = JSON.parse(JSON.stringify(dayLog.segments || []));
        
        // If no segments (Absent/Rest Day), start with one empty segment pre-filled with date
        if (currentEditingSegments.length === 0) {
            currentEditingSegments.push({
                log_id: null,
                time_in: `${dayLog.log_date} 08:00:00`,
                time_out: `${dayLog.log_date} 17:00:00`,
                remarks: ''
            });
        }

        document.getElementById('editModalTitle').textContent = `Edit Logs for ${dayLog.log_date}`;
        renderEditSegments();
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        currentEditingIndex = null;
        currentEditingSegments = [];
    }

    function calculateSegmentDuration(start, end) {
        if (!start || !end) return '0h 0m';
        const startDate = new Date(start);
        const endDate = new Date(end);
        if (endDate <= startDate) return 'Invalid';
        const diffMs = endDate - startDate;
        const hours = Math.floor(diffMs / 3600000);
        const minutes = Math.round((diffMs % 3600000) / 60000);
        return `${hours}h ${minutes}m`;
    }

    function calculateTotalDuration() {
        let totalMs = 0;
        currentEditingSegments.forEach(seg => {
            if (seg.time_in && seg.time_out) {
                const start = new Date(seg.time_in);
                const end = new Date(seg.time_out);
                if (end > start) {
                    totalMs += (end - start);
                }
            }
        });
        const hours = Math.floor(totalMs / 3600000);
        const minutes = Math.round((totalMs % 3600000) / 60000);
        return `${hours}h ${minutes}m`;
    }

    function renderEditSegments() {
        const container = document.getElementById('editSegmentsContainer');
        container.innerHTML = '';

        if (currentEditingSegments.length === 0) {
             container.innerHTML = '<div class="text-center text-gray-500 py-8 italic border-2 border-dashed border-gray-300 rounded-lg">No time segments. Click "Add Segment" to create one.</div>';
        }

        currentEditingSegments.forEach((segment, idx) => {
            const row = document.createElement('div');
            row.className = 'relative group bg-white border border-gray-200 rounded-lg p-4 mb-3 shadow-sm hover:shadow-md transition-shadow duration-200';
            
            // Format dates for datetime-local input (YYYY-MM-DDTHH:MM)
            const formatForInput = (dateStr) => {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const pad = (n) => n < 10 ? '0' + n : n;
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            };

            let durationDisplay = '0h 0m';
            if (segment.time_in && segment.time_out) {
                 durationDisplay = calculateSegmentDuration(segment.time_in, segment.time_out);
            }

            row.innerHTML = `
                <div class="flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Time In</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-sign-in-alt text-green-500 text-lg"></i>
                            </div>
                            <input type="datetime-local" value="${formatForInput(segment.time_in)}" 
                                onchange="updateSegment(${idx}, 'time_in', this.value)"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 text-base border-gray-300 rounded-md py-3">
                        </div>
                    </div>
                    
                    <div class="hidden sm:flex flex-col items-center justify-center px-2 pt-6">
                        <i class="fas fa-arrow-right text-gray-300 text-xl"></i>
                    </div>

                    <div class="flex-1 w-full">
                        <label class="block text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Time Out</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-sign-out-alt text-red-500 text-lg"></i>
                            </div>
                            <input type="datetime-local" value="${formatForInput(segment.time_out)}" 
                                onchange="updateSegment(${idx}, 'time_out', this.value)"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 text-base border-gray-300 rounded-md py-3">
                        </div>
                    </div>

                    <div class="w-full sm:w-auto flex justify-between sm:block items-center pt-4 sm:pt-0">
                         <div class="sm:hidden text-sm font-medium text-gray-600">
                            Duration: <span id="duration-text-${idx}" class="text-indigo-600">${durationDisplay}</span>
                         </div>
                         <div class="flex items-center">
                             <button onclick="toggleRemarks(${idx})" class="text-gray-400 hover:text-indigo-600 transition-colors duration-200 p-3 rounded-full hover:bg-indigo-50 mr-1" title="Add/Edit Remarks">
                                <i class="fas fa-comment-alt text-xl"></i>
                            </button>
                             <button onclick="removeSegment(${idx})" class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-3 rounded-full hover:bg-red-50" title="Remove Segment">
                                <i class="fas fa-trash-alt text-xl"></i>
                            </button>
                         </div>
                    </div>
                </div>
                
                <!-- Remarks Section -->
                <div id="remarks-container-${idx}" class="${segment.remarks ? '' : 'hidden'} mt-3 pt-3 border-t border-gray-100">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Remarks</label>
                    <input type="text" value="${segment.remarks || ''}" 
                        onchange="updateSegment(${idx}, 'remarks', this.value)"
                        placeholder="Enter remarks (e.g. Late due to traffic)"
                        class="focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-gray-300 rounded-md py-2">
                </div>

                <div id="duration-badge-${idx}" class="hidden sm:block absolute top-0 right-0 -mt-2 -mr-2 bg-indigo-100 text-indigo-800 text-sm font-bold px-3 py-1 rounded-full shadow-sm border border-indigo-200">
                    ${durationDisplay}
                </div>
            `;
            container.appendChild(row);
        });

        updateTotalDurationDisplay();
    }

    function toggleRemarks(idx) {
        const container = document.getElementById(`remarks-container-${idx}`);
        if (container) {
            container.classList.toggle('hidden');
        }
    }

    function updateTotalDurationDisplay() {
        const totalDuration = calculateTotalDuration();
        const totalEl = document.getElementById('editTotalDuration');
        if(totalEl) totalEl.textContent = totalDuration;
    }

    function updateSegment(idx, field, value) {
        // For datetime-local, value is YYYY-MM-DDTHH:MM
        if (field === 'time_in' || field === 'time_out') {
            if (value) value = value.replace('T', ' ') + ':00';
        }
        currentEditingSegments[idx][field] = value;
        
        // Update duration display for this segment without re-rendering
        const segment = currentEditingSegments[idx];
        let durationDisplay = '0h 0m';
        if (segment.time_in && segment.time_out) {
             durationDisplay = calculateSegmentDuration(segment.time_in, segment.time_out);
        }
        
        const badgeEl = document.getElementById(`duration-badge-${idx}`);
        const textEl = document.getElementById(`duration-text-${idx}`);
        
        if (badgeEl) badgeEl.textContent = durationDisplay;
        if (textEl) textEl.textContent = durationDisplay;
        
        updateTotalDurationDisplay();
    }

    function addSegment() {
        const dayLog = allLogsData[currentEditingIndex];
        currentEditingSegments.push({
            log_id: null,
            time_in: `${dayLog.log_date} 08:00:00`,
            time_out: `${dayLog.log_date} 17:00:00`,
            remarks: ''
        });
        renderEditSegments();
    }

    function removeSegment(idx) {
        if (confirm('Remove this segment?')) {
            currentEditingSegments.splice(idx, 1);
            renderEditSegments();
        }
    }

    async function saveChanges() {
        // Confirmation removed as requested
        // if (!confirm('Are you sure you want to save these changes?')) return;

        const dayLog = allLogsData[currentEditingIndex];
        const employeeId = <?php echo json_encode($employee_id); ?>;
        const logDate = dayLog.log_date;

        // 1. Identify Deleted Segments (in original but not in current)
        const currentIds = currentEditingSegments.map(s => s.log_id).filter(id => id);
        const toDelete = originalSegments.filter(s => s.log_id && !currentIds.includes(s.log_id));

        // 2. Process Deletions
        for (const seg of toDelete) {
            try {
                await fetch('api/adjust_log.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ log_id: seg.log_id })
                });
            } catch (e) {
                console.error("Error deleting segment", e);
            }
        }

        // 3. Process Updates/Inserts
        for (const seg of currentEditingSegments) {
            // Skip empty segments
            if (!seg.time_in) continue;

            const payload = {
                log_id: seg.log_id,
                employee_id: employeeId,
                log_date: logDate,
                time_in: seg.time_in,
                time_out: seg.time_out,
                remarks: seg.remarks
            };

            try {
                await fetch('api/adjust_log.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
            } catch (e) {
                console.error("Error saving segment", e);
            }
        }

        alert('Changes saved.');
        location.reload();
    }

    async function deleteDayLogs(index) {
        if (!confirm('Are you sure you want to DELETE ALL logs for this day? This cannot be undone.')) return;

        const dayLog = allLogsData[index];
        const segments = dayLog.segments || [];

        if (segments.length === 0) {
            alert('No logs to delete.');
            return;
        }

        let successCount = 0;
        for (const seg of segments) {
            if (!seg.log_id) continue;
            try {
                const res = await fetch('api/adjust_log.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ log_id: seg.log_id })
                });
                const data = await res.json();
                if (data.success) successCount++;
            } catch (e) {
                console.error("Error deleting log", e);
            }
        }

        alert(`Deleted ${successCount} log(s).`);
        location.reload();
    }

    // --- Action Modal Functions ---
    let actionModal = null; // Initialize as null
    let currentActionDate = null;
    const currentEmployeeId = <?php echo json_encode($employee_id); ?>;

    function getActionModal() {
        if (!actionModal) {
            actionModal = document.getElementById('actionModal');
        }
        return actionModal;
    }

    function openActionModal(dateStr) {
        console.log('Opening Action Modal for date:', dateStr);
        const modal = getActionModal();
        if (!modal) {
            console.error("Action modal element not found!");
            alert("Error: Modal not found. Please refresh the page.");
            return;
        }

        currentActionDate = dateStr;
        const dateDisplay = document.getElementById('actionDateDisplay');
        if (dateDisplay) dateDisplay.textContent = new Date(dateStr).toDateString();
        
        const schedField = document.getElementById('action_schedule_date');
        if (schedField) schedField.value = dateStr;
        
        const leaveField = document.getElementById('action_leave_date');
        if (leaveField) leaveField.value = dateStr;
        
        modal.classList.remove('hidden');
    }

    function closeActionModal() {
        const modal = getActionModal();
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    async function setSchedule(type) {
        const payload = {
            employee_id: currentEmployeeId,
            work_date: currentActionDate,
            type: type // 'work_day' or 'off_day'
        };

        if (type === 'work_day') {
            const start = document.getElementById('sched_start').value;
            const end = document.getElementById('sched_end').value;
            if (!start || !end) {
                alert('Please enter start and end times for a work day.');
                return;
            }
            payload.shift_start = start;
            payload.shift_end = end;
        }

        try {
            const res = await fetch('api/add_schedule.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Network error.');
        }
    }

    async function submitLeaveRequest() {
        const type = document.getElementById('leave_type_select').value;
        const reason = document.getElementById('leave_reason').value;

        if (!type || !reason) {
            alert('Please select a leave type and provide a reason.');
            return;
        }

        const payload = {
            employee_id: currentEmployeeId,
            start_date: currentActionDate,
            end_date: currentActionDate,
            leave_type: type,
            reason: reason,
            status: 'Approved' // Auto-approve since admin is doing it from logs view
        };

        try {
            const res = await fetch('api/submit_leave_request.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                alert('Leave request submitted successfully.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Network error.');
        }
    }


    // Initial column setup
    document.addEventListener('DOMContentLoaded', () => {
        renderLogs();
        
        // Check for auto_print parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_print') === 'true') {
            // Small delay to ensure table is rendered before printing
            setTimeout(() => {
                window.print();
            }, 500);
        }
    });

</script>

<!-- Action Modal Structure -->
<div id="actionModal" class="fixed z-40 inset-0 overflow-y-auto hidden print-hide" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeActionModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-bold text-white flex items-center" id="actionModalTitle">
                    <i class="fas fa-calendar-alt mr-2 bg-white/20 p-2 rounded-lg"></i>
                    Manage Date: <span id="actionDateDisplay" class="ml-2 font-mono bg-white/10 px-2 py-0.5 rounded text-white/90"></span>
                </h3>
                <button onclick="closeActionModal()" class="text-white hover:text-gray-200 focus:outline-none transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="px-6 py-6 space-y-6 bg-gray-50/50">
                <!-- Section 1: Schedule -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center mb-4 border-b border-gray-100 pb-2">
                        <div class="bg-indigo-100 p-1.5 rounded-lg mr-3">
                            <i class="fas fa-clock text-indigo-600"></i>
                        </div>
                        <h4 class="font-bold text-gray-800">Schedule Exception</h4>
                    </div>
                    <input type="hidden" id="action_schedule_date">
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Start Time</label>
                            <input type="time" id="sched_start" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 text-gray-800">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">End Time</label>
                            <input type="time" id="sched_end" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 text-gray-800">
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="setSchedule('work_day')" class="flex-1 bg-indigo-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow flex justify-center items-center">
                            <i class="fas fa-save mr-2"></i> Set Shift
                        </button>
                        <button onclick="setSchedule('off_day')" class="flex-1 bg-gray-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-800 shadow-sm transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow flex justify-center items-center">
                            <i class="fas fa-bed mr-2"></i> Mark Off/Rest
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2 italic text-center">Overrides the standard schedule for this specific date.</p>
                </div>

                <!-- Section 2: Leave -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center mb-4 border-b border-gray-100 pb-2">
                        <div class="bg-green-100 p-1.5 rounded-lg mr-3">
                            <i class="fas fa-umbrella-beach text-green-600"></i>
                        </div>
                        <h4 class="font-bold text-gray-800">Add Leave Record</h4>
                    </div>
                    <input type="hidden" id="action_leave_date">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Leave Type</label>
                            <div class="relative">
                                <select id="leave_type_select" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-lg shadow-sm bg-gray-50">
                                    <option value="" disabled selected>Select a leave type...</option>
                                    <option value="Vacation">Vacation Leave</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Personal Day">Personal Day</option>
                                    <option value="Unpaid Leave">Unpaid Leave</option>
                                    <option value="Emergency Leave">Emergency Leave</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Reason / Remarks</label>
                            <textarea id="leave_reason" rows="2" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm bg-gray-50 resize-none" placeholder="Enter reason for leave..."></textarea>
                        </div>
                        <button onclick="submitLeaveRequest()" class="w-full bg-green-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-green-700 shadow-sm transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow flex justify-center items-center">
                            <i class="fas fa-check-circle mr-2"></i> Submit Approved Leave
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-100 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-gray-200">
                <button type="button" onclick="closeActionModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include 'template/footer.php';
?>
