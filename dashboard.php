<?php
// FILENAME: employee/dashboard.php

// Set the page title before including the header
$pageTitle = 'My Dashboard';

// Include the header. It handles session, auth, DB connection, and sidebar.
// It will also redirect to login.html if the user is not logged in.
include 'template/header.php';
?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-xl p-6 mb-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
    </h1>
    <p class="text-gray-600">This is your personal dashboard. Here is a quick overview of your status.</p>
</div>

<!-- NEW: Dynamic Analytics Cards -->
<div id="analytics-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Loading State -->
    <div class="lg:col-span-4 text-center p-8 bg-white rounded-xl shadow-xl" id="loading-state">
        <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
        <p class="mt-3 text-gray-600">Loading your dashboard...</p>
    </div>
    <!-- Cards will be injected here by JavaScript -->
</div>
<!-- END NEW -->

<!-- NEW: Announcements Panel -->
<div class="my-8">
    <?php include 'template/_announcements_panel.php'; ?>
</div>
<!-- END NEW -->

<!-- Quick Access Cards (Unchanged) -->
<h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Access</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <!-- My Profile Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-blue-100 p-4 rounded-full mb-4">
            <i class="fas fa-user h-10 w-10 text-blue-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My Profile</h2>
        <p class="text-gray-600 mb-4">View your personal and employment details.</p>
        <a href="my_profile.php" class="mt-auto bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            View Profile
        </a>
    </div>

    <!-- My Time Logs Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-yellow-100 p-4 rounded-full mb-4">
            <i class="fas fa-clock h-10 w-10 text-yellow-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My Time Logs</h2>
        <p class="text-gray-600 mb-4">Review your past time in and time out records.</p>
        <a href="my_time_logs.php" class="mt-auto bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            View Logs
        </a>
    </div>

    <!-- My QR Code Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-gray-100 p-4 rounded-full mb-4">
            <i class="fas fa-qrcode h-10 w-10 text-gray-600 leading-10 text-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My QR Code & PIN</h2>
        <p class="text-gray-600 mb-4">Display your unique code or PIN for the attendance kiosk.</p>
        <a href="my_qr_code.php" class="mt-auto bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            Show Code & PIN
        </a>
    </div>

</div>

<!-- NEW: JavaScript for Analytics -->
<script>
    document.addEventListener('DOMContentLoaded', fetchAnalytics);

    const analyticsContainer = document.getElementById('analytics-container');
    const loadingState = document.getElementById('loading-state');

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

        // 1. Last Clock-In
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Last Clock-In',
            data.last_clock_in,
            'Most recent attendance log',
            'fa-sign-in-alt',
            'border-green-400'
        ));

        // 2. Pending Leave Requests
        const leaveColor = data.pending_leave_count > 0 ? 'border-red-400' : 'border-blue-400';
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Pending Leave Requests',
            data.pending_leave_count,
            'Awaiting manager approval',
            'fa-plane-departure',
            leaveColor
        ));

        // 3. Vacation Days
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Vacation Days Left',
            `${data.vacation_available} Days`,
            'Available for request',
            'fa-umbrella-beach',
            'border-blue-400'
        ));

        // 4. Sick Days
        analyticsContainer.insertAdjacentHTML('beforeend', createCard(
            'Sick Days Left',
            `${data.sick_available} Days`,
            'Available for use',
            'fa-briefcase-medical',
            'border-yellow-400'
        ));
    }

    async function fetchAnalytics() {
        loadingState.classList.remove('hidden');

        try {
            const response = await fetch('api/employee_analytics.php');
            const result = await response.json();

            loadingState.classList.add('hidden'); // Hide loading state regardless of outcome

            if (result.success) {
                renderAnalytics(result.data);
            } else {
                analyticsContainer.innerHTML = `
                    <div class="lg:col-span-4 p-4 bg-red-100 text-red-700 rounded-xl shadow-lg text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Could not load your dashboard analytics: ${result.message}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching employee analytics:', error);
            loadingState.classList.add('hidden');
            analyticsContainer.innerHTML = `
                <div class="lg:col-span-4 p-4 bg-red-100 text-red-700 rounded-xl shadow-lg text-center">
                    <i class="fas fa-times-circle mr-2"></i> Network error. Could not load your dashboard.
                </div>
            `;
        }
    }
</script>
<!-- END NEW -->

<?php
// Include the footer
include 'template/footer.php';
?>
