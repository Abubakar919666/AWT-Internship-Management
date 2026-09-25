<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Handle Intern Photo Upload, Camera Scan & Removal
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
       || (!empty($_POST['ajax']) && $_POST['ajax'] === '1');

function respond(bool $success, string $message, ?string $photoUrl = null, int $internId = 0, bool $isAjax = false) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'   => $success,
            'message'   => $message,
            'photo_url' => $photoUrl,
            'intern_id' => $internId
        ]);
        exit;
    }

    set_flash($success ? 'success' : 'danger', $message);
    $redirectUrl = ($internId > 0) ? "intern_view.php?id={$internId}" : "interns.php";
    header("Location: {$redirectUrl}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', null, 0, $isAjax);
}

// Validate CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf)) {
    respond(false, 'Security session expired. Please refresh the page and try again.', null, 0, $isAjax);
}

$internId = (int)($_POST['intern_id'] ?? 0);
if ($internId <= 0) {
    respond(false, 'Invalid intern identifier.', null, 0, $isAjax);
}

// Make sure photo column exists
ensure_intern_photo_column();

$intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$internId]);
if (!$intern) {
    respond(false, 'Intern record not found.', null, $internId, $isAjax);
}

$action = sanitize($_POST['action'] ?? 'upload');

// REMOVE PHOTO ACTION
if ($action === 'remove') {
    if (!empty($intern['photo'])) {
        $oldFile = __DIR__ . '/../' . ltrim($intern['photo'], '/');
        if (file_exists($oldFile) && is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    db_query("UPDATE interns SET photo = NULL, Photograph = 0 WHERE id = ?", [$internId]);

    if (!empty($intern['user_id'])) {
        db_query("UPDATE users SET avatar = 'assets/images/default-avatar.png' WHERE id = ?", [$intern['user_id']]);
    }

    log_activity(current_user()['id'], 'Removed Intern Photo', "Removed photo for intern #{$internId}: {$intern['sname']}");
    respond(true, "Photograph for {$intern['sname']} has been removed.", null, $internId, $isAjax);
}

// UPLOAD / SCAN ACTION
$uploadResult = null;

// 1. Direct camera scan (Base64 data)
if (!empty($_POST['captured_image_data'])) {
    $uploadResult = handle_base64_image_upload($_POST['captured_image_data'], 'uploads/photos', 'intern_' . $internId . '_');
}
// 2. Standard file upload
elseif (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadResult = handle_file_upload($_FILES['photo_file'], 'uploads/photos');
} else {
    respond(false, 'Please select an image file or take a camera scan to upload.', null, $internId, $isAjax);
}

if (!$uploadResult || !$uploadResult['success']) {
    $errMsg = $uploadResult['error'] ?? 'File upload failed. Please verify format and size.';
    respond(false, $errMsg, null, $internId, $isAjax);
}

$newPhotoPath = $uploadResult['relative_path'];

// Safely delete previous photo if it exists and differs
if (!empty($intern['photo']) && $intern['photo'] !== $newPhotoPath) {
    $prevFile = __DIR__ . '/../' . ltrim($intern['photo'], '/');
    if (file_exists($prevFile) && is_file($prevFile)) {
        @unlink($prevFile);
    }
}

// Update intern record (save path & set Photograph checklist as received)
db_query("UPDATE interns SET photo = ?, Photograph = 1 WHERE id = ?", [$newPhotoPath, $internId]);

// Update linked user avatar if present
if (!empty($intern['user_id'])) {
    db_query("UPDATE users SET avatar = ? WHERE id = ?", [$newPhotoPath, $intern['user_id']]);
}

log_activity(current_user()['id'], 'Uploaded Intern Photo', "Uploaded new photograph for intern #{$internId}: {$intern['sname']}");

respond(true, "Photograph for {$intern['sname']} has been uploaded and updated successfully.", $newPhotoPath, $internId, $isAjax);
