<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * 10-Criteria Performance Appraisal & Grading System
 */

define('AWT_APP', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_admin();

$pageTitle = 'Performance Appraisal';
$pageSubtitle = 'Grade intern performance across the official 10 assessment criteria';

$selectedInternId = (int)($_GET['intern_id'] ?? 0);
$intern = null;

if ($selectedInternId > 0) {
    $intern = db_fetch_one("SELECT * FROM interns WHERE id = ?", [$selectedInternId]);
}

// Handle Evaluation Save (POST)
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
        $mentor = sanitize($_POST['mentor'] ?? '');

        if ($internId > 0) {
            db_query("
                UPDATE interns SET
                    punctuality = ?, regularity = ?, productivity = ?, relationship_with_others = ?,
                    Initiative = ?, Maturity = ?, Confidence = ?, Analytical_ability = ?,
                    abilityhardword = ?, knowledge = ?, comments = ?, Mentor = ?,
                    status = CASE WHEN confirmed = 1 THEN 'completed' ELSE status END
                WHERE id = ?
            ", [
                $punctuality, $regularity, $productivity, $relOthers,
                $initiative, $maturity, $confidence, $analytical,
                $hardwork, $knowledge, $comments, $mentor,
                $internId
            ]);

            $total = $punctuality + $regularity + $productivity + $relOthers + $initiative + $maturity + $confidence + $analytical + $hardwork + $knowledge;
            log_activity(current_user()['id'], 'Submitted Appraisal', "Rated intern #{$internId} (Total: {$total}/100)");
            set_flash('success', "Performance appraisal saved successfully. Total Score: {$total}/100.");
            header("Location: appraisal.php?intern_id=" . $internId);
            exit;
        }
    }
}

