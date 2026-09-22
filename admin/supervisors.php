<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisors & Department Heads Management
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Supervisors & Mentors';
$pageSubtitle = 'Manage department supervisors and mentor assignments';
$error = '';

// Handle POST: Add or Update Supervisor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $action = sanitize($_POST['action'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $designation = sanitize($_POST['designation'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            $error = 'Supervisor name is required.';
        } else {
            if ($action === 'create') {
                // Optionally create user account for supervisor login
                $userId = null;
                if (!empty($email)) {
                    $existingUser = db_fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
                    if (!$existingUser) {
                        $pwdHash = password_hash('supervisor123', PASSWORD_DEFAULT);
                        db_query("
                            INSERT INTO users (name, email, password_hash, role, status)
                            VALUES (?, ?, ?, 'supervisor', 'active')
                        ", [$name, $email, $pwdHash]);
                        $userId = (int)db_last_insert_id();
                    } else {
                        $userId = (int)$existingUser['id'];
                    }
                }

                db_query("
                    INSERT INTO supervisors (user_id, name, email, phone, department, designation, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$userId, $name, $email, $phone, $department, $designation, $isActive]);

                $supId = (int)db_last_insert_id();
                if ($userId) {
                    db_query("UPDATE users SET supervisor_id = ? WHERE id = ?", [$supId, $userId]);
                }

                log_activity(current_user()['id'], 'Created Supervisor', "Added supervisor #{$supId}: {$name}");
                set_flash('success', "Supervisor {$name} added successfully (Default password: supervisor123).");
                header("Location: supervisors.php");
                exit;
            } elseif ($action === 'update') {
                $supId = (int)($_POST['supervisor_id'] ?? 0);
                db_query("
                    UPDATE supervisors SET name = ?, email = ?, phone = ?, department = ?, designation = ?, is_active = ?
                    WHERE id = ?
                ", [$name, $email, $phone, $department, $designation, $isActive, $supId]);

                log_activity(current_user()['id'], 'Updated Supervisor', "Updated supervisor #{$supId}: {$name}");
                set_flash('success', "Supervisor record updated successfully.");
                header("Location: supervisors.php");
                exit;
            }
        }
    }
}

// Fetch all supervisors with count of assigned interns
$supervisors = db_fetch_all("
    SELECT s.*, COUNT(i.id) AS intern_count
    FROM supervisors s
    LEFT JOIN interns i ON i.supervisor_id = s.id
    GROUP BY s.id
    ORDER BY s.is_active DESC, s.name ASC
");

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= e($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0 text-dark">Active Supervisors: <span class="text-primary"><?= count($supervisors); ?></span></h5>
    </div>
    <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addSupervisorModal">
        <i class="fas fa-user-plus me-1"></i> Add Supervisor / Mentor
    </button>
</div>

<!-- Supervisors Roster Cards Grid -->
<div class="row g-4">
    <?php foreach ($supervisors as $sup): ?>
        <div class="col-md-6 col-lg-4">
            <div class="awt-card h-100 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="brand-icon" style="background:#eff6ff;color:#1e3a8a;border:1px solid #bfdbfe;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <span class="badge bg-<?= $sup['is_active'] ? 'success' : 'secondary'; ?>">
                            <?= $sup['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-1"><?= e($sup['name']); ?></h5>
                <p class="text-primary small fw-semibold mb-2"><?= e($sup['designation'] ?: 'Department Mentor'); ?></p>

                <div class="small text-muted mb-3 border-top pt-2">
                    <div><i class="fas fa-building text-secondary me-2"></i><?= e($sup['department'] ?: 'General Administration'); ?></div>
                    <?php if (!empty($sup['email'])): ?>
                        <div><i class="fas fa-envelope text-secondary me-2"></i><?= e($sup['email']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($sup['phone'])): ?>
                        <div><i class="fas fa-phone text-secondary me-2"></i><?= e($sup['phone']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="border-top pt-3 mt-auto d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-user-graduate me-1 text-primary"></i> <?= $sup['intern_count']; ?> Assigned Interns
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSupModal<?= $sup['id']; ?>">
                        <i class="fas fa-edit me-1"></i> Edit
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Supervisor Modal -->
        <div class="modal fade" id="editSupModal<?= $sup['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="supervisors.php" method="POST" class="modal-content">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="supervisor_id" value="<?= $sup['id']; ?>">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Supervisor: <?= e($sup['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= e($sup['name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= e($sup['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone / Cell</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($sup['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" name="department" class="form-control" value="<?= e($sup['department']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Designation / Title</label>
                            <input type="text" name="designation" class="form-control" value="<?= e($sup['designation']); ?>">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="activeChk<?= $sup['id']; ?>" <?= $sup['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activeChk<?= $sup['id']; ?>">Active Supervisor</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">Update Supervisor</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add Supervisor -->
<div class="modal fade" id="addSupervisorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="supervisors.php" method="POST" class="modal-content">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="create">

            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>Add New Supervisor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Supervisor Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Muhammad Wali Saleem">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address (Used for Login)</label>
                    <input type="email" name="email" class="form-control" placeholder="name@alamgirwelfaretrust.com.pk">
                    <div class="form-text">Will create login account with password: <code>supervisor123</code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="0300-1234567">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Department Assigned</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. Coordination, Health Services, IT, HR">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Official Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Coordinator">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="newActiveChk" checked>
                    <label class="form-check-label" for="newActiveChk">Mark as Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Supervisor</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
