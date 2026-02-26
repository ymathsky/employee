<?php
// FILENAME: employee/api/generate_qr_token.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include the utility file that contains the token logic
require_once __DIR__ . '/../config/utils.php';
require_once 'db_connect.php'; // Need DB to fetch secret

$employee_id = $_SESSION['user_id'];
$token_duration = 60; // Default: QR Token valid for 1 minute (60 seconds)

// Fetch Global Settings for auto_refresh_qr
try {
    $stmt_set = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'auto_refresh_qr'");
    $stmt_set->execute();
    $auto_refresh = $stmt_set->fetchColumn();
    // strict check: if it is explicitly '0', then it is disabled.
    if ($auto_refresh === '0') {
        $token_duration = 31536000; // 1 Year (effectively static)
    }
} catch (Exception $e) {
    // Ignore setting fetch error, fall back to default
}

$mode = $_GET['mode'] ?? 'all'; // 'all', 'qr', 'pin'

$response = ['success' => true];

try {
    // 1. Generate QR Token (if requested)
    if ($mode === 'all' || $mode === 'qr') {
        $token = generateSignedToken($employee_id, $token_duration);
        $response['token'] = $token;
        $response['expires_in'] = $token_duration;
    }

    // 2. Generate PIN (if requested)
    if ($mode === 'all' || $mode === 'pin') {
        // Fetch PIN Secret
        $stmt = $pdo->prepare("SELECT pin_secret FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $secret = $stmt->fetchColumn();
        
        // If no secret exists, generate one and save it (Lazy Initialization)
        if (empty($secret)) {
            try {
                $secret = bin2hex(random_bytes(16)); // Generate a 32-character hex string
                $updateStmt = $pdo->prepare("UPDATE employees SET pin_secret = ? WHERE employee_id = ?");
                $updateStmt->execute([$secret, $employee_id]);
            } catch (Exception $ex) {
                // Fallback if update fails, though unlikely
                error_log("Failed to generate/save PIN secret for ID $employee_id: " . $ex->getMessage());
                $secret = null; 
            }
        }
        
        $pin = '000000';
        if ($secret) {
            $pin = generateEmployeePIN($secret);
        }
        $response['pin'] = $pin;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Token Generation Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to generate token.']);
}
?>
