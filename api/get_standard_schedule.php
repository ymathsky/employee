<?php
// FILENAME: api/get_standard_schedule.php
// Returns the standard schedule for a given employee
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

    $schedule = [];
    if ($row) {
        $days_map = [
            'Mon' => 'mon',
            'Tue' => 'tue',
            'Wed' => 'wed',
            'Thu' => 'thu',
            'Fri' => 'fri',
            'Sat' => 'sat',
            'Sun' => 'sun'
        ];

        foreach ($days_map as $display_day => $db_prefix) {
            $start_col = $db_prefix . '_start';
            $end_col = $db_prefix . '_end';
            
            $start_time = $row[$start_col];
            $end_time = $row[$end_col];
            
            // Determine if it's a rest day (if times are null or empty)
            $is_rest_day = (empty($start_time) || empty($end_time)) ? 1 : 0;

            $schedule[] = [
                'day_of_week' => $display_day,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'is_rest_day' => $is_rest_day
            ];
        }
    }

    // Return BOTH the transformed schedule array (for schedule_management.php)
    // AND the raw data (for standard_schedule.php)
    echo json_encode([
        'success' => true, 
        'schedule' => $schedule,
        'data' => $row ?: []
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
