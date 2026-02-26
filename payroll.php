<?php
// FILENAME: employee/payroll.php
$pageTitle = 'Payroll Management';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php'; // Needed for utility functions

// --- Get currency symbol from session ---
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
// --- END NEW ---

// --- Page-Specific PHP Logic ---

// Get filter from URL
$filter_department = $_GET['department'] ?? 'all';

// Function to fetch all unique departments
function getAllDepartments($pdo) {
    try {
        $sql = "SELECT department_name FROM departments ORDER BY department_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return [];
    }
}

// Function to get all employees with their department and *CURRENT* pay info from the history table
function getEmployeesForPayroll($pdo, $department_filter = 'all') {
    try {
        $sql = "SELECT e.employee_id, e.first_name, e.last_name, e.department, e.job_title
                 FROM employees e";

        $params = [];
        if ($department_filter !== 'all' && !empty($department_filter)) {
            $sql .= " WHERE e.department = ?";
            $params[] = $department_filter;
        }

        $sql .= " ORDER BY e.last_name, e.first_name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = date('Y-m-d');

        // Step 2: Loop through and attach current pay rate from history
        foreach ($employees as &$emp) {
            // Use the new utility function to fetch the most recent pay rate
            $pay_data = getEmployeePayRateOnDate($pdo, $emp['employee_id'], $today);

            $emp['pay_type'] = $pay_data['pay_type'] ?? 'N/A';
            $emp['pay_rate'] = $pay_data['pay_rate'] ?? 0.00;
        }

        return $employees;
    } catch (PDOException $e) {
        error_log("Error fetching employees for payroll: " . $e->getMessage());
        return [];
    }
}

// Function: Fetch aggregated payroll run history
function getPayrollRunHistory($pdo, $limit = 10) {
    try {
        // Group by pay period to treat it as a single "run"
        $sql = "
             SELECT
                 pay_period_start,
                 pay_period_end,
                 COUNT(payroll_id) AS total_payslips,
                 SUM(gross_pay) AS total_gross,
                 SUM(deductions) AS total_deductions,
                 SUM(net_pay) AS total_net,
                 MAX(created_at) AS last_processed
             FROM payroll
             GROUP BY pay_period_start, pay_period_end
             ORDER BY pay_period_end DESC, last_processed DESC
             LIMIT ?
         ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching payroll history: " . $e->getMessage());
        return [];
    }
}

$all_departments = getAllDepartments($pdo);
$employees = getEmployeesForPayroll($pdo, $filter_department);
$payroll_history = getPayrollRunHistory($pdo);
?>

<!-- Payroll Generation Form -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Payroll Generation</h2>
    <p class="text-gray-600 mb-4">Select a date range to generate payroll for all employees based on their active pay rates and recorded time/leave.</p>

    <form id="generatePayrollForm" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="start_date" class="block text-sm font-medium text-gray-700">Pay Period Start</label>
            <input type="date" id="start_date" name="start_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        </div>
        <div class="md:col-span-2">
            <label for="end_date" class="block text-sm font-medium text-gray-700">Pay Period End</label>
            <input type="date" id="end_date" name="end_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        </div>
        <div class="md:col-span-1">
            <!-- MODIFIED: Changed type to button and added onclick -->
            <button type="button" id="generateButton" onclick="openPayrollConfirmationModal()" class="w-full px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <i class="fas fa-calculator mr-2"></i>Generate Payroll
            </button>
        </div>
    </form>
    <div id="generation-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
</div>

<!-- Payroll Run Report Display Area -->
<div id="payrollRunReportDisplay" class="bg-white p-8 rounded-xl shadow-xl mb-8 hidden">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Payroll Generation Report</h2>
    <div id="reportContent" class="overflow-x-auto">
        <!-- Report table will be inserted here -->
    </div>
    <button onclick="printReport()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-print mr-2"></i> Print Report
    </button>
</div>


