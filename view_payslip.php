<?php
// FILENAME: employee/view_payslip.php

$pageTitle = 'Payslip Details';
include 'template/header.php'; // Handles session, auth, DB

$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
$payroll_id = $_GET['id'] ?? null;

if (!$payroll_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-xl shadow-xl'>Error: No payslip ID provided.</div>";
    include 'template/footer.php';
    exit;
}
?>

<div id="loading-container" class="bg-white p-8 rounded-xl shadow-xl text-center">
    <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
    <p class="mt-4 text-gray-600">Loading payslip details...</p>
</div>

<!-- Payslip View Card (Hidden until data loads) -->
<div id="payslip-container" class="bg-white p-8 rounded-xl shadow-xl max-w-4xl mx-auto hidden">
    <div class="flex justify-between items-center border-b pb-4 mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Payslip</h2>
        <a href="my_payslips.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 print:hidden">
            &larr; Back to History
        </a>
    </div>

    <!-- Employee and Period Info -->
    <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
        <div>
            <p class="text-gray-500">Employee Name:</p>
            <p class="font-semibold text-gray-800" id="employeeName"></p>
            <p class="text-gray-500">Job Title:</p>
            <p class="font-semibold text-gray-800" id="jobTitle"></p>
        </div>
        <div class="text-right">
            <p class="text-gray-500">Pay Period:</p>
            <p class="font-semibold text-gray-800" id="payPeriod"></p>
            <p class="text-gray-500">Processing Date:</p>
            <p class="font-semibold text-gray-800" id="processedDate"></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- 1. Earnings Column -->
        <div class="lg:col-span-1 border border-gray-200 rounded-lg p-4 bg-gray-50">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Earnings</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Pay Type/Rate:</span>
                    <span class="font-medium text-gray-800" id="payRateInfo"></span>
                </div>

                <!-- NEW: Total Payable Hours (Hourly Only) -->
                <div class="flex justify-between hidden" id="totalHoursInfo">
                    <span class="text-gray-600">Total Payable Hours:</span>
                    <span class="font-medium text-gray-800" id="totalHours"></span>
                </div>

                <!-- NEW: Paid Leave Days (Hourly Only) -->
                <div class="flex justify-between hidden" id="totalLeaveDaysInfo">
                    <span class="text-gray-600">Paid Leave Days:</span>
                    <span class="font-medium text-gray-800" id="totalLeaveDays"></span>
                </div>

                <!-- NEW: Overtime Info -->
                <div class="flex justify-between hidden" id="overtimeInfo">
                    <span class="text-gray-600">Overtime:</span>
                    <span class="font-medium text-gray-800" id="overtimeAmount"></span>
                </div>

                <!-- NEW: Allowance Breakdown -->
                <div id="allowanceBreakdown" class="border-t border-gray-200 pt-2 mt-2 space-y-2 hidden">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase">Allowances / Bonuses</h4>
                    <!-- Populated via JS -->
                </div>

                <!-- NEW: Basic Pay & Late/Absent Breakdown -->
                <div id="attendanceBreakdown" class="hidden border-t border-gray-200 pt-2 mt-2 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Basic / Expected Pay:</span>
                        <span class="font-medium text-gray-800" id="basicPay"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Less: Late / Absent:</span>
                        <span class="font-medium text-orange-600" id="lateAbsentDeduction"></span>
                    </div>
                </div>

                <!-- Gross Pay Total -->
                <div class="flex justify-between border-t border-gray-300 pt-3">
                    <span class="font-bold text-lg text-gray-800">GROSS PAY:</span>
                    <span class="font-bold text-lg text-green-700" id="grossPay"></span>
                </div>
            </div>
        </div>

        <!-- 2. Deductions Column -->
        <div class="lg:col-span-1 border border-red-300 rounded-lg p-4">
            <h3 class="text-xl font-semibold text-red-700 mb-4 border-b pb-2">Deductions</h3>
            <div id="deductionBreakdown" class="space-y-3">
                <!-- Deduction items populated by JS -->
            </div>

            <!-- Total Deductions -->
            <div class="flex justify-between border-t border-gray-300 pt-3 mt-3">
                <span class="font-bold text-lg text-gray-800">TOTAL DEDUCTIONS:</span>
                <span class="font-bold text-lg text-red-700" id="totalDeductions"></span>
            </div>
        </div>

        <!-- 3. Net Pay Final Summary -->
        <div class="lg:col-span-1 bg-indigo-600 rounded-lg p-6 text-white flex flex-col justify-center items-center text-center print:bg-indigo-600" style="-webkit-print-color-adjust: exact; print-color-adjust: exact;">
            <h3 class="text-2xl font-light mb-2">NET PAY</h3>
            <div class="font-extrabold text-5xl tracking-tight" id="netPay"></div>
            <p class="text-sm mt-4 opacity-80">Deposited on: <span id="payDate"></span></p>
        </div>
    </div>

    <div id="view-message" class="mt-8 hidden p-3 rounded-lg text-center"></div>

    <div class="mt-8 flex justify-center space-x-4 print:hidden">
        <!-- DOWNLOAD PDF BUTTON - Link is already correct -->
        <a id="downloadPdfButton" href="api/download_payslip.php?id=<?php echo htmlspecialchars($payroll_id); ?>" target="_blank"
           class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
            <i class="fas fa-file-pdf mr-2"></i> Download PDF
        </a>
        <!-- END DOWNLOAD PDF BUTTON -->
        <button onclick="window.print()" class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
            <i class="fas fa-print mr-2"></i> Print Payslip
        </button>
    </div>
