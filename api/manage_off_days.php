<?php
// FILENAME: employee/api/manage_off_days.php
session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// FIX: Check $_REQUEST instead of just $_POST to handle GET requests (fetch) and POST requests (add/delete)
$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'add') {
        // Add is a POST request
        $employee_id = $_POST['employee_id'] ?? null;
        $day_of_week = $_POST['day_of_week'] ?? null;
        $effective_date = $_POST['effective_date'] ?? null;
        $reason = $_POST['reason'] ?? 'Rest Day';

        // Validate
        if (empty($employee_id) || empty($day_of_week) || empty($effective_date)) {
            throw new Exception("All fields are required.");
        }

        // Check for duplicates
        $stmt = $pdo->prepare("SELECT id FROM dedicated_off_days WHERE employee_id = ? AND day_of_week = ? AND effective_date = ?");
        $stmt->execute([$employee_id, $day_of_week, $effective_date]);
        if ($stmt->fetch()) {
            throw new Exception("A rule for this day and date already exists.");
        }

        $sql = "INSERT INTO dedicated_off_days (employee_id, day_of_week, effective_date, reason) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id, $day_of_week, $effective_date, $reason]);

        log_action($pdo, $_SESSION['user_id'], 'SCHEDULE_UPDATE', "Added dedicated off day ($day_of_week) for EID $employee_id effective $effective_date");
        echo json_encode(['success' => true, 'message' => 'Dedicated off day added successfully.']);

    } elseif ($action === 'delete') {
        // Delete is a POST request
        $id = $_POST['id'] ?? null;

        if (!$id) {
            throw new Exception("ID is required for deletion.");
        }

        $stmt = $pdo->prepare("DELETE FROM dedicated_off_days WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Rule removed.']);

    } elseif ($action === 'fetch') {
        // Fetch is a GET request
        $employee_id = $_GET['employee_id'] ?? null;

        if (!$employee_id) {
            throw new Exception("Employee ID is required.");
        }

        $stmt = $pdo->prepare("SELECT * FROM dedicated_off_days WHERE employee_id = ? ORDER BY effective_date DESC, day_of_week");
        $stmt->execute([$employee_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);

    } else {
        throw new Exception("Invalid action: " . htmlspecialchars($action));
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>