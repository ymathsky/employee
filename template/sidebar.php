<?php
// FILENAME: employee/template/sidebar.php
require_once __DIR__ . '/../config/app_config.php';

$is_admin      = isset($_SESSION['role']) && in_array($_SESSION['role'], ['HR Admin', 'Super Admin']);
$is_super_admin= isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin';
$is_manager    = isset($_SESSION['role']) && $_SESSION['role'] === 'Manager';
$is_leave_manager = isset($_SESSION['role']) && $_SESSION['role'] === 'Leave Manager';

$current_uri = $_SERVER['REQUEST_URI'];

// Helper: render a sidebar link
function sl($href, $icon, $label, $match_strings, $current_uri, $extra_class = '') {
    $active = false;
    foreach ((array)$match_strings as $m) {
        if (strpos($current_uri, $m) !== false) { $active = true; break; }
    }
    $cls = 'sidebar-link' . ($active ? ' active' : '') . ($extra_class ? " $extra_class" : '');
    echo "<a href=\"{$href}\" class=\"{$cls}\"><i class=\"fas {$icon} icon\"></i><span>{$label}</span></a>";
}

// Profile picture
$sidebar_pic_src = !empty($_SESSION['profile_picture_url'])
    ? '../' . htmlspecialchars($_SESSION['profile_picture_url'])
    : 'https://placehold.co/40x40/4f46e5/ffffff?text=' . strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));

$panel_label = $is_super_admin ? 'Super Admin' : ($is_admin ? 'Admin Panel' : ($is_manager ? 'Manager Panel' : ($is_leave_manager ? 'Leave Manager' : 'Employee Portal')));
?>

<!-- Mobile overlay -->
<div x-cloak x-show="sidebarOpen" x-transition.opacity
     class="fixed inset-0 z-20 bg-black/60 md:hidden print-hide"
     @click="sidebarOpen = false"></div>

<?php
// Build the nav content once as a variable to avoid duplication between mobile/desktop
ob_start();
?>
<div class="flex items-center gap-3 px-4 py-5 border-b border-white/10">
    <img src="<?php echo $sidebar_pic_src; ?>" alt=""
         class="w-10 h-10 rounded-xl object-cover ring-2 ring-white/20 flex-shrink-0">
    <div class="min-w-0">
        <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
        <p class="text-xs text-indigo-300 truncate"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></p>
    </div>
</div>

<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">

