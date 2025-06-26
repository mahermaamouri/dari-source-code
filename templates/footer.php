<?php
// templates/footer.php
$current_page_for_footer = basename($_SERVER['PHP_SELF']);
?>
    </main> <nav class="bottom-nav fixed-bottom bg-white shadow-lg d-flex justify-content-around py-2 d-md-none">
        <a href="<?php echo BASE_PATH; ?>index.php" class="nav-item flex-fill text-center text-decoration-none <?php echo ($current_page_for_footer === 'index.php') ? 'active text-primary' : 'text-muted'; ?>">
            <div class="nav-item-content d-flex flex-column align-items-center">
                <i class="ph ph-house fs-4"></i>
                <span class="nav-text small">Accueil</span>
            </div>
        </a>
        <button class="nav-item flex-fill text-center text-decoration-none text-muted search-trigger btn border-0 bg-transparent" id="bottomNavSearchTrigger">
            <div class="nav-item-content d-flex flex-column align-items-center">
                <i class="ph ph-magnifying-glass fs-4"></i>
                <span class="nav-text small">Rechercher</span>
            </div>
        </button>
        <a href="<?php echo BASE_PATH; ?>pages/contact.php" class="nav-item flex-fill text-center text-decoration-none <?php echo ($current_page_for_footer === 'contact.php') ? 'active text-primary' : 'text-muted'; ?>">
            <div class="nav-item-content d-flex flex-column align-items-center">
                <i class="ph ph-chat-circle fs-4"></i>
                <span class="nav-text small">Contact</span>
            </div>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (isset($include_flatpickr) && $include_flatpickr): ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script> <?php endif; ?>

    <?php if (isset($include_swiper) && $include_swiper): ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <?php endif; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>

    <script type="module" src="<?php echo ASSETS_PATH; ?>/js/main.js"></script>

    <?php if (isset($page_specific_js)): ?>
        <script type="module" src="<?php echo ASSETS_PATH; ?>/js/<?php echo htmlspecialchars($page_specific_js); ?>"></script>
    <?php endif; ?>

</body>
</html>
