<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Edit Intern Record
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$id]);

if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

ensure_intern_photo_column();

$pageTitle = 'Edit Intern #' . $id;
$pageSubtitle = 'Update record details for ' . e($intern['sname']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = 'Security session expired. Please submit again.';
    } else {
        $sname = sanitize($_POST['sname'] ?? '');
        $fatherName = sanitize($_POST['father_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $cell = sanitize($_POST['cellnumber'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $institute = sanitize($_POST['institute'] ?? '');
        $degree = sanitize($_POST['degree'] ?? '');
        $sterm = sanitize($_POST['sterm'] ?? '');
        $hours = sanitize($_POST['hours'] ?? '');
        $iyear = sanitize($_POST['iyear'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        $dateFrom = !empty($_POST['dateassignfrom']) ? $_POST['dateassignfrom'] : null;
        $dateTo = !empty($_POST['dateassignto']) ? $_POST['dateassignto'] : null;
        $supervisorId = !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null;
        $mentor = sanitize($_POST['mentor'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $groupName = !empty($_POST['group_name']) ? (int)$_POST['group_name'] : null;
        $groupTime = sanitize($_POST['group_time'] ?? '');
        $assignedWork = sanitize($_POST['assigned_work'] ?? '');
        $remarks = sanitize($_POST['remarks'] ?? '');
        $certDetail = sanitize($_POST['certificate_period_detail'] ?? '');

        // Document checkboxes
        $reqForm = isset($_POST['request_form']) ? 1 : 0;
        $photo = isset($_POST['photograph']) ? 1 : 0;
        $cv = isset($_POST['cv']) ? 1 : 0;
        $recomLetter = isset($_POST['recommendation_letter']) ? 1 : 0;
        $cnic = isset($_POST['cnic_copy']) ? 1 : 0;
        $studentId = isset($_POST['student_id']) ? 1 : 0;

        $confirmed = in_array($status, ['confirmed', 'active', 'completed']) ? 1 : 0;
        $waiting = ($status === 'waiting') ? 1 : 0;

        if (empty($sname)) {
            $error = 'Student Full Name is required.';
        } else {
            try {
                // Check if new photo was uploaded
                if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $photoUpload = handle_file_upload($_FILES['photo_file'], 'uploads/photos');
                    if ($photoUpload['success']) {
                        $newPhotoPath = $photoUpload['relative_path'];
                        if (!empty($intern['photo']) && $intern['photo'] !== $newPhotoPath) {
                            @unlink(__DIR__ . '/../' . ltrim($intern['photo'], '/'));
                        }
                        db_query("UPDATE interns SET photo = ? WHERE id = ?", [$newPhotoPath, $id]);
                        $photo = 1;
                        if (!empty($intern['user_id'])) {
                            db_query("UPDATE users SET avatar = ? WHERE id = ?", [$newPhotoPath, $intern['user_id']]);
                        }
                    }
                }
                if ($supervisorId && empty($mentor)) {
                    $supRow = db_fetch_one("SELECT name FROM supervisors WHERE id = ?", [$supervisorId]);
                    if ($supRow) $mentor = $supRow['name'];
                }

                $sql = "
                    UPDATE interns SET
                        sname = ?, Father_name = ?, Email = ?, Cellnumber = ?, Address = ?, Location = ?,
                        sInstitute = ?, Degree = ?, sterm = ?, Hours = ?, iyear = ?, status = ?,
                        confirmed = ?, Waiting = ?, dateassignfrom = ?, dateassignto = ?,
                        supervisor_id = ?, Mentor = ?, Department = ?, GroupName = ?, GroupTime = ?,
                        Assignedwork = ?, Remarks = ?, Certificate_Period_Detail = ?,
                        Request_Form = ?, Photograph = ?, CV = ?, Recommendation_Letter = ?,
                        CNIC_copy = ?, Student_ID = ?
                    WHERE id = ?
                ";

                db_query($sql, [
                    $sname, $fatherName, $email, $cell, $address, $location,
                    $institute, $degree, $sterm, $hours, $iyear, $status,
                    $confirmed, $waiting, $dateFrom, $dateTo,
                    $supervisorId, $mentor, $department, $groupName, $groupTime,
                    $assignedWork, $remarks, $certDetail,
                    $reqForm, $photo, $cv, $recomLetter,
                    $cnic, $studentId, $id
                ]);

                log_activity(current_user()['id'], 'Updated Intern', "Edited intern #{$id}: {$sname}");
                set_flash('success', "Intern record #{$id} has been updated successfully.");
                header("Location: intern_view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    }
}

// Re-fetch supervisors
$supervisors = db_fetch_all("SELECT id, name, department, designation FROM supervisors WHERE is_active = 1 ORDER BY name ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= e($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="intern_edit.php?id=<?= $id; ?>" method="POST" enctype="multipart/form-data" class="awt-card p-4">
            <?= csrf_input(); ?>

            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-edit text-primary me-2"></i>Edit Intern Record #<?= $id; ?></h4>
                    <span class="text-muted small">Registered: <?= format_datetime($intern['created_at'] ?? $intern['Dateofentry']); ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="intern_view.php?id=<?= $id; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1"></i> View Profile</a>
                    <a href="interns.php" class="btn btn-light border btn-sm">Back</a>
                </div>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-4" id="internEditTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="pill" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="fas fa-id-card me-1"></i> Personal & Academic
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="program-tab" data-bs-toggle="pill" data-bs-target="#tab-program" type="button" role="tab">
                        <i class="fas fa-calendar-alt me-1"></i> Tenure & Assignment
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="docs-tab" data-bs-toggle="pill" data-bs-target="#tab-docs" type="button" role="tab">
                        <i class="fas fa-check-square me-1"></i> Document Checklist
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="internEditTabsContent">
                <!-- TAB 1: Personal & Academic -->
                <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="sname" class="form-control" required value="<?= e($intern['sname']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?= e($intern['Father_name']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= e($intern['Email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cell / Mobile Number</label>
                            <input type="text" name="cellnumber" class="form-control" value="<?= e($intern['Cellnumber']); ?>">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Residential Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e($intern['Address']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location / Area</label>
                            <input type="text" name="location" class="form-control" value="<?= e($intern['Location']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">University / Institute</label>
                            <input type="text" name="institute" class="form-control" value="<?= e($intern['sInstitute']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Degree Program</label>
                            <input type="text" name="degree" class="form-control" value="<?= e($intern['Degree']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Current Semester</label>
                            <input type="text" name="sterm" class="form-control" value="<?= e($intern['sterm']); ?>">
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Tenure & Assignment -->
                <div class="tab-pane fade" id="tab-program" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Internship Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $intern['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="confirmed" <?= $intern['status'] === 'confirmed' || $intern['confirmed'] ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?= $intern['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="waiting" <?= $intern['status'] === 'waiting' || $intern['Waiting'] ? 'selected' : ''; ?>>Waiting List</option>
                                <option value="pending" <?= $intern['status'] === 'pending' ? 'selected' : ''; ?>>Pending Application</option>
                                <option value="terminated" <?= $intern['status'] === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Internship Year</label>
                            <input type="text" name="iyear" class="form-control" value="<?= e($intern['iyear']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duration / Required Hours</label>
                            <input type="text" name="hours" class="form-control" value="<?= e($intern['Hours']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tenure Start Date</label>
                            <input type="date" name="dateassignfrom" class="form-control" value="<?= !empty($intern['dateassignfrom']) ? date('Y-m-d', strtotime($intern['dateassignfrom'])) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tenure End Date</label>
                            <input type="date" name="dateassignto" class="form-control" value="<?= !empty($intern['dateassignto']) ? date('Y-m-d', strtotime($intern['dateassignto'])) : ''; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assign Organization Supervisor</label>
                            <select name="supervisor_id" class="form-select">
                                <option value="">Select Supervisor...</option>
                                <?php foreach ($supervisors as $sup): ?>
                                    <option value="<?= $sup['id']; ?>" <?= ($intern['supervisor_id'] == $sup['id']) ? 'selected' : ''; ?>>
                                        <?= e($sup['name']); ?> (<?= e($sup['department'] ?: 'General'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mentor Name (Custom)</label>
                            <input type="text" name="mentor" class="form-control" value="<?= e($intern['Mentor']); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department Assigned</label>
                            <input type="text" name="department" class="form-control" value="<?= e($intern['Department']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Group Name / No</label>
                            <input type="number" name="group_name" class="form-control" value="<?= e($intern['GroupName']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Group Time</label>
                            <input type="text" name="group_time" class="form-control" value="<?= e($intern['GroupTime']); ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Certificate Period Detail (Text on Certificate)</label>
                            <input type="text" name="certificate_period_detail" class="form-control" placeholder="e.g. From June 16, 2025 to July 31, 2025" value="<?= e($intern['Certificate_Period_Detail']); ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Assigned Tasks / Work Summary</label>
                            <textarea name="assigned_work" class="form-control" rows="3"><?= e($intern['Assignedwork']); ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Internal Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2"><?= e($intern['Remarks']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Document Checklist -->
                <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                    <div class="row g-3">
                        <!-- Photograph Upload Widget -->
                        <div class="col-12 mb-2">
                            <div class="p-3 bg-light rounded-4 border d-flex align-items-center gap-3 flex-wrap">
                                <?php 
                                $editPhoto = intern_photo_url($intern['photo'] ?? null);
                                if ($editPhoto): ?>
                                    <img src="../<?= e($editPhoto); ?>" alt="Intern Photo" class="rounded-3 shadow-sm border" style="width:64px;height:64px;object-fit:cover;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-white border text-primary d-flex align-items-center justify-content-center shadow-sm" style="width:64px;height:64px;font-size:24px;">
                                        <i class="fas fa-portrait"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        <i class="fas fa-camera text-primary me-1"></i>Upload / Replace Intern Photograph
                                    </label>
                                    <input type="file" name="photo_file" class="form-control form-control-sm" accept="image/png, image/jpeg, image/jpg, image/webp">
                                    <small class="text-muted">Select an image (JPG, PNG, WEBP) to update the intern's photo on their profile and ID card.</small>
                                </div>
                                <?php if ($editPhoto): ?>
                                    <a href="id_card.php?id=<?= $id; ?>" target="_blank" class="btn btn-outline-success btn-sm fw-bold">
                                        <i class="fas fa-id-badge me-1"></i> View on ID Card
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="request_form" id="chk1" <?= $intern['Request_Form'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk1">
                                    <i class="fas fa-file-alt text-primary me-2"></i>Official Request Form
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="photograph" id="chk2" <?= $intern['Photograph'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk2">
                                    <i class="fas fa-portrait text-info me-2"></i>Passport Size Photograph
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="cv" id="chk3" <?= $intern['CV'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk3">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>Curriculum Vitae (CV / Resume)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="recommendation_letter" id="chk4" <?= $intern['Recommendation_Letter'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk4">
                                    <i class="fas fa-envelope-open-text text-warning me-2"></i>University Recommendation Letter
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="cnic_copy" id="chk5" <?= $intern['CNIC_copy'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk5">
                                    <i class="fas fa-id-card text-success me-2"></i>CNIC / B-Form Copy
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="student_id" id="chk6" <?= $intern['Student_ID'] ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="chk6">
                                    <i class="fas fa-address-card text-secondary me-2"></i>Student ID Card Copy
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="border-top pt-3 mt-4 text-end">
                <a href="intern_view.php?id=<?= $id; ?>" class="btn btn-light px-4 me-2">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="fas fa-save me-1"></i> Update Intern Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
