<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Daily Attendance Management System
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Daily Attendance';
$pageSubtitle = 'Record and monitor daily intern presence, leaves, and absences';

$selectedDate = sanitize($_GET['date'] ?? date('Y-m-d'));

// Handle Attendance Save (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $postDate = sanitize($_POST['attendance_date'] ?? date('Y-m-d'));
        $statuses = $_POST['attendance_status'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $adminId = current_user()['id'];

        $countSaved = 0;
        foreach ($statuses as $internId => $status) {
            $internId = (int)$internId;
            $cleanStatus = in_array($status, ['Present', 'Absent', 'Leave', 'Late']) ? $status : 'Present';
            $rem = sanitize($remarks[$internId] ?? '');

            db_query("
                INSERT INTO attendance (intern_id, attendance_date, status, remarks, marked_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)
            ", [$internId, $postDate, $cleanStatus, $rem, $adminId]);
            $countSaved++;
        }

        log_activity($adminId, 'Marked Attendance', "Saved attendance for {$countSaved} interns on {$postDate}");
        set_flash('success', "Attendance records for {$postDate} saved successfully ({$countSaved} updated).");
        header("Location: attendance.php?date=" . $postDate);
        exit;
    }
}

// Fetch active or confirmed interns (or recent 50)
$interns = db_fetch_all("
    SELECT id, sname, sInstitute, Degree, Department, status
    FROM interns 
    WHERE (confirmed = 1 OR status = 'confirmed' OR status = 'active')
    ORDER BY sname ASC
    LIMIT 100
");

// Fetch existing attendance records for the selected date
$existingAttendance = [];
$attRows = db_fetch_all("SELECT intern_id, status, remarks, time_in, time_out FROM attendance WHERE attendance_date = ?", [$selectedDate]);
foreach ($attRows as $row) {
    $existingAttendance[$row['intern_id']] = $row;
}

// Summary for this day
$dayPresent = 0;
$dayAbsent = 0;
$dayLeave = 0;
foreach ($existingAttendance as $att) {
    if ($att['status'] === 'Present') $dayPresent++;
    elseif ($att['status'] === 'Absent') $dayAbsent++;
    elseif ($att['status'] === 'Leave') $dayLeave++;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Date Selector & Summary Header -->
<div class="row g-3 align-items-center mb-4">
    <div class="col-md-5">
        <form method="GET" action="attendance.php" class="d-flex align-items-center gap-2">
            <label class="form-label fw-bold text-nowrap mb-0"><i class="fas fa-calendar-alt text-primary me-1"></i> Date:</label>
            <input type="date" name="date" class="form-control" value="<?= e($selectedDate); ?>" onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary btn-sm px-3">Go</button>
        </form>
    </div>
    <div class="col-md-7 d-flex justify-content-md-end gap-2 flex-wrap">
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
            <i class="fas fa-check-circle me-1"></i> Present: <?= $dayPresent; ?>
        </span>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
            <i class="fas fa-times-circle me-1"></i> Absent: <?= $dayAbsent; ?>
        </span>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 fs-6">
            <i class="fas fa-umbrella-beach me-1"></i> Leave: <?= $dayLeave; ?>
        </span>
    </div>
</div>

<!-- Attendance Form Sheet -->
<form action="attendance.php" method="POST" class="awt-card p-0 overflow-hidden">
    <?= csrf_input(); ?>
    <input type="hidden" name="attendance_date" value="<?= e($selectedDate); ?>">

    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-clipboard-check text-primary me-2"></i>Attendance Sheet for <?= format_date($selectedDate); ?></h5>
            <small class="text-muted"><?= count($interns); ?> confirmed interns eligible</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success batch-attendance-btn" data-status="Present">
                <i class="fas fa-check-double me-1"></i> Mark All Present
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger batch-attendance-btn" data-status="Absent">
                <i class="fas fa-times me-1"></i> Mark All Absent
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table awt-table mb-0">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Intern Name</th>
                    <th>Institution & Dept</th>
                    <th style="width: 280px;">Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($interns)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No confirmed interns found. Enroll or confirm interns first.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($interns as $intern): 
                        $curStatus = $existingAttendance[$intern['id']]['status'] ?? 'Present';
                        $curRemarks = $existingAttendance[$intern['id']]['remarks'] ?? '';
                    ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                            <td>
                                <a href="intern_view.php?id=<?= $intern['id']; ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($intern['sname']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= e($intern['sInstitute'] ?: '—'); ?></div>
                                <small class="text-muted"><?= e($intern['Department'] ?: 'General'); ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="pres_<?= $intern['id']; ?>" value="Present" <?= $curStatus === 'Present' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="pres_<?= $intern['id']; ?>"><i class="fas fa-check me-1"></i>Present</label>

                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="abs_<?= $intern['id']; ?>" value="Absent" <?= $curStatus === 'Absent' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger" for="abs_<?= $intern['id']; ?>"><i class="fas fa-times me-1"></i>Absent</label>

                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="leave_<?= $intern['id']; ?>" value="Leave" <?= $curStatus === 'Leave' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-warning" for="leave_<?= $intern['id']; ?>"><i class="fas fa-bed me-1"></i>Leave</label>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="remarks[<?= $intern['id']; ?>]" class="form-control form-control-sm" placeholder="Optional notes (e.g. Field visit)" value="<?= e($curRemarks); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-3 bg-light border-top text-end">
        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
            <i class="fas fa-save me-1"></i> Save Attendance Sheet
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
