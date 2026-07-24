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
        // Fetch settings scoped to this company
        $session_company_id = (int)($_SESSION['company_id'] ?? 1);
        $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE company_id = ?");
        $stmt_settings->execute([$session_company_id]);
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
$is_leave_manager = ($user_role === 'Leave Manager');

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
    'reports.php', // <-- ADDED REPORTS.PHP to admin pages
    'database_backup.php',
    'database_restore.php'
];

// List of pages restricted to Manager/Admin roles
$manager_pages = [
    'manager_dashboard.php',
    'team_attendance.php',
    'manage_leave.php',
    'team_management.php', // <-- ADDED TEAM_MANAGEMENT.PHP
    'team_attendance_logs.php', // <-- ADDED TEAM_ATTENDANCE_LOGS.PHP
    'admin_attendance_requests.php',
];

// Pages Leave Manager role may access (subset of manager pages)
$leave_manager_pages = [
    'manage_leave.php',
    'leave_policy_management.php',
    'admin_attendance_requests.php',
];

// Admin page check: If trying to access an admin page and NOT an admin, redirect.
// Exception: Leave Managers may access their explicitly listed management pages.
if (in_array($pageName, $admin_pages) && !$is_admin) {
    if (!($is_leave_manager && in_array($pageName, $leave_manager_pages))) {
        header('Location: dashboard.php');
        exit;
    }
}

// Manager page check: if not admin, manager, or leave manager ? redirect.
if (in_array($pageName, $manager_pages) && !$is_admin && !$is_manager) {
    // Leave managers may only access their specific pages
    if (!($is_leave_manager && in_array($pageName, $leave_manager_pages))) {
        header('Location: dashboard.php');
        exit;
    }
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

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HR Portal">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">

    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN for icons -->
    <!-- FIX: Upgraded to version 6.5.2 to bypass cache -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- ENHANCEMENT: Added 'defer' to the Alpine.js script tag to prevent render-blocking -->
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- --- NEW: Added Chart.js CDN --- -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- --- END NEW --- -->

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* ?? Scrollbar ??????????????????????????? */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #818cf8; }

        /* ?? Reusable card ??????????????????????? */
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
            border: 1px solid rgba(0,0,0,.04);
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        /* ?? Stat cards ?????????????????????????? */
        .stat-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(0,0,0,.04);
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .stat-icon {
            width: 3rem; height: 3rem;
            border-radius: .75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* ?? Badges ?????????????????????????????? */
        .badge {
            display: inline-flex; align-items: center; gap: .25rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-red    { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef9c3; color: #a16207; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .badge-purple { background: #ede9fe; color: #6d28d9; }
        .badge-gray   { background: #f1f5f9; color: #475569; }
        .badge-orange { background: #ffedd5; color: #c2410c; }

        /* ?? Tables ?????????????????????????????? */
        .data-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .data-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .data-table thead th { padding: .75rem 1rem; text-align: left; font-weight: 600; color: #475569; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
        .data-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
        .data-table tbody tr:hover { background: #f8fafc; }
        .data-table tbody td { padding: .875rem 1rem; color: #374151; vertical-align: middle; }
        .data-table tbody tr:last-child { border-bottom: none; }

        /* ?? Buttons ????????????????????????????? */
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem 1rem; border-radius: .5rem; font-size: .875rem; font-weight: 600; cursor: pointer; transition: all .2s; border: none; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-primary   { background: #4f46e5; color: #fff; }
        .btn-primary:hover:not(:disabled)   { background: #4338ca; box-shadow: 0 4px 12px rgba(79,70,229,.4); }
        .btn-success   { background: #16a34a; color: #fff; }
        .btn-success:hover:not(:disabled)   { background: #15803d; box-shadow: 0 4px 12px rgba(22,163,74,.4); }
        .btn-danger    { background: #dc2626; color: #fff; }
        .btn-danger:hover:not(:disabled)    { background: #b91c1c; box-shadow: 0 4px 12px rgba(220,38,38,.4); }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .btn-secondary:hover:not(:disabled) { background: #e2e8f0; }
        .btn-outline   { background: transparent; color: #4f46e5; border: 1.5px solid #4f46e5; }
        .btn-outline:hover:not(:disabled)   { background: #ede9fe; }
        .btn-sm { padding: .3rem .7rem; font-size: .8rem; }
        .btn-lg { padding: .75rem 1.5rem; font-size: 1rem; }

        /* ?? Form inputs ????????????????????????? */
        .form-input {
            display: block; width: 100%; padding: .6rem .9rem;
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: .5rem; font-size: .875rem; color: #1e293b;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); outline: none; }
        .form-label { display: block; font-size: .875rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }

        /* ?? Section headings ???????????????????? */
        .section-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; }
        .section-subtitle { font-size: .85rem; color: #64748b; margin-top: .1rem; }

        /* ?? Topbar ?????????????????????????????? */
        .topbar {
            position: sticky; top: 0; z-index: 40;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }

        /* ?? Sidebar ????????????????????????????? */
        .sidebar-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem .875rem; border-radius: .625rem;
            font-size: .875rem; font-weight: 500; color: #94a3b8;
            transition: background .15s, color .15s;
            text-decoration: none;
        }
        .sidebar-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-link.active { background: #4f46e5; color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,.35); }
        .sidebar-link .icon { width: 1.25rem; text-align: center; flex-shrink: 0; }
        .sidebar-section { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #475569; padding: .5rem .875rem .25rem; margin-top: .5rem; }

        /* ?? Print ??????????????????????????????? */
        @media print {
            body { background-color: #fff !important; height: auto !important; overflow: visible !important; }
            body > div, body > div > div, main { height: auto !important; overflow: visible !important; display: block !important; position: static !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .print-hide { display: none !important; }
            .print-container { box-shadow: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .print-no-break { page-break-inside: avoid; }
            #reportChartContainer { max-height: 400px !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100">
<div class="flex h-screen bg-slate-100 overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Topbar -->
        <nav class="topbar print-hide">
            <div class="px-4 sm:px-6 py-3 flex items-center gap-3">

                <!-- Hamburger (mobile) -->
                <button @click="sidebarOpen = !sidebarOpen"
                        :aria-expanded="sidebarOpen.toString()"
                        aria-controls="mobile-sidebar"
                        class="flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none md:hidden flex-shrink-0">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Page title -->
                <h1 class="text-lg font-bold text-slate-800 truncate flex-1">
                    <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?>
                </h1>

                <!-- Right side -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <!-- Avatar + name -->
                    <div class="hidden sm:flex items-center gap-2 mr-1">
                        <?php
                        $nav_pic = !empty($_SESSION['profile_picture_url'])
                            ? '../' . htmlspecialchars($_SESSION['profile_picture_url'])
                            : 'https://placehold.co/32x32/4f46e5/ffffff?text=' . strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
                        ?>
                        <img src="<?php echo $nav_pic; ?>" alt="" class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-100">
                        <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </div>
                    <a href="api/logout.php"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 text-sm font-semibold transition-colors">
                        <i class="fas fa-arrow-right-from-bracket text-xs"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 flex flex-col">
