<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * High-Fidelity Printable Certificate of Internship
 * Faithfully formatted based on the Access "Certificate IBA" report
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_auth(); // Admin, Supervisor, or Intern can view

$id = (int)($_GET['id'] ?? 0);
$intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$id]);

if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

// Check or generate certificate entry in `certificates` table
$cert = db_fetch_one("SELECT * FROM certificates WHERE intern_id = ?", [$id]);
if (!$cert) {
    $token = bin2hex(random_bytes(16));
    $certNo = 'AWT-' . ($intern['iyear'] ?: date('Y')) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    $issueDate = !empty($intern['dateassignto']) ? date('Y-m-d', strtotime($intern['dateassignto'])) : date('Y-m-d');
    $period = !empty($intern['Certificate_Period_Detail']) ? $intern['Certificate_Period_Detail'] : (format_date($intern['dateassignfrom']) . ' to ' . format_date($intern['dateassignto']));

    db_query("
        INSERT INTO certificates (intern_id, certificate_no, issue_date, period_detail, hours_duration, qr_token, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Issued')
    ", [$id, $certNo, $issueDate, $period, $intern['Hours'] ?: '6 Weeks', $token]);

    $cert = db_fetch_one("SELECT * FROM certificates WHERE intern_id = ?", [$id]);
}

$issueDateDisplay = format_date($cert['issue_date'], 'M jS, Y');
$signatoryName = get_setting('certificate_signatory', 'Nisar Ahmed');
$signatoryTitle = get_setting('certificate_signatory_title', 'HR & Internship Coordinator');
$periodText = !empty($intern['Certificate_Period_Detail']) ? $intern['Certificate_Period_Detail'] : ($cert['period_detail'] ?? 'the prescribed duration');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Internship — <?= e($intern['sname']); ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/certificate.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/images/awt-logo.png">
</head>
<body>

<div class="certificate-preview-wrapper">
    <!-- Action Bar (Hidden on Print) -->
    <div class="certificate-actions no-print">
        <button type="button" class="btn btn-warning px-4 py-2 fw-bold shadow" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print Certificate (A4 Landscape)
        </button>
        <?php if (current_user_role() === 'admin'): ?>
            <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light px-3 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to Profile
            </a>
        <?php else: ?>
            <a href="../intern/index.php" class="btn btn-light px-3 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        <?php endif; ?>
    </div>

    <!-- Official Certificate Sheet (Printable A4 Landscape) -->
    <div class="certificate-sheet">
        <div class="certificate-border">
            <!-- Decorative Corners -->
            <div class="certificate-corner-tl"></div>
            <div class="certificate-corner-tr"></div>
            <div class="certificate-corner-bl"></div>
            <div class="certificate-corner-br"></div>

            <!-- Certificate Header -->
            <div class="cert-header">
                <div class="cert-logo">
                    <img src="../assets/images/awt-logo.png" alt="Alamgir Welfare Trust Int'l" style="width:75px;height:75px;object-fit:contain;" class="mb-1">
                </div>
                <h1 class="cert-org-name">Alamgir Welfare Trust Int'l</h1>
                <div class="cert-org-sub">A NON-PROFIT CHARITABLE ORGANIZATION &bull; ESTABLISHED 1993</div>
                
                <div class="cert-title-wrap">
                    <h2 class="cert-title">Certificate of Internship</h2>
                </div>
            </div>

            <!-- Certificate Body -->
            <div class="cert-body">
                <div class="cert-presented">This certificate is proudly awarded to</div>
                <div class="cert-student-name"><?= e($intern['sname']); ?></div>

                <div class="cert-narrative">
                    <?php if (!empty($intern['Father_name'])): ?>
                        Child of <span class="cert-highlight"><?= e($intern['Father_name']); ?></span>,
                    <?php endif; ?>
                    a bona fide student of <span class="cert-highlight"><?= e($intern['sInstitute'] ?: 'the participating university'); ?></span>
                    <?php if (!empty($intern['Degree'])): ?>
                        (<?= e($intern['Degree']); ?>)
                    <?php endif; ?>
                    has successfully completed an internship period of <span class="cert-highlight"><?= e($intern['Hours'] ?: '6 Weeks'); ?></span>
                    with <span class="cert-highlight">Alamgir Welfare Trust Int'l</span>
                    <?php if (!empty($periodText)): ?>
                        during the term <span class="cert-highlight"><?= e($periodText); ?></span>.
                    <?php else: ?>
                        in the session of <span class="cert-highlight"><?= e($intern['iyear'] ?: date('Y')); ?></span>.
                    <?php endif; ?>
                    <br>
                    During this tenure, the intern demonstrated exceptional enthusiasm, dedication, and social responsibility across assigned humanitarian community development projects.
                </div>
            </div>

            <!-- Certificate Footer -->
            <div class="cert-footer">
                <!-- Issue Date -->
                <div class="cert-sig-block text-start" style="width:200px;">
                    <div class="small text-muted mb-1">Date of Issue:</div>
                    <div class="fw-bold text-dark fs-6"><?= $issueDateDisplay; ?></div>
                    <div class="cert-no mt-2">Cert #: <?= e($cert['certificate_no']); ?></div>
                </div>

                <!-- Verification QR Token Box -->
                <div class="cert-qr-block">
                    <div class="cert-qr-box">
                        <div class="text-center">
                            <i class="fas fa-qrcode fs-3 text-dark d-block"></i>
                            <span style="font-size:8px;" class="fw-bold">VERIFIED</span>
                        </div>
                    </div>
                    <div class="cert-no">Verify: <?= substr($cert['qr_token'], 0, 10); ?>...</div>
                </div>

                <!-- HR Coordinator Signature -->
                <div class="cert-sig-block">
                    <div style="height:35px;" class="d-flex align-items-end justify-content-center">
                        <span style="font-family:'Great Vibes',cursive;font-size:1.8rem;color:#1e3a8a;">Nisar Ahmed</span>
                    </div>
                    <div class="cert-sig-line"></div>
                    <div class="cert-sig-name"><?= e($signatoryName); ?></div>
                    <div class="cert-sig-title"><?= e($signatoryTitle); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
