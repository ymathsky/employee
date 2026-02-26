<?php
require_once 'api/db_connect.php';

echo "Starting Payroll Data Simulation...\n";

// 1. Update Pay Rates
echo "Updating Pay Rates...\n";
$employees = $pdo->query("SELECT employee_id FROM employees")->fetchAll(PDO::FETCH_COLUMN);

foreach ($employees as $empId) {
    // Assign a random rate between 500 and 1500
    $rate = rand(500, 1500);
    $stmt = $pdo->prepare("UPDATE employees SET pay_rate = ? WHERE employee_id = ? AND (pay_rate IS NULL OR pay_rate = 0)");
    $stmt->execute([$rate, $empId]);
}
echo "Pay rates updated for " . count($employees) . " employees.\n";

// 2. Generate Attendance Logs for Nov 16, 2025 - Nov 30, 2025
$startDate = new DateTime('2025-11-16');
$endDate = new DateTime('2025-11-30');

echo "Generating Attendance Logs from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "...\n";

$count = 0;
while ($startDate <= $endDate) {
    $dateStr = $startDate->format('Y-m-d');
    $dayOfWeek = $startDate->format('N'); // 1 (Mon) to 7 (Sun)

    // Skip Sundays (7) and maybe Saturdays (6) depending on policy, but let's just skip Sunday for now to have some data.
    // Actually, let's skip Sat and Sun for standard M-F 9-5.
    if ($dayOfWeek < 6) {
        foreach ($employees as $empId) {
            // Check if log exists
            $check = $pdo->prepare("SELECT log_id FROM attendance_logs WHERE employee_id = ? AND log_date = ?");
            $check->execute([$empId, $dateStr]);
            if ($check->fetch()) {
                continue; // Skip if exists
            }

            // Randomize Time In (07:45 - 08:15)
            $inHour = 7; // or 8
            $inMinute = rand(45, 75); // 45 to 75 minutes past 7:00
            // Adjust for hour overflow
            if ($inMinute >= 60) {
                $inHour++;
                $inMinute -= 60;
            }
            $timeIn = sprintf("%s %02d:%02d:%02d", $dateStr, $inHour, $inMinute, rand(0, 59));

            // Randomize Time Out (17:00 - 18:00)
            $outHour = 17;
            $outMinute = rand(0, 59);
            $timeOut = sprintf("%s %02d:%02d:%02d", $dateStr, $outHour, $outMinute, rand(0, 59));

            // Insert
            $stmt = $pdo->prepare("INSERT INTO attendance_logs (employee_id, log_date, time_in, time_out, remarks) VALUES (?, ?, ?, ?, 'Present')");
            $stmt->execute([$empId, $dateStr, $timeIn, $timeOut]);
            $count++;
        }
    }
    $startDate->modify('+1 day');
}

echo "Inserted $count attendance logs.\n";
echo "Simulation Complete.\n";
?>