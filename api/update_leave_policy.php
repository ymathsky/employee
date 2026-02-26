<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_error.log');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Failed: ' . $e->getMessage()]);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

$employee_id = $data['employee_id'];
$vacation = floatval($data['vacation_days'] ?? 0);
$sick = floatval($data['sick_days'] ?? 0);
$personal = floatval($data['personal_days'] ?? 0);
$annual = floatval($data['annual_days'] ?? 0);

try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT employee_id FROM leave_balances WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $sql = "UPDATE leave_balances SET vacation_days_accrued = ?, sick_days_accrued = ?, personal_days_accrued = ?, annual_days_accrued = ? WHERE employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vacation, $sick, $personal, $annual, $employee_id]);
    } else {
        $sql = "INSERT INTO leave_balances (employee_id, vacation_days_accrued, sick_days_accrued, personal_days_accrued, annual_days_accrued) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id, $vacation, $sick, $personal, $annual]);
    }

    echo json_encode(['success' => true, 'message' => 'Leave policy updated successfully.']);

} catch (PDOException $e) {
    error_log("Update Leave Policy Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
