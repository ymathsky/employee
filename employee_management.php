<?php
// FILENAME: employee/employee_management.php

// Set the page title before including the header
$pageTitle = 'Employee Management';

// Include the header, which handles session, auth, and DB connection
include 'template/header.php';

// --- Page-Specific PHP Logic ---

// Function to fetch all employees from the database
function getEmployees($pdo) {
    try {
        // JOIN with users table to get the role
        $sql = "SELECT e.*, u.role 
                FROM employees e
                LEFT JOIN users u ON e.employee_id = u.employee_id 
                ORDER BY e.created_at DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

$employees = getEmployees($pdo);
?>

<!-- Employee List Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800">Current Employees</h2>
        <a href="add_employee_page.php" class="px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i>Add New Employee
        </a>
    </div>

    <div id="form-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee ID
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Job Title
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Department
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Role
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($employees) > 0): ?>
                <?php foreach ($employees as $employee): ?>
                    <tr id="employee-row-<?php echo $employee['employee_id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($employee['employee_id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($employee['job_title']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($employee['department']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($employee['role'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm">
                            <?php 
                            $status = $employee['status'] ?? 'Active';
                            $statusColor = '';
                            switch ($status) {
                                case 'Active':
                                    $statusColor = 'text-green-600 bg-green-100';
                                    break;
                                case 'Terminated':
                                    $statusColor = 'text-red-600 bg-red-100';
                                    break;
                                case 'Resigned':
                                    $statusColor = 'text-orange-600 bg-orange-100';
                                    break;
                                case 'Contract Ended':
                                    $statusColor = 'text-gray-600 bg-gray-100';
                                    break;
                                default:
                                    $statusColor = 'text-gray-600 bg-gray-100';
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium flex space-x-3">
                            <a href="view_employee_profile.php?id=<?php echo $employee['employee_id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="edit_employee_page.php?id=<?php echo $employee['employee_id']; ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                            <!-- NEW: Reset Password Button -->
                            <button onclick="openResetPasswordModal(<?php echo $employee['employee_id']; ?>, '<?php echo htmlspecialchars(addslashes($employee['first_name'] . ' ' . $employee['last_name'])); ?>')"
                                    class="text-yellow-600 hover:text-yellow-900">
                                Reset Pass
                            </button>

                            <button onclick="deleteEmployee('<?php echo $employee['employee_id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No employees found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- NEW: Reset Password Modal -->
<div id="resetPasswordModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="resetPasswordForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-key text-yellow-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="resetModalTitle">
                                Reset Password for Employee
                            </h3>
                            <p class="text-sm text-gray-500" id="resetEmployeeName"></p>
                            <div class="mt-4 space-y-4">
                                <!-- Hidden input for employee_id -->
                                <input type="hidden" id="reset_employee_id" name="employee_id">

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
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm Reset
                    </button>
                    <button type="button" onclick="closeResetPasswordModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END NEW MODAL -->

<!-- Page-Specific JavaScript -->
<script>
    const formMessage = document.getElementById('form-message');
    const resetModal = document.getElementById('resetPasswordModal');
    const resetForm = document.getElementById('resetPasswordForm');
    const resetFormMessage = document.getElementById('reset-form-message');

    // --- Show Global Message (for delete) ---
    function showMessage(messageBox, message, className) {
        messageBox.textContent = message;
        messageBox.className = `mb-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
    }

    // --- Reset Password Modal Control ---
    function openResetPasswordModal(employeeId, employeeName) {
        resetForm.reset();
        resetFormMessage.classList.add('hidden');

        document.getElementById('resetModalTitle').textContent = `Reset Password for ${employeeName}`;
        document.getElementById('resetEmployeeName').textContent = `Employee ID: ${employeeId}`;
        document.getElementById('reset_employee_id').value = employeeId;

        resetModal.classList.remove('hidden');
    }

    function closeResetPasswordModal() {
        resetModal.classList.add('hidden');
    }

    // --- Reset Password Form Submission ---
    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_new_password').value;
        const employeeId = document.getElementById('reset_employee_id').value;

        if (newPassword !== confirmPassword) {
            showMessage(resetFormMessage, 'Error: Passwords do not match.', 'bg-red-100 text-red-700');
            return;
        }

        if (newPassword.length < 8) {
            showMessage(resetFormMessage, 'Error: Password must be at least 8 characters long.', 'bg-red-100 text-red-700');
            return;
        }

        showMessage(resetFormMessage, 'Resetting password...', 'bg-blue-100 text-blue-700');

        try {
            const response = await fetch('api/reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    new_password: newPassword
                })
            });

            const result = await response.json();

            if (result.success) {
                showMessage(resetFormMessage, result.message, 'bg-green-100 text-green-700');
                // Use global message box for success visibility after modal closes
                showMessage(formMessage, result.message, 'bg-green-100 text-green-700');

                setTimeout(closeResetPasswordModal, 1500);
            } else {
                showMessage(resetFormMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(resetFormMessage, 'An unexpected network error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // --- Delete Employee Function ---
    function deleteEmployee(employeeId) {
        // This function uses the native browser confirm for simplicity in this context
        const userConfirmed = confirm('Are you sure you want to delete this employee?');

        if (userConfirmed) {
            fetch('api/delete_employee.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: employeeId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(formMessage, 'Employee deleted successfully.', 'bg-green-100 text-green-700');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showMessage(formMessage, 'Error: ' + data.message, 'bg-red-100 text-red-700');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage(formMessage, 'An error occurred while trying to delete the employee.', 'bg-red-100 text-red-700');
                });
        }
    }
</script>

<?php
// Include the footer
include 'template/footer.php';
?>
