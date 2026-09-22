<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Logout Handler
 */

define('AWT_APP', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

logout_user();
header("Location: login.php?message=logged_out");
exit;