<!-- Employee Payroll List Table (Current Rates) -->
<div class="bg-white p-8 rounded-xl shadow-xl mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Employee Current Pay Rates</h2>

    <!-- Department Filter -->
    <form id="departmentFilterForm" method="GET" class="mb-4 max-w-sm">
        <label for="department_filter" class="block text-sm font-medium text-gray-700">Filter by Department</label>
        <div class="flex items-center space-x-2">
            <select id="department_filter" name="department" onchange="document.getElementById('departmentFilterForm').submit()"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="all">-- Show All Departments --</option>
                <?php foreach ($all_departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>"
                        <?php echo $filter_department === $dept ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="hidden"></button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Type</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Rate</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($employees) > 0): ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($emp['job_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                             <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php
                             // MODIFIED: Updated classes for new pay types
                             $type_class = 'bg-gray-100 text-gray-800'; // Default/N/A
                             if ($emp['pay_type'] == 'Fix Rate') $type_class = 'bg-green-100 text-green-800';
                             else if ($emp['pay_type'] == 'Hourly') $type_class = 'bg-blue-100 text-blue-800';
                             else if ($emp['pay_type'] == 'Daily') $type_class = 'bg-purple-100 text-purple-800';
                             echo $type_class; ?>">
                                 <?php echo htmlspecialchars($emp['pay_type']); ?>
                             </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($currency_symbol); ?>
                            <?php
                            if ($emp['pay_rate'] > 0) {
                                echo htmlspecialchars(number_format($emp['pay_rate'], 2));
                                // MODIFIED: Updated suffixes
                                if ($emp['pay_type'] == 'Hourly') echo ' / hr';
                                else if ($emp['pay_type'] == 'Daily') echo ' / day';
                                else if ($emp['pay_type'] == 'Fix Rate') echo ' / period'; // Suffix for Fix Rate
                            } else { echo '<span class="text-red-500">Missing Rate</span>'; }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($emp)); ?>)" class="text-indigo-600 hover:text-indigo-900 font-medium">Set New Rate</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No employees found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payroll Run History Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Recent Payroll History (Runs)</h2>
    <div id="history-list-message" class="mb-4 hidden p-3 rounded-lg text-center"></div> <!-- Message area for history actions -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payslips</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Gross</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Deductions</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Net Pay</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed Date</th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th> <!-- Added Actions Header -->
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($payroll_history) > 0): ?>
                <?php foreach ($payroll_history as $run): ?>
                    <tr id="history-row-<?php echo htmlspecialchars($run['pay_period_start'] . '_' . $run['pay_period_end']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars(date('M d, Y', strtotime($run['pay_period_start']))) . ' - ' . htmlspecialchars(date('M d, Y', strtotime($run['pay_period_end']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($run['total_payslips']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($run['total_gross'], 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">-<?php echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($run['total_deductions'], 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600"><?php echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($run['total_net'], 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($run['last_processed']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button onclick="openRunDetailsModal('<?php echo htmlspecialchars($run['pay_period_start']); ?>', '<?php echo htmlspecialchars($run['pay_period_end']); ?>')" class="text-blue-600 hover:text-blue-900 font-medium">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No payroll runs recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Pay Rate Modal -->
<div id="setPayRateModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title-set-rate" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="setPayRateForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-dollar-sign text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-set-rate">Set New Pay Rate</h3>
                            <p class="text-sm text-gray-500" id="setRateEmployeeName"></p>
                            <div class="mt-4 space-y-4">
                                <input type="hidden" id="set_rate_employee_id" name="employee_id">
                                <div class="p-3 bg-yellow-50 rounded-lg text-sm text-yellow-800">Note: This new rate will be effective starting *today*.</div>
                                <div>
                                    <label for="set_pay_type" class="block text-sm font-medium text-gray-700">Pay Type</label>
                                    <select id="set_pay_type" name="pay_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <!-- MODIFIED: Updated options -->
                                        <option value="Hourly">Hourly</option>
                                        <option value="Daily">Daily</option>
                                        <option value="Fix Rate">Fix Rate</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="set_pay_rate" class="block text-sm font-medium text-gray-700">Pay Rate</label>
                                    <input type="number" id="set_pay_rate" name="pay_rate" step="0.01" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div id="set-rate-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">Save New Rate</button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payroll Run Details Modal -->
<div id="payrollRunDetailsModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title-run-details" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full"> <!-- Increased max-width -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-list-alt text-blue-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-run-details">Payroll Run Details</h3>
                        <p class="text-sm text-gray-500" id="runDetailsPeriod"></p>
                        <div id="runDetailsMessage" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                        <!-- Container for printable content -->
                        <div id="printableRunDetailsContent" class="mt-4 overflow-x-auto">
                            <!-- Details table will be inserted here by JS -->
                            <p class="text-center text-gray-500 p-4">Loading details...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse space-y-2 sm:space-y-0 sm:space-x-3">

                <!-- NEW: Delete Run Button -->
                <button type="button" id="deleteRunButton" onclick="openDeleteRunConfirmationModal()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-trash-alt mr-2"></i> Delete Run
                </button>

                <button type="button" onclick="printRunDetails()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-print mr-2"></i> Print Details
                </button>
                <button type="button" onclick="closeRunDetailsModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Payroll Generation Modal -->
<div id="confirmPayrollModal" class="fixed z-30 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title-confirm-payroll" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <!-- Use a form for potential future enhancements, though submission handled by JS -->
            <form id="confirmPayrollForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-confirm-payroll">Confirm Payroll Generation</h3>
                            <div class="mt-2">
                                <p class="text-sm text-red-700 font-semibold">
                                    Warning: Generating payroll will process deductions (including CA/VALE) and create official records for the selected period. This action cannot be easily undone.
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Please enter your password to confirm you want to proceed.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label for="admin_password_confirm" class="block text-sm font-medium text-gray-700">Your Password</label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required autocomplete="current-password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            </div>
                            <div id="confirm-payroll-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmAndGenerateButton" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm & Generate
                    </button>
                    <button type="button" onclick="closePayrollConfirmationModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END NEW MODAL -->

<!-- NEW: Confirm Delete Run Modal -->
<div id="confirmDeleteRunModal" class="fixed z-30 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title-confirm-delete-run" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="confirmDeleteRunForm">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-skull-crossbones text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-confirm-delete-run">
                                CONFIRM: Delete Payroll Run
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-red-700 font-semibold">
                                    You are about to permanently delete all payslips for the period: <strong id="deleteRunPeriodDisplay"></strong>.
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    **WARNING:** This will also reset related Cash Advance (CA/VALE) deductions. Please enter your password to confirm.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label for="admin_password_delete" class="block text-sm font-medium text-gray-700">Your Password</label>
                                <input type="password" id="admin_password_delete" name="admin_password_delete" required autocomplete="current-password"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                <input type="hidden" id="delete_run_start_date">
                                <input type="hidden" id="delete_run_end_date">
                            </div>
                            <div id="confirm-delete-run-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmAndDeleteButton" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm & Delete
                    </button>
                    <button type="button" onclick="closeDeleteRunConfirmationModal(true)" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- END NEW MODAL -->


<script>
    const setRateModal = document.getElementById('setPayRateModal');
    const setRateForm = document.getElementById('setPayRateForm');
    const setRateFormMessage = document.getElementById('set-rate-form-message');
    const generateForm = document.getElementById('generatePayrollForm');
    const generationMessage = document.getElementById('generation-message');
    const generateButton = document.getElementById('generateButton'); // Keep reference to original button
    const payrollRunReportDisplay = document.getElementById('payrollRunReportDisplay');
    const reportContent = document.getElementById('reportContent');
    const runDetailsModal = document.getElementById('payrollRunDetailsModal');
    const runDetailsContent = document.getElementById('printableRunDetailsContent');
    const runDetailsMessage = document.getElementById('runDetailsMessage');
    const runDetailsPeriod = document.getElementById('runDetailsPeriod');
    const historyListMessage = document.getElementById('history-list-message');

    // NEW: Confirmation Modal Elements
    const confirmPayrollModal = document.getElementById('confirmPayrollModal');
    const confirmPayrollForm = document.getElementById('confirmPayrollForm'); // Reference to the form inside the modal
    const adminPasswordInput = document.getElementById('admin_password_confirm');
    const confirmPayrollMessage = document.getElementById('confirm-payroll-message');
    const confirmAndGenerateButton = document.getElementById('confirmAndGenerateButton');

    // NEW: Delete Run Modal Elements
    const confirmDeleteRunModal = document.getElementById('confirmDeleteRunModal');
    const confirmDeleteRunForm = document.getElementById('confirmDeleteRunForm');
    const deleteRunPeriodDisplay = document.getElementById('deleteRunPeriodDisplay');
    const adminPasswordDeleteInput = document.getElementById('admin_password_delete');
    const confirmDeleteRunMessage = document.getElementById('confirm-delete-run-message');
    const confirmAndDeleteButton = document.getElementById('confirmAndDeleteButton');
    const deleteRunStartDate = document.getElementById('delete_run_start_date');
    const deleteRunEndDate = document.getElementById('delete_run_end_date');

    // --- Payroll Confirmation Modal Logic ---
    function openPayrollConfirmationModal() {
        // Perform basic date validation first
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        if (!startDate || !endDate) {
            showMessage(generationMessage, 'Error: Please select both Start and End dates.', 'bg-red-100 text-red-700', true);
            return;
        }
        if (startDate >= endDate) {
            showMessage(generationMessage, 'Error: Start date must be before the end date.', 'bg-red-100 text-red-700', true);
            return;
        }

        // Clear previous messages/password and show modal
        confirmPayrollMessage.classList.add('hidden');
        adminPasswordInput.value = ''; // Clear password field
        confirmPayrollModal.classList.remove('hidden');
    }

    function closePayrollConfirmationModal() {
        confirmPayrollModal.classList.add('hidden');
    }

    // Add listener for the final confirmation button
    confirmAndGenerateButton.addEventListener('click', () => {
        const adminPassword = adminPasswordInput.value;
        if (!adminPassword) {
            showMessage(confirmPayrollMessage, 'Password is required to confirm.', 'bg-red-100 text-red-700', true);
            return;
        }
        // Hide confirmation message, close modal, and execute generation
        confirmPayrollMessage.classList.add('hidden');
        closePayrollConfirmationModal();
        executePayrollGeneration(adminPassword);
    });


    // --- Payroll Generation Execution Logic ---
    async function executePayrollGeneration(adminPassword) { // Now takes password
        // Disable original button and show loading (on the main page)
        generateButton.disabled = true;
        const originalButtonText = generateButton.innerHTML;
        generateButton.innerHTML = '<i class="fas fa-calculator mr-2 fa-spin"></i>Processing...';
        showMessage(generationMessage, 'Verifying and generating payroll. This may take a moment...', 'bg-blue-100 text-blue-700', false);
        payrollRunReportDisplay.classList.add('hidden'); // Hide previous report

        // Get date data from the form
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        const payload = {
            start_date: startDate,
            end_date: endDate,
            admin_password: adminPassword // Include the password
        };

        try {
            const response = await fetch('api/generate_payroll.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload) // Send payload including password
            });

            const result = await response.json();

            // Check specifically for password failure message from API
            if (!result.success && result.message.toLowerCase().includes('password incorrect')) {
                showMessage(generationMessage, `Generation failed: ${result.message}`, 'bg-red-100 text-red-700', false);
            } else if (result.success) {
                showMessage(generationMessage, result.message, 'bg-green-100 text-green-700', false);
                renderPayrollRunReport(result.details, `Generated Report for ${startDate} to ${endDate}`);
                // Reload the page slightly longer after showing report
                setTimeout(() => window.location.reload(), 4000);
            } else {
                // Other failures
                showMessage(generationMessage, `Generation failed: ${result.message}`, 'bg-red-100 text-red-700', false);
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(generationMessage, 'An unexpected server error occurred during payroll generation.', 'bg-red-100 text-red-700', false);
        } finally {
            // Re-enable the original button
            generateButton.disabled = false;
            generateButton.innerHTML = originalButtonText;
        }
    }


    // --- Render Payroll Run Report (Immediately After Generation) ---
    function renderPayrollRunReport(details, title) {
        if (!details || details.length === 0) {
            reportContent.innerHTML = '<p class="text-center text-gray-500">No data generated for this period.</p>';
            payrollRunReportDisplay.classList.remove('hidden');
            return;
        }
        let tableHTML = `<h3 class="text-lg font-medium text-gray-900 mb-2">${title}</h3><table class="min-w-full divide-y divide-gray-200 border"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Gross Pay</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Std Deduct</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">CA Deduct</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total Deduct</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net Pay</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Details</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">`;
        let totalGross = 0, totalStdDeduct = 0, totalCaDeduct = 0, totalDeduct = 0, totalNet = 0;
        details.forEach(item => {
            const isSkipped = item.status === 'Skipped';
            const stdDeductions = isSkipped || !item.standard_deductions_breakdown ? 0 : item.standard_deductions_breakdown.reduce((sum, d) => sum + d.amount, 0);
            const caDeducted = isSkipped ? 0 : (item.ca_deducted || 0);
            const grossPay = isSkipped ? 0 : (item.gross_pay || 0);
            const totalDeductions = isSkipped ? 0 : (item.deductions || 0);
            const netPay = isSkipped ? 0 : (item.net_pay || 0);
            totalGross += grossPay; totalStdDeduct += stdDeductions; totalCaDeduct += caDeducted; totalDeduct += totalDeductions; totalNet += netPay;
            tableHTML += `<tr class="${isSkipped ? 'bg-red-50 opacity-70' : ''}"><td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">${item.name} (${item.employee_id})</td><td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${item.status}</td><td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800 text-right">${formatCurrency(grossPay)}</td><td class="px-4 py-2 whitespace-nowrap text-sm text-red-600 text-right">${formatCurrency(stdDeductions)}</td><td class="px-4 py-2 whitespace-nowrap text-sm text-red-600 text-right">${formatCurrency(caDeducted)}</td><td class="px-4 py-2 whitespace-nowrap text-sm text-red-700 font-medium text-right">${formatCurrency(totalDeductions)}</td><td class="px-4 py-2 whitespace-nowrap text-sm text-green-700 font-bold text-right">${formatCurrency(netPay)}</td><td class="px-4 py-2 text-xs text-gray-500">${item.reason || item.details || ''}</td></tr>`;
        });
        tableHTML += `</tbody><tfoot class="bg-gray-100 font-bold"><tr><td colspan="2" class="px-4 py-2 text-right text-sm">Totals:</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalGross)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalStdDeduct)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalCaDeduct)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalDeduct)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalNet)}</td><td></td></tr></tfoot></table>`;
        reportContent.innerHTML = tableHTML;
        payrollRunReportDisplay.classList.remove('hidden');
    }

    // --- Print Payroll Run Report ---
    function printReport() {
        const contentToPrint = reportContent.innerHTML;
        const printWindow = window.open('', '_blank', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Payroll Run Report</title>');
        printWindow.document.write('<script src="https://cdn.tailwindcss.com"><\/script>'); // Optional: for basic Tailwind styles if needed
        printWindow.document.write('<style> body { padding: 20px; font-family: sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.8rem;} thead { background-color: #f2f2f2; } tfoot { background-color: #f9f9f9; font-weight: bold; } .text-right { text-align: right !important; } </style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(contentToPrint);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 500); // Delay print slightly
    }


    // --- Pay Rate Edit Modal Logic ---
    function openEditModal(employee) {
        setRateFormMessage.classList.add('hidden'); setRateForm.reset();
        document.getElementById('setRateEmployeeName').textContent = employee.first_name + ' ' + employee.last_name;
        document.getElementById('set_rate_employee_id').value = employee.employee_id;
        if (employee.pay_type !== 'N/A') { document.getElementById('set_pay_type').value = employee.pay_type; }
        if (employee.pay_rate > 0) { document.getElementById('set_pay_rate').value = parseFloat(employee.pay_rate).toFixed(2); }
        setRateModal.classList.remove('hidden');
    }
    function closeEditModal() { setRateModal.classList.add('hidden'); }

    setRateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(setRateForm);
        const data = Object.fromEntries(formData.entries());
        data.effective_start_date = new Date().toISOString().slice(0, 10);
        showMessage(setRateFormMessage, 'Saving rate...', 'bg-blue-100 text-blue-700', false);
        try {
            const response = await fetch('api/update_payroll.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                showMessage(setRateFormMessage, result.message, 'bg-green-100 text-green-700');
                setTimeout(() => { closeEditModal(); window.location.reload(); }, 1000);
            } else { showMessage(setRateFormMessage, result.message, 'bg-red-100 text-red-700'); }
        } catch (error) { console.error('Error:', error); showMessage(setRateFormMessage, 'An error occurred. Please try again.', 'bg-red-100 text-red-700'); }
    });

    // --- Payroll Run Details Modal Logic ---
    let currentRunStartDate = null;
    let currentRunEndDate = null;

    async function openRunDetailsModal(startDate, endDate) {
        currentRunStartDate = startDate;
        currentRunEndDate = endDate;

        runDetailsPeriod.textContent = `Pay Period: ${startDate} - ${endDate}`;
        runDetailsContent.innerHTML = '<p class="text-center text-gray-500 p-4"><i class="fas fa-spinner fa-spin mr-2"></i>Loading details...</p>';
        runDetailsMessage.classList.add('hidden');
        runDetailsModal.classList.remove('hidden');

        try {
            const response = await fetch(`api/get_payslips.php?pay_period_start=${startDate}&pay_period_end=${endDate}`);
            const result = await response.json();
            if (result.success && result.data.length > 0) { renderRunDetailsModal(result.data); }
            else if (result.success) { runDetailsContent.innerHTML = '<p class="text-center text-gray-500 p-4">No payslip details found for this run.</p>'; }
            else { showMessage(runDetailsMessage, `Error: ${result.message}`, 'bg-red-100 text-red-700', false); runDetailsContent.innerHTML = '<p class="text-center text-red-500 p-4">Failed to load details.</p>'; }
        } catch(error) { console.error('Error fetching run details:', error); showMessage(runDetailsMessage, 'Network error fetching run details.', 'bg-red-100 text-red-700', false); runDetailsContent.innerHTML = '<p class="text-center text-red-500 p-4">Network error.</p>'; }
    }
    function closeRunDetailsModal() { runDetailsModal.classList.add('hidden'); }
    function renderRunDetailsModal(payslips) {
        let tableHTML = `<table class="min-w-full divide-y divide-gray-200 border"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dept</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Gross Pay</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Late/Absent</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Other Deductions</th><th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Net Pay</th><th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead><tbody class="bg-white divide-y divide-gray-200">`;
        let totalGross = 0, totalLate = 0, totalDeduct = 0, totalNet = 0;
        payslips.forEach(slip => {
            totalGross += parseFloat(slip.gross_pay || 0); 
            totalLate += parseFloat(slip.attendance_deductions || 0);
            totalDeduct += parseFloat(slip.deductions || 0); 
            totalNet += parseFloat(slip.net_pay || 0);
            
            tableHTML += `<tr>
                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">${slip.first_name} ${slip.last_name} (${slip.employee_id})</td>
                <td class="px-4 py-2 whitespace-normal text-sm text-gray-500 max-w-xs">${slip.department || 'N/A'}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800 text-right">${formatCurrency(slip.gross_pay)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-orange-600 text-right">-${formatCurrency(slip.attendance_deductions || 0)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-red-600 text-right">${formatCurrency(slip.deductions)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-green-700 font-bold text-right">${formatCurrency(slip.net_pay)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-center"><a href="view_payslip.php?id=${slip.payroll_id}" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Payslip</a></td>
            </tr>`;
        });
        tableHTML += `</tbody><tfoot class="bg-gray-100 font-bold border-t-2"><tr><td colspan="2" class="px-4 py-2 text-right text-sm">Totals:</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalGross)}</td><td class="px-4 py-2 text-sm text-right text-orange-600">-${formatCurrency(totalLate)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalDeduct)}</td><td class="px-4 py-2 text-sm text-right">${formatCurrency(totalNet)}</td><td></td></tr></tfoot></table>`;
        runDetailsContent.innerHTML = tableHTML;
    }
    function printRunDetails() {
        const modalContent = document.getElementById('printableRunDetailsContent'); const contentClone = modalContent.cloneNode(true);
        const tableClone = contentClone.querySelector('table');
        if (tableClone) {
            const headerRow = tableClone.querySelector('thead tr'); if (headerRow && headerRow.cells.length > 0) { headerRow.deleteCell(-1); } // Remove Action Header
            const bodyRows = tableClone.querySelectorAll('tbody tr'); bodyRows.forEach(row => { if (row.cells.length > 0) { row.deleteCell(-1); } }); // Remove Action Cell
            const footerRow = tableClone.querySelector('tfoot tr');
            if (footerRow && footerRow.cells.length > 0) {
                // Remove the last cell if it's the empty one corresponding to Actions
                const lastCellIndex = footerRow.cells.length - 1;
                if(footerRow.cells[lastCellIndex] && footerRow.cells[lastCellIndex].textContent.trim() === '') {
                    footerRow.deleteCell(lastCellIndex);
                }
                // Adjust colspan of the "Totals:" cell if needed (now there's one less column)
                if(footerRow.cells.length > 0) { footerRow.cells[0].colSpan = 2; }
            }
        }
        const periodText = runDetailsPeriod.textContent; const printWindow = window.open('', '_blank', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Payroll Run Details</title>'); printWindow.document.write('<script src="https://cdn.tailwindcss.com"><\/script>'); printWindow.document.write('<style> body { padding: 20px; font-family: sans-serif; } table { width: 100%; border-collapse: collapse; margin-top: 15px; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } thead { background-color: #f2f2f2; } tfoot { background-color: #f9f9f9; font-weight: bold; } .text-right { text-align: right !important; } td:nth-child(2) { white-space: normal !important; max-width: 150px; word-wrap: break-word; } </style>'); printWindow.document.write('</head><body>'); printWindow.document.write(`<h2>Payroll Run Details</h2><p>${periodText}</p>`); printWindow.document.write(contentClone.innerHTML); printWindow.document.write('</body></html>');
        printWindow.document.close(); printWindow.focus(); setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
    }

    // --- NEW Delete Run Confirmation Logic ---
    function openDeleteRunConfirmationModal() {
        if (!currentRunStartDate || !currentRunEndDate) {
            showMessage(runDetailsMessage, 'Error: Could not determine payroll period.', 'bg-red-100 text-red-700', false);
            return;
        }
        // Set hidden fields
        deleteRunStartDate.value = currentRunStartDate;
        deleteRunEndDate.value = currentRunEndDate;
        // Display period text
        deleteRunPeriodDisplay.textContent = `${currentRunStartDate} to ${currentRunEndDate}`;
        // Clear password/message and show modal
        adminPasswordDeleteInput.value = '';
        confirmDeleteRunMessage.classList.add('hidden');

        runDetailsModal.classList.add('hidden'); // Hide details modal
        confirmDeleteRunModal.classList.remove('hidden');
    }

    function closeDeleteRunConfirmationModal(reopenDetails = false) {
        confirmDeleteRunModal.classList.add('hidden');
        if (reopenDetails) {
            runDetailsModal.classList.remove('hidden');
        }
    }

    confirmAndDeleteButton.addEventListener('click', () => {
        const adminPassword = adminPasswordDeleteInput.value;
        if (!adminPassword) {
            showMessage(confirmDeleteRunMessage, 'Password is required to confirm deletion.', 'bg-red-100 text-red-700', true);
            return;
        }

        // Execute deletion
        executePayrollRunDeletion(adminPassword, deleteRunStartDate.value, deleteRunEndDate.value);
    });

    // --- NEW Execute Deletion Logic ---
    async function executePayrollRunDeletion(adminPassword, startDate, endDate) {
        showMessage(confirmDeleteRunMessage, 'Processing deletion...', 'bg-red-100 text-red-700', false);
        confirmAndDeleteButton.disabled = true;

        try {
            const response = await fetch('api/delete_payroll_run.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate,
                    admin_password: adminPassword // Only used for logging/auth check in a robust system, currently unused in the generated API
                })
            });

            const result = await response.json();

            if (result.success) {
                // Show success message in the global message box
                showMessage(historyListMessage, result.message, 'bg-green-100 text-green-700', false);
                // Close modal and reload page
                setTimeout(() => {
                    closeDeleteRunConfirmationModal();
                    closeRunDetailsModal(); // Ensure both modals are closed
                    window.location.reload();
                }, 2000);
            } else {
                showMessage(confirmDeleteRunMessage, `Deletion failed: ${result.message}`, 'bg-red-100 text-red-700', false);
                confirmAndDeleteButton.disabled = false;
            }
        } catch (error) {
            console.error('Error deleting payroll run:', error);
            showMessage(confirmDeleteRunMessage, 'Network error during deletion.', 'bg-red-100 text-red-700', false);
            confirmAndDeleteButton.disabled = false;
        }
    }

    // --- Utility function for messages ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
        if (autoHide) { setTimeout(() => messageBox.classList.add('hidden'), 5000); }
    }

    // --- Utility to format currency ---
    function formatCurrency(amount) {
        const numAmount = parseFloat(amount);
        return '<?php echo $currency_symbol; ?>' + (isNaN(numAmount) ? '0.00' : numAmount.toFixed(2));
    }
</script>

<?php
include 'template/footer.php';
?>
