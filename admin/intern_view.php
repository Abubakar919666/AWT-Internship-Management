<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * 360° Intern Profile View
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$intern = db_fetch_one("
    SELECT i.*, s.name AS supervisor_name, s.department AS supervisor_dept
    FROM interns i
    LEFT JOIN supervisors s ON i.supervisor_id = s.id
    WHERE i.id = ?
", [$id]);

if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

ensure_intern_photo_column();
$photoUrl = intern_photo_url($intern['photo'] ?? null);
$hasPhoto = !empty($photoUrl);

$pageTitle = e($intern['sname']);
$pageSubtitle = "Intern Profile #{$id} — " . e($intern['sInstitute']);

// Fetch Tasks for this intern
$tasks = db_fetch_all("SELECT * FROM tasks WHERE intern_id = ? ORDER BY id DESC", [$id]);

// Fetch Attendance records for this intern
$attendanceRecords = db_fetch_all("SELECT * FROM attendance WHERE intern_id = ? ORDER BY attendance_date DESC LIMIT 30", [$id]);
$attendanceStats = db_fetch_one("
    SELECT 
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_days,
        SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) AS leave_days
    FROM attendance WHERE intern_id = ?
", [$id]);

$totalDays = (int)($attendanceStats['total_days'] ?? 0);
$presentDays = (int)($attendanceStats['present_days'] ?? 0);
$attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;

// Evaluation / Appraisal Metrics (10 criteria)
$criteria = [
    'Punctuality'               => (int)($intern['punctuality'] ?? 0),
    'Regularity'                => (int)($intern['regularity'] ?? 0),
    'Productivity'              => (int)($intern['productivity'] ?? 0),
    'Relationship with Others'  => (int)($intern['relationship_with_others'] ?? 0),
    'Initiative'                => (int)($intern['Initiative'] ?? 0),
    'Maturity'                  => (int)($intern['Maturity'] ?? 0),
    'Confidence'                => (int)($intern['Confidence'] ?? 0),
    'Analytical Ability'        => (int)($intern['Analytical_ability'] ?? 0),
    'Hard Work & Dedication'    => (int)($intern['abilityhardword'] ?? 0),
    'Subject Knowledge'         => (int)($intern['knowledge'] ?? 0),
];

$totalScore = array_sum($criteria);
$gradeInfo = calculate_grade($totalScore);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Profile Header Banner -->
<div class="awt-card mb-4 bg-white">
    <div class="row align-items-center g-3">
        <div class="col-auto">
            <div class="position-relative intern-avatar-wrapper" style="width:78px;height:78px;">
                <?php if ($hasPhoto): ?>
                    <img src="../<?= e($photoUrl); ?>" alt="<?= e($intern['sname']); ?>" 
                         class="shadow-sm intern-avatar-img" 
                         style="width:78px;height:78px;object-fit:cover;border-radius:18px;border:3px solid #ffffff;cursor:pointer;display:block;"
                         data-bs-toggle="modal" data-bs-target="#viewPhotoModal" title="Click to view full photograph">
                <?php else: ?>
                    <div class="brand-icon shadow-sm" 
                         style="width:78px;height:78px;font-size:2.2rem;border-radius:18px;cursor:pointer;" 
                         data-bs-toggle="modal" data-bs-target="#uploadPhotoModal" title="Click to upload or scan photograph">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                <?php endif; ?>

                <!-- Upload/Scan Photo Button on Avatar -->
                <button type="button" 
                        class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 shadow border border-2 border-white d-flex align-items-center justify-content-center p-0" 
                        style="width:28px;height:28px;transform:translate(18%, 18%);z-index:2;" 
                        data-bs-toggle="modal" 
                        data-bs-target="#uploadPhotoModal" 
                        title="Upload or scan intern photo">
                    <i class="fas fa-camera text-white" style="font-size:11px;"></i>
                </button>
            </div>
        </div>
        <div class="col">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h3 class="fw-bold mb-0 text-dark"><?= e($intern['sname']); ?></h3>
                <span class="badge bg-light text-muted border">ID: #<?= $intern['id']; ?></span>
                <?= status_badge($intern['status']); ?>
                <?php if ($totalScore > 0): ?>
                    <span class="badge bg-<?= $gradeInfo['color']; ?> px-3 py-1 fw-bold">Grade <?= $gradeInfo['grade']; ?> (<?= $totalScore; ?>/100)</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0">
                <i class="fas fa-university me-1 text-secondary"></i><strong><?= e($intern['sInstitute'] ?: 'Institution Not Listed'); ?></strong> &bull;
                <span><?= e($intern['Degree'] ?: 'Degree N/A'); ?></span>
                <?php if (!empty($intern['sterm'])): ?> &bull; <span><?= e($intern['sterm']); ?></span><?php endif; ?>
            </p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                <i class="fas fa-camera me-1"></i> Upload Photo
            </button>
            <a href="id_card.php?id=<?= $intern['id']; ?>" class="btn btn-outline-success fw-bold" target="_blank">
                <i class="fas fa-id-badge me-1"></i> ID Card
            </a>
            <a href="certificate.php?id=<?= $intern['id']; ?>" class="btn btn-outline-warning fw-bold">
                <i class="fas fa-certificate me-1"></i> Certificate
            </a>
            <a href="offer_letter.php?id=<?= $intern['id']; ?>" class="btn btn-outline-info text-dark fw-bold">
                <i class="fas fa-envelope-open-text me-1"></i> Offer Letter
            </a>
            <a href="intern_edit.php?id=<?= $intern['id']; ?>" class="btn btn-primary fw-bold">
                <i class="fas fa-edit me-1"></i> Edit Record
            </a>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">
            <i class="fas fa-id-card me-1"></i> Profile & Documents
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="appraisal-tab" data-bs-toggle="pill" data-bs-target="#tab-appraisal" type="button">
            <i class="fas fa-star-half-alt me-1"></i> Performance Appraisal (<?= $totalScore; ?>/100)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tab-tasks" type="button">
            <i class="fas fa-tasks me-1"></i> Assigned Tasks (<?= count($tasks); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="attendance-tab" data-bs-toggle="pill" data-bs-target="#tab-attendance" type="button">
            <i class="fas fa-calendar-check me-1"></i> Attendance Log (<?= $attendanceRate; ?>%)
        </button>
    </li>
</ul>

<div class="tab-content" id="profileTabsContent">
    <!-- TAB 1: Overview & Details -->
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="awt-card">
                    <div class="card-header-clean">
                        <h5><i class="fas fa-info-circle text-primary"></i> Academic & Contact Information</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Father's Name</label>
                            <span class="fw-semibold"><?= e($intern['Father_name'] ?: '—'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Email Address</label>
                            <span class="fw-semibold"><?= e($intern['Email'] ?: '—'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Cell Number</label>
                            <span class="fw-semibold"><?= e($intern['Cellnumber'] ?: '—'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Location / Area</label>
                            <span class="fw-semibold"><?= e($intern['Location'] ?: 'Karachi'); ?></span>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Residential Address</label>
                            <span class="fw-semibold"><?= e($intern['Address'] ?: '—'); ?></span>
                        </div>
                    </div>

                    <div class="card-header-clean mt-4">
                        <h5><i class="fas fa-briefcase text-teal"></i> Internship Assignment Details</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Duration / Hours</label>
                            <span class="fw-semibold"><?= e($intern['Hours'] ?: '6 Weeks'); ?></span>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Tenure From</label>
                            <span class="fw-semibold"><?= format_date($intern['dateassignfrom']); ?></span>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small d-block">Tenure To</label>
                            <span class="fw-semibold"><?= format_date($intern['dateassignto']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Assigned Mentor / Supervisor</label>
                            <span class="fw-semibold text-primary"><?= e($intern['Mentor'] ?: ($intern['supervisor_name'] ?: 'Not Assigned')); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Department</label>
                            <span class="fw-semibold"><?= e($intern['Department'] ?: 'General Administration'); ?></span>
                        </div>
                        <?php if (!empty($intern['GroupName'])): ?>
                            <div class="col-sm-6">
                                <label class="text-muted small d-block">Group Name & Time</label>
                                <span class="fw-semibold">Group #<?= $intern['GroupName']; ?> (<?= e($intern['GroupTime']); ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="text-muted small d-block">Assigned Work / Projects</label>
                            <div class="p-3 bg-light rounded-3 text-dark small" style="white-space:pre-line;">
                                <?= e($intern['Assignedwork'] ?: 'No specific work summary recorded.'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Checklist Card -->
            <div class="col-lg-4">
                <div class="awt-card mb-4">
                    <div class="card-header-clean">
                        <h5><i class="fas fa-tasks text-warning"></i> Documents Submitted</h5>
                    </div>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-file-alt text-primary me-2"></i>Official Request Form</span>
                            <span class="badge bg-<?= $intern['Request_Form'] ? 'success' : 'secondary'; ?>"><?= $intern['Request_Form'] ? 'Verified' : 'Pending'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-portrait text-info me-2"></i>Photograph</span>
                            <div class="d-flex align-items-center gap-1">
                                <?php if ($hasPhoto || $intern['Photograph']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Received</span>
                                    <?php if ($hasPhoto): ?>
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 ms-1" style="font-size:11px;" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" title="View Full Photograph">
                                            <i class="fas fa-eye me-1"></i>View
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pending</span>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 ms-1" style="font-size:11px;" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal" title="Upload Photograph">
                                        <i class="fas fa-upload me-1"></i>Upload
                                    </button>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-file-pdf text-danger me-2"></i>Curriculum Vitae (CV)</span>
                            <span class="badge bg-<?= $intern['CV'] ? 'success' : 'secondary'; ?>"><?= $intern['CV'] ? 'Received' : 'Pending'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-envelope-open-text text-warning me-2"></i>Recommendation Letter</span>
                            <span class="badge bg-<?= $intern['Recommendation_Letter'] ? 'success' : 'secondary'; ?>"><?= $intern['Recommendation_Letter'] ? 'Verified' : 'Pending'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-id-card text-success me-2"></i>CNIC Copy</span>
                            <span class="badge bg-<?= $intern['CNIC_copy'] ? 'success' : 'secondary'; ?>"><?= $intern['CNIC_copy'] ? 'Received' : 'Pending'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-address-card text-secondary me-2"></i>Student ID Card</span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-<?= $intern['Student_ID'] ? 'success' : 'secondary'; ?>"><?= $intern['Student_ID'] ? 'Received' : 'Pending'; ?></span>
                                <a href="id_card.php?id=<?= $intern['id']; ?>" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2 ms-1" style="font-size:11px;" title="View Official Intern ID Card">
                                    <i class="fas fa-id-badge me-1"></i>ID Pass
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- HR & Coordinator Badge -->
                <div class="awt-card bg-light border-0">
                    <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-stamp text-primary me-2"></i>Certification Authority</h6>
                    <p class="small text-muted mb-1">Coordinated by:</p>
                    <div class="fw-bold text-dark"><?= e($intern['HRperson'] ?: 'Nisar Ahmed'); ?></div>
                    <div class="small text-muted mb-2"><?= e($intern['HRdesignation'] ?: 'Coordinator'); ?></div>
                    <div class="border-top pt-2 mt-2 small text-muted">
                        Certificate Session: <strong><?= e($intern['iyear'] ?: date('Y')); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: 10-Criteria Performance Appraisal -->
    <div class="tab-pane fade" id="tab-appraisal">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="awt-card">
                    <div class="card-header-clean">
                        <h5><i class="fas fa-chart-pie text-primary"></i> 10-Criteria Evaluation Radar</h5>
                        <a href="appraisal.php?intern_id=<?= $intern['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sliders-h me-1"></i> Update Ratings
                        </a>
                    </div>
                    <div style="position: relative; height: 350px;">
                        <canvas id="appraisalRadarChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="awt-card">
                    <div class="card-header-clean">
                        <h5><i class="fas fa-list-ol text-teal"></i> Criteria Scores Breakdown</h5>
                        <span class="badge bg-<?= $gradeInfo['color']; ?> fs-6"><?= $gradeInfo['desc']; ?> (<?= $totalScore; ?>/100)</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm awt-table">
                            <thead>
                                <tr>
                                    <th>Criteria Dimension</th>
                                    <th class="text-center">Score (1-10)</th>
                                    <th>Rating Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $critName => $score): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= $critName; ?></td>
                                        <td class="text-center fw-bold text-primary"><?= $score; ?> / 10</td>
                                        <td style="width: 40%;">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar <?= $score >= 8 ? 'bg-success' : ($score >= 6 ? 'bg-primary' : 'bg-warning'); ?>" 
                                                     role="progressbar" style="width: <?= $score * 10; ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Supervisor Comments -->
                    <div class="mt-3 p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold mb-1 text-dark"><i class="fas fa-comment-alt text-primary me-2"></i>Evaluator Comments & Remarks</h6>
                        <p class="small text-muted mb-0" style="white-space:pre-line;">
                            <?= e($intern['comments'] ?: 'No evaluation comments recorded yet.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Tasks -->
    <div class="tab-pane fade" id="tab-tasks">
        <div class="awt-card">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-tasks text-primary me-2"></i>Tasks Assigned to <?= e($intern['sname']); ?></h5>
                <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                    <i class="fas fa-plus me-1"></i> Assign New Task
                </button>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list display-6 text-secondary mb-3 d-block"></i>
                    No tasks currently assigned to this intern.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table awt-table">
                        <thead>
                            <tr>
                                <th>Task Title</th>
                                <th>Priority</th>
                                <th>Assigned Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <?= e($task['title']); ?>
                                        <?php if (!empty($task['description'])): ?>
                                            <small class="text-muted d-block font-normal"><?= e($task['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= priority_badge($task['priority']); ?></td>
                                    <td class="small"><?= format_date($task['assigned_date']); ?></td>
                                    <td class="small fw-semibold <?= strtotime($task['due_date']) < time() && $task['status'] !== 'Completed' ? 'text-danger' : ''; ?>">
                                        <?= format_date($task['due_date']); ?>
                                    </td>
                                    <td><?= task_status_badge($task['status']); ?></td>
                                    <td class="text-end">
                                        <a href="tasks.php?intern_id=<?= $intern['id']; ?>&task_id=<?= $task['id']; ?>" class="btn btn-sm btn-light border" title="Manage Task">
                                            <i class="fas fa-external-link-alt text-primary"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB 4: Attendance -->
    <div class="tab-pane fade" id="tab-attendance">
        <div class="awt-card">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-muted small">Total Logged Days</span>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalDays; ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-muted small">Present</span>
                        <h4 class="fw-bold text-success mb-0"><?= $presentDays; ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-muted small">Absent</span>
                        <h4 class="fw-bold text-danger mb-0"><?= (int)($attendanceStats['absent_days'] ?? 0); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <span class="text-muted small">Attendance Rate</span>
                        <h4 class="fw-bold text-primary mb-0"><?= $attendanceRate; ?>%</h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table awt-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceRecords)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No attendance entries recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceRecords as $att): ?>
                                <tr>
                                    <td class="fw-semibold"><?= format_date($att['attendance_date']); ?></td>
                                    <td><?= status_badge($att['status']); ?></td>
                                    <td class="small"><?= $att['time_in'] ? date('h:i A', strtotime($att['time_in'])) : '—'; ?></td>
                                    <td class="small"><?= $att['time_out'] ? date('h:i A', strtotime($att['time_out'])) : '—'; ?></td>
                                    <td class="small text-muted"><?= e($att['remarks'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Assign Task -->
<div class="modal fade" id="assignTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="tasks.php" method="POST" class="modal-content">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create_task">
            <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Assign Task to <?= e($intern['sname']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Conduct Survey of OPD Services">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description / Scope</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Provide instructions for the intern..."></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> Assign Task</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Upload / Scan Intern Photograph    -->
<!-- ========================================== -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="fas fa-camera text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="uploadPhotoModalLabel">Upload Intern Photograph</h5>
                        <small class="text-white-50"><?= e($intern['sname']); ?> &bull; ID #<?= $intern['id']; ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Upload Method Tabs -->
                <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill" id="photoSourceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold" id="file-tab" data-bs-toggle="pill" data-bs-target="#tab-file-upload" type="button" role="tab">
                            <i class="fas fa-cloud-upload-alt me-1"></i> File / Scanned Image
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold" id="cam-tab" data-bs-toggle="pill" data-bs-target="#tab-camera-scan" type="button" role="tab">
                            <i class="fas fa-video me-1"></i> Camera / Scanner Live
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="photoSourceTabsContent">
                    <!-- TAB 1: File / Scanned Document Upload -->
                    <div class="tab-pane fade show active" id="tab-file-upload" role="tabpanel">
                        <form action="intern_photo_upload.php" method="POST" enctype="multipart/form-data" id="fileUploadForm">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">
                            <input type="hidden" name="action" value="upload">

                            <div class="photo-drop-zone text-center p-4 border border-2 border-dashed rounded-4 bg-light position-relative mb-3" 
                                 id="photoDropZone" style="border-color:#cbd5e1 !important; transition: all 0.25s ease; cursor:pointer;">
                                <input type="file" name="photo_file" id="photoFileInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" accept="image/png, image/jpeg, image/jpg, image/webp" style="cursor:pointer;">
                                
                                <div id="dropZoneContent">
                                    <div class="mb-3">
                                        <i class="fas fa-id-badge text-primary" style="font-size:3.5rem;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Click to browse or drag & drop scanned picture</h6>
                                    <p class="text-muted small mb-2">Supports JPG, PNG, WEBP (Max 5MB). Passport or ID format recommended.</p>
                                    <span class="btn btn-outline-primary btn-sm px-3 rounded-pill fw-semibold">
                                        <i class="fas fa-folder-open me-1"></i> Choose Image File
                                    </span>
                                </div>

                                <!-- Live Selected File Preview -->
                                <div id="filePreviewContainer" class="d-none mt-2">
                                    <div class="position-relative d-inline-block">
                                        <img id="filePreviewImg" src="" alt="Preview" class="shadow-sm rounded-4 border" style="width:130px;height:150px;object-fit:cover;">
                                        <button type="button" id="clearFileBtn" class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 shadow" style="transform:translate(30%, -30%);" title="Clear selection">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2 text-dark fw-bold small" id="fileInfoText"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    <i class="fas fa-shield-alt text-success me-1"></i> Will appear instantly on intern profile & ID card.
                                </div>
                                <button type="submit" id="submitFileBtn" class="btn btn-primary fw-bold px-4 rounded-pill" disabled>
                                    <i class="fas fa-upload me-1"></i> Upload & Apply Photo
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: Live Camera / Scanner Capture -->
                    <div class="tab-pane fade" id="tab-camera-scan" role="tabpanel">
                        <form action="intern_photo_upload.php" method="POST" id="cameraUploadForm">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="captured_image_data" id="capturedImageData" value="">

                            <div class="text-center p-3 bg-light rounded-4 mb-3 border">
                                <!-- Camera Feed Container -->
                                <div id="camLiveSection">
                                    <div class="position-relative mx-auto rounded-4 overflow-hidden shadow-sm bg-dark" style="max-width:400px; height:300px;">
                                        <video id="webcamVideo" autoplay playsinline class="w-100 h-100" style="object-fit:cover;"></video>
                                        <!-- ID Photo Guideline Frame -->
                                        <div class="position-absolute top-50 start-50 translate-middle border border-2 border-warning rounded-4" 
                                             style="width:170px; height:210px; pointer-events:none; box-shadow:0 0 0 9999px rgba(0,0,0,0.45);">
                                            <span class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark" style="font-size:9px;">Align Face / Photo</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 d-flex justify-content-center gap-2">
                                        <button type="button" id="startCamBtn" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold">
                                            <i class="fas fa-play me-1"></i> Start Camera
                                        </button>
                                        <button type="button" id="snapCamBtn" class="btn btn-success btn-sm rounded-pill fw-bold px-3 d-none">
                                            <i class="fas fa-camera me-1"></i> Capture Snapshot
                                        </button>
                                    </div>
                                    <div id="cameraStatusMsg" class="small text-muted mt-2">Click "Start Camera" to access scanner or webcam feed.</div>
                                </div>

                                <!-- Hidden Canvas for capture -->
                                <canvas id="webcamCanvas" class="d-none"></canvas>

                                <!-- Snapshot Preview Section -->
                                <div id="camSnapshotSection" class="d-none text-center">
                                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-check-circle me-1"></i>Photo Captured Successfully</h6>
                                    <div class="d-inline-block p-1 bg-white rounded-4 shadow-sm border mb-3">
                                        <img id="snapshotPreviewImg" src="" alt="Snapshot" style="width:140px;height:165px;object-fit:cover;border-radius:12px;">
                                    </div>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" id="retakeCamBtn" class="btn btn-outline-secondary btn-sm rounded-pill">
                                            <i class="fas fa-redo me-1"></i> Retake
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-bold px-4">
                                            <i class="fas fa-save me-1"></i> Save This Photo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Existing Photo Option -->
                <?php if ($hasPhoto): ?>
                    <div class="border-top pt-3 mt-4 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="fas fa-image me-1"></i> An active photograph is currently attached to this intern.
                        </div>
                        <form action="intern_photo_upload.php" method="POST" onsubmit="return confirm('Are you sure you want to remove the photograph for this intern?');" class="m-0">
                            <?= csrf_input(); ?>
                            <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                <i class="fas fa-trash-alt me-1"></i> Remove Current Photo
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: View Full High-Res Photograph      -->
<!-- ========================================== -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-labelledby="viewPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg text-center" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                <span class="small fw-semibold"><i class="fas fa-user-graduate me-1 text-primary"></i> <?= e($intern['sname']); ?> (ID: #<?= $intern['id']; ?>)</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="d-inline-block p-2 bg-white rounded-4 shadow-sm border mb-3">
                    <?php if ($hasPhoto): ?>
                        <img src="../<?= e($photoUrl); ?>" alt="<?= e($intern['sname']); ?>" 
                             class="img-fluid rounded-3" style="max-height:360px; max-width:100%; object-fit:contain;">
                    <?php else: ?>
                        <div class="p-5 text-muted">
                            <i class="fas fa-user-graduate fa-4x mb-3 text-secondary"></i>
                            <p class="mb-0">No photograph uploaded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= e($intern['sname']); ?></h5>
                <p class="text-muted small mb-0"><?= e($intern['sInstitute'] ?: 'University Student'); ?> &bull; <?= e($intern['Degree'] ?: 'Undergraduate'); ?></p>
            </div>
            <div class="modal-footer justify-content-between bg-white border-0 py-2 px-3">
                <a href="id_card.php?id=<?= $intern['id']; ?>" class="btn btn-outline-success btn-sm fw-bold rounded-pill" target="_blank">
                    <i class="fas fa-id-badge me-1"></i> Open ID Card
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill fw-semibold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                        <i class="fas fa-camera me-1"></i> Change Photo
                    </button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill border" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ----------------------------------------------------
    // Radar Chart initialization
    // ----------------------------------------------------
    const ctxRadar = document.getElementById('appraisalRadarChart');
    if (ctxRadar) {
        new Chart(ctxRadar.getContext('2d'), {
            type: 'radar',
            data: {
                labels: <?= json_encode(array_keys($criteria)); ?>,
                datasets: [{
                    label: 'Score (out of 10)',
                    data: <?= json_encode(array_values($criteria)); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.2)',
                    borderColor: '#2563eb',
                    pointBackgroundColor: '#1e3a8a',
                    pointBorderColor: '#ffffff',
                    pointHoverBackgroundColor: '#ffffff',
                    pointHoverBorderColor: '#1e3a8a',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 10,
                        ticks: { stepSize: 2 }
                    }
                }
            }
        });
    }

    // ----------------------------------------------------
    // Photo Upload File Drag-and-Drop & Instant Preview
    // ----------------------------------------------------
    const dropZone = document.getElementById('photoDropZone');
    const fileInput = document.getElementById('photoFileInput');
    const dropContent = document.getElementById('dropZoneContent');
    const previewContainer = document.getElementById('filePreviewContainer');
    const previewImg = document.getElementById('filePreviewImg');
    const fileInfoText = document.getElementById('fileInfoText');
    const submitFileBtn = document.getElementById('submitFileBtn');
    const clearFileBtn = document.getElementById('clearFileBtn');

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            handleFileSelect(this.files[0]);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#0284c7';
                dropZone.style.background = '#e0f2fe';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = '#cbd5e1';
                dropZone.style.background = '#f8fafc';
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                fileInput.files = dt.files;
                handleFileSelect(dt.files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file) return;
            if (!file.type.match('image.*')) {
                alert('Please select a valid image file (JPG, PNG, or WEBP).');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                fileInfoText.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                dropContent.classList.add('d-none');
                previewContainer.classList.remove('d-none');
                submitFileBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }

        if (clearFileBtn) {
            clearFileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                previewImg.src = '';
                previewContainer.classList.add('d-none');
                dropContent.classList.remove('d-none');
                submitFileBtn.disabled = true;
            });
        }
    }

    // ----------------------------------------------------
    // Live Webcam / Document Scanner Capture
    // ----------------------------------------------------
    let mediaStream = null;
    const startCamBtn = document.getElementById('startCamBtn');
    const snapCamBtn = document.getElementById('snapCamBtn');
    const retakeCamBtn = document.getElementById('retakeCamBtn');
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    const camLiveSection = document.getElementById('camLiveSection');
    const camSnapshotSection = document.getElementById('camSnapshotSection');
    const snapshotPreviewImg = document.getElementById('snapshotPreviewImg');
    const capturedImageData = document.getElementById('capturedImageData');
    const cameraStatusMsg = document.getElementById('cameraStatusMsg');
    const uploadPhotoModal = document.getElementById('uploadPhotoModal');

    function stopCamera() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
        if (video) video.srcObject = null;
        if (startCamBtn) startCamBtn.classList.remove('d-none');
        if (snapCamBtn) snapCamBtn.classList.add('d-none');
        if (cameraStatusMsg) cameraStatusMsg.textContent = 'Camera turned off.';
    }

    if (startCamBtn) {
        startCamBtn.addEventListener('click', async function() {
            try {
                cameraStatusMsg.textContent = 'Requesting camera access...';
                mediaStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 720 }, height: { ideal: 720 }, facingMode: 'user' },
                    audio: false
                });
                video.srcObject = mediaStream;
                startCamBtn.classList.add('d-none');
                snapCamBtn.classList.remove('d-none');
                cameraStatusMsg.textContent = 'Camera active. Frame portrait in the yellow box.';
            } catch (err) {
                console.error('Camera access error:', err);
                cameraStatusMsg.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Could not access camera. Please check permissions or use standard File Upload tab.</span>';
            }
        });
    }

    if (snapCamBtn) {
        snapCamBtn.addEventListener('click', function() {
            if (!video.videoWidth) return;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
            capturedImageData.value = dataUrl;
            snapshotPreviewImg.src = dataUrl;

            camLiveSection.classList.add('d-none');
            camSnapshotSection.classList.remove('d-none');
            stopCamera();
        });
    }

    if (retakeCamBtn) {
        retakeCamBtn.addEventListener('click', function() {
            camSnapshotSection.classList.add('d-none');
            camLiveSection.classList.remove('d-none');
            capturedImageData.value = '';
            if (startCamBtn) startCamBtn.click();
        });
    }

    // Stop camera when modal is closed
    if (uploadPhotoModal) {
        uploadPhotoModal.addEventListener('hidden.bs.modal', function() {
            stopCamera();
            if (camSnapshotSection) camSnapshotSection.classList.add('d-none');
            if (camLiveSection) camLiveSection.classList.remove('d-none');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
