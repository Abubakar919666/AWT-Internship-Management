<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisor - Attendance Marking
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_supervisor();

$pageTitle = 'Intern Attendance Sheet';
$pageSubtitle = 'Record daily attendance for interns assigned to your department';

$user = current_user();
$supervisorId = $user['supervisor_id'];
if (!$supervisorId) {
    $supRow = db_fetch_one("SELECT id FROM supervisors WHERE email = ? OR user_id = ? LIMIT 1", [$user['email'], $user['id']]);
    if ($supRow) $supervisorId = (int)$supRow['id'];
}

$selectedDate = sanitize($_GET['date'] ?? date('Y-m-d'));

// Handle Save Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $postDate = sanitize($_POST['attendance_date'] ?? date('Y-m-d'));
        $statuses = $_POST['attendance_status'] ?? [];
        $remarks = $_POST['remarks'] ?? [];

        $countSaved = 0;
        foreach ($statuses as $internId => $status) {
            $internId = (int)$internId;
            $cleanStatus = in_array($status, ['Present', 'Absent', 'Leave', 'Late']) ? $status : 'Present';
            $rem = sanitize($remarks[$internId] ?? '');

            db_query("
                INSERT INTO attendance (intern_id, attendance_date, status, remarks, marked_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)
            ", [$internId, $postDate, $cleanStatus, $rem, $user['id']]);
            $countSaved++;
        }

        set_flash('success', "Attendance saved for {$countSaved} interns on {$postDate}.");
        header("Location: attendance.php?date=" . $postDate);
        exit;
    }
}

// Fetch assigned interns
$interns = db_fetch_all("
    SELECT id, sname, sInstitute, Degree, Department
    FROM interns
    WHERE supervisor_id = ? OR Mentor LIKE ?
    ORDER BY sname ASC
", [$supervisorId, "%{$user['name']}%"]);

// Fetch existing attendance for this date
$existingAttendance = [];
if (!empty($interns)) {
    $internIds = array_column($interns, 'id');
    $inClause = implode(',', $internIds);
    $attRows = db_fetch_all("SELECT intern_id, status, remarks FROM attendance WHERE attendance_date = ? AND intern_id IN ($inClause)", [$selectedDate]);
    foreach ($attRows as $row) {
        $existingAttendance[$row['intern_id']] = $row;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 align-items-center mb-4">
    <div class="col-md-5">
        <form method="GET" action="attendance.php" class="d-flex align-items-center gap-2">
            <label class="form-label fw-bold text-nowrap mb-0"><i class="fas fa-calendar-alt text-primary me-1"></i> Date:</label>
            <input type="date" name="date" class="form-control" value="<?= e($selectedDate); ?>" onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary btn-sm px-3">Go</button>
        </form>
    </div>
</div>

<form action="attendance.php" method="POST" class="awt-card p-0 overflow-hidden">
    <?= csrf_input(); ?>
    <input type="hidden" name="attendance_date" value="<?= e($selectedDate); ?>">

    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-clipboard-check text-primary me-2"></i>Daily Attendance: <?= format_date($selectedDate); ?></h5>
            <small class="text-muted"><?= count($interns); ?> assigned interns</small>
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
                    <th>Institution</th>
                    <th style="width: 280px;">Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($interns)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No interns assigned to your supervisor account.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($interns as $intern): 
                        $curStatus = $existingAttendance[$intern['id']]['status'] ?? 'Present';
                        $curRemarks = $existingAttendance[$intern['id']]['remarks'] ?? '';
                    ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $intern['id']; ?></td>
                            <td class="fw-bold text-primary"><?= e($intern['sname']); ?></td>
                            <td><?= e($intern['sInstitute'] ?: '—'); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="pres_<?= $intern['id']; ?>" value="Present" <?= $curStatus === 'Present' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="pres_<?= $intern['id']; ?>">Present</label>

                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="abs_<?= $intern['id']; ?>" value="Absent" <?= $curStatus === 'Absent' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-danger" for="abs_<?= $intern['id']; ?>">Absent</label>

                                    <input type="radio" class="btn-check" name="attendance_status[<?= $intern['id']; ?>]" id="leave_<?= $intern['id']; ?>" value="Leave" <?= $curStatus === 'Leave' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-warning" for="leave_<?= $intern['id']; ?>">Leave</label>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="remarks[<?= $intern['id']; ?>]" class="form-control form-control-sm" placeholder="Optional notes" value="<?= e($curRemarks); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-3 bg-light border-top text-end">
        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
            <i class="fas fa-save me-1"></i> Save Attendance
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
