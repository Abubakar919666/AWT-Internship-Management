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

/**
 * Ensure `photo` column exists in `interns` table
 */
function ensure_intern_photo_column(): void {
    static $checked = false;
    if ($checked) return;
    try {
        $cols = db_fetch_all("SHOW COLUMNS FROM interns LIKE 'photo'");
        if (empty($cols)) {
            get_db()->exec("ALTER TABLE interns ADD COLUMN `photo` VARCHAR(255) NULL AFTER `Student_ID`");
        }
        $checked = true;
    } catch (Exception $e) {
        $checked = true;
    }
}

/**
 * Handle base64 encoded image upload (from webcam, camera scan, or cropped canvas)
 */
function handle_base64_image_upload(string $dataUri, string $targetSubdir = 'uploads/photos', string $prefix = 'photo_'): array {
    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/is', $dataUri, $matches)) {
        return ['success' => false, 'error' => 'Invalid image format data.'];
    }

    $ext = strtolower($matches[1]);
    if ($ext === 'jpeg') $ext = 'jpg';
    $rawBase64 = str_replace(' ', '+', $matches[2]);
    $imageData = base64_decode($rawBase64);

    if ($imageData === false || strlen($imageData) < 100) {
        return ['success' => false, 'error' => 'Failed to decode captured image data.'];
    }

    if (strlen($imageData) > 8 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image exceeds 8MB size limit.'];
    }

    $uploadDir = __DIR__ . '/../assets/' . $targetSubdir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;

    if (file_put_contents($targetPath, $imageData) === false) {
        return ['success' => false, 'error' => 'Failed to write image file to disk. Check permissions.'];
    }

    return [
        'success'       => true,
        'relative_path' => 'assets/' . $targetSubdir . '/' . $filename,
        'original_name' => 'camera_scan_' . date('Ymd_His') . '.' . $ext,
        'filename'      => $filename
    ];
}

/**
 * Returns valid relative web path for an intern's photo, or null if missing/not found
 */
function intern_photo_url(?string $photoPath): ?string {
    if (!empty($photoPath)) {
        $clean = ltrim($photoPath, '/');
        $fullPath = __DIR__ . '/../' . $clean;
        if (file_exists($fullPath)) {
            return $clean;
        }
    }
    return null;
}

/**
 * Ensure all department supervisors exist, are active, and have login accounts
 */
function ensure_all_supervisors_active(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $supervisorsList = [
            [
                'id'          => 1,
                'name'        => 'Muhammad Wali Saleem',
                'email'       => 'supervisor@awt.org',
                'username'    => 'wali.saleem',
                'department'  => 'Coordination & Media',
                'designation' => 'Officer - Coordination',
                'phone'       => '+92-300-1234567',
                'mentors'     => ['%Wali%', '%Saleem%']
            ],
            [
                'id'          => 2,
                'name'        => 'Abdul Latif',
                'email'       => 'latif@awt.org',
                'username'    => 'abdul.latif',
                'department'  => 'Operations & Social Work',
                'designation' => 'Senior Supervisor',
                'phone'       => '+92-300-7654321',
                'mentors'     => ['%Latif%']
            ],
            [
                'id'          => 3,
                'name'        => 'Muhammad Amir',
                'email'       => 'amir@awt.org',
                'username'    => 'muhammad.amir',
                'department'  => 'Health & OPD Unit',
                'designation' => 'Department Supervisor',
                'phone'       => '+92-300-9988776',
                'mentors'     => ['%Amir%']
            ],
            [
                'id'          => 4,
                'name'        => 'Sohail Ahmed Khan',
                'email'       => 'sohail@awt.org',
                'username'    => 'sohail.khan',
                'department'  => 'Administration',
                'designation' => 'Assistant Coordinator',
                'phone'       => '+92-300-5544332',
                'mentors'     => ['%Sohail%']
            ],
            [
                'id'          => 5,
                'name'        => 'Umer Qureshi',
                'email'       => 'umer@awt.org',
                'username'    => 'umer.qureshi',
                'department'  => 'Marketing & Public Relations',
                'designation' => 'Officer - Coordination',
                'phone'       => '+92-300-4433221',
                'mentors'     => ['%Umer%', '%Qureshi%']
            ],
            [
                'id'          => 6,
                'name'        => 'Niaz Khan',
                'email'       => 'niaz@awt.org',
                'username'    => 'niaz.khan',
                'department'  => 'Field Operations & Logistics',
                'designation' => 'Field Supervisor',
                'phone'       => '+92-300-3322110',
                'mentors'     => ['%Niaz%']
            ],
            [
                'id'          => 7,
                'name'        => 'Nisar Ahmed',
                'email'       => 'nisar@awt.org',
                'username'    => 'nisar.ahmed',
                'department'  => 'HR & Internship Coordination',
                'designation' => 'HR Coordinator',
                'phone'       => '+92-300-1122334',
                'mentors'     => ['%Nisar%']
            ]
        ];

        $defaultHash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm'; // supervisor123

        foreach ($supervisorsList as $s) {
            // 1. Ensure supervisor record in `supervisors`
            $sup = db_fetch_one("SELECT * FROM supervisors WHERE id = ? OR email = ? OR name = ? LIMIT 1", [$s['id'], $s['email'], $s['name']]);
            $supId = null;

            if ($sup) {
                $supId = (int)$sup['id'];
                db_query("UPDATE supervisors SET name = ?, email = ?, phone = ?, department = ?, designation = ?, is_active = 1 WHERE id = ?", [
                    $s['name'], $s['email'], $s['phone'], $s['department'], $s['designation'], $supId
                ]);
            } else {
                db_query("INSERT INTO supervisors (id, name, email, phone, department, designation, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)", [
                    $s['id'], $s['name'], $s['email'], $s['phone'], $s['department'], $s['designation']
                ]);
                $supId = $s['id'];
            }

            // 2. Ensure user account in `users`
            $u = db_fetch_one("SELECT * FROM users WHERE email = ? OR username = ? OR supervisor_id = ? LIMIT 1", [$s['email'], $s['username'], $supId]);
            $userId = null;

            if ($u) {
                $userId = (int)$u['id'];
                db_query("UPDATE users SET name = ?, email = ?, username = ?, role = 'supervisor', supervisor_id = ?, status = 'active' WHERE id = ?", [
                    $s['name'], $s['email'], $s['username'], $supId, $userId
                ]);
            } else {
                db_query("INSERT INTO users (name, email, username, password_hash, role, supervisor_id, status) VALUES (?, ?, ?, ?, 'supervisor', ?, 'active')", [
                    $s['name'], $s['email'], $s['username'], $defaultHash, $supId
                ]);
                $userId = (int)db_last_insert_id();
            }

            // Link user_id in supervisors
            db_query("UPDATE supervisors SET user_id = ? WHERE id = ?", [$userId, $supId]);

            // Link unassigned matching interns to this supervisor_id
            foreach ($s['mentors'] as $pattern) {
                db_query("UPDATE interns SET supervisor_id = ? WHERE (supervisor_id IS NULL OR supervisor_id = 0) AND Mentor LIKE ?", [$supId, $pattern]);
            }
        }
    } catch (Exception $e) {
        // Silently continue
    }
}