// Fetch list of interns for the selector
$internList = db_fetch_all("
    SELECT id, sname, sInstitute, iyear, punctuality, 
           (punctuality + regularity + productivity + relationship_with_others + Initiative + Maturity + Confidence + Analytical_ability + abilityhardword + knowledge) AS total_score
    FROM interns 
    WHERE confirmed = 1 OR status = 'confirmed' OR status = 'completed' OR status = 'active'
    ORDER BY id DESC 
    LIMIT 200
");

// Pre-fill score values
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

<div class="row g-4">
    <!-- Left column: Intern Selector -->
    <div class="col-lg-4">
        <div class="awt-card p-3">
            <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-users text-primary me-2"></i>Select Intern to Grade</h5>
            <input type="text" id="tableSearchInput" class="form-control form-control-sm mb-3" placeholder="Filter interns list...">

            <div class="list-group filterable-table" style="max-height: 520px; overflow-y: auto;">
                <?php foreach ($internList as $item): ?>
                    <a href="appraisal.php?intern_id=<?= $item['id']; ?>" 
                       class="list-group-item list-group-item-action <?= ($selectedInternId === (int)$item['id']) ? 'active' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><?= e($item['sname']); ?></span>
                            <?php if ($item['total_score'] > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $item['total_score']; ?>/100</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Ungraded</span>
                            <?php endif; ?>
                        </div>
                        <small class="<?= ($selectedInternId === (int)$item['id']) ? 'text-white-50' : 'text-muted'; ?> d-block">
                            #<?= $item['id']; ?> &bull; <?= e($item['sInstitute'] ?: 'AWT'); ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right column: 10-Criteria Evaluation Form -->
    <div class="col-lg-8">
        <?php if (!$intern): ?>
            <div class="awt-card text-center py-5">
                <i class="fas fa-clipboard-check display-4 text-secondary mb-3"></i>
                <h4 class="fw-bold text-dark">Select an Intern</h4>
                <p class="text-muted">Choose an intern from the list on the left to review or enter their 10-criteria performance appraisal.</p>
            </div>
        <?php else: ?>
            <form action="appraisal.php" method="POST" class="awt-card p-4">
                <?= csrf_input(); ?>
                <input type="hidden" name="intern_id" value="<?= $intern['id']; ?>">

                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4 flex-wrap gap-2">
                    <div>
                        <span class="badge bg-primary mb-1">Official Appraisal Form</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($intern['sname']); ?></h4>
                        <span class="text-muted small">ID: #<?= $intern['id']; ?> &bull; <?= e($intern['sInstitute']); ?> &bull; <?= e($intern['Degree']); ?></span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Cumulative Score</span>
                        <h3 class="fw-extrabold text-primary mb-0" id="evalTotalScoreDisplay"><?= $totalNow; ?> / 100</h3>
                        <span id="evalGradeDisplay" class="badge bg-<?= $gradeNow['color']; ?>"><?= $gradeNow['grade']; ?> (<?= $gradeNow['desc']; ?>)</span>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-sliders-h text-primary me-2"></i>Ten Evaluation Criteria (1 to 10 Scale)</h5>

                <div class="row g-3 mb-4">
                    <!-- Criteria 1: Punctuality -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>1. Punctuality (Timekeeping)</span>
                                <span class="rating-score"><span id="val_punctuality"><?= $scores['punctuality']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="punctuality" value="<?= $scores['punctuality']; ?>" oninput="document.getElementById('val_punctuality').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 2: Regularity -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>2. Regularity & Attendance</span>
                                <span class="rating-score"><span id="val_regularity"><?= $scores['regularity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="regularity" value="<?= $scores['regularity']; ?>" oninput="document.getElementById('val_regularity').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 3: Productivity -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>3. Productivity & Output</span>
                                <span class="rating-score"><span id="val_productivity"><?= $scores['productivity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="productivity" value="<?= $scores['productivity']; ?>" oninput="document.getElementById('val_productivity').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 4: Relationship with others -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>4. Teamwork & Interpersonal Skills</span>
                                <span class="rating-score"><span id="val_rel"><?= $scores['relationship_with_others']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="relationship_with_others" value="<?= $scores['relationship_with_others']; ?>" oninput="document.getElementById('val_rel').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 5: Initiative -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>5. Initiative & Self-Motivation</span>
                                <span class="rating-score"><span id="val_initiative"><?= $scores['initiative']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="initiative" value="<?= $scores['initiative']; ?>" oninput="document.getElementById('val_initiative').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 6: Maturity -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>6. Maturity & Composure</span>
                                <span class="rating-score"><span id="val_maturity"><?= $scores['maturity']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="maturity" value="<?= $scores['maturity']; ?>" oninput="document.getElementById('val_maturity').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 7: Confidence -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>7. Confidence & Public Interaction</span>
                                <span class="rating-score"><span id="val_confidence"><?= $scores['confidence']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="confidence" value="<?= $scores['confidence']; ?>" oninput="document.getElementById('val_confidence').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 8: Analytical Ability -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>8. Analytical & Problem Solving</span>
                                <span class="rating-score"><span id="val_analytical"><?= $scores['analytical_ability']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="analytical_ability" value="<?= $scores['analytical_ability']; ?>" oninput="document.getElementById('val_analytical').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 9: Hard Work -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>9. Ability to Work Hard / Dedication</span>
                                <span class="rating-score"><span id="val_hardwork"><?= $scores['abilityhardword']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="abilityhardword" value="<?= $scores['abilityhardword']; ?>" oninput="document.getElementById('val_hardwork').textContent = this.value">
                        </div>
                    </div>

                    <!-- Criteria 10: Knowledge -->
                    <div class="col-md-6">
                        <div class="rating-item">
                            <div class="rating-header">
                                <span>10. Job Knowledge & Comprehension</span>
                                <span class="rating-score"><span id="val_knowledge"><?= $scores['knowledge']; ?></span>/10</span>
                            </div>
                            <input type="range" class="form-range eval-score-input" min="1" max="10" name="knowledge" value="<?= $scores['knowledge']; ?>" oninput="document.getElementById('val_knowledge').textContent = this.value">
                        </div>
                    </div>
                </div>

                <!-- Evaluator Notes & Feedback -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Evaluator / Mentor Name</label>
                        <input type="text" name="mentor" class="form-control" value="<?= e($intern['Mentor'] ?: 'Muhammad Wali Saleem'); ?>" placeholder="Mentor name">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Performance Remarks, Suggestions & Strengths</label>
                        <textarea name="comments" class="form-control" rows="4" placeholder="Provide qualitative feedback on intern behavior, learning, and contributions..."><?= e($intern['comments']); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <a href="intern_view.php?id=<?= $intern['id']; ?>" class="btn btn-light"><i class="fas fa-arrow-left me-1"></i> View Profile</a>
                    <div class="d-flex gap-2">
                        <a href="certificate.php?id=<?= $intern['id']; ?>" class="btn btn-outline-warning fw-semibold">
                            <i class="fas fa-certificate me-1"></i> Preview Certificate
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Save Performance Appraisal
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
