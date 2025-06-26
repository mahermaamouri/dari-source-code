<?php
// admin/dashboard.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php'; // Path to init.php from admin folder

// 2. Authentication Check - Redirect to login if not authenticated
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Tableau de Bord";
$include_chartjs = true; // To tell admin_footer.php to include Chart.js
$admin_page_specific_js = "dashboard.js"; // Specific JS for this page

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header: Title and Action Buttons -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#houseModal" id="adminDashboardAddHouseBtn">
            <i class="ph ph-plus me-1"></i>
            Nouvelle propriété
        </button>
        <!-- Note: The #houseModal target implies the house add/edit modal HTML should be available on this page
             or loaded dynamically if this button is intended to work here.
             Alternatively, this button could link to admin/houses.php -->
    </div>
</div>

<!-- Stat Cards -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-primary text-white rounded me-3">
                    <i class="ph ph-house fs-3"></i>
                </div>
                <div>
                    <div id="statTotalProperties" class="fs-4 fw-bold">--</div>
                    <div class="text-muted small">Propriétés</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-info text-white rounded me-3">
                    <i class="ph ph-calendar-check fs-3"></i>
                </div>
                <div>
                    <div id="statTotalReservations" class="fs-4 fw-bold">--</div>
                    <div class="text-muted small">Réservations</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-warning text-dark rounded me-3">
                    <i class="ph ph-clock-countdown fs-3"></i>
                </div>
                <div>
                    <div id="statPendingReservations" class="fs-4 fw-bold">--</div>
                    <div class="text-muted small">En attente</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="p-3 bg-success text-white rounded me-3">
                    <i class="ph ph-currency-circle-dollar fs-3"></i>
                </div>
                <div>
                    <div id="statTotalRevenue" class="fs-4 fw-bold">-- TND</div>
                    <div class="text-muted small">Revenue (TND)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="h5 mb-0">Réservations par mois</h3>
            </div>
            <div class="card-body chart-container">
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="h5 mb-0">Propriétés les plus réservées</h3>
            </div>
            <div class="card-body chart-container">
                <canvas id="propertiesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Section -->
<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h5 mb-0">Réservations récentes</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Propriété</th>
                        <th>Dates</th>
                        <th>Statut</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="recentBookingsTableBody">
                    <!-- Rows will be populated by JS (admin/dashboard.js) -->
                    <tr><td colspan="6" class="text-center p-5"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Chargement des réservations...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
