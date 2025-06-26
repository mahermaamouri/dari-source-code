<?php
// admin/availability.php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/includes/auth_check.php';

$page_title = "Gérer les Disponibilités";
$include_flatpickr = true; 
$admin_page_specific_js = 'availability.js';

// Fetch all houses for the dropdown
try {
    $stmt = $pdo->query("SELECT house_id, title FROM Houses ORDER BY title ASC");
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $houses = [];
    $error_message = "Erreur: Impossible de charger la liste des maisons.";
}

include_once __DIR__ . '/../templates/admin_header.php';
?>

<style>
    /* Ensure calendar takes full width of its container */
    #availability-calendar .flatpickr-calendar {
        width: 100% !important;
        box-shadow: none;
        border: none;
    }

    /* Day State Colors */
    .flatpickr-day.day-confirmed { background-color: rgba(25, 135, 84, 0.2); border-color: transparent; }
    .flatpickr-day.day-pending { background-color: rgba(255, 193, 7, 0.2); border-color: transparent; }
    .flatpickr-day.day-unavailable { background-color: rgba(220, 53, 69, 0.2); border-color: transparent; text-decoration: line-through; }

    /* Styling for the price tag on each day */
    .day-price {
        display: block;
        font-size: 0.7em;
        color: #6c757d;
        margin-top: -2px;
        font-weight: 500;
    }
    
    /* Ensure selected range is clearly visible over custom backgrounds */
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }
    .flatpickr-day.selected .day-price, .flatpickr-day.startRange .day-price, .flatpickr-day.endRange .day-price {
        color: #fff;
    }
    
    /* Smooth transition for the edit form appearing */
    #edit-form-container {
        transition: opacity 0.3s ease-in-out, max-height 0.3s ease-in-out;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
    }
    #edit-form-container.visible {
        max-height: 500px; /* Adjust as needed */
        opacity: 1;
    }
</style>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="ph-duotone ph-calendar-check me-2"></i><?php echo $page_title; ?></h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Contrôles</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="houseSelector" class="form-label fw-bold">1. Choisir une Propriété</label>
                        <select id="houseSelector" class="form-select form-select-lg">
                            <option value="">-- Choisir une maison --</option>
                            <?php foreach ($houses as $house): ?>
                                <option value="<?php echo $house['house_id']; ?>">
                                    <?php echo htmlspecialchars($house['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr>

                    <div id="edit-form-container">
                        <label class="form-label fw-bold">2. Modifier la Sélection</label>
                        <p class="text-muted small">Appliquez un prix ou un statut à la période sélectionnée.</p>
                        <form id="availabilityForm">
                            <input type="hidden" id="houseId" name="house_id">
                            <input type="hidden" id="startDate" name="start_date">
                            <input type="hidden" id="endDate" name="end_date">
                            
                            <p class="mb-2">Période: <strong id="selected-dates" class="text-primary"></strong></p>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Prix par Nuit (TND)</label>
                                <input type="number" class="form-control" id="price" name="price" placeholder="Laisser vide pour le prix par défaut" min="0" step="0.01">
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Statut de la Période</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="available">Disponible</option>
                                    <option value="unavailable">Indisponible (Fermé par l'admin)</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="saveAvailability">Enregistrer les Modifications</button>
                                <button type="button" class="btn btn-secondary" id="cancelEdit">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div id="calendar-container" class="card shadow-sm" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                   <h5 id="calendar-house-title" class="mb-0 me-3">Calendrier</h5>
                   <div class="legend d-flex align-items-center gap-3 flex-wrap">
                       <span class="legend-item"><span class="legend-color" style="background-color: rgba(25, 135, 84, 0.2);"></span> Confirmé</span>
                       <span class="legend-item"><span class="legend-color" style="background-color: rgba(255, 193, 7, 0.2);"></span> En attente</span>
                       <span class="legend-item"><span class="legend-color" style="background-color: rgba(220, 53, 69, 0.2);"></span> Fermé</span>
                   </div>
                </div>
                <div class="card-body">
                    <div class="text-center my-5" id="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="availability-calendar"></div>
                </div>
            </div>
            <div id="calendar-placeholder" class="text-center p-5 bg-light rounded">
                <i class="ph-duotone ph-calendar-blank" style="font-size: 4rem; color: #ced4da;"></i>
                <p class="mt-3 text-muted">Veuillez sélectionner une propriété pour afficher son calendrier de disponibilité.</p>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../templates/admin_footer.php'; ?>