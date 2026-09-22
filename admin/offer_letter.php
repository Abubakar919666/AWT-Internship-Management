<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Official Internship Acceptance / Offer Letter
 * Faithfully formatted based on the Access "Report" template
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);
$intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$id]);

if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

$today = date('F d, Y');
$signatoryName = get_setting('hr_coordinator_name', 'Nisar Ahmed');
$signatoryTitle = get_setting('hr_coordinator_title', 'Coordinator');
$orgName = get_setting('org_name', 'Alamgir Welfare Trust Int\'l');
$hours = !empty($intern['Hours']) ? $intern['Hours'] : '6 Weeks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Offer Letter — <?= e($intern['sname']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/certificate.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/images/awt-logo.png">
    <style>
        body { background: #475569; padding: 2rem 1rem; }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button type="button" class="btn btn-warning px-4 py-2 fw-bold shadow" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Print Offer Letter (A4 Portrait)
    </button>
    <?php if (current_user_role() === 'admin'): ?>
        <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light px-3 py-2 fw-semibold ms-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Profile
        </a>
    <?php else: ?>
        <a href="../intern/index.php" class="btn btn-light px-3 py-2 fw-semibold ms-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    <?php endif; ?>
</div>

<div class="letter-sheet">
    <!-- Letterhead -->
    <div class="letter-header">
        <div class="d-flex align-items-center gap-3">
            <img src="../assets/images/awt-logo.png" alt="Alamgir Welfare Trust Int'l" style="width:58px;height:58px;object-fit:contain;">
            <div>
                <h3 class="fw-bold mb-0 text-primary" style="font-family:'Cinzel',serif;"><?= e($orgName); ?></h3>
                <span class="small text-muted text-uppercase tracking-wider">Human Resource & Internship Coordination Department</span>
            </div>
        </div>
        <div class="text-end text-muted small">
            <div>Ref: AWT/INT/<?= $intern['iyear'] ?: date('Y'); ?>/<?= str_pad($intern['id'], 4, '0', STR_PAD_LEFT); ?></div>
            <div>Date: <?= $today; ?></div>
        </div>
    </div>

    <!-- Recipient Info -->
    <div class="mb-4">
        <div class="text-muted small">To:</div>
        <h5 class="fw-bold mb-1 text-dark"><?= e($intern['sname']); ?></h5>
        <?php if (!empty($intern['Father_name'])): ?>
            <div class="small text-muted">S/O, D/O: <?= e($intern['Father_name']); ?></div>
        <?php endif; ?>
        <div class="small text-muted"><?= e($intern['sInstitute'] ?: 'University Student'); ?> — <?= e($intern['Degree'] ?: 'Bachelors Program'); ?></div>
        <?php if (!empty($intern['Location'])): ?>
            <div class="small text-muted"><?= e($intern['Location']); ?>, Karachi.</div>
        <?php else: ?>
            <div class="small text-muted">Karachi.</div>
        <?php endif; ?>
    </div>

    <div class="fw-bold fs-6 text-primary mb-3 text-uppercase border-bottom pb-2">
        Subject: Offer of Internship Placement & Community Engagement
    </div>

    <!-- Letter Body (Faithful to original AWT letter) -->
    <div class="letter-body">
        <p>Dear <strong><?= e($intern['sname']); ?></strong>,</p>

        <p>Congratulations on getting selected for the opportunity to volunteer at <strong>Alamgir Welfare Trust Int'l</strong>!</p>

        <p>We value the time and efforts of young capable individuals like yourself, and try to make your experience with us as meaningful as possible.</p>

        <p>The volunteer internship program is based on <strong><?= e($hours); ?></strong> of community service which you will undertake with other volunteers, doctors, coordinators, and supervisors from our organization. You will be required to portray commitment and a desire to give back to the society which will be reflected by the outcome of your assigned projects and departmental rotations.</p>

        <p>
            Your tenure is scheduled to commence on <strong><?= format_date($intern['dateassignfrom'] ?: date('Y-m-d')); ?></strong> and conclude on <strong><?= format_date($intern['dateassignto'] ?: date('Y-m-d', strtotime('+6 weeks'))); ?></strong>.
            <?php if (!empty($intern['Mentor'])): ?>
                You will report directly to mentor <strong><?= e($intern['Mentor']); ?></strong>.
            <?php endif; ?>
        </p>

        <p>At the end of the volunteer program, you will be evaluated based on your attendance, dedication, teamwork, and overall performance during the period of the program, following which your official Certificate of Internship will be awarded.</p>

        <p>We wish you the very best of luck and look forward to having you on board!</p>

        <p class="mt-4">Best regards,</p>
    </div>

    <!-- Signatures -->
    <div class="mt-5 pt-3">
        <div style="width:220px;">
            <div style="height:40px;" class="d-flex align-items-end">
                <span style="font-family:'Great Vibes',cursive;font-size:1.8rem;color:#1e3a8a;">Nisar Ahmed</span>
            </div>
            <div style="border-top: 1px solid #0f172a; margin-top:4px;"></div>
            <div class="fw-bold text-dark mt-1"><?= e($signatoryName); ?></div>
            <div class="small text-muted"><?= e($signatoryTitle); ?></div>
            <div class="small text-muted"><?= e($orgName); ?></div>
        </div>
    </div>
</div>

</body>
</html>
