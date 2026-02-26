<?php
// FILENAME: employee/api/generate_payroll.php
session_start();
header('Content-Type: application/json');
ob_start(); // CRITICAL FIX 1: Start output buffering immediately

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // For calculateDuration and getEmployeePayRateOnDate, and logging

$admin_id = $_SESSION['user_id']; // For logging
$admin_role = $_SESSION['role'];

$data = json_decode(file_get_contents('php://input'), true);

$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$admin_password = $data['admin_password'] ?? null;

// --- Password Verification ---
if (empty($admin_password)) {
    ob_end_clean();
    log_action($pdo, $admin_id, 'PAYROLL_AUTH_FAILED', "Missing password for payroll generation.");
    echo json_encode(['success' => false, 'message' => 'Your password is required to generate payroll.']);
    exit;
}
try {
    $stmt_pass = $pdo->prepare("SELECT password_hash FROM users WHERE employee_id = ?");
    $stmt_pass->execute([$admin_id]);
    $admin_hash = $stmt_pass->fetchColumn();
    if (!$admin_hash || !password_verify($admin_password, $admin_hash)) {
        ob_end_clean(); log_action($pdo, $admin_id, 'PAYROLL_AUTH_FAILED', "Incorrect password.");
        echo json_encode(['success' => false, 'message' => 'Password incorrect. Payroll generation cancelled.']); exit;
    }
} catch (PDOException $e) {
    ob_end_clean();
    error_log('Payroll Auth DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during authentication.']);
    exit;
}
// --- END Password Verification ---


if (empty($start_date) || empty($end_date)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required.']);
    exit;
}

// Date Conversion & Validation
try {
    $dt_start = new DateTime($start_date);
    $dt_end = new DateTime($end_date);
    $pay_period_days = $dt_end->diff($dt_start)->days + 1;
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid date format provided.']);
    exit;
}

// Fetch Active Deductions & CA Name
$active_deductions = []; $ca_deduction_type_name = $_SESSION['settings']['system_ca_deduction_name'] ?? 'Cash Advance';
// Get Grace Period
$late_grace_period = isset($_SESSION['settings']['late_grace_period_minutes']) ? (int)$_SESSION['settings']['late_grace_period_minutes'] : 0;

try {
    // FIX 2: Removed deleted_at filter since the column might not exist after cleanup
    // Fetch ALL active deductions, including employee-specific ones
    $stmt_deductions = $pdo->query("SELECT * FROM deduction_types WHERE is_active = TRUE");
    $active_deductions = $stmt_deductions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Deduction Exclusions Map
    $exclusions_map = [];
    try {
        $stmt_exclusions = $pdo->query("SELECT deduction_id, employee_id FROM deduction_exclusions");
        $all_exclusions = $stmt_exclusions->fetchAll(PDO::FETCH_ASSOC);
        foreach ($all_exclusions as $ex) {
            $exclusions_map[$ex['deduction_id']][] = $ex['employee_id'];
        }
    } catch (PDOException $e) { /* exclusions table might not exist yet */ }

} catch (PDOException $e) {
    error_log('Warning: Deduction types table query failed: ' . $e->getMessage());
}

// --- NEW: Fetch Active Allowances ---
$active_allowances = [];
$allowance_exclusions_map = [];
try {
    $stmt_allowances = $pdo->query("SELECT * FROM allowance_types WHERE is_active = TRUE");
    $active_allowances = $stmt_allowances->fetchAll(PDO::FETCH_ASSOC);

    $stmt_allowance_ex = $pdo->query("SELECT allowance_id, employee_id FROM allowance_exclusions");
    $all_allowance_ex = $stmt_allowance_ex->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_allowance_ex as $ex) {
        $allowance_exclusions_map[$ex['allowance_id']][] = $ex['employee_id'];
    }
} catch (PDOException $e) {
    error_log('Warning: Allowance table query failed: ' . $e->getMessage());
}

// --- NEW: Fetch Standard Schedules for ALL employees upfront ---
$standard_schedules_map = [];
try {
    $stmt_std = $pdo->query("SELECT * FROM standard_schedules");
    $schedules_raw = $stmt_std->fetchAll(PDO::FETCH_ASSOC);
    foreach($schedules_raw as $sched) {
        $standard_schedules_map[$sched['employee_id']] = $sched;
    }
} catch (PDOException $e) {
    error_log("Warning: Could not fetch standard schedules. Daily/Hourly calculations might be inaccurate. " . $e->getMessage());
}

