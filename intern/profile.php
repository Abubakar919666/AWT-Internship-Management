<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Intern Profile Page
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_intern();

$user = current_user();
$internId = $user['intern_id'];
if (!$internId) {
    $internRow = db_fetch_one("SELECT id FROM interns WHERE Email = ? OR user_id = ? LIMIT 1", [$user['email'], $user['id']]);
    if ($internRow) $internId = (int)$internRow['id'];
}

$intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$internId]);
if (!$intern) {
    echo "Intern profile not found.";
    exit;
}

$pageTitle = 'My Profile';
$pageSubtitle = 'Academic details, contact information, and document checklist';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-10">
        <!-- Personal Details Card -->
        <div class="awt-card mb-4">
            <div class="card-header-clean">
                <h5><i class="fas fa-user-graduate text-primary"></i> Academic & Personal Information</h5>
                <span class="badge bg-light text-muted border">Intern #<?= $intern['id']; ?></span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="text-muted small d-block">Full Name</label>
                    <span class="fw-bold text-dark fs-6"><?= e($intern['sname']); ?></span>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small d-block">Father's Name</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Father_name'] ?: '—'); ?></span>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small d-block">University / Institute</label>
                    <span class="fw-bold text-primary"><?= e($intern['sInstitute'] ?: '—'); ?></span>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small d-block">Degree Program</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Degree'] ?: '—'); ?></span>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small d-block">Current Semester</label>
                    <span class="fw-semibold text-dark"><?= e($intern['sterm'] ?: '—'); ?></span>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small d-block">Registered Email</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Email'] ?: '—'); ?></span>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small d-block">Cell / Contact Number</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Cellnumber'] ?: '—'); ?></span>
                </div>
                <div class="col-md-8">
                    <label class="text-muted small d-block">Residential Address</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Address'] ?: '—'); ?></span>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small d-block">Location Area</label>
                    <span class="fw-semibold text-dark"><?= e($intern['Location'] ?: 'Karachi'); ?></span>
                </div>
            </div>
        </div>

        <!-- Document Verification Checklist -->
        <div class="awt-card mb-4">
            <div class="card-header-clean">
                <h5><i class="fas fa-clipboard-check text-success"></i> Submitted Documents Status</h5>
                <span class="badge bg-light text-muted border">HR Verification</span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-alt text-primary me-2"></i><strong>Official Request Form</strong>
                        </div>
                        <span class="badge bg-<?= $intern['Request_Form'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['Request_Form'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-portrait text-info me-2"></i><strong>Passport Photograph</strong>
                        </div>
                        <span class="badge bg-<?= $intern['Photograph'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['Photograph'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-pdf text-danger me-2"></i><strong>Curriculum Vitae (CV)</strong>
                        </div>
                        <span class="badge bg-<?= $intern['CV'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['CV'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-envelope-open-text text-warning me-2"></i><strong>Recommendation Letter</strong>
                        </div>
                        <span class="badge bg-<?= $intern['Recommendation_Letter'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['Recommendation_Letter'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-id-card text-success me-2"></i><strong>CNIC / B-Form Copy</strong>
                        </div>
                        <span class="badge bg-<?= $intern['CNIC_copy'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['CNIC_copy'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-address-card text-secondary me-2"></i><strong>Student ID Card</strong>
                        </div>
                        <span class="badge bg-<?= $intern['Student_ID'] ? 'success' : 'secondary'; ?>">
                            <?= $intern['Student_ID'] ? 'Verified' : 'Not Submitted'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
