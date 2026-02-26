<?php
// FILENAME: employee/api/adjust_log.php
session_start();
// Start output buffering immediately to suppress stray output
ob_start();
header('Content-Type: application/json');

// Only HR Admin, Super Admin, and Manager can adjust logs.
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$action = $_GET['action'] ?? 'save'; // Default action to 'save' (add/update)

// --- NEW: Global Setting Permission Check ---
// Retrieve settings from session (populated in login.php)
$settings = $_SESSION['settings'] ?? [];
$allow_edit_setting = $settings['allow_manual_attendance_edit'] ?? '1'; // Default to allowed

// Super Admin always has access.
// HR Admin and Manager access depends on the global setting.
$has_adjustment_privileges = false;

if ($user_role === 'Super Admin') {
    $has_adjustment_privileges = true;
} elseif (in_array($user_role, ['HR Admin', 'Manager'])) {
    if ($allow_edit_setting == '1') {
        $has_adjustment_privileges = true;
    }
}

if (!$has_adjustment_privileges) {
    ob_end_clean();
    $msg = ($allow_edit_setting == '0')
        ? 'Manual adjustments are currently disabled by the Administrator.'
        : 'Unauthorized access.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
// --- END NEW ---

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

$data = json_decode(file_get_contents('php://input'), true);

$log_id = $data['log_id'] ?? null; // Null if adding a new log
$employee_id = $data['employee_id'] ?? null;
$log_date = $data['log_date'] ?? null;
$time_in_full = $data['time_in'] ?? null;
$time_out_full = $data['time_out'] ?? null; // Optional: can be null if clocking in manually
$remarks = $data['remarks'] ?? null;

// --- Manager Scope Check Function (Used by both save and delete actions) ---
function checkManagerScope($pdo, $manager_id, $target_employee_id) {
    global $user_role;
    if ($user_role === 'Manager') {
        // Check if the target employee belongs to the manager's department
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id]);
        $manager_department = $stmt_dept->fetchColumn();

        $stmt_target_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_target_dept->execute([$target_employee_id]);
        $target_department = $stmt_target_dept->fetchColumn();

        if (empty($manager_department) || $manager_department !== $target_department) {
            log_action($pdo, $manager_id, 'LOG_ADJUST_SCOPE_DENIED', "Manager EID {$manager_id} attempted adjustment for out-of-scope EID {$target_employee_id}.");
            return false;
        }
    }
    return true;
}
// --- End Manager Scope Check Function ---


