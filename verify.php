<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Public Certificate Verification
 */

define('AWT_APP', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

$token = sanitize($_GET['token'] ?? '');
$certificate = null;
$searched = !empty($token);

if ($searched) {
    // Search by qr_token or certificate_no or intern id
    $certificate = db_fetch_one("
        SELECT c.*, i.sname, i.Father_name, i.sInstitute, i.Degree, i.iyear, i.Hours, i.Certificate_Period_Detail
        FROM certificates c
        JOIN interns i ON c.intern_id = i.id
        WHERE c.qr_token = ? OR c.certificate_no = ? OR (c.intern_id = ? AND ? > 0)
        LIMIT 1
    ", [$token, $token, is_numeric($token) ? (int)$token : 0, is_numeric($token) ? (int)$token : 0]);

    // If not found in certificates table, check if the token matches an intern ID in interns table where confirmed = 1 or completed
    if (!$certificate && is_numeric($token)) {
        $intern = db_fetch_one("
            SELECT * FROM interns 
            WHERE id = ? AND (confirmed = 1 OR punctuality > 0 OR (Certificate_Period_Detail IS NOT NULL AND Certificate_Period_Detail != ''))
            LIMIT 1
        ", [(int)$token]);

        if ($intern) {
            $certificate = [
                'certificate_no' => 'AWT-CERT-' . $intern['id'] . '-' . ($intern['iyear'] ?: date('Y')),
                'issue_date'     => $intern['dateassignto'] ?: date('Y-m-d'),
                'status'         => 'Issued',
                'sname'          => $intern['sname'],
                'Father_name'    => $intern['Father_name'],
                'sInstitute'     => $intern['sInstitute'],
                'Degree'         => $intern['Degree'],
                'iyear'          => $intern['iyear'],
                'Hours'          => $intern['Hours'] ?: '6 Weeks',
                'Certificate_Period_Detail' => $intern['Certificate_Period_Detail']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Internship Certificate | Alamgir Welfare Trust Int'l</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/awt-logo.png">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark py-3" style="background:#0f172a !important;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
            <img src="assets/images/awt-logo.png" alt="AWT Logo" style="width:38px;height:38px;object-fit:contain;">
            <span>Alamgir Welfare Trust Int'l</span>
        </a>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-home me-1"></i> Home</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4 text-center">
                <div class="brand-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.6rem;background:linear-gradient(135deg, #d97706, #b45309);">
                    <i class="fas fa-award"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Official Certificate Verification</h3>
                <p class="text-muted small mb-4">Validate the authenticity of certificates issued by Alamgir Welfare Trust Int'l</p>

                <form action="verify.php" method="GET" class="mb-4">
                    <div class="input-group input-group-lg shadow-sm">
                        <input type="text" name="token" class="form-control" placeholder="Certificate No, Token, or Intern ID..." value="<?= e($token); ?>" required autofocus>
                        <button class="btn btn-primary px-4 fw-bold" type="submit"><i class="fas fa-search me-1"></i> Verify</button>
                    </div>
                </form>

                <?php if ($searched): ?>
                    <?php if ($certificate): ?>
                        <div class="alert alert-success text-start p-4 rounded-3 border-0 shadow-sm mb-0">
                            <div class="d-flex align-items-center gap-2 text-success fw-bold fs-5 mb-3">
                                <i class="fas fa-check-circle fs-3"></i> Valid & Authentic Certificate
                            </div>
                            <div class="row g-2 small">
                                <div class="col-sm-4 text-muted">Recipient Name:</div>
                                <div class="col-sm-8 fw-bold fs-6 text-dark"><?= e($certificate['sname']); ?></div>

                                <?php if (!empty($certificate['Father_name'])): ?>
                                    <div class="col-sm-4 text-muted">Father's Name:</div>
                                    <div class="col-sm-8 text-dark"><?= e($certificate['Father_name']); ?></div>
                                <?php endif; ?>

                                <div class="col-sm-4 text-muted">Institute / University:</div>
                                <div class="col-sm-8 text-dark fw-semibold"><?= e($certificate['sInstitute'] ?: 'Recognized Institution'); ?></div>

                                <div class="col-sm-4 text-muted">Certificate Number:</div>
                                <div class="col-sm-8 font-monospace text-primary fw-bold"><?= e($certificate['certificate_no']); ?></div>

                                <div class="col-sm-4 text-muted">Duration / Period:</div>
                                <div class="col-sm-8 text-dark"><?= e($certificate['Hours'] ?: 'Completed Duration'); ?> (<?= e($certificate['iyear'] ?: date('Y')); ?>)</div>

                                <div class="col-sm-4 text-muted">Status:</div>
                                <div class="col-sm-8"><span class="badge bg-success">Verified & Issued</span></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger text-start p-4 rounded-3 border-0 shadow-sm mb-0">
                            <div class="d-flex align-items-center gap-2 text-danger fw-bold fs-5 mb-2">
                                <i class="fas fa-times-circle fs-3"></i> Certificate Not Found
                            </div>
                            <p class="mb-0 small text-muted">
                                We could not find a verified certificate matching the record "<strong><?= e($token); ?></strong>". Please ensure the certificate number or ID was entered accurately or contact our HR Coordination office.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
