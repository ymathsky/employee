<?php
// FILENAME: employee/journal_management.php
$pageTitle = 'Performance Journal Management';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
// Only Admins and Managers can view this page.
$user_role = $_SESSION['role'] ?? 'Employee';
if (!in_array($user_role, ['Manager', 'HR Admin', 'Super Admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Function to get all employees for the filter dropdown
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

<div class="bg-white p-8 rounded-xl shadow-xl max-w-7xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Performance Journal Management</h2>
    <p class="text-gray-600 mb-6">Review, filter, and manage all performance journal entries for all employees.</p>

    <!-- Filter Form -->
    <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg border">
        <div>
            <label for="filter_employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
            <select id="filter_employee_id" name="employee_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">-- All Employees --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['employee_id']; ?>">
                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_entry_type" class="block text-sm font-medium text-gray-700">Entry Type</label>
            <select id="filter_entry_type" name="entry_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">-- All Types --</option>
                <option value="Positive">Positive</option>
                <option value="Coaching">Coaching</option>
                <option value="Warning">Warning</option>
            </select>
        </div>
        <div>
            <label for="filter_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" id="filter_start_date" name="start_date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="filter_end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="filter_end_date" name="end_date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div class="col-span-1 md:col-span-4 flex justify-end gap-3">
            <button type="button" id="clearFilters" class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Clear
            </button>
            <button type="submit" class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </div>
    </form>

    <!-- Form Message Area -->
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>

    <!-- Journal Entry List -->
    <div id="journal-list" class="space-y-6">
        <!-- Entries will be loaded here by JavaScript -->
        <div id="loading" class="text-center p-8">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
            <p class="text-gray-500 mt-3">Loading entries...</p>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 m-4">
        <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Journal Entry</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete this journal entry? This action cannot be undone.
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
            <button type="button" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                Delete
            </button>
            <button type="button" id="cancelDeleteBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                Cancel
            </button>
        </div>
    </div>
</div>


<script>
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const journalList = document.getElementById('journal-list');
    const loadingIndicator = document.getElementById('loading');
    const formMessage = document.getElementById('form-message');

    // Modal elements
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let entryToDeleteId = null;

    function showMessage(message, className, autoHide = true) {
        formMessage.textContent = message;
        formMessage.className = `mt-4 mb-4 p-3 rounded-lg text-center ${className}`;
        formMessage.classList.remove('hidden');

        if(autoHide) {
            setTimeout(() => {
                formMessage.classList.add('hidden');
            }, 3000);
        }
    }

    function getEntryColor(type) {
        switch (type) {
            case 'Positive': return 'bg-green-100 text-green-800 border-green-400';
            case 'Coaching': return 'bg-blue-100 text-blue-800 border-blue-400';
            case 'Warning': return 'bg-red-100 text-red-800 border-red-400';
            default: return 'bg-gray-100 text-gray-800 border-gray-400';
        }
    }

    function htmlEntities(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function nl2br(str) {
        return htmlEntities(str).replace(/(\r\n|\n\r|\r|\n)/g, '<br>');
    }

    async function fetchJournalEntries() {
        loadingIndicator.style.display = 'block';
        journalList.innerHTML = ''; // Clear current list

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch(`api/get_journal_entries.php?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                renderEntries(result.data);
            } else {
                journalList.innerHTML = `<div class="p-8 text-center bg-gray-50 rounded-lg">
                    <i class="fas fa-exclamation-circle text-4xl text-red-400 mb-3"></i>
                    <p class="text-red-600">${htmlEntities(result.message)}</p>
                </div>`;
            }
        } catch (error) {
            console.error('Error fetching journal entries:', error);
            journalList.innerHTML = `<div class="p-8 text-center bg-gray-50 rounded-lg">
                <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-3"></i>
                <p class="text-red-600">An error occurred while fetching data.</p>
            </div>`;
        } finally {
            loadingIndicator.style.display = 'none';
        }
    }

    function renderEntries(entries) {
        journalList.innerHTML = ''; // Clear list and loading
        if (entries.length === 0) {
            journalList.innerHTML = `<div class="p-8 text-center bg-gray-50 rounded-lg">
                <i class="fas fa-book-open text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-500">No journal entries found matching your criteria.</p>
            </div>`;
            return;
        }

        entries.forEach(entry => {
            const entryColor = getEntryColor(entry.entry_type);
            const entryHtml = `
                <div class="p-4 rounded-lg border-l-4 shadow-sm ${entryColor}" data-entry-id="${entry.journal_id}">
                    <div class="flex justify-between items-start mb-2">
                        <!-- Left Column: Employee & Date -->
                <div>
                <span class="font-bold text-lg block">
                ${htmlEntities(entry.subject_first_name + ' ' + entry.subject_last_name)}
                </span>
                <span class="text-xs font-medium opacity-80">
                Date: ${new Date(entry.entry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
    </span>
    </div>

    <!-- Middle Column: Type & Logger -->
    <div class="text-center">
        <span class="font-semibold block text-sm">
        ${htmlEntities(entry.entry_type)}
        </span>
        <span class="text-xs opacity-80 block">Logged By:</span>
    <span class_ "text-xs font-medium">
    ${htmlEntities(entry.logger_first_name + ' ' + entry.logger_last_name)}
    </span>
    </div>

    <!-- Right Column: Delete Button -->
    <button class="delete-btn text-red-500 hover:text-red-700" data-id="${entry.journal_id}" title="Delete this entry">
        <i class="fas fa-trash-alt"></i>
        </button>
        </div>

        <div class="text-sm text-gray-700 border-t border-current pt-2 mt-2">
        ${nl2br(entry.description)}
        </div>
        </div>
        `;
            journalList.insertAdjacentHTML('beforeend', entryHtml);
        });
    }

    // --- Event Listeners ---

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchJournalEntries();
    });

    clearFiltersBtn.addEventListener('click', () => {
        filterForm.reset();
        fetchJournalEntries();
    });

    // Event delegation for delete buttons
    journalList.addEventListener('click', (e) => {
        const deleteButton = e.target.closest('.delete-btn');
        if (deleteButton) {
            entryToDeleteId = deleteButton.dataset.id;
            deleteModal.classList.remove('hidden');
        }
    });

    // Modal close buttons
    cancelDeleteBtn.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
        entryToDeleteId = null;
    });

    confirmDeleteBtn.addEventListener('click', async () => {
        if (!entryToDeleteId) return;

        showMessage('Deleting entry...', 'bg-blue-100 text-blue-700', false);
        deleteModal.classList.add('hidden');

        try {
            const response = await fetch('api/delete_journal_entry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ journal_id: entryToDeleteId })
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'bg-green-100 text-green-700');
                // Remove the element from the DOM
                const entryElement = journalList.querySelector(`[data-entry-id="${entryToDeleteId}"]`);
                if (entryElement) {
                    entryElement.remove();
                }
                // Check if list is now empty
                if (journalList.children.length === 0) {
                    renderEntries([]);
                }
            } else {
                showMessage(result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error deleting entry:', error);
            showMessage('An unexpected error occurred while deleting.', 'bg-red-100 text-red-700');
        } finally {
            entryToDeleteId = null;
        }
    });

    // Initial load
    document.addEventListener('DOMContentLoaded', fetchJournalEntries);

</script>

<?php
include 'template/footer.php';
?>
