<?php
// FILENAME: employee/config/utils.php

/**
 * Global utility functions and security tools for the Employee System.
 */

// --- SECURITY CONSTANT ---
// NOTE: In a real system, this should be a long, randomly generated string
// stored outside the web root (e.g., environment variable or config file).
const HMAC_SECRET = 'D1B9C7A5E3F2D4A1B0C9D8E7F6A5B4C3D2E1F0A9B8C7D6E5F4A3B2C1D0E9F8A7';


// --- LOGGING CONSTANTS (UPDATED) ---
const LOG_ACTION_LOGIN_SUCCESS = 'LOGIN_SUCCESS';
const LOG_ACTION_LOGIN_FAILED = 'LOGIN_FAILED';
const LOG_ACTION_EMPLOYEE_ADDED = 'EMPLOYEE_ADDED';
const LOG_ACTION_EMPLOYEE_DELETED = 'EMPLOYEE_DELETED';
const LOG_ACTION_EMPLOYEE_PROFILE_UPDATED = 'EMPLOYEE_PROFILE_UPDATED';
const LOG_ACTION_EMPLOYEE_PROFILE_UPDATE_FAILED = 'EMPLOYEE_PROFILE_UPDATE_FAILED';
const LOG_ACTION_PAYROLL_GENERATED = 'PAYROLL_GENERATED';
const LOG_ACTION_MANAGER_ANALYTICS_VIEWED = 'MANAGER_ANALYTICS_VIEWED';
const LOG_ACTION_PROFILE_PICTURE_UPLOADED = 'PROFILE_PICTURE_UPLOADED';
const LOG_ACTION_PROFILE_PICTURE_UPLOAD_FAILED = 'PROFILE_PICTURE_UPLOAD_FAILED';
const LOG_ACTION_PASSWORD_RESET = 'PASSWORD_RESET';
const LOG_ACTION_ATTENDANCE_UPDATED = 'ATTENDANCE_UPDATED';
const LOG_ACTION_ATTENDANCE_ADDED = 'ATTENDANCE_ADDED';
// NEW LOGGING ACTIONS
const LOG_ACTION_DEPT_ADDED = 'DEPT_ADDED';
const LOG_ACTION_DEPT_UPDATED = 'DEPT_UPDATED';
const LOG_ACTION_DEPT_DELETED = 'DEPT_DELETED';
const LOG_ACTION_DEDUCTION_ADDED = 'DEDUCTION_ADDED';
const LOG_ACTION_DEDUCTION_UPDATED = 'DEDUCTION_UPDATED';
const LOG_ACTION_DEDUCTION_DELETED = 'DEDUCTION_DELETED';
const LOG_ACTION_SCHEDULE_STANDARD_UPDATED = 'SCHEDULE_STANDARD_UPDATED';
const LOG_ACTION_SCHEDULE_EXCEPTION_ADDED = 'SCHEDULE_EXCEPTION_ADDED';
const LOG_ACTION_SCHEDULE_EXCEPTION_DELETED = 'SCHEDULE_EXCEPTION_DELETED';
const LOG_ACTION_PAY_RATE_UPDATED = 'PAY_RATE_UPDATED';
const LOG_ACTION_PAY_RATE_DELETED = 'PAY_RATE_DELETED';
const LOG_ACTION_GLOBAL_SETTINGS_UPDATED = 'GLOBAL_SETTINGS_UPDATED';
const LOG_ACTION_LEAVE_REVIEWED = 'LEAVE_REVIEWED';
// CRITICAL FIX: Missing constant for log deletion
const LOG_ACTION_ATTENDANCE_DELETED = 'ATTENDANCE_DELETED';
const LOG_ACTION_PIN_GENERATED = 'PIN_GENERATED';
const LOG_ACTION_PIN_USED = 'PIN_USED';


/**
 * Generates a Time-based PIN for an employee.
 * Refreshes every 15 seconds.
 * 
 * @param string $secret The employee's unique secret key.
 * @return string The 6-digit PIN.
 */
function generateEmployeePIN($secret) {
    if (empty($secret)) return '000000';
    
    // Time window: 15 seconds
    $time_window = floor(time() / 15);
    
    // Create a hash based on the secret and the current time window
    $hash = hash_hmac('sha256', (string)$time_window, $secret);
    
    // Convert part of the hash to a number and mod 1000000 to get 6 digits
    // We use the last 5 chars of hex (20 bits) which is enough for randomness
    $offset = hexdec(substr($hash, -5)); 
    $pin = str_pad($offset % 1000000, 6, '0', STR_PAD_LEFT);
    
    return $pin;
}

/**
 * Validates a submitted PIN.
 * Checks the current time window AND the previous one (to allow for edge cases/clock drift).
 * 
 * @param string $submitted_pin The PIN entered by the user.
 * @param string $secret The employee's unique secret key.
 * @return bool True if valid, False otherwise.
 */
