<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Add New Intern Record
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Add New Intern';
$pageSubtitle = "Register and onboard an intern to Alamgir Welfare Trust Int'l";
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
        $hours = sanitize($_POST['hours'] ?? '6 Weeks');
        $iyear = sanitize($_POST['iyear'] ?? date('Y'));
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
                // If supervisor selected, also get supervisor name
                if ($supervisorId && empty($mentor)) {
                    $supRow = db_fetch_one("SELECT name FROM supervisors WHERE id = ?", [$supervisorId]);
                    if ($supRow) $mentor = $supRow['name'];
                }

                $sql = "
                    INSERT INTO interns (
                        sname, Father_name, Email, Cellnumber, Address, Location, sInstitute, Degree, sterm,
                        Hours, iyear, status, confirmed, Waiting, dateassignfrom, dateassignto,
                        supervisor_id, Mentor, Department, GroupName, GroupTime, Assignedwork, Remarks,
                        Request_Form, Photograph, CV, Recommendation_Letter, CNIC_copy, Student_ID,
                        Organizationname, HRperson, HRdesignation, Dateofentry
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, 'Nisar Ahmed', 'Coordinator', NOW()
                    )
                ";

                db_query($sql, [
                    $sname, $fatherName, $email, $cell, $address, $location, $institute, $degree, $sterm,
                    $hours, $iyear, $status, $confirmed, $waiting, $dateFrom, $dateTo,
                    $supervisorId, $mentor, $department, $groupName, $groupTime, $assignedWork, $remarks,
                    $reqForm, $photo, $cv, $recomLetter, $cnic, $studentId, "Alamgir Welfare Trust Int'l"
                ]);

                $newId = db_last_insert_id();
                log_activity(current_user()['id'], 'Enrolled Intern', "Created intern #{$newId}: {$sname}");
                set_flash('success', "Intern record #{$newId} ({$sname}) has been created successfully.");
                header("Location: intern_view.php?id=" . $newId);
                exit;
            } catch (Exception $e) {
                $error = 'Error saving intern: ' . $e->getMessage();
            }
        }
    }
}

// Fetch active supervisors for dropdown
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

        <form action="intern_add.php" method="POST" class="awt-card p-4">
            <?= csrf_input(); ?>

            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h4 class="fw-bold mb-0 text-dark"><i class="fas fa-user-plus text-primary me-2"></i>New Intern Registration</h4>
                <a href="interns.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Roster</a>
            </div>

            <!-- Tab Navigation (Mirrors the original Access Form1 layout) -->
            <ul class="nav nav-pills mb-4" id="internAddTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="info-tab" data-bs-toggle="pill" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="fas fa-id-card me-1"></i> 1. Personal & Academic Info
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="program-tab" data-bs-toggle="pill" data-bs-target="#tab-program" type="button" role="tab">
                        <i class="fas fa-calendar-alt me-1"></i> 2. Internship & Mentor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="docs-tab" data-bs-toggle="pill" data-bs-target="#tab-docs" type="button" role="tab">
                        <i class="fas fa-check-square me-1"></i> 3. Document Checklist
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="internAddTabsContent">
                <!-- TAB 1: Personal & Academic -->
                <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="sname" class="form-control" required placeholder="e.g. Nabeel Afaq Chandna" value="<?= e($_POST['sname'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" placeholder="e.g. Afaq Nasir Chandna" value="<?= e($_POST['father_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com" value="<?= e($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cell / Mobile Number</label>
                            <input type="text" name="cellnumber" class="form-control" placeholder="0300-1234567" value="<?= e($_POST['cellnumber'] ?? ''); ?>">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Residential Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Full address" value="<?= e($_POST['address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location / Area</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Bahadurabad, Karachi" value="<?= e($_POST['location'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">University / Institute</label>
                            <input type="text" name="institute" class="form-control" placeholder="e.g. IBA, IoBM, SZABIST, KU, FAST" value="<?= e($_POST['institute'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Degree Program</label>
                            <input type="text" name="degree" class="form-control" placeholder="e.g. BBA, BSCS, BSAF" value="<?= e($_POST['degree'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Current Semester</label>
                            <input type="text" name="sterm" class="form-control" placeholder="e.g. 4th Semester" value="<?= e($_POST['sterm'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Internship & Supervisor -->
                <div class="tab-pane fade" id="tab-program" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Internship Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active (Currently Serving)</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="waiting">Waiting List</option>
                                <option value="pending">Pending Application</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Internship Year</label>
                            <input type="text" name="iyear" class="form-control" value="<?= date('Y'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duration / Required Hours</label>
                            <input type="text" name="hours" class="form-control" placeholder="e.g. 6 Weeks, 72 Hours" value="6 Weeks">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tenure Start Date (Date Assign From)</label>
                            <input type="date" name="dateassignfrom" class="form-control" value="<?= date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tenure End Date (Date Assign To)</label>
                            <input type="date" name="dateassignto" class="form-control" value="<?= date('Y-m-d', strtotime('+6 weeks')); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assign Organization Supervisor</label>
                            <select name="supervisor_id" class="form-select">
                                <option value="">Select Supervisor...</option>
                                <?php foreach ($supervisors as $sup): ?>
                                    <option value="<?= $sup['id']; ?>">
                                        <?= e($sup['name']); ?> (<?= e($sup['department'] ?: 'General'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mentor Name (Custom)</label>
                            <input type="text" name="mentor" class="form-control" placeholder="e.g. Muhammad Wali Saleem">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department Assigned</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Coordination, Media, Health Care, Admin">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Group Name / No</label>
                            <input type="number" name="group_name" class="form-control" placeholder="e.g. 1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Group Time</label>
                            <input type="text" name="group_time" class="form-control" placeholder="e.g. 10:00 AM - 02:00 PM">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Assigned Tasks / Initial Projects</label>
                            <textarea name="assigned_work" class="form-control" rows="3" placeholder="Description of projects, departmental duties, and tasks assigned..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Internal Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Administrative or HR notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Document Checklist -->
                <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                    <p class="text-muted small mb-3">Mark the verification status of student documents submitted to the HR office:</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="request_form" id="chk1" checked>
                                <label class="form-check-label fw-semibold" for="chk1">
                                    <i class="fas fa-file-alt text-primary me-2"></i>Official Request Form
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="photograph" id="chk2">
                                <label class="form-check-label fw-semibold" for="chk2">
                                    <i class="fas fa-portrait text-info me-2"></i>Passport Size Photograph
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="cv" id="chk3" checked>
                                <label class="form-check-label fw-semibold" for="chk3">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>Curriculum Vitae (CV / Resume)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="recommendation_letter" id="chk4">
                                <label class="form-check-label fw-semibold" for="chk4">
                                    <i class="fas fa-envelope-open-text text-warning me-2"></i>University Recommendation Letter
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="cnic_copy" id="chk5">
                                <label class="form-check-label fw-semibold" for="chk5">
                                    <i class="fas fa-id-card text-success me-2"></i>CNIC / B-Form Copy
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="checklist-card">
                                <input class="form-check-input" type="checkbox" name="student_id" id="chk6">
                                <label class="form-check-label fw-semibold" for="chk6">
                                    <i class="fas fa-address-card text-secondary me-2"></i>Student ID Card Copy
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="border-top pt-3 mt-4 text-end">
                <a href="interns.php" class="btn btn-light px-4 me-2">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="fas fa-save me-1"></i> Save Intern Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
