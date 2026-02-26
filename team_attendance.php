<?php
// FILENAME: employee/team_attendance.php (or team_management.php)
$pageTitle = 'Team Management';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php';

// --- Page-Specific Role Check ---
$user_role = $_SESSION['role'] ?? 'Employee';
if (!in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    header('Location: dashboard.php');
    exit;
}

$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
$manager_id = $_SESSION['user_id'];
$is_admin = ($user_role === 'HR Admin' || $user_role === 'Super Admin');

// Get the department managed by the current user (if manager)
$manager_department = '';
try {
    if (!$is_admin) {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id]);
        $manager_department = $stmt_dept->fetchColumn();
    }
} catch (PDOException $e) { /* ... */ }

// Function to fetch employees based on role/department
function getTeamEmployees($pdo, $department, $is_admin) {
    try {
        $sql = "SELECT e.*, u.role
                 FROM employees e
                 LEFT JOIN users u ON e.employee_id = u.employee_id";
        $params = [];
        if (!$is_admin && !empty($department)) {
            // Handle multi-department managers
            $manager_depts = explode(',', $department);
            $dept_clauses = [];
            foreach ($manager_depts as $dept) {
                $dept_clauses[] = "e.department LIKE ?";
                $params[] = '%' . trim($dept) . '%';
            }
            if (!empty($dept_clauses)) {
                $sql .= " WHERE (" . implode(' OR ', $dept_clauses) . ")";
            }
        }
        $sql .= " ORDER BY e.last_name, e.first_name";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $today = date('Y-m-d');
        foreach ($employees as &$emp) { // Attach current pay rate
            $pay_data = getEmployeePayRateOnDate($pdo, $emp['employee_id'], $today);
            $emp['pay_type'] = $pay_data['pay_type'] ?? 'N/A';
            $emp['pay_rate'] = $pay_data['pay_rate'] ?? 0.00;
        }
        return $employees;
    } catch (PDOException $e) { /* ... */ return []; }
}

$employees = getTeamEmployees($pdo, $manager_department, $is_admin);
$display_title = $is_admin ? 'All Employee Profiles' : "Team Employees in: " . htmlspecialchars($manager_department ?? 'N/A');
?>

<!-- Employee List Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold text-gray-800"><?php echo $display_title; ?></h2>
    </div>
    <?php if (!$is_admin && empty($manager_department)): ?>
        <!-- ... warning message ... -->
    <?php endif; ?>
    <div id="form-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Rate</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($employees) > 0): ?>
                <?php foreach ($employees as $employee): ?>
                    <?php if (!$is_admin && $employee['employee_id'] == $manager_id) continue; ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($employee['job_title']); ?></td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php
                            if ($employee['pay_rate'] > 0) {
                                echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($employee['pay_rate'], 2));
                                // MODIFIED: Updated Suffix logic
                                if ($employee['pay_type'] == 'Hourly') echo ' / hr';
                                else if ($employee['pay_type'] == 'Daily') echo ' / day';
                                else if ($employee['pay_type'] == 'Fix Rate') echo ' / period';
                            } else {
                                echo '<span class="text-red-500">Rate Not Set</span>';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="view_employee_profile.php?id=<?php echo $employee['employee_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">View Profile</a>
                            <button onclick="openSetRateModal(<?php echo htmlspecialchars(json_encode($employee, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)" class="text-green-600 hover:text-green-900">Set Rate</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($employees) === 1 && !$is_admin && $employees[0]['employee_id'] == $manager_id): ?>
                    <tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No team members found in your department.</td></tr>
                <?php endif; ?>
            <?php else: ?>
                <tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No employees found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pay Rate Modal -->
<div id="setRateModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="setRateForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-dollar-sign text-green-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="setRateModalTitle">Set New Pay Rate for Team Member</h3>
                            <p class="text-sm text-gray-500" id="setRateEmployeeName"></p>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="set_rate_employee_id" name="employee_id">
                                <div class="p-3 bg-yellow-50 rounded-lg text-sm text-yellow-800">Note: This new rate will be effective starting *today*.</div>
                                <div>
                                    <label for="set_pay_type" class="block text-sm font-medium text-gray-700">Pay Type</label>
                                    <select id="set_pay_type" name="pay_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                        <!-- MODIFIED: Updated options -->
                                        <option value="Hourly">Hourly</option>
                                        <option value="Daily">Daily</option>
                                        <option value="Fix Rate">Fix Rate</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="set_pay_rate" class="block text-sm font-medium text-gray-700">Pay Rate (<?php echo htmlspecialchars($currency_symbol); ?>)</label>
                                    <input type="number" id="set_pay_rate" name="pay_rate" step="0.01" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                </div>
                                <div id="set-rate-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">Save New Rate</button>
                    <button type="button" onclick="closeSetRateModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Pay Rate Modal -->

<script>
    const setRateModal = document.getElementById('setRateModal');
    const setRateForm = document.getElementById('setRateForm');
    const setRateMessage = document.getElementById('set-rate-form-message');
    const globalMessage = document.getElementById('form-message');

    // --- Utility Functions ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
        if (autoHide) { setTimeout(() => { messageBox.classList.add('hidden'); }, 3000); }
    }

    // --- Modal Control ---
    function openSetRateModal(employee) {
        setRateMessage.classList.add('hidden'); setRateForm.reset();
        document.getElementById('setRateEmployeeName').textContent = employee.first_name + ' ' + employee.last_name;
        document.getElementById('set_rate_employee_id').value = employee.employee_id;
        if (employee.pay_type !== 'N/A') { document.getElementById('set_pay_type').value = employee.pay_type; }
        if (employee.pay_rate > 0) { document.getElementById('set_pay_rate').value = parseFloat(employee.pay_rate).toFixed(2); }
        setRateModal.classList.remove('hidden');
    }
    function closeSetRateModal() { setRateModal.classList.add('hidden'); }

    // --- Form Submission: Set Rate ---
    setRateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(setRateForm);
        const data = Object.fromEntries(formData.entries());
        showMessage(setRateMessage, 'Saving rate...', 'bg-blue-100 text-blue-700', false);
        try {
            // Uses update_payroll.php which now handles history correctly
            const response = await fetch('api/update_payroll.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                showMessage(setRateMessage, result.message, 'bg-green-100 text-green-700');
                showMessage(globalMessage, result.message, 'bg-green-100 text-green-700'); // Show global message too
                setTimeout(() => { closeSetRateModal(); window.location.reload(); }, 1000);
            } else { showMessage(setRateMessage, result.message, 'bg-red-100 text-red-700'); }
        } catch (error) { console.error('Error:', error); showMessage(setRateMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700'); }
    });
</script>

<?php
include 'template/footer.php';
?>

