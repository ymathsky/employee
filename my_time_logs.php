<?php
// FILENAME: employee/my_time_logs.php
$pageTitle = 'My Time Logs';
include 'template/header.php'; // Handles session, auth, DB

// --- TIMEZONE FIX AND INITIALIZATION ---
$timezone = $_SESSION['settings']['timezone'] ?? 'UTC';
date_default_timezone_set($timezone);

// --- NEW: Include Global Utility Functions ---
require_once __DIR__ . '/config/utils.php';

// Get Employee ID from the user's session
$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Not logged in.</div>";
    include 'template/footer.php';
    exit;
}

// --- Page-Specific PHP Logic: Fetch all segments for the employee ---
function getMyAttendanceLogs($pdo, $employee_id) {
    try {
        // Select all logs for the logged-in employee, including log_id for reference
        $sql = "SELECT log_id, time_in, time_out, log_date, scheduled_start_time, remarks
                FROM attendance_logs
                WHERE employee_id = ?
                ORDER BY log_date DESC, time_in ASC"; // Order by date DESC, time_in ASC to group properly
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching my attendance logs: " . $e->getMessage());
        return [];
    }
}

$logs = getMyAttendanceLogs($pdo, $employee_id);

// --- New Function: Aggregate logs into days (PHP/Pre-processing) ---
$daily_aggregated_logs = [];
foreach ($logs as $log) {
    $date = $log['log_date'];
    if (!isset($daily_aggregated_logs[$date])) {
        $daily_aggregated_logs[$date] = [
            'segments' => [],
            'log_date' => $date
        ];
    }
    $daily_aggregated_logs[$date]['segments'][] = $log;
}

// --- Fetch Pending Adjustment Requests ---
$pending_requests = [];
try {
    $stmt_req = $pdo->prepare("SELECT * FROM attendance_adjustment_requests WHERE employee_id = ? ORDER BY requested_at DESC"); // Wait date created_at?
    // DB Column created_at
    $stmt_req = $pdo->prepare("SELECT * FROM attendance_adjustment_requests WHERE employee_id = ? ORDER BY created_at DESC");
    $stmt_req->execute([$employee_id]);
    $pending_requests = $stmt_req->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching requests: " . $e->getMessage());
}

// Convert to an indexed array for easier JavaScript iteration
$json_logs = json_encode(array_values($daily_aggregated_logs));
$json_requests = json_encode($pending_requests);

?>

<!-- Attendance Log Table -->
<div class="bg-white p-8 rounded-xl shadow-xl">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">My Attendance Log</h2>
        <button onclick="openRequestModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg flex items-center shadow-md transition duration-300">
            <i class="fas fa-plus-circle mr-2"></i> Request Adjustment
        </button>
    </div>

    <div id="log-message" class="mb-4 hidden p-3 rounded-lg text-center"></div>

    <!-- Tabs for Logs and Requests -->
    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="myTab" data-tabs-toggle="#myTabContent" role="tablist">
            <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-indigo-600 border-indigo-600" id="logs-tab" data-tabs-target="#logs" type="button" role="tab" aria-controls="logs" aria-selected="true" onclick="switchTab('logs')">Attendance History</button>
            </li>
            <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="requests-tab" data-tabs-target="#requests" type="button" role="tab" aria-controls="requests" aria-selected="false" onclick="switchTab('requests')">Adjustment Requests</button>
            </li>
        </ul>
    </div>

    <div id="myTabContent">
        <!-- Logs Tab -->
        <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="logs" role="tabpanel" aria-labelledby="logs-tab">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Clock-In</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Clock-Out</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Hours</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    </tr>
                    </thead>
                    <tbody id="log-body" class="bg-white divide-y divide-gray-200">
                    <!-- Content populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Requests Tab -->
        <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="requests" role="tabpanel" aria-labelledby="requests-tab">
             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log Date</th>
                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                    </thead>
                    <tbody id="request-body" class="bg-white divide-y divide-gray-200">
                    <!-- Pending Requests injected by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Request Adjustment Modal -->
