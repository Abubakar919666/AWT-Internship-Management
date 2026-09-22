<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Intern Deletion Handler
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!validate_csrf_token($token)) {
    set_flash('danger', 'Security validation failed. Deletion aborted.');
    header("Location: interns.php");
    exit;
}

$intern = db_fetch_one("SELECT id, sname FROM interns WHERE id = ?", [$id]);
if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

try {
    db_query("DELETE FROM interns WHERE id = ?", [$id]);
    log_activity(current_user()['id'], 'Deleted Intern', "Deleted intern #{$id}: {$intern['sname']}");
    set_flash('success', "Intern record #{$id} ({$intern['sname']}) was deleted successfully.");
} catch (Exception $e) {
    set_flash('danger', 'Error deleting intern: ' . $e->getMessage());
}

header("Location: interns.php");
exit;