<?php if ($is_admin || $is_super_admin): ?>

    <?php sl('admin_dashboard.php', 'fa-house', 'Dashboard', 'admin_dashboard.php', $current_uri); ?>

    <p class="sidebar-section">People</p>
    <?php
    $is_emp = strpos($current_uri, 'employee_management.php') !== false
           || strpos($current_uri, 'add_employee_page.php') !== false
           || strpos($current_uri, 'view_employee_profile.php') !== false
           || strpos($current_uri, 'edit_employee_page.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_emp ? 'true' : 'false'; ?> }">
        <button @click="open = !open"
                class="sidebar-link w-full <?php echo $is_emp ? 'active' : ''; ?>">
            <i class="fas fa-users icon"></i><span class="flex-1 text-left">Employees</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('employee_management.php',  'fa-list',       'Employee List',  'employee_management.php',  $current_uri); ?>
            <?php sl('add_employee_page.php',    'fa-user-plus',  'Add Employee',   'add_employee_page.php',    $current_uri); ?>
            <?php if (strpos($current_uri, 'view_employee_profile.php') !== false) sl('#', 'fa-eye', 'View Profile', '', $current_uri); ?>
            <?php if (strpos($current_uri, 'edit_employee_page.php')    !== false) sl('#', 'fa-pencil', 'Edit Profile', '', $current_uri); ?>
        </div>
    </div>
    <?php sl('department_management.php', 'fa-building', 'Departments', 'department_management.php', $current_uri); ?>

    <p class="sidebar-section">Operations</p>
    <?php
    $is_journal_a = strpos($current_uri, 'log_journal.php') !== false
                 || strpos($current_uri, 'my_journal.php') !== false
                 || strpos($current_uri, 'journal_management.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_journal_a ? 'true' : 'false'; ?> }">
        <button @click="open = !open" class="sidebar-link w-full <?php echo $is_journal_a ? 'active' : ''; ?>">
            <i class="fas fa-trophy icon"></i><span class="flex-1 text-left">Performance</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('log_journal.php',       'fa-pen',       'Log Entry',          'log_journal.php',       $current_uri); ?>
            <?php sl('my_journal.php',        'fa-book-open', 'My Journal',         'my_journal.php',        $current_uri); ?>
            <?php sl('journal_management.php','fa-list-check','Journal Management', 'journal_management.php',$current_uri); ?>
        </div>
    </div>

    <?php
    $is_sched = strpos($current_uri, 'standard_schedule.php') !== false
             || strpos($current_uri, 'schedule_management.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_sched ? 'true' : 'false'; ?> }">
        <button @click="open = !open" class="sidebar-link w-full <?php echo $is_sched ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt icon"></i><span class="flex-1 text-left">Scheduling</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('standard_schedule.php', 'fa-calendar-check','Standard Schedules',  'standard_schedule.php',  $current_uri); ?>
            <?php sl('schedule_management.php','fa-calendar-xmark','Schedule Exceptions', 'schedule_management.php', $current_uri); ?>
        </div>
    </div>

    <p class="sidebar-section">Finance</p>
    <?php
    $is_pay = strpos($current_uri, 'payroll.php') !== false
           || strpos($current_uri, 'deduction_management.php') !== false
           || strpos($current_uri, 'allowance_management.php') !== false
           || strpos($current_uri, 'pay_history_management.php') !== false
           || strpos($current_uri, 'ca_management.php') !== false
           || strpos($current_uri, 'overtime_management.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_pay ? 'true' : 'false'; ?> }">
        <button @click="open = !open" class="sidebar-link w-full <?php echo $is_pay ? 'active' : ''; ?>">
            <i class="fas fa-coins icon"></i><span class="flex-1 text-left">Payroll</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('payroll.php',               'fa-calculator',       'Generate Payroll',    'payroll.php',               $current_uri); ?>
            <?php sl('pay_history_management.php','fa-clock-rotate-left','Pay History',         'pay_history_management.php',$current_uri); ?>
            <?php sl('ca_management.php',         'fa-hand-holding-dollar','CA/VALE',           'ca_management.php',         $current_uri); ?>
            <?php sl('deduction_management.php',  'fa-minus-circle',     'Deductions',          'deduction_management.php',  $current_uri); ?>
            <?php sl('overtime_management.php',   'fa-hourglass-half',   'Overtime',            'overtime_management.php',   $current_uri); ?>
            <?php sl('allowance_management.php',  'fa-plus-circle',      'Allowances & Bonus',  'allowance_management.php',  $current_uri); ?>
        </div>
    </div>
    <?php sl('my_payslips.php', 'fa-file-invoice-dollar', 'All Payslips', 'my_payslips.php', $current_uri); ?>

    <?php
    $is_leave_a = strpos($current_uri, 'manage_leave.php') !== false
               || strpos($current_uri, 'leave_policy_management.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_leave_a ? 'true' : 'false'; ?> }">
        <button @click="open = !open" class="sidebar-link w-full <?php echo $is_leave_a ? 'active' : ''; ?>">
            <i class="fas fa-umbrella-beach icon"></i><span class="flex-1 text-left">Leave</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('manage_leave.php',           'fa-list-check',  'Review Requests', 'manage_leave.php',           $current_uri); ?>
            <?php sl('leave_policy_management.php','fa-file-alt',    'Policy & Accrual','leave_policy_management.php',$current_uri); ?>
        </div>
    </div>

    <p class="sidebar-section">Attendance</p>
    <?php sl('time_attendance.php',          'fa-clock',   'Attendance Logs',      'time_attendance.php',          $current_uri); ?>
    <?php sl('admin_attendance_requests.php','fa-pen-to-square','Adj. Requests',   'admin_attendance_requests.php',$current_uri); ?>

    <p class="sidebar-section">Reporting</p>
    <?php sl('reports.php',                 'fa-chart-line', 'Reports',            'reports.php',                  $current_uri); ?>
    <?php sl('announcement_management.php', 'fa-bullhorn',   'Announcements',      'announcement_management.php',  $current_uri); ?>
    <?php sl('holiday_management.php',      'fa-calendar-day','Holidays',          'holiday_management.php',       $current_uri); ?>

    <p class="sidebar-section">My Account</p>
    <?php sl('my_profile.php',  'fa-user',   'My Profile',   'my_profile.php',   $current_uri); ?>
    <?php sl('kiosk.php',       'fa-camera', 'Open Kiosk',   '__never_match__',  $current_uri); ?>

    <?php if ($is_super_admin): ?>
        <p class="sidebar-section" style="color:#f87171">System</p>
        <?php sl('global_settings.php',    'fa-gear',          'Global Settings',  'global_settings.php',    $current_uri, 'text-rose-300'); ?>
        <?php sl('company_management.php', 'fa-building',      'Companies',        'company_management.php', $current_uri, 'text-rose-300'); ?>
        <?php sl('database_backup.php',    'fa-database',      'DB Backup',        'database_backup.php',    $current_uri, 'text-rose-300'); ?>
        <?php sl('database_restore.php',   'fa-rotate-left',   'DB Restore',       'database_restore.php',   $current_uri, 'text-rose-300'); ?>
        <?php sl('audit_log_viewer.php',   'fa-clipboard-list','Audit Log',        'audit_log_viewer.php',   $current_uri, 'text-rose-300'); ?>
    <?php endif; ?>

<?php elseif ($is_manager): ?>

    <?php sl('manager_dashboard.php', 'fa-house', 'Dashboard', 'manager_dashboard.php', $current_uri); ?>

    <p class="sidebar-section">Team</p>
    <?php
    $is_team = strpos($current_uri, 'team_management.php') !== false
            || strpos($current_uri, 'team_attendance_logs.php') !== false;
    ?>
    <div x-data="{ open: <?php echo $is_team ? 'true' : 'false'; ?> }">
        <button @click="open = !open" class="sidebar-link w-full <?php echo $is_team ? 'active' : ''; ?>">
            <i class="fas fa-users-gear icon"></i><span class="flex-1 text-left">Team Oversight</span>
            <i class="fas fa-chevron-right text-xs transition-transform" :class="open ? 'rotate-90' : ''"></i>
        </button>
        <div x-show="open" x-collapse class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
            <?php sl('team_management.php',     'fa-id-badge', 'Profiles / Pay Rates', 'team_management.php',     $current_uri); ?>
            <?php sl('team_attendance_logs.php','fa-clock',    'Attendance Logs',      'team_attendance_logs.php',$current_uri); ?>
        </div>
    </div>
    <?php sl('manage_leave.php',           'fa-umbrella-beach','Manage Leave',     'manage_leave.php',           $current_uri); ?>
    <?php sl('announcement_management.php','fa-bullhorn',       'Announcements',    'announcement_management.php',$current_uri); ?>
    <?php sl('log_journal.php',            'fa-trophy',         'Performance Mgt.', ['log_journal.php','my_journal.php'], $current_uri); ?>

    <p class="sidebar-section">My Account</p>
    <?php sl('my_profile.php',   'fa-user',                'My Profile',        'my_profile.php',   $current_uri); ?>
    <?php sl('my_time_logs.php', 'fa-clock',               'My Time Logs',      'my_time_logs.php', $current_uri); ?>
    <?php sl('my_payslips.php',  'fa-file-invoice-dollar', 'My Payslips',       'my_payslips.php',  $current_uri); ?>
    <?php sl('my_ca_vale.php',   'fa-receipt',             'My CA/VALE',        'my_ca_vale.php',   $current_uri); ?>
    <?php sl('my_leave.php',     'fa-umbrella-beach',      'My Leave Requests', 'my_leave.php',     $current_uri); ?>

<?php elseif ($is_leave_manager): ?>

    <?php sl('dashboard.php', 'fa-house', 'Dashboard', 'dashboard.php', $current_uri); ?>

    <p class="sidebar-section">Leave</p>
    <?php sl('manage_leave.php',            'fa-list-check', 'Review Requests', 'manage_leave.php',            $current_uri); ?>
    <?php sl('leave_policy_management.php', 'fa-file-alt',   'Policy & Accrual', 'leave_policy_management.php', $current_uri); ?>

    <p class="sidebar-section">Attendance</p>
    <?php sl('admin_attendance_requests.php', 'fa-pen-to-square', 'Time Adjustments', 'admin_attendance_requests.php', $current_uri); ?>

    <p class="sidebar-section">My Account</p>
    <?php sl('my_profile.php',   'fa-user',                'My Profile',        'my_profile.php',   $current_uri); ?>
    <?php sl('my_time_logs.php', 'fa-clock',               'My Time Logs',      'my_time_logs.php', $current_uri); ?>
    <?php sl('my_payslips.php',  'fa-file-invoice-dollar', 'My Payslips',       'my_payslips.php',  $current_uri); ?>
    <?php sl('my_ca_vale.php',   'fa-receipt',             'My CA/VALE',        'my_ca_vale.php',   $current_uri); ?>
    <?php sl('my_leave.php',     'fa-umbrella-beach',      'My Leave Requests', 'my_leave.php',     $current_uri); ?>

<?php else: ?>

    <?php sl('dashboard.php',    'fa-house',                'My Dashboard',          'dashboard.php',    $current_uri); ?>

    <p class="sidebar-section">My Work</p>
    <?php sl('my_time_logs.php', 'fa-clock',               'My Time Logs',           'my_time_logs.php', $current_uri); ?>
    <?php sl('my_payslips.php',  'fa-file-invoice-dollar', 'My Payslips',            'my_payslips.php',  $current_uri); ?>
    <?php sl('my_ca_vale.php',   'fa-receipt',             'My CA/VALE',             'my_ca_vale.php',   $current_uri); ?>
    <?php sl('my_leave.php',     'fa-umbrella-beach',      'My Leave Requests',      'my_leave.php',     $current_uri); ?>
    <?php sl('my_journal.php',   'fa-book-open',           'My Performance Journal', 'my_journal.php',   $current_uri); ?>

    <p class="sidebar-section">My Account</p>
    <?php sl('my_profile.php',   'fa-user',    'My Profile',  'my_profile.php',   $current_uri); ?>

<?php endif; ?>

    <p class="sidebar-section">Quick Links</p>
    <?php sl('my_qr_code.php',    'fa-qrcode',   'My QR Code',   'my_qr_code.php',    $current_uri); ?>
    <?php sl('my_virtual_id.php', 'fa-id-card',  'Virtual ID',   'my_virtual_id.php', $current_uri); ?>
    <?php sl('user_manual.php',   'fa-book-open','User Manual',   'user_manual.php',   $current_uri); ?>

</nav>
<?php
$nav_html = ob_get_clean();
?>

<!-- Mobile slide-over sidebar -->
<aside id="mobile-sidebar"
       x-cloak
       x-show="sidebarOpen"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed inset-y-0 left-0 z-30 w-72 flex flex-col md:hidden print-hide"
       style="background: linear-gradient(180deg,#1e1b4b 0%,#312e81 100%);"
       @keydown.escape.window="sidebarOpen = false">
    <!-- Close btn -->
    <button @click="sidebarOpen = false"
            class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-lg text-indigo-300 hover:text-white hover:bg-white/10 focus:outline-none">
        <i class="fas fa-times"></i>
    </button>
    <?php echo $nav_html; ?>
</aside>

<!-- Desktop sidebar (always visible on md+) -->
<aside class="hidden md:flex md:flex-col md:w-64 md:shrink-0 print-hide"
       style="background: linear-gradient(180deg,#1e1b4b 0%,#312e81 100%);">
    <?php echo $nav_html; ?>
</aside>
