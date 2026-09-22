<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Public Online Internship Application Form
 */

define('AWT_APP', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$success = false;
$error = '';
$appId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = 'Session security token expired. Please reload and try again.';
    } else {
        $sname = sanitize($_POST['sname'] ?? '');
        $fatherName = sanitize($_POST['father_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $cell = sanitize($_POST['cellnumber'] ?? '');
        $institute = sanitize($_POST['institute'] ?? '');
        $degree = sanitize($_POST['degree'] ?? '');
        $sterm = sanitize($_POST['sterm'] ?? '');
        $hours = sanitize($_POST['hours'] ?? '6 Weeks');
        $address = sanitize($_POST['address'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $dateFrom = !empty($_POST['dateprefferedfrom']) ? $_POST['dateprefferedfrom'] : null;
        $dateTo = !empty($_POST['dateprefferedto']) ? $_POST['dateprefferedto'] : null;

        if (empty($sname) || empty($email) || empty($cell) || empty($institute)) {
            $error = 'Please fill in all required fields (Name, Email, Cell Number, and University/Institute).';
        } else {
            // Handle optional CV upload
            $hasCV = 0;
            if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
                $upload = handle_file_upload($_FILES['cv_file'], 'uploads/cvs');
                if ($upload['success']) {
                    $hasCV = 1;
                }
            }

            try {
                $sql = "INSERT INTO interns (
                    sname, Father_name, Email, Cellnumber, Address, sInstitute, Degree, sterm,
                    Hours, dateprefferedfrom, dateprefferedto, Location, iyear, Date_of_Submission,
                    Dateofentry, status, confirmed, Waiting, Request_Form, CV, Organizationname
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, NOW(),
                    NOW(), 'pending', 0, 0, 1, ?, ?
                )";

                db_query($sql, [
                    $sname, $fatherName, $email, $cell, $address, $institute, $degree, $sterm,
                    $hours, $dateFrom, $dateTo, $location, date('Y'), $hasCV, "Alamgir Welfare Trust Int'l"
                ]);

                $appId = (int)db_last_insert_id();
                $success = true;
                log_activity(null, 'Public Application Submitted', "New application #{$appId} submitted by {$sname} ({$institute})");
            } catch (Exception $e) {
                $error = 'Failed to submit application: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Internship | Alamgir Welfare Trust Int'l</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/awt-logo.png">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark py-3" style="background:#0f172a !important;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
            <img src="assets/images/awt-logo.png" alt="AWT Logo" style="width:38px;height:38px;object-fit:contain;">
            <span>Alamgir Welfare Trust Int'l</span>
        </a>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Portal</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php if ($success): ?>
                <div class="card border-0 shadow-lg p-5 text-center bg-white rounded-4">
                    <div class="text-success mb-3">
                        <i class="fas fa-check-circle display-3"></i>
                    </div>
                    <h2 class="fw-bold text-dark">Application Received!</h2>
                    <p class="text-muted lead">Thank you for applying to the Alamgir Welfare Trust Int'l Internship Program.</p>
                    <div class="alert alert-info d-inline-block px-4 py-3 mx-auto my-3 border-0">
                        <span class="text-muted small text-uppercase tracking-wider d-block">Your Application Reference ID</span>
                        <span class="fs-2 fw-bold text-primary">#<?= $appId; ?></span>
                    </div>
                    <p class="small text-muted mb-4">
                        Our HR and Coordination department will review your credentials and university recommendation. You will receive an official notification or interview schedule via cell/email.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="index.php" class="btn btn-primary px-4 fw-semibold"><i class="fas fa-home me-1"></i> Return to Homepage</a>
                        <a href="login.php" class="btn btn-outline-secondary px-4 fw-semibold">Sign In</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 text-white" style="background:linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%);">
                        <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold">Session <?= date('Y'); ?></span>
                        <h3 class="fw-bold mb-1">Internship Application Form</h3>
                        <p class="mb-0 text-white-50">Fill in your academic and contact details to apply for social service & professional internships.</p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= e($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="apply.php" method="POST" enctype="multipart/form-data">
                            <?= csrf_input(); ?>

                            <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="fas fa-user-graduate me-2"></i>Personal Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="sname" class="form-control" placeholder="e.g. Ali Raza" required value="<?= e($_POST['sname'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control" placeholder="e.g. Muhammad Raza" value="<?= e($_POST['father_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="name@domain.com" required value="<?= e($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Cell / Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="cellnumber" class="form-control" placeholder="0300-1234567" required value="<?= e($_POST['cellnumber'] ?? ''); ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Residential Address</label>
                                    <input type="text" name="address" class="form-control" placeholder="Area, City" value="<?= e($_POST['address'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Location / Area</label>
                                    <input type="text" name="location" class="form-control" placeholder="e.g. Gulshan-e-Iqbal, Karachi" value="<?= e($_POST['location'] ?? ''); ?>">
                                </div>
                            </div>

                            <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="fas fa-university me-2"></i>Academic Profile</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">University / Institute <span class="text-danger">*</span></label>
                                    <input type="text" name="institute" class="form-control" placeholder="e.g. IBA, IoBM, SZABIST, KU, FAST" required value="<?= e($_POST['institute'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Degree Program</label>
                                    <input type="text" name="degree" class="form-control" placeholder="e.g. BBA, BSCS, BSAF" value="<?= e($_POST['degree'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Current Semester / Term</label>
                                    <input type="text" name="sterm" class="form-control" placeholder="e.g. 4th Semester" value="<?= e($_POST['sterm'] ?? ''); ?>">
                                </div>
                            </div>

                            <h5 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="fas fa-calendar-alt me-2"></i>Internship Preferences & Documents</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Required Duration / Hours</label>
                                    <select name="hours" class="form-select">
                                        <option value="6 Weeks">6 Weeks (Standard)</option>
                                        <option value="8 Weeks">8 Weeks</option>
                                        <option value="72 Hours">72 Hours Community Service</option>
                                        <option value="120 Hours">120 Hours</option>
                                        <option value="3 Months">3 Months</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Preferred Start Date</label>
                                    <input type="date" name="dateprefferedfrom" class="form-control" value="<?= e($_POST['dateprefferedfrom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Preferred End Date</label>
                                    <input type="date" name="dateprefferedto" class="form-control" value="<?= e($_POST['dateprefferedto'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Upload Curriculum Vitae (CV / Resume)</label>
                                    <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                                    <div class="form-text">PDF or DOC format, max 5MB.</div>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                <label class="form-check-label small" for="termsCheck">
                                    I hereby confirm that all information supplied is accurate and I agree to abide by the discipline, code of conduct, and volunteer ethics of <strong>Alamgir Welfare Trust Int'l</strong>.
                                </label>
                            </div>

                            <div class="text-end">
                                <a href="index.php" class="btn btn-light px-4 me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="background:#1e3a8a;border-color:#1e3a8a;">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
