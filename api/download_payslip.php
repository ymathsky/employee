<?php
// FILENAME: employee/api/download_payslip.php
session_start();

// --- NEW: Include Composer's Autoloader ---
// This file gives us access to all the libraries installed via Composer, including Dompdf.
require_once __DIR__ . '/../vendor/autoload.php';

// --- NEW: Import the Dompdf classes ---
use Dompdf\Dompdf;
use Dompdf\Options;

// --- DUMMY PDF CLASS DEFINITION ---
// We replace the old dummy class with the real implementation.

class PdfGenerator {
    public static function streamHtmlAsPdf($html, $filename) {
        // --- REAL WORLD IMPLEMENTATION ---

        // 1. Set options for Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true); // Use the modern HTML5 parser
        $options->set('isRemoteEnabled', true); // Allow loading images, fonts, etc. (though we don't use them yet)
        $options->set('defaultFont', 'sans-serif'); // Set a default font

        // 2. Instantiate Dompdf with our options
        $dompdf = new Dompdf($options);

        // 3. Load the HTML content
        $dompdf->loadHtml($html);

        // 4. Set the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // 5. Render the HTML as PDF
        $dompdf->render();

        // 6. Stream the file to the browser for download
        // "Attachment" => true forces a download. Set to false to view in browser.
        $dompdf->stream($filename, ["Attachment" => true]);

        exit; // Stop script execution after PDF is sent
    }
}
// --- END PDF CLASS REPLACEMENT ---


// Basic check for login
if (!isset($_SESSION['user_id'])) { die("Unauthorized Access."); }

require_once 'db_connect.php';
require_once __DIR__ . '/../config/utils.php'; // Required for logging, date math helpers potentially

$payroll_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '$';
$company_name = $_SESSION['settings']['company_name'] ?? 'The Company';

if (empty($payroll_id)) { die("Error: No Payslip ID provided."); }


