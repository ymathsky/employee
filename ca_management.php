<?php
// FILENAME: employee/ca_management.php
$pageTitle = 'CA/VALE Management';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

// Get currency symbol for display
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';

// Function to get all employees for the dropdown
function getAllEmployees($pdo) {
    try {
        $sql = "SELECT employee_id, first_name, last_name FROM employees ORDER BY last_name, first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}
$employees = getAllEmployees($pdo);
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-hand-holding-usd text-indigo-600 mr-3"></i>
        <span>Cash Advance (CA/VALE) Management</span>
    </h2>

    <!-- Add Transaction Form -->
    <div class="border p-6 rounded-lg bg-indigo-50 mb-8">
        <h3 class="text-xl font-medium text-gray-700 mb-4">Log New CA/VALE Transaction</h3>
        <form id="addCATransactionForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
                <select id="employee_id" name="employee_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="" disabled selected>-- Select employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="transaction_date" class="block text-sm font-medium text-gray-700">Date of Advance</label>
                <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount (<?php echo htmlspecialchars($currency_symbol); ?>)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex items-center space-x-4">
                <button type="submit" class="w-full px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i> Log Advance
                </button>
            </div>
        </form>
        <div id="add-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex justify-between items-center mb-3">
            <h4 class="text-sm font-medium text-gray-700">Filter Transactions</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="filter_employee" class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                <select id="filter_employee" class="block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_date" class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                <input type="date" id="filter_date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex items-end">
                <button id="clearFiltersBtn" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Deduction History</h3>
                <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mt-2">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payroll Period</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount Deducted</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Content loaded via JS -->
                    </tbody>
                </table>
                <div id="historyLoading" class="text-center py-4 hidden">
                    <i class="fas fa-spinner fa-spin text-indigo-600"></i> Loading...
                </div>
                <div id="historyEmpty" class="text-center py-4 text-gray-500 hidden">
                    No deduction history found.
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button onclick="closeHistoryModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>

    <!-- Transaction List Section -->
    <div class="flex justify-between items-center mb-4 border-t pt-6">
        <h3 id="transactionListTitle" class="text-xl font-medium text-gray-700">Pending Transactions (To be deducted)</h3>
        <div class="flex items-center space-x-2">
            <label for="showDeletedToggle" class="text-sm font-medium text-gray-700">Show Deleted:</label>
            <input type="checkbox" id="showDeletedToggle" name="show_deleted" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
        </div>
    </div>

    <div id="list-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" id="deletedDateHeader" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden">Deleted On</th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody id="transaction-body" class="bg-white divide-y divide-gray-200">
            <!-- Content loaded by JavaScript -->
            <tr><td colspan="6" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading transactions...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const addForm = document.getElementById('addCATransactionForm');
    const addMessage = document.getElementById('add-form-message');
    const listMessage = document.getElementById('list-message');
    const transactionBody = document.getElementById('transaction-body');
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;
    const showDeletedToggle = document.getElementById('showDeletedToggle');
    const transactionListTitle = document.getElementById('transactionListTitle');
    const deletedDateHeader = document.getElementById('deletedDateHeader');
    const initialColspan = 6; // Start with max colspan

    // Filter Elements
    const filterEmployee = document.getElementById('filter_employee');
    const filterDate = document.getElementById('filter_date');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    // --- Utility Functions ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
        if(autoHide) { setTimeout(() => messageBox.classList.add('hidden'), 3000); }
    }

    function formatCurrency(amount) {
        const numAmount = parseFloat(amount);
        // Use 0.00 as fallback for display if amount is invalid or zero
        return currencySymbol + (isNaN(numAmount) ? '0.00' : numAmount.toFixed(2));
    }

    function formatDateTime(dateTimeString) {
        // Explicitly return 'N/A' if the input is null, undefined, or an empty string
        if (!dateTimeString) return 'N/A';
        try {
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
            // Attempt to parse the date. Handle potential variations if needed.
            const date = new Date(dateTimeString.replace(' ', 'T') + 'Z'); // Assume UTC if no timezone provided
            if (isNaN(date.getTime())) { // Check if date parsing failed
                return 'Invalid Date';
            }
            return date.toLocaleString(undefined, options);
        } catch(e) {
            console.error("Error formatting date:", dateTimeString, e); // Log error for debugging
            return 'Invalid Date'; // Return clear error string
        }
    }

    // --- Load Transactions ---
    async function loadTransactions() {
        const showDeleted = showDeletedToggle.checked;
        const empId = filterEmployee.value;
        const dateVal = filterDate.value;

        let apiUrl = `api/ca_transactions.php?action=get${showDeleted ? '&show_deleted=true' : ''}`;
        if (empId) apiUrl += `&filter_employee_id=${empId}`;
        if (dateVal) apiUrl += `&filter_date=${dateVal}`;

        const currentCols = showDeleted ? 6 : 5;

        // Update Title and Header visibility
        transactionListTitle.textContent = showDeleted ? 'Deleted CA/VALE Transactions (Recoverable)' : 'Pending Transactions (To be deducted)';
        deletedDateHeader.classList.toggle('hidden', !showDeleted);

        transactionBody.innerHTML = `<tr><td colspan="${currentCols}" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading transactions...</td></tr>`;
        listMessage.classList.add('hidden');

        try {
            const response = await fetch(apiUrl);
            const result = await response.json();
            transactionBody.innerHTML = ''; // Clear loading/previous rows

            if (result.success && result.data.length > 0) {
                result.data.forEach(t => {
                    // Determine state based on API data
                    const isDeleted = t.deleted_at !== null && t.deleted_at !== undefined;
                    const isDeducted = t.deducted_in_payroll == 1;

                    // Set default styles/text
                    let statusText = 'Pending';
                    let statusClass = 'bg-yellow-100 text-yellow-800';
                    let actionButton = '';
                    let rowClass = '';
                    let amountColor = 'text-red-600';
                    let nameColor = 'text-gray-900';
                    let dateColor = 'text-gray-600';

                    // Apply styles/text based on state
                    if (isDeleted) {
                        statusText = 'Deleted';
                        statusClass = 'bg-gray-100 text-gray-500';
                        rowClass = 'bg-red-50 opacity-70';
                        amountColor = 'text-gray-500'; // Make amount less prominent
                        nameColor = 'text-gray-600';
                        dateColor = 'text-gray-500';
                        // Safety check: only allow restore if NOT deducted
                        actionButton = !isDeducted
                            ? `<button onclick="restoreTransaction(${t.transaction_id})" class="text-green-600 hover:text-green-900 font-medium mr-2">Restore</button>`
                            : `<span class="text-gray-400 italic mr-2">N/A (Deducted)</span>`;
                    } else if (isDeducted) {
                        statusText = 'Deducted';
                        statusClass = 'bg-green-100 text-green-800';
                        actionButton = `<span class="text-gray-400 italic mr-2">N/A (Deducted)</span>`;
                    } else { // Active and Pending (not deleted, not deducted)
                        statusText = 'Pending';
                        statusClass = 'bg-yellow-100 text-yellow-800';
                        actionButton = `<button onclick="deleteTransaction(${t.transaction_id})" class="text-red-600 hover:text-red-900 font-medium mr-2">Delete</button>`;
                    }

                    // Add History Button
                    actionButton += `<button onclick="viewHistory(${t.transaction_id})" class="text-indigo-600 hover:text-indigo-900 font-medium"><i class="fas fa-history"></i></button>`;


                    const row = document.createElement('tr');
                    row.id = `row-${t.transaction_id}`;
                    row.className = rowClass;
                    row.innerHTML = `
                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${nameColor}">
                             ${t.first_name} ${t.last_name} <span class="text-xs text-gray-500 block">(ID: ${t.employee_id})</span>
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm ${dateColor}">${t.transaction_date}</td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm ${amountColor} font-semibold">- ${formatCurrency(t.amount)}</td>
                         <td class="px-6 py-4 whitespace-nowrap text-center">
                             <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                 ${statusText}
                             </span>
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 deleted-col ${!showDeleted ? 'hidden' : ''}">
                             ${formatDateTime(t.deleted_at)}
                         </td>
                         <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium action-col">
                             ${actionButton}
                         </td>
                     `;
                    // Explicitly hide/show the 'Deleted On' cell after adding to DOM
                    const deletedCell = row.querySelector('.deleted-col');
                    if (deletedCell) {
                        deletedCell.style.display = showDeleted ? '' : 'none';
                    }

                    transactionBody.appendChild(row);
                });
            } else if (result.success) {
                const message = showDeleted ? 'No deleted transactions found.' : 'No pending cash advance transactions!';
                transactionBody.innerHTML = `<tr><td colspan="${currentCols}" class="text-center p-6 text-gray-500">${message}</td></tr>`;
            } else {
                showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false);
                transactionBody.innerHTML = `<tr><td colspan="${currentCols}" class="text-center p-6 text-red-500">Failed to load data.</td></tr>`;
            }
        } catch (error) {
            console.error('Network Error:', error);
            showMessage(listMessage, 'Network error fetching transactions.', 'bg-red-100 text-red-700', false);
            transactionBody.innerHTML = `<tr><td colspan="${currentCols}" class="text-center p-6 text-red-500">Network error.</td></tr>`;
        }
    }

    // --- Add Transaction Logic (Unchanged) ---
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(addMessage, 'Logging advance...', 'bg-blue-100 text-blue-700', false);
        const formData = new FormData(addForm);
        const data = Object.fromEntries(formData.entries());
        data.amount = parseFloat(data.amount);

        try {
            const response = await fetch('api/ca_transactions.php?action=add', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                showMessage(addMessage, result.message, 'bg-green-100 text-green-700');
                addForm.reset();
                document.getElementById('transaction_date').value = new Date().toISOString().slice(0, 10);
                if (!showDeletedToggle.checked) { setTimeout(loadTransactions, 1000); }
            } else { showMessage(addMessage, result.message, 'bg-red-100 text-red-700'); }
        } catch (error) { console.error('Error:', error); showMessage(addMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700'); }
    });

    // --- Delete Transaction Logic (Soft Delete) ---
    function deleteTransaction(transactionId) {
        if (!confirm('Are you sure you want to delete this cash advance transaction? It can be restored later.')) { return; }
        showMessage(listMessage, 'Deleting...', 'bg-blue-100 text-blue-700', false);
        fetch('api/ca_transactions.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ transaction_id: transactionId }) })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage(listMessage, result.message, 'bg-green-100 text-green-700');
                    // Don't visually remove immediately, let loadTransactions handle refresh
                    setTimeout(loadTransactions, 500);
                } else { showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false); }
            })
            .catch(error => { console.error('Error:', error); showMessage(listMessage, 'An error occurred during deletion.', 'bg-red-100 text-red-700', false); });
    }

    // --- Restore Transaction Logic ---
    function restoreTransaction(transactionId) {
        if (!confirm('Are you sure you want to restore this deleted cash advance transaction?')) { return; }
        showMessage(listMessage, 'Restoring...', 'bg-blue-100 text-blue-700', false);
        fetch('api/ca_transactions.php?action=restore', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ transaction_id: transactionId }) })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage(listMessage, result.message, 'bg-green-100 text-green-700');
                    // Don't visually remove immediately, let loadTransactions handle refresh
                    setTimeout(loadTransactions, 500);
                } else { showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false); }
            })
            .catch(error => { console.error('Error:', error); showMessage(listMessage, 'An error occurred during restoration.', 'bg-red-100 text-red-700', false); });
    }

    // --- History Modal Logic ---
    const historyModal = document.getElementById('historyModal');
    const historyTableBody = document.getElementById('historyTableBody');
    const historyLoading = document.getElementById('historyLoading');
    const historyEmpty = document.getElementById('historyEmpty');

    function closeHistoryModal() {
        historyModal.classList.add('hidden');
    }

    async function viewHistory(transactionId) {
        historyModal.classList.remove('hidden');
        historyTableBody.innerHTML = '';
        historyLoading.classList.remove('hidden');
        historyEmpty.classList.add('hidden');

        try {
            const response = await fetch(`api/ca_transactions.php?action=get_history&transaction_id=${transactionId}`);
            const result = await response.json();
            
            historyLoading.classList.add('hidden');

            if (result.success && result.data.length > 0) {
                result.data.forEach(h => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${h.deduction_date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${h.pay_period_start ? `${h.pay_period_start} to ${h.pay_period_end}` : 'N/A'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-red-600">
                            - ${formatCurrency(h.amount)}
                        </td>
                    `;
                    historyTableBody.appendChild(row);
                });
            } else {
                historyEmpty.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error fetching history:', error);
            historyLoading.classList.add('hidden');
            historyEmpty.textContent = 'Error loading history.';
            historyEmpty.classList.remove('hidden');
        }
    }

    // Initial load and toggle listener
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('transaction_date').value = new Date().toISOString().slice(0, 10);
        loadTransactions();
        showDeletedToggle.addEventListener('change', loadTransactions); // Reload when toggle changes

        // Filter Listeners
        filterEmployee.addEventListener('change', loadTransactions);
        filterDate.addEventListener('change', loadTransactions);
        clearFiltersBtn.addEventListener('click', () => {
            filterEmployee.value = '';
            filterDate.value = '';
            loadTransactions();
        });
    });
</script>

<?php
include 'template/footer.php';
?>

