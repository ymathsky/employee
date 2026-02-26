<?php
// FILENAME: employee/edit_employee_page.php

// Set the page title before including the header
$pageTitle = 'Edit Employee';
include 'template/header.php';

// Only allow Admins
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Access Denied. You do not have permission to view this page.</div>";
    include 'template/footer.php';
    exit;
}

// Get Employee ID from URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: No employee ID provided.</div>";
    include 'template/footer.php';
    exit;
}

// Get roles from config
require_once __DIR__ . '/config/app_config.php';
?>

<!-- Edit Employee Form -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Edit Employee Details</h2>
        <a href="employee_management.php" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
            &larr; Back to Employee List
        </a>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="text-center p-8">
        <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
        <p class="mt-3 text-gray-600">Loading employee data...</p>
    </div>

    <!-- The form will be populated by JavaScript -->
    <form id="editEmployeeForm" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Hidden input to store employee_id -->
        <input type="hidden" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">

        <!-- Personal Info -->
        <div class="md:col-span-3">
            <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4">Personal Information</h3>
        </div>
        <div>
            <label for="firstName" class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" id="firstName" name="firstName" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="lastName" class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" id="lastName" name="lastName" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" id="email" name="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <!-- Job Info -->
        <div class="md:col-span-3">
            <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4 mt-4">Employment Details</h3>
        </div>
        <div>
            <label for="jobTitle" class="block text-sm font-medium text-gray-700">Job Title</label>
            <input type="text" id="jobTitle" name="jobTitle" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="hiredDate" class="block text-sm font-medium text-gray-700">Hired Date</label>
            <input type="date" id="hiredDate" name="hiredDate" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700">Department (Hold Ctrl/Cmd to select multiple)</label>
            <select id="department" name="department[]" multiple required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm h-32">
                <option value="">Loading...</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Supervisors/Managers can be assigned to multiple departments.</p>
        </div>
        
        <!-- NEW: Flexible Schedule Checkbox -->
        <div class="flex items-center pt-6">
            <input id="isFlexible" name="isFlexible" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="isFlexible" class="ml-2 block text-sm text-gray-900">
                Flexible Schedule (No Lates)
            </label>
        </div>

        <div class="md:col-span-1">
            <!-- Spacer column -->
        </div>

        <!-- System Access -->
        <div class="md:col-span-3">
            <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4 mt-4">System Access</h3>
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <?php foreach (APP_ROLES as $role): ?>
                    <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($role); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Employment Status</label>
            <select id="status" name="status" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="Active">Active</option>
                <option value="Terminated">Terminated</option>
                <option value="Resigned">Resigned</option>
                <option value="Contract Ended">Contract Ended</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <!-- Spacer column -->
        </div>

        <!-- *** REMOVED: Leave Balances Section *** -->


        <div class="md:col-span-3 flex justify-between items-center mt-6">
            <a href="employee_management.php" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                &larr; Back to Employee List
            </a>
            <button type="submit" id="submitButton" class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                Update Employee
            </button>
        </div>
    </form>
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
</div>

<!-- Page-Specific JavaScript -->
<script>
    const editEmployeeForm = document.getElementById('editEmployeeForm');
    const formMessage = document.getElementById('form-message');
    const employeeId = document.getElementById('employee_id').value;
    const departmentSelect = document.getElementById('department');
    // *** REMOVED: leaveContainer ***
    const loadingSpinner = document.getElementById('loading-spinner');
    const submitButton = document.getElementById('submitButton');

    // 1. Fetch departments
    async function fetchDepartments() {
        try {
            const response = await fetch('api/get_departments.php');
            const result = await response.json();
            if (result.success) {
                departmentSelect.innerHTML = ''; // Clear loading message
                result.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    departmentSelect.appendChild(option);
                });
            } else {
                departmentSelect.innerHTML = '<option value="">Could not load</option>';
            }
        } catch (error) {
            console.error('Error fetching departments:', error);
            departmentSelect.innerHTML = '<option value="">Network error</option>';
        }
    }

    // 2. Fetch employee data on page load
    async function fetchEmployeeData() {
        try {
            const response = await fetch(`api/get_employee_details.php?id=${employeeId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const result = await response.json();

            if (result.success) {
                const data = result.data;

                // Populate form fields
                document.getElementById('editEmployeeForm').reset(); // Clear first
                document.getElementById('firstName').value = data.first_name || '';
                document.getElementById('lastName').value = data.last_name || '';
                document.getElementById('email').value = data.email || '';
                document.getElementById('jobTitle').value = data.job_title || '';
                document.getElementById('hiredDate').value = data.hired_date || '';
                document.getElementById('role').value = data.role || 'Employee';
                document.getElementById('status').value = data.status || 'Active';
                
                // NEW: Populate Flexible Schedule
                if (parseInt(data.is_flexible_schedule) === 1) {
                    document.getElementById('isFlexible').checked = true;
                } else {
                    document.getElementById('isFlexible').checked = false;
                }

                // Handle Department Multi-Select
                if (data.department) {
                    const assignedDepts = data.department.split(',').map(d => d.trim());
                    Array.from(departmentSelect.options).forEach(option => {
                        if (assignedDepts.includes(option.value)) {
                            option.selected = true;
                        }
                    });
                }
                
                // Show form, hide spinner
                loadingSpinner.classList.add('hidden');
                editEmployeeForm.classList.remove('hidden');

            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
                loadingSpinner.innerHTML = `<p class="text-red-700">${result.message}</p>`;
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            loadingSpinner.innerHTML = '<p class="text-red-700">Error loading employee data.</p>';
        }
    }

    // 3. Handle form submission
    editEmployeeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;
        submitButton.textContent = 'Updating...';

        const formData = new FormData(editEmployeeForm);
        
        // Collect multi-select values manually
        const selectedDepartments = Array.from(departmentSelect.selectedOptions).map(option => option.value);
        const isFlexible = document.getElementById('isFlexible').checked;

        // *** MODIFIED: Simplified data object ***
        const data = {
            employee_id: formData.get('employee_id'),
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            jobTitle: formData.get('jobTitle'),
            hiredDate: formData.get('hiredDate'),
            department: selectedDepartments, // Send as array
            role: formData.get('role'),
            status: formData.get('status'),
            isFlexible: isFlexible // NEW
        };

        // *** REMOVED: Manually collect leave balances ***

        try {
            const response = await fetch('api/update_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    // Optionally redirect
                    // window.location.href = 'employee_management.php';
                }, 1500);
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An unexpected network error occurred. Please try again.', 'bg-red-100 text-red-700');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Update Employee';
        }
    });

    function showMessage(message, className) {
        formMessage.textContent = message;
        formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        formMessage.classList.remove('hidden');
    }

    // --- Init ---
    // Run fetches in parallel
    Promise.all([
        fetchDepartments(),
        fetchEmployeeData()
    ]);

</script>

<?php
// Include the footer
include 'template/footer.php';
?>

