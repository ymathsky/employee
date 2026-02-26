<?php
// FILENAME: api/get_rest_days.php
// Returns rest days for a given employee from the standard schedule
header('Content-Type: application/json');
require_once 'db_connect.php';

$employee_id = $_GET['employee_id'] ?? null;
if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
    exit;
}

try {
    // Query the correct table 'standard_schedules' (plural)
    $stmt = $pdo->prepare('SELECT * FROM standard_schedules WHERE employee_id = ?');
    $stmt->execute([$employee_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $rest_days = [];
    if ($row) {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        foreach ($days as $day) {
            $lower_day = strtolower($day);
            $start_col = $lower_day . '_start';
            $end_col = $lower_day . '_end';

            // If start or end time is empty, it's a rest day
            if (empty($row[$start_col]) || empty($row[$end_col])) {
                $rest_days[] = $day;
            }
        }
    }

    echo json_encode(['success' => true, 'rest_days' => $rest_days]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
