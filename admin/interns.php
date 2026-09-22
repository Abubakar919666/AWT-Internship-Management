<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Interns Directory (Search, Filter, Paginate, Actions)
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Interns Directory';
$pageSubtitle = 'Manage and filter all registered intern records';

// Filters & Search
$search = sanitize($_GET['search'] ?? '');
$instituteFilter = sanitize($_GET['institute'] ?? '');
$yearFilter = sanitize($_GET['year'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(sname LIKE ? OR Email LIKE ? OR Cellnumber LIKE ? OR sInstitute LIKE ? OR ERPNO LIKE ? OR id = ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, is_numeric($search) ? (int)$search : 0]);
}

if (!empty($instituteFilter)) {
    $where[] = "sInstitute = ?";
    $params[] = $instituteFilter;
}

if (!empty($yearFilter)) {
    $where[] = "iyear = ?";
    $params[] = $yearFilter;
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'confirmed') {
        $where[] = "(confirmed = 1 OR status = 'confirmed')";
    } elseif ($statusFilter === 'waiting') {
        $where[] = "(Waiting = 1 OR status = 'waiting')";
    } elseif ($statusFilter === 'completed') {
        $where[] = "status = 'completed'";
    } elseif ($statusFilter === 'pending') {
        $where[] = "(status = 'pending' AND confirmed = 0 AND Waiting = 0)";
    } else {
        $where[] = "status = ?";
        $params[] = $statusFilter;
    }
}

$whereSql = implode(' AND ', $where);

// Total Count
$countRow = db_fetch_one("SELECT COUNT(*) AS total FROM interns WHERE {$whereSql}", $params);
$totalRecords = (int)($countRow['total'] ?? 0);
$totalPages = ceil($totalRecords / $perPage);

// Fetch paginated records
$sql = "
    SELECT id, sname, Father_name, sInstitute, Degree, sterm, Hours,
           dateassignfrom, dateassignto, Mentor, punctuality, confirmed, Waiting, status,
           Request_Form, Photograph, CV, Recommendation_Letter, CNIC_copy, Student_ID, Email, Cellnumber
    FROM interns 
    WHERE {$whereSql}
    ORDER BY id DESC 
    LIMIT {$perPage} OFFSET {$offset}
";
$interns = db_fetch_all($sql, $params);

