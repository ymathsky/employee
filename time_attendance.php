<?php
// FILENAME: employee/time_attendance.php
$pageTitle = 'Time & Attendance';
include 'template/header.php'; // Handles session, auth, DB
require_once __DIR__ . '/config/utils.php'; // NEW: Include utility functions

// --- TIMEZONE FIX AND INITIALIZATION ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

// --- NEW: PERMISSION CHECK FOR MANUAL EDIT ---
// Super Admin always has access.
// HR/Manager access depends on the global setting 'allow_manual_attendance_edit' (1=Yes, 0=No).
$user_role = $_SESSION['role'] ?? '';
$settings = $_SESSION['settings'] ?? [];
$allow_edit_setting = $settings['allow_manual_attendance_edit'] ?? '1';

$can_adjust = false;
if ($user_role === 'Super Admin') {
    $can_adjust = true;
} elseif (in_array($user_role, ['HR Admin', 'Manager'])) {
    if ($allow_edit_setting == '1') {
        $can_adjust = true;
    }
}
// --- END PERMISSION CHECK ---

// --- Page-Specific PHP Logic ---
function getAttendanceLogs($pdo) {
    try {
        // Join with employees table to get names
        $sql = "SELECT a.*, e.first_name, e.last_name 
                FROM attendance_logs a
                JOIN employees e ON a.employee_id = e.employee_id
                ORDER BY a.log_date DESC, a.time_in DESC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching attendance logs: " . $e->getMessage());
        return [];
    }
}

// NEW: Function to get all employees for the dropdown
function getAllEmployees($pdo) {
    try {
        $sql = "SELECT employee_id, first_name, last_name, department 
                FROM employees 
                ORDER BY last_name, first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees for attendance adjustment: " . $e->getMessage());
        return [];
    }
}


$logs = getAttendanceLogs($pdo);
$employees = getAllEmployees($pdo);
// Pass all logs data to JavaScript for client-side filtering/pagination
$json_logs = json_encode($logs);
?>

    <div class="bg-white p-8 rounded-xl shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 md:mb-0">Attendance Log</h2>
            <!-- NEW: Manual Adjustment Button (Conditional Render) -->
            <div class="flex space-x-3">
                <button onclick="printTable()"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 print-hide">
                    <i class="fas fa-print mr-2"></i> Print Table
                </button>

                <?php if ($can_adjust): ?>
                    <button id="openAdjustModal" onclick="openAdjustModal()"
                            class="px-5 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 print-hide">
                        <i class="fas fa-edit mr-2"></i> Manual Log Adjustment
                    </button>
                <?php endif; ?>
            </div>
            <!-- END NEW -->
        </div>

        <div id="log-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

        <!-- ADVANCED TABLE CONTROLS -->
        <div class="flex flex-col sm:flex-row justify-between items-end mb-4 space-y-3 sm:space-y-0 sm:space-x-4 print-hide">

            <!-- Date Filters -->
            <div class="flex space-x-3 w-full sm:w-auto">
                <div>
                    <label for="startDateFilter" class="block text-xs font-medium text-gray-700">Start Date</label>
                    <input type="date" id="startDateFilter" onchange="filterAndPaginateLogs()"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="endDateFilter" class="block text-xs font-medium text-gray-700">End Date</label>
                    <input type="date" id="endDateFilter" onchange="filterAndPaginateLogs()"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Search Input -->
            <div class="w-full sm:w-1/3 min-w-[200px] mt-4 sm:mt-0">
                <label for="logSearch" class="sr-only">Search Logs</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="logSearch" oninput="filterAndPaginateLogs(this.value)" placeholder="Search by name, date or ID..."
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Pagination Display (Moved below filters) -->
        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 print-hide">
            <div class="text-sm text-gray-700 flex items-center space-x-4">
                <span id="pagination-info" class="whitespace-nowrap"></span>
            </div>
            <div class="flex space-x-2">
                <button id="prevPageBtn" disabled class="px-3 py-1 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-xs">
                    <i class="fas fa-chevron-left"></i> Prev
                </button>
                <button id="nextPageBtn" disabled class="px-3 py-1 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-xs">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <!-- END ADVANCED TABLE CONTROLS -->

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
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider print-hide">
                        Action
                    </th>
                </tr>
                </thead>
                <tbody id="log-body" class="bg-white divide-y divide-gray-200">
                <!-- Content populated by JavaScript -->
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading data...
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- --- NEW: Log Adjustment Modal --- -->
<?php if ($can_adjust): ?>
    <div id="adjustLogModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="adjustLogForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-clock text-indigo-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="adjustModalTitle">
                                    Manual Log Adjustment
                                </h3>
                                <p class="text-sm text-gray-500">Edit or manually add a time clock entry.</p>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" id="log_id" name="log_id">
                                    <input type="hidden" id="hidden_employee_id" name="employee_id_edit_mode">

                                    <div>
                                        <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
                                        <select id="employee_id" name="employee_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="" disabled selected>-- Select employee --</option>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?php echo $emp['employee_id']; ?>" data-name="<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>">
                                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . ' (' . htmlspecialchars($emp['department']) . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="log_date" class="block text-sm font-medium text-gray-700">Date</label>
                                        <input type="date" id="log_date" name="log_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="time_in" class="block text-sm font-medium text-gray-700">Time In</label>
                                            <input type="time" id="time_in" name="time_in" step="1" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="time_out" class="block text-sm font-medium text-gray-700">Time Out (Optional)</label>
                                            <input type="time" id="time_out" name="time_out" step="1" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                    </div>

                                    <div id="adjust-form-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" id="saveLogButton" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Log
                        </button>
                        <button type="button" onclick="closeAdjustModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
    <!-- --- END NEW MODAL --- -->

    <!-- --- NEW: Delete Confirmation Modal --- -->