<div id="requestModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRequestModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-edit text-indigo-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Request Attendance Adjustment</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4">Submit a request for missing logs or corrections. Admins will review this.</p>
                            <form id="adjustmentForm">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="adj_date">Date</label>
                                    <input type="date" id="adj_date" name="adj_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="adj_time_in">Time In</label>
                                        <input type="time" id="adj_time_in" name="adj_time_in" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="adj_time_out">Time Out</label>
                                        <input type="time" id="adj_time_out" name="adj_time_out" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="adj_reason">Reason</label>
                                    <textarea id="adj_reason" name="adj_reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="e.g. Forgot to clock in, System error..." required></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitAdjustmentRequest()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Submit Request
                </button>
                <button type="button" onclick="closeRequestModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const allLogsData = <?php echo $json_logs; ?>;
    const allRequestsData = <?php echo $json_requests; ?>;
    const logBody = document.getElementById('log-body');
    const requestBody = document.getElementById('request-body');
    const timezone = '<?php echo htmlspecialchars($timezone); ?>';

    // ... existing functions ...

    function renderRequests() {
        requestBody.innerHTML = '';
        if (allRequestsData.length === 0) {
            requestBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">No adjustment requests found.</td></tr>';
            return;
        }
        
        allRequestsData.forEach(req => {
            const statusColors = {
                'Pending': 'bg-yellow-100 text-yellow-800',
                'Approved': 'bg-green-100 text-green-800',
                'Rejected': 'bg-red-100 text-red-800'
            };
            const statusClass = statusColors[req.status] || 'bg-gray-100 text-gray-800';
            
            // Format Times
             const timeIn = new Date(req.time_in).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
             const timeOut = new Date(req.time_out).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
             const reqDate = new Date(req.created_at).toLocaleDateString();

            const row = `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${reqDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${req.log_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${timeIn}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${timeOut}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="${req.reason}">${req.reason}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                            ${req.status}
                        </span>
                    </td>
                </tr>
            `;
            requestBody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Modal Functions
    const requestModal = document.getElementById('requestModal');
    function openRequestModal() {
        requestModal.classList.remove('hidden');
        // Set default date to today
        document.getElementById('adj_date').valueAsDate = new Date();
    }
    function closeRequestModal() {
        requestModal.classList.add('hidden');
        document.getElementById('adjustmentForm').reset();
    }

    async function submitAdjustmentRequest() {
        const date = document.getElementById('adj_date').value;
        const timeIn = document.getElementById('adj_time_in').value;
        const timeOut = document.getElementById('adj_time_out').value;
        const reason = document.getElementById('adj_reason').value;

        if (!date || !timeIn || !timeOut || !reason) {
            alert("All fields are required.");
            return;
        }

        // Combine date and time to ISO string for backend?
        // Or just send raw values. Backend expects DATETIME for DB.
        // Let's send raw and let PHP handle conversion
        
        const payload = {
            log_date: date,
            time_in: `${date} ${timeIn}:00`,
            time_out: `${date} ${timeOut}:00`,
            reason: reason
        };

        try {
            const res = await fetch('api/submit_attendance_adjustment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            
            if (result.success) {
                alert("Request submitted successfully!");
                location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (error) {
            console.error(error);
            alert("Network error.");
        }
    }

    // Tabs
    function switchTab(tabId) {
        document.getElementById('logs').classList.add('hidden');
        document.getElementById('requests').classList.add('hidden');
        document.getElementById('logs-tab').classList.remove('text-indigo-600', 'border-indigo-600');
        document.getElementById('requests-tab').classList.remove('text-indigo-600', 'border-indigo-600');
        
        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById(tabId + '-tab').classList.add('text-indigo-600', 'border-indigo-600');
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        renderLogs(); // Existing function (assumed to be there)
        renderRequests();
        switchTab('logs'); // Default tab
    });


    // Utility to format time to 'h:i:s A'
    function formatTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        try {
            const dt = new Date(dateTimeString);
            return dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        } catch (e) {
            return 'Error';
        }
    }

    // Utility to format a duration in seconds to hours/minutes
    function formatDuration(totalSeconds) {
        if (isNaN(totalSeconds) || totalSeconds <= 0) return '0 hrs';
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.round((totalSeconds % 3600) / 60);

        if (hours > 0) {
            return `${hours} hrs ${minutes} min`;
        } else {
            return `${minutes} min`;
        }
    }

    // Main calculation and rendering function
    function renderLogs() {
        logBody.innerHTML = '';
        if (allLogsData.length === 0) {
            logBody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No attendance logs found.</td></tr>';
            return;
        }

        allLogsData.forEach(dayLog => {
            const segments = dayLog.segments;
            let totalSecondsWorked = 0;
            let totalBreakSeconds = 0;
            let firstClockIn = null;
            let lastClockOut = null;
            let currentClockOutTime = null;

            // 1. Calculate Worked Time and Break Time
            segments.forEach((segment, index) => {
                const timeIn = segment.time_in;
                const timeOut = segment.time_out;

                if (timeIn) {
                    if (!firstClockIn) firstClockIn = new Date(timeIn);

                    if (timeOut) {
                        const timeInDt = new Date(timeIn);
                        let timeOutDt = new Date(timeOut);

                        // Handle midnight crossover for shift duration calculation
                        if (timeOutDt < timeInDt) {
                            timeOutDt.setDate(timeOutDt.getDate() + 1);
                        }

                        const segmentDurationSeconds = (timeOutDt.getTime() - timeInDt.getTime()) / 1000;
                        totalSecondsWorked += segmentDurationSeconds;

                        // Calculate break time: break is the gap between this segment's clock-out and the next segment's clock-in
                        if (index < segments.length - 1) {
                            const nextTimeIn = segments[index + 1].time_in;
                            if (nextTimeIn) {
                                const breakStart = timeOutDt;
                                const breakEnd = new Date(nextTimeIn);
                                if (breakEnd > breakStart) { // Only count forward breaks
                                    const breakDurationSeconds = (breakEnd.getTime() - breakStart.getTime()) / 1000;
                                    totalBreakSeconds += breakDurationSeconds;
                                }
                            }
                        }

                        lastClockOut = timeOutDt;
                        currentClockOutTime = timeOut; // Keep the string for the last display column
                    } else {
                        // Employee is currently clocked in (unclosed segment)
                        lastClockOut = '<span class="text-indigo-600 font-semibold">ACTIVE</span>';
                        currentClockOutTime = null;
                    }
                }
            });


            // 2. Build Details for Modal/Tooltip
            let segmentDetails = segments.map((segment, index) => {
                if (!segment.time_in) return '';

                const segmentTimeIn = formatTime(segment.time_in);
                const segmentTimeOut = segment.time_out ? formatTime(segment.time_out) : '<span class="text-indigo-600">ACTIVE</span>';

                let status = 'Completed';
                let duration = '';

                if (segment.time_in && segment.time_out) {
                    const durationMs = new Date(segment.time_out).getTime() - new Date(segment.time_in).getTime();
                    duration = ` (${formatDuration(durationMs / 1000)})`;
                } else {
                    status = 'Active';
                }

                // If this is not the last segment, include the following break time
                let breakLine = '';
                if (index < segments.length - 1 && segments[index].time_out && segments[index + 1].time_in) {
                    const breakStart = new Date(segments[index].time_out);
                    const breakEnd = new Date(segments[index + 1].time_in);
                    const breakDurationSeconds = (breakEnd.getTime() - breakStart.getTime()) / 1000;

                    if (breakDurationSeconds > 0) {
                        breakLine = `<div class="text-xs text-red-500 ml-4 border-l pl-2">Break: ${formatDuration(breakDurationSeconds)}</div>`;
                    }
                }


                return `
                    <div class="mb-2 p-2 rounded-md ${status === 'Active' ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50'}">
                        <strong>Segment ${index + 1}:</strong> ${segmentTimeIn} to ${segmentTimeOut} ${duration}
                    </div>
                    ${breakLine}
                `;
            }).join('');

            const totalDurationMinutes = totalSecondsWorked / 60;
            const totalBreakMinutes = totalBreakSeconds / 60;

            // Collect remarks from all segments
            const remarksList = segments.map(s => s.remarks).filter(r => r).join(', ');

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${dayLog.log_date}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    ${formatTime(segments[0].time_in)}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm ${currentClockOutTime ? 'text-gray-500' : 'text-indigo-600 font-semibold'}">
                    ${currentClockOutTime ? formatTime(currentClockOutTime) : lastClockOut}
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="${totalDurationMinutes < 420 ? 'text-red-600 font-medium' : 'text-green-600 font-medium'}">
                        ${(totalSecondsWorked / 3600).toFixed(2)} hrs
                    </span>
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="${totalBreakMinutes > 0 ? 'text-orange-600 font-medium' : 'text-gray-500'}">
                        ${formatDuration(totalBreakSeconds)}
                    </span>
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">
                    <span class="text-xs font-semibold ${remarksList.includes('Late') ? 'text-red-600' : 'text-gray-600'}">
                        ${remarksList || '-'}
                    </span>
                </td>
                <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium">
                    <button onclick='showDetailsModal("${dayLog.log_date}", \`${segmentDetails}\`)' class="text-indigo-600 hover:text-indigo-800">
                        View Segments
                    </button>
                </td>
            `;
            logBody.appendChild(row);
        });
    }

    // --- Dynamic Modal for Details (Need a simple modal for this) ---

    // Since we don't have a pre-existing modal structure in my_time_logs.php,
    // I will dynamically create a simple, non-blocking modal for the breakdown.
    function showDetailsModal(date, detailsHtml) {
        let modal = document.getElementById('detailsModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'detailsModal';
            modal.className = 'fixed z-20 inset-0 overflow-y-auto hidden';
            modal.innerHTML = `
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeDetailsModal()" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-3" id="detailsModalTitle">
                                Log Segments for ${date}
                            </h3>
                            <div id="detailsModalBody" class="text-sm text-gray-700 max-h-80 overflow-y-auto">
                                <!-- Details inserted here -->
                </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeDetailsModal()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                Close
                </button>
                </div>
                </div>
                </div>
                `;
            document.body.appendChild(modal);
        }

        document.getElementById('detailsModalTitle').textContent = `Log Segments for ${date}`;
        document.getElementById('detailsModalBody').innerHTML = detailsHtml;
        modal.classList.remove('hidden');
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', renderLogs);

</script>

<?php
include 'template/footer.php';
?>
