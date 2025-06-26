<?php
// admin/voucher.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Authentication Check
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Envoyer un Voucher de Réservation";
$admin_page_specific_js = "voucher.js";

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header: Title -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="voucherForm" novalidate>
                    <input type="hidden" id="reservationId" name="reservation_id">
                    
                    <h5 class="mb-3">Informations du Client</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="clientName" class="form-label">Nom du client</label>
                            <input type="text" id="clientName" name="client_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="clientEmail" class="form-label">Email</label>
                            <input type="email" id="clientEmail" name="client_email" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="clientPhone" class="form-label">Téléphone</label>
                            <input type="tel" id="clientPhone" name="client_phone" class="form-control" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Détails de la Réservation</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="propertySelect" class="form-label">Propriété</label>
                            <select id="propertySelect" name="house_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="startDate" class="form-label">Date d'arrivée</label>
                            <input type="date" id="startDate" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="endDate" class="form-label">Date de départ</label>
                            <input type="date" id="endDate" name="end_date" class="form-control" required>
                        </div>
                    </div>

                     <hr class="my-4">

                    <h5 class="mb-3">Détails Financiers</h5>
                     <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="pricePerNight" class="form-label">Prix / Nuit (TND)</label>
                            <input type="number" id="pricePerNight" name="price_per_night" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="totalPrice" class="form-label">Prix Total (TND)</label>
                            <input type="number" id="totalPrice" name="total_price" class="form-control" step="0.01" required readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="advancePayment" class="form-label">Acompte Payé (TND)</label>
                            <input type="number" id="advancePayment" name="advance_payment" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="paymentStatus" class="form-label">Statut du Paiement</label>
                            <select id="paymentStatus" name="payment_status" class="form-select" required>
                                <option value="Acompte Payé">Acompte Payé</option>
                                <option value="Payé en Totalité">Payé en Totalité</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="sendVoucherBtn">
                            <i class="ph ph-paper-plane-tilt me-2"></i>Envoyer le Voucher par Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- NEW: Vouchers History Table -->
<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h3 class="h5 mb-0">Historique des Vouchers Envoyés</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Propriété</th>
                        <th>Email</th>
                        <th>Date d'envoi</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody id="vouchersHistoryTableBody">
                    <!-- JS will populate this -->
                    <tr><td colspan="5" class="text-center p-4">Chargement de l'historique...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