</div>

<script>
    const payrollId = <?php echo json_encode($payroll_id); ?>;
    const currencySymbol = '<?php echo $currency_symbol; ?>';
    const hidePayRate = <?php echo (isset($_SESSION['settings']['hide_pay_rate_from_employee']) && $_SESSION['settings']['hide_pay_rate_from_employee'] == '1') ? 'true' : 'false'; ?>;
    const loadingContainer = document.getElementById('loading-container');

    const payslipContainer = document.getElementById('payslip-container');
    const deductionBreakdown = document.getElementById('deductionBreakdown');
    const viewMessage = document.getElementById('view-message');

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        // Handle MySQL DATETIME (YYYY-MM-DD HH:MM:SS) - strip time part if present
        if (dateString.includes(' ')) {
            dateString = dateString.split(' ')[0];
        }

        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        // Append T00:00:00 to ensure it's treated as local date (not UTC) to prevent off-by-one errors
        return new Date(dateString + 'T00:00:00').toLocaleDateString(undefined, options);
    }

    function formatCurrency(amount) {
        return currencySymbol + parseFloat(amount).toFixed(2);
    }

    function showMessage(message, className) {
        viewMessage.textContent = message;
        viewMessage.className = `mt-8 p-3 rounded-lg text-center ${className}`;
        viewMessage.classList.remove('hidden');
    }

    async function loadPayslipDetails() {
        loadingContainer.classList.remove('hidden');
        payslipContainer.classList.add('hidden');

        let result;

        try {
            const response = await fetch(`api/get_payslip_details.php?id=${payrollId}`);

            if (!response.ok) {
                let errorText = `HTTP Error: ${response.status} ${response.statusText}`;

                try {
                    const errorJson = await response.json();
                    errorText = errorJson.message || errorText;
                } catch (e) {
                    const rawText = await response.text();
                    // Fallback in case of non-JSON output (like PHP error dumped)
                    console.error("Raw API response:", rawText);
                    errorText = `API returned non-JSON data. Server Error? Raw start: ${rawText.substring(0, 100)}...`;
                }

                throw new Error(errorText);
            }

            try {
                result = await response.json();
            } catch (e) {
                const rawText = await response.text();
                throw new Error(`Invalid JSON response from API. Raw output: ${rawText.substring(0, 100)}...`);
            }

            if (result.success) {
                const data = result.data.payslip;
                const deductions = result.data.deduction_breakdown;
                const allowances = result.data.allowance_breakdown || [];

                // --- Populate General Info ---
                document.getElementById('employeeName').textContent = data.first_name + ' ' + data.last_name;
                document.getElementById('jobTitle').textContent = data.job_title;
                document.getElementById('payPeriod').textContent = `${formatDate(data.pay_period_start)} - ${formatDate(data.pay_period_end)}`;
                document.getElementById('processedDate').textContent = formatDate(data.created_at);

                // --- Populate Earnings ---
                const payType = data.pay_type_used || data.pay_type; // Use the stored value
                const payRate = parseFloat(data.pay_rate_used || data.pay_rate);

                let payRateSuffix = '';
                if (payType === 'Hourly') payRateSuffix = ' / hr';
                else if (payType === 'Daily') payRateSuffix = ' / day';
                else if (payType === 'Fix Rate') payRateSuffix = ' / period';


                if (hidePayRate) {
                    document.getElementById('payRateInfo').textContent = 'Hidden';
                } else {
                    document.getElementById('payRateInfo').textContent = `${formatCurrency(payRate)} ${payRateSuffix}`;
                }

                
                // --- Handle Allowances ---
                const allowanceContainer = document.getElementById('allowanceBreakdown');
                let totalAllowances = 0;
                allowanceContainer.innerHTML = '<h4 class="text-xs font-semibold text-gray-500 uppercase">Allowances / Bonuses</h4>';
                
                if (allowances.length > 0) {
                    allowanceContainer.classList.remove('hidden');
                    allowances.forEach(allowance => {
                        const row = document.createElement('div');
                        row.className = 'flex justify-between text-sm';
                        // Check type from breakdown or data
                        const typeLabel = allowance.type === 'Percentage' ? `(${parseFloat(allowance.value).toFixed(1)}%)` : '';
                        row.innerHTML = `
                            <span class="text-gray-600">${allowance.name} ${typeLabel}:</span>
                            <span class="font-medium text-green-600">+ ${formatCurrency(allowance.amount)}</span>
                        `;
                        allowanceContainer.appendChild(row);
                        totalAllowances += parseFloat(allowance.amount);
                    });
                } else {
                    allowanceContainer.classList.add('hidden');
                }

                // --- NEW: Handle Attendance Deductions (Late/Absent) ---
                const grossPay = parseFloat(data.gross_pay);
                // Correct Basic Pay calculation: Gross - Allowances + Deduction
                // Wait, Basic Pay should be what they WOULDA got if no late.
                // Gross = (Basic - Late) + Allowances.
                // So Basic = Gross - Allowances + Late.
                const storedAllowances = parseFloat(data.allowances || 0); // Use stored total if available, else 0
                const attendanceDeductions = parseFloat(data.attendance_deductions || 0);
                
                // Adjusted Gross (Base Pay actually received)
                const basePayReceived = grossPay - storedAllowances;
                
                if (attendanceDeductions > 0 || storedAllowances > 0) {
                     // We show breakdowns
                     const potentialBasicPay = basePayReceived + attendanceDeductions;
                     
                     // Update the Basic Pay label to clarify
                     document.getElementById('basicPay').textContent = hidePayRate ? 'Hidden' : formatCurrency(potentialBasicPay);
                     document.getElementById('lateAbsentDeduction').textContent = hidePayRate ? 'Hidden' : `-${formatCurrency(attendanceDeductions)}`;
                     
                     if (attendanceDeductions > 0) {
                        document.getElementById('attendanceBreakdown').classList.remove('hidden');
                     } else {
                        // If no deduction but we have allowances, we might still want to show "Base Pay"?
                        // The current UI hides 'attendanceBreakdown' if no deduction.
                        // Implies 'Basic Pay' line is hidden.
                        // So we only see Allowances and Gross Pay.
                        // That's fine.
                        document.getElementById('attendanceBreakdown').classList.add('hidden');
                     }
                } else {
                     document.getElementById('attendanceBreakdown').classList.add('hidden');
                }

                document.getElementById('grossPay').textContent = hidePayRate ? 'Hidden' : formatCurrency(grossPay);
                document.getElementById('netPay').textContent = hidePayRate ? 'Hidden' : formatCurrency(data.net_pay);
                document.getElementById('totalDeductions').textContent = hidePayRate ? 'Hidden' : formatCurrency(data.deductions);

                // Estimate the pay date as the end date + 1 day
                const payDateDt = new Date(data.pay_period_end + 'T00:00:00'); // Ensure UTC for consistent date math
                payDateDt.setDate(payDateDt.getDate() + 1);
                document.getElementById('payDate').textContent = formatDate(payDateDt.toISOString().slice(0, 10));

                // Hours Info (Only visible for Hourly/Daily)
                const totalHoursInfo = document.getElementById('totalHoursInfo');
                const totalLeaveDaysInfo = document.getElementById('totalLeaveDaysInfo');

                if (payType === 'Hourly' || payType === 'Daily') {
                    // Use the newly stored payable hours and leave days
                    const payableHours = parseFloat(data.total_payable_hours);
                    const leaveDays = parseInt(data.total_paid_leave_days);

                    document.getElementById('totalHours').textContent = `${payableHours.toFixed(2)} hrs`;
                    totalHoursInfo.classList.remove('hidden');

                    document.getElementById('totalLeaveDays').textContent = `${leaveDays} day(s)`;
                    if (leaveDays > 0) {
                        totalLeaveDaysInfo.classList.remove('hidden');
                    } else {
                        totalLeaveDaysInfo.classList.add('hidden');
                    }
                } else {
                    totalHoursInfo.classList.add('hidden');
                    totalLeaveDaysInfo.classList.add('hidden');
                }

                // Overtime Logic
                const overtimeHours = parseFloat(data.overtime_hours || 0);
                const overtimePay = parseFloat(data.overtime_pay || 0);
                const overtimeInfo = document.getElementById('overtimeInfo');

                if (overtimeHours > 0) {
                    // document.getElementById('overtimeAmount').textContent = `${overtimeHours.toFixed(2)} hrs (${formatCurrency(overtimePay)})`; // formatCurrency not available in JS block scope? It is global function in utils or header?
                    // Wait, formatCurrency is likely defined in this file or header.
                    // Checking line 250 in read_file output: yes, formatCurrency(potentialBasicPay) is used.
                    // But wait, formatCurrency is NOT defined in the JS I read. Check earlier read.
                    // It is defined in reports.php but maybe not here.
                    // Let's check if formatCurrency is available.
                    // It's used in line 301: formatCurrency(grossPay). So it must be available.
                    
                    const formattedOTPay = (typeof formatCurrency === 'function') ? formatCurrency(overtimePay) : '$' + overtimePay.toFixed(2);
                    document.getElementById('overtimeAmount').textContent = `${overtimeHours.toFixed(2)} hrs (${formattedOTPay})`;
                    overtimeInfo.classList.remove('hidden');
                } else {
                    overtimeInfo.classList.add('hidden');
                }

                // --- Populate Deductions ---
                deductionBreakdown.innerHTML = '';
                if (deductions.length > 0) {
                    deductions.forEach(deduction => {
                        const deductionRow = document.createElement('div');
                        deductionRow.className = 'flex justify-between';

                        let deductionLabel = `${deduction.name}`;
                        if (deduction.type === 'Percentage') {
                            deductionLabel += ` (${deduction.value.toFixed(1)}%)`;
                        } else if (deduction.type === 'System Fixed') {
                            deductionLabel += ` (CA/Vale)`;
                        } else {
                            deductionLabel += ` (Fixed)`;
                        }


                        deductionRow.innerHTML = `
                            <span class="text-gray-600">${deductionLabel}:</span>
                            <span class="font-medium text-red-600">${hidePayRate ? 'Hidden' : '- ' + formatCurrency(deduction.amount)}</span>
                        `;
                        deductionBreakdown.appendChild(deductionRow);
                    });
                } else {
                    deductionBreakdown.innerHTML = '<p class="text-gray-500 italic text-center">No active deductions applied.</p>';
                }

                // Show the payslip
                payslipContainer.classList.remove('hidden');

            } else {
                // API returned success: false
                console.error("API Failure:", result.message);
                showMessage(`Failed to load payslip: ${result.message}`, 'bg-red-100 text-red-700');
            }

        } catch (error) {
            // Network or parsing error
            console.error('Request Error:', error.message);
            showMessage(`Error loading data. Details: ${error.message}`, 'bg-red-100 text-red-700');
        } finally {
            // CRITICAL: Ensure the loading spinner is always hidden
            loadingContainer.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', loadPayslipDetails);
</script>

<?php
include 'template/footer.php';
?>