// Fetch distinct universities and years for dropdown filters
$institutes = db_fetch_all("SELECT DISTINCT sInstitute FROM interns WHERE sInstitute IS NOT NULL AND sInstitute <> '' ORDER BY sInstitute ASC");
$years = db_fetch_all("SELECT DISTINCT iyear FROM interns WHERE iyear IS NOT NULL AND iyear <> '' ORDER BY iyear DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Search & Filter Card -->
<div class="awt-card mb-4">
    <form method="GET" action="interns.php" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold small text-muted">Search Intern Records</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search by name, institute, email, ERP..." value="<?= e($search); ?>">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold small text-muted">University / Institute</label>
            <select name="institute" class="form-select">
                <option value="">All Institutions</option>
                <?php foreach ($institutes as $inst): ?>
                    <option value="<?= e($inst['sInstitute']); ?>" <?= $instituteFilter === $inst['sInstitute'] ? 'selected' : ''; ?>>
                        <?= e($inst['sInstitute']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-semibold small text-muted">Year</label>
            <select name="year" class="form-select">
                <option value="">All Years</option>
                <?php foreach ($years as $yr): ?>
                    <option value="<?= e($yr['iyear']); ?>" <?= $yearFilter === $yr['iyear'] ? 'selected' : ''; ?>>
                        <?= e($yr['iyear']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-semibold small text-muted">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="waiting" <?= $statusFilter === 'waiting' ? 'selected' : ''; ?>>Waiting List</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>

        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100" title="Apply Filter"><i class="fas fa-filter"></i></button>
            <a href="interns.php" class="btn btn-light border" title="Reset Filters"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<!-- Results Header & Add Button -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold text-dark mb-0">Total Matches: <span class="text-primary"><?= number_format($totalRecords); ?></span> Interns</h5>
        <small class="text-muted">Showing page <?= $page; ?> of <?= max(1, $totalPages); ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="reports.php?export=csv<?= !empty($_SERVER['QUERY_STRING']) ? '&' . e($_SERVER['QUERY_STRING']) : ''; ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-file-csv me-1"></i> Export Filtered CSV
        </a>
        <a href="intern_add.php" class="btn btn-primary btn-sm fw-bold">
            <i class="fas fa-user-plus me-1"></i> Add New Intern
        </a>
    </div>
</div>

<!-- Table Card -->
<div class="awt-table-container">
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Institution & Degree</th>
                    <th>Duration & Dates</th>
                    <th>Assigned Mentor</th>
                    <th>Docs</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($interns)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-search display-6 text-secondary mb-3 d-block"></i>
                            No intern records match your search criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($interns as $intern): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                            <td>
                                <a href="intern_view.php?id=<?= $intern['id']; ?>" class="fw-bold text-primary text-decoration-none d-block">
                                    <?= e($intern['sname']); ?>
                                </a>
                                <?php if (!empty($intern['Cellnumber'])): ?>
                                    <small class="text-muted"><i class="fas fa-phone-alt me-1 text-secondary" style="font-size:10px;"></i><?= e($intern['Cellnumber']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($intern['sInstitute'] ?: '—'); ?></div>
                                <small class="text-muted"><?= e($intern['Degree'] ?: '—'); ?> <?= !empty($intern['sterm']) ? '(' . e($intern['sterm']) . ')' : ''; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border mb-1"><?= e($intern['Hours'] ?: 'Standard'); ?></span>
                                <div class="small text-muted" style="font-size:11px;">
                                    <?= format_date($intern['dateassignfrom']); ?> &rarr; <?= format_date($intern['dateassignto']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= e($intern['Mentor'] ?: 'Unassigned'); ?></div>
                            </td>
                            <td>
                                <!-- Document Icons Tooltip -->
                                <span title="CV" class="<?= $intern['CV'] ? 'text-success' : 'text-black-50'; ?>"><i class="fas fa-file-pdf me-1"></i></span>
                                <span title="Request Form" class="<?= $intern['Request_Form'] ? 'text-success' : 'text-black-50'; ?>"><i class="fas fa-file-alt me-1"></i></span>
                                <span title="Recommendation Letter" class="<?= $intern['Recommendation_Letter'] ? 'text-success' : 'text-black-50'; ?>"><i class="fas fa-envelope-open-text me-1"></i></span>
                                <span title="CNIC Copy" class="<?= $intern['CNIC_copy'] ? 'text-success' : 'text-black-50'; ?>"><i class="fas fa-id-badge"></i></span>
                            </td>
                            <td><?= status_badge($intern['status']); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="View 360° Profile"><i class="fas fa-eye text-primary"></i></a>
                                    <a href="intern_edit.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="Edit Record"><i class="fas fa-edit text-dark"></i></a>
                                    <a href="certificate.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="Generate Certificate"><i class="fas fa-certificate text-warning"></i></a>
                                    <a href="offer_letter.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="Acceptance Letter"><i class="fas fa-envelope-open-text text-teal"></i></a>
                                    <a href="intern_delete.php?id=<?= $intern['id']; ?>&csrf_token=<?= generate_csrf_token(); ?>" 
                                       class="btn btn-light border btn-confirm-delete" 
                                       title="Delete Record"
                                       data-confirm="Are you sure you want to permanently delete intern #<?= $intern['id']; ?> (<?= e($intern['sname']); ?>)?">
                                        <i class="fas fa-trash text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white">
            <span class="small text-muted">Showing <?= $offset + 1; ?> to <?= min($offset + $perPage, $totalRecords); ?> of <?= number_format($totalRecords); ?></span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Prev</a>
                    </li>
                    <?php
                    $startP = max(1, $page - 2);
                    $endP = min($totalPages, $page + 2);
                    for ($p = $startP; $p <= $endP; $p++):
                    ?>
                        <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?= $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
