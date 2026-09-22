<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisor - My Assigned Interns
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_supervisor();

$pageTitle = 'My Assigned Interns';
$pageSubtitle = 'Roster of interns under your supervision';

$user = current_user();
$supervisorId = $user['supervisor_id'];
if (!$supervisorId) {
    $supRow = db_fetch_one("SELECT id FROM supervisors WHERE email = ? OR user_id = ? LIMIT 1", [$user['email'], $user['id']]);
    if ($supRow) $supervisorId = (int)$supRow['id'];
}

$interns = db_fetch_all("
    SELECT id, sname, Father_name, Email, Cellnumber, sInstitute, Degree, sterm,
           dateassignfrom, dateassignto, Hours, status, punctuality,
           (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score,
           comments
    FROM interns
    WHERE supervisor_id = ? OR Mentor LIKE ?
    ORDER BY id DESC
", [$supervisorId, "%{$user['name']}%"]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="awt-table-container">
    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">Total Supervised Interns: <span class="text-primary"><?= count($interns); ?></span></h5>
    </div>
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Intern Name</th>
                    <th>University & Degree</th>
                    <th>Contact Info</th>
                    <th>Duration & Dates</th>
                    <th>Status</th>
                    <th>Appraisal</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($interns)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-users display-6 text-secondary mb-3 d-block"></i>
                            No interns currently assigned to you.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($interns as $intern): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                            <td class="fw-bold text-primary"><?= e($intern['sname']); ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($intern['sInstitute'] ?: '—'); ?></div>
                                <small class="text-muted"><?= e($intern['Degree'] ?: '—'); ?> <?= !empty($intern['sterm']) ? '(' . e($intern['sterm']) . ')' : ''; ?></small>
                            </td>
                            <td class="small">
                                <div><i class="fas fa-envelope text-muted me-1"></i><?= e($intern['Email'] ?: '—'); ?></div>
                                <div><i class="fas fa-phone text-muted me-1"></i><?= e($intern['Cellnumber'] ?: '—'); ?></div>
                            </td>
                            <td class="small text-muted">
                                <span class="badge bg-light text-dark border mb-1"><?= e($intern['Hours'] ?: '6 Weeks'); ?></span>
                                <div><?= format_date($intern['dateassignfrom']); ?> &rarr; <?= format_date($intern['dateassignto']); ?></div>
                            </td>
                            <td><?= status_badge($intern['status']); ?></td>
                            <td>
                                <?php if ($intern['total_score'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><?= $intern['total_score']; ?>/100</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Ungraded</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="appraisal.php?intern_id=<?= $intern['id']; ?>" class="btn btn-sm btn-primary" title="Evaluate Intern">
                                    <i class="fas fa-star-half-alt me-1"></i> Evaluate
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
