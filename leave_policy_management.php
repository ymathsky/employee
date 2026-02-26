<?php
// FILENAME: employee/leave_policy_management.php
$pageTitle = 'Leave Policy Management';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/app_config.php'; // Load constants

// --- Page-Specific Role Check ---
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

// Function to fetch all employees with their current leave policy/balance
function getEmployeeLeavePolicies($pdo) {
    try {
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, 
                       lb.vacation_days_accrued, lb.sick_days_accrued, lb.personal_days_accrued, lb.annual_days_accrued
                FROM employees e
                LEFT JOIN leave_balances lb ON e.employee_id = lb.employee_id
                ORDER BY e.last_name, e.first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching leave policies: " . $e->getMessage());
        return [];
    }
}

$employees = getEmployeeLeavePolicies($pdo);
$leaveTypes = ['vacation', 'sick', 'personal', 'annual'];
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Employee Leave Policy and Accrual</h2>
        <button onclick="autoCalculateLeave()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-calculator mr-2"></i>
            Auto-Calculate Annual Leave
        </button>
    </div>
    <p class="text-gray-600 mb-6">Set the **annual accrual policy in days** for each type of paid leave. Employees will see their balance (Accrued - Used) in their portal.</p>

    <div id="policy-form-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Vacation (Days)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Sick (Days)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Personal (Days)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Annual (Days)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody id="policy-body" class="bg-white divide-y divide-gray-200">
            <?php if (count($employees) > 0): ?>
                <?php foreach ($employees as $emp): ?>
                    <tr id="row-<?php echo $emp['employee_id']; ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </td>
                        <?php foreach ($leaveTypes as $type): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 policy-value" data-type="<?php echo $type; ?>">
                                <?php
                                $accrued_key = $type . '_days_accrued';
                                $days = $emp[$accrued_key] ?? constant('DEFAULT_' . strtoupper($type) . '_DAYS');
                                echo number_format($days, 1);
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openPolicyModal(<?php echo htmlspecialchars(json_encode($emp)); ?>)" class="text-indigo-600 hover:text-indigo-900 font-medium">Set Policy</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                        No employees found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Leave Policy Modal -->
<div id="policyModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="policyForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-umbrella-beach text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="policyModalTitle">
                                Set Leave Policy
                            </h3>
                            <p class="text-sm text-gray-500" id="policyEmployeeName"></p>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="policy_employee_id" name="employee_id">

                                <!-- Vacation Policy -->
                                <div>
                                    <label for="vacation_days" class="block text-sm font-medium text-gray-700">Annual Vacation Days</label>
                                    <input type="number" id="vacation_days" name="vacation_days" step="0.5" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <!-- Sick Policy -->
                                <div>
                                    <label for="sick_days" class="block text-sm font-medium text-gray-700">Annual Sick Days</label>
                                    <input type="number" id="sick_days" name="sick_days" step="0.5" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <!-- Personal Policy -->
                                <div>
                                    <label for="personal_days" class="block text-sm font-medium text-gray-700">Annual Personal Days</label>
                                    <input type="number" id="personal_days" name="personal_days" step="0.5" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <!-- Annual Policy -->
                                <div>
                                    <label for="annual_days" class="block text-sm font-medium text-gray-700">Annual Leave Days</label>
                                    <input type="number" id="annual_days" name="annual_days" step="0.5" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div id="policy-modal-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Policy
                    </button>
                    <button type="button" onclick="closePolicyModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Leave Policy Modal -->

<script>
    const policyModal = document.getElementById('policyModal');
    const policyForm = document.getElementById('policyForm');
    const policyFormMessage = document.getElementById('policy-form-message');
    const policyModalMessage = document.getElementById('policy-modal-message');

    const DEFAULT_VACATION = <?php echo DEFAULT_VACATION_DAYS; ?>;
    const DEFAULT_SICK = <?php echo DEFAULT_SICK_DAYS; ?>;
    const DEFAULT_PERSONAL = <?php echo DEFAULT_PERSONAL_DAYS; ?>;
    const DEFAULT_ANNUAL = <?php echo DEFAULT_ANNUAL_DAYS; ?>;

    // Utility function for messages
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');

        if (autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 3000);
        }
    }

    function openPolicyModal(employee) {
        policyModalMessage.classList.add('hidden');
        policyForm.reset();

        document.getElementById('policyModalTitle').textContent = `Set Leave Policy for ${employee.first_name} ${employee.last_name}`;
        document.getElementById('policyEmployeeName').textContent = `ID: ${employee.employee_id}`;
        document.getElementById('policy_employee_id').value = employee.employee_id;

        // Populate fields with current data or defaults
        document.getElementById('vacation_days').value = employee.vacation_days_accrued || DEFAULT_VACATION;
        document.getElementById('sick_days').value = employee.sick_days_accrued || DEFAULT_SICK;
        document.getElementById('personal_days').value = employee.personal_days_accrued || DEFAULT_PERSONAL;
        document.getElementById('annual_days').value = employee.annual_days_accrued || DEFAULT_ANNUAL;

        policyModal.classList.remove('hidden');
    }

    function closePolicyModal() {
        policyModal.classList.add('hidden');
    }

    // Handle Form Submission
    policyForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        showMessage(policyModalMessage, 'Saving policy...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(policyForm);
        const data = Object.fromEntries(formData.entries());

        // Ensure days are non-negative numbers
        data.vacation_days = parseFloat(data.vacation_days);
        data.sick_days = parseFloat(data.sick_days);
        data.personal_days = parseFloat(data.personal_days);
        data.annual_days = parseFloat(data.annual_days);

        try {
            const response = await fetch('api/update_leave_policy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showMessage(policyFormMessage, result.message, 'bg-green-100 text-green-700', true);

                // Update the table dynamically
                const row = document.getElementById(`row-${data.employee_id}`);
                if(row) {
                    row.querySelector('[data-type="vacation"]').textContent = data.vacation_days.toFixed(1);
                    row.querySelector('[data-type="sick"]').textContent = data.sick_days.toFixed(1);
                    row.querySelector('[data-type="personal"]').textContent = data.personal_days.toFixed(1);
                    row.querySelector('[data-type="annual"]').textContent = data.annual_days.toFixed(1);
                }

                setTimeout(closePolicyModal, 500);

            } else {
                showMessage(policyModalMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(policyModalMessage, 'An error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // Auto Calculate Function
    async function autoCalculateLeave() {
        if (!confirm('This will recalculate Annual Leave for ALL active employees based on their Hired Date. Existing manual overrides for Annual Leave might be overwritten. Continue?')) {
            return;
        }

        showMessage(policyFormMessage, 'Calculating leave entitlements...', 'bg-blue-100 text-blue-700', false);

        try {
            const response = await fetch('api/auto_calculate_leave.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                showMessage(policyFormMessage, result.message, 'bg-green-100 text-green-700', true);
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showMessage(policyFormMessage, result.message, 'bg-red-100 text-red-700', false);
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(policyFormMessage, 'Network error during calculation.', 'bg-red-100 text-red-700', false);
        }
    }

</script>

<?php
include 'template/footer.php';
?>
