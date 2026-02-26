<?php
// FILENAME: employee/view_employee_profile.php

// Set the page title before including the header
$pageTitle = 'Employee Profile';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php'; // For getEmployeePayRateOnDate

// Get currency symbol from session
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
$is_admin = ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin');

// Get Employee ID from URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: No employee ID provided.</div>";
    include 'template/footer.php';
    exit;
}

// Fetch employee data and current pay rate
$current_pay_data = [];
try {
    // Fetch employee core data JOINED with user role
    $sql = "SELECT e.*, u.role
             FROM employees e
             LEFT JOIN users u ON e.employee_id = u.employee_id
             WHERE e.employee_id = ?";
    $stmt = $pdo->prepare($sql); $stmt->execute([$employee_id]); $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    // *** FIX START: Check if $employee data was fetched successfully ***
    if (!$employee) {
        echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Employee not found. Check the ID in the URL.</div>";
        include 'template/footer.php';
        exit;
    }
    // *** FIX END ***

    // Fetch current pay rate from history table
    $current_pay_data = getEmployeePayRateOnDate($pdo, $employee_id, date('Y-m-d'));

    // --- FIX FOR LINE 43 (Pay Rate Data Access) START ---
    // Format data for display using null coalescing and conditional access to prevent errors
    $pay_rate_display = $current_pay_data && $current_pay_data['pay_rate'] ? number_format($current_pay_data['pay_rate'], 2) : 'N/A';
    $pay_type_display = $current_pay_data['pay_type'] ?? 'N/A'; // Use fetched pay type
    $joinedDate = date('F j, Y', strtotime($employee['created_at']));
    // --- FIX FOR LINE 43 (Pay Rate Data Access) END ---

    // --- NEW: Determine profile picture source ---
    $profile_pic_src = !empty($employee['profile_picture_url'])
        ? '../' . htmlspecialchars($employee['profile_picture_url'])
        : 'https://placehold.co/160x160/dddddd/555555?text=NO+PIC';
    // --- END NEW ---


} catch (PDOException $e) {
    error_log('View Profile Error: ' . $e->getMessage());
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Database error. Could not retrieve employee data.</div>"; include 'template/footer.php'; exit;
}
?>

