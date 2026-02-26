<?php
// FILENAME: api/copy_standard_schedule.php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // 1. Validate Inputs
    if (empty($_POST['source_employee_id']) || empty($_POST['target_employee_ids'])) {
        throw new Exception('Missing source or target employees.');
    }

    $sourceEmpId = $_POST['source_employee_id'];
    $targetEmpIds = $_POST['target_employee_ids']; // Array
    
    if (!is_array($targetEmpIds)) {
        throw new Exception('Target employees must be an array.');
    }

    // 2. Prepare Schedule Data from POST
    // We are taking the data directly from the form submission which includes the schedule for the source employee.
    // This allows us to copy "what is on the screen" rather than "what is in the DB for the source".
    $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    $scheduleData = [];

    foreach ($days as $day) {
        $scheduleData[$day . '_start'] = !empty($_POST[$day . '_start']) ? $_POST[$day . '_start'] : null;
        $scheduleData[$day . '_end'] = !empty($_POST[$day . '_end']) ? $_POST[$day . '_end'] : null;
    }

    // 3. Prepare SQL Statement
    // We will use ON DUPLICATE KEY UPDATE to either insert or update
    $sql = "INSERT INTO standard_schedules (
                employee_id, 
                mon_start, mon_end, 
                tue_start, tue_end, 
                wed_start, wed_end, 
                thu_start, thu_end, 
                fri_start, fri_end, 
                sat_start, sat_end, 
                sun_start, sun_end
            ) VALUES (
                :employee_id, 
                :mon_start, :mon_end, 
                :tue_start, :tue_end, 
                :wed_start, :wed_end, 
                :thu_start, :thu_end, 
                :fri_start, :fri_end, 
                :sat_start, :sat_end, 
                :sun_start, :sun_end
            ) ON DUPLICATE KEY UPDATE 
                mon_start = VALUES(mon_start), mon_end = VALUES(mon_end),
                tue_start = VALUES(tue_start), tue_end = VALUES(tue_end),
                wed_start = VALUES(wed_start), wed_end = VALUES(wed_end),
                thu_start = VALUES(thu_start), thu_end = VALUES(thu_end),
                fri_start = VALUES(fri_start), fri_end = VALUES(fri_end),
                sat_start = VALUES(sat_start), sat_end = VALUES(sat_end),
                sun_start = VALUES(sun_start), sun_end = VALUES(sun_end)";

    $stmt = $pdo->prepare($sql);

    // 4. Execute for each target
    $successCount = 0;
    $pdo->beginTransaction();

    foreach ($targetEmpIds as $targetId) {
        // Skip if target is same as source (though frontend filters this)
        if ($targetId == $sourceEmpId) continue;

        $params = [':employee_id' => $targetId];
        foreach ($days as $day) {
            $params[":{$day}_start"] = $scheduleData["{$day}_start"];
            $params[":{$day}_end"] = $scheduleData["{$day}_end"];
        }

        if ($stmt->execute($params)) {
            $successCount++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Schedule successfully copied to $successCount employees."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error copying schedule: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
