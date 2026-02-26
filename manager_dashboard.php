<?php
// FILENAME: employee/manager_dashboard.php

// Set the page title before including the header
$pageTitle = 'Manager Dashboard';

// Include the header. It handles session, auth, DB connection, and sidebar.
include 'template/header.php';
?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-xl p-6 mb-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">
        Welcome, Manager <?php echo htmlspecialchars($_SESSION['username']); ?>!
    </h1>
    <p class="text-gray-600">This is your dashboard for managing your team. Below are key performance indicators for your department.</p>
</div>

<!-- Manager Analytics Cards -->
<div id="analytics-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Loading State -->
    <div class="lg:col-span-4 text-center p-8 bg-white rounded-xl shadow-xl" id="loading-state">
        <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
        <p class="mt-3 text-gray-600">Loading department analytics...</p>
    </div>

    <!-- Cards will be injected here by JavaScript -->
</div>
<div class="my-8">
    <?php include 'template/_announcements_panel.php'; ?>
</div>
<!-- Quick Access Cards (Existing) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

    <!-- NEW: Team Management Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-indigo-100 p-4 rounded-full mb-4">
            <i class="fas fa-user-friends h-10 w-10 text-indigo-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Team Management</h2>
        <p class="text-gray-600 mb-4">View profiles and manage pay rates for your team members.</p>
        <a href="team_management.php" class="mt-auto bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Manage Team
        </a>
    </div>
    <!-- END NEW -->

    <!-- Team Attendance Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-purple-100 p-4 rounded-full mb-4">
            <i class="fas fa-users h-10 w-10 text-purple-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Team Attendance</h2>
        <p class="text-gray-600 mb-4">View time in/out logs for your department.</p>
        <a href="team_attendance.php" class="mt-auto bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            View Team Logs
        </a>
    </div>

    <!-- Manage Leave Card (Red highlight to draw attention) -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center border-2 border-red-300">
        <div class="bg-red-100 p-4 rounded-full mb-4">
            <i class="fas fa-plane h-10 w-10 text-red-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Manage Leave</h2>
        <p class="text-gray-600 mb-4">Review and approve or reject time off requests.</p>
        <a href="manage_leave.php" class="mt-auto bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Review Requests
        </a>
    </div>

    <!-- My QR Code Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-gray-100 p-4 rounded-full mb-4">
            <i class="fas fa-qrcode h-10 w-10 text-gray-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My QR Code</h2>
        <p class="text-gray-600 mb-4">Display your unique code for the attendance kiosk.</p>
        <a href="my_qr_code.php" class="mt-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Show Code
        </a>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', fetchAnalytics);

    const analyticsContainer = document.getElementById('analytics-container');
    const loadingState = document.getElementById('loading-state');

    function createCard(title, value, iconClass, colorClass) {
        return `
            <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 ${colorClass}">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas ${iconClass} text-3xl opacity-75 ${colorClass.replace('border-l-4 ', '').replace('border-', 'text-').replace('-400', '-600')}"></i>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500 truncate">${title}</p>
                        <p class="text-2xl font-bold text-gray-900">${value}</p>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAnalytics(data) {
        analyticsContainer.innerHTML = '';

        // 1. Department Employee Count
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            `Employees in ${data.department}`,
            data.employee_count,
            'fa-users',
            'border-indigo-400'
        ));

        // 2. Pending Leave Requests
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Pending Leave Requests',
            data.pending_leave_count,
            'fa-plane',
            data.pending_leave_count > 0 ? 'border-red-400' : 'border-green-400'
        ));

        // 3. Average Clock-In Time
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Avg. Clock In (Last 7 Days)',
            data.avg_time_in,
            'fa-clock',
            'border-yellow-400'
        ));

        // 4. Average Clock-Out Time
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Avg. Clock Out (Last 7 Days)',
            data.avg_time_out,
            'fa-sign-out-alt',
            'border-blue-400'
        ));
    }

    async function fetchAnalytics() {
        loadingState.classList.remove('hidden');

        try {
            const response = await fetch('api/manager_analytics.php');
            const result = await response.json();

            loadingState.classList.add('hidden');

            if (result.success) {
                renderAnalytics(result.data);
            } else {
                analyticsContainer.innerHTML = `
                    <div class="lg:col-span-4 p-4 bg-red-100 text-red-700 rounded-xl shadow-lg text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${result.message} Check your department assignment.
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching manager analytics:', error);
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
