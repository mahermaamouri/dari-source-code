<?php
// templates/header.php
// This file should be included at the top of your public-facing PHP pages.
// Ensure config/init.php is included before this template.

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Dari' : 'Dari - Location de Maisons Moderne'; ?></title>
    
    <link rel="icon" href="<?php echo ASSETS_PATH; ?>/images/logo.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css" />

    <?php if (isset($include_flatpickr) && $include_flatpickr): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <?php endif; ?>

    <?php if (isset($include_swiper) && $include_swiper): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <?php endif; ?>

    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/utilities.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/components.css">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>style.css">

    <?php if (isset($page_specific_css)): ?>
        <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/<?php echo htmlspecialchars($page_specific_css); ?>">
    <?php endif; ?>
    
    <script>
        window.basePath = '<?php echo BASE_PATH; ?>';
    </script>
</head>
<body class="bg-light">

    <nav class="top-nav fixed-top bg-white shadow-sm py-2">
        <div class="container d-flex justify-content-center">
            <a href="<?php echo BASE_PATH; ?>index.php" class="nav-brand text-decoration-none d-flex align-items-center">
                <i class="ph ph-house fs-2 text-primary"></i>
                <span class="ms-2 fs-5 fw-semibold" style="color: var(--color-primary);">Dari</span>
            </a>
        </div>
    </nav>

    <div class="search-overlay fixed-top vw-100 vh-100 p-3" id="searchOverlay" style="display: none; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); z-index: 1040; transform: translateY(100%); transition: transform 0.3s ease-in-out;">
        <div class="container h-100 d-flex flex-column justify-content-center" style="max-width: 600px;">
            <div class="search-wrapper position-relative mb-3">
                <i class="ph ph-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted fs-5"></i>
                <input type="text" class="form-control form-control-lg ps-5 search-input" placeholder="Rechercher des propriétés...">
                <button class="btn position-absolute top-50 end-0 translate-middle-y me-2 search-close" id="closeSearch" type="button">
                    <i class="ph ph-x fs-4 text-muted"></i>
                </button>
            </div>
            <div class="search-results bg-white rounded shadow-lg" id="searchResults" style="max-height: 70vh; overflow-y: auto;">
                </div>
        </div>
    </div>

    <main class="container mt-5 pt-4 pb-5">