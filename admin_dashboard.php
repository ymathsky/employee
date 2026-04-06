<?php
// FILENAME: employee/admin_dashboard.php

// Set the page title before including the header
$pageTitle = 'Admin Dashboard';

// Include the header. It handles session, auth, DB connection, and sidebar.
include 'template/header.php';

// Get currency symbol for display
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
?>

<!-- REMOVED: Introductory Welcome Block -->

<!-- Analytics Cards -->
<h2 class="text-xl font-semibold text-gray-800 mb-4">Company Overview Analytics</h2>
<div id="analytics-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Loading State -->
    <div class="lg:col-span-4 text-center p-8 bg-white rounded-xl shadow-xl" id="loading-state">
        <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
        <p class="mt-3 text-gray-600">Loading company-wide analytics...</p>
    </div>
    <!-- Cards will be injected here by JavaScript -->
</div>
<!-- NEW: Announcements Panel -->
<div class="my-8">
    <?php include 'template/_announcements_panel.php'; ?>
</div>
<!-- END NEW -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-indigo-100 p-4 rounded-full mb-4">
            <svg class="h-10 w-10 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372m-1.125-1.125a9.38 9.38 0 0 0-2.625-.372M15 19.128v-1.5m1.125 1.125v-1.5m-1.125 1.125H15m3.75-3.375a9.375 9.375 0 0 0-9.375-9.375h-.375m1.125 1.125a9.375 9.375 0 0 1 9.375 9.375v.375m-1.125-1.125a9.375 9.375 0 0 1-9.375-9.375h-.375m1.125 1.125v.375m-1.125 1.125a9.375 9.375 0 0 1-9.375-9.375H3m1.125 1.125v-1.5m-1.125 1.125v-1.5m1.125 1.125H3m15 1.125v-1.5m-1.125 1.125v-1.5m1.125 1.125H15M9 10.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm1.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm1.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm1.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm1.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Employee Management</h2>
        <p class="text-gray-600 mb-4">Add new employees, edit profiles, and manage personal data.</p>
        <a href="employee_management.php" class="mt-auto bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-green-100 p-4 rounded-full mb-4">
            <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9v1a1 1 0 01-1 1H8a1 1 0 01-1-1V9a2 2 0 00-2-2h12a2 2 0 00-2 2zm2 10V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM12 17a4 4 0 100-8 4 4 0 000 8zM12 17a4 4 0 100-8 4 4 0 000 8zM12 13a1 1 0 100-2 1 1 0 000 2z"/>
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Payroll Processing</h2>
        <p class="text-gray-600 mb-4">Run payroll, manage deductions, and generate payslips.</p>
        <a href="payroll.php" class="mt-auto bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-yellow-100 p-4 rounded-full mb-4">
            <svg class="h-10 w-10 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Time & Attendance</h2>
        <p class="text-gray-600 mb-4">View time logs, track attendance, and approve adjustments.</p>
        <a href="time_attendance.php" class="mt-auto bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <!-- NEW CARD: Leave Policy Management -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-red-100 p-4 rounded-full mb-4">
            <i class="fas fa-umbrella-beach h-10 w-10 text-red-600 flex items-center justify-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Leave Policy Mgt.</h2>
        <p class="text-gray-600 mb-4">Set employee leave accrual rates and view global usage.</p>
        <a href="leave_policy_management.php" class="mt-auto bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <!-- NEW CARD: Overtime Management -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-orange-100 p-4 rounded-full mb-4">
            <i class="fas fa-clock h-10 w-10 text-orange-600 flex items-center justify-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Overtime Mgmt.</h2>
        <p class="text-gray-600 mb-4">Approve overtime hours for payroll calculation.</p>
        <a href="overtime_management.php" class="mt-auto bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-red-100 p-4 rounded-full mb-4">
            <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-4.5v.75m0 0v.75m0 0v.75m0 0v.75M9 12v.75m0 0v.75m0 0v.75m0 0v.75m0 0v.75m6-4.5v.75m0 0v.75m0 0v.75m0 0v.75m0 0v.75M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
            </svg>

        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Audit Log Viewer</h2>
        <p class="text-gray-600 mb-4">Review all administrative and security actions in the system.</p>
        <a href="audit_log_viewer.php" class="mt-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

    <!-- NEW CARD: Department Management (Added for completeness) -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-purple-100 p-4 rounded-full mb-4">
            <i class="fas fa-sitemap h-10 w-10 text-purple-600 flex items-center justify-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Department Management</h2>
        <p class="text-gray-600 mb-4">Add, edit, or delete organizational departments.</p>
        <a href="department_management.php" class="mt-auto bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Go to Module
        </a>
    </div>

