<?php
// FILENAME: index.php
session_start();

/**
 * Main application entry point.
 * Redirects authenticated users to their appropriate dashboard,
 * otherwise, redirects unauthenticated users to the login page.
 */

// Define the relative paths to key pages
$login_page = 'login.html';
$default_employee_dashboard = 'dashboard.php';
$admin_dashboard = 'admin_dashboard.php';
$manager_dashboard = 'manager_dashboard.php';

if (isset($_SESSION['user_id'])) {
    // User is authenticated. Redirect to the appropriate dashboard.
    $role = $_SESSION['role'] ?? 'Employee';

    if ($role === 'HR Admin' || $role === 'Super Admin') {
        header("Location: {$admin_dashboard}");
    } elseif ($role === 'Manager') {
        header("Location: {$manager_dashboard}");
    } else {
        // Default for 'Employee' role
        header("Location: {$default_employee_dashboard}");
    }
    exit;
} else {
    // User is NOT authenticated. Redirect to the login page.
    header("Location: {$login_page}");
    exit;
}
?>
