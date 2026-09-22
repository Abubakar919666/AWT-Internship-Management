<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Intern Task Center & Work Submissions
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

$pageTitle = 'My Assigned Tasks';
$pageSubtitle = 'View tasks, submit deliverables, and check supervisor reviews';

// Handle Work Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $submissionText = sanitize($_POST['submission_text'] ?? '');
        $filePath = null;
        $fileName = null;

        // Check file attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['attachment'], 'uploads/tasks');
            if ($upload['success']) {
                $filePath = $upload['relative_path'];
                $fileName = $upload['original_name'];
            }
        }

        if ($taskId > 0 && !empty($submissionText)) {
            db_query("
                INSERT INTO task_submissions (task_id, intern_id, submission_text, file_path, file_name, status)
                VALUES (?, ?, ?, ?, ?, 'Submitted')
            ", [$taskId, $internId, $submissionText, $filePath, $fileName]);

            // Mark task as Under Review
            db_query("UPDATE tasks SET status = 'Under Review' WHERE id = ? AND intern_id = ?", [$taskId, $internId]);

            log_activity($user['id'], 'Task Submitted', "Intern submitted work on task #{$taskId}");
            set_flash('success', "Work deliverable submitted successfully! Your supervisor will review it.");
            header("Location: tasks.php");
            exit;
        }
    }
}

// Fetch all tasks for this intern
$tasks = db_fetch_all("
    SELECT t.*, s.name AS supervisor_name
    FROM tasks t
    LEFT JOIN supervisors s ON t.supervisor_id = s.id
    WHERE t.intern_id = ?
    ORDER BY t.id DESC
", [$internId]);

// Fetch all submissions with supervisor feedback
$submissions = db_fetch_all("
    SELECT ts.*, t.title AS task_title
    FROM task_submissions ts
    JOIN tasks t ON ts.task_id = t.id
    WHERE ts.intern_id = ?
    ORDER BY ts.id DESC
", [$internId]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Tasks List -->
    <div class="col-lg-8">
        <div class="awt-card">
            <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-tasks text-primary me-2"></i>Assigned Work Tasks</h5>

            <?php if (empty($tasks)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-check display-5 text-secondary mb-3 d-block"></i>
                    No tasks currently assigned to you.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($tasks as $task): ?>
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1"><?= e($task['title']); ?></h5>
                                    <div class="small text-muted">
                                        Assigned: <?= format_date($task['assigned_date']); ?> &bull;
                                        Deadline: <strong class="<?= strtotime($task['due_date']) < time() && $task['status'] !== 'Completed' ? 'text-danger' : ''; ?>"><?= format_date($task['due_date']); ?></strong>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <?= priority_badge($task['priority']); ?>
                                    <?= task_status_badge($task['status']); ?>
                                </div>
                            </div>

                            <?php if (!empty($task['description'])): ?>
                                <p class="small text-muted mb-3"><?= e($task['description']); ?></p>
                            <?php endif; ?>

                            <?php if ($task['status'] !== 'Completed'): ?>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#submitWorkModal<?= $task['id']; ?>">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Work Deliverable
                                </button>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Completed</span>
                            <?php endif; ?>

                            <!-- Submit Work Modal -->
                            <div class="modal fade text-start" id="submitWorkModal<?= $task['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="tasks.php" method="POST" enctype="multipart/form-data" class="modal-content">
                                        <?= csrf_input(); ?>
                                        <input type="hidden" name="task_id" value="<?= $task['id']; ?>">

                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Submit Work: <?= e($task['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Work Summary / Observations <span class="text-danger">*</span></label>
                                                <textarea name="submission_text" class="form-control" rows="4" placeholder="Detail the work performed, findings, or results..." required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Attach Document / Report (Optional)</label>
                                                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.zip">
                                                <div class="form-text">PDF, Word, or Image file (Max 5MB).</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary fw-bold">Submit to Mentor</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submissions & Feedback Sidebar -->
    <div class="col-lg-4">
        <div class="awt-card">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="fas fa-comment-dots text-primary me-2"></i>Supervisor Feedback</h5>
            <?php if (empty($submissions)): ?>
                <p class="small text-muted mb-0">No submissions uploaded yet.</p>
            <?php else: ?>
                <div class="list-group list-group-flush small">
                    <?php foreach ($submissions as $sub): ?>
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="fw-bold text-dark"><?= e($sub['task_title']); ?></div>
                            <small class="text-muted d-block mb-1">Submitted on <?= format_date($sub['submitted_at']); ?></small>
                            <span class="badge bg-<?= $sub['status'] === 'Approved' ? 'success' : 'warning text-dark'; ?> mb-2">
                                <?= e($sub['status']); ?>
                            </span>
                            <?php if (!empty($sub['feedback'])): ?>
                                <div class="p-2 bg-light rounded border text-dark">
                                    <i class="fas fa-quote-left text-secondary me-1"></i><?= e($sub['feedback']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