</div>
<script>
    document.addEventListener('DOMContentLoaded', fetchAnalytics);

    const analyticsContainer = document.getElementById('analytics-container');
    const loadingState = document.getElementById('loading-state');
    const currencySymbol = '<?php echo $currency_symbol; ?>';

    function createCard(title, value, subtitle, iconClass, colorClass) {
        return `
            <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 ${colorClass}">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas ${iconClass} text-3xl opacity-75 ${colorClass.replace('border-l-4 ', '').replace('border-', 'text-').replace('-400', '-600')}"></i>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 truncate">${title}</p>
                        <p class="text-2xl font-bold text-gray-900">${value}</p>
                        <p class="text-xs text-gray-400 mt-1">${subtitle}</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAnalytics(data) {
        analyticsContainer.innerHTML = ''; // Clear loading state

        // 1. Total Employees
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Total Employees',
            data.total_employees,
            `Across ${data.total_departments} Departments`,
            'fa-user-tie',
            'border-indigo-400'
        ));

        // 2. Pending Leave Requests
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Pending Leave Requests',
            data.pending_leave_count,
            'Awaiting approval company-wide',
            'fa-plane-departure',
            data.pending_leave_count > 0 ? 'border-red-400' : 'border-green-400'
        ));

        // 3. Avg. Sick Leave Used
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Avg. Sick Days Used',
            `${data.avg_sick_leave_per_employee} days`,
            'Per employee (last 12 months)',
            'fa-user-minus',
            data.avg_sick_leave_per_employee > 1 ? 'border-yellow-400' : 'border-green-400'
        ));

        // 4. Total Monthly Gross Payroll
        const payrollValue = `${currencySymbol}${parseFloat(data.total_monthly_gross_pay).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Monthly Gross Payroll',
            payrollValue,
            `Estimated for ${data.payroll_period}`,
            'fa-sack-dollar',
            'border-green-400'
        ));

        // 5. REMOVED: Pending Mandatory Training (formerly item 5)
        /*
         analyticsContainer.insertAdjacentHTML('beforeend', createCard(
         'Pending Mandatory Training',
         data.pending_training_count,
         'Awaiting completion company-wide',
         'fa-graduation-cap',
         data.pending_training_count > 0 ? 'border-red-400' : 'border-blue-400'
         ));
         */
    }

    async function fetchAnalytics() {
        loadingState.classList.remove('hidden');

        try {
            const response = await fetch('api/admin_analytics.php');
            const result = await response.json();

            loadingState.classList.add('hidden');

            if (result.success) {
                renderAnalytics(result.data);
            } else {
                analyticsContainer.innerHTML = `
                    <div class="lg:col-span-4 p-4 bg-red-100 text-red-700 rounded-xl shadow-lg text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${result.message}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching admin analytics:', error);
            loadingState.classList.add('hidden');
            analyticsContainer.innerHTML = `
                <div class="lg:col-span-4 p-4 bg-red-100 text-red-700 rounded-xl shadow-lg text-center">
                    <i class="fas fa-times-circle mr-2"></i> Network error. Could not load analytics.
                </div>
            `;
        }
    }
</script>

<?php
// Include the footer
include 'template/footer.php';
?>
