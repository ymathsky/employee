<?php
// FILENAME: employee/api/mobile_login.php
// Mobile-specific login endpoint that returns session token + user data as JSON.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

try {
    require_once __DIR__ . '/db_connect.php';
    require_once __DIR__ . '/../config/utils.php';

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $email    = isset($data['email'])    ? strtolower(trim($data['email']))  : '';
    $password = isset($data['password']) ? $data['password']                 : '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $sql = "SELECT u.password_hash, u.role, u.employee_id, u.username, 
                   e.profile_picture_url, e.first_name, e.last_name, e.job_title, e.department
            FROM users u
            JOIN employees e ON u.employee_id = e.employee_id
            WHERE LOWER(e.email) = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        // Store in session exactly like web login does
        $_SESSION['user_id']             = $user['employee_id'];
        $_SESSION['role']                = $user['role'];
        $_SESSION['username']            = $user['username'];
        $_SESSION['profile_picture_url'] = $user['profile_picture_url'];

        // Load global settings into session
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
        $settings_raw  = $stmt_settings ? $stmt_settings->fetchAll() : [];
        $settings = [];
        foreach ($settings_raw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $_SESSION['settings'] = $settings;

        log_action($pdo, $user['employee_id'], 'LOGIN_SUCCESS',
            "Mobile: User {$user['username']} ({$user['role']}) logged in.");

        echo json_encode([
            'success'      => true,
            'session_token' => session_id(),
            'user' => [
                'employee_id'         => $user['employee_id'],
                'role'                => $user['role'],
                'username'            => $user['username'],
                'first_name'          => $user['first_name'],
                'last_name'           => $user['last_name'],
                'job_title'           => $user['job_title'],
                'department'          => $user['department'],
                'profile_picture_url' => $user['profile_picture_url'],
            ],
        ]);
    } else {
        log_action($pdo, 0, 'LOGIN_FAILED', "Mobile: Failed login for email: {$email}");
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
