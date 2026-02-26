<?php
// FILENAME: employee/api/log_attendance.php
header('Content-Type: application/json');

require_once 'db_connect.php'; // Get the PDO connection
require_once __DIR__ . '/../config/utils.php'; // Load token utilities and logging

// --- CRITICAL FIX: Ensure Timezone is set for Kiosk (Public API) ---
session_start();

// Load settings from session, or fetch from DB if not in session
$timezone = 'UTC';
if (isset($_SESSION['settings']) && !empty($_SESSION['settings']['timezone'])) {
    $timezone = $_SESSION['settings']['timezone'];
} else {
    try {
        $stmt_tz = $pdo->query("SELECT setting_value FROM global_settings WHERE setting_key = 'timezone'");
        $db_timezone = $stmt_tz->fetchColumn();
        if ($db_timezone) {
            $timezone = $db_timezone;
        }
    } catch (PDOException $e) {
        error_log('Kiosk Timezone DB Fallback Error: ' . $e->getMessage());
    }
}

if (!@date_default_timezone_set($timezone)) {
    $timezone = 'UTC';
    date_default_timezone_set('UTC');
}
// --- END CRITICAL FIX ---


// Get the POST data from the kiosk
$data = json_decode(file_get_contents('php://input'), true);

$qr_token = $data['qr_token'] ?? null;
$pin_code = $data['pin'] ?? null;

// --- CRITICAL SECURITY: Validate the token and extract employee ID ---
$employee_id = false;

if (!empty($qr_token)) {
    // QR Code Logic
    $employee_id = validateSignedToken($qr_token);
} elseif (!empty($pin_code)) {
    // PIN Code Logic
    // Since we don't know the user, we must check against all active PIN secrets.
    // This is acceptable for small-medium businesses. For large scale, we'd need Employee ID + PIN.
    try {
        // Removed 'status' check as the column does not exist
        $stmt_secrets = $pdo->query("SELECT employee_id, pin_secret FROM employees WHERE pin_secret IS NOT NULL");
        while ($row = $stmt_secrets->fetch(PDO::FETCH_ASSOC)) {
            if (validateEmployeePIN($pin_code, $row['pin_secret'])) {
                $employee_id = $row['employee_id'];
                break; // Found the user!
            }
        }
    } catch (PDOException $e) {
        error_log("PIN Validation DB Error: " . $e->getMessage());
    }
}

if ($employee_id === false) {
    $error_msg = !empty($pin_code) ? 'Invalid PIN code. Please try again.' : 'Invalid or expired QR code.';
    log_action($pdo, 0, 'ATTENDANCE_FAILED', "Auth failed. QR: " . substr($qr_token ?? 'N/A', 0, 10) . "..., PIN: " . ($pin_code ? '****' : 'N/A'));
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}
// --- END SECURITY VALIDATION ---


// Get current time and date in the application's timezone
$dt = new DateTime('now', new DateTimeZone($timezone));

$log_datetime_full = $dt->format('Y-m-d H:i:s');
$log_date = $dt->format('Y-m-d');
$day_of_week = $dt->format('D'); // e.g., Sat