<?php if ($can_adjust): ?>
    <div id="deleteLogModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="delete-modal-title">
                                Confirm Deletion
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to permanently delete the attendance log for <strong id="deleteLogEmployeeName"></strong>?
                                    This action **cannot be undone** and may affect payroll calculation for that period.
                                </p>
                                <input type="hidden" id="deleteLogId">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmDeleteLogBtn" type="button" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete Permanently
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
    <!-- --- END Delete Confirmation Modal --- -->


    <script>
        // DOM Elements for Modals (Only exist if permissions allow)
        const adjustLogModal = document.getElementById('adjustLogModal');
        const adjustLogForm = document.getElementById('adjustLogForm');
        const adjustModalTitle = document.getElementById('adjustModalTitle');
        const saveLogButton = document.getElementById('saveLogButton');
        const adjustFormMessage = document.getElementById('adjust-form-message');
        const logMessage = document.getElementById('log-message');

        const employeeIdSelect = document.getElementById('employee_id');
        const hiddenEmployeeId = document.getElementById('hidden_employee_id');

        const deleteLogModal = document.getElementById('deleteLogModal');
        const deleteLogIdInput = document.getElementById('deleteLogId');
        const deleteLogEmployeeNameDisplay = document.getElementById('deleteLogEmployeeName');
        const confirmDeleteLogBtn = document.getElementById('confirmDeleteLogBtn');

        // ADVANCED TABLE VARIABLES
        const logBody = document.getElementById('log-body');
        const logSearch = document.getElementById('logSearch');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const paginationInfo = document.getElementById('pagination-info');
        const startDateFilter = document.getElementById('startDateFilter');
        const endDateFilter = document.getElementById('endDateFilter');

        // Data and State
        const allLogsData = <?php echo $json_logs; ?>;
        // Pass permission variable to JS
        const canAdjust = <?php echo json_encode($can_adjust); ?>;

        let filteredLogs = [...allLogsData];
        let currentPage = 1;
        const rowsPerPage = 10;

        function showMessage(messageBox, message, className, autoHide = true) {
            messageBox.textContent = message;
            messageBox.className = `p-3 rounded-lg text-center ${className}`;
            messageBox.classList.remove('hidden');
            if (autoHide) {
                setTimeout(() => {
                    messageBox.classList.add('hidden');
                }, 3000);
            }
        }

        function formatDuration(timeIn, timeOut) {
            if (!timeIn || !timeOut) return 'N/A';
            try {
                const timeInDt = new Date(timeIn);
                let timeOutDt = new Date(timeOut);
                if (timeOutDt < timeInDt) {
                    timeOutDt.setDate(timeOutDt.getDate() + 1);
                }
                const diffMs = timeOutDt - timeInDt;
                const totalHours = Math.round((diffMs / 3600000) * 100) / 100;
                return totalHours.toFixed(2) + ' hrs';
            } catch (e) {
                console.error("Error calculating duration:", e);
                return 'Error';
            }
        }

        function formatTime(dateTimeString) {
            if (!dateTimeString) return 'N/A';
            try {
                const dt = new Date(dateTimeString);
                return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            } catch (e) {
                return 'Error';
            }
        }

        // --- Advanced Table Logic ---

        function filterAndPaginateLogs(query = null) {
            const textQuery = (query === null ? logSearch.value : query).toLowerCase().trim();
            const startDate = startDateFilter.value;
            const endDate = endDateFilter.value;

            let startMoment = null;
            if (startDate) {
                startMoment = new Date(startDate);
                startMoment.setHours(0, 0, 0, 0);
            }

            let endMoment = null;
            if (endDate) {
                endMoment = new Date(endDate);
                endMoment.setHours(23, 59, 59, 999);
            }

            filteredLogs = allLogsData.filter(log => {
                const logDateStr = log.log_date;
                const logDate = new Date(logDateStr + 'T00:00:00');
                const employeeName = `${log.first_name} ${log.last_name}`.toLowerCase();
                const logDateDisplay = logDateStr.toLowerCase();
                const employeeId = log.employee_id.toString();

                const passesTextSearch = employeeName.includes(textQuery) ||
                    logDateDisplay.includes(textQuery) ||
                    employeeId.includes(textQuery);

                let passesDateFilter = true;
                if (startMoment && logDate < startMoment) passesDateFilter = false;
                if (endMoment && logDate > endMoment) passesDateFilter = false;

                return passesTextSearch && passesDateFilter;
            });

            currentPage = 1;
            renderTable();
        }

        function renderTable() {
            logBody.innerHTML = '';

            const totalPages = Math.ceil(filteredLogs.length / rowsPerPage);
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const currentBatch = filteredLogs.slice(start, end);

            if (filteredLogs.length === 0) {
                logBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No matching attendance logs found.</td></tr>';
                updatePaginationInfo(0, 0, 0, 0);
                return;
            }

            currentBatch.forEach(log => {
                const row = document.createElement('tr');
                row.id = `log-row-${log.log_id}`;
                const timeInFull = log.time_in;
                const timeOutFull = log.time_out;

                // Calculate Day of Week
                let dayName = '';
                if (log.log_date) {
                    const dateParts = log.log_date.split('-');
                    if (dateParts.length === 3) {
                        const dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
                        dayName = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                    }
                }

                // Conditionally Render Buttons
                let actionHtml = '';
                if (canAdjust) {
                    actionHtml = `
                    <button onclick='openAdjustModal(${JSON.stringify(log)})' class="text-indigo-600 hover:text-indigo-900">Edit</button>
                    <button onclick='openDeleteModal(${log.log_id}, "${log.first_name} ${log.last_name}")' class="text-red-600 hover:text-red-900">Delete</button>
                `;
                } else {
                    actionHtml = '<span class="text-gray-400 text-xs italic">Locked</span>';
                }

                row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${log.first_name} ${log.last_name}
                    <span class="text-xs text-gray-500 block">(ID: ${log.employee_id})</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div>${log.log_date}</div>
                    <div class="text-xs text-gray-500 font-medium mt-1">${dayName}</div>
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${formatTime(timeInFull)}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${formatTime(timeOutFull)}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${formatDuration(timeInFull, timeOutFull)}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${log.remarks || '-'}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium flex space-x-3 justify-center print-hide">
                    ${actionHtml}
                </td>
            `;
                logBody.appendChild(row);
            });

            updatePaginationInfo(start + 1, end > filteredLogs.length ? filteredLogs.length : end, filteredLogs.length, totalPages);
        }

        function updatePaginationInfo(startRow, endRow, totalRows, totalPages) {
            paginationInfo.textContent = `Showing ${startRow} to ${endRow} of ${totalRows} logs (Page ${currentPage} of ${totalPages})`;
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
        }

        // --- Print Logic ---
        function printTable() {
            const timezone = "<?php echo htmlspecialchars($timezone); ?>";
            const totalLogsDisplayed = filteredLogs.length;
            const printWindow = window.open('', '_blank');

            printWindow.document.write('<html><head><title>Attendance Log Report</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
            body { font-family: Arial, sans-serif; margin: 20px; font-size: 10pt; }
            .header-info { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
            .header-info h2 { font-size: 16pt; margin: 0; }
            .header-info p { font-size: 9pt; margin: 2px 0; color: #555; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f0f0f0; font-size: 9pt; text-transform: uppercase; color: #333; }
            td { font-size: 9pt; }
        `);
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="header-info">');
            printWindow.document.write(`<h2>Attendance Log Report</h2>`);
            printWindow.document.write(`<p>Generated: ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>`);
            printWindow.document.write(`<p>Timezone: ${timezone}</p>`);
            printWindow.document.write(`<p>Total Logs: ${totalLogsDisplayed}</p>`);
            printWindow.document.write('</div>');
            printWindow.document.write('<table>');

            const table = document.querySelector('.min-w-full');
            const headerRow = table.querySelector('thead tr');
            let printHeader = '<thead><tr>';
            headerRow.querySelectorAll('th').forEach(th => {
                if (!th.classList.contains('print-hide')) {
                    printHeader += `<th>${th.textContent}</th>`;
                }
            });
            printHeader += '</tr></thead>';
            printWindow.document.write(printHeader);

            printWindow.document.write('<tbody>');
            filteredLogs.forEach(log => {
                printWindow.document.write(`
                <tr>
                    <td>${log.first_name} ${log.last_name} (ID: ${log.employee_id})</td>
                    <td>${log.log_date}</td>
                    <td>${formatTime(log.time_in)}</td>
                    <td>${formatTime(log.time_out)}</td>
                    <td>${formatDuration(log.time_in, log.time_out)}</td>
                    <td>${log.remarks || '-'}</td>
                </tr>
            `);
            });
            printWindow.document.write('</tbody>');
            printWindow.document.write('</table>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        logSearch.addEventListener('input', (e) => filterAndPaginateLogs(e.target.value));
        prevPageBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderTable(); } });
        nextPageBtn.addEventListener('click', () => { if (currentPage < Math.ceil(filteredLogs.length / rowsPerPage)) { currentPage++; renderTable(); } });

        // --- Modal Control (Only wrap in logic if permission exists) ---
        <?php if ($can_adjust): ?>
        function openAdjustModal(logData = null) {
            adjustLogForm.reset();
            adjustFormMessage.classList.add('hidden');
            employeeIdSelect.disabled = false;
            hiddenEmployeeId.name = 'employee_id_edit_mode';
            const today = new Date().toISOString().slice(0, 10);

            if (logData) {
                adjustModalTitle.textContent = 'Edit Existing Log';
                saveLogButton.textContent = 'Update Log';
                document.getElementById('log_id').value = logData.log_id;
                employeeIdSelect.value = logData.employee_id;
                employeeIdSelect.disabled = true;
                hiddenEmployeeId.value = logData.employee_id;
                hiddenEmployeeId.name = 'employee_id';
                document.getElementById('log_date').value = logData.log_date;
                document.getElementById('time_in').value = logData.time_in ? logData.time_in.substring(11, 16) : '';
                document.getElementById('time_out').value = logData.time_out ? logData.time_out.substring(11, 16) : '';
            } else {
                adjustModalTitle.textContent = 'Add New Attendance Log';
                saveLogButton.textContent = 'Save New Log';
                document.getElementById('log_id').value = '';
                employeeIdSelect.name = 'employee_id';
                document.getElementById('log_date').value = today;
            }
            adjustLogModal.classList.remove('hidden');
        }

        function closeAdjustModal() {
            adjustLogModal.classList.add('hidden');
        }

        let logIdToDelete = null;
        function openDeleteModal(logId, employeeName) {
            logIdToDelete = logId;
            deleteLogIdInput.value = logId;
            deleteLogEmployeeNameDisplay.textContent = employeeName;
            deleteLogModal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            deleteLogModal.classList.add('hidden');
            logIdToDelete = null;
        }

        if(adjustLogForm) {
            adjustLogForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                showMessage(adjustFormMessage, 'Saving log...', 'bg-blue-100 text-blue-700', false);

                const data = {};
                const formData = new FormData(adjustLogForm);
                data.log_id = formData.get('log_id');
                if (data.log_id) {
                    data.employee_id = formData.get('employee_id');
                } else {
                    data.employee_id = formData.get('employee_id');
                }
                data.log_date = formData.get('log_date');
                const timeInTime = formData.get('time_in');
                const timeOutTime = formData.get('time_out');

                if (!data.employee_id || !data.log_date || !timeInTime) {
                    showMessage(adjustFormMessage, 'Employee, date, and Time In are required.', 'bg-red-100 text-red-700');
                    return;
                }

                // Helper to ensure time string is valid (HH:MM:SS)
                const formatTimeForDB = (dateStr, timeStr) => {
                    if (!timeStr) return null;
                    // If timeStr is HH:MM, append :00. If HH:MM:SS, leave it.
                    const parts = timeStr.split(':');
                    if (parts.length === 2) {
                        return `${dateStr} ${timeStr}:00`;
                    }
                    return `${dateStr} ${timeStr}`;
                };

                data.time_in = formatTimeForDB(data.log_date, timeInTime);
                data.time_out = formatTimeForDB(data.log_date, timeOutTime);

                if (data.time_out && new Date(data.time_in) >= new Date(data.time_out)) {
                    showMessage(adjustFormMessage, 'Time Out must be later than Time In.', 'bg-red-100 text-red-700');
                    return;
                }

                try {
                    const response = await fetch('api/adjust_log.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    if (result.success) {
                        showMessage(adjustFormMessage, result.message, 'bg-green-100 text-green-700');
                        showMessage(logMessage, result.message, 'bg-green-100 text-green-700');
                        setTimeout(() => { closeAdjustModal(); window.location.reload(); }, 1000);
                    } else {
                        showMessage(adjustFormMessage, result.message, 'bg-red-100 text-red-700');
                    }
                } catch (error) {
                    showMessage(adjustFormMessage, 'An unexpected error occurred.', 'bg-red-100 text-red-700');
                }
            });
        }

        if(confirmDeleteLogBtn) {
            confirmDeleteLogBtn.addEventListener('click', async () => {
                if (logIdToDelete) {
                    const id = logIdToDelete;
                    closeDeleteModal();
                    showMessage(logMessage, 'Deleting log...', 'bg-blue-100 text-blue-700', false);
                    try {
                        const response = await fetch('api/adjust_log.php?action=delete', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ log_id: id })
                        });
                        const result = await response.json();
                        if (result.success) {
                            showMessage(logMessage, result.message, 'bg-green-100 text-green-700');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showMessage(logMessage, result.message, 'bg-red-100 text-red-700');
                        }
                    } catch (error) {
                        showMessage(logMessage, 'An unexpected error occurred.', 'bg-red-100 text-red-700');
                    }
                }
            });
        }
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', () => {
            renderTable();
        });
    </script>

<?php
include 'template/footer.php';
?>