// Function to fetch and prepare all payslip data
function fetchPayslipData($pdo, $payroll_id, $user_id, $user_role) {
    // Fetch the main payroll record
    $sql = "SELECT p.*, e.first_name, e.last_name, e.job_title, p.pay_type_used, p.pay_rate_used, p.total_payable_hours, p.total_paid_leave_days
             FROM payroll p JOIN employees e ON p.employee_id = e.employee_id
             WHERE p.payroll_id = ?";
    $stmt = $pdo->prepare($sql); $stmt->execute([$payroll_id]); $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payslip) { return ['error' => 'Payslip not found.']; }

    // Authorization Check
    $is_admin_or_manager = in_array($user_role, ['HR Admin', 'Super Admin', 'Manager']);
    if ($payslip['employee_id'] != $user_id && !$is_admin_or_manager) {
        log_action($pdo, $user_id, 'PAYSLIP_DOWNLOAD_DENIED', "EID {$user_id} denied download PID {$payroll_id}.");
        return ['error' => 'Forbidden: Cannot view this payslip.'];
    }

    // Log successful access
    log_action($pdo, $user_id, 'PAYSLIP_DOWNLOAD_SUCCESS', "EID {$user_id} downloaded/viewed PID {$payroll_id} for EID {$payslip['employee_id']}.");

    // --- NEW: Calculate Allowances ---
    $active_allowances = [];
    $allowance_breakdown = [];
    $total_allowances_recorded = (float)($payslip['allowances'] ?? 0.00);
    // Important: Gross pay in DB is (Base + Allowances). But if we want to show breakdown, we need Base.
    // Base Pay = Gross Pay - Allowances.
    $gross_pay_stored = (float)$payslip['gross_pay'];
    $base_pay = max(0, $gross_pay_stored - $total_allowances_recorded); 

    try {
        // Fetch all active allowance types first
        $stmt_allow = $pdo->query("SELECT id, name, type, value, is_taxable FROM allowance_types WHERE is_active = TRUE");
        $all_active_allowances = $stmt_allow->fetchAll(PDO::FETCH_ASSOC);

        // Fetch exclusions for this employee
        $stmt_exclusions = $pdo->prepare("SELECT allowance_id FROM allowance_exclusions WHERE employee_id = ?");
        $stmt_exclusions->execute([$payslip['employee_id']]);
        $excluded_ids = $stmt_exclusions->fetchAll(PDO::FETCH_COLUMN);

        foreach ($all_active_allowances as $allowance) {
            if (in_array($allowance['id'], $excluded_ids)) {
                continue; // Skip excluded allowances
            }

            $amount = 0.00;
            if ($allowance['type'] === 'Fixed') {
                $amount = (float)$allowance['value'];
            } elseif ($allowance['type'] === 'Percentage') {
                 // Percentage is based on Base Pay
                $amount = $base_pay * ((float)$allowance['value'] / 100);
            }
            
            $allowance_breakdown[] = [
                'name' => $allowance['name'],
                'amount' => round($amount, 2),
                'type' => $allowance['type'],
                'value' => $allowance['value']
            ];
        }
    } catch(PDOException $e) { error_log('Allowance fetch error: ' . $e->getMessage()); }
    // --- END NEW ---


    // Recalculate Deduction Breakdown
    $active_deductions = []; $deduction_breakdown = [];
    try {
        // FIX: Removed 'AND deleted_at IS NULL' as that column does not exist in the deduction_types table
        $stmt_deductions = $pdo->query("SELECT name, type, value FROM deduction_types WHERE is_active = TRUE");
        $active_deductions = $stmt_deductions->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) { error_log('Deduction fetch error: ' . $e->getMessage()); }

    $gross_pay = (float)$payslip['gross_pay'];
    $total_recorded_deductions = (float)$payslip['deductions']; // Total deductions STORED in DB (AUTHORITATIVE)

    // Calculate Standard Deductions (SSS, Tax, Fixed Insurance)
    $standard_deductions_calculated = 0.00;

    foreach ($active_deductions as $deduction_type) {
        $deduction_value = (float)$deduction_type['value'];
        $amount = 0.00;

        if ($deduction_type['type'] === 'Fixed') {
            $amount = $deduction_value;
        }
        elseif ($deduction_type['type'] === 'Percentage') {
            $amount = $gross_pay * ($deduction_value / 100);
        }

        $amount = round($amount, 2);
        $standard_deductions_calculated += $amount;
        $deduction_breakdown[] = [ 'name' => $deduction_type['name'], 'amount' => $amount, 'type' => $deduction_type['type'], 'value' => $deduction_value ];
    }

    // The difference between the stored total deduction and the calculated standard deduction
    // is assumed to be the CA/VALE deduction.
    $ca_amount_deducted = round($total_recorded_deductions - $standard_deductions_calculated, 2);

    // Only add CA/VALE breakdown if the actual CA/VALE deduction stored in the payroll record was positive.
    if ($ca_amount_deducted > 0.005) { // Use tolerance
        $ca_deduction_name = $_SESSION['settings']['system_ca_deduction_name'] ?? 'Cash Advance Deduction';
        $deduction_breakdown[] = [
            'name' => $ca_deduction_name,
            'amount' => $ca_amount_deducted,
            'type' => 'System Fixed',
            'value' => $ca_amount_deducted
        ];
    }

    // Filter out standard deductions that exceed the stored total deductions if the total stored deductions is less than the calculated standard deductions.
    // However, since the payroll calculation logic is assumed to handle the capping before storage, we only rely on the stored total and skip the detailed breakdown in the PDF in this edge case,
    // unless the deductions are exactly zero.
    if ($total_recorded_deductions < $standard_deductions_calculated) {
        // If the total stored deduction is less than the calculated standard deduction (which means the deduction was capped during payroll run),
        // we can't reliably break down how the capping happened here without more information.
        // For simplicity and to match the total, we will rely purely on the stored total and skip the detailed breakdown in the PDF in this edge case,
        // unless the deductions are exactly zero.
        if ($total_recorded_deductions > 0.005) {
            // In a real system, you would store the breakdown. Here, we fall back to a generic item to match the total.
            $deduction_breakdown = [[
                'name' => 'Payroll Deductions (Total)',
                'amount' => $total_recorded_deductions,
                'type' => 'Recalculated',
                'value' => $total_recorded_deductions
            ]];
        } else {
            $deduction_breakdown = [];
        }
    }


    return [
        'payslip' => $payslip,
        'deduction_breakdown' => $deduction_breakdown,
        'allowance_breakdown' => $allowance_breakdown,
        'base_pay' => $base_pay, // Pass the calculated base pay
        'payroll_id' => $payroll_id
    ];
}

