<?php
// FILENAME: employee/deduction_management.php
$pageTitle = 'Deduction Management';
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
    error_log("Error fetching employees for deduction management: " . $e->getMessage());
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Manage Global Payroll Deductions</h2>

    <!-- Add Deduction Form -->
    <div class="border p-6 rounded-lg mb-8 print:hidden">
        <h3 class="text-xl font-medium text-gray-700 mb-4">Add New Deduction Type</h3>
        <p class="text-sm text-gray-500 mb-4">Examples: **CA (Cash Advance)** or **VALE** (Fixed amount), or **Income Tax** (Percentage).</p> <!-- NEW TIP -->
        <form id="addDeductionForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name (e.g., CA, Tax)</label>
                <!-- UPDATED PLACEHOLDER -->
                <input type="text" id="name" name="name" required placeholder="e.g., CA or VALE" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
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
                    <option value="Percentage">Percentage (%)</option>
                    <option value="Fixed">Fixed Amount</option>
                </select>
            </div>
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700">Value (<?php echo htmlspecialchars($currency_symbol); ?> / %)</label>
                <input type="number" id="value" name="value" step="0.01" min="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex items-center space-x-4">
                <button type="submit" class="w-full px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    Add Deduction
                </button>
            </div>
        </form>
        <div id="add-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
    </div>

    <!-- Deduction List Table -->
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-medium text-gray-700">Current Deduction Types</h3>
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
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Value
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider print:hidden">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider print:hidden">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody id="deduction-body" class="bg-white divide-y divide-gray-200">
            <!-- Content loaded by JavaScript -->
            <tr><td colspan="5" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading deductions...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Deduction Modal -->
