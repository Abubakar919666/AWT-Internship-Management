<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisor Dashboard
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_supervisor();

$pageTitle = 'Supervisor Dashboard';
$pageSubtitle = 'Monitor assigned interns, review tasks, and record evaluations';

$supContext = get_active_supervisor_context();
$activeSup = $supContext['supervisor'];
$isAll = $supContext['is_all'];
$allSupervisors = $supContext['all_supervisors'];

// Fetch stats according to active supervisor or all
if ($isAll) {
    $internCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns")['c'] ?? 0);
    $pendingTasks = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM tasks WHERE status IN ('Pending', 'Under Review', 'In Progress')")['c'] ?? 0);
    $assignedInterns = db_fetch_all("
        SELECT id, sname, sInstitute, Degree, dateassignfrom, dateassignto, status, punctuality, Mentor, supervisor_id,
               (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
        FROM interns
        ORDER BY id DESC
        LIMIT 10
    ");
    $reviewTasks = db_fetch_all("
        SELECT t.*, i.sname AS intern_name
        FROM tasks t
        JOIN interns i ON t.intern_id = i.id
        WHERE t.status = 'Under Review'
        ORDER BY t.id DESC
        LIMIT 5
    ");
} else {
    $internCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns WHERE supervisor_id = ? OR Mentor LIKE ?", $supContext['filter_params'])['c'] ?? 0);
    $pendingTasks = (int)(db_fetch_one("
        SELECT COUNT(*) AS c 
        FROM tasks t 
        JOIN interns i ON t.intern_id = i.id 
        WHERE (i.supervisor_id = ? OR t.supervisor_id = ?) AND t.status IN ('Pending', 'Under Review', 'In Progress')
    ", [$activeSup['id'], $activeSup['id']])['c'] ?? 0);
    $assignedInterns = db_fetch_all("
        SELECT id, sname, sInstitute, Degree, dateassignfrom, dateassignto, status, punctuality, Mentor, supervisor_id,
               (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
        FROM interns
        WHERE supervisor_id = ? OR Mentor LIKE ?
        ORDER BY id DESC
        LIMIT 10
    ", $supContext['filter_params']);
    $reviewTasks = db_fetch_all("
        SELECT t.*, i.sname AS intern_name
        FROM tasks t
        JOIN interns i ON t.intern_id = i.id
        WHERE (i.supervisor_id = ? OR t.supervisor_id = ?) AND t.status = 'Under Review'
        ORDER BY t.id DESC
        LIMIT 5
    ", [$activeSup['id'], $activeSup['id']]);
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Supervisor Switcher Ribbon -->
<?php render_supervisor_switcher_ribbon($supContext); ?>

<!-- Supervisor KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card">
            <div class="kpi-icon primary">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $internCount; ?></h3>
                <p><?= $isAll ? 'Total Supervised Interns' : 'Assigned Interns'; ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="kpi-card warning">
            <div class="kpi-icon warning">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $pendingTasks; ?></h3>
                <p>Active / Review Tasks</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="kpi-card success">
            <div class="kpi-icon success">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="kpi-info">
                <h3>Daily</h3>
                <p>Attendance Monitoring</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Supervised Interns Roster -->
    <div class="col-lg-8">
        <div class="awt-table-container">
            <div class="p-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-users text-primary me-2"></i>
                    <?= $isAll ? 'All Supervised Interns' : 'My Supervised Interns (' . e($activeSup['name']) . ')'; ?>
                </h5>
                <a href="interns.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table awt-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Institute</th>
                            <?php if ($isAll): ?><th>Mentor / Dept</th><?php endif; ?>
                            <th>Tenure Period</th>
                            <th>Appraisal</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignedInterns)): ?>
                            <tr>
                                <td colspan="<?= $isAll ? 7 : 6; ?>" class="text-center py-4 text-muted">
                                    No interns currently assigned. Administrators can assign interns from the Admin portal.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedInterns as $intern): ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                                    <td class="fw-bold text-primary"><?= e($intern['sname']); ?></td>
                                    <td>
                                        <div class="small fw-semibold"><?= e($intern['sInstitute'] ?: '—'); ?></div>
                                        <small class="text-muted"><?= e($intern['Degree'] ?: '—'); ?></small>
                                    </td>
                                    <?php if ($isAll): ?>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-user-tie text-primary me-1"></i>
                                                <?= e($intern['Mentor'] ?: 'Unassigned'); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td class="small text-muted">
                                        <?= format_date($intern['dateassignfrom']); ?> &rarr; <?= format_date($intern['dateassignto']); ?>
                                    </td>
                                    <td>
                                        <?php if ($intern['total_score'] > 0): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><?= $intern['total_score']; ?>/100</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Ungraded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="appraisal.php?intern_id=<?= $intern['id']; ?>" class="btn btn-sm btn-outline-primary" title="Grade Intern">
                                            <i class="fas fa-star-half-alt me-1"></i> Grade
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Tasks Under Review -->
    <div class="col-lg-4">
        <div class="awt-card mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="attendance.php" class="btn btn-awt-success text-start">
                    <i class="fas fa-calendar-check me-2"></i> Mark Intern Attendance
                </a>
                <a href="tasks.php" class="btn btn-outline-primary text-start">
                    <i class="fas fa-tasks me-2"></i> Assign & Review Tasks
                </a>
                <a href="appraisal.php" class="btn btn-outline-dark text-start">
                    <i class="fas fa-clipboard-check me-2"></i> Performance Appraisals
                </a>
            </div>
        </div>

        <div class="awt-card">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fas fa-glasses text-info me-2"></i>Tasks Under Review</h5>
            <?php if (empty($reviewTasks)): ?>
                <p class="small text-muted mb-0">No submissions currently awaiting review.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($reviewTasks as $rt): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark"><?= e($rt['title']); ?></div>
                                <small class="text-muted"><?= e($rt['intern_name']); ?></small>
                            </div>
                            <a href="tasks.php?task_id=<?= $rt['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