// Function to generate the payslip HTML structure (optimized for PDF rendering)
function generatePayslipHtml($data, $company_name, $currency_symbol) {
    $payslip = $data['payslip'];
    $deduction_breakdown = $data['deduction_breakdown'];
    $allowance_breakdown = $data['allowance_breakdown'] ?? [];
    $base_pay_val = $data['base_pay'] ?? $payslip['gross_pay']; // Default to gross if not Calc
    $payroll_id = $data['payroll_id'];

    $pay_type = $payslip['pay_type_used'] ?? 'N/A';
    $pay_rate = $payslip['pay_rate_used'] ?? 0.00;
    $total_payable_hours = $payslip['total_payable_hours'] ?? 0.00;
    $total_paid_leave_days = $payslip['total_paid_leave_days'] ?? 0;

    // Date Formatting
    try { $pay_date_dt = (new DateTime($payslip['pay_period_end']))->modify('+1 day'); $pay_date = $pay_date_dt->format('M j, Y'); } catch (Exception $e) { $pay_date = 'N/A'; }
    $period_start = date('M j, Y', strtotime($payslip['pay_period_start']));
    $period_end = date('M j, Y', strtotime($payslip['pay_period_end']));
    $processed_date = date('M j, Y', strtotime($payslip['created_at']));

    // Value Formatting
    $net_pay_format = number_format($payslip['net_pay'], 2);
    $total_deductions_format = number_format($payslip['deductions'], 2);
    $gross_pay_format = number_format($payslip['gross_pay'], 2);
    $base_pay_format = number_format($base_pay_val, 2);
    $pay_rate_format = number_format((float)$pay_rate, 2);

    // Determine Suffix
    $pay_rate_suffix = '';
    switch ($pay_type) {
        case 'Hourly': $pay_rate_suffix = ' / hr'; break;
        case 'Daily': $pay_rate_suffix = ' / day'; break;
        case 'Fix Rate': $pay_rate_suffix = ' / period'; break;
        default: $pay_rate_suffix = ''; break;
    }

    // NEW: Check if pay rate should be hidden
    $hide_pay_rate = isset($_SESSION['settings']['hide_pay_rate_from_employee']) && $_SESSION['settings']['hide_pay_rate_from_employee'] == '1';
    $pay_rate_display = $hide_pay_rate ? 'Hidden' : $currency_symbol . $pay_rate_format . $pay_rate_suffix;

    // HTML Structure (Simplified for better PDF library compatibility)
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payslip - <?php echo htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']); ?></title>
        <style>
            body { font-family: sans-serif; font-size: 10pt; margin: 0; padding: 0; }
            .payslip-doc { width: 100%; max-width: 780px; margin: 0 auto; padding: 20px; }
            .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
            .header h1 { font-size: 16pt; margin: 0; color: #3b82f6; }
            .header p { font-size: 9pt; margin: 2pt 0 0 0; color: #555; }
            .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .info-grid td { padding: 3pt 0; vertical-align: top; }
            .info-label { color: #777; font-weight: bold; width: 100pt; }
            .info-value { color: #333; font-weight: bold; }
            .details-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
            .details-table td, .details-table th { padding: 6pt; border: 1px solid #ddd; text-align: left; }
            .details-table th { background-color: #f4f4f4; font-size: 8pt; text-transform: uppercase; color: #555; }
            .total-row { font-weight: bold; background-color: #eee; }
            .net-pay-section {
                background-color: #3b82f6;
                color: #fff;
                padding: 15px;
                border-radius: 5px;
                text-align: center;
                margin-top: 20px;
                page-break-before: auto; /* Important for PDF generation */
            }
            .net-pay-section h3 { font-size: 12pt; margin: 0; }
            .net-pay-value { font-size: 24pt; font-weight: bold; margin: 5pt 0; }
            .net-pay-section p { font-size: 9pt; margin: 0; }
            .note { margin-top: 20px; font-size: 8pt; color: #777; text-align: center;}
            .breakdown-section { width: 50%; display: inline-block; padding-right: 10px;}
            .breakdown-section:last-child { padding-right: 0; padding-left: 10px;}
        </style>
    </head>
    <body>
    <div class="payslip-doc">
        <div class="header">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 70%;">
                        <h1><?php echo htmlspecialchars($company_name); ?> Payslip</h1>
                        <p>Period: <?php echo $period_start; ?> - <?php echo $period_end; ?></p>
                    </td>
                    <td style="text-align: right; font-size: 10pt;">
                        Processed: <?php echo $processed_date; ?>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-grid">
            <tr>
                <td><span class="info-label">Employee:</span> <span class="info-value"><?php echo htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']); ?></span></td>
                <td style="text-align: right;"><span class="info-label">ID:</span> <span class="info-value"><?php echo htmlspecialchars($payslip['employee_id']); ?></span></td>
            </tr>
            <tr>
                <td><span class="info-label">Title:</span> <span class="info-value"><?php echo htmlspecialchars($payslip['job_title']); ?></span></td>
                <td style="text-align: right;"><span class="info-label">Rate Used:</span> <span class="info-value"><?php echo $pay_rate_display; ?></span></td>
            </tr>
        </table>

        <!-- Earnings and Deductions Table -->
        <table class="details-table">
            <thead>
            <tr>
                <th style="width: 40%; background-color: #e6f7e6; color: #10b981;">Earnings Description</th>
                <th style="width: 20%; background-color: #e6f7e6; color: #10b981; text-align: right;">Hours/Days</th>
                <th style="width: 20%; background-color: #e6f7e6; color: #10b981; text-align: right;">Rate/Type</th>
                <th style="width: 20%; background-color: #e6f7e6; color: #10b981; text-align: right;">Amount</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Basic Salary</td>
                <td style="text-align: right;">
                    <?php
                    if ($pay_type === 'Hourly') { echo number_format($total_payable_hours, 2) . ' hrs'; }
                    else if ($pay_type === 'Daily') { echo number_format($total_payable_hours / 8, 1) . ' days'; }
                    else { echo '-'; }
                    ?>
                </td>
                <td style="text-align: right;"><?php echo $pay_rate_display; ?></td>
                <td style="text-align: right;"><?php echo $hide_pay_rate ? 'Hidden' : $currency_symbol . $base_pay_format; ?></td>
            </tr>
            
            <?php foreach ($allowance_breakdown as $allowance): ?>
            <tr>
                <td><?php echo htmlspecialchars($allowance['name']); ?> <?php echo ($allowance['type'] == 'Percentage' ? '(' . number_format($allowance['value'],1) . '%)' : ''); ?></td>
                <td style="text-align: right;">-</td>
                <td style="text-align: right;"><?php echo $allowance['type']; ?></td>
                <td style="text-align: right;"><?php echo $hide_pay_rate ? 'Hidden' : $currency_symbol . number_format($allowance['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if ($total_paid_leave_days > 0): ?>
                <tr>
                    <td>Paid Leave Time</td>
                    <td style="text-align: right;"><?php echo $total_paid_leave_days . ' days'; ?></td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">-</td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; background-color: #f0f0f0;">TOTAL GROSS PAY</td>
                <td style="text-align: right; background-color: #f0f0f0; color: #10b981;"><?php echo $hide_pay_rate ? 'Hidden' : $currency_symbol . $gross_pay_format; ?></td>
            </tr>

            <!-- Deductions Section -->
            <tr>
                <td colspan="4" style="padding: 0; height: 10pt;"></td>
            </tr>
            <tr>
                <th colspan="3" style="background-color: #fdeaea; color: #ef4444;">Deductions Description</th>
                <th style="background-color: #fdeaea; color: #ef4444; text-align: right;">Amount</th>
            </tr>
            <?php if (!empty($deduction_breakdown)): ?>
                <?php foreach ($deduction_breakdown as $deduction): ?>
                    <tr>
                        <td colspan="3"><?php echo htmlspecialchars($deduction['name']); ?> <?php if ($deduction['type'] === 'Percentage') echo '(' . number_format($deduction['value'], 1) . '%)'; elseif ($deduction['type'] === 'System Fixed') echo '(CA/Vale)'; ?></td>
                        <td style="text-align: right; color: #ef4444;"><?php echo $hide_pay_rate ? 'Hidden' : '- ' . $currency_symbol . number_format($deduction['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center; color: #777;">No active deductions applied.</td></tr>
            <?php endif; ?>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; background-color: #f0f0f0;">TOTAL DEDUCTIONS</td>
                <td style="text-align: right; background-color: #f0f0f0; color: #ef4444;"><?php echo $hide_pay_rate ? 'Hidden' : '- ' . $currency_symbol . $total_deductions_format; ?></td>
            </tr>
            </tbody>
        </table>

        <!-- Net Pay Summary -->
        <div class="net-pay-section">
            <h3>NET PAY</h3>
            <div class="net-pay-value"><?php echo $hide_pay_rate ? 'Hidden' : $currency_symbol . $net_pay_format; ?></div>
            <p>Payment Date: <?php echo $pay_date; ?></p>
        </div>

        <div class="note">
            This is a computer-generated document. Figures subject to final audit. Payslip ID: <?php echo $payroll_id; ?>
        </div>
    </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// --- EXECUTION ---
try {
    $payslip_data = fetchPayslipData($pdo, $payroll_id, $user_id, $user_role);

    if (isset($payslip_data['error'])) {
        die("Error: " . $payslip_data['error']);
    }

    $html = generatePayslipHtml($payslip_data, $company_name, $currency_symbol);

    // Output PDF (or simulated output)
    PdfGenerator::streamHtmlAsPdf($html, "Payslip_{$payroll_id}.pdf");

} catch (Exception $e) {
    error_log('PDF Generation Fatal Error: ' . $e->getMessage());
    die("Fatal Error processing request.");
}
?>
