<?php
// FILENAME: employee/admin_attendance_requests.php
$pageTitle = 'Attendance Adjustment Requests';
include 'template/header.php'; 

// Check permissions
$user_role = $_SESSION['role'] ?? '';
if (!in_array($user_role, ['Super Admin', 'HR Admin', 'Manager'])) {
    echo "<div class='p-6 text-red-600'>You do not have permission to view this page.</div>";
    include 'template/footer.php';
    exit;
}

// Fetch all requests
try {
    $stmt = $pdo->prepare("
        SELECT r.*, e.first_name, e.last_name, e.department, e.profile_picture_url 
        FROM attendance_adjustment_requests r
        JOIN employees e ON r.employee_id = e.employee_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
    $requests = [];
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Pending Adjustment Requests</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="requestsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In / Out</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): 
                        $pic = !empty($req['profile_picture_url']) ? $req['profile_picture_url'] : 'https://placehold.co/40x40/667eea/ffffff?text='.substr($req['first_name'],0,1);
                        $statusColor = match($req['status']) {
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            'Approved' => 'bg-green-100 text-green-800',
                            'Rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($pic); ?>" alt="">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($req['department']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($req['log_date']); ?>
                            <div class="text-xs text-gray-500">Requested: <?php echo date('M d, H:i', strtotime($req['created_at'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="text-green-600">In: <?php echo date('H:i', strtotime($req['time_in'])); ?></div>
                            <div class="text-red-600">Out: <?php echo date('H:i', strtotime($req['time_out'])); ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($req['reason']); ?>">
                            <?php echo htmlspecialchars($req['reason']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <?php if ($req['status'] === 'Pending'): ?>
                                <button onclick="reviewRequest(<?php echo $req['request_id']; ?>, 'Approved')" class="text-green-600 hover:text-green-900 mr-3" title="Approve">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </button>
                                <button onclick="reviewRequest(<?php echo $req['request_id']; ?>, 'Rejected')" class="text-red-600 hover:text-red-900" title="Reject">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    async function reviewRequest(id, action) {
        if (!confirm(`Are you sure you want to mark this request as ${action}?`)) return;

        try {
            const res = await fetch('api/review_attendance_adjustment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ request_id: id, status: action })
            });
            const result = await res.json();
            
            if (result.success) {
                // Determine row color based on action for immediate feedback or reload
                alert(`Request ${action} successfully.`);
                location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (error) {
            console.error(error);
            alert("Network error.");
        }
    }
</script>

<?php include 'template/footer.php'; ?>
