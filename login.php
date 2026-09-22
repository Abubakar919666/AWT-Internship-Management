<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Unified Login Controller
 */

define('AWT_APP', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$error = '';
$selectedRole = sanitize($_GET['role'] ?? '');

// If already logged in, redirect
if (is_logged_in()) {
    $role = current_user_role();
    if ($role === 'admin') header("Location: admin/index.php");
    elseif ($role === 'supervisor') header("Location: supervisor/index.php");
    else header("Location: intern/index.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = 'Security validation failed (CSRF token expired). Please try again.';
    } else {
        $loginInput = sanitize($_POST['login_input'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($loginInput) || empty($password)) {
            $error = 'Please enter both your identifier (email, username, or intern ID) and password.';
        } else {
            // 1. Try finding account in `users` table first
            $user = db_fetch_one(
                "SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active' LIMIT 1",
                [$loginInput, $loginInput]
            );

            if ($user && verify_user_password($password, $user['password_hash'], (int)$user['id'])) {
                login_user($user);
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                } elseif ($user['role'] === 'supervisor') {
                    header("Location: supervisor/index.php");
                } else {
                    header("Location: intern/index.php");
                }
                exit;
            }

            // 2. Fallback check for existing 700 Intern records:
            // Allows intern to log in with their Email, ERPNO, ID, or Cellnumber!
            $intern = db_fetch_one(
                "SELECT * FROM interns WHERE (Email = ? OR ERPNO = ? OR Cellnumber = ? OR id = ?) LIMIT 1",
                [$loginInput, $loginInput, $loginInput, is_numeric($loginInput) ? (int)$loginInput : 0]
            );

            if ($intern) {
                // Check if intern already has user account
                $existingUser = null;
                if (!empty($intern['user_id'])) {
                    $existingUser = db_fetch_one("SELECT * FROM users WHERE id = ? LIMIT 1", [$intern['user_id']]);
                }

                if ($existingUser && verify_user_password($password, $existingUser['password_hash'], (int)$existingUser['id'])) {
                    login_user($existingUser);
                    header("Location: intern/index.php");
                    exit;
                } elseif (!$existingUser && ($password === 'intern123' || $password === 'admin123' || $password === 'awt2026')) {
                    // Provision a user record for this intern on first login
                    $internEmail = !empty($intern['Email']) ? $intern['Email'] : 'intern_' . $intern['id'] . '@awt.org';
                    
                    // Check if email already used in users
                    $checkEmail = db_fetch_one("SELECT id FROM users WHERE email = ?", [$internEmail]);
                    if ($checkEmail) {
                        $internEmail = 'intern_' . $intern['id'] . '_' . time() . '@awt.org';
                    }

                    $pwdHash = password_hash($password, PASSWORD_DEFAULT);
                    db_query(
                        "INSERT INTO users (name, email, username, password_hash, role, intern_id, status) VALUES (?, ?, ?, ?, 'intern', ?, 'active')",
                        [$intern['sname'], $internEmail, 'intern_' . $intern['id'], $pwdHash, $intern['id']]
                    );
                    $newUserId = (int)db_last_insert_id();
                    db_query("UPDATE interns SET user_id = ? WHERE id = ?", [$newUserId, $intern['id']]);

                    $newUser = db_fetch_one("SELECT * FROM users WHERE id = ?", [$newUserId]);
                    login_user($newUser);
                    header("Location: intern/index.php");
                    exit;
                }
            }

            $error = 'Invalid credentials. Please check your username, email, or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | AWT Intern Management System</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Main Style -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/images/awt-logo.png">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
        }
        .login-header {
            background: #f8fafc;
            padding: 2.25rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .login-body {
            padding: 2rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="assets/images/awt-logo.png" alt="AWT Logo" class="mx-auto mb-3 d-block" style="width:64px;height:64px;object-fit:contain;">
        <h4 class="fw-bold mb-1" style="color:var(--awt-primary);">AWT Portal Sign In</h4>
        <p class="text-muted small mb-0">Alamgir Welfare Trust Int'l</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small py-2" role="alert">
                <i class="fas fa-exclamation-circle me-2 fs-6"></i>
                <div><?= e($error); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="login.php<?= !empty($selectedRole) ? '?role=' . e($selectedRole) : ''; ?>" method="POST">
            <?= csrf_input(); ?>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Email, Username, or Intern ID / ERP</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="login_input" class="form-control border-start-0" 
                           placeholder="e.g. admin@awt.org or Intern ID" 
                           value="<?= e($_POST['login_input'] ?? ''); ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-semibold small text-muted mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" 
                           placeholder="Enter your password" 
                           value="" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="background:var(--awt-primary);border-color:var(--awt-primary);">
                <i class="fas fa-sign-in-alt me-1"></i> Sign In to Portal
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
            <span class="mx-2 text-muted">|</span>
            <a href="apply.php" class="text-decoration-none small text-primary fw-semibold">Apply Online</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
