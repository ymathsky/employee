<?php
// FILENAME: employee/edit_employee.php

// Set the page title before including the header
$pageTitle = 'Edit Employee';
include 'template/header.php';

// Get Employee ID from URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: No employee ID provided.</div>";
    include 'template/footer.php';
    exit;
}
?>

<!-- Edit Employee Form -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Edit Employee Details</h2>
    <!-- The form will be populated by JavaScript -->
    <form id="editEmployeeForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Hidden input to store employee_id -->
        <input type="hidden" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">

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
        <div>
            <label for="jobTitle" class="block text-sm font-medium text-gray-700">Job Title</label>
            <input type="text" id="jobTitle" name="jobTitle" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
            <input type="text" id="department" name="department" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="salaryRate" class="block text-sm font-medium text-gray-700">Salary Rate</label>
            <input type="number" id="salaryRate" name="salaryRate" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select id="role" name="role" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="Employee">Employee</option>
                <option value="Manager">Manager</option>
                <option value="HR Admin">HR Admin</option>
                <option value="Super Admin">Super Admin</option>
            </select>
        </div>

        <div class="md:col-span-3 flex justify-between items-center">
            <a href="employee_management.php" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                &larr; Back to Employee List
            </a>
            <button type="submit" class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
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

    // 1. Fetch employee data on page load
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const response = await fetch(`api/get_employee_details.php?id=${employeeId}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                // Populate the form fields
                document.getElementById('firstName').value = data.first_name;
                document.getElementById('lastName').value = data.last_name;
                document.getElementById('email').value = data.email;
                document.getElementById('jobTitle').value = data.job_title;
                document.getElementById('department').value = data.department;
                document.getElementById('salaryRate').value = data.salary_rate;
                document.getElementById('role').value = data.role;
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            showMessage('Could not load employee data.', 'bg-red-100 text-red-700');
        }
    });

    // 2. Handle form submission
    editEmployeeForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(editEmployeeForm);
        const data = Object.fromEntries(formData.entries());

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
                // Redirect back to the employee list after 1 second
                setTimeout(() => {
                    window.location.href = 'employee_management.php';
                }, 1000);
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    function showMessage(message, className) {
        if (formMessage) {
            formMessage.textContent = message;
            formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
            formMessage.classList.remove('hidden');
        }
    }
</script>

<?php
// Include the footer
include 'template/footer.php';
?>
