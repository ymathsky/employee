<?php
// FILENAME: employee/team_attendance_logs.php
$pageTitle = 'Team Attendance Logs';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php';

// --- TIMEZONE FIX AND INITIALIZATION ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

// --- Page-Specific Role Check and Scope Determination ---
$user_role = $_SESSION['role'] ?? 'Employee';
$manager_id = $_SESSION['user_id'];
$is_admin = ($user_role === 'HR Admin' || $user_role === 'Super Admin');
$is_manager = ($user_role === 'Manager');

if (!$is_admin && !$is_manager) {
    header('Location: dashboard.php');
    exit;
}

$scope_department = '';
if ($is_manager) {
    try {
        $stmt_dept = $pdo->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $stmt_dept->execute([$manager_id]);
        $scope_department = $stmt_dept->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching manager department: " . $e->getMessage());
    }
}

// Function to fetch relevant attendance logs
function getTeamAttendanceLogs($pdo, $scope_department, $is_admin) {
    try {
        $sql = "SELECT a.*, e.first_name, e.last_name, e.department
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id";

        $params = [];
        if (!$is_admin && !empty($scope_department)) {
            // Filter logs by the manager's department(s)
            // Handle multi-department managers (e.g., "HR, IT")
            $manager_depts = explode(',', $scope_department);
            $dept_clauses = [];
            
            foreach ($manager_depts as $dept) {
                $dept_clauses[] = "e.department LIKE ?";
                $params[] = '%' . trim($dept) . '%';
            }
            
            if (!empty($dept_clauses)) {
                $sql .= " WHERE (" . implode(' OR ', $dept_clauses) . ")";
            }
        } elseif (!$is_admin && empty($scope_department)) {
            // Manager but no department assigned, show nothing
            return [];
        }

        $sql .= " ORDER BY a.log_date DESC, a.time_in DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching team attendance logs: " . $e->getMessage());
        return [];
    }
}

$logs = getTeamAttendanceLogs($pdo, $scope_department, $is_admin);
$display_title = $is_admin ? 'All Employee Attendance Logs' : "Attendance Logs for " . htmlspecialchars($scope_department ?? 'N/A');
?>

<!-- Attendance Log Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $display_title; ?></h2>

    <?php if ($is_manager && empty($scope_department)): ?>
        <div class="p-4 bg-red-100 text-red-700 rounded-lg mb-4">
            Warning: You are a Manager but are not currently assigned a department. No team logs are visible.
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time In (<?php echo htmlspecialchars($timezone); ?>)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time Out (<?php echo htmlspecialchars($timezone); ?>)
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total Hours
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Remarks
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                            <span class="text-xs text-gray-500 block">(<?php echo htmlspecialchars($log['department'] ?? 'N/A'); ?>)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div><?php echo htmlspecialchars(date('M d, Y', strtotime($log['log_date']))); ?></div>
                            <div class="text-xs text-gray-500 font-medium mt-1"><?php echo date('l', strtotime($log['log_date'])); ?></div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $log['time_in'] ? htmlspecialchars(date('h:i:s A', strtotime($log['time_in']))) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $log['time_out'] ? htmlspecialchars(date('h:i:s A', strtotime($log['time_out']))) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo calculateDuration($log['time_in'], $log['time_out']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($log['remarks'] ?? '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        <?php echo $is_admin ? 'No attendance logs found.' : 'No team attendance logs found for your department.'; ?>
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
