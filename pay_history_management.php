<?php
// FILENAME: employee/pay_history_management.php
$pageTitle = 'Pay History Management';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

// --- Get currency symbol from session ---
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
// --- END NEW ---

// --- REMOVED PHP function getEmployeesForPayHistory ---
// --- REMOVED $employees variable assignment ---
?>

<!-- Employee List Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Employee Pay Rate History</h2>
    <div id="list-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Job Title
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Current Pay Rate
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <!-- MODIFIED: Added ID and initial loading row -->
            <tbody id="employeeListBody" class="bg-white divide-y divide-gray-200">
            <tr>
                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading employee list...
                </td>
            </tr>
            </tbody>
            <!-- END MODIFICATION -->
        </table>
    </div>
</div>

<!-- Pay History Modal -->
<div id="historyModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-history text-green-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="historyModalTitle">
                            Pay Rate History for [Employee Name]
                        </h3>
                        <input type="hidden" id="history_employee_id">
                        <div id="history-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

                        <!-- History Table -->
                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Type</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody id="history-body" class="bg-white divide-y divide-gray-200">
                                <!-- History rows loaded by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="openAddEditHistoryModal()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-plus mr-2"></i> Add New Rate
                </button>
                <button type="button" onclick="closeHistoryModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
<!-- End History Modal -->


<!-- Add/Edit Pay History Modal (Combined) -->
<div id="addEditHistoryModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="add-edit-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="addEditHistoryForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-dollar-sign text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="addEditModalTitle">
                                Add New Pay Rate Entry
                            </h3>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="history_id" name="history_id">
                                <input type="hidden" id="rate_employee_id" name="employee_id">

                                <div>
                                    <label for="effective_start_date" class="block text-sm font-medium text-gray-700">Effective Start Date</label>
                                    <input type="date" id="effective_start_date" name="effective_start_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="pay_type" class="block text-sm font-medium text-gray-700">Pay Type</label>
                                    <select id="pay_type" name="pay_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <!-- MODIFIED: Updated options -->
                                        <option value="Hourly">Hourly</option>
                                        <option value="Daily">Daily</option>
                                        <option value="Fix Rate">Fix Rate</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="pay_rate" class="block text-sm font-medium text-gray-700">Pay Rate (<?php echo htmlspecialchars($currency_symbol); ?>)</label>
                                    <input type="number" id="pay_rate" name="pay_rate" step="0.01" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div id="add-edit-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="saveRateBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Rate
                    </button>
                    <button type="button" onclick="closeAddEditHistoryModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add/Edit Modal -->

