<?php
// FILENAME: employee/manage_leave.php
$pageTitle = 'Manage Leave Requests';
include 'template/header.php'; // Handles session, auth, DB

// Only Admins and Managers can access this page (RBAC handled in header.php)
$is_admin = ($_SESSION['role'] === 'HR Admin' || $_SESSION['role'] === 'Super Admin');
$manager_id = $_SESSION['user_id'] ?? null;
$manager_department = '';

// Get manager's department if they are a manager
if ($_SESSION['role'] === 'Manager') {
    try {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id]);
        $manager_info = $stmt_dept->fetch();
        $manager_department = $manager_info['department'] ?? '';
    } catch (PDOException $e) {
        error_log("Error fetching manager department: " . $e->getMessage());
    }
}

// Function to fetch relevant leave requests
function getLeaveRequestsForReview($pdo, $is_admin, $manager_department) {
    try {
        $sql = "
            SELECT 
                lr.*, 
                e.first_name, 
                e.last_name,
                e.department
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.employee_id
            WHERE 1=1
        ";
        $params = [];

        // If not an admin, filter by the manager's department
        if (!$is_admin && !empty($manager_department)) {
            // Use LIKE for multi-department support
            $sql .= " AND e.department LIKE ?";
            $params[] = "%" . $manager_department . "%";
        }

        $sql .= " ORDER BY lr.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching leave requests for review: " . $e->getMessage());
        return [];
    }
}

$requests = getLeaveRequestsForReview($pdo, $is_admin, $manager_department);

// Fetch all employees for the "Add Leave" modal (Admin only)
$all_employees = [];
if ($is_admin) {
    try {
        $stmt_all = $pdo->query("SELECT employee_id, first_name, last_name, department FROM employees ORDER BY last_name, first_name");
        $all_employees = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
    }
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">
            <?php echo $is_admin ? 'All Leave Requests' : 'Team Leave Requests'; ?>
        </h2>
        <?php if ($is_admin): ?>
            <button onclick="openAddLeaveModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded inline-flex items-center transition duration-150 ease-in-out">
                <i class="fas fa-plus mr-2"></i> Add Leave
            </button>
        <?php endif; ?>
    </div>

    <?php if (!$is_admin && empty($manager_department)): ?>
        <div class="p-4 bg-red-100 text-red-700 rounded-lg mb-4">
            Warning: You are a Manager but are not currently assigned a department in the employee records. Showing no team leave requests.
        </div>
    <?php endif; ?>

    <div id="review-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee (Dept)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dates
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type / Reason
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($requests) > 0): ?>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>
                            <span class="text-xs text-gray-500 block">(<?php echo htmlspecialchars($req['department']); ?>)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars(date('M j, Y', strtotime($req['start_date']))) . ' to ' . htmlspecialchars(date('M j, Y', strtotime($req['end_date']))); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <span class="font-medium text-red-600"><?php echo htmlspecialchars($req['leave_type']); ?></span>
                            <span class="block text-xs italic max-w-xs truncate"><?php echo htmlspecialchars($req['reason']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            switch ($req['status']) {
                                case 'Approved':
                                    $status_class = 'bg-green-100 text-green-800';
                                    break;
                                case 'Rejected':
                                    $status_class = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    break;
                            }
                            ?>
                            <span id="status-<?php echo $req['request_id']; ?>" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <?php if ($req['status'] === 'Pending'): ?>
                                <button onclick="handleRequest(<?php echo $req['request_id']; ?>, 'Approved')" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                <button onclick="handleRequest(<?php echo $req['request_id']; ?>, 'Rejected')" class="text-red-600 hover:text-red-900 mr-3">Reject</button>
                            <?php else: ?>
                                <span class="text-gray-400 mr-3">Completed</span>
                            <?php endif; ?>
                            
                            <button onclick="deleteRequest(<?php echo $req['request_id']; ?>)" class="text-gray-400 hover:text-red-600" title="Delete Request">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No leave requests found for review.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const reviewFormMessage = document.getElementById('review-form-message');
    const managerId = <?php echo json_encode($manager_id); ?>;

    async function handleRequest(requestId, newStatus) {
        if (!confirm(`Are you sure you want to ${newStatus.toLowerCase()} this leave request?`)) {
            return;
        }

        showMessage('Processing...', 'bg-blue-100 text-blue-700', reviewFormMessage, false);

        try {
            const response = await fetch('api/review_leave_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    request_id: requestId,
                    status: newStatus,
                    manager_id: managerId
                })
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'bg-green-100 text-green-700', reviewFormMessage);
                // Simple DOM manipulation to update status without full reload
                updateStatusBadge(requestId, newStatus);
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700', reviewFormMessage);
            }
        } catch (error) {
            console.error('Error reviewing leave:', error);
            showMessage('An unexpected error occurred.', 'bg-red-100 text-red-700', reviewFormMessage);
        }
    }

    function updateStatusBadge(requestId, newStatus) {
        const badge = document.getElementById(`status-${requestId}`);
        if (!badge) return;

        let className = '';
        if (newStatus === 'Approved') {
            className = 'bg-green-100 text-green-800';
        } else if (newStatus === 'Rejected') {
            className = 'bg-red-100 text-red-800';
        } else {
            className = 'bg-yellow-100 text-yellow-800';
        }

        // Update the badge text and color
        badge.textContent = newStatus;
        badge.className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${className}`;

        // Disable action buttons in the same row
        const row = badge.closest('tr');
        if (row) {
            const actionCell = row.querySelector('td:last-child');
            if (actionCell) {
                actionCell.innerHTML = '<span class="text-gray-400">Completed</span>';
            }
        }
    }


    function showMessage(message, className, messageBox, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');

        if(autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 3000);
        }
    }

    async function deleteRequest(requestId) {
        if (!confirm('Are you sure you want to delete this leave request? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch('api/delete_leave_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId })
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error deleting request:', error);
            alert('An error occurred while deleting the request.');
        }
    }
</script>

<!-- Add Leave Modal (Admin Only) -->
<?php if ($is_admin): ?>
<div id="addLeaveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Add Leave for Employee</h3>
            <div class="mt-2 px-7 py-3">
                <form id="adminAddLeaveForm">
                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="employee_id">Employee</label>
                        <select id="employee_id" name="employee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($all_employees as $emp): ?>
                                <option value="<?php echo $emp['employee_id']; ?>">
                                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['department'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>

                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="leave_type">Leave Type</label>
                        <select id="leave_type" name="leave_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select Type</option>
                            <?php foreach (LEAVE_TYPES as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="reason">Reason</label>
                        <textarea id="reason" name="reason" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3" required></textarea>
                    </div>

                    <div class="mb-4 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Status</label>
                        <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="Approved" selected>Approved</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div class="items-center px-4 py-3">
                        <button id="submitAddLeave" type="submit" class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            Submit Leave
                        </button>
                    </div>
                </form>
                <div class="items-center px-4 py-3">
                    <button onclick="closeAddLeaveModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openAddLeaveModal() {
    document.getElementById('addLeaveModal').classList.remove('hidden');
}

function closeAddLeaveModal() {
    document.getElementById('addLeaveModal').classList.add('hidden');
    document.getElementById('adminAddLeaveForm').reset();
}

document.getElementById('adminAddLeaveForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    fetch('api/submit_leave_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Leave added successfully!');
            closeAddLeaveModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the request.');
    });
});
</script>
<?php endif; ?>

<?php
include 'template/footer.php';
?>