try {
    // 1. Check if employee exists and get their name
    $stmt = $pdo->prepare("SELECT first_name, last_name, employee_id, is_flexible_schedule FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        log_action($pdo, $employee_id, 'ATTENDANCE_FAILED', "Attempt to log attendance for non-existent EID {$employee_id}.");
        echo json_encode(['success' => false, 'message' => 'Employee ID not recognized.']);
        exit;
    }
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    $is_flexible = !empty($employee['is_flexible_schedule']);

    // Start Transaction
    $pdo->beginTransaction();

    // 2. Check for an existing clock-in
    $sql_check = "SELECT log_id, time_in, scheduled_start_time FROM attendance_logs 
                  WHERE employee_id = ? 
                  AND time_out IS NULL 
                  AND time_in NOT LIKE '0000-00-00%' 
                  ORDER BY time_in DESC LIMIT 1";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$employee_id]);
    $active_log = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($active_log) {
        // --- CLOCK OUT ---
        
        // SMART KIOSK: Prevent accidental double scan (Clock Out immediately after Clock In)
        $tz = new DateTimeZone($timezone);
        $actual_time_in_dt = new DateTime($active_log['time_in'], $tz);
        $current_time_dt = new DateTime($log_datetime_full, $tz);
        
        $seconds_since_in = $current_time_dt->getTimestamp() - $actual_time_in_dt->getTimestamp();
        
        // If less than 60 seconds since clock in, block the clock out
        if ($seconds_since_in < 60) {
            $pdo->rollBack();
            log_action($pdo, $employee_id, 'CLOCK_OUT_BLOCKED', "Blocked accidental double scan (Clock Out < 60s after Clock In).");
            echo json_encode(['success' => false, 'message' => "Double Scan Detected! You just clocked in. Please wait at least 1 minute before clocking out."]);
            exit;
        }

        // Calculate remarks (Half Day)
        $remarks = null;
        $time_out_dt = new DateTime($log_datetime_full, $tz);
        
        // Handle overnight shifts
        if ($time_out_dt < $actual_time_in_dt) {
            $time_out_dt->modify('+1 day');
        }
        
        $interval = $actual_time_in_dt->diff($time_out_dt);
        $hours_worked = $interval->h + ($interval->days * 24) + ($interval->i / 60);

        // Logic: If worked less than 4 hours, mark as Half Day
        if ($hours_worked < 4.0) {
            $remarks = 'Half Day';
        }

        $sql_update = "UPDATE attendance_logs SET time_out = ?, remarks = IF(remarks IS NULL, ?, CONCAT(remarks, ', ', ?)) WHERE log_id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$log_datetime_full, $remarks, $remarks, $active_log['log_id']]);

        // Duration logic
        $invalid_date_time = ['0000-00-00 00:00:00', '0000-00-00', null, ''];

        if (in_array($active_log['time_in'], $invalid_date_time, true)) {
            $pdo->rollBack();
            log_action($pdo, $employee_id, 'CLOCK_OUT_FAILED', "Invalid time_in data found for log_id: {$active_log['log_id']}.");
            echo json_encode(['success' => false, 'message' => 'Clock-Out failed: Cannot find valid Clock-In time data.']);
            exit;
        }

        $actual_time_in_dt = new DateTime($active_log['time_in'], $tz);
        $time_out_dt = new DateTime($log_datetime_full, $tz);
        $effective_time_in_dt = $actual_time_in_dt;

        if (!empty($active_log['scheduled_start_time'])) {
            $scheduled_start_dt = new DateTime($active_log['scheduled_start_time'], $tz);
            if ($actual_time_in_dt < $scheduled_start_dt) {
                $effective_time_in_dt = $scheduled_start_dt;
            }
        }

        if ($time_out_dt < $effective_time_in_dt) {
            $time_out_dt->modify('+1 day');
        }

        $interval = $effective_time_in_dt->diff($time_out_dt);
        $hours = $interval->h + ($interval->days * 24);
        $duration = "{$hours} hours, {$interval->i} minutes";
        $message = "Clock-Out successful for {$employee_name}! Shift duration: {$duration}.";

        log_action($pdo, $employee_id, 'CLOCK_OUT_SUCCESS', "Clocked out successfully. Log ID: {$active_log['log_id']}. Duration: {$duration}.");

    } else {
        // --- CLOCK IN ---

        // Check for existing logs today
        $sql_today_logs = "SELECT log_id, time_out FROM attendance_logs 
                            WHERE employee_id = ? AND log_date = ? 
                            ORDER BY time_in ASC";
        $stmt_today_logs = $pdo->prepare($sql_today_logs);
        $stmt_today_logs->execute([$employee_id, $log_date]);
        $existing_logs = $stmt_today_logs->fetchAll(PDO::FETCH_ASSOC);

        if (empty($existing_logs)) {
            // First clock-in: Check Schedule
            $shift = getActiveShift($pdo, $employee_id, $log_date, $day_of_week);

            // Flexible Schedule Logic: Bypass strict schedule requirement
            if (!$shift && !$is_flexible) {
                // IF WE ARE HERE, IT MIGHT BE DUE TO THE DEDICATED OFF DAY LOGIC
                $pdo->rollBack();
                $message = "Clock-In failed: {$employee_name} is not scheduled to work today (Off Day).";
                log_action($pdo, $employee_id, 'CLOCK_IN_FAILED', "Attempted clock-in on non-scheduled day/off-day.");
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }

            // ... Schedule Threshold Logic ...
            $tz = new DateTimeZone($timezone);
            $scheduled_time_to_insert = null;
            $validation_message = null;
            $remarks = null;
            $log_description = "Clocked in at {$log_datetime_full}.";

            if ($is_flexible) {
                // For flexible employees, set scheduled start to actual time (no late/early)
                $scheduled_time_to_insert = $log_datetime_full;
                $remarks = "Flexible Schedule";
                $log_description .= " (Flexible)";
            } else {
                // Standard Logic
                $expected_start_dt = new DateTime("{$log_date} {$shift['shift_start']}", $tz);
                $scheduled_time_to_insert = $expected_start_dt->format('Y-m-d H:i:s');
                $log_description .= " Scheduled start: {$scheduled_time_to_insert}.";

                $grace_start_dt = clone $expected_start_dt;
                $grace_start_dt->modify('-5 minutes');
                $grace_end_dt = clone $expected_start_dt;
                $grace_end_dt->modify('+5 minutes'); // Using global grace period setting would be better here, but stick to existing logic for now
                
                // Fetch Global Grace Period if available (Optional improvement)
                // For now, use the hardcoded +/- 5 mins or existing logic
                
                $current_time_dt = new DateTime($log_datetime_full, $tz);

                if ($current_time_dt < $grace_start_dt) {
                    $validation_message = "Clock-In successful! WARNING: You clocked in early.";
                    $log_description .= " (Early)";
                    $remarks = "Early";
                } elseif ($current_time_dt > $grace_end_dt) {
                    $validation_message = "Clock-In successful! WARNING: You clocked in late.";
                    $log_description .= " (Late)";
                    $remarks = "Late";
                }
            }

            $sql_insert = "INSERT INTO attendance_logs (employee_id, log_date, time_in, scheduled_start_time, remarks) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$employee_id, $log_date, $log_datetime_full, $scheduled_time_to_insert, $remarks]);

            $message = $validation_message ?? "Clock-In successful for {$employee_name} at " . $dt->format('h:i:s A') . ".";
            log_action($pdo, $employee_id, 'CLOCK_IN_SUCCESS', $log_description);

        } else {
            // Return from break logic (unchanged)
            foreach($existing_logs as $log) {
                if (empty($log['time_out'])) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => "Error: Found an unclosed segment."]);
                    exit;
                }
            }
            $sql_insert = "INSERT INTO attendance_logs (employee_id, log_date, time_in, scheduled_start_time) VALUES (?, ?, ?, NULL)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$employee_id, $log_date, $log_datetime_full]);
            $message = "Welcome back, {$employee_name}! Clock-In successful.";
            log_action($pdo, $employee_id, 'CLOCK_IN_SUCCESS', "Clocked in from break.");
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Kiosk DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database Error.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Kiosk Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System Error.']);
}
?>