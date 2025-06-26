<?php
// pages/house-detail.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Get House ID from URL
$house_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$house_id) {
    header('Location: ' . BASE_PATH);
    exit;
}

// 3. Page-specific variables
$page_title = "Détails de la Propriété";
$include_flatpickr = true;
$include_swiper = true;
$page_specific_js = "house-detail.js";

// 4. Include Header
require_once TEMPLATES_PATH . '/header.php';
?>
<style>
    .house-detail-container {
        background-color: #ffffff;
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
    }
    .house-title {
        font-weight: 700;
        color: var(--color-text);
    }
    .house-location {
        color: var(--color-text-light);
        font-size: 1rem;
    }
    .gallery-main img {
        border-radius: var(--radius-md);
    }
    .gallery-thumbnails img {
        border: 2px solid transparent;
        border-radius: var(--radius-sm);
        transition: border-color 0.2s;
    }
    .gallery-thumbnails img.active {
        border-color: var(--color-primary);
    }
    .stat-item {
        font-size: 0.9rem;
    }
    .feature-item-reworked {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
        font-size: 0.95rem; 
        color: var(--color-text);
        border-bottom: 1px solid #f3f4f6;
    }
    .feature-item-reworked .ph {
        font-size: 1.25rem;
        color: var(--color-primary);
    }
    #staticMapLink img {
        transition: opacity 0.2s ease-in-out;
    }
    #staticMapLink:hover img {
        opacity: 0.85;
    }
    .booking-card {
        position: sticky;
        top: 80px;
        background-color: #f8f9fa;
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        border: 1px solid #e9ecef;
    }
    /* Styles for Gallery Arrows */
    .gallery-container {
        position: relative;
    }
    .gallery-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(255, 255, 255, 0.8);
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #333;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: background-color 0.2s, color 0.2s;
        z-index: 10;
    }
    .gallery-arrow:hover {
        background-color: #fff;
        color: #000;
    }
    #prev-image {
        left: 1rem;
    }
    #next-image {
        right: 1rem;
    }
</style>

<div class="house-detail-container py-3">
    <div id="houseDetailLoading" class="text-center p-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="mt-3 fs-5 text-muted">Chargement...</p>
    </div>

    <div id="houseDetailContent" style="display: none;">
        <div class="house-header mb-3">
            <h1 class="house-title display-6" id="houseTitle"></h1>
            <p class="house-location" id="houseLocation">
                <i class="ph ph-map-pin me-1"></i>
                <span class="small"></span>
            </p>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <!-- MODIFICATION: Wrapped gallery in a container for positioning arrows -->
                <div class="gallery-container mb-4">
                    <div class="gallery-main mb-2">
                        <img src="" alt="Image principale" id="mainImage" class="img-fluid w-100" style="aspect-ratio: 16/10; object-fit: cover;">
                    </div>
                    <!-- Navigation Arrows -->
                    <button id="prev-image" class="gallery-arrow"><i class="ph ph-caret-left"></i></button>
                    <button id="next-image" class="gallery-arrow"><i class="ph ph-caret-right"></i></button>
                    
                    <div class="gallery-thumbnails d-flex gap-2 overflow-auto pb-2" id="thumbnails"></div>
                </div>
                
                <div class="house-map-section mb-4">
                    <h2 class="h5 fw-semibold mb-3">Emplacement</h2>
                    <a id="staticMapLink" href="#" target="_blank" rel="noopener noreferrer">
                        <img id="staticMapImage" src="" alt="Map location" class="img-fluid" style="border-radius: var(--radius-md); border: 1px solid #dee2e6;">
                    </a>
                </div>

                <hr class="my-4">

                <div class="house-description mb-4">
                    <h2 class="h5 fw-semibold mb-3">À propos de ce logement</h2>
                    <div class="house-stats d-flex justify-content-start gap-4 mb-3 py-2">
                        <div class="stat-item text-muted"><i class="ph ph-bed text-primary me-1"></i><span id="bedrooms"></span> chambres</div>
                        <div class="stat-item text-muted"><i class="ph ph-bathtub text-primary me-1"></i><span id="bathrooms"></span> SDB</div>
                        <div class="stat-item text-muted"><i class="ph ph-ruler text-primary me-1"></i><span id="surface"></span> m²</div>
                    </div>
                    <p class="text-muted" id="houseDescription" style="line-height: 1.7;"></p>
                </div>

                <hr class="my-4">

                <div class="house-features-section mb-4">
                    <h2 class="h5 fw-semibold mb-3">Équipements</h2>
                    <div class="row row-cols-1 row-cols-md-2" id="featuresGrid"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="booking-card">
                    <div class="booking-header mb-3">
                        <span class="fs-4 fw-bold text-primary" id="housePrice"></span>
                        <span class="text-muted">/ nuit</span>
                        <div class="small text-muted mt-1" id="minStayInfo" style="display: none;"></div>
                    </div>
                    <div class="booking-section">
                        <h2 class="h6 fw-semibold mb-3">Réservez votre séjour</h2>
                        <div id="bookingDatePickerContainer" class="mb-3"></div>
                        
                        <div id="minimum-nights-warning" class="alert alert-warning p-2 small" style="display: none;">
                            Cette propriété requiert un séjour minimum de <strong id="minimum-nights-value">X</strong> nuits.
                        </div>

                        <button class="btn btn-primary w-100 btn-lg" id="bookNowBtn" disabled>
                            <i class="ph ph-calendar-check me-1"></i>Vérifier la disponibilité
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="similar-houses mt-5 pt-4 border-top">
            <h2 class="h4 fw-semibold mb-3">Propriétés similaires</h2>
            <div class="swiper" id="similarHousesSwiper">
                <div class="swiper-wrapper" id="similarHousesWrapper"></div>
                <div class="swiper-pagination position-relative mt-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title h5 fw-semibold" id="bookingModalLabel">Confirmer votre réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeModal"></button>
            </div>
            <div class="modal-body">
                <div class="booking-summary bg-light-subtle p-3 rounded mb-3 border">
                    <div id="bookingSummaryContent"></div>
                </div>
                <form id="bookingForm" class="contact-form needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label small">Nom complet</label>
                        <input type="text" id="name" name="name" class="form-control form-control-sm" required>
                        <div class="invalid-feedback">Veuillez entrer votre nom complet.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label small">Email</label>
                        <input type="email" id="email" name="email" class="form-control form-control-sm" required>
                        <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label small">Numéro de téléphone</label>
                        <input type="tel" id="phone" name="phone" class="form-control form-control-sm" required pattern="[0-9]{8,}">
                        <div class="invalid-feedback">Veuillez entrer un numéro de téléphone valide.</div>
                    </div>
                    <input type="hidden" id="bookingHouseId" name="house_id">
                    <input type="hidden" id="bookingStartDate" name="start_date">
                    <input type="hidden" id="bookingEndDate" name="end_date">
                    <input type="hidden" id="bookingTotalPrice" name="total_price">
                    <button type="submit" class="btn btn-primary w-100 submitBookingBtn">
                        <i class="ph ph-paper-plane-tilt me-1"></i>Envoyer la demande
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="responseToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header"><i class="ph me-2 fs-5" id="toastIcon"></i><strong class="me-auto" id="toastTitle"></strong><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>
    <div class="toast-body" id="toastBody"></div>
  </div>
</div>

<?php
require_once TEMPLATES_PATH . '/footer.php';
?>