function validateEmployeePIN($submitted_pin, $secret) {
    if (empty($secret) || empty($submitted_pin)) return false;
    
    $current_time = time();
    
    // Check Current Window (15s)
    $window_now = floor($current_time / 15);
    $hash_now = hash_hmac('sha256', (string)$window_now, $secret);
    $pin_now = str_pad(hexdec(substr($hash_now, -5)) % 1000000, 6, '0', STR_PAD_LEFT);
    
    if ($submitted_pin === $pin_now) return true;
    
    // Check Previous Window (Grace period of 15s)
    $window_prev = $window_now - 1;
    $hash_prev = hash_hmac('sha256', (string)$window_prev, $secret);
    $pin_prev = str_pad(hexdec(substr($hash_prev, -5)) % 1000000, 6, '0', STR_PAD_LEFT);
    
    if ($submitted_pin === $pin_prev) return true;
    
    return false;
}


/**
 * Logs a sensitive action to the audit_logs table.
 * @param PDO $pdo The PDO database connection object.
 * @param int $employee_id The ID of the employee performing the action (0 for anonymous/system/failed actions).
 * @param string $action The type of action (use LOG_ACTION_* constants).
 * @param string $description A detailed description of the action.
 * @return void
 */
function log_action($pdo, $employee_id, $action, $description) {
    try {
        $sql = "INSERT INTO audit_logs (employee_id, action, description) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        // Ensure employee_id is cast to int; use 0 for system/failed/anonymous actions
        $log_employee_id = (int)$employee_id;

        $stmt->execute([$log_employee_id, $action, $description]);
    } catch (PDOException $e) {
        // Log the error itself, but don't stop application flow
        error_log('AUDIT LOGGING FAILED: ' . $e->getMessage());
    }
}


// --- HELPER FUNCTION: Calculate total hours (for display) ---
/**
 * Calculates the duration between two datetime strings and returns the result in hours (rounded to 2 decimal places).
 * @param string|null $time_in The clock-in time (Y-m-d H:i:s format).
 * @param string|null $time_out The clock-out time (Y-m-d H:i:s format).
 * @return string The total duration string (e.g., '8.50 hrs') or 'N/A'/'Error'.
 */
