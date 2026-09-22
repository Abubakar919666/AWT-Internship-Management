<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Public Landing Portal
 */

define('AWT_APP', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    $role = current_user_role();
    if ($role === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($role === 'supervisor') {
        header("Location: supervisor/index.php");
        exit;
    } else {
        header("Location: intern/index.php");
        exit;
    }
}

// Fetch quick stats for public counters
$totalInterns = 700;
try {
    $row = db_fetch_one("SELECT COUNT(*) AS total FROM interns");
    if ($row) $totalInterns = (int)$row['total'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWT Intern Management System | Alamgir Welfare Trust Int'l</title>
    <meta name="description" content="Official Internship Management Portal of Alamgir Welfare Trust Int'l. Manage applications, attendance, tasks, and certificates.">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Main Style -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/awt-logo.png">
    <style>
        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0d9488 100%);
            color: #ffffff;
            padding: 5rem 0 4rem;
            position: relative;
            overflow: hidden;
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 100px;
            background: var(--awt-bg);
            border-radius: 50% 50% 0 0;
        }
        .portal-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.1);
        }
        .portal-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

<!-- Navigation Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 border-bottom border-white-10" style="background:#0f172a !important;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
            <img src="assets/images/awt-logo.png" alt="AWT Logo" style="width:40px;height:40px;object-fit:contain;">
            <span>Alamgir Welfare Trust Int'l</span>
        </a>
        <div class="d-flex gap-2">
            <a href="login.php" class="btn btn-outline-light btn-sm px-3"><i class="fas fa-sign-in-alt me-1"></i> Sign In</a>
            <a href="apply.php" class="btn btn-primary btn-sm px-3"><i class="fas fa-paper-plane me-1"></i> Apply Online</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-banner text-center">
    <div class="container position-relative" style="z-index:2;">
        <span class="badge bg-warning text-dark px-3 py-2 mb-3 fw-bold text-uppercase tracking-wider">Official Internship Program</span>
        <h1 class="display-5 fw-extrabold mb-3">Empowering Youth Through<br>Community Leadership & Service</h1>
        <p class="lead mx-auto mb-4" style="max-width: 750px; opacity: 0.9;">
            Alamgir Welfare Trust Int'l provides premier internship opportunities for university and college students to contribute to impactful social development, healthcare, and humanitarian projects.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="apply.php" class="btn btn-light btn-lg px-4 fw-bold shadow-sm"><i class="fas fa-file-signature text-primary me-2"></i>Apply for Internship</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-4 fw-semibold"><i class="fas fa-user-lock me-2"></i>Access Portal</a>
            <a href="verify.php" class="btn btn-outline-light btn-lg px-4 fw-semibold"><i class="fas fa-check-circle me-2"></i>Verify Certificate</a>
        </div>
    </div>
</section>

<!-- Portals & Quick Access Grid -->
<section class="py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <!-- Portal 1: Intern Portal -->
            <div class="col-md-4">
                <div class="portal-card">
                    <div class="portal-icon" style="background:#eff6ff;color:#2563eb;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Intern Portal</h4>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Active interns can log in to view assigned projects, submit daily work updates, track attendance records, and access their official completion certificate.
                    </p>
                    <a href="login.php?role=intern" class="btn btn-outline-primary w-100 fw-semibold">
                        Intern Sign In <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Portal 2: Supervisor Panel -->
            <div class="col-md-4">
                <div class="portal-card">
                    <div class="portal-icon" style="background:#f0fdfa;color:#0d9488;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Supervisor Panel</h4>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Department mentors and supervisors can monitor assigned students, assign and review tasks, mark attendance, and submit 10-criteria performance appraisals.
                    </p>
                    <a href="login.php?role=supervisor" class="btn btn-outline-success w-100 fw-semibold">
                        Supervisor Sign In <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Portal 3: Administration -->
            <div class="col-md-4">
                <div class="portal-card">
                    <div class="portal-icon" style="background:#fdf2f8;color:#db2777;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Admin Dashboard</h4>
                    <p class="text-muted small mb-4 flex-grow-1">
                        Full administrative access to manage all 700+ intern records, process new applications, issue official certificates, manage supervisors, and export reports.
                    </p>
                    <a href="login.php?role=admin" class="btn btn-outline-dark w-100 fw-semibold">
                        Administrator Sign In <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats & Certificate Verification Box -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm p-4 bg-white rounded-4 text-center">
                    <h5 class="fw-bold mb-2"><i class="fas fa-award text-warning me-2"></i>Verify Internship Certificate</h5>
                    <p class="text-muted small mb-3">Employers and universities can verify authentic certificates issued by Alamgir Welfare Trust Int'l.</p>
                    <form action="verify.php" method="GET" class="d-flex gap-2 max-w-500 mx-auto w-100" style="max-width: 480px;">
                        <input type="text" name="token" class="form-control" placeholder="Enter Certificate No or Token..." required>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Verify</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-white border-top py-4 text-center text-muted small mt-auto">
    <div class="container">
        <p class="mb-1">&copy; <?= date('Y'); ?> <strong>Alamgir Welfare Trust Int'l</strong>. All rights reserved.</p>
        <p class="mb-0">Head Office: Alamgir Road, Bahadurabad, Karachi, Pakistan | Phone: +92-21-111-153-153</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
