<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Reports & Data Export Module
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Reports & Analytics';
$pageSubtitle = 'Export statistical reports, rosters, and completion lists';

// Check if CSV export was requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $reportType = sanitize($_GET['type'] ?? 'all');
    $year = sanitize($_GET['year'] ?? '');
    $uni = sanitize($_GET['institute'] ?? '');

    $where = ["1=1"];
    $params = [];
    if (!empty($year)) {
        $where[] = "iyear = ?";
        $params[] = $year;
    }
    if (!empty($uni)) {
        $where[] = "sInstitute = ?";
        $params[] = $uni;
    }

    if ($reportType === 'confirmed') {
        $where[] = "(confirmed = 1 OR status = 'confirmed')";
    } elseif ($reportType === 'completed') {
        $where[] = "(status = 'completed' OR punctuality > 0)";
    } elseif ($reportType === 'waiting') {
        $where[] = "(Waiting = 1 OR status = 'waiting')";
    }

    $whereSql = implode(' AND ', $where);
    $rows = db_fetch_all("
        SELECT id, sname, Father_name, Cellnumber, Email, sInstitute, Degree, sterm,
               Hours, iyear, dateassignfrom, dateassignto, Mentor, Department, status,
               punctuality, regularity, productivity, relationship_with_others, Initiative,
               Maturity, Confidence, Analytical_ability, abilityhardword, knowledge,
               (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score,
               comments
        FROM interns
        WHERE {$whereSql}
        ORDER BY id DESC
    ", $params);

    $headers = [
        'ID', 'Student Name', 'Father Name', 'Cell Number', 'Email', 'University/Institute',
        'Degree', 'Semester', 'Duration', 'Year', 'Start Date', 'End Date', 'Mentor',
        'Department', 'Status', 'Punctuality', 'Regularity', 'Productivity', 'Teamwork',
        'Initiative', 'Maturity', 'Confidence', 'Analytical', 'Hard Work', 'Knowledge',
        'Total Score', 'Comments'
    ];

    $filename = "awt_interns_report_" . date('Y-m-d') . ".csv";
    export_csv($headers, $rows, $filename);
    exit;
}

// 1. University Breakdown
$uniStats = db_fetch_all("
    SELECT sInstitute, COUNT(*) AS total,
           SUM(CASE WHEN confirmed = 1 OR status = 'confirmed' OR status = 'completed' THEN 1 ELSE 0 END) AS confirmed_count,
           ROUND(AVG(NULLIF(punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge, 0)), 1) AS avg_score
    FROM interns
    WHERE sInstitute IS NOT NULL AND sInstitute <> ''
    GROUP BY sInstitute
    ORDER BY total DESC
    LIMIT 15
");

// 2. Year Breakdown
$yearStats = db_fetch_all("
    SELECT iyear, COUNT(*) AS total,
           SUM(CASE WHEN confirmed = 1 OR status = 'confirmed' OR status = 'completed' THEN 1 ELSE 0 END) AS confirmed_count
    FROM interns
    WHERE iyear IS NOT NULL AND iyear <> ''
    GROUP BY iyear
    ORDER BY iyear DESC
");

// 3. Top Performing Interns
$topPerformers = db_fetch_all("
    SELECT id, sname, sInstitute, Degree, iyear, Mentor,
           (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
    FROM interns
    WHERE punctuality > 0
    ORDER BY total_score DESC, id DESC
    LIMIT 10
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
    <!-- CSV Export Card -->
    <div class="col-12">
        <div class="awt-card bg-primary text-white p-4" style="background:linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%) !important;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="fw-bold mb-1"><i class="fas fa-file-export me-2"></i>Export Official Internship Records</h4>
                    <p class="mb-0 text-white-50">Generate Excel/CSV reports with full academic profiles, tenure dates, and performance appraisal ratings.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="reports.php?export=csv&type=all" class="btn btn-warning fw-bold px-4 shadow-sm">
                        <i class="fas fa-download me-2"></i> Export All 700+ Records
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Table 1: University Breakdown -->
    <div class="col-lg-7">
        <div class="awt-card">
            <div class="card-header-clean">
                <h5><i class="fas fa-university text-primary"></i> Partner Universities Breakdown</h5>
                <span class="badge bg-light text-muted border">Top 15 Institutes</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm awt-table">
                    <thead>
                        <tr>
                            <th>Institute</th>
                            <th class="text-center">Total Interns</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uniStats as $u): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= e($u['sInstitute']); ?></td>
                                <td class="text-center"><?= number_format($u['total']); ?></td>
                                <td class="text-center text-success fw-semibold"><?= number_format($u['confirmed_count']); ?></td>
                                <td class="text-center fw-bold text-primary"><?= $u['avg_score'] ? $u['avg_score'] . '/100' : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Table 2: Year Breakdown & Export Filters -->
    <div class="col-lg-5">
        <div class="awt-card mb-4">
            <div class="card-header-clean">
                <h5><i class="fas fa-calendar-alt text-teal"></i> Annual Batches Summary</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm awt-table">
                    <thead>
                        <tr>
                            <th>Batch Year</th>
                            <th class="text-center">Total Interns</th>
                            <th class="text-end">Export CSV</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yearStats as $y): ?>
                            <tr>
                                <td class="fw-bold text-dark">Session <?= e($y['iyear']); ?></td>
                                <td class="text-center fw-semibold"><?= number_format($y['total']); ?></td>
                                <td class="text-end">
                                    <a href="reports.php?export=csv&year=<?= e($y['iyear']); ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performers Card -->
        <div class="awt-card">
            <div class="card-header-clean">
                <h5><i class="fas fa-medal text-warning"></i> High Achievers Honor Roll</h5>
                <span class="badge bg-warning text-dark">Top Rated</span>
            </div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($topPerformers as $performer): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <a href="intern_view.php?id=<?= $performer['id']; ?>" class="fw-bold text-primary text-decoration-none">
                                <?= e($performer['sname']); ?>
                            </a>
                            <small class="text-muted d-block"><?= e($performer['sInstitute']); ?></small>
                        </div>
                        <span class="badge bg-success fs-6 fw-bold"><?= $performer['total_score']; ?>/100</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
