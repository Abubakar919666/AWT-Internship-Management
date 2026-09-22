<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Common Helper Functions & Utilities
 */

if (!defined('AWT_APP')) {
    define('AWT_APP', true);
}

/**
 * Escape HTML output to prevent XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize text input from POST/GET
 */
function sanitize(?string $string): string {
    return trim(strip_tags($string ?? ''));
}

/**
 * Set flash alert message for next page view
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Display and clear flash alert message
 */
function display_flash(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = match($type) {
            'success' => 'fa-check-circle',
            'danger'  => 'fa-exclamation-triangle',
            'warning' => 'fa-exclamation-circle',
            default   => 'fa-info-circle'
        };
        return "
            <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm' role='alert'>
                <i class='fas {$icon} me-2 fs-5'></i>
                <div>{$msg}</div>
                <button type='button' class='btn-close ms-auto' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }
    return '';
}

/**
 * Format date nicely (e.g., Aug 14, 2026)
 */
function format_date(?string $date, string $format = 'M d, Y'): string {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Format datetime nicely
 */
function format_datetime(?string $datetime, string $format = 'M d, Y h:i A'): string {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return '—';
    }
}

/**
 * Render bootstrap badge for intern/application status
 */
function status_badge(string $status): string {
    $clean = strtolower(trim($status));
    $map = [
        'confirmed'  => ['bg' => 'success',   'icon' => 'fa-check',         'text' => 'Confirmed'],
        'completed'  => ['bg' => 'primary',   'icon' => 'fa-graduation-cap','text' => 'Completed'],
        'active'     => ['bg' => 'info',      'icon' => 'fa-user-check',    'text' => 'Active'],
        'waiting'    => ['bg' => 'warning',   'icon' => 'fa-clock',         'text' => 'Waiting List'],
        'pending'    => ['bg' => 'secondary', 'icon' => 'fa-hourglass-start','text' => 'Pending'],
        'terminated' => ['bg' => 'danger',    'icon' => 'fa-ban',           'text' => 'Terminated'],
        'present'    => ['bg' => 'success',   'icon' => 'fa-check',         'text' => 'Present'],
        'absent'     => ['bg' => 'danger',    'icon' => 'fa-times',         'text' => 'Absent'],
        'leave'      => ['bg' => 'warning',   'icon' => 'fa-umbrella-beach','text' => 'Leave'],
        'late'       => ['bg' => 'secondary', 'icon' => 'fa-clock',         'text' => 'Late'],
    ];

    $cfg = $map[$clean] ?? ['bg' => 'secondary', 'icon' => 'fa-circle', 'text' => ucfirst($clean)];
    return "<span class='badge bg-{$cfg['bg']} px-2 py-1'><i class='fas {$cfg['icon']} me-1'></i>{$cfg['text']}</span>";
}

/**
 * Render task priority badge
 */
function priority_badge(string $priority): string {
    $clean = ucfirst(strtolower(trim($priority)));
    $map = [
        'Urgent' => 'danger',
        'High'   => 'warning',
        'Medium' => 'primary',
        'Low'    => 'secondary'
    ];
    $bg = $map[$clean] ?? 'secondary';
    return "<span class='badge bg-{$bg}'>{$clean}</span>";
}

/**
 * Render task status badge
 */
function task_status_badge(string $status): string {
    $clean = trim($status);
    $map = [
        'Pending'      => ['bg' => 'secondary', 'icon' => 'fa-clock'],
        'In Progress'  => ['bg' => 'info text-dark', 'icon' => 'fa-spinner fa-spin'],
        'Completed'    => ['bg' => 'success',   'icon' => 'fa-check-circle'],
        'Under Review' => ['bg' => 'warning',   'icon' => 'fa-glasses']
    ];
    $cfg = $map[$clean] ?? ['bg' => 'secondary', 'icon' => 'fa-circle'];
    return "<span class='badge bg-{$cfg['bg']}'><i class='fas {$cfg['icon']} me-1'></i>{$clean}</span>";
}

/**
 * Compute performance grade letter and badge from total score (out of 100)
 */
function calculate_grade(int $totalScore): array {
    if ($totalScore >= 90) {
        return ['grade' => 'A+', 'color' => 'success', 'desc' => 'Outstanding'];
    } elseif ($totalScore >= 80) {
        return ['grade' => 'A',  'color' => 'success', 'desc' => 'Excellent'];
    } elseif ($totalScore >= 70) {
        return ['grade' => 'B',  'color' => 'primary', 'desc' => 'Very Good'];
    } elseif ($totalScore >= 60) {
        return ['grade' => 'C',  'color' => 'info',    'desc' => 'Good'];
    } elseif ($totalScore >= 50) {
        return ['grade' => 'D',  'color' => 'warning', 'desc' => 'Satisfactory'];
    } elseif ($totalScore > 0) {
        return ['grade' => 'F',  'color' => 'danger',  'desc' => 'Needs Improvement'];
    } else {
        return ['grade' => 'N/A', 'color' => 'secondary', 'desc' => 'Pending Evaluation'];
    }
}

/**
 * Check if the active script matches the link for navigation highlighting
 */
function is_active_page(string $page): string {
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return ($current === $page) ? 'active' : '';
}

/**
 * Stream a CSV file directly to user download
 */
function export_csv(array $headers, array $rows, string $filename = 'export.csv'): void {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM so Excel opens it with proper encoding
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

/**
 * Handle safe file upload (e.g. CV, assignment, avatar)
 */
function handle_file_upload(array $file, string $targetSubdir = 'uploads'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'No file was uploaded.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'Uploaded file exceeds max file size (5MB).'];
        default:
            return ['success' => false, 'error' => 'Unknown upload error.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds maximum limit of 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip'];
    if (!in_array($ext, $allowed, true)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, ZIP.'];
    }

    $uploadDir = __DIR__ . '/../assets/' . $targetSubdir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $finalFilename = time() . '_' . $safeName . '.' . $ext;
    $targetPath = $uploadDir . '/' . $finalFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.'];
    }

    return [
        'success'       => true,
        'relative_path' => 'assets/' . $targetSubdir . '/' . $finalFilename,
        'original_name' => $file['name'],
        'filename'      => $finalFilename
    ];
}
