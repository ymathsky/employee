<?php
// FILENAME: employee/my_leave.php
$pageTitle = 'My Leave Requests';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/app_config.php';

$employee_id = $_SESSION['user_id'] ?? null;

// Function to fetch employee's leave requests
function getMyLeaveRequests($pdo, $employee_id) {
    try {
        $sql = "SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY start_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching leave requests: " . $e->getMessage());
        return [];
    }
}

$requests = getMyLeaveRequests($pdo, $employee_id);
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Request New Time Off</h2>

    <!-- NEW: Leave Balances Display -->
    <div class="border p-4 rounded-lg bg-gray-50 mb-8">
        <h3 class="text-xl font-medium text-gray-700 mb-3">Your Current Leave Balances (Days)</h3>
        <div id="balance-display" class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="p-3 border rounded-lg bg-indigo-50">
                <span class="text-xs font-medium text-indigo-700 block">Vacation</span>
                <span id="vacation-balance" class="text-xl font-bold text-indigo-900"><i class="fas fa-spinner fa-spin"></i></span>
            </div>
            <div class="p-3 border rounded-lg bg-indigo-50">
                <span class="text-xs font-medium text-indigo-700 block">Sick Leave</span>
                <span id="sick-balance" class="text-xl font-bold text-indigo-900"><i class="fas fa-spinner fa-spin"></i></span>
            </div>
            <div class="p-3 border rounded-lg bg-indigo-50">
                <span class="text-xs font-medium text-indigo-700 block">Personal Day</span>
                <span id="personal-balance" class="text-xl font-bold text-indigo-900"><i class="fas fa-spinner fa-spin"></i></span>
            </div>
            <div class="p-3 border rounded-lg bg-indigo-50">
                <span class="text-xs font-medium text-indigo-700 block">Annual Leave</span>
                <span id="annual-balance" class="text-xl font-bold text-indigo-900"><i class="fas fa-spinner fa-spin"></i></span>
            </div>
            <!-- Placeholder for other types or legend -->
            <div class="p-3 border rounded-lg bg-gray-200">
                <span class="text-xs font-medium text-gray-700 block">Total Used</span>
                <span id="total-used" class="text-xl font-bold text-gray-900">0.0</span>
            </div>
        </div>
        <div id="balance-message" class="mt-3 text-sm text-center text-gray-600 hidden"></div>
    </div>
    <!-- END NEW: Leave Balances Display -->

    <form id="leaveRequestForm" class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-8 border-b border-gray-200 mb-8">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" id="start_date" name="start_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="end_date" name="end_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
        </div>
        <div class="md:col-span-2">
            <label for="leave_type" class="block text-sm font-medium text-gray-700">Type of Leave</label>
            <select id="leave_type" name="leave_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                <option value="" disabled selected>Select leave type</option>
                <?php foreach (LEAVE_TYPES as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
            <textarea id="reason" name="reason" rows="3" required placeholder="Briefly describe your need for time off." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
        </div>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                Submit Request
            </button>
        </div>
    </form>

    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Request History</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dates
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Reason
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Submitted
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($req['start_date']))) . ' - ' . htmlspecialchars(date('M j, Y', strtotime($req['end_date']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($req['leave_type']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            <?php echo htmlspecialchars($req['reason']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            switch ($req['status']) {
                                case 'Approved': $status_class = 'bg-green-100 text-green-800'; break;
                                case 'Rejected': $status_class = 'bg-red-100 text-red-800'; break;
                                default: $status_class = 'bg-yellow-100 text-yellow-800'; break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($req['created_at']))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No leave requests found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const form = document.getElementById('leaveRequestForm');
    const formMessage = document.getElementById('form-message');
    const balanceMessage = document.getElementById('balance-message');

    // --- Utility Functions ---
    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');

        if(autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 3000);
        }
    }

    // --- Leave Balance Fetcher ---
    async function fetchLeaveBalances() {
        try {
            const response = await fetch('api/get_leave_balances.php');
            const result = await response.json();

            if (result.success) {
                const balances = result.data;

                let totalUsed = 0;

                document.getElementById('vacation-balance').textContent = balances['Vacation'].available.toFixed(1);
                document.getElementById('sick-balance').textContent = balances['Sick Leave'].available.toFixed(1);
                document.getElementById('personal-balance').textContent = balances['Personal Day'].available.toFixed(1);
                document.getElementById('annual-balance').textContent = balances['Annual Leave'].available.toFixed(1);

                // Calculate total used for display
                totalUsed += balances['Vacation'].used;
                totalUsed += balances['Sick Leave'].used;
                totalUsed += balances['Personal Day'].used;
                totalUsed += balances['Annual Leave'].used;

                document.getElementById('total-used').textContent = totalUsed.toFixed(1);

            } else {
                balanceMessage.textContent = `Error: ${result.message}`;
                balanceMessage.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error fetching balances:', error);
            balanceMessage.textContent = 'Network error. Could not retrieve balances.';
            balanceMessage.classList.remove('hidden');
        }
    }

    // --- Form Submission ---
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage(formMessage, 'Submitting request...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/submit_leave_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(formMessage, result.message, 'bg-green-100 text-green-700');
                form.reset();

                // Immediately update balances and history after success
                setTimeout(() => {
                    fetchLeaveBalances();
                    window.location.reload();
                }, 1500);
            } else {
                showMessage(formMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error submitting leave:', error);
            showMessage(formMessage, 'An unexpected error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // Initial load
    document.addEventListener('DOMContentLoaded', fetchLeaveBalances);
</script>

<?php
include 'template/footer.php';
?>
