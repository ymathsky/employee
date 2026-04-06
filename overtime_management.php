<?php
// FILENAME: employee/overtime_management.php
$pageTitle = 'Overtime Management';
include 'template/header.php'; 

// Only Admins can access
if ($_SESSION['role'] !== 'HR Admin' && $_SESSION['role'] !== 'Super Admin') {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_overtime') {
        $emp_id = $_POST['employee_id'];
        $date = $_POST['ot_date'];
        $hours = $_POST['hours'];
        $multiplier = isset($_POST['multiplier']) ? floatval($_POST['multiplier']) : 1.25;
        // Clamp multiplier to a sensible range
        $multiplier = max(1.0, min(5.0, $multiplier));
        $reason = $_POST['reason'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO overtime_requests (employee_id, ot_date, hours, multiplier, reason, status) VALUES (?, ?, ?, ?, ?, 'Approved')");
            $stmt->execute([$emp_id, $date, $hours, $multiplier, $reason]);
            $message = '<div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">Overtime allocated successfully.</div>';
        } catch (PDOException $e) {
            $message = '<div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">Error: ' . $e->getMessage() . '</div>';
        }
    }
    elseif ($_POST['action'] === 'delete_overtime') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM overtime_requests WHERE id = ?");
            $stmt->execute([$id]);
            $message = '<div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">Overtime record deleted.</div>';
        } catch (PDOException $e) {
            $message = '<div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">Error: ' . $e->getMessage() . '</div>';
        }
    }
    elseif ($_POST['action'] === 'edit_overtime') {
        $id         = intval($_POST['id']);
        $emp_id     = intval($_POST['employee_id']);
        $date       = $_POST['ot_date'];
        $hours      = floatval($_POST['hours']);
        $multiplier = max(1.0, min(5.0, floatval($_POST['multiplier'])));
        $reason     = $_POST['reason'];
        try {
            $stmt = $pdo->prepare("UPDATE overtime_requests SET employee_id=?, ot_date=?, hours=?, multiplier=?, reason=? WHERE id=?");
            $stmt->execute([$emp_id, $date, $hours, $multiplier, $reason, $id]);
            $message = '<div class="p-4 mb-4 text-green-700 bg-green-100 rounded-lg">Overtime record updated.</div>';
        } catch (PDOException $e) {
            $message = '<div class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Fetch Employees
$employees = $pdo->query("SELECT employee_id, first_name, last_name FROM employees ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Overtime
$recent_ot = $pdo->query("SELECT o.*, e.first_name, e.last_name FROM overtime_requests o JOIN employees e ON o.employee_id = e.employee_id ORDER BY o.ot_date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Overtime Management</h1>
        <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800">Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <!-- Add Overtime Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-semibold mb-4">Log Approved Overtime</h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="action" value="add_overtime">
            
            <div class="col-span-1 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <select name="employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="ot_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Hours</label>
                <input type="number" name="hours" step="0.5" min="0.5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">OT Rate</label>
                <select name="multiplier" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="1.0">1.00x &mdash; Regular OT Rate</option>
                    <option value="1.25" selected>1.25x &mdash; Regular OT</option>
                    <option value="1.5">1.50x &mdash; Rest Day OT</option>
                    <option value="2.0">2.00x &mdash; Special Holiday</option>
                    <option value="2.6">2.60x &mdash; Regular Holiday</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Add Log</button>
            </div>
            
            <div class="col-span-1 md:col-span-5">
                <label class="block text-sm font-medium text-gray-700">Reason / Notes</label>
                <input type="text" name="reason" placeholder="Project deadline, extra shift, etc." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OT Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recent_ot as $ot): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($ot['first_name'] . ' ' . $ot['last_name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('M j, Y', strtotime($ot['ot_date'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                        <?= number_format($ot['hours'], 2) ?> hrs
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?php
                            $m = (float)$ot['multiplier'];
                            $labels = ['1.00' => 'Regular OT Rate', '1.25' => 'Regular OT', '1.50' => 'Rest Day OT', '2.00' => 'Special Holiday', '2.60' => 'Regular Holiday'];
                            $label = $labels[number_format($m, 2)] ?? 'Custom';
                            $color = $m >= 2.0 ? 'text-red-600' : ($m >= 1.5 ? 'text-orange-600' : ($m >= 1.25 ? 'text-indigo-600' : 'text-gray-600'));
                        ?>
                        <span class="font-semibold <?= $color ?>"><?= number_format($m, 2) ?>x</span>
                        <span class="text-gray-400 ml-1"><?= $label ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= htmlspecialchars($ot['reason'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 space-x-3">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($ot)) ?>)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this record?');">
                            <input type="hidden" name="action" value="delete_overtime">
                            <input type="hidden" name="id" value="<?= $ot['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_ot)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No overtime records found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Edit Overtime Record</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit_overtime">
            <input type="hidden" name="id" id="edit_id">

            <div>
                <label class="block text-sm font-medium text-gray-700">Employee</label>
                <select name="employee_id" id="edit_employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="ot_date" id="edit_ot_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hours</label>
                    <input type="number" name="hours" id="edit_hours" step="0.5" min="0.5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">OT Rate</label>
                <select name="multiplier" id="edit_multiplier" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="1.0">1.00x &mdash; Regular OT Rate</option>
                    <option value="1.25">1.25x &mdash; Regular OT</option>
                    <option value="1.5">1.50x &mdash; Rest Day OT</option>
                    <option value="2.0">2.00x &mdash; Special Holiday</option>
                    <option value="2.6">2.60x &mdash; Regular Holiday</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Reason / Notes</label>
                <input type="text" name="reason" id="edit_reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(ot) {
    document.getElementById('edit_id').value          = ot.id;
    document.getElementById('edit_employee_id').value = ot.employee_id;
    document.getElementById('edit_ot_date').value     = ot.ot_date;
    document.getElementById('edit_hours').value       = ot.hours;
    document.getElementById('edit_reason').value      = ot.reason || '';

    // Match multiplier to nearest option
    const sel = document.getElementById('edit_multiplier');
    const m   = parseFloat(ot.multiplier).toFixed(2);
    const match = [...sel.options].find(o => parseFloat(o.value).toFixed(2) === m);
    sel.value = match ? match.value : '1.25';

    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
// Close on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php include 'template/footer.php'; ?>