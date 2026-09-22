/**
 * AWT Intern Management System (AWT-IMS)
 * Main Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle for Mobile & Responsive
    const sidebar = document.querySelector('.app-sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay && sidebar) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // 2. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 3. Quick Table Live Filter
    const liveSearchInput = document.getElementById('tableSearchInput');
    if (liveSearchInput) {
        liveSearchInput.addEventListener('keyup', function () {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('.filterable-table tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(val) > -1 ? '' : 'none';
            });
        });
    }

    // 4. Batch Attendance Selector
    const batchRadioButtons = document.querySelectorAll('.batch-attendance-btn');
    if (batchRadioButtons.length > 0) {
        batchRadioButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const status = this.getAttribute('data-status'); // 'Present', 'Absent', 'Leave'
                const inputs = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
                inputs.forEach(function (radio) {
                    radio.checked = true;
                });
            });
        });
    }

    // 5. Delete Confirmation Prompts
    const deleteTriggers = document.querySelectorAll('.btn-confirm-delete');
    deleteTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this record? This action cannot be undone.';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 6. Dynamic Evaluation Score Summing
    const scoreInputs = document.querySelectorAll('.eval-score-input');
    const totalScoreDisplay = document.getElementById('evalTotalScoreDisplay');
    const gradeDisplay = document.getElementById('evalGradeDisplay');

    if (scoreInputs.length > 0 && totalScoreDisplay) {
        function calculateEvalTotal() {
            let total = 0;
            scoreInputs.forEach(function (inp) {
                const val = parseInt(inp.value, 10) || 0;
                total += val;
            });
            totalScoreDisplay.textContent = total + ' / 100';

            if (gradeDisplay) {
                let grade = 'F';
                let cls = 'badge bg-danger';
                if (total >= 90) { grade = 'A+ (Outstanding)'; cls = 'badge bg-success'; }
                else if (total >= 80) { grade = 'A (Excellent)'; cls = 'badge bg-success'; }
                else if (total >= 70) { grade = 'B (Very Good)'; cls = 'badge bg-primary'; }
                else if (total >= 60) { grade = 'C (Good)'; cls = 'badge bg-info text-dark'; }
                else if (total >= 50) { grade = 'D (Satisfactory)'; cls = 'badge bg-warning text-dark'; }
                gradeDisplay.textContent = grade;
                gradeDisplay.className = cls;
            }
        }

        scoreInputs.forEach(function (inp) {
            inp.addEventListener('input', calculateEvalTotal);
        });
        calculateEvalTotal();
    }
});
