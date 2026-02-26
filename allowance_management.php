<?php
// FILENAME: employee/allowance_management.php
$pageTitle = 'Allowance Management';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
// Only Admins can access this page
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

// Get currency symbol for display
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';

// Fetch all employees for the dropdown
$employees = [];
try {
    $stmt = $pdo->query("SELECT employee_id, first_name, last_name FROM employees ORDER BY first_name ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching employees for allowance management: " . $e->getMessage());
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Manage Payroll Allowances & Bonuses</h2>

    <!-- Add Allowance Form -->
    <div class="border p-6 rounded-lg mb-8 print:hidden">
        <h3 class="text-xl font-medium text-gray-700 mb-4">Add New Allowance Type</h3>
        <p class="text-sm text-gray-500 mb-4">Examples: **Housing Allowance**, **Transport**, **Performance Bonus** (Fixed amount or Percentage).</p>
        <form id="addAllowanceForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name (e.g., Housing, Bonus)</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Housing Allowance" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700">Target Employee</label>
                <select id="employee_id" name="employee_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" onchange="toggleExclusionField('add')">
                    <option value="">-- Global (All Employees) --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="add_exclusion_container" class="hidden">
                 <label for="excluded_employees" class="block text-sm font-medium text-gray-700">Exclude Employees</label>
                 <select id="excluded_employees" name="excluded_employees[]" multiple class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm h-24">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                 </select>
                 <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select id="type" name="type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="Fixed">Fixed Amount</option>
                    <option value="Percentage">Percentage (%)</option>
                </select>
            </div>
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700">Value (<?php echo htmlspecialchars($currency_symbol); ?> / %)</label>
                <input type="number" id="value" name="value" step="0.01" min="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex items-center space-x-4">
                <button type="submit" class="w-full px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Add Allowance
                </button>
            </div>
        </form>
        <div id="add-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
    </div>

    <!-- Allowance List Table -->
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-medium text-gray-700">Current Allowance Types</h3>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center print:hidden">
            <i class="fas fa-print mr-2"></i> Print List
        </button>
    </div>
    <div id="list-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Target
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Value print:hidden
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody id="allowance-body" class="bg-white divide-y divide-gray-200">
            <!-- Content loaded by JavaScript -->
            <tr><td colspan="6" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading allowances...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Allowance Modal -->
<div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="editAllowanceForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-plus-circle text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="edit-modal-title">Edit Allowance</h3>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="edit_allowance_id" name="allowance_id">

                                <div>
                                    <label for="edit_name" class="block text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" id="edit_name" name="name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="edit_employee_id" class="block text-sm font-medium text-gray-700">Target Employee</label>
                                    <select id="edit_employee_id" name="employee_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" onchange="toggleExclusionField('edit')">
                                        <option value="">-- Global (All Employees) --</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo $emp['employee_id']; ?>">
                                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="edit_exclusion_container" class="hidden">
                                     <label for="edit_excluded_employees" class="block text-sm font-medium text-gray-700">Exclude Employees</label>
                                     <select id="edit_excluded_employees" name="excluded_employees[]" multiple class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm h-24">
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo $emp['employee_id']; ?>">
                                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                     </select>
                                     <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                                </div>
                                <div>
                                    <label for="edit_type" class="block text-sm font-medium text-gray-700">Type</label>
                                    <select id="edit_type" name="type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="Fixed">Fixed Amount</option>
                                        <option value="Percentage">Percentage (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_value" class="block text-sm font-medium text-gray-700">Value (<?php echo htmlspecialchars($currency_symbol); ?> / %)</label>
                                    <input type="number" id="edit_value" name="value" step="0.01" min="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex items-center">
                                    <input id="edit_is_active" name="is_active" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    <label for="edit_is_active" class="ml-2 block text-sm text-gray-900">
                                        Active (Apply to payroll runs)
                                    </label>
                                </div>
                                <div id="edit-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Edit Modal -->

<script>
    const addForm = document.getElementById('addAllowanceForm');
    const addMessage = document.getElementById('add-form-message');
    const listMessage = document.getElementById('list-message');
    const allowanceBody = document.getElementById('allowance-body');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editAllowanceForm');
    const editMessage = document.getElementById('edit-form-message');
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;

    // --- toggleExclusionField ---
    function toggleExclusionField(mode) {
        const targetSelectId = mode === 'add' ? 'employee_id' : 'edit_employee_id';
        const containerId = mode === 'add' ? 'add_exclusion_container' : 'edit_exclusion_container';
        
        const targetSelect = document.getElementById(targetSelectId);
        const container = document.getElementById(containerId);
        
        if (targetSelect.value === "") {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }
    
    // Initialize toggles
    toggleExclusionField('add');

    // --- Utility Functions ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');

        if(autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 3000);
        }
    }

    // --- Load Allowances ---
    async function loadAllowances() {
        allowanceBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading allowances...</td></tr>';
        listMessage.classList.add('hidden');

        try {
            const response = await fetch('api/get_allowances.php');
            const result = await response.json();

            allowanceBody.innerHTML = ''; // Clear loading row

            if (result.success && result.data.length > 0) {
                result.data.forEach(allowance => {
                    const valueDisplay = allowance.type === 'Percentage' ? `${parseFloat(allowance.value).toFixed(2)}%` : `${currencySymbol}${parseFloat(allowance.value).toFixed(2)}`;
                    const statusClass = allowance.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const statusText = allowance.is_active == 1 ? 'Active' : 'Inactive';
                    
                    let targetDisplay = '<span class="text-gray-500 italic">Global</span>';
                    if (allowance.employee_id) {
                        targetDisplay = `<span class="text-indigo-600 font-medium">${allowance.first_name} ${allowance.last_name}</span>`;
                    }

                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${allowance.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${targetDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${allowance.type}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">${valueDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap"> print:hidden
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick='openEditModal(${JSON.stringify(allowance)})' class="text-indigo-600 hover:text-indigo-900 mr-4 font-medium">Edit</button>
                                <button onclick="deleteAllowance(${allowance.allowance_id}, '${allowance.name}')" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                            </td>
                        </tr>
                    `;
                    allowanceBody.insertAdjacentHTML('beforeend', row);
                });
            } else if (result.success) {
                allowanceBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-gray-500">No allowance types defined. Add one above.</td></tr>';
            } else {
                showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false);
                allowanceBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-red-500">Failed to load data.</td></tr>';
            }
        } catch (error) {
            console.error('Network Error:', error);
            showMessage(listMessage, 'Network error fetching allowance data.', 'bg-red-100 text-red-700', false);
            allowanceBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-red-500">Network error.</td></tr>';
        }
    }

    // --- Add Allowance Logic ---
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(addMessage, 'Adding...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(addForm);
        const data = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) {
                    data[cleanKey] = [];
                }
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        });
        
        data.value = parseFloat(data.value);

        try {
            const response = await fetch('api/add_allowance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showMessage(addMessage, result.message, 'bg-green-100 text-green-700');
                addForm.reset();
                setTimeout(loadAllowances, 1000); // Reload table
            } else {
                showMessage(addMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(addMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    // --- Edit Allowance Modal Logic ---
    function openEditModal(allowance) {
        editMessage.classList.add('hidden');
        editForm.reset();

        document.getElementById('edit_allowance_id').value = allowance.allowance_id;
        document.getElementById('edit_name').value = allowance.name;
        document.getElementById('edit_type').value = allowance.type;
        document.getElementById('edit_value').value = parseFloat(allowance.value).toFixed(2);
        document.getElementById('edit_is_active').checked = allowance.is_active == 1;
        
        // Set employee dropdown
        const empSelect = document.getElementById('edit_employee_id');
        if (allowance.employee_id) {
            empSelect.value = allowance.employee_id;
        } else {
            empSelect.value = ""; // Global
        }
        
        // Populate Exclusions
        const excludeSelect = document.getElementById('edit_excluded_employees');
        // Clear previous selections
        Array.from(excludeSelect.options).forEach(option => option.selected = false);
        
        if (allowance.excluded_employees && Array.isArray(allowance.excluded_employees)) {
            allowance.excluded_employees.forEach(id => {
                 const option = excludeSelect.querySelector(`option[value="${id}"]`);
                 if (option) option.selected = true;
            });
        }
        
        toggleExclusionField('edit'); // Update visibility

        editModal.classList.remove('hidden');
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
    }

    // Handle Edit Submit
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(editMessage, 'Saving...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(editForm);
        const data = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) {
                    data[cleanKey] = [];
                }
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        });
        
        data.value = parseFloat(data.value);
        // Handle checkbox manual override if not sent by form (FormData handles checkboxes only if checked)
        // If unchecked, it won't be in FormData, so we need to set it.
        // But my logic below uses `Object.fromEntries` logic which failed for Arrays, but here I am manual parsing.
        // Wait, checkboxes are tricky. If unchecked, not in FormData.
        data.is_active = document.getElementById('edit_is_active').checked;

        if(!data.allowance_id) {
             showMessage(editMessage, 'Missing allowance ID', 'bg-red-100 text-red-700', false);
             return;
        }

        try {
            const response = await fetch('api/update_allowance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showMessage(editMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    closeEditModal();
                    loadAllowances();
                }, 1000);
            } else {
                showMessage(editMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(editMessage, 'An error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // --- Delete Allowance Logic ---
    function deleteAllowance(allowanceId, allowanceName) {
        if (!confirm(`Are you sure you want to delete the allowance type: "${allowanceName}"? This cannot be undone.`)) {
            return;
        }

        showMessage(listMessage, 'Deleting...', 'bg-blue-100 text-blue-700', false);

        fetch('api/delete_allowance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ allowance_id: allowanceId })
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage(listMessage, result.message, 'bg-green-100 text-green-700');
                    setTimeout(loadAllowances, 1000);
                } else {
                    showMessage(listMessage, result.message, 'bg-red-100 text-red-700');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(listMessage, 'An error occurred during deletion.', 'bg-red-100 text-red-700');
            });
    }


    document.addEventListener('DOMContentLoaded', loadAllowances);
</script>

<?php
include 'template/footer.php';
?>
