<?php
// FILENAME: employee/global_settings.php
$pageTitle = 'Global Settings';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
// Only Super Admins can access this page
if ($_SESSION['role'] !== 'Super Admin') {
    // Not a Super Admin, redirect to their dashboard
    header('Location: ' . ($_SESSION['role'] === 'HR Admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

// Get all timezones
$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
?>

    <div class="bg-white p-8 rounded-xl shadow-xl max-w-2xl mx-auto">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Global Application Settings</h2>

        <form id="settingsForm">
            <div class="space-y-6">
                <!-- Company Name -->
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                    <input type="text" id="company_name" name="company_name" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                    <select id="timezone" name="timezone" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Select a Timezone...</option>
                        <?php foreach ($timezones as $tz): ?>
                            <option value="<?php echo htmlspecialchars($tz); ?>"><?php echo htmlspecialchars($tz); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Currency Symbol -->
                <div>
                    <label for="currency_symbol" class="block text-sm font-medium text-gray-700">Currency Symbol</label>
                    <input type="text" id="currency_symbol" name="currency_symbol" required placeholder="$"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- CA Deduction Name -->
                <div class="pt-4 border-t">
                    <label for="system_ca_deduction_name" class="block text-sm font-medium text-gray-700">
                        CA/VALE Payslip Name (e.g., Cash Advance)
                    </label>
                    <p class="text-xs text-gray-500 mb-1">This name will appear on payslips for automatic CA deductions.</p>
                    <input type="text" id="system_ca_deduction_name" name="system_ca_deduction_name" required placeholder="Cash Advance"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- NEW: Manual Adjustment Toggle -->
                <div class="pt-4 border-t mt-4">
                    <label for="allow_manual_attendance_edit" class="block text-sm font-medium text-gray-700">
                        Allow Manual Attendance Adjustments (HR & Managers)
                    </label>
                    <p class="text-xs text-gray-500 mb-1">
                        If set to "No", only Super Admins can manually add, edit, or delete attendance logs.
                    </p>
                    <select id="allow_manual_attendance_edit" name="allow_manual_attendance_edit" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="1">Yes - Allowed</option>
                        <option value="0">No - Restricted to Super Admin only</option>
                    </select>
                </div>

                <!-- NEW: Late Grace Period -->
                <div class="pt-4 border-t mt-4">
                    <label for="late_grace_period_minutes" class="block text-sm font-medium text-gray-700">
                        Late Grace Period (Minutes)
                    </label>
                    <p class="text-xs text-gray-500 mb-1">
                        Employees clocking in within this many minutes of their scheduled start time will NOT be marked as late or deducted.
                    </p>
                    <input type="number" id="late_grace_period_minutes" name="late_grace_period_minutes" min="0" required placeholder="0"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <!-- END NEW -->

                <!-- NEW: Hide Pay Rate Toggle -->
                <div class="pt-4 border-t mt-4">
                    <label for="hide_pay_rate_from_employee" class="block text-sm font-medium text-gray-700">
                        Hide Pay Rate from Employee
                    </label>
                    <p class="text-xs text-gray-500 mb-1">
                        If set to "Yes", employees will not see their pay rate on their profile or payslips.
                    </p>
                    <select id="hide_pay_rate_from_employee" name="hide_pay_rate_from_employee" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="0">No - Show Pay Rate</option>
                        <option value="1">Yes - Hide Pay Rate</option>
                    </select>
                </div>

                <!-- NEW: Auto-Refresh QR Code -->
                <div class="pt-4 border-t mt-4">
                    <label for="auto_refresh_qr" class="block text-sm font-medium text-gray-700">
                        Auto-Refresh QR Code
                    </label>
                    <p class="text-xs text-gray-500 mb-1">
                        If set to "No", QR codes will become static and will not expire every minute (useful for printed ID cards).
                    </p>
                    <select id="auto_refresh_qr" name="auto_refresh_qr" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="1">Yes - Auto-Refresh (Secure)</option>
                        <option value="0">No - Static (For ID Cards)</option>
                    </select>
                </div>


            </div>

            <!-- Submit Button -->
            <div class="flex justify-end mt-8 border-t pt-6">
                <button type="submit"
                        class="w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    Save Settings
                </button>
            </div>
        </form>
        <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
    </div>

    <script>
        const settingsForm = document.getElementById('settingsForm');
        const formMessage = document.getElementById('form-message');

        // 1. Fetch current settings on page load
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch('api/get_settings.php');
                const result = await response.json();

                if (result.success) {
                    // Populate the form fields with current values
                    document.getElementById('company_name').value = result.data.company_name || 'My Company';
                    document.getElementById('timezone').value = result.data.timezone || 'UTC';
                    document.getElementById('currency_symbol').value = result.data.currency_symbol || '$';
                    document.getElementById('system_ca_deduction_name').value = result.data.system_ca_deduction_name || 'Cash Advance';

                    // NEW: Populate the toggle
                    // Default to '1' (Allowed) if not set to avoid locking people out on first run
                    const editSetting = result.data.allow_manual_attendance_edit;
                    document.getElementById('allow_manual_attendance_edit').value = (editSetting !== undefined && editSetting !== null) ? editSetting : '1';
                    
                    // NEW: Late Grace Period
                    document.getElementById('late_grace_period_minutes').value = result.data.late_grace_period_minutes || '0';

                    // NEW: Hide Pay Rate
                    document.getElementById('hide_pay_rate_from_employee').value = result.data.hide_pay_rate_from_employee || '0';
                    
                    // NEW: Auto-Refresh QR
                    document.getElementById('auto_refresh_qr').value = (result.data.auto_refresh_qr !== undefined && result.data.auto_refresh_qr !== null) ? result.data.auto_refresh_qr : '1';


                } else {
                    showMessage(result.message, 'bg-red-100 text-red-700');
                }
            } catch (error) {
                console.error('Error fetching settings:', error);
                showMessage('Could not load settings.', 'bg-red-100 text-red-700');
            }
        });

        // 2. Handle form submission
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(settingsForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/update_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'bg-green-100 text-green-700');
                    // Optional: Reload to reflect changes in session immediately if needed,
                    // though usually takes effect on next page load/login
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(result.message, 'bg-red-100 text-red-700');
                }
            } catch (error) {
                console.error('Error updating settings:', error);
                showMessage('An error occurred. Please try again.', 'bg-red-100 text-red-700');
            }
        });

        function showMessage(message, className) {
            formMessage.textContent = message;
            formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
            formMessage.classList.remove('hidden');
        }
    </script>

<?php
include 'template/footer.php';
?>