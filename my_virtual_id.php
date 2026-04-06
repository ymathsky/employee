<?php
// FILENAME: employee/my_virtual_id.php
$pageTitle = 'Virtual ID';
include 'template/header.php';

$employee_id = $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Error: Not logged in.</div>";
    include 'template/footer.php'; exit;
}

$company_name    = $_SESSION['settings']['company_name'] ?? 'Your Company';
$currency_symbol = $_SESSION['settings']['currency_symbol'] ?? '₱';

try {
    $stmt = $pdo->prepare(
        "SELECT e.employee_id, e.first_name, e.last_name, e.job_title, e.department,
                e.profile_picture_url, e.hired_date, e.status, u.role
         FROM employees e
         LEFT JOIN users u ON e.employee_id = u.employee_id
         WHERE e.employee_id = ?"
    );
    $stmt->execute([$employee_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$emp) {
        echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Employee record not found.</div>";
        include 'template/footer.php'; exit;
    }
} catch (PDOException $e) {
    error_log('Virtual ID Error: ' . $e->getMessage());
    echo "<div class='bg-red-100 text-red-700 p-4 rounded-lg'>Database error.</div>";
    include 'template/footer.php'; exit;
}

$full_name   = htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']);
$job_title   = htmlspecialchars($emp['job_title'] ?? 'N/A');
$department  = htmlspecialchars($emp['department'] ?? 'N/A');
$hired_date  = !empty($emp['hired_date']) ? date('F j, Y', strtotime($emp['hired_date'])) : 'N/A';
$status      = $emp['status'] ?? 'Active';
$role_label  = $emp['role'] ?? 'Employee';
$emp_id_padded = str_pad($emp['employee_id'], 6, '0', STR_PAD_LEFT);

// Profile picture
$pic_src = !empty($emp['profile_picture_url'])
    ? '../' . htmlspecialchars($emp['profile_picture_url'])
    : 'https://placehold.co/200x200/e0e7ff/4338ca?text=' . urlencode(strtoupper(substr($emp['first_name'],0,1) . substr($emp['last_name'],0,1)));
?>

<style>
/* ---- ID Card Styles ---- */
.id-card {
    width: 340px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    background: #fff;
    font-family: 'Segoe UI', Arial, sans-serif;
    position: relative;
}
.id-card-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%);
    padding: 28px 20px 72px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.id-card-header::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 44px;
    background: #fff;
    border-radius: 50% 50% 0 0 / 100% 100% 0 0;
}
.id-card-company {
    color: rgba(255,255,255,0.95);
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    position: relative; z-index: 2;
}
.id-card-badge {
    display: inline-block;
    margin-top: 6px;
    font-size: 9px;
    font-weight: 600;
    color: rgba(255,255,255,0.8);
    letter-spacing: 2.5px;
    text-transform: uppercase;
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 20px;
    padding: 3px 12px;
    position: relative; z-index: 2;
}
.id-card-corner-circles {
    position: absolute;
    top: 10px; right: 14px;
    opacity: 0.15;
    z-index: 1;
}
.id-card-photo-wrap {
    margin-top: -68px;
    display: flex;
    justify-content: center;
    position: relative;
    z-index: 10;
    padding-bottom: 4px;
}
.id-card-photo {
    width: 108px; height: 108px;
    border-radius: 50%;
    border: 4px solid #fff;
    object-fit: cover;
    box-shadow: 0 4px 18px rgba(79,70,229,0.30);
    background: #e0e7ff;
}
.id-card-body {
    padding: 8px 28px 24px;
    text-align: center;
}
.id-card-name {
    font-size: 18px;
    font-weight: 800;
    color: #1e1b4b;
    margin-top: 8px;
    line-height: 1.2;
    letter-spacing: 0.3px;
}
.id-card-title {
    font-size: 11px;
    color: #6366f1;
    font-weight: 700;
    letter-spacing: 1px;
    margin-top: 5px;
    text-transform: uppercase;
}
.id-card-divider {
    border: none;
    border-top: 1px dashed #e2e8f0;
    margin: 14px 0;
}
.id-card-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    text-align: left;
}
.id-card-info-block label {
    font-size: 9px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
}
.id-card-info-block span {
    font-size: 12px;
    color: #1e293b;
    font-weight: 600;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.id-card-status-active {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #dcfce7;
    color: #16a34a;
    font-size: 10px;
    font-weight: 700;
    border-radius: 20px;
    padding: 2px 10px;
    letter-spacing: 0.5px;
}
.id-card-id-number {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    border-radius: 10px;
    padding: 10px 16px;
    margin-top: 14px;
    text-align: center;
}
.id-card-id-number .label {
    font-size: 9px;
    color: #94a3b8;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}
.id-card-id-number .number {
    font-size: 24px;
    font-weight: 900;
    color: #4f46e5;
    letter-spacing: 5px;
    font-family: 'Courier New', monospace;
}
.id-card-footer {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    padding: 10px 20px;
    text-align: center;
    color: rgba(255,255,255,0.75);
    font-size: 9px;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 4px;
}

/* Print */
@media print {
    body * { visibility: hidden; }
    #id-print-area, #id-print-area * { visibility: visible; }
    #id-print-area {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        gap: 24px;
    }
    .print-hide { display: none !important; }
}
/* Back card specific */
.id-card-back-header {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 60%, #818cf8 100%);
    padding: 18px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.id-card-back-header::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 22px;
    background: #fff;
    border-radius: 50% 50% 0 0 / 100% 100% 0 0;
}
.id-card-back-body {
    padding: 14px 22px 20px;
}
.id-card-back-section {
    margin-bottom: 14px;
}
.id-card-back-section-title {
    font-size: 8px;
    font-weight: 800;
    color: #94a3b8;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 4px;
    margin-bottom: 8px;
}
.id-card-back-rule {
    font-size: 10px;
    color: #475569;
    line-height: 1.6;
    display: flex;
    gap: 6px;
}
.id-card-back-rule::before {
    content: '•';
    color: #6366f1;
    font-weight: 900;
    flex-shrink: 0;
}
.id-card-sig-line {
    border-top: 1px solid #cbd5e1;
    margin-top: 8px;
    padding-top: 4px;
    font-size: 9px;
    color: #94a3b8;
    text-align: center;
    letter-spacing: 0.5px;
}
</style>

<div class="container mx-auto px-4 py-8 max-w-3xl">
    <div class="print-hide flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Virtual Employee ID</h1>
        <button onclick="window.print()" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
            <i class="fas fa-print"></i> Print / Save
        </button>
    </div>

    <div id="id-print-area" class="flex flex-col md:flex-row justify-center gap-6 items-start">
        <div class="id-card">
            <!-- Header -->
            <div class="id-card-header">
                <!-- Decorative circles -->
                <div class="id-card-corner-circles">
                    <svg width="60" height="60" viewBox="0 0 60 60"><circle cx="30" cy="30" r="28" stroke="white" stroke-width="4" fill="none"/><circle cx="30" cy="30" r="18" stroke="white" stroke-width="4" fill="none"/></svg>
                </div>
                <div class="id-card-company"><?= htmlspecialchars($company_name) ?></div>
                <div class="id-card-badge">Employee ID Card</div>
            </div>

            <!-- Photo -->
            <div class="id-card-photo-wrap">
                <img src="<?= $pic_src ?>" alt="Profile Photo" class="id-card-photo" onerror="this.src='https://placehold.co/200x200/e0e7ff/4338ca?text=<?= urlencode(strtoupper(substr($emp['first_name'],0,1).substr($emp['last_name'],0,1))) ?>'">
            </div>

            <!-- Body -->
            <div class="id-card-body">
                <div class="id-card-name"><?= $full_name ?></div>
                <div class="id-card-title"><?= $job_title ?></div>

                <hr class="id-card-divider">

                <div class="id-card-info-grid">
                    <div class="id-card-info-block">
                        <label>Employee ID</label>
                        <span style="font-family:'Courier New',monospace;color:#4f46e5;font-size:14px;letter-spacing:3px;"><?= $emp_id_padded ?></span>
                    </div>
                    <div class="id-card-info-block">
                        <label>Status</label>
                        <span>
                            <?php if ($status === 'Active'): ?>
                                <span class="id-card-status-active"><span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;"></span> Active</span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-weight:700;"><?= htmlspecialchars($status) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <!-- Department full-width -->
                <div class="id-card-info-block" style="text-align:left;margin-top:12px;">
                    <label>Department</label>
                    <span style="white-space:normal;font-size:11px;line-height:1.4;"><?= $department ?></span>
                </div>

                <!-- QR Code -->
                <div style="margin-top:16px;display:flex;flex-direction:column;align-items:center;gap:4px;">
                    <div id="id-qr-code"></div>
                    <span style="font-size:9px;color:#94a3b8;letter-spacing:1px;text-transform:uppercase;">Scan to verify</span>
                </div>

            </div>

        </div><!-- /id-card front -->

        <!-- ========== BACK ========== -->
        <div class="id-card">
            <!-- Back Header -->
            <div class="id-card-back-header">
                <div class="id-card-corner-circles">
                    <svg width="60" height="60" viewBox="0 0 60 60"><circle cx="30" cy="30" r="28" stroke="white" stroke-width="4" fill="none"/><circle cx="30" cy="30" r="18" stroke="white" stroke-width="4" fill="none"/></svg>
                </div>
                <div class="id-card-company" style="position:relative;z-index:2;"><?= htmlspecialchars($company_name) ?></div>
                <div class="id-card-badge" style="position:relative;z-index:2;">Identification Card</div>
            </div>

            <!-- Back Body -->
            <div class="id-card-back-body">

                <!-- If Found -->
                <div class="id-card-back-section">
                    <div class="id-card-back-section-title">If found, please return to</div>
                    <p style="font-size:11px;font-weight:700;color:#1e293b;margin-bottom:2px;"><?= htmlspecialchars($company_name) ?></p>
                    <p style="font-size:10px;color:#64748b;line-height:1.5;">This card is property of <?= htmlspecialchars($company_name) ?>.<br>Return to HR Department immediately.</p>
                </div>

                <!-- Reminders -->
                <div class="id-card-back-section">
                    <div class="id-card-back-section-title">Reminders</div>
                    <div class="id-card-back-rule">This ID must be worn visibly at all times.</div>
                    <div class="id-card-back-rule">Report loss immediately to HR.</div>
                    <div class="id-card-back-rule">Non-transferable. For holder use only.</div>
                    <div class="id-card-back-rule">Present when logging attendance.</div>
                </div>

                <!-- Large QR -->
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;margin:14px 0 10px;">
                    <div id="id-qr-back"></div>
                    <span style="font-size:9px;color:#94a3b8;letter-spacing:1px;text-transform:uppercase;"><?= $emp_id_padded ?></span>
                </div>

                <!-- Signature -->
                <div style="margin-top:10px;padding:0 8px;">
                    <div style="border-top:1px solid #cbd5e1;padding-top:4px;">
                        <div style="font-size:9px;color:#94a3b8;text-align:center;letter-spacing:0.5px;">Authorized Signature / HR</div>
                    </div>
                </div>

            </div>

            <!-- Back Footer -->
            <div class="id-card-footer">
                <?= htmlspecialchars($company_name) ?> &bull; Confidential &bull; Not Transferable
            </div>
        </div><!-- /id-card back -->

    </div><!-- /id-print-area -->

    <!-- Info note -->
    <p class="print-hide text-center text-xs text-gray-400 mt-5">
        <i class="fas fa-info-circle mr-1"></i>
        Front &amp; back of the ID card for <?= $full_name ?>. Use "Print / Save" to export as PDF.
    </p>
</div>

<?php include 'template/footer.php'; ?>

<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Front QR - small
new QRCode(document.getElementById('id-qr-code'), {
    text: 'EMP-<?= $emp_id_padded ?>',
    width: 72,
    height: 72,
    colorDark: '#4f46e5',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
});
// Back QR - larger
new QRCode(document.getElementById('id-qr-back'), {
    text: 'EMP-<?= $emp_id_padded ?>',
    width: 100,
    height: 100,
    colorDark: '#4f46e5',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
});
</script>