<div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="editDeductionForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-minus-circle text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="edit-modal-title">Edit Deduction</h3>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="edit_deduction_id" name="deduction_id">

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
                                        <option value="Percentage">Percentage (%)</option>
                                        <option value="Fixed">Fixed Amount</option>
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
    const addForm = document.getElementById('addDeductionForm');
    const addMessage = document.getElementById('add-form-message');
    const listMessage = document.getElementById('list-message');
    const deductionBody = document.getElementById('deduction-body');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editDeductionForm');
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

    // --- Load Deductions ---
    async function loadDeductions() {
        deductionBody.innerHTML = '<tr><td colspan="5" class="text-center p-6 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Loading deductions...</td></tr>';
        listMessage.classList.add('hidden');

        try {
            const response = await fetch('api/get_deductions.php');
            const result = await response.json();

            deductionBody.innerHTML = ''; // Clear loading row

            if (result.success && result.data.length > 0) {
                let totalFixed = 0;

                result.data.forEach(deduction => {
                    const val = parseFloat(deduction.value);
                    if (deduction.type === 'Fixed' && deduction.is_active == 1) {
                         totalFixed += val;
                    }

                    const valueDisplay = deduction.type === 'Percentage' ? `${val.toFixed(2)}%` : `${currencySymbol}${val.toFixed(2)}`;
                    const statusClass = deduction.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const statusText = deduction.is_active == 1 ? 'Active' : 'Inactive';
                    
                    let targetDisplay = '<span class="text-gray-500 italic">Global</span>';
                    if (deduction.employee_id) {
                        targetDisplay = `<span class="text-indigo-600 font-medium">${deduction.first_name} ${deduction.last_name}</span>`;
                    }

                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${deduction.name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${targetDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${deduction.type}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold text-right">${valueDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap print:hidden">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium print:hidden">
                                <button onclick='openEditModal(${JSON.stringify(deduction)})' class="text-indigo-600 hover:text-indigo-900 mr-4 font-medium">Edit</button>
                                <button onclick="deleteDeduction(${deduction.deduction_id}, '${deduction.name}')" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                            </td>
                        </tr>
                    `;
                    deductionBody.insertAdjacentHTML('beforeend', row);
                });

                // Add Total Row
                const totalRow = `
                    <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                        <td colspan="3" class="px-6 py-4 text-right text-gray-700 uppercase tracking-wider">Total Active Fixed Deductions:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-indigo-700 text-right">${currencySymbol}${totalFixed.toFixed(2)}</td>
                        <td class="px-6 py-4 print:hidden"></td>
                        <td class="px-6 py-4 print:hidden"></td>
                    </tr>
                `;
                deductionBody.insertAdjacentHTML('beforeend', totalRow);

            } else if (result.success) {
                deductionBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-gray-500">No deduction types defined. Add one above.</td></tr>';
            } else {
                showMessage(listMessage, result.message, 'bg-red-100 text-red-700', false);
                deductionBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-red-500">Failed to load data.</td></tr>';
            }
        } catch (error) {
            console.error('Network Error:', error);
            showMessage(listMessage, 'Network error fetching deduction data.', 'bg-red-100 text-red-700', false);
            deductionBody.innerHTML = '<tr><td colspan="6" class="text-center p-6 text-red-500">Network error.</td></tr>';
        }
    }

    // --- Add Deduction Logic ---
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(addMessage, 'Adding...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(addForm);
        // Handle multiple select manually because FormData might verify it differently depending on backend
        // But standard FormData handles [] named fields well if PHP reads it correctly. 
        // We will convert FormData to a plain structure that handles array values.
        
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
            const response = await fetch('api/add_deduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showMessage(addMessage, result.message, 'bg-green-100 text-green-700');
                addForm.reset();
                setTimeout(loadDeductions, 1000); // Reload table
            } else {
                showMessage(addMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(addMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    // --- Edit Deduction Modal Logic ---
    function openEditModal(deduction) {
        editMessage.classList.add('hidden');
        editForm.reset();

        document.getElementById('edit_deduction_id').value = deduction.deduction_id;
        document.getElementById('edit_name').value = deduction.name;
        document.getElementById('edit_type').value = deduction.type;
        document.getElementById('edit_value').value = parseFloat(deduction.value).toFixed(2);
        document.getElementById('edit_is_active').checked = deduction.is_active == 1;
        
        // Set employee dropdown
        const empSelect = document.getElementById('edit_employee_id');
        if (deduction.employee_id) {
            empSelect.value = deduction.employee_id;
        } else {
            empSelect.value = ""; // Global
        }
        
        // Populate Exclusions
        const excludeSelect = document.getElementById('edit_excluded_employees');
        // Clear previous selections
        Array.from(excludeSelect.options).forEach(option => option.selected = false);
        
        if (deduction.excluded_employees && Array.isArray(deduction.excluded_employees)) {
            deduction.excluded_employees.forEach(id => {
                 const option = excludeSelect.querySelector(`option[value="${id}"]`);
                 if (option) option.selected = true;
            });
        }
        
        toggleExclusionField('edit'); // Update visibility

        editModal.classList.remove('hidden');
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
        if(!data.deduction_id) {
             showMessage(editMessage, 'Missing deduction ID', 'bg-red-100 text-red-700', false);
             return;
        }

        try {
            const response = await fetch('api/update_deduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) { // Fixed typo result.sucess 
                showMessage(editMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    closeEditModal();
                    loadDeductions();
                }, 1000);
            } else {
                showMessage(editMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(editMessage, 'An error occurred.', 'bg-red-100 text-red-700');
        }
    });

    function closeEditModal() {
        editModal.classList.add('hidden');
    }

    // Handle Edit Form Submission
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(editMessage, 'Saving changes...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());

        // Handle checkbox value manually
        data.is_active = document.getElementById('edit_is_active').checked;
        data.value = parseFloat(data.value);

        try {
            const response = await fetch('api/update_deduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showMessage(editMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    closeEditModal();
                    loadDeductions();
                }, 1000);
            } else {
                showMessage(editMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(editMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    // --- Delete Deduction Logic ---
    function deleteDeduction(deductionId, deductionName) {
        if (!confirm(`Are you sure you want to delete the deduction type: "${deductionName}"? This cannot be undone.`)) {
            return;
        }

        showMessage(listMessage, 'Deleting...', 'bg-blue-100 text-blue-700', false);

        fetch('api/delete_deduction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deduction_id: deductionId })
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage(listMessage, result.message, 'bg-green-100 text-green-700');
                    setTimeout(loadDeductions, 1000);
                } else {
                    showMessage(listMessage, result.message, 'bg-red-100 text-red-700');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(listMessage, 'An error occurred during deletion.', 'bg-red-100 text-red-700');
            });
    }


    document.addEventListener('DOMContentLoaded', loadDeductions);
</script>

<?php
include 'template/footer.php';
?>
