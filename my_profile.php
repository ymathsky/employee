<?php
// FILENAME: employee/my_profile.php
$pageTitle = 'My Profile';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php'; // For getEmployeePayRateOnDate

// Get currency symbol from session
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';

// Get Employee ID from the user's session
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Not logged in.</div>"; include 'template/footer.php'; exit;
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

    if (!$employee) {
        // This should theoretically not happen if the user is logged in, but safe check.
        echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Employee not found.</div>"; include 'template/footer.php'; exit;
    }

    // Fetch current pay rate from history table
    $current_pay_data = getEmployeePayRateOnDate($pdo, $employee_id, date('Y-m-d'));

    // --- FIX: Safely access pay rate data only if $current_pay_data is an array ---
    $hide_pay_rate = isset($_SESSION['settings']['hide_pay_rate_from_employee']) && $_SESSION['settings']['hide_pay_rate_from_employee'] == '1';
    
    if ($hide_pay_rate) {
        $pay_rate_display = 'Hidden';
    } else {
        $pay_rate_display = $current_pay_data && $current_pay_data['pay_rate'] ? number_format($current_pay_data['pay_rate'], 2) : 'N/A';
    }
    
    $pay_type_display = $current_pay_data['pay_type'] ?? 'N/A'; // Use fetched pay type
    // --- END FIX ---

    $joinedDate = date('F j, Y', strtotime($employee['created_at']));

    // Determine correct image source path
    $profile_pic_src = !empty($employee['profile_picture_url']) ? '../' . htmlspecialchars($employee['profile_picture_url']) : 'https://placehold.co/160x160/dddddd/555555?text=NO+PIC';

} catch (PDOException $e) {
    error_log('My Profile Error: ' . $e->getMessage());
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Database error. Could not retrieve employee data.</div>"; include 'template/footer.php'; exit;
}
?>

