<?php
// FILENAME: employee/template/header.php
session_start();

// NEW: Include configuration file to access APP_ROLES
require_once __DIR__ . '/../config/app_config.php';

// Check if the user is logged in.
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: login.html');
    exit;
}

// Database Connection
try {
    // Use __DIR__ to get the correct path to the api folder
    require_once __DIR__ . '/../api/db_connect.php';
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    // Stop the script and show a user-friendly error
    die("
        <div style='font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f8f8; border: 1px solid #ddd; margin: 20px;'>
            <h1 style='color: #c00;'>Connection Error</h1>
            <p style='color: #333;'>We're having trouble connecting to the database. Please contact support.</p>
        </div>
    ");
}

// --- HARDENED: Load Global Settings into Session if not present ---
if (!isset($_SESSION['settings'])) {
    try {
        // Fetch all settings from the database
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM global_settings");
        $settings_raw = $stmt_settings ? $stmt_settings->fetchAll() : [];
        $settings = [];
        foreach ($settings_raw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        // If settings were fetched successfully (even if empty), use them.
        $_SESSION['settings'] = $settings;
    } catch (PDOException $e) {
        // CRITICAL FIX: If the global_settings table is missing or query fails,
        // we set safe fallbacks instead of crashing or outputting PHP errors.
        error_log('Settings Load Error: ' . $e->getMessage());
        $_SESSION['settings'] = [
            'timezone' => 'UTC',
            'currency_symbol' => '$',
            'company_name' => 'Employee Portal'
        ];
    }
}
// --- END HARDENED ---

// --- Role-based access control ---

$pageName = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'Employee'; // Default to Employee if role is missing

// Define which roles are considered administrative
$admin_roles = [
    'HR Admin',
    'Super Admin'
];

$is_admin = in_array($user_role, $admin_roles);
$is_manager = ($user_role === 'Manager');

// List of pages restricted to Admin roles
$admin_pages = [
    'admin_dashboard.php',
    'employee_management.php',
    'add_employee_page.php',
    'edit_employee_page.php',
    'view_employee_profile.php',
    'time_attendance.php',
    'department_management.php',
    'payroll.php',
    'schedule_management.php',
    'standard_schedule.php',
    'global_settings.php', // Include Super Admin only page in admin list for consistency
    'pay_history_management.php',
    'leave_policy_management.php', // Include new leave policy page
    'reports.php' // <-- ADDED REPORTS.PHP to admin pages
];

// List of pages restricted to Manager/Admin roles
$manager_pages = [
    'manager_dashboard.php',
    'team_attendance.php',
    'manage_leave.php',
    'team_management.php', // <-- ADDED TEAM_MANAGEMENT.PHP
    'team_attendance_logs.php' // <-- ADDED TEAM_ATTENDANCE_LOGS.PHP
];

// Admin page check: If trying to access an admin page and NOT an admin, redirect.
if (in_array($pageName, $admin_pages) && !$is_admin) {
    header('Location: dashboard.php'); // Redirect to their default dashboard
    exit;
}

// Manager page check: If trying to access a manager page and NOT a manager OR admin, redirect.
if (in_array($pageName, $manager_pages) && !$is_admin && !$is_manager) {
    header('Location: dashboard.php'); // Redirect to their default dashboard
    exit;
}


// Get the current page filename to set the active state in the sidebar
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The $pageTitle variable is set in the page *before* including this header -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : htmlspecialchars($_SESSION['settings']['company_name'] ?? 'Employee Portal'); ?></title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN for icons -->
    <!-- FIX: Upgraded to version 6.5.2 to bypass cache -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- ENHANCEMENT: Added 'defer' to the Alpine.js script tag to prevent render-blocking -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- --- NEW: Added Chart.js CDN --- -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- --- END NEW --- -->

    <style>
        /* Custom font for a cleaner look */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Hide content that Alpine.js will control until it loads */
        [x-cloak] { display: none !important; }

        /* --- NEW: Print styles for reports --- */
        @media print {
            body {
                background-color: #fff !important;
                height: auto !important;
                overflow: visible !important;
            }
            /* Reset layout containers to allow full height printing */
            body > div, 
            body > div > div,
            main {
                height: auto !important;
                overflow: visible !important;
                display: block !important;
                position: static !important;
            }
            
            main {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .print-hide {
                display: none !important;
            }
            .print-container {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
            .print-no-break {
                page-break-inside: avoid;
            }
            #reportChartContainer {
                max-height: 400px !important;
                width: 100% !important;
            }
        }
        /* --- END NEW --- */
    </style>
</head>
<body class="bg-gray-100">
<!-- MODIFIED: Added overflow-hidden to the main wrapper to prevent horizontal scroll when sidebar is open on mobile -->
<div class="flex h-screen bg-gray-200 overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar -->
        <nav class="bg-white shadow-md print-hide"> <!-- Added print-hide -->
            <div class="container mx-auto px-6 py-3 flex justify-between items-center">

                <!-- NEW: Hamburger Menu for Mobile -->
                <!-- Only visible on md screens or smaller -->
                <button @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-controls="mobile-sidebar" class="text-gray-500 focus:outline-none md:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <h1 class="text-2xl font-bold text-gray-800 ml-4 md:ml-0"><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></h1>

                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 text-sm hidden sm:inline">
                        Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!
                    </span>
                    <a href="api/logout.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 flex flex-col">
            <!-- Content goes here -->
