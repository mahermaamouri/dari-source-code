<?php
// admin/analytics.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Authentication Check
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Statistiques Détaillées";
$admin_page_specific_js = "analytics.js"; // This will drive the page

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<!-- Stat Cards for Analytics -->
<div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-primary text-white rounded me-3"><i class="ph ph-eye fs-3"></i></div>
                <div>
                    <div id="statTotalViews" class="fs-4 fw-bold">--</div>
                    <div class="text-muted small">Vues de Propriétés (Total)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-info text-white rounded me-3"><i class="ph ph-cursor-click fs-3"></i></div>
                <div>
                    <div id="statTotalClicks" class="fs-4 fw-bold">--</div>
                    <div class="text-muted small">Clics sur Propriétés (Total)</div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Data Tables -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header"><h3 class="h5 mb-0">Propriétés les Plus Vues</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Propriété</th><th>Vues</th></tr></thead>
                        <tbody id="topViewedTable">
                            <!-- JS will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header"><h3 class="h5 mb-0">Propriétés les Plus Cliquées</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Propriété</th><th>Clics</th></tr></thead>
                        <tbody id="topClickedTable">
                             <!-- JS will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
         <div class="card shadow-sm">
            <div class="card-header"><h3 class="h5 mb-0">Recherches de Dates Populaires</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Date début</th><th>Date fin</th><th>Nombre de recherches</th></tr></thead>
                        <tbody id="topSearchesTable">
                             <!-- JS will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
