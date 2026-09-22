<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Admin Settings & Organization Configuration
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'System Settings';
$pageSubtitle = 'Configure organization details, certificate signatory, and system preferences';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = 'Security session expired. Please submit again.';
    } else {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'save_org_settings') {
            $settingsToUpdate = [
                'org_name'                    => sanitize($_POST['org_name'] ?? ''),
                'org_tagline'                 => sanitize($_POST['org_tagline'] ?? ''),
                'org_address'                 => sanitize($_POST['org_address'] ?? ''),
                'org_phone'                   => sanitize($_POST['org_phone'] ?? ''),
                'org_email'                   => sanitize($_POST['org_email'] ?? ''),
                'org_website'                 => sanitize($_POST['org_website'] ?? ''),
                'hr_coordinator_name'         => sanitize($_POST['hr_coordinator_name'] ?? ''),
                'hr_coordinator_title'        => sanitize($_POST['hr_coordinator_title'] ?? ''),
                'active_year'                 => sanitize($_POST['active_year'] ?? ''),
                'certificate_signatory'       => sanitize($_POST['certificate_signatory'] ?? ''),
                'certificate_signatory_title' => sanitize($_POST['certificate_signatory_title'] ?? '')
            ];

            foreach ($settingsToUpdate as $key => $val) {
                db_query("
                    INSERT INTO settings (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ", [$key, $val]);
            }

            log_activity(current_user()['id'], 'Updated Settings', 'Organization settings updated');
            set_flash('success', 'Organization and certification settings updated successfully.');
            header("Location: settings.php");
            exit;
        } elseif ($action === 'change_admin_password') {
            $currentPwd = $_POST['current_password'] ?? '';
            $newPwd = $_POST['new_password'] ?? '';
            $confirmPwd = $_POST['confirm_password'] ?? '';

            $user = db_fetch_one("SELECT * FROM users WHERE id = ?", [current_user()['id']]);

            if (!verify_user_password($currentPwd, $user['password_hash'], (int)$user['id'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPwd) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($newPwd !== $confirmPwd) {
                $error = 'New password confirmation does not match.';
            } else {
                $hash = password_hash($newPwd, PASSWORD_DEFAULT);
                db_query("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $user['id']]);
                log_activity($user['id'], 'Changed Password', 'Admin changed their password');
                set_flash('success', 'Password updated successfully.');
                header("Location: settings.php");
                exit;
            }
        }
    }
}

// Load current settings
$currentSettings = [
    'org_name'                    => get_setting('org_name', 'Alamgir Welfare Trust Int\'l'),
    'org_tagline'                 => get_setting('org_tagline', 'Serving Humanity with Honor and Integrity'),
    'org_address'                 => get_setting('org_address', 'Alamgir Road, Bahadurabad, Karachi, Pakistan'),
    'org_phone'                   => get_setting('org_phone', '+92-21-111-153-153'),
    'org_email'                   => get_setting('org_email', 'internship@alamgirwelfaretrust.com.pk'),
    'org_website'                 => get_setting('org_website', 'www.alamgirwelfaretrust.com.pk'),
    'hr_coordinator_name'         => get_setting('hr_coordinator_name', 'Nisar Ahmed'),
    'hr_coordinator_title'        => get_setting('hr_coordinator_title', 'Coordinator'),
    'active_year'                 => get_setting('active_year', date('Y')),
    'certificate_signatory'       => get_setting('certificate_signatory', 'Nisar Ahmed'),
    'certificate_signatory_title' => get_setting('certificate_signatory_title', 'HR & Internship Coordinator')
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-10">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= e($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Organization & Certification Settings Form -->
        <form action="settings.php" method="POST" class="awt-card p-4 mb-4">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="save_org_settings">

            <div class="border-bottom pb-3 mb-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-building text-primary me-2"></i>Organization & Certificate Parameters</h5>
                <small class="text-muted">These values are dynamically rendered on official letters, certificates, and email notices.</small>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Organization Legal Name</label>
                    <input type="text" name="org_name" class="form-control" value="<?= e($currentSettings['org_name']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Current Active Year</label>
                    <input type="text" name="active_year" class="form-control" value="<?= e($currentSettings['active_year']); ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Organization Tagline / Subtitle</label>
                    <input type="text" name="org_tagline" class="form-control" value="<?= e($currentSettings['org_tagline']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Head Office Address</label>
                    <input type="text" name="org_address" class="form-control" value="<?= e($currentSettings['org_address']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Official Phone Number</label>
                    <input type="text" name="org_phone" class="form-control" value="<?= e($currentSettings['org_phone']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Official Email</label>
                    <input type="email" name="org_email" class="form-control" value="<?= e($currentSettings['org_email']); ?>">
                </div>
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-signature text-warning me-2"></i>Certificate & Offer Letter Signatory</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">HR Coordinator Name</label>
                    <input type="text" name="hr_coordinator_name" class="form-control" value="<?= e($currentSettings['hr_coordinator_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Coordinator Designation</label>
                    <input type="text" name="hr_coordinator_title" class="form-control" value="<?= e($currentSettings['hr_coordinator_title']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Certificate Signatory Name</label>
                    <input type="text" name="certificate_signatory" class="form-control" value="<?= e($currentSettings['certificate_signatory']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Certificate Signatory Title</label>
                    <input type="text" name="certificate_signatory_title" class="form-control" value="<?= e($currentSettings['certificate_signatory_title']); ?>" required>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Save Settings
                </button>
            </div>
        </form>

        <!-- Admin Security / Change Password Form -->
        <form action="settings.php" method="POST" class="awt-card p-4">
            <?= csrf_input(); ?>
            <input type="hidden" name="action" value="change_admin_password">

            <div class="border-bottom pb-3 mb-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-lock text-danger me-2"></i>Administrator Security & Password</h5>
                <small class="text-muted">Update your administrative login password</small>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">New Password</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-outline-danger px-4 fw-bold">
                    <i class="fas fa-key me-1"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
