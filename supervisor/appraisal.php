<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Supervisor - Performance Appraisal Rating
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_supervisor();

$pageTitle = 'Intern Performance Evaluation';
$pageSubtitle = 'Record 10-criteria performance grading and mentorship comments';

$user = current_user();
$supContext = get_active_supervisor_context();
$activeSup = $supContext['supervisor'];
$isAll = $supContext['is_all'];

$selectedInternId = (int)($_GET['intern_id'] ?? 0);
$intern = null;

if ($selectedInternId > 0) {
    $intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$selectedInternId]);
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (validate_csrf_token($csrf)) {
        $internId = (int)($_POST['intern_id'] ?? 0);
        $punctuality = min(10, max(0, (int)($_POST['punctuality'] ?? 0)));
        $regularity = min(10, max(0, (int)($_POST['regularity'] ?? 0)));
        $productivity = min(10, max(0, (int)($_POST['productivity'] ?? 0)));
        $relOthers = min(10, max(0, (int)($_POST['relationship_with_others'] ?? 0)));
        $initiative = min(10, max(0, (int)($_POST['initiative'] ?? 0)));
        $maturity = min(10, max(0, (int)($_POST['maturity'] ?? 0)));
        $confidence = min(10, max(0, (int)($_POST['confidence'] ?? 0)));
        $analytical = min(10, max(0, (int)($_POST['analytical_ability'] ?? 0)));
        $hardwork = min(10, max(0, (int)($_POST['abilityhardword'] ?? 0)));
        $knowledge = min(10, max(0, (int)($_POST['knowledge'] ?? 0)));
        $comments = sanitize($_POST['comments'] ?? '');

        if ($internId > 0) {
            $mentorName = $isAll ? $user['name'] : $activeSup['name'];
            db_query("
                UPDATE interns SET
                    punctuality = ?, regularity = ?, productivity = ?, relationship_with_others = ?,
                    Initiative = ?, Maturity = ?, Confidence = ?, Analytical_ability = ?,
                    abilityhardword = ?, knowledge = ?, comments = ?, Mentor = ?
                WHERE id = ?
            ", [
                $punctuality, $regularity, $productivity, $relOthers,
                $initiative, $maturity, $confidence, $analytical,
                $hardwork, $knowledge, $comments, $mentorName,
                $internId
            ]);

            $total = $punctuality + $regularity + $productivity + $relOthers + $initiative + $maturity + $confidence + $analytical + $hardwork + $knowledge;
            set_flash('success', "Evaluation saved successfully. Total Score: {$total}/100.");
            header("Location: appraisal.php?intern_id=" . $internId);
            exit;
        }
    }
}

