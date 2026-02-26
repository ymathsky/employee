<?php
// FILENAME: employee/template/sidebar.php
// This file is intended to be included by other pages that have already started the session.

// NEW: Include configuration file
require_once __DIR__ . '/../config/app_config.php';

$is_admin = (isset($_SESSION['role']) && ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin'));
$is_super_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'); // Specific check for Super Admin
$is_manager = (isset($_SESSION['role']) && $_SESSION['role'] === 'Manager');

$current_uri = $_SERVER['REQUEST_URI'];
?>
<div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-20 bg-black/50 md:hidden print-hide" @click="sidebarOpen = false"></div>

<aside id="mobile-sidebar"
       x-cloak
       x-show="sidebarOpen"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed inset-y-0 left-0 z-30 w-72 max-w-full bg-gray-800 text-white p-6 overflow-y-auto md:hidden print-hide"
       @keydown.escape.window="sidebarOpen = false"
>
    <div class="flex justify-between items-center space-x-3 mb-8">
        <div class="flex items-center space-x-3">
            <?php
            $sidebar_pic_src = !empty($_SESSION['profile_picture_url'])
                ? '../' . htmlspecialchars($_SESSION['profile_picture_url'])
                : 'https://placehold.co/40x40/667eea/ffffff?text=' . strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
            ?>
            <img src="<?php echo $sidebar_pic_src; ?>" alt="Profile" class="h-10 w-10 rounded-full object-cover bg-indigo-400">
            <span class="text-lg font-bold"><?php echo $is_super_admin ? 'Super Admin' : ($is_admin ? 'Admin Panel' : ($is_manager ? 'Manager Panel' : 'Employee Portal')); ?></span>
        </div>

        <button @click="sidebarOpen = false" class="text-gray-300 hover:text-white focus:outline-none">
            <i class="fas fa-times text-2xl"></i>
        </button>
    </div>

    <nav class="flex-grow">
        <ul class="space-y-2">
            <?php if ($is_admin || $is_super_admin): ?>
                <li>
                    <a href="admin_dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'admin_dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php
                $is_employee_page = (
                    strpos($current_uri, 'employee_management.php') !== false ||
                    strpos($current_uri, 'add_employee_page.php') !== false ||
                    strpos($current_uri, 'view_employee_profile.php') !== false ||
                    strpos($current_uri, 'edit_employee_page.php') !== false
                );
                ?>
                <li>
                    <a href="employee_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_employee_page ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-users w-5 text-center"></i>
                        <span>Employee Management</span>
                    </a>
                    <?php if ($is_employee_page): ?>
                        <ul class="ml-4 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="employee_management.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'employee_management.php') !== false) ? 'text-white' : 'text-gray-400 hover:text-white'; ?>">
                                    Employee List
                                </a>
                            </li>
                            <li>
                                <a href="add_employee_page.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'add_employee_page.php') !== false) ? 'text-white' : 'text-gray-400 hover:text-white'; ?>">
                                    Add New Employee
                                </a>
                            </li>
                            <?php if (strpos($current_uri, 'view_employee_profile.php') !== false): ?>
                                <li>
                                    <a href="#" class="block px-4 py-2 rounded-lg text-sm text-white">Viewing Profile</a>
                                </li>
                            <?php endif; ?>
                            <?php if (strpos($current_uri, 'edit_employee_page.php') !== false): ?>
                                <li>
                                    <a href="#" class="block px-4 py-2 rounded-lg text-sm text-white">Editing Profile</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </li>

                <li>
                    <a href="department_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'department_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-building w-5 text-center"></i>
                        <span>Department Mgt.</span>
                    </a>
                </li>

                <?php
                // MODIFIED: Added journal_management.php to the check
                $is_journal_page_admin = (
                    strpos($current_uri, 'log_journal.php') !== false ||
                    strpos($current_uri, 'my_journal.php') !== false ||
                    strpos($current_uri, 'journal_management.php') !== false
                );
                ?>
                <li>
                    <a href="log_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_journal_page_admin ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-trophy w-5 text-center"></i>
                        <span>Performance Mgt.</span>
                    </a>
                    <?php if ($is_journal_page_admin): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="log_journal.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'log_journal.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Log Journal Entry
                                </a>
                            </li>
                            <li>
                                <a href="my_journal.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'my_journal.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    My Journal (Admin View)
                                </a>
                            </li>
                            <li>
                                <a href="journal_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'journal_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Journal Management
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_schedule_page = (
                    strpos($current_uri, 'standard_schedule.php') !== false ||
                    strpos($current_uri, 'schedule_management.php') !== false
                );
                $is_schedule_active = $is_schedule_page;
                ?>
                <li>
                    <a href="standard_schedule.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_schedule_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar-alt w-5 text-center"></i>
                        <span>Scheduling</span>
                    </a>
                    <?php if ($is_schedule_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="standard_schedule.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'standard_schedule.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Standard Schedules
                                </a>
                            </li>
                            <li>
                                <a href="schedule_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'schedule_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Schedule Exceptions
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_payroll_page = (
                    strpos($current_uri, 'payroll.php') !== false ||
                    strpos($current_uri, 'deduction_management.php') !== false ||
                    strpos($current_uri, 'allowance_management.php') !== false ||
                    strpos($current_uri, 'pay_history_management.php') !== false ||
                    strpos($current_uri, 'ca_management.php') !== false
                );
                $is_payroll_active = $is_payroll_page;
                ?>
                <li>
                    <a href="payroll.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_payroll_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-dollar-sign w-5 text-center"></i>
                        <span>Payroll</span>
                    </a>
                    <?php if ($is_payroll_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="payroll.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'payroll.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Generate Payroll
                                </a>
                            </li>
                            <li>
                                <a href="pay_history_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'pay_history_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Pay History Mgt.
                                </a>
                            </li>
                            <li>
                                <a href="ca_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'ca_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    CA/VALE Management
                                </a>
                            </li>
                            <li>
                                <a href="deduction_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'deduction_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Deduction Management
                                </a>
                            </li>
                            <li>
                                <a href="allowance_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'allowance_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Allowance & Bonus Mgt.
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_leave_admin_page = (
                    strpos($current_uri, 'manage_leave.php') !== false ||
                    strpos($current_uri, 'leave_policy_management.php') !== false
                );
                $is_leave_admin_active = $is_leave_admin_page;
                ?>
                <li>
                    <a href="manage_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_leave_admin_active ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>Leave Management</span>
                    </a>
                    <?php if ($is_leave_admin_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="manage_leave.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'manage_leave.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Review Requests
                                </a>
                            </li>
                            <li>
                                <a href="leave_policy_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'leave_policy_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Policy & Accrual
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>All Payslips</span>
                    </a>
                </li>

                <li>
                    <a href="time_attendance.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'time_attendance.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>Attendance Logs Mgt.</span>
                    </a>
                </li>
                <li>
                    <a href="admin_attendance_requests.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'admin_attendance_requests.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-edit w-5 text-center"></i>
                        <span>Adjustment Requests</span>
                    </a>
                </li>

                <li>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'reports.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-chart-line w-5 text-center"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <li>
                    <a href="announcement_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'announcement_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-bullhorn w-5 text-center"></i>
                        <span>Announcement Mgt.</span>
                    </a>
                </li>

                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="kiosk.php" target="_blank" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white">
                        <i class="fas fa-camera w-5 text-center"></i>
                        <span>Open Kiosk</span>
                    </a>
                </li>

                <?php if ($is_super_admin): ?>
                    <hr class="border-gray-600 my-4">
                    <li>
                        <a href="global_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'global_settings.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                            <i class="fas fa-cogs w-5 text-center"></i>
                            <span>Global Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="audit_log_viewer.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'audit_log_viewer.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                            <i class="fas fa-clipboard-list w-5 text-center"></i>
                            <span>Audit Log Viewer</span>
                        </a>
                    </li>
                <?php endif; ?>

            <?php elseif ($is_manager): ?>
                <li>
                    <a href="manager_dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'manager_dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="log_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'log_journal.php') !== false || strpos($current_uri, 'my_journal.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-trophy w-5 text-center"></i>
                        <span>Performance Mgt.</span>
                    </a>
                </li>

                <?php
                // UPDATED: Use a single, more descriptive variable name for team active state
                $is_team_oversight_active = (
                    strpos($current_uri, 'team_management.php') !== false ||
                    strpos($current_uri, 'team_attendance_logs.php') !== false
                );
                ?>
                <li x-data="{ open: <?php echo $is_team_oversight_active ? 'true' : 'false'; ?> }">
                    <button @click="open = !open" class="flex items-center space-x-3 px-4 py-3 rounded-lg w-full text-left transition-colors <?php echo $is_team_oversight_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-users-cog w-5 text-center"></i>
                        <span>Team Oversight</span>
                        <i class="fas fa-chevron-down ml-auto text-sm transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-collapse class="ml-4 mt-2 space-y-1 border-l border-gray-600">
                        <li>
                            <a href="team_management.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'team_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                Team Profiles / Pay Rates
                            </a>
                        </li>
                        <li>
                            <a href="team_attendance_logs.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'team_attendance_logs.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                Team Attendance Logs
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="manage_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'manage_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>Manage Leave</span>
                    </a>
                </li>

                <li>
                    <a href="announcement_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'announcement_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-bullhorn w-5 text-center"></i>
                        <span>Announcement Mgt.</span>
                    </a>
                </li>

                <hr class="border-gray-600 my-4">

                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="my_time_logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_time_logs.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>My Time Logs</span>
                    </a>
                </li>
                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
                <li>
                    <a href="my_ca_vale.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_ca_vale.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-receipt w-5 text-center"></i>
                        <span>My CA/VALE</span>
                    </a>
                </li>
                <li>
                    <a href="my_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>My Leave Requests</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>My Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="my_time_logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_time_logs.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>My Time Logs</span>
                    </a>
                </li>
                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
                <li>
                    <a href="my_ca_vale.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_ca_vale.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-receipt w-5 text-center"></i>
                        <span>My CA/VALE</span>
                    </a>
                </li>
                <li>
                    <a href="my_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_journal.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-book-open w-5 text-center"></i>
                        <span>My Performance Journal</span>
                    </a>
                </li>
                <li>
                    <a href="my_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>My Leave Requests</span>
                    </a>
                </li>
            <?php endif; ?>

            <hr class="border-gray-600 my-4">

            <li>
                <a href="my_qr_code.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_qr_code.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-qrcode w-5 text-center"></i>
                    <span>My QR Code</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="mt-auto pt-6">
        <span class="text-gray-400 text-sm block">Logged in as:</span>
        <span class="text-white font-medium block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        <span class="text-gray-400 text-xs block"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></span>
    </div>
</aside>

<aside class="hidden md:flex md:flex-col md:w-72 md:shrink-0 bg-gray-800 text-white p-6 overflow-y-auto print-hide" aria-hidden="false">
    <div class="flex justify-between items-center space-x-3 mb-8">
        <div class="flex items-center space-x-3">
            <?php
            // We can re-use the $sidebar_pic_src variable defined for mobile
            ?>
            <img src="<?php echo $sidebar_pic_src; ?>" alt="Profile" class="h-10 w-10 rounded-full object-cover bg-indigo-400">
            <span class="text-2xl font-bold"><?php echo $is_super_admin ? 'Super Admin' : ($is_admin ? 'Admin Panel' : ($is_manager ? 'Manager Panel' : 'Employee Portal')); ?></span>
        </div>
    </div>

    <nav class="flex-grow">
        <ul class="space-y-2">
            <?php if ($is_admin || $is_super_admin): ?>
                <li>
                    <a href="admin_dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'admin_dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php
                $is_employee_page = (
                    strpos($current_uri, 'employee_management.php') !== false ||
                    strpos($current_uri, 'add_employee_page.php') !== false ||
                    strpos($current_uri, 'view_employee_profile.php') !== false ||
                    strpos($current_uri, 'edit_employee_page.php') !== false
                );
                ?>
                <li>
                    <a href="employee_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_employee_page ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-users w-5 text-center"></i>
                        <span>Employee Management</span>
                    </a>
                    <?php if ($is_employee_page): ?>
                        <ul class="ml-4 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="employee_management.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'employee_management.php') !== false) ? 'text-white' : 'text-gray-400 hover:text-white'; ?>">
                                    Employee List
                                </a>
                            </li>
                            <li>
                                <a href="add_employee_page.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'add_employee_page.php') !== false) ? 'text-white' : 'text-gray-400 hover:text-white'; ?>">
                                    Add New Employee
                                </a>
                            </li>
                            <?php if (strpos($current_uri, 'view_employee_profile.php') !== false): ?>
                                <li>
                                    <a href="#" class="block px-4 py-2 rounded-lg text-sm text-white">Viewing Profile</a>
                                </li>
                            <?php endif; ?>
                            <?php if (strpos($current_uri, 'edit_employee_page.php') !== false): ?>
                                <li>
                                    <a href="#" class="block px-4 py-2 rounded-lg text-sm text-white">Editing Profile</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </li>

                <li>
                    <a href="department_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'department_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-building w-5 text-center"></i>
                        <span>Department Mgt.</span>
                    </a>
                </li>

                <?php
                // MODIFIED: Added journal_management.php to the check
                $is_journal_page_admin = (
                    strpos($current_uri, 'log_journal.php') !== false ||
                    strpos($current_uri, 'my_journal.php') !== false ||
                    strpos($current_uri, 'journal_management.php') !== false
                );
                ?>
                <li>
                    <a href="log_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_journal_page_admin ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-trophy w-5 text-center"></i>
                        <span>Performance Mgt.</span>
                    </a>
                    <?php if ($is_journal_page_admin): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="log_journal.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'log_journal.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Log Journal Entry
                                </a>
                            </li>
                            <li>
                                <a href="my_journal.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'my_journal.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    My Journal (Admin View)
                                </a>
                            </li>
                            <li>
                                <a href="journal_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'journal_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Journal Management
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_schedule_page = (
                    strpos($current_uri, 'standard_schedule.php') !== false ||
                    strpos($current_uri, 'schedule_management.php') !== false
                );
                $is_schedule_active = $is_schedule_page;
                ?>
                <li>
                    <a href="standard_schedule.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_schedule_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar-alt w-5 text-center"></i>
                        <span>Scheduling</span>
                    </a>
                    <?php if ($is_schedule_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="standard_schedule.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'standard_schedule.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Standard Schedules
                                </a>
                            </li>
                            <li>
                                <a href="schedule_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'schedule_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Schedule Exceptions
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_payroll_page = (
                    strpos($current_uri, 'payroll.php') !== false ||
                    strpos($current_uri, 'deduction_management.php') !== false ||
                    strpos($current_uri, 'allowance_management.php') !== false ||
                    strpos($current_uri, 'pay_history_management.php') !== false ||
                    strpos($current_uri, 'ca_management.php') !== false
                );
                $is_payroll_active = $is_payroll_page;
                ?>
                <li>
                    <a href="payroll.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_payroll_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-dollar-sign w-5 text-center"></i>
                        <span>Payroll</span>
                    </a>
                    <?php if ($is_payroll_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="payroll.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'payroll.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Generate Payroll
                                </a>
                            </li>
                            <li>
                                <a href="pay_history_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'pay_history_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Pay History Mgt.
                                </a>
                            </li>
                            <li>
                                <a href="ca_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'ca_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    CA/VALE Management
                                </a>
                            </li>
                            <li>
                                <a href="deduction_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'deduction_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Deduction Management
                                </a>
                            </li>
                            <li>
                                <a href="allowance_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'allowance_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Allowance & Bonus Mgt.
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <?php
                $is_leave_admin_page = (
                    strpos($current_uri, 'manage_leave.php') !== false ||
                    strpos($current_uri, 'leave_policy_management.php') !== false
                );
                $is_leave_admin_active = $is_leave_admin_page;
                ?>
                <li>
                    <a href="manage_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo $is_leave_admin_active ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>Leave Management</span>
                    </a>
                    <?php if ($is_leave_admin_page): ?>
                        <ul class="ml-8 mt-2 space-y-1 border-l border-gray-600">
                            <li>
                                <a href="manage_leave.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'manage_leave.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Review Requests
                                </a>
                            </li>
                            <li>
                                <a href="leave_policy_management.php" class="block pl-2 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'leave_policy_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                    Policy & Accrual
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </li>

                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>All Payslips</span>
                    </a>
                </li>

                <li>
                    <a href="time_attendance.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'time_attendance.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>Attendance Logs Mgt.</span>
                    </a>
                </li>
                <li>
                    <a href="admin_attendance_requests.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'admin_attendance_requests.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-edit w-5 text-center"></i>
                        <span>Adjustment Requests</span>
                    </a>
                </li>

                <li>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'reports.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-chart-line w-5 text-center"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <li>
                    <a href="announcement_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'announcement_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-bullhorn w-5 text-center"></i>
                        <span>Announcement Mgt.</span>
                    </a>
                </li>

                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li>
                    <a href="kiosk.php" target="_blank" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white">
                        <i class="fas fa-camera w-5 text-center"></i>
                        <span>Open Kiosk</span>
                    </a>
                </li>

                <?php if ($is_super_admin): ?>
                    <hr class="border-gray-600 my-4">
                    <li>
                        <a href="global_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'global_settings.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                            <i class="fas fa-cogs w-5 text-center"></i>
                            <span>Global Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="audit_log_viewer.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'audit_log_viewer.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                            <i class="fas fa-clipboard-list w-5 text-center"></i>
                            <span>Audit Log Viewer</span>
                        </a>
                    </li>
                <?php endif; ?>

            <?php elseif ($is_manager): ?>
                <li>
                    <a href="manager_dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'manager_dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="log_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'log_journal.php') !== false || strpos($current_uri, 'my_journal.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-trophy w-5 text-center"></i>
                        <span>Performance Mgt.</span>
                    </a>
                </li>

                <?php
                // UPDATED: Use a single, more descriptive variable name for team active state
                $is_team_oversight_active = (
                    strpos($current_uri, 'team_management.php') !== false ||
                    strpos($current_uri, 'team_attendance_logs.php') !== false
                );
                ?>
                <li x-data="{ open: <?php echo $is_team_oversight_active ? 'true' : 'false'; ?> }">
                    <button @click="open = !open" class="flex items-center space-x-3 px-4 py-3 rounded-lg w-full text-left transition-colors <?php echo $is_team_oversight_active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-users-cog w-5 text-center"></i>
                        <span>Team Oversight</span>
                        <i class="fas fa-chevron-down ml-auto text-sm transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-collapse class="ml-4 mt-2 space-y-1 border-l border-gray-600">
                        <li>
                            <a href="team_management.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'team_management.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                Team Profiles / Pay Rates
                            </a>
                        </li>
                        <li>
                            <a href="team_attendance_logs.php" class="block px-4 py-2 rounded-lg text-sm <?php echo (strpos($current_uri, 'team_attendance_logs.php') !== false) ? 'text-white font-medium' : 'text-gray-400 hover:text-white'; ?>">
                                Team Attendance Logs
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="manage_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'manage_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>Manage Leave</span>
                    </a>
                </li>

                <li>
                    <a href="announcement_management.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'announcement_management.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-bullhorn w-5 text-center"></i>
                        <span>Announcement Mgt.</span>
                    </a>
                </li>

                <hr class="border-gray-600 my-4">

                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="my_time_logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_time_logs.php') !== false) ? 'bg-indigo-6Example (live example).600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>My Time Logs</span>
                    </a>
                </li>
                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
                <li>
                    <a href="my_ca_vale.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_ca_vale.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-receipt w-5 text-center"></i>
                        <span>My CA/VALE</span>
                    </a>
                </li>
                <li>
                    <a href="my_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>My Leave Requests</span>
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'dashboard.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span>My Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_profile.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_profile.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user w-5 text-center"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="my_time_logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_time_logs.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-clock w-5 text-center"></i>
                        <span>My Time Logs</span>
                    </a>
                </li>
                <li>
                    <a href="my_payslips.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_payslips.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
                <li>
                    <a href="my_ca_vale.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_ca_vale.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-receipt w-5 text-center"></i>
                        <span>My CA/VALE</span>
                    </a>
                </li>
                <li>
                    <a href="my_journal.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_journal.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-book-open w-5 text-center"></i>
                        <span>My Performance Journal</span>
                    </a>
                </li>
                <li>
                    <a href="my_leave.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_leave.php') !== false) ? 'bg-red-600 text-white' : 'text-red-300 hover:bg-red-700 hover:text-white'; ?>">
                        <i class="fas fa-plane w-5 text-center"></i>
                        <span>My Leave Requests</span>
                    </a>
                </li>
            <?php endif; ?>

            <hr class="border-gray-600 my-4">

            <li>
                <a href="my_qr_code.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?php echo (strpos($current_uri, 'my_qr_code.php') !== false) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-qrcode w-5 text-center"></i>
                    <span>My QR Code</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="mt-auto pt-6">
        <span class="text-gray-400 text-sm hidden sm:inline">Logged in as:</span>
        <span class="text-white font-medium block"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        <span class="text-gray-400 text-xs block"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></span>
    </div>
</aside>
