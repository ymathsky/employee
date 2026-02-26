<?php
// FILENAME: employee/my_journal.php
$pageTitle = 'My Performance Journal';
include 'template/header.php'; // Handles session, auth, DB

$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header('Location: dashboard.php');
    exit;
}

// Function to fetch journal entries for the current employee
function getMyJournalEntries($pdo, $employee_id) {
    try {
        $sql = "SELECT 
                    ej.*,
                    e_logger.first_name AS logger_first_name,
                    e_logger.last_name AS logger_last_name
                FROM employee_journal ej
                JOIN employees e_logger ON ej.logged_by_id = e_logger.employee_id
                WHERE ej.employee_id = ?
                ORDER BY ej.entry_date DESC, ej.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching journal entries: " . $e->getMessage());
        return [];
    }
}

$journal_entries = getMyJournalEntries($pdo, $employee_id);

function getEntryColor($type) {
    switch ($type) {
        case 'Positive': return 'bg-green-100 text-green-800 border-green-400';
        case 'Coaching': return 'bg-blue-100 text-blue-800 border-blue-400';
        case 'Warning': return 'bg-red-100 text-red-800 border-red-400';
        default: return 'bg-gray-100 text-gray-800 border-gray-400';
    }
}
?>

<div class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">My Performance Journal Entries</h2>
    <p class="text-gray-600 mb-6">This record includes notes from your managers regarding your performance, coaching moments, and recognition. This is a private record to support your growth.</p>

    <div class="space-y-6">
        <?php if (count($journal_entries) > 0): ?>
            <?php foreach ($journal_entries as $entry): ?>
                <div class="p-4 rounded-lg border-l-4 shadow-sm <?php echo getEntryColor($entry['entry_type']); ?>">
                    <div class="flex justify-between items-start mb-2">
                        <!-- Entry Date and Type -->
                        <div>
                            <span class="font-bold text-lg block">
                                <?php echo htmlspecialchars($entry['entry_type']); ?>
                            </span>
                            <span class="text-xs font-medium opacity-80">
                                <?php echo htmlspecialchars(date('M j, Y', strtotime($entry['entry_date']))); ?>
                            </span>
                        </div>

                        <!-- Logger Info -->
                        <div class="text-right text-sm">
                            <span class="text-gray-700 block">Logged By:</span>
                            <span class="font-semibold block">
                                <?php echo htmlspecialchars($entry['logger_first_name'] . ' ' . $entry['logger_last_name']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="text-sm text-gray-700 border-t border-current pt-2 mt-2">
                        <?php echo nl2br(htmlspecialchars($entry['description'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-8 text-center bg-gray-50 rounded-lg">
                <i class="fas fa-book-open text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-500">No journal entries have been logged for you yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'template/footer.php';
?>