try {
    $pdo->beginTransaction();

    if ($action === 'delete') {
        // --- DELETE EXISTING LOG ---
        if (empty($log_id)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Log ID is required for deletion.']);
            exit;
        }

        // 1. Get info and check scope before deleting
        $stmt_info = $pdo->prepare("SELECT employee_id, log_date FROM attendance_logs WHERE log_id = ?");
        $stmt_info->execute([$log_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Log not found.']);
            exit;
        }
        $target_employee_id = $info['employee_id'];

        // 2. Check manager scope
        if (!checkManagerScope($pdo, $user_id, $target_employee_id)) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Forbidden. You can only delete logs for employees in your department.']);
            exit;
        }

        // 3. Perform deletion
        $sql = "DELETE FROM attendance_logs WHERE log_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$log_id]);

        if ($stmt->rowCount() > 0) {
            $message = "Attendance Log ID {$log_id} deleted successfully.";
            log_action($pdo, $user_id, LOG_ACTION_ATTENDANCE_DELETED, "{$user_role} deleted log ID {$log_id} for EID {$target_employee_id} on {$info['log_date']}.");
        } else {
            $message = 'Log not found or already deleted.';
        }

    } else {
        // --- SAVE (UPDATE/INSERT) LOG ---

        // --- Validation ---
        if (empty($employee_id) || empty($log_date) || empty($time_in_full)) {
            log_action($pdo, $user_id, 'LOG_ADJUST_FAILED', "Missing required fields for log adjustment. EID: {$employee_id}.");
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Employee, date, and Time In are required.']);
            exit;
        }

        // --- Manager Scope Check (CRITICAL) ---
        if (!checkManagerScope($pdo, $user_id, $employee_id)) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Forbidden. You can only adjust logs for employees in your department.']);
            exit;
        }
        // --- End Manager Scope Check ---

        // --- AUTOMATIC REMARKS CALCULATION ---
        $system_remarks = [];
        $day_of_week = date('D', strtotime($log_date));
        
        // 1. Check for Late (Clock In)
        $shift = getActiveShift($pdo, $employee_id, $log_date, $day_of_week);
        if ($shift && !empty($shift['shift_start'])) {
            $tz = new DateTimeZone($_SESSION['settings']['timezone'] ?? 'UTC');
            $expected_start_dt = new DateTime("{$log_date} {$shift['shift_start']}", $tz);
            $grace_end_dt = clone $expected_start_dt;
            $grace_end_dt->modify('+5 minutes');
            
            $actual_time_in_dt = new DateTime($time_in_full, $tz);
            
            if ($actual_time_in_dt > $grace_end_dt) {
                $system_remarks[] = 'Late';
            }
        }

        // 2. Check for Half Day (Clock Out)
        if (!empty($time_out_full)) {
            $tz = new DateTimeZone($_SESSION['settings']['timezone'] ?? 'UTC');
            $in_dt = new DateTime($time_in_full, $tz);
            $out_dt = new DateTime($time_out_full, $tz);
            
            if ($out_dt < $in_dt) {
                $out_dt->modify('+1 day');
            }
            
            $interval = $in_dt->diff($out_dt);
            $hours_worked = $interval->h + ($interval->days * 24) + ($interval->i / 60);
            
            if ($hours_worked < 4.0) {
                $system_remarks[] = 'Half Day';
            }
        }

        // Merge system remarks with user remarks
        $final_remarks = $remarks;
        if (!empty($system_remarks)) {
            $existing_parts = $remarks ? array_map('trim', explode(',', $remarks)) : [];
            foreach ($system_remarks as $sys_rem) {
                if (!in_array($sys_rem, $existing_parts)) {
                    $existing_parts[] = $sys_rem;
                }
            }
            $final_remarks = implode(', ', $existing_parts);
        }
        // --- END AUTOMATIC REMARKS ---


        if ($log_id) {
            // --- UPDATE EXISTING LOG ---
            $sql = "UPDATE attendance_logs SET log_date = ?, time_in = ?, time_out = ?, remarks = ? WHERE log_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$log_date, $time_in_full, $time_out_full, $final_remarks, $log_id]);

            $message = "Attendance Log ID {$log_id} updated successfully.";
            log_action($pdo, $user_id, LOG_ACTION_ATTENDANCE_UPDATED, "{$user_role} adjusted existing log ID {$log_id} for EID {$employee_id}.");

        } else {
            // --- INSERT NEW LOG ---

            // Check for existing completed log on the same day for this employee
            if (!empty($time_out_full)) {
                $sql_check = "SELECT log_id FROM attendance_logs 
                              WHERE employee_id = ? AND log_date = ? AND time_out IS NOT NULL";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([$employee_id, $log_date]);

                if ($stmt_check->rowCount() > 0) {
                    $pdo->rollBack();
                    log_action($pdo, $user_id, 'LOG_ADJUST_FAILED', "Attempted to add new log for EID {$employee_id}: Conflict with existing completed shift on {$log_date}.");
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Error: A completed log already exists for this employee on this date.']);
                    exit;
                }
            }

            $sql = "INSERT INTO attendance_logs (employee_id, log_date, time_in, time_out, remarks, scheduled_start_time) 
                    VALUES (?, ?, ?, ?, ?, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id, $log_date, $time_in_full, $time_out_full, $final_remarks]);

            $message = "New Attendance Log created successfully.";
            $new_log_id = $pdo->lastInsertId();
            log_action($pdo, $user_id, LOG_ACTION_ATTENDANCE_ADDED, "{$user_role} manually added new log ID {$new_log_id} for EID {$employee_id} on {$log_date}.");
        }
    }

    $pdo->commit();
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Adjust Log DB Error: ' . $e->getMessage());
    log_action($pdo, $user_id, 'LOG_ADJUST_ERROR', "DB error during adjustment for EID {$employee_id}: " . $e->getMessage());

    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: Could not save/delete attendance log.']);
}
?>