<?php
// FILENAME: employee/log_journal.php
$pageTitle = 'Log Performance Journal Entry';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
// Only Admins and Managers can log entries.
$user_role = $_SESSION['role'] ?? 'Employee';
if (!in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Function to get all employees
function getAllEmployees($pdo) {
    try {
        $sql = "SELECT employee_id, first_name, last_name, department 
                FROM employees 
                ORDER BY last_name, first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}
$employees = getAllEmployees($pdo);
?>

<div class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Log Performance Entry</h2>
    <p class="text-gray-600 mb-6">Document important coaching moments, positive feedback, or performance warnings for an employee.</p>

    <form id="journalForm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Employee Select -->
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700">Select Employee</label>
                <select id="employee_id" name="employee_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="" disabled selected>-- Choose employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . ' (' . htmlspecialchars($emp['department']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Entry Date -->
            <div>
                <label for="entry_date" class="block text-sm font-medium text-gray-700">Date of Incident/Feedback</label>
                <input type="date" id="entry_date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Entry Type -->
            <div class="md:col-span-2">
                <label for="entry_type" class="block text-sm font-medium text-gray-700">Entry Type</label>
                <select id="entry_type" name="entry_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="Positive">Positive Feedback / Recognition</option>
                    <option value="Coaching">Coaching / Development Note</option>
                    <option value="Warning">Performance Warning / Disciplinary</option>
                </select>
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Detailed Description</label>
                <textarea id="description" name="description" rows="5" required placeholder="Describe the situation, the feedback given, and the expected outcome or next steps." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end mt-8 border-t pt-6">
            <button type="submit" class="w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                Submit Journal Entry
            </button>
        </div>
    </form>
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
</div>

<script>
    const journalForm = document.getElementById('journalForm');
    const formMessage = document.getElementById('form-message');

    function showMessage(message, className, autoHide = true) {
        formMessage.textContent = message;
        formMessage.className = `mt-4 p-3 rounded-lg text-center ${className}`;
        formMessage.classList.remove('hidden');

        if(autoHide) {
            setTimeout(() => {
                formMessage.classList.add('hidden');
            }, 3000);
        }
    }

    journalForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        showMessage('Submitting entry...', 'bg-blue-100 text-blue-700', false);

        const formData = new FormData(journalForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/log_journal_entry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'bg-green-100 text-green-700');
                journalForm.reset();
                // Ensure the date field defaults back to today
                document.getElementById('entry_date').value = new Date().toISOString().slice(0, 10);
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error submitting journal entry:', error);
            showMessage('An unexpected error occurred.', 'bg-red-100 text-red-700');
        }
    });

</script>

<?php
include 'template/footer.php';
?>