// Fetch supervisor's interns or all
if ($isAll) {
    $internList = db_fetch_all("
        SELECT id, sname, sInstitute, 
               (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
        FROM interns 
        ORDER BY sname ASC
    ");
} else {
    $internList = db_fetch_all("
        SELECT id, sname, sInstitute, 
               (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
        FROM interns 
        WHERE supervisor_id = ? OR Mentor LIKE ?
        ORDER BY sname ASC
    ", [$activeSup['id'], "%{$activeSup['name']}%"]);
}

$scores = [
    'punctuality'               => $intern ? (int)$intern['punctuality'] : 8,
    'regularity'                => $intern ? (int)$intern['regularity'] : 8,
    'productivity'              => $intern ? (int)$intern['productivity'] : 8,
    'relationship_with_others'  => $intern ? (int)$intern['relationship_with_others'] : 8,
    'initiative'                => $intern ? (int)$intern['Initiative'] : 8,
    'maturity'                  => $intern ? (int)$intern['Maturity'] : 8,
    'confidence'                => $intern ? (int)$intern['Confidence'] : 8,
    'analytical_ability'        => $intern ? (int)$intern['Analytical_ability'] : 8,
    'abilityhardword'           => $intern ? (int)$intern['abilityhardword'] : 8,
    'knowledge'                 => $intern ? (int)$intern['knowledge'] : 8
];
$totalNow = array_sum($scores);
$gradeNow = calculate_grade($totalNow);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Supervisor Switcher Ribbon -->
<?php render_supervisor_switcher_ribbon($supContext); ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="awt-card p-3">
            <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-users text-primary me-2"></i>Select Intern to Grade</h5>
            <div class="list-group" style="max-height: 520px; overflow-y: auto;">
                <?php if (empty($internList)): ?>
                    <div class="p-3 text-muted small text-center">No assigned interns found.</div>
                <?php else: ?>
                    <?php foreach ($internList as $item): ?>
                        <a href="appraisal.php?intern_id=<?= $item['id']; ?>" 
                           class="list-group-item list-group-item-action <?= ($selectedInternId === (int)$item['id']) ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><?= e($item['sname']); ?></span>
                                <span class="badge <?= $item['total_score'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?= $item['total_score'] > 0 ? $item['total_score'] . '/100' : 'Ungraded'; ?>
                                </span>
                            </div>
                            <small class="<?= ($selectedInternId === (int)$item['id']) ? 'text-white-50' : 'text-muted'; ?> d-block">
                                #<?= $item['id']; ?> &bull; <?= e($item['sInstitute'] ?: '—'); ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (!$intern): ?>
            <div class="awt-card text-center py-5">
                <i class="fas fa-clipboard-check display-4 text-secondary mb-3"></i>
                <h4 class="fw-bold text-dark">Select an Intern</h4>
                <p class="text-muted">Choose an intern from the list on the left to enter their performance appraisal.</p>
            </div>
        <?php else: ?>
            <form action="appraisal.php" method="POST" class="awt-card p-4">
                <?= csrf_input(); ?>
                <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">

                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4 flex-wrap gap-2">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($intern['sname']); ?></h4>
                        <span class="text-muted small">ID: #<?= $intern['id']; ?> &bull; <?= e($intern['sInstitute']); ?></span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Total Score</span>
                        <h3 class="fw-extrabold text-primary mb-0" id="evalTotalScoreDisplay"><?= $totalNow; ?> / 100</h3>
                        <span id="evalGradeDisplay" class="badge bg-<?= $gradeNow['color']; ?>"><?= $gradeNow['grade']; ?> (<?= $gradeNow['desc']; ?>)</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>1. Punctuality</span>
                                <span class="rating-score"><span id="val_punc"><?= $scores['punctuality']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="punctuality" value="<?= $scores['punctuality']; ?>" oninput="document.getElementById('val_punc').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>2. Regularity</span>
                                <span class="rating-score"><span id="val_reg"><?= $scores['regularity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="regularity" value="<?= $scores['regularity']; ?>" oninput="document.getElementById('val_reg').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>3. Productivity</span>
                                <span class="rating-score"><span id="val_prod"><?= $scores['productivity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="productivity" value="<?= $scores['productivity']; ?>" oninput="document.getElementById('val_prod').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>4. Teamwork & Interpersonal</span>
                                <span class="rating-score"><span id="val_rel"><?= $scores['relationship_with_others']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="relationship_with_others" value="<?= $scores['relationship_with_others']; ?>" oninput="document.getElementById('val_rel').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>5. Initiative</span>
                                <span class="rating-score"><span id="val_init"><?= $scores['initiative']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="initiative" value="<?= $scores['initiative']; ?>" oninput="document.getElementById('val_init').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>6. Maturity</span>
                                <span class="rating-score"><span id="val_mat"><?= $scores['maturity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="maturity" value="<?= $scores['maturity']; ?>" oninput="document.getElementById('val_mat').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>7. Confidence</span>
                                <span class="rating-score"><span id="val_conf"><?= $scores['confidence']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="confidence" value="<?= $scores['confidence']; ?>" oninput="document.getElementById('val_conf').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>8. Analytical Ability</span>
                                <span class="rating-score"><span id="val_ana"><?= $scores['analytical_ability']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="analytical_ability" value="<?= $scores['analytical_ability']; ?>" oninput="document.getElementById('val_ana').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>9. Ability to Work Hard</span>
                                <span class="rating-score"><span id="val_hard"><?= $scores['abilityhardword']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="abilityhardword" value="<?= $scores['abilityhardword']; ?>" oninput="document.getElementById('val_hard').textContent = this.value">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>10. Job Knowledge</span>
                                <span class="rating-score"><span id="val_know"><?= $scores['knowledge']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="knowledge" value="<?= $scores['knowledge']; ?>" oninput="document.getElementById('val_know').textContent = this.value">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Supervisor Feedback & Observations</label>
                    <textarea name="comments" class="form-control" rows="4" placeholder="Feedback on student demeanor, strengths, and recommendations..."><?= e($intern['comments']); ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> Save Evaluation
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
