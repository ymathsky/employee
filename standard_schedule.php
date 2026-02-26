<?php
// FILENAME: employee/standard_schedule.php
$pageTitle = 'Standard Schedule Management';
include 'template/header.php';

// Only Admin/Manager
if (!in_array($_SESSION['role'], ['HR Admin', 'Super Admin', 'Manager'])) {
    header('Location: dashboard.php');
    exit;
}

// Fetch employees for the dropdown
$employees = [];
try {
    // If Manager, filter by department (optional, keeping generic for now)
    $stmt = $pdo->query("SELECT employee_id, first_name, last_name, department FROM employees ORDER BY last_name, first_name");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching employees: " . $e->getMessage());
}
?>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- SECTION 1: STANDARD WEEKLY SCHEDULE (Existing Feature) -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-10">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Standard Weekly Schedule</h2>
                    <p class="text-sm text-gray-500">Set the default recurring shift times for employees.</p>
                </div>
            </div>

            <div class="p-6">
                <div class="mb-6 max-w-md">
                    <label for="schedule_employee_id" class="block text-sm font-medium text-gray-700 mb-2">Select Employee to Configure</label>
                    <select id="schedule_employee_id" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">-- Choose an Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>">
                                <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['department'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <form id="standardScheduleForm" class="hidden">
                    <input type="hidden" name="employee_id" id="form_employee_id">

                    <!-- Quick Actions Toolbar -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <span class="text-sm font-semibold text-gray-700 flex items-center">
                                <i class="fas fa-magic mr-2 text-indigo-500"></i> Quick Templates
                            </span>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="applyQuickSchedule('09:00', '17:00')" class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 rounded-full hover:bg-indigo-50 shadow-sm transition-colors">
                                    9:00 AM - 5:00 PM (M-F)
                                </button>
                                <button type="button" onclick="applyQuickSchedule('08:00', '17:00')" class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 rounded-full hover:bg-indigo-50 shadow-sm transition-colors">
                                    8:00 AM - 5:00 PM (M-F)
                                </button>
                                <button type="button" onclick="applyQuickSchedule('08:00', '16:00')" class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 rounded-full hover:bg-indigo-50 shadow-sm transition-colors">
                                    8:00 AM - 4:00 PM (M-F)
                                </button>
                                <button type="button" onclick="applyQuickSchedule('07:00', '16:00')" class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 rounded-full hover:bg-indigo-50 shadow-sm transition-colors">
                                    7:00 AM - 4:00 PM (M-F)
                                </button>
                                <button type="button" onclick="applyQuickSchedule('09:00', '18:00')" class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-white border border-indigo-200 rounded-full hover:bg-indigo-50 shadow-sm transition-colors">
                                    9:00 AM - 6:00 PM (M-F)
                                </button>
                                <button type="button" onclick="clearAllDays()" class="px-3 py-1.5 text-xs font-medium text-red-700 bg-white border border-red-200 rounded-full hover:bg-red-50 shadow-sm transition-colors">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Day</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Start Time</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">End Time</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                            foreach ($days as $day):
                                $lowerDay = strtolower($day);
                                ?>
                                <tr id="row_<?php echo $lowerDay; ?>" class="transition-colors duration-200 hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mr-3 text-xs font-bold">
                                            <?php echo substr($day, 0, 1); ?>
                                        </div>
                                        <?php echo $day; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="time" name="<?php echo $lowerDay; ?>_start" id="<?php echo $lowerDay; ?>_start" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md transition-colors">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="time" name="<?php echo $lowerDay; ?>_end" id="<?php echo $lowerDay; ?>_end" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md transition-colors">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="rest_<?php echo $lowerDay; ?>" onchange="toggleRestDay('<?php echo $lowerDay; ?>')" class="sr-only peer">
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                            <span class="ms-3 text-sm font-medium text-gray-500 peer-checked:text-indigo-600">Rest Day</span>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-between items-center">
                        <button type="button" onclick="openCopyModal()" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-copy mr-2"></i> Copy to Others
                        </button>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Standard Schedule
                        </button>
                    </div>
                    <div id="schedule-message" class="mt-4 hidden p-3 rounded-lg text-center"></div>
                </form>
            </div>
        </div>

        <!-- Copy Schedule Modal -->
        <div id="copyScheduleModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-copy text-indigo-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Copy Schedule to Other Employees
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Select employees to apply the current schedule to. This will overwrite their existing standard schedule.
                                    </p>
                                    <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2">
                                        <?php foreach ($employees as $emp): ?>
                                            <div class="flex items-center mb-2">
                                                <input type="checkbox" name="target_employees[]" value="<?php echo $emp['employee_id']; ?>" class="target-emp-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                <label class="ml-2 block text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['department'] . ')'); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-2 flex items-center">
                                        <input type="checkbox" id="selectAllTargets" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="selectAllTargets" class="ml-2 block text-sm text-gray-900 font-medium">Select All</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="submitCopySchedule()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Copy Schedule
                        </button>
                        <button type="button" onclick="closeCopyModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: DEDICATED OFF DAYS (New Feature) -->
        <div id="offDaysSection" class="bg-white rounded-xl shadow-lg overflow-hidden hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-orange-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-orange-800">Dedicated Off Days (Effectivity Rules)</h2>
                    <p class="text-sm text-orange-600">Set specific days of the week as "Off Days" starting from a future date.</p>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Add Rule Form -->
                <div class="lg:col-span-1 bg-gray-50 p-5 rounded-lg border border-gray-200 h-fit">
                    <h3 class="font-semibold text-gray-700 mb-4">Add New Rule</h3>
                    <form id="addOffDayForm" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="employee_id" id="off_day_employee_id">

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Day of Week</label>
                            <select name="day_of_week" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Mon">Monday</option>
                                <option value="Tue">Tuesday</option>
                                <option value="Wed">Wednesday</option>
                                <option value="Thu">Thursday</option>
                                <option value="Fri">Friday</option>
                                <option value="Sat">Saturday</option>
                                <option value="Sun">Sunday</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Effective Date</label>
                            <input type="date" name="effective_date" required class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Rule applies on or after this date.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Reason (Optional)</label>
                            <input type="text" name="reason" placeholder="e.g. Rest Day Change" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            Add Off Day Rule
                        </button>
                        <div id="off-day-message" class="hidden text-xs text-center p-2 rounded mt-2"></div>
                    </form>
                </div>

                <!-- List of Active Rules -->
                <div class="lg:col-span-2">
                    <h3 class="font-semibold text-gray-700 mb-4">Active Off Day Rules</h3>
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Day</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective From</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                            </thead>
                            <tbody id="offDaysTableBody" class="bg-white divide-y divide-gray-200">
                            <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const employeeSelect = document.getElementById('schedule_employee_id');

        // Standard Schedule Elements
        const scheduleForm = document.getElementById('standardScheduleForm');
        const formEmployeeId = document.getElementById('form_employee_id');
        const scheduleMessage = document.getElementById('schedule-message');

        // Off Day Elements
        const offDaysSection = document.getElementById('offDaysSection');
        const offDayEmployeeId = document.getElementById('off_day_employee_id');
        const addOffDayForm = document.getElementById('addOffDayForm');
        const offDaysTableBody = document.getElementById('offDaysTableBody');
        const offDayMessage = document.getElementById('off-day-message');

        // --- 1. EMPLOYEE SELECTION HANDLER ---
        employeeSelect.addEventListener('change', function() {
            const empId = this.value;

            if (empId) {
                // Show Forms
                scheduleForm.classList.remove('hidden');
                offDaysSection.classList.remove('hidden');

                // Set ID in hidden inputs
                formEmployeeId.value = empId;
                offDayEmployeeId.value = empId;

                // Load Data
                fetchSchedule(empId);
                fetchOffDays(empId);
            } else {
                scheduleForm.classList.add('hidden');
                offDaysSection.classList.add('hidden');
            }
        });

        // --- 2. STANDARD SCHEDULE FUNCTIONS ---
        function toggleRestDay(day) {
            const checkbox = document.getElementById('rest_' + day);
            const isRest = checkbox.checked;
            const startInput = document.getElementById(day + '_start');
            const endInput = document.getElementById(day + '_end');
            const row = document.getElementById('row_' + day);

            if (isRest) {
                startInput.value = '';
                endInput.value = '';
                startInput.disabled = true;
                endInput.disabled = true;
                startInput.classList.add('bg-gray-100', 'text-gray-400');
                endInput.classList.add('bg-gray-100', 'text-gray-400');
                row.classList.add('bg-gray-50');
            } else {
                startInput.disabled = false;
                endInput.disabled = false;
                startInput.classList.remove('bg-gray-100', 'text-gray-400');
                endInput.classList.remove('bg-gray-100', 'text-gray-400');
                row.classList.remove('bg-gray-50');
            }
        }

        // Auto-uncheck Rest Day if user manually enters time
        function handleTimeInput(day) {
            const checkbox = document.getElementById('rest_' + day);
            if (checkbox.checked) {
                checkbox.checked = false;
                toggleRestDay(day);
            }
        }

        // Attach listeners to all time inputs
        document.addEventListener('DOMContentLoaded', () => {
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            days.forEach(day => {
                document.getElementById(day + '_start').addEventListener('input', () => handleTimeInput(day));
                document.getElementById(day + '_end').addEventListener('input', () => handleTimeInput(day));
            });
        });

        async function fetchSchedule(empId) {
            try {
                const response = await fetch(`api/get_standard_schedule.php?employee_id=${empId}`);
                const result = await response.json();

                // Reset form
                scheduleForm.reset();
                formEmployeeId.value = empId; // Re-set ID after reset

                const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

                // If no data or empty data, set all to Rest Day
                if (!result.success || !result.data || Object.keys(result.data).length === 0) {
                    days.forEach(day => {
                        document.getElementById('rest_' + day).checked = true;
                        toggleRestDay(day);
                    });
                    return;
                }

                days.forEach(day => {
                    const start = result.data[day + '_start'];
                    const end = result.data[day + '_end'];

                    // Check if valid time exists (not null, not empty, not 00:00:00)
                    const hasTime = start && end && start !== '00:00:00' && end !== '00:00:00';

                    if (hasTime) {
                        document.getElementById(day + '_start').value = start;
                        document.getElementById(day + '_end').value = end;
                        document.getElementById('rest_' + day).checked = false;
                    } else {
                        document.getElementById('rest_' + day).checked = true;
                    }
                    // Apply visual state based on the checkbox we just set
                    toggleRestDay(day);
                });

            } catch (error) {
                console.error('Error fetching schedule:', error);
            }
        }

        scheduleForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(scheduleForm);

            try {
                const response = await fetch('api/update_standard_schedule.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                scheduleMessage.textContent = result.message;
                scheduleMessage.className = result.success ? 'mt-4 p-3 rounded-lg text-center bg-green-100 text-green-700' : 'mt-4 p-3 rounded-lg text-center bg-red-100 text-red-700';
                scheduleMessage.classList.remove('hidden');
                setTimeout(() => scheduleMessage.classList.add('hidden'), 3000);

                // Refresh the Off Days list to show the newly created rules
                if (result.success) {
                    fetchOffDays(formEmployeeId.value);
                }
            } catch (error) {
                console.error('Error saving schedule:', error);
            }
        });


        // --- 3. DEDICATED OFF DAY FUNCTIONS (NEW) ---

        async function fetchOffDays(empId) {
            offDaysTableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">Loading...</td></tr>';

            try {
                const response = await fetch(`api/manage_off_days.php?action=fetch&employee_id=${empId}`);
                const result = await response.json();

                if (result.success) {
                    if (result.data.length === 0) {
                        offDaysTableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No specific off-day rules set.</td></tr>';
                    } else {
                        offDaysTableBody.innerHTML = result.data.map(rule => `
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${rule.day_of_week}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${rule.effective_date}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${rule.reason || '-'}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="deleteOffDay(${rule.id})" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    }
                } else {
                    offDaysTableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-sm text-red-500">Failed to load rules.</td></tr>';
                }
            } catch (error) {
                console.error('Error fetching off days:', error);
                offDaysTableBody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-sm text-red-500">System Error.</td></tr>';
            }
        }

        addOffDayForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addOffDayForm);

            // Manually append action since it might not be picked up if disabled
            if(!formData.has('action')) formData.append('action', 'add');

            try {
                const response = await fetch('api/manage_off_days.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                offDayMessage.textContent = result.message;
                offDayMessage.className = result.success ? 'text-xs text-center p-2 rounded mt-2 bg-green-100 text-green-700' : 'text-xs text-center p-2 rounded mt-2 bg-red-100 text-red-700';
                offDayMessage.classList.remove('hidden');

                if (result.success) {
                    addOffDayForm.reset();
                    // Re-populate ID hidden field
                    offDayEmployeeId.value = employeeSelect.value;
                    // Refresh Table
                    fetchOffDays(employeeSelect.value);
                }

                setTimeout(() => offDayMessage.classList.add('hidden'), 3000);
            } catch (error) {
                console.error('Error adding off day:', error);
            }
        });

        async function deleteOffDay(id) {
            if (!confirm('Are you sure you want to remove this off-day rule?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const response = await fetch('api/manage_off_days.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    fetchOffDays(employeeSelect.value);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting off day:', error);
            }
        }

        // --- 4. QUICK APPLY & COPY FUNCTIONS (NEW) ---

        function applyQuickSchedule(start, end) {
            const workDays = ['mon', 'tue', 'wed', 'thu', 'fri'];
            const restDays = ['sat', 'sun'];

            // Set working days
            workDays.forEach(day => {
                document.getElementById(day + '_start').value = start;
                document.getElementById(day + '_end').value = end;
                document.getElementById('rest_' + day).checked = false;
                toggleRestDay(day);
            });

            // Clear rest days (Sat, Sun) to ensure they are set as Rest Days
            restDays.forEach(day => {
                document.getElementById('rest_' + day).checked = true;
                toggleRestDay(day);
            });
        }

        function clearAllDays() {
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            days.forEach(day => {
                document.getElementById('rest_' + day).checked = true;
                toggleRestDay(day);
            });
        }

        // Modal Functions
        const copyModal = document.getElementById('copyScheduleModal');
        const selectAllCheckbox = document.getElementById('selectAllTargets');
        
        function openCopyModal() {
            // Check if an employee is selected first
            if (!employeeSelect.value) {
                alert('Please select a source employee first.');
                return;
            }
            copyModal.classList.remove('hidden');
        }

        function closeCopyModal() {
            copyModal.classList.add('hidden');
        }

        // Select All Logic
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.target-emp-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                });
            });
        }

        async function submitCopySchedule() {
            const sourceEmpId = employeeSelect.value;
            if (!sourceEmpId) return;

            // Get selected targets
            const selectedTargets = [];
            document.querySelectorAll('.target-emp-checkbox:checked').forEach(cb => {
                // Don't copy to self
                if (cb.value !== sourceEmpId) {
                    selectedTargets.push(cb.value);
                }
            });

            if (selectedTargets.length === 0) {
                alert('Please select at least one employee to copy the schedule to.');
                return;
            }

            if (!confirm(`Are you sure you want to copy this schedule to ${selectedTargets.length} employees? This will overwrite their existing standard schedules.`)) {
                return;
            }

            // Prepare data: We need the schedule data from the form
            const formData = new FormData(scheduleForm);
            // Add targets
            selectedTargets.forEach(id => formData.append('target_employee_ids[]', id));
            // Add source for reference (though form data has it)
            formData.append('source_employee_id', sourceEmpId);

            try {
                const response = await fetch('api/copy_standard_schedule.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeCopyModal();
                    // Uncheck all
                    document.querySelectorAll('.target-emp-checkbox').forEach(cb => cb.checked = false);
                    if(selectAllCheckbox) selectAllCheckbox.checked = false;
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error copying schedule:', error);
                alert('System error occurred while copying schedule.');
            }
        }


    </script>

<?php
include 'template/footer.php';
?>