<?php
$file = __DIR__ . '/view_payslip.php';
$content = file_get_contents($file);

// 1. Add Allowance Section to HTML
// Insert before #attendanceBreakdown
$search1 = "                <!-- NEW: Basic Pay & Late/Absent Breakdown -->
                <div id=\"attendanceBreakdown\" class=\"hidden border-t border-gray-200 pt-2 mt-2 space-y-2\">";
$insert1 = "                <!-- NEW: Allowance Breakdown -->
                <div id=\"allowanceBreakdown\" class=\"border-t border-gray-200 pt-2 mt-2 space-y-2 hidden\">
                    <h4 class=\"text-xs font-semibold text-gray-500 uppercase\">Allowances / Bonuses</h4>
                    <!-- Populated via JS -->
                </div>

                <!-- NEW: Basic Pay & Late/Absent Breakdown -->
                <div id=\"attendanceBreakdown\" class=\"hidden border-t border-gray-200 pt-2 mt-2 space-y-2\">";

$content = str_replace($search1, $insert1, $content);

// 2. Update JS to handle Allowances
// Insert after fetching result
$search2 = "const deductions = result.data.deduction_breakdown;";
$insert2 = "const deductions = result.data.deduction_breakdown;
                const allowances = result.data.allowance_breakdown || [];";
$content = str_replace($search2, $insert2, $content);

// 3. Populate Allowances in JS
// Insert before Attendance Breakdown Logic
$search3 = "// --- NEW: Handle Attendance Deductions (Late/Absent) ---
                const grossPay = parseFloat(data.gross_pay);";

$insert3 = "// --- Handle Allowances ---
                const allowanceContainer = document.getElementById('allowanceBreakdown');
                let totalAllowances = 0;
                allowanceContainer.innerHTML = '<h4 class=\"text-xs font-semibold text-gray-500 uppercase\">Allowances / Bonuses</h4>';
                
                if (allowances.length > 0) {
                    allowanceContainer.classList.remove('hidden');
                    allowances.forEach(allowance => {
                        const row = document.createElement('div');
                        row.className = 'flex justify-between text-sm';
                        // Check type from breakdown or data
                        const typeLabel = allowance.type === 'Percentage' ? `(\${parseFloat(allowance.value).toFixed(1)}%)` : '';
                        row.innerHTML = `
                            <span class=\"text-gray-600\">\${allowance.name} \${typeLabel}:</span>
                            <span class=\"font-medium text-green-600\">+ \${formatCurrency(allowance.amount)}</span>
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
                     document.getElementById('lateAbsentDeduction').textContent = hidePayRate ? 'Hidden' : `-\${formatCurrency(attendanceDeductions)}`;
                     
                     if (attendanceDeductions > 0) {
                        document.getElementById('attendanceBreakdown').classList.remove('hidden');
                     } else {
                        // If no deduction but we have allowances, we might still want to show \"Base Pay\"?
                        // The current UI hides 'attendanceBreakdown' if no deduction.
                        // Implies 'Basic Pay' line is hidden.
                        // So we only see Allowances and Gross Pay.
                        // That's fine.
                        document.getElementById('attendanceBreakdown').classList.add('hidden');
                     }
                } else {
                     document.getElementById('attendanceBreakdown').classList.add('hidden');
                }";

// Replace the original attendance logic block carefully
// I need to match the original block start to end of if/else
// The original code was:
/*
                // --- NEW: Handle Attendance Deductions (Late/Absent) ---
                const grossPay = parseFloat(data.gross_pay);
                const attendanceDeductions = parseFloat(data.attendance_deductions || 0);
                
                if (attendanceDeductions > 0) {
                    const basicPay = grossPay + attendanceDeductions;
                    document.getElementById('basicPay').textContent = hidePayRate ? 'Hidden' : formatCurrency(basicPay);
                    document.getElementById('lateAbsentDeduction').textContent = hidePayRate ? 'Hidden' : `-${formatCurrency(attendanceDeductions)}`;
                    document.getElementById('attendanceBreakdown').classList.remove('hidden');
                } else {
                    document.getElementById('attendanceBreakdown').classList.add('hidden');
                }
*/
$search4 = "// --- NEW: Handle Attendance Deductions (Late/Absent) ---
                const grossPay = parseFloat(data.gross_pay);
                const attendanceDeductions = parseFloat(data.attendance_deductions || 0);
                
                if (attendanceDeductions > 0) {
                    const basicPay = grossPay + attendanceDeductions;
                    document.getElementById('basicPay').textContent = hidePayRate ? 'Hidden' : formatCurrency(basicPay);
                    document.getElementById('lateAbsentDeduction').textContent = hidePayRate ? 'Hidden' : `-\${formatCurrency(attendanceDeductions)}`;
                    document.getElementById('attendanceBreakdown').classList.remove('hidden');
                } else {
                    document.getElementById('attendanceBreakdown').classList.add('hidden');
                }";

$content = str_replace($search4, $insert3, $content);

file_put_contents($file, $content);
echo "Updated view_payslip.php with allowance display logic.\n";
?>