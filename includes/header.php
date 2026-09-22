<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Shared Top Header & Navigation
 */

if (!defined('AWT_APP')) {
    define('AWT_APP', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$currentUser = current_user();
$baseUrl = get_base_url();
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> | AWT Intern Management System</title>
    <meta name="description" content="Professional Intern Management System for Alamgir Welfare Trust Int'l">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom Application CSS -->
    <link href="<?= $baseUrl; ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= $baseUrl; ?>assets/css/certificate.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $baseUrl; ?>assets/images/awt-logo.png">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Include Role-based Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="page-title"><?= e($pageTitle); ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <p class="page-breadcrumb mb-0"><?= e($pageSubtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="topbar-right">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle fs-5 text-primary"></i>
                            <span class="d-none d-md-inline fw-semibold"><?= e($currentUser['name']); ?></span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize d-none d-sm-inline"><?= e($currentUser['role']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="dropdown-header">
                                <small class="text-muted">Signed in as</small><br>
                                <strong><?= e($currentUser['email']); ?></strong>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($currentUser['role'] === 'intern'): ?>
                                <li><a class="dropdown-item" href="<?= $baseUrl; ?>intern/profile.php"><i class="fas fa-id-card me-2"></i>My Profile</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= $baseUrl; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="app-content">
            <?= display_flash(); ?>
