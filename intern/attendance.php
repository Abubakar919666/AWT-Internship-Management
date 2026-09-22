<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Intern Personal Attendance Log
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_intern();

$user = current_user();
$internId = $user['intern_id'];
if (!$internId) {
    $internRow = db_fetch_one("SELECT id FROM interns WHERE Email = ? OR user_id = ? LIMIT 1", [$user['email'], $user['id']]);
    if ($internRow) $internId = (int)$internRow['id'];
}

$pageTitle = 'My Attendance Log';
$pageSubtitle = 'Record of days present, leaves, and absences';

$stats = db_fetch_one("
    SELECT 
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_days,
        SUM(CASE WHEN status = 'Leave' THEN 1 ELSE 0 END) AS leave_days
    FROM attendance WHERE intern_id = ?
", [$internId]);

$totalDays = (int)($stats['total_days'] ?? 0);
$presentDays = (int)($stats['present_days'] ?? 0);
$attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 100;

$records = db_fetch_all("
    SELECT * FROM attendance 
    WHERE intern_id = ? 
    ORDER BY attendance_date DESC
", [$internId]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon primary"><i class="fas fa-calendar-alt"></i></div>
            <div class="kpi-info">
                <h3><?= $totalDays; ?></h3>
                <p>Total Days Recorded</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card success">
            <div class="kpi-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-info">
                <h3><?= $presentDays; ?></h3>
                <p>Days Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card danger">
            <div class="kpi-icon danger"><i class="fas fa-times-circle"></i></div>
            <div class="kpi-info">
                <h3><?= (int)($stats['absent_days'] ?? 0); ?></h3>
                <p>Days Absent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card info">
            <div class="kpi-icon info"><i class="fas fa-percentage"></i></div>
            <div class="kpi-info">
                <h3><?= $attendanceRate; ?>%</h3>
                <p>Attendance Ratio</p>
            </div>
        </div>
    </div>
</div>

<div class="awt-table-container">
    <div class="p-3 bg-white border-bottom">
        <h5 class="fw-bold mb-0 text-dark">Historical Attendance Entries</h5>
    </div>
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Attendance Status</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No attendance marked yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="fw-semibold"><?= format_date($r['attendance_date']); ?></td>
                            <td><?= status_badge($r['status']); ?></td>
                            <td class="small"><?= $r['time_in'] ? date('h:i A', strtotime($r['time_in'])) : '—'; ?></td>
                            <td class="small"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—'; ?></td>
                            <td class="small text-muted"><?= e($r['remarks'] ?: '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