<!-- View Profile Card -->
<div class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto">
    <div class="mt-3 text-left">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl leading-6 font-semibold text-gray-900">Employee Profile</h3>
            <div class="flex space-x-3">
                <a href="employee_management.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">&larr; Back to Employee List</a>
                <?php if ($is_admin): ?>
                    <a href="edit_employee_page.php?id=<?php echo $employee['employee_id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit Profile</a>
                    <button onclick="openResetPasswordModal(<?php echo $employee['employee_id']; ?>, '<?php echo htmlspecialchars(addslashes($employee['first_name'] . ' ' . $employee['last_name'])); ?>', true)" class="text-sm font-medium text-yellow-600 hover:text-yellow-800">Reset Pass</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile content grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
            <!-- Left Column / Profile Pic Placeholder -->
            <div class="md:col-span-1 flex flex-col items-center">
                <!-- MODIFIED: Replaced icon placeholder with <img> -->
                <div class="w-40 h-40 bg-gray-200 rounded-full flex items-center justify-center mb-4 overflow-hidden border-4 border-gray-300">
                    <img id="profile-pic-display" src="<?php echo $profile_pic_src; ?>" onerror="this.onerror=null; this.src='https://placehold.co/160x160/dddddd/555555?text=NO+PIC';" class="w-full h-full object-cover">
                </div>
                <!-- END MODIFIED -->
                <h4 class="text-2xl font-bold text-indigo-600 text-center" id="view_fullName"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                <p class="text-md text-gray-600 text-center" id="view_jobTitle"><?php echo htmlspecialchars($employee['job_title']); ?></p>
            </div>

            <!-- Details Section -->
            <div class="md:col-span-2 border-t md:border-t-0 md:border-l border-gray-200 pt-6 md:pt-0 md:pl-8">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Employee ID</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_employeeId"><?php echo htmlspecialchars($employee['employee_id']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">System Role</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_role"><?php echo htmlspecialchars($employee['role']); ?></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_email"><?php echo htmlspecialchars($employee['email']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_department"><?php echo htmlspecialchars($employee['department']); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Pay Type</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900"><?php echo htmlspecialchars($pay_type_display); ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Current Pay Rate</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_salaryRate">
                            <?php
                            if ($pay_rate_display !== 'N/A') {
                                echo htmlspecialchars($currency_symbol) . $pay_rate_display;
                                // MODIFIED: Updated suffix logic
                                if ($pay_type_display === 'Hourly') echo ' / hr';
                                else if ($pay_type_display === 'Daily') echo ' / day';
                                else if ($pay_type_display === 'Fix Rate') echo ' / period';
                            } else { echo '<span class="text-red-500">Rate Not Set</span>'; }
                            ?>
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="mt-1 text-md font-semibold text-gray-900" id="view_createdAt"><?php echo $joinedDate; ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<div id="form-message" class="mt-4 max-w-4xl mx-auto hidden p-3 rounded-lg text-center"></div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="resetPasswordForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-key text-yellow-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="resetModalTitle">Reset Password for Employee</h3>
                            <p class="text-sm text-gray-500" id="resetEmployeeName"></p>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="reset_employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password (min 8 chars)</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="8" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="confirm_new_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                                </div>
                                <div id="reset-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 sm:ml-3 sm:w-auto sm:text-sm">Confirm Reset</button>
                    <button type="button" onclick="closeResetPasswordModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page-Specific JavaScript -->
<script>
    const formMessage = document.getElementById('form-message');
    const resetModal = document.getElementById('resetPasswordModal');
    const resetForm = document.getElementById('resetPasswordForm');
    const resetFormMessage = document.getElementById('reset-form-message');

    // --- Show Global Message ---
    function showMessage(messageBox, message, className) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center max-w-4xl mx-auto ${className}`;
        messageBox.classList.remove('hidden');
        setTimeout(() => { messageBox.classList.add('hidden'); }, 4000); // Hide after 4 secs
    }

    // --- Reset Password Modal Control ---
    function openResetPasswordModal(employeeId, employeeName, isProfilePage = false) {
        resetForm.reset(); resetFormMessage.classList.add('hidden');
        document.getElementById('resetModalTitle').textContent = `Reset Password for ${employeeName}`;
        document.getElementById('resetEmployeeName').textContent = `Employee ID: ${employeeId}`;
        document.getElementById('reset_employee_id').value = employeeId;
        resetModal.classList.remove('hidden'); formMessage.classList.add('hidden');
    }
    function closeResetPasswordModal() { resetModal.classList.add('hidden'); }

    // --- Reset Password Form Submission ---
    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_new_password').value;
        const employeeId = document.getElementById('reset_employee_id').value;
        if (newPassword !== confirmPassword) { showMessage(resetFormMessage, 'Error: Passwords do not match.', 'bg-red-100 text-red-700'); return; }
        if (newPassword.length < 8) { showMessage(resetFormMessage, 'Error: Password must be at least 8 characters long.', 'bg-red-100 text-red-700'); return; }
        resetFormMessage.className = 'mt-4 p-3 rounded-lg text-center bg-blue-100 text-blue-700';
        resetFormMessage.textContent = 'Resetting password...'; resetFormMessage.classList.remove('hidden');
        try {
            const response = await fetch('api/reset_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ employee_id: employeeId, new_password: newPassword }) });
            const result = await response.json();
            if (result.success) {
                showMessage(resetFormMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => { showMessage(formMessage, result.message, 'bg-green-100 text-green-700'); closeResetPasswordModal(); }, 500);
            } else { showMessage(resetFormMessage, result.message, 'bg-red-100 text-red-700'); }
        } catch (error) { console.error('Error:', error); showMessage(resetFormMessage, 'An unexpected network error occurred.', 'bg-red-100 text-red-700'); }
    });
</script>

<?php
include 'template/footer.php';
?>

