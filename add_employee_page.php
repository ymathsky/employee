<?php
// FILENAME: employee/add_employee_page.php

// Set the page title before including the header
$pageTitle = 'Add New Employee';
include 'template/header.php';

// Only allow Admins
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Access Denied. You do not have permission to view this page.</div>";
    include 'template/footer.php';
    exit;
}

// Get roles from config
require_once __DIR__ . '/config/app_config.php';

// --- NEW: Generate Default Password ---
$companyName = $_SESSION['settings']['company_name'] ?? 'Company';
// Remove spaces for a cleaner default password (e.g., "Tech Corp" -> "TechCorp@2025")
$defaultPassword = str_replace(' ', '', trim($companyName)) . '@2025';
?>

<!-- Add Employee Form -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Add New Employee</h2>
        <a href="employee_management.php" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
            &larr; Back to Employee List
        </a>
    </div>

    <form id="addEmployeeForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
            <label for="department" class="block text-sm font-medium text-gray-700">Department(s)</label>
            <select id="department" name="department" multiple class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm h-32">
                <option value="">Loading departments...</option>
                <!-- Departments will be loaded by JS -->
            </select>
            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</p>
        </div>
        
        <!-- NEW: Flexible Schedule Checkbox -->
        <div class="flex items-center pt-6">
            <input id="isFlexible" name="isFlexible" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="isFlexible" class="ml-2 block text-sm text-gray-900">
                Flexible Schedule (No Lates)
            </label>
        </div>

        <!-- System & Security -->
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
            <label for="password" class="block text-sm font-medium text-gray-700">Password (min 8 chars)</label>
            <input type="password" id="password" name="password" required minlength="8" value="<?php echo htmlspecialchars($defaultPassword); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required minlength="8" value="<?php echo htmlspecialchars($defaultPassword); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            
            <!-- Show Password Toggle -->
            <div class="flex items-center mt-2">
                <input type="checkbox" id="showPasswordToggle" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="showPasswordToggle" class="ml-2 block text-sm text-gray-900">Show Password</label>
            </div>
        </div>


        <div class="md:col-span-3 text-right">
            <button type="submit" id="submitButton" class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                Create Employee
            </button>
        </div>
    </form>
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
</div>

<script>
    const addEmployeeForm = document.getElementById('addEmployeeForm');
    const formMessage = document.getElementById('form-message');
    const departmentSelect = document.getElementById('department');
    const submitButton = document.getElementById('submitButton');
    const jobTitleInput = document.getElementById('jobTitle');
    const showPasswordToggle = document.getElementById('showPasswordToggle');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Toggle Password Visibility
    showPasswordToggle.addEventListener('change', function() {
        const type = this.checked ? 'text' : 'password';
        passwordInput.type = type;
        confirmPasswordInput.type = type;
    });

    // Job Title Listener
    jobTitleInput.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        const hint = document.querySelector('label[for="department"] + select + p');
        if (val.includes('supervisor')) {
            hint.innerHTML = '<span class="text-indigo-600 font-bold">Supervisor Role Detected:</span> You can select multiple departments by holding Ctrl/Cmd.';
            departmentSelect.classList.add('ring-2', 'ring-indigo-500');
        } else {
            hint.textContent = 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple.';
            departmentSelect.classList.remove('ring-2', 'ring-indigo-500');
        }
    });

    // 1. Fetch departments on page load
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const response = await fetch('api/get_departments.php');
            const result = await response.json();

            if (result.success) {
                departmentSelect.innerHTML = ''; // Clear loading
                result.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    departmentSelect.appendChild(option);
                });
            } else {
                departmentSelect.innerHTML = '<option value="">Could not load departments</option>';
            }
        } catch (error) {
            console.error('Error fetching departments:', error);
            departmentSelect.innerHTML = '<option value="">Network error</option>';
        }
    });

    // 2. Handle form submission
    addEmployeeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;
        submitButton.textContent = 'Creating...';

        const formData = new FormData(addEmployeeForm);
        
        // Handle Multi-Select Department
        const selectedOptions = Array.from(departmentSelect.selectedOptions).map(opt => opt.value);
        if (selectedOptions.length === 0 || (selectedOptions.length === 1 && selectedOptions[0] === "")) {
             showMessage('Please select at least one department.', 'bg-red-100 text-red-700');
             submitButton.disabled = false;
             submitButton.textContent = 'Create Employee';
             return;
        }
        
        // Check Flexible Schedule
        const isFlexible = document.getElementById('isFlexible').checked;

        // Create data object
        const data = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            jobTitle: formData.get('jobTitle'),
            hiredDate: formData.get('hiredDate'),
            department: selectedOptions, // Send as array
            role: formData.get('role'),
            password: formData.get('password'),
            confirmPassword: formData.get('confirmPassword'),
            isFlexible: isFlexible // New Field
        };

        if (data.password !== data.confirmPassword) {
            showMessage('Passwords do not match.', 'bg-red-100 text-red-700');
            submitButton.disabled = false;
            submitButton.textContent = 'Create Employee';
            return;
        }

        try {
            const response = await fetch('api/add_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'bg-green-100 text-green-700');
                addEmployeeForm.reset();
                setTimeout(() => window.location.href = 'employee_management.php', 1500);
            } else {
                showMessage('Error: ' + result.message, 'bg-red-100 text-red-700');
                submitButton.disabled = false;
                submitButton.textContent = 'Create Employee';
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An unexpected error occurred.', 'bg-red-100 text-red-700');
            submitButton.disabled = false;
            submitButton.textContent = 'Create Employee';
        }
    });

    function showMessage(message, className) {
        formMessage.innerHTML = message;
        formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        formMessage.classList.remove('hidden');
    }
</script>
                // Redirect back to the main list after 2 seconds
                setTimeout(() => {
                    window.location.href = 'employee_management.php';
                }, 2000);
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An unexpected network error occurred. Please try again.', 'bg-red-100 text-red-700');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Create Employee';
        }
    });

    function showMessage(message, className) {
        formMessage.textContent = message;
        formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        formMessage.classList.remove('hidden');
    }
</script>

<?php
// Include the footer
include 'template/footer.php';
?>
