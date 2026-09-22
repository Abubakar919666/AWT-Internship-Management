<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Admin Dashboard
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Dashboard Overview';
$pageSubtitle = "Alamgir Welfare Trust Int'l — Internship Program Statistics";

// 1. Fetch KPI Counts
$totalInterns = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns")['c'] ?? 0);
$confirmedInterns = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns WHERE confirmed = 1 OR status = 'confirmed' OR status = 'completed'")['c'] ?? 0);
$pendingApps = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns WHERE status = 'pending' OR (confirmed = 0 AND Waiting = 0)")['c'] ?? 0);
$waitingList = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns WHERE Waiting = 1 OR status = 'waiting'")['c'] ?? 0);
$totalSupervisors = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM supervisors WHERE is_active = 1")['c'] ?? 0);
$totalTasks = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM tasks")['c'] ?? 0);

// 2. Fetch Top Universities for Chart
$uniData = db_fetch_all("
    SELECT sInstitute, COUNT(*) AS count 
    FROM interns 
    WHERE sInstitute IS NOT NULL AND sInstitute <> '' 
    GROUP BY sInstitute 
    ORDER BY count DESC 
    LIMIT 6
");

$uniLabels = [];
$uniCounts = [];
foreach ($uniData as $u) {
    $uniLabels[] = $u['sInstitute'];
    $uniCounts[] = (int)$u['count'];
}

// 3. Fetch Yearly Intake Trend
$yearData = db_fetch_all("
    SELECT iyear, COUNT(*) AS count 
    FROM interns 
    WHERE iyear IS NOT NULL AND iyear <> '' 
    GROUP BY iyear 
    ORDER BY iyear ASC 
    LIMIT 10
");

$yearLabels = [];
$yearCounts = [];
foreach ($yearData as $y) {
    $yearLabels[] = $y['iyear'];
    $yearCounts[] = (int)$y['count'];
}

// 4. Fetch Recent 6 Intern Records
$recentInterns = db_fetch_all("
    SELECT id, sname, sInstitute, Degree, dateassignfrom, dateassignto, punctuality, confirmed, Waiting, status
    FROM interns 
    ORDER BY id DESC 
    LIMIT 6
");

require_once __DIR__ . '/../includes/header.php';
?>

<!-- KPI Metric Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon primary">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="kpi-info">
                <h3><?= number_format($totalInterns); ?></h3>
                <p>Total Registered Interns</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card success">
            <div class="kpi-icon success">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="kpi-info">
                <h3><?= number_format($confirmedInterns); ?></h3>
                <p>Confirmed / Completed</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card warning">
            <div class="kpi-icon warning">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="kpi-info">
                <h3><?= number_format($pendingApps); ?></h3>
                <p>Pending Applications</p>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card info">
            <div class="kpi-icon info">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="kpi-info">
                <h3><?= number_format($totalSupervisors); ?></h3>
                <p>Active Mentors / Supervisors</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Yearly Intake -->
    <div class="col-lg-7">
        <div class="awt-card h-100">
            <div class="card-header-clean">
                <h5><i class="fas fa-chart-bar text-primary"></i> Yearly Internship Intake Trend</h5>
                <span class="badge bg-light text-muted border">Annual Records</span>
            </div>
            <div style="position: relative; height: 300px;">
                <canvas id="yearlyIntakeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Top Partner Universities -->
    <div class="col-lg-5">
        <div class="awt-card h-100">
            <div class="card-header-clean">
                <h5><i class="fas fa-university text-teal"></i> Top Universities & Institutes</h5>
                <span class="badge bg-light text-muted border">By Placement</span>
            </div>
            <div style="position: relative; height: 300px;">
                <canvas id="universitiesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Interns Table & Quick Actions -->
<div class="row g-4">
    <div class="col-lg-9">
        <div class="awt-table-container">
            <div class="p-3 d-flex align-items-center justify-content-between border-bottom bg-white">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-clock text-primary me-2"></i>Recent Intern Placements</h5>
                <a href="interns.php" class="btn btn-sm btn-outline-primary">View All Interns <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table awt-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>University / Institute</th>
                            <th>Degree</th>
                            <th>Tenure Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentInterns)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No intern records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentInterns as $intern): ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                                    <td class="fw-bold text-primary">
                                        <a href="intern_view.php?id=<?= $intern['id']; ?>" class="text-decoration-none">
                                            <?= e($intern['sname']); ?>
                                        </a>
                                    </td>
                                    <td><?= e($intern['sInstitute'] ?: '—'); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($intern['Degree'] ?: 'General'); ?></span></td>
                                    <td class="small text-muted">
                                        <?= format_date($intern['dateassignfrom']); ?> &rarr; <?= format_date($intern['dateassignto']); ?>
                                    </td>
                                    <td><?= status_badge($intern['status']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="View 360 Profile"><i class="fas fa-eye text-primary"></i></a>
                                            <a href="intern_edit.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="Edit Record"><i class="fas fa-edit text-dark"></i></a>
                                            <a href="certificate.php?id=<?= $intern['id']; ?>" class="btn btn-light border" title="Certificate"><i class="fas fa-certificate text-warning"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Operations Sidebar Card -->
    <div class="col-lg-3">
        <div class="awt-card">
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="intern_add.php" class="btn btn-awt-primary text-start">
                    <i class="fas fa-user-plus me-2"></i> Enroll New Intern
                </a>
                <a href="attendance.php" class="btn btn-awt-success text-start">
                    <i class="fas fa-calendar-check me-2"></i> Mark Daily Attendance
                </a>
                <a href="tasks.php" class="btn btn-outline-primary text-start">
                    <i class="fas fa-tasks me-2"></i> Assign Work Tasks
                </a>
                <a href="appraisal.php" class="btn btn-outline-dark text-start">
                    <i class="fas fa-star-half-alt me-2"></i> Performance Appraisal
                </a>
                <a href="applications.php" class="btn btn-outline-secondary text-start position-relative">
                    <i class="fas fa-file-signature me-2"></i> Review Applications
                    <?php if ($pendingApps > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end"><?= $pendingApps; ?></span>
                    <?php endif; ?>
                </a>
                <a href="reports.php?export=csv" class="btn btn-outline-success text-start">
                    <i class="fas fa-file-csv me-2"></i> Export All to CSV
                </a>
            </div>
        </div>

        <div class="awt-card bg-light border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-white rounded-circle shadow-sm text-primary fs-4">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">AWT Security Status</h6>
                    <span class="badge bg-success"><i class="fas fa-lock me-1"></i> RBAC Active</span>
                    <small class="d-block text-muted mt-1">PHP 8+ & PDO Prepared</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Yearly Intake Bar Chart
    const ctxYear = document.getElementById('yearlyIntakeChart').getContext('2d');
    new Chart(ctxYear, {
        type: 'bar',
        data: {
            labels: <?= json_encode($yearLabels); ?>,
            datasets: [{
                label: 'Interns Registered',
                data: <?= json_encode($yearCounts); ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                borderColor: '#1d4ed8',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Top Universities Doughnut Chart
    const ctxUni = document.getElementById('universitiesChart').getContext('2d');
    new Chart(ctxUni, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($uniLabels); ?>,
            datasets: [{
                data: <?= json_encode($uniCounts); ?>,
                backgroundColor: [
                    '#1e3a8a',
                    '#2563eb',
                    '#0d9488',
                    '#d97706',
                    '#ec4899',
                    '#8b5cf6'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
