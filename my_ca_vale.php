<?php
// FILENAME: employee/my_ca_vale.php
$pageTitle = 'My CA/VALE Transactions';
include 'template/header.php'; // Handles session, auth, DB

// Only logged-in employees (or managers/admins on their own behalf) can access this.
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header('Location: dashboard.php');
    exit;
}

$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';

// Function to fetch total pending CA/VALE
function getPendingTotal($pdo, $employee_id) {
    try {
        $sql = "SELECT SUM(pending_amount) FROM ca_transactions WHERE employee_id = ? AND deducted_in_payroll = FALSE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return floatval($stmt->fetchColumn() ?? 0.00);
    } catch (PDOException $e) {
        error_log("Error fetching pending CA total: " . $e->getMessage());
        return 0.00;
    }
}

// Function to fetch all CA/VALE transactions
function getAllTransactions($pdo, $employee_id) {
    try {
        $sql = "
            SELECT t.*, p.pay_period_start, p.pay_period_end 
            FROM ca_transactions t
            LEFT JOIN payroll p ON t.payroll_id = p.payroll_id
            WHERE t.employee_id = ? 
            ORDER BY t.transaction_date DESC, t.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching all CA transactions: " . $e->getMessage());
        return [];
    }
}

$pending_total = getPendingTotal($pdo, $employee_id);
$transactions = getAllTransactions($pdo, $employee_id);

?>

<div class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-receipt text-indigo-600 mr-3"></i>
        <span>My Cash Advance (CA/VALE) History</span>
    </h2>
    <p class="text-gray-600 mb-6">View your recorded cash advance transactions. Any pending amounts will be automatically deducted in your next payroll run.</p>

    <!-- Pending Balance Card -->
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-8 rounded-lg shadow-md flex justify-between items-center">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
            <span class="font-medium">Total Pending Advance to be deducted:</span>
        </div>
        <span class="text-3xl font-bold">
            <?php echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($pending_total, 2)); ?>
        </span>
    </div>

    <div id="message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

    <!-- Transaction History Table -->
    <h3 class="text-xl font-medium text-gray-700 mb-4">Transaction Details</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date Taken
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Amount
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deduction Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deducted In Payroll Period
                </th>
            </tr>
            </thead>
            <tbody id="transaction-body" class="bg-white divide-y divide-gray-200">
            <?php if (count($transactions) > 0): ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars(date('M d, Y', strtotime($t['transaction_date']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                            - <?php echo htmlspecialchars($currency_symbol) . htmlspecialchars(number_format($t['original_amount'], 2)); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = $t['deducted_in_payroll'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            $status_text = $t['deducted_in_payroll'] ? 'Deducted' : 'Pending';
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($t['deducted_in_payroll']): ?>
                                <?php echo htmlspecialchars(date('M d, Y', strtotime($t['pay_period_start']))) . ' to ' . htmlspecialchars(date('M d, Y', strtotime($t['pay_period_end']))); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No cash advance transactions recorded yet.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'template/footer.php';
?>
