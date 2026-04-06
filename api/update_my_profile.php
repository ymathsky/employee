<?php
// FILENAME: employee/api/update_my_profile.php
session_start();
header('Content-Type: application/json');

// Must be a logged-in user
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CRITICAL FIX: To prevent unexpected output (like HTML errors or notices) from polluting the JSON,
// we wrap the entire logic, including the require calls, in a try-catch block.
try {
    require_once 'db_connect.php';
    require_once __DIR__ . '/../config/utils.php';
} catch (Throwable $e) {
    // This catches errors like a missing db_connect.php or a connection failure.
    error_log('Fatal API Dependency Error (update_my_profile): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A critical system error occurred (dependency/database).']);
    exit;
}

$employee_id = $_SESSION['user_id'];

// --- CRITICAL CHANGE: Use $_POST instead of json_decode for multipart/form-data ---
// Extract fields that employees are ALLOWED to update.
// Ensure email is lowercase
$new_email = strtolower(trim($_POST['email'] ?? ''));
$new_first_name = trim($_POST['firstName'] ?? '');
$new_last_name = trim($_POST['lastName'] ?? '');
$new_phone = trim($_POST['phone'] ?? '');

// Initialize profile picture URL update path
$profile_picture_url_update = '';
$params = [];
$set_clauses = [];

// Validation
if (empty($new_email) || empty($new_first_name) || empty($new_last_name)) {
    echo json_encode(['success' => false, 'message' => 'First Name, Last Name, and Email are required.']);
    exit;
}
if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

try {
    // 1. Handle File Upload (Requires the 'uploads' directory to exist)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        // NOTE: Adjusted path for better file system consistency.
        $upload_dir = __DIR__ . '/../uploads/profiles/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        // Create directory if it doesn't exist (assuming 0777 or proper permissions)
        if (!is_dir($upload_dir)) {
            // Suppress warnings in case of permissions issues, which will be caught below
            @mkdir($upload_dir, 0777, true);
        }

        // Basic validation
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']);
            exit;
        }
        if ($file['size'] > $max_file_size) {
            echo json_encode(['success' => false, 'message' => 'File size exceeds 2MB limit.']);
            exit;
        }

        // Generate a unique filename (e.g., employeeID_timestamp.ext)
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_file_name = "{$employee_id}_" . time() . ".{$file_ext}";
        $destination = $upload_dir . $new_file_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Save the *relative* URL for the database (e.g., uploads/profiles/...)
            // The path must be relative to the application's root for the frontend to find it.
            $profile_picture_url_update = 'uploads/profiles/' . $new_file_name;
            $set_clauses[] = 'profile_picture_url = ?';
            $params[] = $profile_picture_url_update;

            // Log the file upload
            log_action($pdo, $employee_id, LOG_ACTION_PROFILE_PICTURE_UPLOADED, "Employee uploaded new picture: {$profile_picture_url_update}");

        } else {
            // File move failed, possibly due to permission issues on the 'uploads' folder
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check the server\'s upload folder permissions.']);
            exit;
        }
    }

    // 2. Prepare SQL for non-file fields
    $set_clauses[] = 'first_name = ?';
    $params[] = $new_first_name;

    $set_clauses[] = 'last_name = ?';
    $params[] = $new_last_name;

    $set_clauses[] = 'email = ?';
    $params[] = $new_email;

    $set_clauses[] = 'phone = ?';
    $params[] = $new_phone;

    // 3. Execute Database Update
    $sql = "UPDATE employees SET " . implode(', ', $set_clauses) . " WHERE employee_id = ?";
    $params[] = $employee_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0 || !empty($profile_picture_url_update)) {
        // Update session username if name changed
        $_SESSION['username'] = $new_first_name . ' ' . $new_last_name;

        // --- NEW: Update session profile picture if it was changed ---
        if (!empty($profile_picture_url_update)) {
            $_SESSION['profile_picture_url'] = $profile_picture_url_update;
        }
        // --- END NEW ---

        log_action($pdo, $employee_id, LOG_ACTION_EMPLOYEE_PROFILE_UPDATED, "Employee updated their own profile details.");

        // --- MODIFIED: Return the new_picture_url for the frontend JS ---
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'new_picture_url' => $profile_picture_url_update ?: null
        ]);
        // --- END MODIFIED ---
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes detected or profile not found.']);
    }

} catch (PDOException $e) {
    $log_description = "Failed to update profile: " . $e->getMessage();
    log_action($pdo, $employee_id, LOG_ACTION_EMPLOYEE_PROFILE_UPDATE_FAILED, $log_description);

    if ($e->errorInfo[1] == 1062) { // Duplicate entry error
        echo json_encode(['success' => false, 'message' => 'Error: That email address is already in use.']);
    } else {
        error_log('Update My Profile Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error. Could not update profile.']);
    }
} catch (Throwable $e) {
    // Catch any other unexpected PHP errors
    error_log('Update My Profile General Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected server error occurred: ' . $e->getMessage()]);
}
?>

