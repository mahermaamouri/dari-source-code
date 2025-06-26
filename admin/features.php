<?php
// admin/features.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Authentication Check
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Gestion des Équipements";
// $include_chartjs = false; // No charts on this page
$admin_page_specific_js = "features.js"; // Specific JS for this page

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header: Title and Action Buttons -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="addFeatureBtn" data-bs-toggle="modal" data-bs-target="#featureModal">
            <i class="ph ph-plus me-1"></i>
            Ajouter un équipement
        </button>
    </div>
</div>

<!-- Features Table -->
<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="h5 mb-0">Liste des Équipements</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom de l'équipement</th>
                        <th>Classe d'icône (Phosphor)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="featuresTableBody">
                    <!-- Rows will be populated by JS (admin/features.js) -->
                    <tr><td colspan="4" class="text-center p-5"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Chargement des équipements...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Feature Modal (Bootstrap) -->
<div class="modal fade" id="featureModal" tabindex="-1" aria-labelledby="featureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="featureModalLabel">Ajouter un équipement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="featureForm" class="needs-validation" novalidate>
                    <input type="hidden" id="featureId" name="feature_id">
                    <div class="mb-3">
                        <label for="featureName" class="form-label">Nom de l'équipement</label>
                        <input type="text" class="form-control" id="featureName" name="feature_name" required>
                        <div class="invalid-feedback">Le nom de l'équipement est requis.</div>
                    </div>
                    <div class="mb-3">
                        <label for="featureIconClass" class="form-label">Classe d'icône (ex: ph-wifi-high)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="iconPreview" class="ph ph-tag"></i></span>
                            <input type="text" class="form-control" id="featureIconClass" name="icon_class" placeholder="ph-nom-de-l-icone">
                        </div>
                        <div class="form-text">
                            Trouvez les classes sur <a href="https://phosphoricons.com/" target="_blank" rel="noopener noreferrer">Phosphor Icons</a>. Laissez vide si pas d'icône.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary" form="featureForm" id="saveFeatureBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Can be a generic modal if you create one) -->
<div class="modal fade" id="deleteFeatureModal" tabindex="-1" aria-labelledby="deleteFeatureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFeatureModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet équipement ? <strong id="featureNameToDelete"></strong></p>
                <p class="text-danger small">Cette action ne peut pas être annulée.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteFeatureBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>


<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