// --- NEW: Fetch Schedule Exceptions for ALL employees in range ---
$exceptions_map = [];
try {
    $stmt_ex = $pdo->prepare("SELECT * FROM schedules WHERE work_date BETWEEN ? AND ?");
    $stmt_ex->execute([$start_date, $end_date]);
    $exceptions_raw = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
    foreach($exceptions_raw as $ex) {
        $exceptions_map[$ex['employee_id']][$ex['work_date']] = $ex;
    }
} catch (PDOException $e) {
    error_log("Warning: Could not fetch schedule exceptions. " . $e->getMessage());
}

// --- NEW Helper Function: Get standard schedule details for a specific day ---
// MOVED TO config/utils.php
// function getScheduleDetails($employee_id, $date_str, $schedules_map) { ... }


try {
    $pdo->beginTransaction();

    // Fetch employees (Only Active)
    $sql_employees = "SELECT employee_id, first_name, last_name, is_flexible_schedule FROM employees WHERE status = 'Active' ORDER BY employee_id";
    $stmt_employees = $pdo->query($sql_employees); $employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);
    $payroll_results = []; $total_processed = 0;

    // --- Main Payroll Loop ---
    foreach ($employees as $emp) {
        $employee_id = $emp['employee_id'];
        $is_flexible = !empty($emp['is_flexible_schedule']); // Check flexible status
        $gross_pay = 0.00; $total_payable_hours = 0.00; $total_paid_leave_days = 0;
        $deductions = 0.00; $ca_deducted_amount_final = 0.00;
        $standard_deductions_total = 0.00; $standard_deductions_breakdown = [];
        $worked_hours_pay = 0.00; // Pay from actual work logs
        $leave_pay = 0.00; // Pay from approved leave
        $attendance_deductions = 0.00; // NEW: Track lost pay due to late/absent

        // Fetch Pay Rate for the period end date
        $pay_data = getEmployeePayRateOnDate($pdo, $employee_id, $end_date);
        if (!$pay_data || empty($pay_data['pay_type']) || $pay_data['pay_rate'] <= 0) {
            log_action($pdo, $admin_id, 'PAYROLL_SKIP', "Skipped EID {$employee_id}: No active/valid pay rate.");
            $payroll_results[] = ['employee_id' => $employee_id, 'name' => "{$emp['first_name']} {$emp['last_name']}", 'status' => 'Skipped', 'reason' => 'No active/valid pay rate defined.', 'standard_deductions_total' => 0.00, 'ca_deducted' => 0.00];
            continue;
        }
        $pay_type = $pay_data['pay_type'];
        $pay_rate = (float)$pay_data['pay_rate']; // This is Annual for Salary, Daily for Daily, Hourly for Hourly, Per-Period for Fix Rate

        // --- NEW: Calculate Expected Gross Pay (Potential) ---
        $expected_gross_pay = 0.00;
        if ($pay_type === 'Fix Rate') {
            $expected_gross_pay = $pay_rate;
        } else {
            // For Daily/Hourly, sum up potential earnings for all scheduled days in period
            $p_start = new DateTime($start_date);
            $p_end = new DateTime($end_date);
            while ($p_start <= $p_end) {
                $d_str = $p_start->format('Y-m-d');
                $sched_details = getScheduleDetails($employee_id, $d_str, $standard_schedules_map);

                // --- NEW: Apply Exception Override ---
                if (isset($exceptions_map[$employee_id][$d_str])) {
                    $ex = $exceptions_map[$employee_id][$d_str];
                    if (!empty($ex['shift_start']) && !empty($ex['shift_end'])) {
                        $ex_start = new DateTime("$d_str " . $ex['shift_start']);
                        $ex_end = new DateTime("$d_str " . $ex['shift_end']);
                        if ($ex_end <= $ex_start) $ex_end->modify('+1 day');
                        $diff = $ex_start->diff($ex_end);
                        $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
                        $sched_details = [
                            'hours' => round($hours, 2),
                            'start' => $ex_start,
                            'end' => $ex_end
                        ];
                    } else {
                        // Exception exists but empty start/end -> OFF DAY
                        $sched_details = ['hours' => 0, 'start' => null, 'end' => null];
                    }
                }
                // --- END NEW ---

                $std_h = $sched_details['hours'];
                if ($std_h > 0) {
                    if ($pay_type === 'Daily') {
                        $expected_gross_pay += $pay_rate;
                    } elseif ($pay_type === 'Hourly') {
                        $expected_gross_pay += ($std_h * $pay_rate);
                    }
                }
                $p_start->modify('+1 day');
            }
        }
        // --- END NEW ---

        // Fetch Approved Paid Leave Days within the period
        $sql_leave = "SELECT start_date, end_date FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND leave_type IN ('Vacation', 'Sick Leave', 'Personal Day', 'Annual Leave') AND start_date <= ? AND end_date >= ?";
        $stmt_leave = $pdo->prepare($sql_leave);
        $stmt_leave->execute([$employee_id, $end_date, $start_date]);
        $approved_leaves = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);
        foreach ($approved_leaves as $leave) {
            try {
                $leave_start = new DateTime($leave['start_date']);
                $leave_end = new DateTime($leave['end_date']);
                $effective_start = max($dt_start, $leave_start);
                $effective_end = min($dt_end, $leave_end);
                if ($effective_start <= $effective_end) {
                    $interval = $effective_start->diff($effective_end);
                    $total_paid_leave_days += $interval->days + 1;
                }
            } catch (Exception $e) { error_log("Leave date calculation error: " . $e->getMessage()); }
        }


        // --- Calculate Gross Pay Based on NEW Pay Type Logic ---

        if ($pay_type === 'Fix Rate') {
            // Gross pay is simply the stored rate for the period. Attendance/Leave don't affect it.
            $gross_pay = round($pay_rate, 2);

        } else { // Daily or Hourly - requires attendance logs and standard schedules

            // Fetch ALL COMPLETED segments within the period (UNGROUPED for strict overlap calculation)
            $sql_logs = "
                SELECT 
                    log_date, 
                    time_in,
                    time_out
                FROM attendance_logs 
                WHERE employee_id = ? 
                AND log_date BETWEEN ? AND ? 
                AND time_out IS NOT NULL 
                ORDER BY log_date ASC, time_in ASC
            ";
            $stmt_logs = $pdo->prepare($sql_logs);
            $stmt_logs->execute([$employee_id, $start_date, $end_date]);
            $raw_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

            $daily_effective_hourly_rate = 0; // Only used for Daily type

            // Loop through each log segment
            foreach ($raw_logs as $log) {
                try {
                    $log_date_str = $log['log_date'];
                    $sched_details = getScheduleDetails($employee_id, $log_date_str, $standard_schedules_map);

                    // --- NEW: Apply Exception Override ---
                    if (isset($exceptions_map[$employee_id][$log_date_str])) {
                        $ex = $exceptions_map[$employee_id][$log_date_str];
                        if (!empty($ex['shift_start']) && !empty($ex['shift_end'])) {
                            $ex_start = new DateTime("$log_date_str " . $ex['shift_start']);
                            $ex_end = new DateTime("$log_date_str " . $ex['shift_end']);
                            if ($ex_end <= $ex_start) $ex_end->modify('+1 day');
                            $diff = $ex_start->diff($ex_end);
                            $hours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
                            $sched_details = [
                                'hours' => round($hours, 2),
                                'start' => $ex_start,
                                'end' => $ex_end
                            ];
                        } else {
                            // Exception exists but empty start/end -> OFF DAY
                            $sched_details = ['hours' => 0, 'start' => null, 'end' => null];
                        }
                    }
                    // --- END NEW ---

                    $standard_hours_today = $sched_details['hours'];
                    $sched_start = $sched_details['start'];
                    $sched_end = $sched_details['end'];

                    $payable_hours_log = 0.00;

                    // FLEXIBLE SCHEDULE LOGIC
                    if ($is_flexible) {
                        $actual_in = new DateTime($log['time_in']);
                        $actual_out = new DateTime($log['time_out']);

                        // Handle overnight or same-time
                        if ($actual_out <= $actual_in) $actual_out->modify('+1 day');

                        $diff_seconds = $actual_out->getTimestamp() - $actual_in->getTimestamp();
                        $payable_hours_log = round($diff_seconds / 3600, 2);

                        // For Daily Rate employees who are flexible, we must assume a standard divisor
                        // if they don't have a schedule for today. Creating a virtual 8-hour standard.
                        if ($pay_type === 'Daily' && $standard_hours_today == 0) {
                            $standard_hours_today = 8.00; 
                        }
                    } 
                    // STANDARD STRICT SCHEDULE LOGIC
                    elseif ($standard_hours_today > 0 && $sched_start && $sched_end) {
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

                        // STRICT LATE DEDUCTION LOGIC:
                        // Only pay for the time that overlaps with the scheduled shift.
                        $overlap_start = max($sched_start, $actual_in);
                        $overlap_end = min($sched_end, $actual_out);

                        if ($overlap_start < $overlap_end) {
                            $payable_seconds = $overlap_end->getTimestamp() - $overlap_start->getTimestamp();
                            $payable_hours_log = round($payable_seconds / 3600, 2);
                        }
                    } else {
                        // No standard schedule (Off day). Strict policy: No pay for unscheduled work unless approved (OT).
                        $payable_hours_log = 0.00;
                    }

                    if ($pay_type === 'Daily') {
                        if ($standard_hours_today > 0) {
                            $daily_effective_hourly_rate = $pay_rate / $standard_hours_today;
                            $worked_hours_pay += round($payable_hours_log * $daily_effective_hourly_rate, 2);
                            $total_payable_hours += $payable_hours_log;
                        }
                    }
                    elseif ($pay_type === 'Hourly') {
                        $worked_hours_pay += round($payable_hours_log * $pay_rate, 2);
                        $total_payable_hours += $payable_hours_log;
                    }

                } catch (Exception $e) {
                    error_log("Payroll calculation error for EID {$employee_id} on {$log_date_str}: " . $e->getMessage());
                }
            } // End foreach log loop

            // --- Calculate Leave Pay ---
            if ($total_paid_leave_days > 0) {
                if ($pay_type === 'Daily') {
                    $leave_pay = round($total_paid_leave_days * $pay_rate, 2);
                } elseif ($pay_type === 'Hourly') {
                    // Assume a standard 8-hour day for leave pay calculation
                    $leave_pay = round($total_paid_leave_days * 8.00 * $pay_rate, 2);
                    // Add leave hours to total payable for Hourly to be stored correctly
                    $total_payable_hours += round($total_paid_leave_days * 8.00, 2);
                }
            }

            // --- Final Gross Pay for Daily/Hourly ---
            $gross_pay = round($worked_hours_pay + $leave_pay, 2);

        } // End if Fix Rate else block

        // --- NEW: Calculate Allowances ---
        $allowances_total = 0.00;
        if (!empty($active_allowances)) {
            foreach ($active_allowances as $allowance_type) {
                // Filter: Apply if Global (employee_id is NULL) OR Specific to this employee
                $is_global = empty($allowance_type['employee_id']);
                $is_for_me = !$is_global && $allowance_type['employee_id'] == $employee_id;
                
                // Excluded Check
                $is_excluded = isset($allowance_exclusions_map[$allowance_type['allowance_id']]) 
                               && in_array($employee_id, $allowance_exclusions_map[$allowance_type['allowance_id']]);

                if (($is_global || $is_for_me) && !$is_excluded) {
                    $allowance_value = (float)$allowance_type['value'];
                    $amount = 0.00;
                    if ($allowance_type['type'] === 'Fixed') {
                        $amount = $allowance_value;
                    } elseif ($allowance_type['type'] === 'Percentage') {
                        // Percentage usually based on Basic Pay (Gross before allowances)
                        $amount = ($gross_pay * ($allowance_value / 100));
                    }
                    $allowances_total += round($amount, 2);
                }
            }
        }

        // Add Allowances to Gross Pay
        // Note: Gross Pay usually includes allowances. 
        // Logic: Gross Pay (from hours) -> Base Pay. Total Gross = Base + Allowances.
        // We will update $gross_pay to include allowances for Net Pay calculation.
        $base_gross_pay = $gross_pay; // Keep original for reference if needed
        $gross_pay += $allowances_total;

        // --- NEW: Calculate Attendance Deduction (Late/Absent) ---
        // Difference between what they COULD have earned vs what they DID earn
        $attendance_deductions = max(0, round($expected_gross_pay - $base_gross_pay, 2));


        // --- Calculate Standard Deductions & Breakdown (Based on final gross_pay) ---
        if ($gross_pay > 0) {
            foreach ($active_deductions as $deduction_type) {
                // Filter: Apply if Global (employee_id is NULL) OR Specific to this employee
                $is_global = empty($deduction_type['employee_id']);
                $is_for_me = !$is_global && $deduction_type['employee_id'] == $employee_id;
                
                // Excluded Check
                $is_excluded = isset($exclusions_map[$deduction_type['deduction_id']]) 
                               && in_array($employee_id, $exclusions_map[$deduction_type['deduction_id']]);

                if (($is_global || $is_for_me) && !$is_excluded) {
                    $deduction_value = (float)$deduction_type['value']; $amount = 0.00;
                    if ($deduction_type['type'] === 'Fixed') { $amount = $deduction_value; }
                    elseif ($deduction_type['type'] === 'Percentage') { $amount = ($gross_pay * ($deduction_value / 100)); }
                    $amount = round($amount, 2);
                    $standard_deductions_total += $amount;
                    $standard_deductions_breakdown[] = ['name' => $deduction_type['name'], 'amount' => $amount];
                }
            }
        }
        $deductions = $standard_deductions_total;

        // --- Calculate CA/VALE Deduction (using new pending_amount column) ---
        $ca_total_pending_amount = 0.00; $max_deduction_room = max(0, $gross_pay - $standard_deductions_total);
        // FIX 3: Select pending_amount and original_amount
        // FILTER BY DATE RANGE as requested: Only deduct CAs that fall within the payroll period
        $sql_ca = "SELECT transaction_id, pending_amount, original_amount FROM ca_transactions WHERE employee_id = ? AND deducted_in_payroll = FALSE AND deleted_at IS NULL AND pending_amount > 0 AND transaction_date BETWEEN ? AND ? ORDER BY created_at ASC";
        $stmt_ca = $pdo->prepare($sql_ca); 
        $stmt_ca->execute([$employee_id, $start_date, $end_date]); 
        $ca_transactions_list = $stmt_ca->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ca_transactions_list as $ca) { $ca_total_pending_amount += (float)$ca['pending_amount']; }
        $ca_deducted_amount_final = min($ca_total_pending_amount, $max_deduction_room);
        $ca_deducted_amount_final = max(0, round($ca_deducted_amount_final, 2));
        $deductions += $ca_deducted_amount_final;

        // Calculate Net Pay
        $net_pay = round($gross_pay - $deductions, 2);
        if ($net_pay < 0) { $net_pay = 0.00; $deductions = $gross_pay; } // Cap net pay at zero

        // Insert into Payroll & Update CA (Only if gross pay >= 0)
        if ($gross_pay >= 0) {

            if ($gross_pay == 0 && $ca_deducted_amount_final == 0) {
                log_action($pdo, $admin_id, 'PAYROLL_SKIP', "Skipped EID {$employee_id}: Zero gross pay calculated.");
                $payroll_results[] = ['employee_id' => $employee_id, 'name' => "{$emp['first_name']} {$emp['last_name']}", 'status' => 'Skipped', 'reason' => 'Zero gross pay calculated.', 'standard_deductions_total' => 0.00, 'ca_deducted' => 0.00];
                continue;
            }

            // Insert into payroll table
            $sql_insert = "INSERT INTO payroll
                            (employee_id, pay_period_start, pay_period_end, gross_pay, allowances, attendance_deductions, deductions, net_pay, status,
                             pay_type_used, pay_rate_used, total_payable_hours, total_paid_leave_days)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Processed', ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                $employee_id, $start_date, $end_date, $gross_pay, $allowances_total, $attendance_deductions, $deductions, $net_pay,
                $pay_type, $pay_rate, round($total_payable_hours, 2), $total_paid_leave_days
            ]);
            $payroll_id = $pdo->lastInsertId();
            $total_processed++;

            // --- NEW: Insert Detailed Deduction Logs ---
            if (!empty($standard_deductions_breakdown)) {
                $sql_log_deduction = "INSERT INTO payroll_deduction_logs (payroll_id, deduction_name, amount) VALUES (?, ?, ?)";
                $stmt_log_deduction = $pdo->prepare($sql_log_deduction);
                foreach ($standard_deductions_breakdown as $item) {
                    $stmt_log_deduction->execute([$payroll_id, $item['name'], $item['amount']]);
                }
            }
            // --- END NEW ---

            // Update CA Transactions
            if ($ca_deducted_amount_final > 0) {
                $amount_to_collect = $ca_deducted_amount_final; $update_ca_log = [];
                foreach ($ca_transactions_list as $ca) {
                    $transaction_id = $ca['transaction_id']; $pending_amount = (float)$ca['pending_amount'];
                    if ($amount_to_collect <= 0) break;

                    // The amount actually deducted from this specific transaction
                    $deduction_from_this_ca = min($pending_amount, $amount_to_collect);
                    $new_pending_amount = $pending_amount - $deduction_from_this_ca;
                    $is_fully_deducted = $new_pending_amount <= 0.005; // Use tolerance for float check

                    // FIX 4: Update the pending_amount column and deducted_in_payroll flag
                    $sql_update_ca = "UPDATE ca_transactions SET pending_amount = ?, deducted_in_payroll = ?, payroll_id = ? WHERE transaction_id = ?";
                    $pdo->prepare($sql_update_ca)->execute([
                        max(0, $new_pending_amount), // Ensure pending_amount is not negative
                        $is_fully_deducted,
                        $payroll_id,
                        $transaction_id
                    ]);

                    // NEW: Log to deduction history
                    $sql_hist = "INSERT INTO ca_deductions_history (transaction_id, payroll_id, amount, deduction_date) VALUES (?, ?, ?, ?)";
                    $pdo->prepare($sql_hist)->execute([$transaction_id, $payroll_id, $deduction_from_this_ca, $end_date]);


                    $amount_to_collect -= $deduction_from_this_ca;

                    $log_type = $is_fully_deducted ? "Full ded. {$deduction_from_this_ca}" : "Partial ded. {$deduction_from_this_ca}";
                    $update_ca_log[] = "{$log_type} (TID {$transaction_id}). Remaining: " . number_format(max(0, $new_pending_amount), 2);
                }
                log_action($pdo, $admin_id, 'CA_DEDUCTED', "Ded. {$ca_deducted_amount_final} CA for EID {$employee_id} (PID {$payroll_id}). Details: " . implode('; ', $update_ca_log));
            }

            // Prepare result message
            $details_message = '';
            switch ($pay_type) {
                case 'Fix Rate':
                    $details_message = "Fixed rate for the period.";
                    break;
                case 'Daily':
                    $details_message = "Based on daily rate and capped hours." . ($total_paid_leave_days > 0 ? " Incl. {$total_paid_leave_days} leave day(s)." : "");
                    break;
                case 'Hourly':
                    $details_message = "{$total_payable_hours} total payable hours (capped daily)." . ($total_paid_leave_days > 0 ? " Incl. {$total_paid_leave_days} leave day(s)." : "");
                    break;
                default:
                    $details_message = 'N/A';
                    break;
            }

            // Store detailed result
            $payroll_results[] = [
                'employee_id' => $employee_id, 'name' => "{$emp['first_name']} {$emp['last_name']}",
                'gross_pay' => $gross_pay, 'deductions' => $deductions, 'net_pay' => $net_pay,
                'status' => "Processed ({$pay_type})", 'details' => $details_message,
                'standard_deductions_total' => $standard_deductions_total,
                'ca_deducted' => $ca_deducted_amount_final
            ];
        } else {
            // Gross pay is negative (should be capped at zero gross/deductions earlier, but for safety)
            log_action($pdo, $admin_id, 'PAYROLL_SKIP', "Skipped EID {$employee_id}: Net pay negative after final calculation.");
            $payroll_results[] = ['employee_id' => $employee_id, 'name' => "{$emp['first_name']} {$emp['last_name']}", 'status' => 'Skipped', 'reason' => 'Net pay resulted in a negative amount.', 'standard_deductions_total' => 0.00, 'ca_deducted' => 0.00];
        }
    } // End foreach employee loop

    $pdo->commit();

    log_action($pdo, $admin_id, 'PAYROLL_GENERATED', "Payroll run completed for period {$start_date} to {$end_date}. Processed {$total_processed} payslips.");

    // CRITICAL FIX 3: End buffer and ensure only JSON is outputted
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => "Payroll run complete. Processed {$total_processed} records.", 'details' => $payroll_results ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Payroll Generation DB Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'PAYROLL_DB_ERROR', "DB Error on run {$start_date} to {$end_date}: " . $e->getMessage());
    // CRITICAL FIX 4: End buffer on error and return JSON
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Payroll Generation General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'PAYROLL_GENERAL_ERROR', "General Error on run {$start_date} to {$end_date}: " . $e->getMessage());
    // CRITICAL FIX 5: End buffer on general error and return JSON
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'System Error during payroll calculation: ' . $e->getMessage()]);
}
?>
