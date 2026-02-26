<?php
// FILENAME: employee/api/login.php

// We must set the content type *before* any output
header('Content-Type: application/json');
session_start(); // Start the session

try {
    // Include the database connection
    require_once __DIR__ . '/db_connect.php';
    // NEW: Include logging utility
    require_once __DIR__ . '/../config/utils.php';

    // Get the POST data from the JavaScript request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // *** MODIFIED: Use 'email' instead of 'username' ***
    // Ensure email is case-insensitive by converting to lowercase
    $email = isset($data['email']) ? strtolower(trim($data['email'])) : '';
    $password = $data['password'] ?? '';

    // Basic server-side validation
    if (empty($email) || empty($password)) {
        // NEW: Log failure
        log_action($pdo, 0, 'LOGIN_FAILED', "Attempted login with empty credentials from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    // *** MODIFIED: Prepare a SQL statement to find user by EMAIL ***
    // We join the employees and users tables to find the user record
    // associated with the provided email.
    // --- UPDATED: Added e.profile_picture_url ---
    // --- UPDATED: Use LOWER() for case-insensitive comparison ---
    $sql = "SELECT u.password_hash, u.role, u.employee_id, u.username, e.profile_picture_url 
            FROM users u
            JOIN employees e ON u.employee_id = e.employee_id
            WHERE LOWER(e.email) = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]); // $email is already lowercased above
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct, start a session

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['employee_id']; // Store the employee_id
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username']; // We still store the username for display
        // --- NEW: Store profile pic URL in session ---
        $_SESSION['profile_picture_url'] = $user['profile_picture_url'];

        // --- NEW: Load Global Settings into Session on Login ---
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
        $settings_raw = $stmt_settings ? $stmt_settings->fetchAll() : [];
        $settings = [];
        foreach ($settings_raw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $_SESSION['settings'] = $settings;
        // --- END NEW ---

        // NEW: Log successful login
        log_action($pdo, $user['employee_id'], 'LOGIN_SUCCESS', "User {$user['username']} ({$user['role']}) successfully logged in.");


        // Determine the redirection page based on the user's role
        // *** MODIFIED: Removed '../' from paths to be correct ***
        $redirectPage = 'dashboard.php'; // Default for Employee
        if ($user['role'] === 'HR Admin' || $user['role'] === 'Super Admin') {
            $redirectPage = 'admin_dashboard.php';
        } elseif ($user['role'] === 'Manager') {
            $redirectPage = 'manager_dashboard.php';
        }

        echo json_encode(['success' => true, 'redirect' => $redirectPage]);
    } else {
        // NEW: Log failed login attempt
        log_action($pdo, 0, 'LOGIN_FAILED', "Failed login attempt for email: {$email} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
        // *** MODIFIED: Updated error message ***
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }

} catch (PDOException $e) {
    // Log the error (not to the user)
    error_log('Database Error: ' . $e->getMessage());
    // Send a generic error message as JSON
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ]);
}
?>

