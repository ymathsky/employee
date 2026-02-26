<?php
// FILENAME: employee/dashboard.php

// Set the page title before including the header
$pageTitle = 'My Dashboard';

// Include the header. It handles session, auth, DB connection, and sidebar.
include 'template/header.php';

// --- Page-Specific Role Check ---
// Redirect admins or managers if they land here
if ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin') {
    header('Location: admin_dashboard.php');
    exit;
}
if ($_SESSION['role'] === 'Manager') {
    header('Location: manager_dashboard.php');
    exit;
}

// At this point, user is a regular 'Employee'
?>

<!-- Page Content -->
<div class="bg-white rounded-xl shadow-xl p-6 mb-8">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p class="text-gray-600">This is your personal employee portal. From here, you can:</p>
    <ul class="list-disc list-inside mt-4 space-y-2 text-gray-700">
        <li>View your personal information.</li>
        <li>Check your payslips.</li>
        <li>View your attendance records.</li>
        <li>Request time off.</li>
        <li>See your QR code for time-in/out.</li>
    </ul>
</div>

<!-- Placeholder for employee-specific widgets -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-green-100 p-4 rounded-full mb-4">
            <i class="fas fa-qrcode h-10 w-10 text-green-600 flex items-center justify-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My QR Code</h2>
        <p class="text-gray-600 mb-4">Use this code at the kiosk to clock in and out.</p>
        <a href="my_qr_code.php" class="mt-auto bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            View Code
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center text-center">
        <div class="bg-yellow-100 p-4 rounded-full mb-4">
            <i class="fas fa-file-invoice-dollar h-10 w-10 text-yellow-600 flex items-center justify-center text-2xl"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">My Payslips</h2>
        <p class="text-gray-600 mb-4">View and download your past payslips.</p>
        <a href="my_payslips.php" class="mt-auto bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
            View Payslips
        </a>
    </div>
</div>

<?php
// Include the footer
include 'template/footer.php';
?>
