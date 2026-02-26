<?php
// FILENAME: employee/reports.php
$pageTitle = 'Generate Reports';
include 'template/header.php'; // Handles session, auth, DB

// --- Page-Specific Role Check ---
if (!in_array($_SESSION['role'], ['HR Admin', 'Super Admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Get currency symbol for display
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';

// Function to get all departments for the filter
function getAllDepartments($pdo) {
    try {
        $stmt = $pdo->query("SELECT department_name FROM departments ORDER BY department_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching departments for reports: " . $e->getMessage());
        return [];
    }
}
$departments = getAllDepartments($pdo);

// Default date range (e.g., last 30 days)
$default_end_date = date('Y-m-d');
$default_start_date = date('Y-m-d', strtotime('-30 days'));
?>

<div class="bg-white p-8 rounded-xl shadow-xl print-container"> <!-- Added print-container class -->
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-chart-line text-indigo-600 mr-3"></i>
        <span>Advanced Reporting</span>
    </h2>

    <!-- Report Selection Form -->
    <form id="reportForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end border p-6 rounded-lg bg-gray-50 mb-8 print-hide"> <!-- Added print-hide class -->
        <div>
            <label for="report_type" class="block text-sm font-medium text-gray-700">Report Type</label>
            <select id="report_type" name="report_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="" disabled selected>-- Select Report --</option>
                <option value="payroll_summary">Payroll Summary</option>
                <option value="deduction_report">Deduction Report</option>
                <option value="attendance_summary">Attendance Summary</option>
                <option value="leave_balance">Leave Balance Report</option>
            </select>
        </div>
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $default_start_date; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $default_end_date; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="department_filter" class="block text-sm font-medium text-gray-700">Department</label>
            <select id="department_filter" name="department" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="all">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>">
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" id="generateBtn" class="w-full md:w-auto px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                <i class="fas fa-sync-alt mr-2"></i> Generate Report
            </button>
        </div>
    </form>
    <div id="form-message" class="mt-4 hidden p-3 rounded-lg text-center print-hide"></div> <!-- Added print-hide class -->

    <!-- Report Display Area -->
    <div id="reportDisplay" class="mt-8 border-t pt-6 hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 id="reportTitle" class="text-xl font-semibold text-gray-800">Report Results</h3>
            <div class="flex space-x-2 print-hide">
                <button id="printAllDetailsBtn" class="hidden px-4 py-2 border border-indigo-300 rounded-lg text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-copy mr-2"></i> Print All Details
                </button>
                <button onclick="window.print()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fas fa-print mr-2"></i> Print Summary
                </button>
            </div>
        </div>

        <!-- NEW: Chart Container -->
        <div id="reportChartContainer" class="mb-8 p-4 border rounded-lg bg-gray-50 print-no-break" style="max-height: 400px;">
            <canvas id="reportChart"></canvas>
        </div>
        <!-- END NEW -->

        <h4 class="text-lg font-semibold text-gray-700 mb-2">Detailed Data</h4>
        <div id="reportContent" class="overflow-x-auto print-no-break">
            <!-- Report table will be generated here -->
            <p class="text-center text-gray-500 p-6">Select report options and click "Generate Report".</p>
        </div>
    </div>
</div>

<script>
    const reportForm = document.getElementById('reportForm');
    const formMessage = document.getElementById('form-message');
    const reportDisplay = document.getElementById('reportDisplay');
    const reportTitle = document.getElementById('reportTitle');
    const reportContent = document.getElementById('reportContent');
    const generateBtn = document.getElementById('generateBtn');
    const printAllDetailsBtn = document.getElementById('printAllDetailsBtn'); // NEW
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;

    // --- NEW: Chart.js instance ---
    const chartCanvas = document.getElementById('reportChart');
    const chartContainer = document.getElementById('reportChartContainer');
    let currentReportChart = null; // To store the Chart.js instance

    function showMessage(messageBox, message, className, autoHide = true) {
        messageBox.textContent = message;
        messageBox.className = `p-3 rounded-lg text-center ${className}`;
        messageBox.classList.remove('hidden');
        if (autoHide) {
            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 5000);
        }
    }

    function formatCurrency(amount) {
        if (amount === null || isNaN(amount)) return 'N/A';
        return currencySymbol + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatHours(hours) {
        if (hours === null || isNaN(hours)) return 'N/A';
        return parseFloat(hours).toFixed(2) + ' hrs';
    }

    function formatDays(days) {
        if (days === null || isNaN(days)) return 'N/A';
        return parseFloat(days).toFixed(1) + ' days';
    }


    reportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        reportDisplay.classList.add('hidden');
        reportContent.innerHTML = '<p class="text-center text-gray-500 p-6"><i class="fas fa-spinner fa-spin mr-2"></i> Generating report...</p>';
        chartContainer.classList.add('hidden'); // Hide chart container initially
        reportDisplay.classList.remove('hidden'); // Show display area with loading indicator
        formMessage.classList.add('hidden');
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';

        // Destroy old chart instance if it exists
        if (currentReportChart) {
            currentReportChart.destroy();
            currentReportChart = null;
        }

        const formData = new FormData(reportForm);
        const params = new URLSearchParams(formData).toString();

        try {
            const response = await fetch(`api/generate_report.php?${params}`);
            const result = await response.json();

            if (result.success) {
                renderReport(result.report_title, result.headers, result.data, result.report_type); // Pass report_type
                // --- NEW: Call renderChart ---
                if (result.data && result.data.length > 0) {
                    renderChart(result.report_type, result.data);
                    chartContainer.classList.remove('hidden');
                } else {
                    chartContainer.classList.add('hidden'); // Hide chart if no data
                }
                // --- END NEW ---
            } else {
                showMessage(formMessage, result.message, 'bg-red-100 text-red-700', false);
                reportContent.innerHTML = '<p class="text-center text-red-500 p-6">Failed to generate report.</p>';
            }
        } catch (error) {
            console.error('Error generating report:', error);
            showMessage(formMessage, 'Network error or invalid response from server.', 'bg-red-100 text-red-700', false);
            reportContent.innerHTML = '<p class="text-center text-red-500 p-6">An error occurred.</p>';
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Generate Report';
        }
    });

    function renderReport(title, headers, data, reportType) {
        reportTitle.textContent = title;
        reportContent.innerHTML = ''; // Clear previous content or loading

        // --- NEW: Handle Print All Details Button ---
        if (reportType === 'attendance_summary') {
            printAllDetailsBtn.classList.remove('hidden');
            printAllDetailsBtn.onclick = () => {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const department = document.getElementById('department_filter').value;
                window.open(`print_all_attendance.php?start_date=${startDate}&end_date=${endDate}&department=${department}`, '_blank');
            };
        } else {
            printAllDetailsBtn.classList.add('hidden');
        }
        // --- END NEW ---

        if (!data || data.length === 0) {
            reportContent.innerHTML = '<p class="text-center text-gray-500 p-6">No data found for the selected criteria.</p>';
            return;
        }

        // Add Action header for Attendance Summary
        if (reportType === 'attendance_summary') {
            headers.push('Action');
        }

        const table = document.createElement('table');
        table.className = 'min-w-full divide-y divide-gray-200 border';

        // Table Header
        const thead = document.createElement('thead');
        thead.className = 'bg-gray-100';
        const headerRow = document.createElement('tr');
        headers.forEach(headerText => {
            const th = document.createElement('th');
            th.scope = 'col';
            th.className = 'px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider';
            th.textContent = headerText;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Table Body
        const tbody = document.createElement('tbody');
        tbody.className = 'bg-white divide-y divide-gray-200';
        data.forEach(rowData => {
            const tr = document.createElement('tr');
            headers.forEach(header => {
                const td = document.createElement('td');
                td.className = 'px-4 py-3 whitespace-nowrap text-sm text-gray-700';

                if (header === 'Action' && reportType === 'attendance_summary') {
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    
                    const div = document.createElement('div');
                    div.className = 'flex space-x-3';

                    const viewBtn = document.createElement('a');
                    viewBtn.href = `view_employee_logs.php?employee_id=${rowData.employee_id}&start_date=${startDate}&end_date=${endDate}`;
                    viewBtn.target = '_blank';
                    viewBtn.className = 'text-indigo-600 hover:text-indigo-900 font-medium flex items-center';
                    viewBtn.innerHTML = '<i class="fas fa-eye mr-1"></i> View';
                    
                    const printBtn = document.createElement('a');
                    printBtn.href = `view_employee_logs.php?employee_id=${rowData.employee_id}&start_date=${startDate}&end_date=${endDate}&auto_print=true`;
                    printBtn.target = '_blank';
                    printBtn.className = 'text-gray-600 hover:text-gray-900 font-medium flex items-center';
                    printBtn.innerHTML = '<i class="fas fa-print mr-1"></i> Print';

                    div.appendChild(viewBtn);
                    div.appendChild(printBtn);
                    td.appendChild(div);
                } else {
                    const key = header.toLowerCase().replace(/ /g, '_'); // Simple key generation
                    let cellValue = rowData[key] ?? rowData[header] ?? 'N/A'; // Try matching key or header text

                    // Apply formatting based on report type and header
                    if (reportType === 'payroll_summary') {
                        if (key.includes('gross') || key.includes('deductions') || key.includes('net')) {
                            cellValue = formatCurrency(cellValue);
                        }
                    } else if (reportType === 'attendance_summary') {
                        if (key.includes('hours')) {
                            cellValue = formatHours(cellValue);
                        }
                    } else if (reportType === 'leave_balance') {
                        if (key.includes('days')) {
                            cellValue = formatDays(cellValue);
                        }
                    }
                    td.textContent = cellValue;
                }
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        reportContent.appendChild(table);
        reportDisplay.classList.remove('hidden');
    }

    // --- NEW: Chart Rendering Function ---
    function renderChart(reportType, data) {
        if (currentReportChart) {
            currentReportChart.destroy();
        }

        const ctx = chartCanvas.getContext('2d');
        let chartConfig = {};

        try {
            switch (reportType) {
                case 'payroll_summary':
                    chartConfig = {
                        type: 'bar',
                        data: {
                            labels: data.map(d => d.department),
                            datasets: [
                                {
                                    label: 'Total Gross Pay',
                                    data: data.map(d => d.total_gross_pay),
                                    backgroundColor: 'rgba(59, 130, 246, 0.7)', // blue-500
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Total Net Pay',
                                    data: data.map(d => d.total_net_pay),
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)', // green-500
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { callback: (value) => currencySymbol + value.toLocaleString() } },
                                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }
                            },
                            plugins: { title: { display: true, text: 'Payroll Summary by Department' } }
                        }
                    };
                    break;

                case 'attendance_summary':
                    // Limit to top 15 employees for readability
                    // *** FIX: Corrected variable name ***
                    const slicedData = data.slice(0, 15);
                    chartConfig = {
                        type: 'bar',
                        data: {
                            labels: slicedData.map(d => d.employee_name),
                            datasets: [{
                                label: 'Total Recorded Hours',
                                data: slicedData.map(d => d.total_recorded_hours),
                                backgroundColor: 'rgba(234, 179, 8, 0.7)', // yellow-500
                                borderColor: 'rgba(234, 179, 8, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { callback: (value) => value + ' hrs' } },
                                x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }
                            },
                            plugins: { title: { display: true, text: 'Attendance Summary (Top 15)' } }
                        }
                    };
                    break;

                case 'leave_balance':
                    // Limit to top 15 employees
                    // *** FIX: Corrected variable name ***
                    const slicedLeaveData = data.slice(0, 15);
                    chartConfig = {
                        type: 'bar',
                        data: {
                            labels: slicedLeaveData.map(d => d.employee_name),
                            datasets: [
                                {
                                    label: 'Vacation Available',
                                    data: slicedLeaveData.map(d => d.vacation_available),
                                    backgroundColor: 'rgba(59, 130, 246, 0.7)', // blue-500
                                    stack: 'vacation'
                                },
                                {
                                    label: 'Vacation Used',
                                    data: slicedLeaveData.map(d => d.vacation_used),
                                    backgroundColor: 'rgba(107, 114, 128, 0.4)', // gray-500
                                    stack: 'vacation'
                                },
                                {
                                    label: 'Sick Available',
                                    data: slicedLeaveData.map(d => d.sick_available),
                                    backgroundColor: 'rgba(234, 179, 8, 0.7)', // yellow-500
                                    stack: 'sick'
                                },
                                {
                                    label: 'Sick Used',
                                    data: slicedLeaveData.map(d => d.sick_used),
                                    backgroundColor: 'rgba(107, 114, 128, 0.4)', // gray-500
                                    stack: 'sick'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { stacked: true, ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                                y: { stacked: true, beginAtZero: true, ticks: { callback: (value) => value + ' days' } }
                            },
                            plugins: { title: { display: true, text: 'Leave Balances (Top 15)' } }
                        }
                    };
                    break;

                default:
                    chartContainer.classList.add('hidden');
                    return; // Don't create a chart
            }

            currentReportChart = new Chart(ctx, chartConfig);
            chartContainer.classList.remove('hidden');

        } catch (e) {
            console.error("Chart.js rendering error: ", e);
            chartContainer.innerHTML = `<p class="text-red-500 text-center">Error rendering chart.</p>`;
            chartContainer.classList.remove('hidden');
        }
    }
    // --- END NEW ---

</script>

<?php
include 'template/footer.php';
?>

