<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Shared Role-Based Sidebar
 */

$userRole = current_user_role();
$baseUrl = get_base_url();
$activeScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>

<aside class="app-sidebar">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <img src="<?= $baseUrl; ?>assets/images/awt-logo.png" alt="AWT Logo" style="width:42px;height:42px;object-fit:contain;">
        <div class="brand-text">
            <h5>AWT Interns</h5>
            <span>Alamgir Welfare Trust</span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar-menu">
        <?php if ($userRole === 'admin'): ?>
            <!-- ADMIN MENU -->
            <div class="menu-label">Main Console</div>
            <a class="nav-link <?= $activeScript === 'index.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/index.php">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="menu-label">Internship Management</div>
            <a class="nav-link <?= $activeScript === 'interns.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/interns.php">
                <i class="fas fa-user-graduate"></i> Intern Directory
            </a>
            <a class="nav-link <?= $activeScript === 'intern_add.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/intern_add.php">
                <i class="fas fa-user-plus"></i> Add New Intern
            </a>
            <a class="nav-link <?= $activeScript === 'applications.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/applications.php">
                <i class="fas fa-file-signature"></i> Applications
            </a>
            <a class="nav-link <?= $activeScript === 'supervisors.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/supervisors.php">
                <i class="fas fa-user-tie"></i> Supervisors
            </a>

            <div class="menu-label">Operations & Progress</div>
            <a class="nav-link <?= $activeScript === 'attendance.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/attendance.php">
                <i class="fas fa-calendar-check"></i> Daily Attendance
            </a>
            <a class="nav-link <?= $activeScript === 'tasks.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/tasks.php">
                <i class="fas fa-tasks"></i> Task Assignments
            </a>
            <a class="nav-link <?= $activeScript === 'appraisal.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/appraisal.php">
                <i class="fas fa-star-half-alt"></i> Performance Appraisal
            </a>

            <div class="menu-label">Reports & System</div>
            <a class="nav-link <?= $activeScript === 'reports.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/reports.php">
                <i class="fas fa-chart-line"></i> Analytics & Reports
            </a>
            <a class="nav-link <?= $activeScript === 'settings.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>admin/settings.php">
                <i class="fas fa-sliders-h"></i> System Settings
            </a>

        <?php elseif ($userRole === 'supervisor'): ?>
            <!-- SUPERVISOR MENU -->
            <div class="menu-label">Supervisor Portal</div>
            <a class="nav-link <?= $activeScript === 'index.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>supervisor/index.php">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a class="nav-link <?= $activeScript === 'interns.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>supervisor/interns.php">
                <i class="fas fa-users"></i> My Interns
            </a>
            <a class="nav-link <?= $activeScript === 'attendance.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>supervisor/attendance.php">
                <i class="fas fa-calendar-check"></i> Mark Attendance
            </a>
            <a class="nav-link <?= $activeScript === 'tasks.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>supervisor/tasks.php">
                <i class="fas fa-tasks"></i> Task Reviews
            </a>
            <a class="nav-link <?= $activeScript === 'appraisal.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>supervisor/appraisal.php">
                <i class="fas fa-clipboard-check"></i> Evaluation & Ratings
            </a>

        <?php elseif ($userRole === 'intern'): ?>
            <!-- INTERN MENU -->
            <div class="menu-label">Intern Portal</div>
            <a class="nav-link <?= $activeScript === 'index.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/index.php">
                <i class="fas fa-home"></i> My Portal
            </a>
            <a class="nav-link <?= $activeScript === 'profile.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/profile.php">
                <i class="fas fa-id-card"></i> Personal Profile
            </a>
            <a class="nav-link <?= $activeScript === 'tasks.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/tasks.php">
                <i class="fas fa-tasks"></i> Assigned Tasks
            </a>
            <a class="nav-link <?= $activeScript === 'attendance.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/attendance.php">
                <i class="fas fa-calendar-alt"></i> My Attendance
            </a>
            <a class="nav-link <?= $activeScript === 'certificate.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/certificate.php">
                <i class="fas fa-certificate"></i> My Certificate
            </a>
            <a class="nav-link <?= $activeScript === 'offer_letter.php' ? 'active' : ''; ?>" href="<?= $baseUrl; ?>intern/offer_letter.php">
                <i class="fas fa-envelope-open-text"></i> Offer Letter
            </a>
        <?php endif; ?>

        <div class="menu-label">Public Access</div>
        <a class="nav-link" href="<?= $baseUrl; ?>apply.php" target="_blank">
            <i class="fas fa-paper-plane"></i> Apply for Internship
        </a>
        <a class="nav-link" href="<?= $baseUrl; ?>verify.php" target="_blank">
            <i class="fas fa-search"></i> Verify Certificate
        </a>
    </nav>

    <!-- Sidebar User Footer -->
    <?php if ($currentUser): ?>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="fas fa-user-circle text-white fs-4"></i>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($currentUser['name']); ?></div>
                <div class="sidebar-user-role"><?= e($currentUser['role']); ?></div>
            </div>
            <a href="<?= $baseUrl; ?>logout.php" class="text-secondary ms-auto text-decoration-none" title="Log Out">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    <?php endif; ?>
</aside>
