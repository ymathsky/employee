<?php
// FILENAME: employee/schedule_management.php
$pageTitle = 'Schedule Management';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific PHP Logic ---

// Get current week start date (Monday)
$current_date = isset($_GET['week_start']) ? new DateTime($_GET['week_start']) : new DateTime();
if ($current_date->format('N') != 1) { // 1 is Monday
    $current_date->modify('last monday');
}
$week_start = $current_date->format('Y-m-d');

// Create an array of dates for the 7-day week
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $week_dates[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}
$week_end = end($week_dates);

// Get navigation links
$prev_week = (new DateTime($week_start))->modify('-1 week')->format('Y-m-d');
$next_week = (new DateTime($week_start))->modify('+1 week')->format('Y-m-d');
$today_week = (new DateTime('today'))->modify('last monday')->format('Y-m-d');


// Function to get all employees
function getAllEmployees($pdo) {
    try {
        $sql = "SELECT employee_id, first_name, last_name FROM employees ORDER BY last_name, first_name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

$employees = getAllEmployees($pdo);
?>

<!-- Week Navigation & Add Shift Button -->
<!--suppress ALL -->
<div class="bg-white p-6 rounded-xl shadow-xl mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 sm:mb-0">
            Week of: <?php echo date('M d, Y', strtotime($week_start)); ?>
        </h2>
        <div class="flex items-center space-x-2">
            <a href="?week_start=<?php echo $today_week; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Today
            </a>
            <a href="?week_start=<?php echo $prev_week; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-chevron-left"></i>
            </a>
            <a href="?week_start=<?php echo $next_week; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <button id="openAddShiftModal" class="w-full sm:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
        <i class="fas fa-plus mr-2"></i>Add Shift (Exception)
    </button>
</div>

<!-- Schedule Table -->
<div class="bg-white p-8 rounded-xl shadow-xl overflow-x-auto">
    <div id="schedule-message" class="hidden p-3 rounded-lg text-center mb-4"></div>
    <table class="min-w-full divide-y divide-gray-200 border">
        <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="sticky left-0 bg-gray-50 z-10 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                Employee
            </th>
            <?php foreach ($week_dates as $date): ?>
                <!-- *** FIX: Added min-w-36 for minimum column width *** -->
                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l min-w-36">
                    <?php echo date('D, M d', strtotime($date)); ?>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200" id="schedule-body">
        <!-- Data will be loaded by JavaScript -->
        <tr>
            <td colspan="8" class="text-center p-8 text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl"></i><span class="ml-2">Loading schedule...</span>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<!-- Add Shift Modal -->
<div id="addShiftModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="addShiftForm">
                <input type="hidden" name="schedule_id" id="schedule_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-calendar-plus text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Add New Shift (Exception)
                            </h3>
                            <p class="text-sm text-gray-500">This will override the employee's standard schedule for this day.</p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
                                    <select id="employee_id" name="employee_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select an employee...</option>
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['employee_id']; ?>">
                                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="work_date" class="block text-sm font-medium text-gray-700">Date</label>
                                    <input type="date" id="work_date" name="work_date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="shift_start" class="block text-sm font-medium text-gray-700">Shift Start</label>
                                        <input type="time" id="shift_start" name="shift_start" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="shift_end" class="block text-sm font-medium text-gray-700">Shift End</label>
                                        <input type="time" id="shift_end" name="shift_end" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <div id="add-shift-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Shift
                    </button>
                    <button type="button" id="btnDeleteShift" class="hidden w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeAddModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add Shift Modal -->

<script>
    const addModal = document.getElementById('addShiftModal');
    const addShiftForm = document.getElementById('addShiftForm');
    const addShiftMessage = document.getElementById('add-shift-message');
    const btnDeleteShift = document.getElementById('btnDeleteShift');
    const scheduleMessage = document.getElementById('schedule-message');
    const scheduleBody = document.getElementById('schedule-body');

    const weekStart = '<?php echo $week_start; ?>';
    const weekEnd = '<?php echo $week_end; ?>';
    const weekDates = <?php echo json_encode($week_dates); ?>;
    const employees = <?php echo json_encode($employees); ?>;

    // --- Modal Control ---
    document.getElementById('openAddShiftModal').addEventListener('click', () => {
        addShiftForm.reset();
        document.getElementById('schedule_id').value = ''; // Clear ID
        document.getElementById('modal-title').textContent = 'Add New Shift (Exception)';
        btnDeleteShift.classList.add('hidden'); // Hide delete button
        addShiftMessage.classList.add('hidden');
        addModal.classList.remove('hidden');
    });

    function openEditModal(shift) {
        addShiftForm.reset();
        document.getElementById('employee_id').value = shift.employee_id;
        document.getElementById('work_date').value = shift.work_date;
        
        // Ensure time format is HH:MM
        if (shift.shift_start) {
            document.getElementById('shift_start').value = shift.shift_start.substring(0, 5);
        }
        if (shift.shift_end) {
            document.getElementById('shift_end').value = shift.shift_end.substring(0, 5);
        }
        
        document.getElementById('schedule_id').value = shift.schedule_id || ''; // Might be empty if adding new exception on top of standard

        document.getElementById('modal-title').textContent = 'Edit Shift Exception';
        
        // Only show delete button if it's an existing exception (has an ID)
        if (shift.schedule_id) {
            btnDeleteShift.classList.remove('hidden');
        } else {
            btnDeleteShift.classList.add('hidden');
        }
        
        addShiftMessage.classList.add('hidden');
        addModal.classList.remove('hidden');
    }

    // NEW: Helper to open modal for a specific cell (date + employee)
    function openAddModalForCell(employeeId, dateStr) {
        addShiftForm.reset();
        document.getElementById('employee_id').value = employeeId;
        document.getElementById('work_date').value = dateStr;
        document.getElementById('schedule_id').value = ''; // New exception
        document.getElementById('modal-title').textContent = 'Add New Shift (Exception)';
        btnDeleteShift.classList.add('hidden');
        addShiftMessage.classList.add('hidden');
        addModal.classList.remove('hidden');
    }

    btnDeleteShift.addEventListener('click', () => {
        const scheduleId = document.getElementById('schedule_id').value;
        if (scheduleId) {
            deleteShift(scheduleId);
            closeAddModal();
        }
    });

    function closeAddModal() {
        addModal.classList.add('hidden');
    }

    // --- Add Shift Form Submission ---
    addShiftForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addShiftForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/add_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showMessage(addShiftMessage, 'Shift added successfully!', 'bg-green-100 text-green-700');
                loadSchedule(); // Refresh schedule
                setTimeout(closeAddModal, 1000);
            } else {
                showMessage(addShiftMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(addShiftMessage, 'An error occurred.', 'bg-red-100 text-red-700');
        }
    });

    // --- Load Schedule Data ---
    async function loadSchedule() {
        try {
            const response = await fetch(`api/get_schedules.php?start_date=${weekStart}&end_date=${weekEnd}`);
            if (!response.ok) {
                throw new Error(`API error: ${response.statusText}`);
            }
            const schedules = await response.json();

            if (schedules.error) {
                throw new Error(schedules.error);
            }

            renderSchedule(schedules);
        } catch (error) { // *** FIX: Removed the stray 'S' ***
            console.error('Error loading schedule:', error);
            showMessage(scheduleMessage, `Error loading schedule data: ${error.message}`, 'bg-red-100 text-red-700');
        }
    }

    // --- Fetch Rest Days for Employees ---
    let employeeRestDays = {};

    async function fetchRestDays(employeeId) {
        try {
            const response = await fetch(`api/get_rest_days.php?employee_id=${employeeId}`);
            const result = await response.json();
            if (result.success) {
                employeeRestDays[employeeId] = result.rest_days;
            } else {
                employeeRestDays[employeeId] = [];
            }
        } catch (error) {
            employeeRestDays[employeeId] = [];
        }
    }

    async function fetchAllRestDays() {
        const promises = employees.map(emp => fetchRestDays(emp.employee_id));
        await Promise.all(promises);
    }

    // --- Fetch Standard Schedules for Employees ---
    let employeeStandardSchedules = {};

    async function fetchStandardSchedule(employeeId) {
        try {
            const response = await fetch(`api/get_standard_schedule.php?employee_id=${employeeId}`);
            const result = await response.json();
            if (result.success) {
                employeeStandardSchedules[employeeId] = result.schedule;
            } else {
                employeeStandardSchedules[employeeId] = [];
            }
        } catch (error) {
            employeeStandardSchedules[employeeId] = [];
        }
    }

    async function fetchAllStandardSchedules() {
        const promises = employees.map(emp => fetchStandardSchedule(emp.employee_id));
        await Promise.all(promises);
    }

    // --- Render Schedule Table ---
    function renderSchedule(schedules) {
        scheduleBody.innerHTML = '';
        if (employees.length === 0) {
            scheduleBody.innerHTML = '<tr><td colspan="8" class="text-center p-8 text-gray-500">No employees found. Add employees in Employee Management.</td></tr>';
            return;
        }
        const scheduleMap = {};
        schedules.forEach(shift => {
            const key = `${shift.employee_id}_${shift.work_date}`;
            scheduleMap[key] = shift;
        });
        employees.forEach(emp => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="sticky left-0 bg-white z-5 px-6 py-4 text-sm font-medium text-gray-900 border-r">
                    ${emp.first_name} ${emp.last_name}
                </td>
                ${weekDates.map(date => {
                const key = `${emp.employee_id}_${date}`;
                const shift = scheduleMap[key];
                // Get day of week in PHP format (Mon, Tue, ...)
                const jsDate = new Date(date);
                const dayOfWeek = jsDate.toLocaleString('en-US', { weekday: 'short' });
                const standardSchedule = employeeStandardSchedules[emp.employee_id] || [];
                const scheduleForDay = standardSchedule.find(s => s.day_of_week === dayOfWeek);
                
                // 1. Check for Exception (Shift)
                if (shift && shift.type === 'exception') {
                    const shiftJson = JSON.stringify(shift).replace(/"/g, '&quot;');
                    return `
                        <td class=\"px-4 py-4 text-sm border-l text-center bg-yellow-50 cursor-pointer hover:bg-yellow-100\" onclick=\"openEditModal(${shiftJson})\">
                            <div class=\"font-medium text-red-600\">${formatTime(shift.shift_start)} - ${formatTime(shift.shift_end)}</div>
                            <div class=\"text-xs text-red-700\">(Exception)</div>
                            <div class=\"text-xs text-gray-500 mt-1\">Click to Edit/Delete</div>
                        </td>`;
                }
                
                // 2. Check for Standard Rest Day
                if (scheduleForDay && scheduleForDay.is_rest_day == 1) {
                    return `<td class=\"px-4 py-4 text-sm text-blue-400 border-l text-center italic cursor-pointer hover:bg-blue-50\" title=\"Rest Day - Click to Add Exception\" onclick=\"openAddModalForCell(${emp.employee_id}, '${date}')\">
                        Rest Day <span title=\"Standard Rest Day\" style=\"font-size:1.2em;\">&#x1F6C0;</span>
                        <div class=\"text-xs text-gray-400 mt-1 opacity-0 hover:opacity-100\">Click to Add Work</div>
                    </td>`;
                }

                // 3. Check for Standard Work Day
                if (scheduleForDay && scheduleForDay.is_rest_day != 1) {
                    // Create a pseudo-shift object for the modal to pre-fill standard times
                    const standardShiftObj = {
                        employee_id: emp.employee_id,
                        work_date: date,
                        shift_start: scheduleForDay.start_time,
                        shift_end: scheduleForDay.end_time,
                        schedule_id: null // No ID means it's not an exception yet
                    };
                    const standardJson = JSON.stringify(standardShiftObj).replace(/"/g, '&quot;');
                    
                    return `<td class=\"px-4 py-4 text-sm text-gray-800 border-l text-center bg-gray-50 cursor-pointer hover:bg-gray-100\" onclick=\"openEditModal(${standardJson})\">
                        <div class=\"font-medium\">${formatTime(scheduleForDay.start_time)} - ${formatTime(scheduleForDay.end_time)}</div>
                        <div class=\"text-xs text-gray-500\">(Standard)</div>
                        <div class=\"text-xs text-gray-400 mt-1 opacity-0 hover:opacity-100\">Click to Override</div>
                    </td>`;
                } 
                
                // 4. Default / Off (No standard schedule defined)
                return `<td class=\"px-4 py-4 text-sm text-gray-400 border-l text-center italic cursor-pointer hover:bg-gray-50\" onclick=\"openAddModalForCell(${emp.employee_id}, '${date}')\">
                    Off
                    <div class=\"text-xs text-gray-400 mt-1 opacity-0 hover:opacity-100\">Click to Add Work</div>
                </td>`;
            }).join('')}
            `;
            scheduleBody.appendChild(row);
        });
    }

    // --- Delete Shift ---
    async function deleteShift(scheduleId) {
        // This function now *only* deletes exceptions, which is correct.
        if (!confirm('Are you sure you want to delete this shift exception?')) {
            return;
        }

        try {
            const response = await fetch('api/delete_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ schedule_id: scheduleId })
            });
            const result = await response.json();
            if (result.success) {
                showMessage(scheduleMessage, 'Shift exception deleted.', 'bg-green-100 text-green-700');
                loadSchedule(); // Refresh
            } else {
                showMessage(scheduleMessage, result.message, 'bg-red-100 text-red-700');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage(scheduleMessage, 'An error occurred.', 'bg-red-100 text-red-700');
        }
    }

    // --- Utility Functions ---
    function showMessage(element, message, className) {
        element.textContent = message;
        element.className = `p-3 rounded-lg text-center mb-4 ${className}`;
        element.classList.remove('hidden');
    }

    function formatTime(timeString) {
        if (!timeString) return 'N/A';

        // Check if timeString is in 'HH:MM:SS' or 'HH:MM' format
        const parts = timeString.split(':');
        if (parts.length < 2) return timeString; // Return as-is if not a valid time

        const hour = parts[0];
        const minute = parts[1];

        const h = parseInt(hour);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const formattedHour = h % 12 === 0 ? 12 : h % 12;
        return `${formattedHour}:${minute} ${ampm}`;
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', async () => {
        await fetchAllRestDays();
        await fetchAllStandardSchedules();
        loadSchedule();
    });
</script>

<?php
include 'template/footer.php';
?>

