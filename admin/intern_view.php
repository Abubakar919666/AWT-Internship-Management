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
            <div class="brand-icon" style="width:72px;height:72px;font-size:2rem;border-radius:18px;">
                <i class="fas fa-user-graduate"></i>
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
                            <span class="badge bg-<?= $intern['Photograph'] ? 'success' : 'secondary'; ?>"><?= $intern['Photograph'] ? 'Received' : 'Pending'; ?></span>
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
                            <span class="badge bg-<?= $intern['Student_ID'] ? 'success' : 'secondary'; ?>"><?= $intern['Student_ID'] ? 'Received' : 'Pending'; ?></span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Render Appraisal Radar Chart
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
