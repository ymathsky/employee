<?php
// FILENAME: employee/my_payslips.php
$pageTitle = 'My Payslips';
include 'template/header.php'; // Handles session, auth, DB

// --- NEW: Get currency symbol and user role from session ---
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
$user_role = $_SESSION['role'] ?? 'Employee';
$is_admin = ($user_role === 'HR Admin' || $user_role === 'Super Admin');
$hide_pay_rate = isset($_SESSION['settings']['hide_pay_rate_from_employee']) && $_SESSION['settings']['hide_pay_rate_from_employee'] == '1';
// --- END NEW ---


// Get Employee ID from the user's session for JavaScript use
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Not logged in.</div>";
    include 'template/footer.php';
    exit;
}

// Dynamically set the page title based on role
$pageDisplayTitle = $is_admin ? 'All Payslip History' : 'My Payslip History';
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $pageDisplayTitle; ?></h2>

    <div id="payslip-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <!-- NEW COLUMN: Employee Name (visible only to Admins) -->
                <?php if ($is_admin): ?>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Employee
                    </th>
                <?php endif; ?>
                <!-- END NEW COLUMN -->
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Pay Period
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Gross Pay
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Allowances
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deductions
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Net Pay
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody id="payslip-body" class="bg-white divide-y divide-gray-200">
            <!-- Content loaded by JavaScript -->
            <tr>
                <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center p-6 text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading payslips...
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const payslipBody = document.getElementById('payslip-body');
    const payslipMessage = document.getElementById('payslip-message');
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;
    const currentEmployeeId = <?php echo json_encode($employee_id); ?>;
    const isAdmin = <?php echo json_encode($is_admin); ?>;
    const hidePayRate = <?php echo json_encode($hide_pay_rate); ?>;

    function showMessage(message, className) {

        payslipMessage.textContent = message;
        payslipMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        payslipMessage.classList.remove('hidden');
    }

    async function loadPayslips() {
        if (!currentEmployeeId) {
            showMessage('Error: Employee ID not found in session.', 'bg-red-100 text-red-700');
            return;
        }

        try {
            // API call remains the same as it now handles admin role internally
            const response = await fetch('api/get_payslips.php');
            const result = await response.json();

            payslipBody.innerHTML = ''; // Clear loading row
            const colspanValue = isAdmin ? 8 : 7;

            if (result.success && result.data.length > 0) {
                result.data.forEach(slip => {
                    const statusClass = slip.status === 'Processed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                    const employeeNameCell = isAdmin ? 
                        `<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                            ${slip.first_name} ${slip.last_name}
                            <span class="text-xs text-gray-400 block">(ID: ${slip.employee_id})</span>
                        </td>` : '';
                    
                    // Determine display values based on hidePayRate setting (only for non-admins)
                    let grossDisplay = `${currencySymbol}${parseFloat(slip.gross_pay).toFixed(2)}`;
                    let allowDisplay = `${currencySymbol}${parseFloat(slip.allowances || 0).toFixed(2)}`;
                    let deducDisplay = `${currencySymbol}${parseFloat(slip.deductions).toFixed(2)}`;
                    let netDisplay = `${currencySymbol}${parseFloat(slip.net_pay).toFixed(2)}`;
                    
                    if (hidePayRate && !isAdmin) {
                        grossDisplay = '<span class="text-gray-400 italic">Hidden</span>';
                        allowDisplay = '<span class="text-gray-400 italic">Hidden</span>';
                        deducDisplay = '<span class="text-gray-400 italic">Hidden</span>';
                        netDisplay = '<span class="text-gray-400 italic">Hidden</span>';
                    }

                    const row = `
                        <tr>
                            ${employeeNameCell}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${slip.pay_period_start} to ${slip.pay_period_end}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                    ${slip.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${grossDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">${allowDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-500">${deducDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">${netDisplay}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="view_payslip.php?id=${slip.payroll_id}" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a> | 
                                <a href="api/download_payslip.php?id=${slip.payroll_id}" target="_blank" class="text-gray-600 hover:text-gray-900 font-medium">PDF</a>
                            </td>
                        </tr>
                    `;
                    
                    payslipBody.insertAdjacentHTML('beforeend', row);
                });
            } else if (result.success) {
                payslipBody.innerHTML = `<tr><td colspan="${colspanValue}" class="text-center p-6 text-gray-500">No payslips found yet.</td></tr>`;
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
                payslipBody.innerHTML = `<tr><td colspan="${colspanValue}" class="text-center p-6 text-red-500">Failed to load data. See error message above.</td></tr>`;
            }

        } catch (error) {
            console.error('Network Error:', error);
            showMessage('Network error fetching payslip data.', 'bg-red-100 text-red-700');
            const colspanValue = isAdmin ? 8 : 7;
            payslipBody.innerHTML = `<tr><td colspan="${colspanValue}" class="text-center p-6 text-red-500">Network error.</td></tr>`;
        }
    }

    document.addEventListener('DOMContentLoaded', loadPayslips);
</script>

<?php
include 'template/footer.php';
?>
