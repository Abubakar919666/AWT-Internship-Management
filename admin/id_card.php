<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Official Printable Intern Identity Card & Digital Badge
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);

// If intern is viewing, only allow their own record
if (current_user_role() === 'intern') {
    $curUser = current_user();
    if ($curUser['intern_id'] && (int)$curUser['intern_id'] !== $id) {
        $id = (int)$curUser['intern_id'];
    }
}

ensure_intern_photo_column();

$intern = db_fetch_one("
    SELECT i.*, s.name AS supervisor_name, s.department AS supervisor_dept
    FROM interns i
    LEFT JOIN supervisors s ON i.supervisor_id = s.id
    WHERE i.id = ?
", [$id]);

if (!$intern) {
    set_flash('danger', 'Intern record not found.');
    header("Location: interns.php");
    exit;
}

$photoUrl = intern_photo_url($intern['photo'] ?? null);
$hasPhoto = !empty($photoUrl);

// Card dates
$validFrom = !empty($intern['dateassignfrom']) ? format_date($intern['dateassignfrom'], 'd/m/Y') : '01/07/' . ($intern['iyear'] ?: date('Y'));
$validTo = !empty($intern['dateassignto']) ? format_date($intern['dateassignto'], 'd/m/Y') : '31/08/' . ($intern['iyear'] ?: date('Y'));
$periodDisplay = !empty($intern['Certificate_Period_Detail']) ? $intern['Certificate_Period_Detail'] : "{$validFrom} to {$validTo}";

// Verification URL for QR code
$verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/verify.php?cert=" . urlencode('AWT-' . ($intern['iyear'] ?: date('Y')) . '-' . str_pad($intern['id'], 4, '0', STR_PAD_LEFT));
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($verifyUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern ID Card — <?= e($intern['sname']); ?> (#<?= $intern['id']; ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/images/awt-logo.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@700&family=Libre+Barcode+39+Text&display=swap');

        body {
            background: #1e293b;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
            min-height: 100vh;
            padding: 2.5rem 1rem;
        }

        .id-card-actions {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .cards-deck {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 2.5rem;
            flex-wrap: wrap;
        }

        /* Standard CR-80 Portrait ID Card: 54mm x 85.6mm (approx 320px x 510px) */
        .id-badge {
            width: 330px;
            height: 520px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Top Lanyard Slot Graphic */
        .lanyard-hole {
            width: 50px;
            height: 12px;
            background: #0f172a;
            border-radius: 8px;
            margin: 10px auto 4px auto;
            opacity: 0.25;
            border: 1px solid #ffffff;
        }

        /* Card Header Gradient */
        .id-header {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #0f766e 100%);
            padding: 12px 14px 16px 14px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .id-header::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            right: 0;
            height: 8px;
            background: #d97706;
        }

        .id-header-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            margin-bottom: 4px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .id-header-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .id-header-sub {
            font-size: 9px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #a7f3d0;
            font-weight: 600;
        }

        /* Photo Area */
        .id-photo-section {
            padding-top: 14px;
            text-align: center;
        }

        .id-photo-frame {
            width: 110px;
            height: 135px;
            margin: 0 auto;
            border-radius: 12px;
            padding: 3px;
            background: linear-gradient(135deg, #059669, #d97706);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            display: inline-block;
        }

        .id-photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            background: #e2e8f0;
            display: block;
        }

        .id-photo-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        /* Details */
        .id-details {
            padding: 10px 18px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .id-name {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .id-role-badge {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            border: 1px solid #a7f3d0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .id-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 10px;
            text-align: left;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 10px;
        }

        .id-info-item label {
            display: block;
            color: #64748b;
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 1px;
        }

        .id-info-item span {
            font-weight: 700;
            color: #1e293b;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .id-footer-strip {
            background: #0f172a;
            color: #ffffff;
            padding: 8px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
        }

        .id-barcode {
            font-family: 'Libre Barcode 39 Text', monospace;
            font-size: 26px;
            letter-spacing: 2px;
            line-height: 1;
            color: #1e293b;
            margin-top: 4px;
        }

        /* BACK SIDE OF ID CARD */
        .id-badge.back-side {
            background: #ffffff;
        }

        .back-instructions {
            padding: 16px;
            font-size: 10px;
            color: #334155;
            line-height: 1.5;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .instruction-item {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .instruction-item i {
            color: #047857;
            margin-top: 2px;
        }

        .qr-section {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0;
        }

        .signature-block {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            margin-top: auto;
            font-size: 9px;
            text-align: center;
        }

        .sig-box {
            width: 45%;
        }

        .sig-line {
            border-bottom: 1px solid #94a3b8;
            height: 24px;
            margin-bottom: 3px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .id-card-actions {
                display: none !important;
            }
            .cards-deck {
                gap: 15mm;
                page-break-inside: avoid;
            }
            .id-badge {
                box-shadow: none !important;
                border: 1px solid #94a3b8 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Header -->
    <div class="id-card-actions no-print">
        <button type="button" class="btn btn-warning px-4 py-2 fw-bold shadow" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print ID Card (Front & Back)
        </button>
        <?php if (current_user_role() === 'admin'): ?>
            <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light px-3 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to Intern Profile
            </a>
            <button type="button" class="btn btn-outline-light px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-camera me-1"></i> <?= $hasPhoto ? 'Update Photo' : 'Upload Photo'; ?>
            </button>
        <?php else: ?>
            <a href="../intern/profile.php" class="btn btn-light px-3 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back to My Profile
            </a>
        <?php endif; ?>
    </div>

    <!-- Flash notification -->
    <?= display_flash(); ?>

    <!-- Two-Sided Printable Cards Deck -->
    <div class="cards-deck">
        <!-- ================= FRONT SIDE ================= -->
        <div class="id-badge">
            <div class="lanyard-hole"></div>

            <div class="id-header">
                <img src="../assets/images/awt-logo.png" alt="AWT" class="id-header-logo">
                <div class="id-header-title">Alamgir Welfare Trust Int'l</div>
                <div class="id-header-sub">Official Internship Program</div>
            </div>

            <!-- Photo Frame -->
            <div class="id-photo-section">
                <div class="id-photo-frame">
                    <?php if ($hasPhoto): ?>
                        <img src="../<?= e($photoUrl); ?>" alt="<?= e($intern['sname']); ?>" class="id-photo-img">
                    <?php else: ?>
                        <div class="id-photo-placeholder">
                            <i class="fas fa-user-graduate fa-2x mb-1 text-secondary"></i>
                            <span style="font-size:9px;">No Photo</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Intern Identity Information -->
            <div class="id-details">
                <div>
                    <h4 class="id-name"><?= e($intern['sname']); ?></h4>
                    <span class="id-role-badge"><i class="fas fa-certificate me-1"></i>Internee &bull; #<?= $intern['id']; ?></span>
                </div>

                <div class="id-info-grid">
                    <div class="id-info-item">
                        <label>Father's Name</label>
                        <span><?= e($intern['Father_name'] ?: '—'); ?></span>
                    </div>
                    <div class="id-info-item">
                        <label>University</label>
                        <span><?= e($intern['sInstitute'] ?: 'University Student'); ?></span>
                    </div>
                    <div class="id-info-item">
                        <label>Department</label>
                        <span><?= e($intern['Department'] ?: 'General Admin'); ?></span>
                    </div>
                    <div class="id-info-item">
                        <label>Tenure / Duration</label>
                        <span><?= e($intern['Hours'] ?: '6 Weeks'); ?></span>
                    </div>
                    <div class="id-info-item" style="grid-column: span 2;">
                        <label>Valid Period</label>
                        <span style="color:#047857;"><?= e($periodDisplay); ?></span>
                    </div>
                </div>

                <div class="id-barcode">*AWT-INT-<?= str_pad($intern['id'], 5, '0', STR_PAD_LEFT); ?>*</div>
            </div>

            <div class="id-footer-strip">
                <span>Pass ID: #<?= str_pad($intern['id'], 4, '0', STR_PAD_LEFT); ?></span>
                <span>Session: <?= e($intern['iyear'] ?: date('Y')); ?></span>
            </div>
        </div>

        <!-- ================= BACK SIDE ================= -->
        <div class="id-badge back-side">
            <div class="lanyard-hole"></div>

            <div class="id-header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="id-header-title">Alamgir Welfare Trust Int'l</div>
                <div class="id-header-sub" style="color:#94a3b8;">Terms & Emergency Verification</div>
            </div>

            <div class="back-instructions">
                <div>
                    <div class="instruction-item">
                        <i class="fas fa-check-circle"></i>
                        <div>This identification pass is the property of <strong>Alamgir Welfare Trust Int'l</strong> and must be worn at all times within organizational facilities.</div>
                    </div>
                    <div class="instruction-item">
                        <i class="fas fa-clock"></i>
                        <div>Valid exclusively for the authorized internship duration and non-transferable.</div>
                    </div>
                    <div class="instruction-item">
                        <i class="fas fa-undo"></i>
                        <div>If found, please deposit in the nearest post box or return to: <strong>AWT Head Office, Alamgir Road, Bahadurabad, Karachi</strong>.</div>
                    </div>
                </div>

                <!-- QR & Helpline -->
                <div class="qr-section">
                    <img src="<?= $qrCodeUrl; ?>" alt="QR Code" style="width:58px;height:58px;border-radius:6px;background:#fff;padding:2px;">
                    <div>
                        <div style="font-weight:700;font-size:10px;color:#0f172a;">Digital Authenticity QR</div>
                        <div style="font-size:8.5px;color:#64748b;">Scan with smartphone camera to verify internship status.</div>
                        <div style="font-size:9.5px;font-weight:700;color:#047857;margin-top:2px;">
                            <i class="fas fa-phone-alt me-1"></i>UAN: 111-928-928
                        </div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="signature-block">
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <span>Card Holder Signature</span>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <span style="font-weight:700;color:#0f172a;">HR Coordinator</span>
                    </div>
                </div>
            </div>

            <div class="id-footer-strip" style="background:#047857;">
                <span>www.alamgirwelfaretrust.com.pk</span>
                <span>Karachi, Pakistan</span>
            </div>
        </div>
    </div>
</div>

<?php if (current_user_role() === 'admin'): ?>
<!-- Fast Upload Modal inside ID card -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-camera me-2"></i>Upload Photo for ID Card</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="intern_photo_upload.php" method="POST" enctype="multipart/form-data">
                <?= csrf_input(); ?>
                <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">
                <div class="modal-body p-4 text-center">
                    <p class="text-muted small">Select an intern photograph to display directly on the ID Card and profile:</p>
                    <input type="file" name="photo_file" class="form-control mb-3" accept="image/*" required>
                    <div class="small text-muted text-start">
                        <i class="fas fa-info-circle text-primary me-1"></i> Recommended: Portrait passport size photo (JPG, PNG, WEBP).
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-upload me-1"></i> Upload & Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