/**
 * Get active supervisor context for supervisor dashboard & pages
 * Supports switching between individual supervisors and "All Supervisors (Consolidated View)" mode.
 */
function get_active_supervisor_context(): array {
    ensure_all_supervisors_active();

    // Check if switch requested via GET
    if (isset($_GET['switch_supervisor'])) {
        $req = trim($_GET['switch_supervisor']);
        $_SESSION['active_supervisor_id'] = $req;
    }

    $allSupervisors = db_fetch_all("
        SELECT s.*, u.username,
               (SELECT COUNT(*) FROM interns WHERE supervisor_id = s.id OR Mentor LIKE CONCAT('%', s.name, '%')) AS intern_count
        FROM supervisors s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.is_active = 1
        ORDER BY s.id ASC
    ");

    $user = current_user();
    $activeKey = $_SESSION['active_supervisor_id'] ?? null;

    // 1. All Supervisors mode
    if ($activeKey === 'all') {
        $totalInterns = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns")['c'] ?? 0);
        return [
            'is_all'          => true,
            'active_id'       => 'all',
            'supervisor'      => [
                'id'          => 'all',
                'name'        => 'All Supervisors (Consolidated View)',
                'department'  => 'All Departments',
                'designation' => 'Executive Management',
                'email'       => 'All Supervisors Active',
                'phone'       => 'All Lines',
                'intern_count'=> $totalInterns
            ],
            'all_supervisors' => $allSupervisors,
            'filter_sql'      => '1=1',
            'filter_params'   => []
        ];
    }

    // 2. Specific supervisor by ID requested in session
    if ($activeKey !== null && is_numeric($activeKey)) {
        $targetId = (int)$activeKey;
        foreach ($allSupervisors as $sup) {
            if ((int)$sup['id'] === $targetId) {
                return [
                    'is_all'          => false,
                    'active_id'       => $targetId,
                    'supervisor'      => $sup,
                    'all_supervisors' => $allSupervisors,
                    'filter_sql'      => '(supervisor_id = ? OR Mentor LIKE ?)',
                    'filter_params'   => [$targetId, "%{$sup['name']}%"]
                ];
            }
        }
    }

    // 3. Default to current logged-in user's assigned supervisor profile
    if ($user && !empty($user['supervisor_id'])) {
        foreach ($allSupervisors as $sup) {
            if ((int)$sup['id'] === (int)$user['supervisor_id']) {
                return [
                    'is_all'          => false,
                    'active_id'       => (int)$sup['id'],
                    'supervisor'      => $sup,
                    'all_supervisors' => $allSupervisors,
                    'filter_sql'      => '(supervisor_id = ? OR Mentor LIKE ?)',
                    'filter_params'   => [(int)$sup['id'], "%{$sup['name']}%"]
                ];
            }
        }
    }

    // 4. Default fallback: All Supervisors consolidated view
    $totalInterns = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM interns")['c'] ?? 0);
    return [
        'is_all'          => true,
        'active_id'       => 'all',
        'supervisor'      => [
            'id'          => 'all',
            'name'        => 'All Supervisors (Consolidated View)',
            'department'  => 'All Departments',
            'designation' => 'Executive Management',
            'email'       => 'All Supervisors Active',
            'phone'       => 'All Lines',
            'intern_count'=> $totalInterns
        ],
        'all_supervisors' => $allSupervisors,
        'filter_sql'      => '1=1',
        'filter_params'   => []
    ];

    return [
        'is_all'          => true,
        'active_id'       => 'all',
        'supervisor'      => [
            'id'          => 'all',
            'name'        => 'All Supervisors',
            'department'  => 'Coordination',
            'designation' => 'Supervisor',
            'intern_count'=> 0
        ],
        'all_supervisors' => [],
        'filter_sql'      => '1=1',
        'filter_params'   => []
    ];
}

/**
 * Render supervisor switcher banner ribbon
 */
function render_supervisor_switcher_ribbon(array $context, string $targetAction = ''): void {
    $activeSup = $context['supervisor'];
    $isAll = $context['is_all'];
    $activeId = $context['active_id'];
    $allSupervisors = $context['all_supervisors'];
    ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);border:1px solid #bfdbfe !important;">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center <?= $isAll ? 'bg-warning text-dark' : 'bg-primary text-white'; ?> shadow-sm" style="width:46px;height:46px;font-size:1.25rem;">
                    <i class="fas <?= $isAll ? 'fa-layer-group' : 'fa-user-tie'; ?>"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $isAll ? 'bg-warning text-dark' : 'bg-primary'; ?> text-uppercase px-2 py-1" style="font-size:10px;letter-spacing:0.5px;">
                            <i class="fas fa-check-circle me-1"></i>Active Supervisor View
                        </span>
                        <span class="text-muted small fw-semibold"><?= $isAll ? 'All Departments' : e($activeSup['department'] ?? 'Department'); ?></span>
                    </div>
                    <h5 class="mb-0 fw-bold text-dark mt-1">
                        <?= e($activeSup['name']); ?>
                        <span class="text-primary small fw-semibold ms-2">(<?= (int)($activeSup['intern_count'] ?? 0); ?> Interns Assigned)</span>
                    </h5>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                <span class="small fw-bold text-secondary text-nowrap"><i class="fas fa-exchange-alt me-1 text-primary"></i>Switch Supervisor:</span>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-white border fw-bold dropdown-toggle shadow-sm px-3 py-2 bg-white" data-bs-toggle="dropdown" aria-expanded="false" style="min-width:240px;text-align:left;">
                        <i class="fas <?= $isAll ? 'fa-layer-group text-warning' : 'fa-user-tie text-primary'; ?> me-2"></i>
                        <span class="text-truncate" style="max-width:180px;display:inline-block;vertical-align:bottom;">
                            <?= e($activeSup['name']); ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:320px;border-radius:12px;padding:8px;">
                        <li class="dropdown-header text-uppercase small fw-bold text-muted" style="font-size:11px;">
                            <i class="fas fa-users-cog me-1 text-primary"></i> Select Active Supervisor
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 rounded <?= $isAll ? 'active fw-bold' : ''; ?>" 
                               href="?switch_supervisor=all">
                                <span><i class="fas fa-layer-group me-2 text-warning"></i><strong>All Supervisors (Consolidated)</strong></span>
                                <?php if ($isAll): ?><i class="fas fa-check text-primary"></i><?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php foreach ($allSupervisors as $s): ?>
                            <?php $selected = (!$isAll && (int)$activeId === (int)$s['id']); ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 rounded <?= $selected ? 'active fw-bold' : ''; ?>" 
                                   href="?switch_supervisor=<?= $s['id']; ?>">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="fas fa-user-tie me-2 <?= $selected ? 'text-primary' : 'text-secondary'; ?>"></i>
                                            <?= e($s['name']); ?>
                                        </div>
                                        <small class="text-muted d-block ms-4" style="font-size:11px;">
                                            <?= e($s['department']); ?> &bull; <?= (int)$s['intern_count']; ?> interns
                                        </small>
                                    </div>
                                    <?php if ($selected): ?><i class="fas fa-check text-primary ms-2"></i><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <a href="?switch_supervisor=all" class="btn btn-sm <?= $isAll ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'; ?> shadow-sm py-2">
                    <i class="fas fa-globe me-1"></i> View All
                </a>
            </div>
        </div>
    </div>
    <?php
}

