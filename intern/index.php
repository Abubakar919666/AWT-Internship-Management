<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Intern Portal Dashboard
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_intern();

$user = current_user();
$internId = $user['intern_id'];

// If intern_id is not directly in session, look up by email
if (!$internId) {
    $internRow = db_fetch_one("SELECT id FROM interns WHERE Email = ? OR user_id = ? LIMIT 1", [$user['email'], $user['id']]);
    if ($internRow) $internId = (int)$internRow['id'];
}

$intern = db_fetch_one("
    SELECT i.*, s.name AS supervisor_name, s.department AS supervisor_dept, s.email AS supervisor_email
    FROM interns i
    LEFT JOIN supervisors s ON i.supervisor_id = s.id
    WHERE i.id = ?
", [$internId]);

if (!$intern) {
    echo "Intern profile record not found. Please contact HR administration.";
    exit;
}

$pageTitle = 'Intern Dashboard';
$pageSubtitle = 'Welcome back, ' . e($intern['sname']);

// Fetch Tasks
$tasks = db_fetch_all("SELECT * FROM tasks WHERE intern_id = ? ORDER BY id DESC LIMIT 5", [$internId]);
$pendingTasksCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM tasks WHERE intern_id = ? AND status IN ('Pending', 'In Progress')", [$internId])['c'] ?? 0);
$completedTasksCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM tasks WHERE intern_id = ? AND status = 'Completed'", [$internId])['c'] ?? 0);

// Fetch Attendance
$attendanceStats = db_fetch_one("
    SELECT 
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_days,
        SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) AS leave_days
    FROM attendance WHERE intern_id = ?
", [$internId]);

$totalDays = (int)($attendanceStats['total_days'] ?? 0);
$presentDays = (int)($attendanceStats['present_days'] ?? 0);
$attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 100;

// Evaluation / Appraisal Score
$totalScore = (int)(
    $intern['punctuality'] + $intern['regularity'] + $intern['productivity'] +
    $intern['relationship_with_others'] + $intern['Initiative'] + $intern['Maturity'] +
    $intern['Confidence'] + $intern['Analytical_ability'] + $intern['abilityhardword'] + $intern['knowledge']
);
$gradeInfo = calculate_grade($totalScore);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="awt-card p-4 text-white mb-4" style="background:linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%);">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge bg-warning text-dark mb-2 fw-bold">Internship Session <?= e($intern['iyear'] ?: date('Y')); ?></span>
            <h2 class="fw-bold mb-1">Welcome, <?= e($intern['sname']); ?>!</h2>
            <p class="mb-0 text-white-50">
                <?= e($intern['sInstitute']); ?> &bull; <?= e($intern['Degree'] ?: 'Undergraduate'); ?> &bull;
                Tenure: <?= format_date($intern['dateassignfrom']); ?> &rarr; <?= format_date($intern['dateassignto']); ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="certificate.php" class="btn btn-warning fw-bold px-4 shadow-sm">
                <i class="fas fa-certificate me-1"></i> View Certificate
            </a>
        </div>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon primary">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $attendanceRate; ?>%</h3>
                <p>Attendance Rate</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card warning">
            <div class="kpi-icon warning">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $pendingTasksCount; ?></h3>
                <p>Pending Tasks</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card success">
            <div class="kpi-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $completedTasksCount; ?></h3>
                <p>Completed Tasks</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card info">
            <div class="kpi-icon info">
                <i class="fas fa-star"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $totalScore > 0 ? $totalScore . '/100' : 'In Progress'; ?></h3>
                <p>Evaluation Score</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Active Tasks Section -->
    <div class="col-lg-8">
        <div class="awt-table-container mb-4">
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-tasks text-primary me-2"></i>My Assigned Tasks</h5>
                <a href="tasks.php" class="btn btn-sm btn-outline-primary">View All Tasks</a>
            </div>
            <div class="table-responsive">
                <table class="table awt-table">
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No tasks currently assigned to you.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($task['title']); ?></div>
                                        <small class="text-muted d-block"><?= e($task['description']); ?></small>
                                    </td>
                                    <td><?= priority_badge($task['priority']); ?></td>
                                    <td class="small fw-semibold"><?= format_date($task['due_date']); ?></td>
                                    <td><?= task_status_badge($task['status']); ?></td>
                                    <td class="text-end">
                                        <a href="tasks.php?task_id=<?= $task['id']; ?>" class="btn btn-sm btn-primary">
                                            Submit Work
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mentorship & Work Scope -->
        <div class="awt-card">
            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-briefcase text-teal me-2"></i>My Project Work Scope</h5>
            <div class="p-3 bg-light rounded-3 text-dark small" style="white-space:pre-line;">
                <?= e($intern['Assignedwork'] ?: 'General administrative, social service, and community support rotations.'); ?>
            </div>
        </div>
    </div>

    <!-- Supervisor & Document Checklist Sidebar -->
    <div class="col-lg-4">
        <!-- Mentor Info Card -->
        <div class="awt-card mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fas fa-user-tie text-primary me-2"></i>Assigned Mentor</h5>
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="brand-icon" style="width:48px;height:48px;font-size:1.25rem;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><?= e($intern['Mentor'] ?: ($intern['supervisor_name'] ?: 'Department Head')); ?></h6>
                    <small class="text-primary"><?= e($intern['Department'] ?: 'Coordination Office'); ?></small>
                </div>
            </div>
            <?php if (!empty($intern['supervisor_email'])): ?>
                <div class="small text-muted border-top pt-2">
                    <i class="fas fa-envelope text-secondary me-1"></i> <?= e($intern['supervisor_email']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Links Card -->
        <div class="awt-card">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fas fa-link text-warning me-2"></i>My Documents</h5>
            <div class="d-grid gap-2">
                <a href="offer_letter.php" class="btn btn-outline-primary text-start">
                    <i class="fas fa-file-contract me-2"></i> Official Offer Letter
                </a>
                <a href="certificate.php" class="btn btn-outline-warning text-start">
                    <i class="fas fa-award me-2"></i> Completion Certificate
                </a>
                <a href="attendance.php" class="btn btn-outline-success text-start">
                    <i class="fas fa-calendar-alt me-2"></i> My Attendance Record
                </a>
                <a href="profile.php" class="btn btn-outline-secondary text-start">
                    <i class="fas fa-id-card me-2"></i> View Profile & Docs
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
