<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Applications Workflow Management
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Internship Applications';
$pageSubtitle = 'Review, confirm, or place candidate applications on waiting list';

// Handle Action Updates (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $appId = (int)($_POST['app_id'] ?? 0);
        $action = sanitize($_POST['action'] ?? '');

        if ($appId > 0) {
            if ($action === 'confirm') {
                $dateFrom = !empty($_POST['dateassignfrom']) ? $_POST['dateassignfrom'] : date('Y-m-d');
                $dateTo = !empty($_POST['dateassignto']) ? $_POST['dateassignto'] : date('Y-m-d', strtotime('+6 weeks'));
                db_query("
                    UPDATE interns 
                    SET confirmed = 1, Waiting = 0, status = 'confirmed', dateassignfrom = ?, dateassignto = ?
                    WHERE id = ?
                ", [$dateFrom, $dateTo, $appId]);
                log_activity(current_user()['id'], 'Confirmed Application', "Confirmed intern #{$appId}");
                set_flash('success', "Application #{$appId} confirmed successfully. Tenure dates assigned.");
            } elseif ($action === 'waiting') {
                db_query("UPDATE interns SET confirmed = 0, Waiting = 1, status = 'waiting' WHERE id = ?", [$appId]);
                log_activity(current_user()['id'], 'Placed on Waiting List', "Placed intern #{$appId} on waiting list");
                set_flash('warning', "Application #{$appId} placed on waiting list.");
            } elseif ($action === 'reject') {
                db_query("UPDATE interns SET confirmed = 0, Waiting = 0, status = 'terminated' WHERE id = ?", [$appId]);
                log_activity(current_user()['id'], 'Rejected Application', "Rejected intern #{$appId}");
                set_flash('info', "Application #{$appId} marked as rejected / terminated.");
            }
        }
    }
    header("Location: applications.php");
    exit;
}

// Filter Tab (pending / confirmed / waiting)
$tab = sanitize($_GET['tab'] ?? 'pending');
$whereSql = match($tab) {
    'confirmed' => "(confirmed = 1 OR status = 'confirmed')",
    'waiting'   => "(Waiting = 1 OR status = 'waiting')",
    'all'       => "1=1",
    default     => "(status = 'pending' OR (confirmed = 0 AND Waiting = 0))"
};

$applications = db_fetch_all("
    SELECT id, sname, Father_name, Email, Cellnumber, sInstitute, Degree, sterm,
           dateprefferedfrom, dateprefferedto, Date_of_Submission, status, confirmed, Waiting, CV
    FROM interns 
    WHERE {$whereSql}
    ORDER BY id DESC 
    LIMIT 50
");

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Tab Filters -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'pending' ? 'active' : ''; ?>" href="applications.php?tab=pending">
                <i class="fas fa-hourglass-start me-1"></i> Pending Applications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'confirmed' ? 'active' : ''; ?>" href="applications.php?tab=confirmed">
                <i class="fas fa-check-circle me-1"></i> Confirmed
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'waiting' ? 'active' : ''; ?>" href="applications.php?tab=waiting">
                <i class="fas fa-clock me-1"></i> Waiting List
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'all' ? 'active' : ''; ?>" href="applications.php?tab=all">
                <i class="fas fa-list me-1"></i> All Applications
            </a>
        </li>
    </ul>

    <a href="../apply.php" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
        <i class="fas fa-external-link-alt me-1"></i> Open Public Form
    </a>
</div>

<!-- Applications Table Card -->
<div class="awt-table-container">
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th>App ID</th>
                    <th>Candidate Name</th>
                    <th>University & Degree</th>
                    <th>Contact Info</th>
                    <th>Preferred Dates</th>
                    <th>Submitted On</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox display-6 text-secondary mb-3 d-block"></i>
                            No applications found in this view.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $app['id']; ?></td>
                            <td>
                                <a href="intern_view.php?id=<?= $app['id']; ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($app['sname']); ?>
                                </a>
                                <?php if (!empty($app['Father_name'])): ?>
                                    <small class="text-muted d-block font-normal">S/O <?= e($app['Father_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($app['sInstitute'] ?: '—'); ?></div>
                                <small class="text-muted"><?= e($app['Degree'] ?: '—'); ?> <?= !empty($app['sterm']) ? '(' . e($app['sterm']) . ')' : ''; ?></small>
                            </td>
                            <td class="small">
                                <div><i class="fas fa-envelope text-muted me-1"></i><?= e($app['Email'] ?: '—'); ?></div>
                                <div><i class="fas fa-phone-alt text-muted me-1"></i><?= e($app['Cellnumber'] ?: '—'); ?></div>
                            </td>
                            <td class="small text-muted">
                                <?= format_date($app['dateprefferedfrom']); ?> &rarr; <?= format_date($app['dateprefferedto']); ?>
                            </td>
                            <td class="small text-muted">
                                <?= format_datetime($app['Date_of_Submission'] ?: null); ?>
                            </td>
                            <td><?= status_badge($app['status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="intern_view.php?id=<?= $app['id']; ?>" class="btn btn-light border" title="Review Profile"><i class="fas fa-eye text-primary"></i></a>
                                    <a href="offer_letter.php?id=<?= $app['id']; ?>" class="btn btn-light border" title="Generate Offer Letter"><i class="fas fa-file-contract text-teal"></i></a>
                                    <!-- Confirm Trigger Modal -->
                                    <button type="button" class="btn btn-light border text-success" title="Confirm" data-bs-toggle="modal" data-bs-target="#confirmModal<?= $app['id']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>

                                <!-- Confirm Modal -->
                                <div class="modal fade text-start" id="confirmModal<?= $app['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="applications.php" method="POST" class="modal-content">
                                            <?= csrf_input(); ?>
                                            <input type="hidden" name="app_id" value="<?= $app['id']; ?>">
                                            <input type="hidden" name="action" value="confirm">

                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Confirm Application: <?= e($app['sname']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="small text-muted">Confirming this student marks their status as official intern and assigns internship dates.</p>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tenure Start Date</label>
                                                    <input type="date" name="dateassignfrom" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Tenure End Date</label>
                                                    <input type="date" name="dateassignto" class="form-control" value="<?= date('Y-m-d', strtotime('+6 weeks')); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-check me-1"></i> Confirm Acceptance</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
