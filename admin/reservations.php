<?php
// admin/reservations.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Authentication Check
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Gestion des Réservations";
$admin_page_specific_js = "reservations.js"; // Specific JS for this page

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header: Title and Action Buttons -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="addReservationBtn" data-bs-toggle="modal" data-bs-target="#reservationModal">
            <i class="ph ph-plus me-1"></i>
            Nouvelle réservation
        </button>
    </div>
</div>

<!-- Reservation Tabs -->
<ul class="nav nav-tabs mb-3" id="reservationsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active original-tab-button" id="pending-tab" data-bs-toggle="tab" data-bs-target="#tab-pane-content" type="button" role="tab" aria-controls="tab-pane-content" aria-selected="true" data-tab-filter="pending">En attente</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link original-tab-button" id="confirmed-tab" data-bs-toggle="tab" data-bs-target="#tab-pane-content" type="button" role="tab" aria-controls="tab-pane-content" aria-selected="false" data-tab-filter="confirmed">Confirmées</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link original-tab-button" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#tab-pane-content" type="button" role="tab" aria-controls="tab-pane-content" aria-selected="false" data-tab-filter="cancelled">Annulées</button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="reservationsTabContent">
    <div class="tab-pane fade show active" id="tab-pane-content" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Propriété</th>
                                <th>Dates</th>
                                <th>Prix</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reservationsTableBody">
                            <!-- Reservation rows will be populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Reservation Modal -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nouvelle réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeReservationModal"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm" novalidate>
                    <div class="mb-3">
                        <label for="clientName" class="form-label">Nom du client</label>
                        <input type="text" id="clientName" name="client_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientEmail" class="form-label">Email</label>
                        <input type="email" id="clientEmail" name="client_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientPhone" class="form-label">Téléphone</label>
                        <input type="tel" id="clientPhone" name="client_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="propertySelect" class="form-label">Propriété</label>
                        <select id="propertySelect" name="house_id" class="form-select" required></select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">Date d'arrivée</label>
                            <input type="date" id="startDate" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">Date de départ</label>
                            <input type="date" id="endDate" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="totalPrice" class="form-label">Prix total (TND)</label>
                            <input type="number" id="totalPrice" name="total_price" class="form-control" step="any" required>
                        </div>
                         <div class="col-md-6">
                            <label for="advancePayment" class="form-label">Acompte (TND)</label>
                            <input type="number" id="advancePayment" name="advance_payment" class="form-control" step="any" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">Statut</label>
                        <select id="statusSelect" name="status" class="form-select" required>
                            <option value="pending">En attente</option>
                            <option value="confirmed">Confirmée</option>
                            <option value="cancelled">Annulée</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancelReservationBtn">Annuler</button>
                <button type="submit" class="btn btn-primary" form="reservationForm">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirmer la réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="confirmation-summary p-3 bg-light-subtle border rounded mb-3">
                    <!-- Summary content will be populated by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancelConfirmation">Annuler</button>
                <button type="button" class="btn btn-success" id="confirmReservation">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Supprimer la réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancelDelete">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