function calculateDuration($time_in, $time_out) {
    if (!$time_in || !$time_out) return 'N/A';

    try {
        $time_in_dt = new DateTime($time_in);
        $time_out_dt = new DateTime($time_out);

        // Check for edge case where clock-out is before clock-in (e.g., shifts spanning midnight)
        if ($time_out_dt < $time_in_dt) {
            // Assume the clock-out is on the next day
            $time_out_dt->modify('+1 day');
        }

        $interval = $time_in_dt->diff($time_out_dt);

        // Calculate total seconds
        $total_seconds = ($interval->days * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

        // Convert to hours and round to 2 decimal places
        $total_hours = round($total_seconds / 3600, 2);

        return $total_hours . ' hrs';

    } catch (Exception $e) {
        // Log the error but return user-friendly message
        error_log('Duration Calculation Error: ' . $e->getMessage());
        return 'Error';
    }
}

// --- NEW Helper Function: Get standard schedule details for a specific day ---
function getScheduleDetails($employee_id, $date_str, $schedules_map) {
    $details = ['hours' => 8.00, 'start' => null, 'end' => null]; // Default
    $employee_schedule = $schedules_map[$employee_id] ?? null;

    if ($employee_schedule) {
        try {
            $day_of_week = strtolower((new DateTime($date_str))->format('D')); // mon, tue, etc.
            $start_col = $day_of_week . '_start';
            $end_col = $day_of_week . '_end';

            if (!empty($employee_schedule[$start_col]) && !empty($employee_schedule[$end_col])) {
                $start_time_str = $employee_schedule[$start_col];
                $end_time_str = $employee_schedule[$end_col];
                
                // Create full DateTime objects for the specific date
                $sched_start = new DateTime("$date_str $start_time_str");
                $sched_end = new DateTime("$date_str $end_time_str");
                
                // Handle overnight shifts (end time is next day)
                if ($sched_end <= $sched_start) { 
                    $sched_end->modify('+1 day'); 
                }

                $diff = $sched_start->diff($sched_end);
                $total_seconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
                $hours = round($total_seconds / 3600, 2);

                // Ignore shifts shorter than 1 hour (prevents 1-minute placeholder shifts from causing deductions)
                if ($hours >= 1.0) {
                    $details['hours'] = $hours;
                    $details['start'] = $sched_start;
                    $details['end'] = $sched_end;
                } else {
                     $details['hours'] = 0.00; // Explicitly off
                }
            } else {
                $details['hours'] = 0.00; // Explicitly off
            }
        } catch (Exception $e) {
            error_log("Error calculating schedule for EID {$employee_id} on {$date_str}: " . $e->getMessage());
        }
    }
    return $details;
}


// --- NEW HELPER FUNCTION: Get Employee Pay Rate for a specific date ---
/**
 * Fetches the active pay rate and type for an employee as of a specific date.
 * @param PDO $pdo The PDO database connection object.
 * @param int $employee_id The employee's ID.
 * @param string $check_date The date to check against (Y-m-d).
 * @return array|false Returns pay history array (pay_type, pay_rate) or false if not found.
 */
function getEmployeePayRateOnDate($pdo, $employee_id, $check_date) {
    try {
        $sql = "SELECT pay_type, pay_rate 
                FROM employee_pay_history 
                WHERE employee_id = ? 
                AND effective_start_date <= ? 
                ORDER BY effective_start_date DESC 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id, $check_date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching pay rate for EID {$employee_id} on {$check_date}: " . $e->getMessage());
        return false;
    }
}
// --- END NEW HELPER FUNCTION ---


// --- NEW HELPER FUNCTION: Get Active Shift ---
/**
 * Checks for active schedule and gets shift times for a specific employee and date.
 * @param PDO $pdo The PDO database connection object.
 * @param int $employee_id The employee's ID.
 * @param string $log_date The date to check (Y-m-d).
 * @param string $day_of_week The day of the week (e.g., 'Mon', 'Tue').
 * @return array|null Returns array with shift_start and shift_end, or null if no shift.
 */
function getActiveShift($pdo, $employee_id, $log_date, $day_of_week) {
    // 1. Check for specific date schedule exception (Highest priority)
    $sql_ex = "SELECT shift_start, shift_end FROM schedules WHERE employee_id = ? AND work_date = ?";
    $stmt_ex = $pdo->prepare($sql_ex);
    $stmt_ex->execute([$employee_id, $log_date]);
    $exception = $stmt_ex->fetch(PDO::FETCH_ASSOC);

    if ($exception) {
        return $exception; // Exception shift is active
    }

    // 2. Check for Dedicated Day Off with Effectivity Date
    $sql_off = "SELECT id FROM dedicated_off_days 
                WHERE employee_id = ? 
                AND day_of_week = ? 
                AND effective_date <= ? 
                LIMIT 1";
    $stmt_off = $pdo->prepare($sql_off);
    $stmt_off->execute([$employee_id, $day_of_week, $log_date]);

    if ($stmt_off->fetch()) {
        return null; // It is a dedicated off day, so no shift exists.
    }

    // 3. Check for standard schedule (Lowest Priority)
    $day_start_col = strtolower($day_of_week) . '_start';
    $day_end_col = strtolower($day_of_week) . '_end';

    $sql_std = "SELECT {$day_start_col} AS shift_start, {$day_end_col} AS shift_end 
                FROM standard_schedules WHERE employee_id = ?";
    $stmt_std = $pdo->prepare($sql_std);
    $stmt_std->execute([$employee_id]);
    $standard = $stmt_std->fetch(PDO::FETCH_ASSOC);

    // Ensure we only return a schedule if both start and end times are set
    if ($standard && !empty($standard['shift_start']) && !empty($standard['shift_end'])) {
        return $standard;
    }

    return null; // No active schedule found
}
// --- END NEW HELPER FUNCTION ---


// --- SECURITY FUNCTION: Generate Signed Token ---
/**
 * Generates a signed token containing the employee ID and expiration timestamp.
 * Token format: Base64(EmployeeID.Timestamp.HMAC-Signature)
 * @param int $employee_id The ID of the employee.
 * @param int $duration_seconds How long the token should be valid for (e.g., 300 for 5 minutes).
 * @return string The base64-encoded, signed token.
 */
function generateSignedToken($employee_id, $duration_seconds = 300) {
    // Unix timestamp for token expiration
    $expiry = time() + $duration_seconds;

    // Data to sign (Employee ID and Expiry Time)
    $payload = "{$employee_id}.{$expiry}";

    // Generate HMAC signature
    $signature = hash_hmac('sha256', $payload, HMAC_SECRET);

    // Combine payload and signature, then base64 encode
    $token = base64_encode("{$payload}.{$signature}");

    return $token;
}

// --- SECURITY FUNCTION: Validate and Extract Token ---
/**
 * Validates the signed token against HMAC and checks for expiration.
 * @param string $token The base64-encoded token from the QR scan.
 * @return int|false Returns the employee_id on success, false on failure.
 */
function validateSignedToken($token) {
    $decoded = base64_decode($token);
    if ($decoded === false) {
        return false; // Not a valid base64 string
    }

    // Expected format: EmployeeID.Timestamp.Signature
    $parts = explode('.', $decoded);
    if (count(array_filter($parts)) !== 3) {
        return false; // Malformed token structure
    }

    list($employee_id, $expiry, $provided_signature) = $parts;

    // 1. Check expiration
    if (time() > (int)$expiry) {
        return false; // Token expired
    }

    // 2. Recalculate and verify signature
    $payload = "{$employee_id}.{$expiry}";
    $recalculated_signature = hash_hmac('sha256', $payload, HMAC_SECRET);

    // Use hash_equals to prevent timing attacks
    if (!hash_equals($provided_signature, $recalculated_signature)) {
        return false; // Signature mismatch (token tampered with)
    }

    // 3. Return the employee ID if valid
    return (int)$employee_id;
}
?>
