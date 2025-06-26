<?php
// index.php (Public Homepage)

// 1. Initialize and Configuration
require_once __DIR__ . '/config/init.php';

// 2. Page-specific variables
$page_title = "Accueil";
$include_flatpickr = true; // This page uses Flatpickr for the date filter

// 3. Include Header
require_once TEMPLATES_PATH . '/header.php';
?>

<div class="position-relative">
    <div class="filters-bar d-flex align-items-center justify-content-between p-2 bg-white rounded shadow-sm sticky-top" style="top: 70px; z-index: 1000;">
        <button class="btn btn-filter-bar flex-fill" id="calendarBtn">
            <div class="d-flex flex-column align-items-center">
                <i class="ph ph-calendar fs-4"></i>
                <small>Calendrier</small>
            </div>
        </button>

        <div id="dateDisplay" class="text-center text-muted fw-bold flex-grow-1 mx-2 px-2">Sélectionnez vos dates</div>

        <button class="btn btn-filter-bar flex-fill" id="filtersBtn" data-bs-toggle="offcanvas" data-bs-target="#filtersOffcanvas" aria-controls="filtersOffcanvas">
            <div class="d-flex flex-column align-items-center">
                <i class="ph ph-sliders fs-4"></i>
                <small>Filtres</small>
            </div>
        </button>
    </div>

    <div id="calendarContainerWrapper" style="display: none;">
        <div class="position-relative" id="calendarContainer">
            </div>
    </div>
</div>


<div class="offcanvas offcanvas-end" tabindex="-1" id="filtersOffcanvas" aria-labelledby="filtersOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="filtersOffcanvasLabel"><i class="ph ph-funnel me-2"></i>Filtres de recherche</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="filterForm">
            <div class="filter-section mb-4">
                <h6 class="mb-3">Trier par</h6>
                <select class="form-select" name="sortBy" id="sortBy">
                    <option value="popularity">Popularité</option>
                    <option value="newest">Nouveautés</option>
                    <option value="price_asc">Prix: Croissant</option>
                    <option value="price_desc">Prix: Décroissant</option>
                </select>
            </div>

            <div class="filter-section mb-4">
                <h6 class="mb-3">Prix par nuit</h6>
                 <div id="priceSlider"></div>
                 <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span id="minPriceDisplay">50 TND</span>
                    <span id="maxPriceDisplay">1000 TND</span>
                </div>
            </div>

            <div class="filter-section mb-4">
                <h6 class="mb-3">Chambres</h6>
                <div class="row row-cols-4 g-2">
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bedrooms[]" value="1" id="beds_1"><label class="form-check-label" for="beds_1"><i class="ph ph-bed me-1"></i>1</label></div></div>
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bedrooms[]" value="2" id="beds_2"><label class="form-check-label" for="beds_2"><i class="ph ph-bed me-1"></i>2</label></div></div>
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bedrooms[]" value="3" id="beds_3"><label class="form-check-label" for="beds_3"><i class="ph ph-bed me-1"></i>3</label></div></div>
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bedrooms[]" value="4" id="beds_4"><label class="form-check-label" for="beds_4"><i class="ph ph-bed me-1"></i>4+</label></div></div>
                </div>
            </div>

            <div class="filter-section mb-4">
                <h6 class="mb-3">Salles de bain</h6>
                <div class="row row-cols-3 g-2">
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bathrooms[]" value="1" id="baths_1"><label class="form-check-label" for="baths_1"><i class="ph ph-bathtub me-1"></i>1</label></div></div>
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bathrooms[]" value="2" id="baths_2"><label class="form-check-label" for="baths_2"><i class="ph ph-bathtub me-1"></i>2</label></div></div>
                    <div class="col"><div class="form-check form-check-btn"><input class="form-check-input" type="checkbox" name="bathrooms[]" value="3" id="baths_3"><label class="form-check-label" for="baths_3"><i class="ph ph-bathtub me-1"></i>3+</label></div></div>
                </div>
            </div>
            
            <div class="filter-section mb-4">
                <h6 class="mb-3">Équipements</h6>
                <div class="row row-cols-3 g-2" id="amenitiesFilterContainer">
                    </div>
            </div>

            <div class="d-grid gap-2 sticky-bottom bg-white py-3 border-top">
                <button class="btn btn-primary" type="button" id="applyFiltersBtn" data-bs-dismiss="offcanvas">Appliquer les filtres</button>
                <button class="btn btn-outline-secondary" type="button" id="resetFiltersBtn">Réinitialiser</button>
            </div>
        </form>
    </div>
</div>


<section class="house-grid row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 mt-2" id="houseGrid">
    <div class="col-12 text-center p-5" id="houseGridLoading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Chargement des propriétés...</p>
    </div>
</section>

<?php
// 4. Include Footer
require_once TEMPLATES_PATH . '/footer.php';
?>
