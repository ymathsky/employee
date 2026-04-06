<?php
// FILENAME: employee/api/db_connect.php

// --- CORS Headers (supports mobile app and browser requests) ---
$allowed_origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token, Cookie');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// --- END CORS ---

// Configuration for your database connection
$host = 'localhost'; // Your database host (e.g., localhost)
$dbname = 'employee_system'; // The name of your database
$user = 'root'; // Your database username
$password = ''; // Your database password

// Data Source Name (DSN) string
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Options for the PDO connection
$options = [
    // Throw an exception when an error occurs
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // Turn off prepared statement emulation for security
    PDO::ATTR_EMULATE_PREPARES => false,
    // Set default fetch mode to associative array
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Establish the connection
    $pdo = new PDO($dsn, $user, $password, $options);

    // Note: Schema migration logic removed.
    // Ensure all required tables (employees, users, attendance_logs, payroll, ca_transactions, deduction_types, standard_schedules, global_settings, leave_requests, leave_balances, employee_journal)
    // exist and contain the correct columns before running in a production environment.

} catch (PDOException $e) {
    // We will let the script that includes this file handle the exception.
    throw $e;
}

?>
