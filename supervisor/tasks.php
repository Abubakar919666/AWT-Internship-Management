<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisor - Task Management & Work Submissions
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_supervisor();

$pageTitle = 'Supervisor Task Center';
$pageSubtitle = 'Assign departmental assignments and review student work submissions';

$user = current_user();
$supContext = get_active_supervisor_context();
$activeSup = $supContext['supervisor'];
$isAll = $supContext['is_all'];
$assignSupId = $isAll ? 1 : (int)$activeSup['id'];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'create_task') {
            $internId = (int)($_POST['intern_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $desc = sanitize($_POST['description'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if ($internId > 0 && !empty($title)) {
                db_query("
                    INSERT INTO tasks (intern_id, supervisor_id, title, description, priority, status, due_date, assigned_date)
                    VALUES (?, ?, ?, ?, ?, 'Pending', ?, CURDATE())
                ", [$internId, $assignSupId, $title, $desc, $priority, $dueDate]);
                set_flash('success', "Task \"{$title}\" assigned successfully.");
            }
        } elseif ($action === 'review_submission') {
            $subId = (int)($_POST['submission_id'] ?? 0);
            $feedback = sanitize($_POST['feedback'] ?? '');
            $status = sanitize($_POST['status'] ?? 'Approved');

            db_query("
                UPDATE task_submissions 
                SET feedback = ?, status = ?, reviewed_at = NOW() 
                WHERE id = ?
            ", [$feedback, $status, $subId]);

            if ($status === 'Approved') {
                $sub = db_fetch_one("SELECT task_id FROM task_submissions WHERE id = ?", [$subId]);
                if ($sub) {
                    db_query("UPDATE tasks SET status = 'Completed', completed_at = NOW() WHERE id = ?", [$sub['task_id']]);
                }
            }

            set_flash('success', "Task review and feedback submitted.");
        }
    }
    header("Location: tasks.php");
    exit;
}

// Fetch tasks for this supervisor's interns or all
if ($isAll) {
    $tasks = db_fetch_all("
        SELECT t.*, i.sname AS intern_name, i.sInstitute
        FROM tasks t
        JOIN interns i ON t.intern_id = i.id
        ORDER BY t.id DESC
    ");
    $submissions = db_fetch_all("
        SELECT ts.*, t.title AS task_title, i.sname AS intern_name
        FROM task_submissions ts
        JOIN tasks t ON ts.task_id = t.id
        JOIN interns i ON ts.intern_id = i.id
        ORDER BY ts.id DESC
        LIMIT 20
    ");
    $interns = db_fetch_all("
        SELECT id, sname, sInstitute 
        FROM interns 
        ORDER BY sname ASC
    ");
} else {
    $tasks = db_fetch_all("
        SELECT t.*, i.sname AS intern_name, i.sInstitute
        FROM tasks t
        JOIN interns i ON t.intern_id = i.id
        WHERE i.supervisor_id = ? OR t.supervisor_id = ?
        ORDER BY t.id DESC
    ", [$activeSup['id'], $activeSup['id']]);
    $submissions = db_fetch_all("
        SELECT ts.*, t.title AS task_title, i.sname AS intern_name
        FROM task_submissions ts
        JOIN tasks t ON ts.task_id = t.id
        JOIN interns i ON ts.intern_id = i.id
        WHERE i.supervisor_id = ? OR t.supervisor_id = ?
        ORDER BY ts.id DESC
        LIMIT 20
    ", [$activeSup['id'], $activeSup['id']]);
    $interns = db_fetch_all("
        SELECT id, sname, sInstitute 
        FROM interns 
        WHERE supervisor_id = ? OR Mentor LIKE ?
        ORDER BY sname ASC
    ", [$activeSup['id'], "%{$activeSup['name']}%"]);
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Supervisor Switcher Ribbon -->
<?php render_supervisor_switcher_ribbon($supContext); ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0 text-dark">Tasks & Deliverables</h5>
    </div>
    <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createTaskModal">
        <i class="fas fa-plus me-1"></i> Assign New Task
    </button>
</div>

<!-- Submissions for Review Section -->
<?php if (!empty($submissions)): ?>
    <div class="awt-card mb-4 border-warning-subtle">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-glasses text-warning me-2"></i>Work Submissions from Interns</h5>
        <div class="table-responsive">
            <table class="table table-sm awt-table">
                <thead>
                    <tr>
                        <th>Intern</th>
                        <th>Task</th>
                        <th>Submission Details</th>
                        <th>Submitted At</th>
                        <th>Review Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= e($sub['intern_name']); ?></td>
                            <td class="fw-semibold"><?= e($sub['task_title']); ?></td>
                            <td>
                                <div class="small text-muted" style="max-width:300px;"><?= e($sub['submission_text']); ?></div>
                                <?php if (!empty($sub['file_path'])): ?>
                                    <a href="../<?= e($sub['file_path']); ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none mt-1">
                                        <i class="fas fa-paperclip me-1"></i> Attachment
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= format_datetime($sub['submitted_at']); ?></td>
                            <td>
                                <span class="badge bg-<?= $sub['status'] === 'Approved' ? 'success' : 'warning text-dark'; ?>">
                                    <?= e($sub['status']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $sub['id']; ?>">
                                    <i class="fas fa-comment-dots me-1"></i> Feedback
                                </button>
                            </td>
                        </tr>

                        <!-- Review Modal -->
                        <div class="modal fade text-start" id="reviewModal<?= $sub['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="tasks.php" method="POST" class="modal-content">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="review_submission">
                                    <input type="hidden" name="submission_id" value="<?= $sub['id']; ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Review Submission: <?= e($sub['task_title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Review Decision</label>
                                            <select name="status" class="form-select">
                                                <option value="Approved" <?= $sub['status'] === 'Approved' ? 'selected' : ''; ?>>Approved (Mark Completed)</option>
                                                <option value="Changes Requested" <?= $sub['status'] === 'Changes Requested' ? 'selected' : ''; ?>>Changes Requested</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Supervisor Feedback & Comments</label>
                                            <textarea name="feedback" class="form-control" rows="3" placeholder="Provide constructive feedback for the intern..."><?= e($sub['feedback']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary fw-bold">Save Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Tasks Roster -->
<div class="awt-table-container">
    <div class="p-3 bg-white border-bottom">
        <h5 class="fw-bold mb-0 text-dark">All Tasks Assigned</h5>
    </div>
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Task Title</th>
                    <th>Intern</th>
                    <th>Priority</th>
                    <th>Assigned Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No tasks assigned yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $task['id']; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($task['title']); ?></div>
                                <?php if (!empty($task['description'])): ?>
                                    <small class="text-muted d-block"><?= e($task['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold text-primary"><?= e($task['intern_name']); ?></td>
                            <td><?= priority_badge($task['priority']); ?></td>
                            <td class="small"><?= format_date($task['assigned_date']); ?></td>
                            <td class="small fw-semibold <?= strtotime($task['due_date']) < time() && $task['status'] !== 'Completed' ? 'text-danger' : ''; ?>">
                                <?= format_date($task['due_date']); ?>
                            </td>
                            <td><?= task_status_badge($task['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign New Task -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="tasks.php" method="POST" class="modal-content">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create_task">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Assign New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Intern <span class="text-danger">*</span></label>
                    <select name="intern_id" class="form-select" required>
                        <option value="">Select intern...</option>
                        <?php foreach ($interns as $i): ?>
                            <option value="<?= $i['id']; ?>"><?= e($i['sname']); ?> (<?= e($i['sInstitute']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Conduct Hospital Inventory Check">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Instructions</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Provide task guidance..."></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Assign Task</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