<script>
    const historyModal = document.getElementById('historyModal');
    const historyBody = document.getElementById('history-body');
    const historyMessage = document.getElementById('history-message');
    const addEditModal = document.getElementById('addEditHistoryModal');
    const addEditForm = document.getElementById('addEditHistoryForm');
    const addEditMessage = document.getElementById('add-edit-form-message');
    const employeeListBody = document.getElementById('employeeListBody'); // Reference to table body
    const listMessage = document.getElementById('list-message'); // Message area above table
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;
    let currentEmployeeId = null;
    let currentEmployeeName = '';

    // --- Utility Functions ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `p-3 rounded-lg text-center ${className} mb-4`; // Added margin-bottom
        messageBox.classList.remove('hidden');

        if (autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 4000); // Increased duration slightly
        }
    }

    function formatCurrency(amount) {
        const numAmount = parseFloat(amount);
        return currencySymbol + (isNaN(numAmount) ? '0.00' : numAmount.toFixed(2));
    }

    // --- Load Employee List Asynchronously ---
    async function loadEmployeeList() {
        employeeListBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading employees...</td></tr>';
        listMessage.classList.add('hidden'); // Hide any previous messages

        try {
            // Call the updated pay_history API with the new action
            const response = await fetch(`api/pay_history.php?action=list_employees`);
            const result = await response.json();

            employeeListBody.innerHTML = ''; // Clear loading indicator

            if (result.success && result.data.length > 0) {
                result.data.forEach(emp => {
                    let rateDisplay = '<span class="text-red-500">Missing Rate</span>';
                    if (emp.pay_rate > 0 && emp.pay_type !== 'N/A') {
                        let suffix = '';
                        if (emp.pay_type === 'Hourly') suffix = ' / hr';
                        else if (emp.pay_type === 'Daily') suffix = ' / day';
                        else if (emp.pay_type === 'Fix Rate') suffix = ' / period';
                        rateDisplay = formatCurrency(emp.pay_rate) + suffix;
                    }

                    // Escape single quotes in names for the onclick attribute
                    const safeEmployeeName = emp.first_name.replace(/'/g, "\\'") + ' ' + emp.last_name.replace(/'/g, "\\'");

                    const row = `
                         <tr>
                             <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${emp.first_name} ${emp.last_name}</td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${emp.job_title || 'N/A'}</td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${rateDisplay}</td>
                             <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                 <button onclick="openHistoryModal(${emp.employee_id}, '${safeEmployeeName}')" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                     View/Manage History
                                 </button>
                             </td>
                         </tr>
                     `;
                    employeeListBody.insertAdjacentHTML('beforeend', row);
                });
            } else if (result.success) {
                employeeListBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No employees found.</td></tr>';
            } else {
                showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false);
                employeeListBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Failed to load employee list.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading employee list:', error);
            showMessage(listMessage, 'Network error fetching employee list.', 'bg-red-100 text-red-700', false);
            employeeListBody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Network error.</td></tr>';
        }
    }
    // --- END NEW FUNCTION ---


    // --- History Modal Logic ---
    function openHistoryModal(employeeId, employeeName) {
        currentEmployeeId = employeeId;
        currentEmployeeName = employeeName;
        document.getElementById('historyModalTitle').textContent = `Pay Rate History for ${employeeName}`;
        document.getElementById('history_employee_id').value = employeeId;
        historyModal.classList.remove('hidden');
        loadPayHistory();
    }

    function closeHistoryModal() {
        historyModal.classList.add('hidden');
    }

    // --- Add/Edit Modal Logic ---
    function openAddEditHistoryModal(historyData = null) {
        addEditForm.reset();
        addEditMessage.classList.add('hidden');
        document.getElementById('rate_employee_id').value = currentEmployeeId; // Set employee ID for the form

        if (historyData) {
            // Edit Mode
            document.getElementById('addEditModalTitle').textContent = 'Edit Pay Rate Entry';
            document.getElementById('history_id').value = historyData.history_id;
            document.getElementById('effective_start_date').value = historyData.effective_start_date;
            document.getElementById('pay_type').value = historyData.pay_type;
            document.getElementById('pay_rate').value = parseFloat(historyData.pay_rate).toFixed(2);
            document.getElementById('saveRateBtn').textContent = 'Save Changes';
        } else {
            // Add Mode
            document.getElementById('addEditModalTitle').textContent = 'Add New Pay Rate Entry';
            document.getElementById('history_id').value = ''; // Ensure history_id is empty for add
            document.getElementById('saveRateBtn').textContent = 'Save Rate';
            // Pre-fill today's date for new entry
            document.getElementById('effective_start_date').value = new Date().toISOString().slice(0, 10);
        }

        historyModal.classList.add('hidden'); // Hide history modal temporarily
        addEditModal.classList.remove('hidden');
    }

    function closeAddEditHistoryModal() {
        addEditModal.classList.add('hidden');
        // Only re-show history modal if we came from it (i.e., if currentEmployeeId is set)
        if (currentEmployeeId !== null) {
            historyModal.classList.remove('hidden');
        }
    }

    // --- Fetch History (inside modal) ---
    async function loadPayHistory() {
        if (!currentEmployeeId) return; // Don't load if no employee selected

        historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading history...</td></tr>';
        historyMessage.classList.add('hidden');

        try {
            const response = await fetch(`api/pay_history.php?action=get&employee_id=${currentEmployeeId}`);
            const result = await response.json();

            historyBody.innerHTML = ''; // Clear loading row

            if (result.success && result.data.length > 0) {
                result.data.forEach(history => {
                    // MODIFIED: Updated Suffix logic
                    let suffix = '';
                    if (history.pay_type === 'Hourly') suffix = ' / hr';
                    else if (history.pay_type === 'Daily') suffix = ' / day';
                    else if (history.pay_type === 'Fix Rate') suffix = ' / period';

                    const rateDisplay = formatCurrency(history.pay_rate) + suffix;

                    // Escape single quotes in JSON string for onclick attribute
                    const safeHistoryData = JSON.stringify(history).replace(/'/g, "\\'");

                    const row = `
                         <tr>
                             <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${history.effective_start_date}</td>
                             <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">${history.pay_type}</td>
                             <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">${rateDisplay}</td>
                             <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                                 <button onclick='openAddEditHistoryModal(${safeHistoryData})' class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                 <button onclick="deletePayHistory(${history.history_id})" class="text-red-600 hover:text-red-900">Delete</button>
                             </td>
                         </tr>
                     `;
                    historyBody.insertAdjacentHTML('beforeend', row);
                });
            } else if (result.success) {
                historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500">No pay history records found. Click "Add New Rate" to create one.</td></tr>';
            } else {
                showMessage(historyMessage, result.message, 'bg-red-100 text-red-700', false);
                historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Failed to load history.</td></tr>';
            }
        } catch (error) {
            console.error('Error loading pay history:', error);
            showMessage(historyMessage, 'Network error fetching pay history.', 'bg-red-100 text-red-700', false);
            historyBody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Network error.</td></tr>';
        }
    }

    // --- Submit Add/Edit Form ---
    addEditForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addEditForm);
        const data = Object.fromEntries(formData.entries());

        // Ensure employee_id is included (should be set in openAddEditHistoryModal)
        if (!data.employee_id) {
            showMessage(addEditMessage, 'Error: Employee ID is missing.', 'bg-red-100 text-red-700', false);
            return;
        }

        const isEdit = !!data.history_id; // Check if history_id exists and is not empty
        const action = isEdit ? 'update' : 'add';

        showMessage(addEditMessage, 'Saving rate...', 'bg-blue-100 text-blue-700', false);

        try {
            const response = await fetch(`api/pay_history.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showMessage(addEditMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    closeAddEditHistoryModal(); // Close add/edit modal
                    loadPayHistory(); // Reload history table inside the history modal
                    loadEmployeeList(); // Reload the main employee list table to show updated current rate
                }, 1000);
            } else {
                showMessage(addEditMessage, result.message, 'bg-red-100 text-red-700', false); // Show error in modal
            }
        } catch (error) {
            console.error('Error saving pay history:', error);
            showMessage(addEditMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700', false);
        }
    });

    // --- Delete History ---
    async function deletePayHistory(historyId) {
        if (!confirm('Are you sure you want to delete this pay rate history record? This is critical payroll data and cannot be easily undone.')) {
            return;
        }

        showMessage(historyMessage, 'Deleting record...', 'bg-blue-100 text-blue-700', false);

        try {
            const response = await fetch('api/pay_history.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ history_id: historyId })
            });

            const result = await response.json();
            if (result.success) {
                showMessage(historyMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    loadPayHistory(); // Reload history table
                    loadEmployeeList(); // Reload main table
                }, 1000);
            } else {
                showMessage(historyMessage, result.message, 'bg-red-100 text-red-700', false);
            }
        } catch (error) {
            console.error('Error deleting pay history:', error);
            showMessage(historyMessage, 'An error occurred during deletion.', 'bg-red-100 text-red-700', false);
        }
    }

    // --- Initial Load ---
    document.addEventListener('DOMContentLoaded', loadEmployeeList); // Load main list on page load

</script>

<?php
include 'template/footer.php';
?>

