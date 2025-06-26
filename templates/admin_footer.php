<?php
// templates/admin_footer.php
// This should be included at the bottom of every admin page.
// Ensure config/init.php is included for ASSETS_PATH.
?>
            <footer class="pt-5 my-4 text-muted border-top">
                Dari Admin Panel &copy; <?php echo date("Y"); ?>
            </footer>
            </main> </div> </div> <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <?php if (isset($include_chartjs) && $include_chartjs): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <?php if (isset($include_flatpickr) && $include_flatpickr): ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script> <?php endif; ?>

    <?php if (isset($include_leaflet_js) && $include_leaflet_js): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php endif; ?>

    <?php if (isset($admin_page_specific_js)): ?>
        <script src="<?php echo ASSETS_PATH; ?>/js/admin/<?php echo htmlspecialchars($admin_page_specific_js); ?>"></script>
    <?php endif; ?>

</body>
</html>