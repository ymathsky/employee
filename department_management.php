<?php
// FILENAME: employee/department_management.php
$pageTitle = 'Department Management';
include 'template/header.php'; // Handles session, auth, DB

// ... existing PHP code ...
function getDepartments($pdo) {
    try {
        // *** MODIFIED: Added d.department_id and d.manager_id ***
        $sql = "SELECT d.department_id, d.department_name, d.created_at, d.manager_id, e.first_name, e.last_name
                FROM departments d
                LEFT JOIN employees e ON d.manager_id = e.employee_id
                ORDER BY d.department_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return [];
    }
} // *** FIX: Added missing closing brace ***

function getPotentialManagers($pdo) {
    try {
        $sql = "SELECT employee_id, first_name, last_name, job_title 
                FROM employees 
                ORDER BY last_name, first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

$departments = getDepartments($pdo);
$employees = getPotentialManagers($pdo);
?>

<!-- Add Department Form -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Department</h2>

    <form id="addDepartmentForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="department_name" class="block text-sm font-medium text-gray-700">Department Name</label>
            <input type="text" id="department_name" name="department_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="manager_id" class="block text-sm font-medium text-gray-700">Assign Manager (Optional)</label>
            <select id="manager_id" name="manager_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">None</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?php echo $employee['employee_id']; ?>">
                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) . ' (' . htmlspecialchars($employee['job_title']) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                Save Department
            </button>
        </div>
    </form>
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
</div>

<!-- Department List Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Current Departments</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Department Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Assigned Manager
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date Created
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($departments) > 0): ?>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $dept['first_name'] ? htmlspecialchars($dept['first_name'] . ' ' . $dept['last_name']) : '<span class="text-gray-400">N/A</span>'; ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars(date('M d, Y', strtotime($dept['created_at']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <!-- *** MODIFIED: "Edit" is now a button with an onclick event *** -->
                            <button onclick="openEditModal(<?php echo $dept['department_id']; ?>)" class="text-indigo-600 hover:text-indigo-900 mr-4 font-medium">Edit</button>
                            <!-- *** MODIFIED: "Delete" is now a button with an onclick event *** -->
                            <button onclick="openDeleteModal(<?php echo $dept['department_id']; ?>, '<?php echo htmlspecialchars(addslashes($dept['department_name'])); ?>')" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No departments found. Add one above.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- *** NEW: Edit Department Modal *** -->
<div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="editDepartmentForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-building text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Edit Department
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Hidden input for department_id -->
                                <input type="hidden" id="edit_department_id" name="department_id">

                                <div>
                                    <label for="edit_department_name" class="block text-sm font-medium text-gray-700">Department Name</label>
                                    <input type="text" id="edit_department_name" name="department_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="edit_manager_id" class="block text-sm font-medium text-gray-700">Assign Manager</label>
                                    <select id="edit_manager_id" name="manager_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">None</option>
                                        <!-- Options will be populated by JS, but we can also pre-populate -->
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['employee_id']; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- *** End Edit Modal *** -->

<!-- *** NEW: Delete Confirmation Modal *** -->
<div id="deleteModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="delete-modal-title">
                            Delete Department
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete the "<strong id="deleteDeptName"></strong>" department?
                                All employees in this department will be unassigned. This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button id="confirmDeleteBtn" type="button" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </button>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
<!-- *** End Delete Modal *** -->


<script>
    const addDeptForm = document.getElementById('addDepartmentForm');
    const formMessage = document.getElementById('form-message');

    // --- Add Department Form Logic ---
    addDeptForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addDeptForm);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch('api/add_department.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showMessage(formMessage, result.message, 'bg-green-100 text-green-700');
                addDeptForm.reset();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showMessage(formMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(formMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    // --- NEW: Edit Modal Logic ---
    const editModal = document.getElementById('editModal');
    const editDeptForm = document.getElementById('editDepartmentForm');
    const editFormMessage = document.getElementById('edit-form-message');

    async function openEditModal(departmentId) {
        // Clear previous messages
        editFormMessage.classList.add('hidden');
        editDeptForm.reset();

        // Fetch current department details
        try {
            const response = await fetch(`api/get_department_details.php?id=${departmentId}`);
            const result = await response.json();

            if (result.success) {
                const dept = result.data;
                // Populate the modal form
                document.getElementById('edit_department_id').value = dept.department_id;
                document.getElementById('edit_department_name').value = dept.department_name;
                // Set the manager, handling the "None" (null) case
                document.getElementById('edit_manager_id').value = dept.manager_id || "";

                // Show the modal
                editModal.classList.remove('hidden');
            } else {
                showMessage(formMessage, `Error: ${result.message}`, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error fetching details:', error);
            showMessage(formMessage, 'Could not fetch department details.', 'bg-red-100 text-red-700');
        }
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
    }

    // Handle Edit Form Submission
    editDeptForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editDeptForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/update_department.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showMessage(editFormMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => {
                    closeEditModal();
                    window.location.reload();
                }, 1000);
            } else {
                showMessage(editFormMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(editFormMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700');
        }
    });

    // --- NEW: Delete Modal Logic ---
    const deleteModal = document.getElementById('deleteModal');
    const deleteDeptName = document.getElementById('deleteDeptName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let deptIdToDelete = null;

    function openDeleteModal(departmentId, departmentName) {
        deptIdToDelete = departmentId;
        deleteDeptName.textContent = departmentName;
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        deptIdToDelete = null;
        deleteModal.classList.add('hidden');
    }

    confirmDeleteBtn.addEventListener('click', async () => {
        if (!deptIdToDelete) return;

        try {
            const response = await fetch('api/delete_department.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ department_id: deptIdToDelete })
            });

            const result = await response.json();

            if (result.success) {
                showMessage(formMessage, result.message, 'bg-green-100 text-green-700');
                closeDeleteModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                // Show error message inside the delete modal
                showMessage(document.getElementById('edit-form-message'), result.message, 'bg-red-100 text-red-700'); // Re-use message box
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(document.getElementById('edit-form-message'), 'An error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // --- Utility Functions ---
    function showMessage(messageBox, message, className) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
    }
</script>

<?php
include 'template/footer.php';
?>

