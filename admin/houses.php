<?php
// admin/houses.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Authentication Check
require_once ADMIN_PATH . '/includes/auth_check.php';

// 3. Page-specific variables
$page_title = "Gestion des Propriétés";
$admin_page_specific_js = "houses.js"; // Specific JS for this page
$include_leaflet_js = true; // Flag to include Leaflet JS in the footer

// 4. Include Admin Header
require_once TEMPLATES_PATH . '/admin_header.php';
?>

<!-- Page-specific header: Title and Action Buttons -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 page-header">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="addHouseBtn" data-bs-toggle="modal" data-bs-target="#houseModal">
            <i class="ph ph-plus me-1"></i>
            Nouvelle propriété
        </button>
    </div>
</div>

<!-- Houses Grid/List -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4" id="housesGridContainer">
    <!-- House cards will be populated by JS (admin/houses.js) -->
    <div class="col-12 text-center p-5" id="housesLoadingPlaceholder">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Chargement des propriétés...</p>
    </div>
</div>

<!-- Add/Edit House Modal (Bootstrap) -->
<div class="modal fade" id="houseModal" tabindex="-1" aria-labelledby="houseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"> <!-- modal-xl for more space -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Ajouter une propriété</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeHouseModal"></button>
            </div>
            <div class="modal-body">
                <form id="houseForm" class="needs-validation" novalidate enctype="multipart/form-data">
                    <input type="hidden" id="houseId" name="house_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Left Column: Main Details -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Titre de l'annonce <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">Le titre est requis.</div>
                            </div>
                            <div class="mb-3">
                                <label for="location" class="form-label">Adresse complète (écrite) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" required>
                                 <div class="invalid-feedback">L'adresse est requise.</div>
                            </div>

                             <!-- NEW: Map for exact location -->
                            <div class="mb-3">
                                <label class="form-label">Localisation exacte (Glissez le marqueur)</label>
                                <div id="map" style="height: 300px; border-radius: 0.375rem; border: 1px solid #dee2e6;"></div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                                <div class="form-text">Déplacez le marqueur pour définir les coordonnées précises de la propriété.</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                            </div>

                            <h6 class="mt-4 mb-3">Images</h6>
                            <div class="mb-3 border rounded p-3">
                                <label for="imageUploadInput" class="form-label">Télécharger des images (Max 5MB par image)</label>
                                <input type="file" class="form-control" id="imageUploadInput" name="images[]" multiple accept="image/jpeg, image/png, image/webp">
                                <div class="form-text">Sélectionnez une ou plusieurs images. La première image sélectionnée sera l'image principale par défaut.</div>
                                <div id="imagePreviewContainer" class="mt-3 d-flex flex-wrap gap-2">
                                    <!-- Image previews will be shown here -->
                                </div>
                                <div id="existingImagesContainer" class="mt-3 d-flex flex-wrap gap-2">
                                    <!-- Existing images for editing will be shown here -->
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Right Column: Pricing, Specs, Features -->
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Détails & Prix</h6>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Prix par nuit (TND) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="price" name="price_per_night" min="0" step="0.01" required>
                                        <div class="invalid-feedback">Le prix est requis.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="surface" class="form-label">Surface (m²)</label>
                                        <input type="number" class="form-control" id="surface" name="surface_area_sqm" min="0">
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-sm-6 mb-3">
                                            <label for="bedrooms" class="form-label">Chambres <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" value="1" required>
                                            <div class="invalid-feedback">Nombre de chambres requis.</div>
                                        </div>
                                        <div class="col-sm-6 mb-3">
                                            <label for="bathrooms" class="form-label">Salles de bain <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" step="0.5" value="1" required>
                                            <div class="invalid-feedback">Nombre de SDB requis.</div>
                                        </div>
                                    </div>
                                     <div class="mb-3">
                                        <label for="max_guests" class="form-label">Invités max.</label>
                                        <input type="number" class="form-control" id="max_guests" name="max_guests" min="1" value="2">
                                    </div>
                                    <div class="mb-3">
                                        <label for="availability_status" class="form-label">Statut</label>
                                        <select class="form-select" id="availability_status" name="availability_status">
                                            <option value="available" selected>Disponible</option>
                                            <option value="booked">Réservé</option>
                                            <option value="maintenance">En Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">Équipements</h6>
                                    <div class="features-grid-container row row-cols-2 g-2" style="max-height: 200px; overflow-y: auto;">
                                        <!-- Features checkboxes populated by JS -->
                                        <div class="col-12 text-muted small" id="featuresLoadingPlaceholder">Chargement des équipements...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancelHouseBtn">Annuler</button>
                <button type="submit" class="btn btn-primary" form="houseForm" id="saveHouseBtn">Enregistrer la propriété</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteHouseModal" tabindex="-1" aria-labelledby="deleteHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHouseModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette propriété ? <strong id="houseNameToDelete"></strong></p>
                <p class="text-danger small">Toutes les images et les liens d'équipement associés seront également supprimés. Les réservations existantes ne seront PAS supprimées mais pourraient devenir orphelines si la suppression n'est pas gérée avec RESTRICT.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteHouseBtn">Supprimer la propriété</button>
            </div>
        </div>
    </div>
</div>

<?php
// 5. Include Admin Footer
require_once TEMPLATES_PATH . '/admin_footer.php';
?>
