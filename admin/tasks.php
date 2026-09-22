<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Task Assignment & Tracking Module
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Task Management';
$pageSubtitle = 'Assign work, monitor progress, and review intern submissions';

// Handle POST actions (Create Task, Update Status, Review Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'create_task') {
            $internId = (int)($_POST['intern_id'] ?? 0);
            $supervisorId = !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null;
            $title = sanitize($_POST['title'] ?? '');
            $desc = sanitize($_POST['description'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'Medium');
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if ($internId > 0 && !empty($title)) {
                db_query("
                    INSERT INTO tasks (intern_id, supervisor_id, title, description, priority, status, due_date, assigned_date)
                    VALUES (?, ?, ?, ?, ?, 'Pending', ?, CURDATE())
                ", [$internId, $supervisorId, $title, $desc, $priority, $dueDate]);

                $taskId = db_last_insert_id();
                log_activity(current_user()['id'], 'Assigned Task', "Task #{$taskId} assigned to intern #{$internId}");
                set_flash('success', "Task \"{$title}\" assigned successfully.");
            }
        } elseif ($action === 'update_task_status') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $newStatus = sanitize($_POST['status'] ?? 'Pending');
            $completedAt = ($newStatus === 'Completed') ? date('Y-m-d H:i:s') : null;

            db_query("
                UPDATE tasks 
                SET status = ?, completed_at = ?
                WHERE id = ?
            ", [$newStatus, $completedAt, $taskId]);

            set_flash('success', "Task #{$taskId} status updated to {$newStatus}.");
        } elseif ($action === 'submit_feedback') {
            $subId = (int)($_POST['submission_id'] ?? 0);
            $feedback = sanitize($_POST['feedback'] ?? '');
            $subStatus = sanitize($_POST['sub_status'] ?? 'Approved');

            db_query("
                UPDATE task_submissions 
                SET feedback = ?, status = ?, reviewed_at = NOW() 
                WHERE id = ?
            ", [$feedback, $subStatus, $subId]);

            // If approved, complete the task
            if ($subStatus === 'Approved') {
                $sub = db_fetch_one("SELECT task_id FROM task_submissions WHERE id = ?", [$subId]);
                if ($sub) {
                    db_query("UPDATE tasks SET status = 'Completed', completed_at = NOW() WHERE id = ?", [$sub['task_id']]);
                }
            }

            set_flash('success', "Feedback submitted successfully.");
        }
    }
    header("Location: tasks.php");
    exit;
}

// Fetch filter parameters
$statusFilter = sanitize($_GET['status'] ?? '');
$internFilter = (int)($_GET['intern_id'] ?? 0);

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}
if ($internFilter > 0) {
    $where[] = "t.intern_id = ?";
    $params[] = $internFilter;
}

$whereSql = implode(' AND ', $where);

// Fetch tasks
$tasks = db_fetch_all("
    SELECT t.*, i.sname AS intern_name, i.sInstitute, s.name AS supervisor_name
    FROM tasks t
    JOIN interns i ON t.intern_id = i.id
    LEFT JOIN supervisors s ON t.supervisor_id = s.id
    WHERE {$whereSql}
    ORDER BY t.id DESC
    LIMIT 100
");

// Fetch active interns and supervisors for assignment modal
$internList = db_fetch_all("SELECT id, sname, sInstitute FROM interns WHERE (confirmed = 1 OR status = 'confirmed' OR status = 'active') ORDER BY sname ASC");
$supervisorList = db_fetch_all("SELECT id, name, department FROM supervisors WHERE is_active = 1 ORDER BY name ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0 text-dark">Active Work Tasks: <span class="text-primary"><?= count($tasks); ?></span></h5>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#newTaskModal">
            <i class="fas fa-plus me-1"></i> Assign New Task
        </button>
    </div>
</div>

<!-- Filter Tabs -->
<div class="awt-card p-3 mb-4">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="small fw-bold text-muted me-2"><i class="fas fa-filter text-primary"></i> Filter Status:</span>
        <a href="tasks.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-light border'; ?>">All</a>
        <a href="tasks.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-secondary' : 'btn-light border'; ?>">Pending</a>
        <a href="tasks.php?status=In Progress" class="btn btn-sm <?= $statusFilter === 'In Progress' ? 'btn-info text-dark' : 'btn-light border'; ?>">In Progress</a>
        <a href="tasks.php?status=Under Review" class="btn btn-sm <?= $statusFilter === 'Under Review' ? 'btn-warning text-dark' : 'btn-light border'; ?>">Under Review</a>
        <a href="tasks.php?status=Completed" class="btn btn-sm <?= $statusFilter === 'Completed' ? 'btn-success' : 'btn-light border'; ?>">Completed</a>
    </div>
</div>

<!-- Task Table -->
<div class="awt-table-container">
    <div class="table-responsive">
        <table class="table awt-table">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Task Title & Instructions</th>
                    <th>Assigned Intern</th>
                    <th>Mentor</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-tasks display-6 text-secondary mb-3 d-block"></i>
                            No work tasks found. Click "Assign New Task" to create one.
                        </td>
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
                            <td>
                                <a href="intern_view.php?id=<?= $task['intern_id']; ?>" class="fw-bold text-primary text-decoration-none">
                                    <?= e($task['intern_name']); ?>
                                </a>
                                <small class="text-muted d-block"><?= e($task['sInstitute'] ?: '—'); ?></small>
                            </td>
                            <td class="small fw-semibold text-dark">
                                <?= e($task['supervisor_name'] ?: 'Admin'); ?>
                            </td>
                            <td><?= priority_badge($task['priority']); ?></td>
                            <td class="small fw-semibold <?= strtotime($task['due_date']) < time() && $task['status'] !== 'Completed' ? 'text-danger' : ''; ?>">
                                <?= format_date($task['due_date']); ?>
                            </td>
                            <td><?= task_status_badge($task['status']); ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editTaskModal<?= $task['id']; ?>" title="Update Task Status">
                                    <i class="fas fa-edit text-dark"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal: Update Task Status -->
                        <div class="modal fade text-start" id="editTaskModal<?= $task['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="tasks.php" method="POST" class="modal-content">
                                    <?= csrf_input(); ?>
                                    <input type="hidden" name="action" value="update_task_status">
                                    <input type="hidden" name="task_id" value="<?= $task['id']; ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Update Task: <?= e($task['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="Under Review" <?= $task['status'] === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                                                <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary fw-bold">Save Status</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign New Task -->
<div class="modal fade" id="newTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="tasks.php" method="POST" class="modal-content">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create_task">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Assign Work Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Intern <span class="text-danger">*</span></label>
                    <select name="intern_id" class="form-select" required>
                        <option value="">Select recipient intern...</option>
                        <?php foreach ($internList as $i): ?>
                            <option value="<?= $i['id']; ?>"><?= e($i['sname']); ?> (<?= e($i['sInstitute']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Supervising Mentor</label>
                    <select name="supervisor_id" class="form-select">
                        <option value="">Administrative Lead</option>
                        <?php foreach ($supervisorList as $s): ?>
                            <option value="<?= $s['id']; ?>"><?= e($s['name']); ?> (<?= e($s['department']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Assist in Distribution of Ration Packages">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Instructions / Scope</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Provide step-by-step guidance..."></textarea>
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
                        <label class="form-label fw-semibold">Deadline (Due Date)</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> Assign Task</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
