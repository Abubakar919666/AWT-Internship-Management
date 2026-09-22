<?php
/**
 * AWT Intern Management System (AWT-IMS)
 * Shared Footer & Scripts
 */

$baseUrl = get_base_url();
?>
        </main> <!-- End app-content -->

        <!-- Bottom Footer -->
        <footer class="text-center py-3 border-top bg-white text-muted small mt-auto no-print">
            <div class="container-fluid">
                <span>&copy; <?= date('Y'); ?> <strong>Alamgir Welfare Trust Int'l</strong> — Intern Management System. All rights reserved.</span>
            </div>
        </footer>
    </div> <!-- End app-main -->
</div> <!-- End app-wrapper -->

<!-- Bootstrap 5.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js 4.4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Main Interactive JS -->
<script src="<?= $baseUrl; ?>assets/js/main.js"></script>

<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
