<?php
// FILENAME: employee/api/ca_transactions.php
session_start();
ob_start(); // Start output buffering
header('Content-Type: application/json');

// Admin-only access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['HR Admin', 'Super Admin'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);

try {
    switch ($action) {
        case 'add':
            $employee_id = $data['employee_id'] ?? null;
            $transaction_date = $data['transaction_date'] ?? null;
            $amount = floatval($data['amount'] ?? 0); // This is the total original amount

            if (empty($employee_id) || empty($transaction_date) || $amount <= 0) {
                log_action($pdo, $admin_id, 'CA_ADD_FAILED', "Missing fields for CA transaction.");
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee, date, and amount are required.']);
                exit;
            }

            // CRITICAL FIX: Insert both original_amount and pending_amount
            $sql = "INSERT INTO ca_transactions (employee_id, transaction_date, original_amount, pending_amount, deducted_in_payroll)
                    VALUES (?, ?, ?, ?, FALSE)";
            $stmt = $pdo->prepare($sql);
            // The requested amount becomes both the original and the initial pending balance
            $stmt->execute([$employee_id, $transaction_date, $amount, $amount]);

            log_action($pdo, $admin_id, 'CA_ADDED', "Added CA/VALE of {$amount} for EID {$employee_id} on {$transaction_date}.");
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Cash Advance recorded successfully!']);
            break;

        case 'get':
            $employee_id = $_GET['employee_id'] ?? null;
            $show_deleted = filter_var($_GET['show_deleted'] ?? false, FILTER_VALIDATE_BOOLEAN); // Optional: Param to view deleted
            $is_admin = ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin');

            $where_clause = $show_deleted ? "WHERE 1=1" : "WHERE t.deleted_at IS NULL"; // Filter by deleted_at

            if ($employee_id) {
                // Get all transactions for a specific employee (respecting deleted filter)
                // FIX: Select the pending_amount for display/status
                $sql = "SELECT t.transaction_id, t.employee_id, t.transaction_date, t.pending_amount AS amount, t.deducted_in_payroll, t.payroll_id, t.created_at, t.deleted_at, e.first_name, e.last_name
                        FROM ca_transactions t
                        JOIN employees e ON t.employee_id = e.employee_id
                        {$where_clause} AND t.employee_id = ?
                        ORDER BY t.transaction_date DESC, t.created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$employee_id]);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($is_admin) {
                // Get all pending transactions for all employees (Admin view)
                // FIX: Filter by pending_amount > 0 and select pending_amount AS amount
                
                $filter_employee_id = $_GET['filter_employee_id'] ?? null;
                $filter_date = $_GET['filter_date'] ?? null;

                $sql = "SELECT
                            t.transaction_id, t.employee_id, t.transaction_date, t.pending_amount AS amount, t.deducted_in_payroll, t.payroll_id, t.created_at, t.deleted_at,
                            e.first_name,
                            e.last_name
                        FROM ca_transactions t
                        JOIN employees e ON t.employee_id = e.employee_id
                        {$where_clause} AND t.pending_amount > 0.00 AND t.deducted_in_payroll = FALSE";
                
                $params = [];

                if (!empty($filter_employee_id)) {
                    $sql .= " AND t.employee_id = ?";
                    $params[] = $filter_employee_id;
                }

                if (!empty($filter_date)) {
                    $sql .= " AND t.transaction_date = ?";
                    $params[] = $filter_date;
                }

                $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee ID is required for non-admin requests.']);
                exit;
            }
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $transactions]);
            break;

        case 'delete': // Now SOFT DELETE
            $transaction_id = $data['transaction_id'] ?? null;

            if (empty($transaction_id)) {
                log_action($pdo, $admin_id, 'CA_DELETE_FAILED', "Missing ID for CA transaction deletion.");
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Transaction ID is required.']);
                exit;
            }

            // Check if fully deducted (still relevant, but uses pending_amount)
            $stmt_check = $pdo->prepare("SELECT pending_amount FROM ca_transactions WHERE transaction_id = ? AND deleted_at IS NULL");
            $stmt_check->execute([$transaction_id]);
            $pending_amount = (float)$stmt_check->fetchColumn();

            if ($pending_amount === false) { // Not found or already deleted
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Transaction not found or already deleted.']);
                exit;
            }
            if ($pending_amount <= 0.005) {
                log_action($pdo, $admin_id, 'CA_DELETE_FAILED', "Attempted to delete fully settled CA transaction ID {$transaction_id}.");
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Cannot delete a transaction that has already been fully settled/deducted in payroll.']);
                exit;
            }

            // Perform SOFT DELETE by setting deleted_at
            $sql = "UPDATE ca_transactions SET deleted_at = NOW() WHERE transaction_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$transaction_id]);

            if ($stmt->rowCount() > 0) {
                log_action($pdo, $admin_id, 'CA_DELETED', "Soft deleted pending CA transaction ID {$transaction_id}.");
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Transaction marked as deleted successfully!']);
            } else {
                ob_end_clean();
                // This case should ideally not be reached due to the check above, but included for safety
                echo json_encode(['success' => false, 'message' => 'Transaction not found or could not be marked as deleted.']);
            }
            break;

        // --- NEW: Restore Action ---
        case 'restore':
            $transaction_id = $data['transaction_id'] ?? null;

            if (empty($transaction_id)) {
                log_action($pdo, $admin_id, 'CA_RESTORE_FAILED', "Missing ID for CA transaction restoration.");
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Transaction ID is required.']);
                exit;
            }

            // Check if it's actually deleted and not fully settled
            $stmt_check_restore = $pdo->prepare("SELECT deducted_in_payroll FROM ca_transactions WHERE transaction_id = ? AND deleted_at IS NOT NULL");
            $stmt_check_restore->execute([$transaction_id]);
            $is_deducted_on_deleted = $stmt_check_restore->fetchColumn();

            if ($is_deducted_on_deleted === false) { // Not found or not deleted
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Transaction not found or is not marked as deleted.']);
                exit;
            }
            // If it was already fully deducted when deleted, we block restore as the logic is complex
            if ($is_deducted_on_deleted) { // This check should now be handled by pending_amount in a clean system
                log_action($pdo, $admin_id, 'CA_RESTORE_FAILED', "Attempted to restore already deducted CA transaction ID {$transaction_id}.");
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Cannot restore a transaction that was marked as deducted. This indicates an inconsistency.']);
                exit;
            }


            // Perform Restore by setting deleted_at to NULL
            $sql_restore = "UPDATE ca_transactions SET deleted_at = NULL WHERE transaction_id = ?";
            $stmt_restore = $pdo->prepare($sql_restore);
            $stmt_restore->execute([$transaction_id]);

            if ($stmt_restore->rowCount() > 0) {
                log_action($pdo, $admin_id, 'CA_RESTORED', "Restored deleted CA transaction ID {$transaction_id}.");
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Transaction restored successfully!']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Could not restore transaction.']);
            }
            break;
        // --- END Restore Action ---


        case 'total_pending':
            // MODIFIED: Use pending_amount and deleted_at IS NULL check
            $employee_id = $data['employee_id'] ?? null;

            if (empty($employee_id)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee ID is required.']);
                exit;
            }

            // FIX: Sum pending_amount
            $sql = "SELECT SUM(pending_amount) FROM ca_transactions WHERE employee_id = ? AND deducted_in_payroll = FALSE AND deleted_at IS NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id]);
            $total = $stmt->fetchColumn() ?? 0.00;
            ob_end_clean();
            echo json_encode(['success' => true, 'total' => (float)$total]);
            break;

        case 'get_history':
            $transaction_id = $_GET['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Transaction ID is required.']);
                exit;
            }

            $sql = "SELECT h.*, p.pay_period_start, p.pay_period_end 
                    FROM ca_deductions_history h
                    LEFT JOIN payroll p ON h.payroll_id = p.payroll_id
                    WHERE h.transaction_id = ?
                    ORDER BY h.deduction_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$transaction_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $history]);
            break;

        default:
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid API action.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('CA Transactions DB Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'CA_API_ERROR', "DB Error in action '{$action}': " . $e->getMessage());
    ob_end_clean(); // Clean buffer on error
    echo json_encode(['success' => false, 'message' => 'Database error.']);
} catch (Exception $e) { // Catch broader exceptions
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('CA Transactions General Error: ' . $e->getMessage());
    log_action($pdo, $admin_id, 'CA_API_ERROR', "General Error in action '{$action}': " . $e->getMessage());
    ob_end_clean(); // Clean buffer on error
    echo json_encode(['success' => false, 'message' => 'An unexpected system error occurred.']);
}
?>