<!-- View Profile Card -->
<div class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto">
    <div class="mt-3 text-left">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl leading-6 font-semibold text-gray-900">My Profile (Self-Service)</h3>
            <button onclick="openResetPasswordModal(<?php echo $employee['employee_id']; ?>, '<?php echo htmlspecialchars(addslashes($employee['first_name'] . ' ' . $employee['last_name'])); ?>')"
                    class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200">
                <i class="fas fa-key mr-2"></i> Reset Password
            </button>
        </div>

        <form id="myProfileForm" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                <!-- Left Column / Profile Pic and Upload -->
                <div class="md:col-span-1 flex flex-col items-center">
                    <div class="w-40 h-40 bg-gray-200 rounded-full flex items-center justify-center mb-4 overflow-hidden border-4 border-gray-300">
                        <img id="profile-pic-display" src="<?php echo $profile_pic_src; ?>" onerror="this.onerror=null; this.src='https://placehold.co/160x160/dddddd/555555?text=NO+PIC';" class="w-full h-full object-cover">
                    </div>
                    <h4 class="text-2xl font-bold text-indigo-600 text-center" id="view_fullName"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                    <p class="text-md text-gray-600 text-center mb-4" id="view_jobTitle"><?php echo htmlspecialchars($employee['job_title']); ?></p>
                    <div class="w-full max-w-[200px] text-center">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1">Update Picture (Max 2MB)</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="md:col-span-2 border-t md:border-t-0 md:border-l border-gray-200 pt-6 md:pt-0 md:pl-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Editable Fields -->
                        <div class="sm:col-span-1">
                            <label for="firstName" class="text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($employee['first_name']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="sm:col-span-1">
                            <label for="lastName" class="text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($employee['last_name']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="email" class="text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($employee['email']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="sm:col-span-1">
                            <label for="phone" class="text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" placeholder="(555) 555-5555" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Read-only fields -->
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Employee ID</dt>
                            <dd class="mt-1 text-md font-semibold text-gray-900"><?php echo htmlspecialchars($employee['employee_id']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Department</dt>
                            <dd class="mt-1 text-md font-semibold text-gray-900"><?php echo htmlspecialchars($employee['department']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Job Title</dt>
                            <dd class="mt-1 text-md font-semibold text-gray-900"><?php echo htmlspecialchars($employee['job_title']); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Current Pay Rate</dt>
                            <dd class="mt-1 text-md font-semibold text-gray-900">
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
                            <dd class="mt-1 text-md font-semibold text-gray-900"><?php echo $joinedDate; ?></dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button and Message -->
            <div class="flex justify-end mt-8 border-t pt-6">
                <button type="submit" id="saveButton"
                        class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    Save Profile Changes
                </button>
            </div>
        </form>
        <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
    </div>
</div>

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
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="resetModalTitle">Reset My Password</h3>
                            <p class="text-sm text-gray-500">You are resetting the password for: <span class="font-semibold text-gray-700" id="resetEmployeeName"></span></p>
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

<script>
    const form = document.getElementById('myProfileForm');
    const formMessage = document.getElementById('form-message');
    const saveButton = document.getElementById('saveButton');
    const profilePicDisplay = document.getElementById('profile-pic-display');
    const fileInput = document.getElementById('profile_picture');
    const resetModal = document.getElementById('resetPasswordModal');
    const resetForm = document.getElementById('resetPasswordForm');
    const resetFormMessage = document.getElementById('reset-form-message');
    const currentEmployeeName = '<?php echo htmlspecialchars(addslashes($employee['first_name'] . ' ' . $employee['last_name'])); ?>';

    // Utility to show messages
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
        if(autoHide) setTimeout(() => { messageBox.classList.add('hidden'); }, 3000);
    }

    // --- Password Reset Modal Control ---
    function openResetPasswordModal(employeeId, employeeName) {
        resetForm.reset(); resetFormMessage.classList.add('hidden');
        document.getElementById('resetEmployeeName').textContent = employeeName;
        document.getElementById('reset_employee_id').value = employeeId;
        document.getElementById('resetModalTitle').textContent = `Reset My Password`;
        resetModal.classList.remove('hidden'); formMessage.classList.add('hidden');
    }
    function closeResetPasswordModal() { resetModal.classList.add('hidden'); }

    // --- Password Reset Form Submission ---
    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_new_password').value;
        const employeeId = document.getElementById('reset_employee_id').value;
        if (newPassword !== confirmPassword) { showMessage(resetFormMessage, 'Error: Passwords do not match.', 'bg-red-100 text-red-700', false); return; }
        if (newPassword.length < 8) { showMessage(resetFormMessage, 'Error: Password must be at least 8 characters long.', 'bg-red-100 text-red-700', false); return; }
        showMessage(resetFormMessage, 'Resetting password...', 'bg-blue-100 text-blue-700', false);
        try {
            const response = await fetch('api/reset_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ employee_id: employeeId, new_password: newPassword }) });
            const result = await response.json();
            if (result.success) {
                showMessage(resetFormMessage, result.message, 'bg-green-100 text-green-700', true);
                setTimeout(() => { showMessage(formMessage, result.message, 'bg-green-100 text-green-700', true); closeResetPasswordModal(); }, 500);
            } else { showMessage(resetFormMessage, result.message, 'bg-red-100 text-red-700', false); }
        } catch (error) { console.error('Error:', error); showMessage(resetFormMessage, 'An unexpected network error occurred.', 'bg-red-100 text-red-700', false); }
    });

    // --- Profile Form Submission ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        saveButton.disabled = true; const originalButtonText = saveButton.textContent; saveButton.textContent = 'Saving...';
        showMessage(formMessage, 'Processing changes...', 'bg-blue-100 text-blue-700', false);
        const formData = new FormData(form); // Use FormData for file upload
        try {
            const response = await fetch('api/update_my_profile.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showMessage(formMessage, result.message, 'bg-green-100 text-green-700', true);
                if (result.new_picture_url) { profilePicDisplay.src = '../' + result.new_picture_url; } // Update pic display
                const firstName = document.getElementById('firstName').value; const lastName = document.getElementById('lastName').value;
                document.getElementById('view_fullName').textContent = firstName + ' ' + lastName; // Update name display
                setTimeout(() => formMessage.classList.add('hidden'), 2000);
            } else { showMessage(formMessage, result.message, 'bg-red-100 text-red-700', false); }
        } catch (error) { console.error('Error:', error); showMessage(formMessage, 'An unexpected network error occurred.', 'bg-red-100 text-red-700', false);
        } finally { saveButton.disabled = false; saveButton.textContent = originalButtonText; fileInput.value = null; } // Re-enable button, clear file input
    });

    // Preview selected image
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) { profilePicDisplay.src = e.target.result; };
            reader.readAsDataURL(file);
        } else { profilePicDisplay.src = '<?php echo $profile_pic_src; ?>'; } // Revert on cancel
    });

    // Set display name in reset modal
    document.getElementById('resetEmployeeName').textContent = currentEmployeeName;
</script>

<?php
include 'template/footer.php';
?>
