// assets/js/house-detail.js - Complete Implementation

// --- Configuration ---
const API_URL_DETAIL = window.basePath + 'api';
const ASSETS_URL_DETAIL = window.basePath + 'assets';
// --- ACTION REQUIRED: Replace with your actual Google Maps API Key ---
const Maps_API_KEY = 'AIzaSyCW7SN149aJGlw8c8B7RmTGWwQXuR5r0Ws';

// --- DOM Elements ---
const houseDetailLoadingDiv = document.getElementById('houseDetailLoading');
const houseDetailContentDiv = document.getElementById('houseDetailContent');
const mainImageElement = document.getElementById('mainImage');
const thumbnailsContainer = document.getElementById('thumbnails');
const prevImageArrow = document.getElementById('prev-image');
const nextImageArrow = document.getElementById('next-image');
const houseTitleElement = document.getElementById('houseTitle');
const houseLocationSpan = document.querySelector('#houseLocation span');
const housePriceElement = document.getElementById('housePrice');
const bedroomsSpan = document.getElementById('bedrooms');
const bathroomsSpan = document.getElementById('bathrooms');
const surfaceSpan = document.getElementById('surface');
const houseDescriptionP = document.getElementById('houseDescription');
const featuresGridDiv = document.getElementById('featuresGrid');
const bookingDatePickerContainer = document.getElementById('bookingDatePickerContainer');
const bookNowBtn = document.getElementById('bookNowBtn');
const bookingModalElement = document.getElementById('bookingModal');
const bookingModalInstance = bookingModalElement ? new bootstrap.Modal(bookingModalElement) : null;
const bookingForm = document.getElementById('bookingForm');
const bookingSummaryContentDiv = document.getElementById('bookingSummaryContent');
const bookingHouseIdInput = document.getElementById('bookingHouseId');
const bookingStartDateInput = document.getElementById('bookingStartDate');
const bookingEndDateInput = document.getElementById('bookingEndDate');
const bookingTotalPriceInput = document.getElementById('bookingTotalPrice');
const responseToastElement = document.getElementById('responseToast');
const responseToastInstance = responseToastElement ? new bootstrap.Toast(responseToastElement, { delay: 5000 }) : null;
const toastIcon = document.getElementById('toastIcon');
const toastTitle = document.getElementById('toastTitle');
const toastBody = document.getElementById('toastBody');
const similarHousesWrapper = document.getElementById('similarHousesWrapper');
const staticMapLink = document.getElementById('staticMapLink');
const staticMapImage = document.getElementById('staticMapImage');
// NEW: Add selectors for min stay elements
const minStayInfoDiv = document.getElementById('minStayInfo');
const minNightsWarningDiv = document.getElementById('minimum-nights-warning');
const minNightsValueSpan = document.getElementById('minimum-nights-value');


// --- State ---
let currentHouseData = null;
let flatpickrBookingInstance = null;
let selectedBookingDates = [];
let currentImageIndex = 0;
let allImages = [];

// --- Helper Functions ---
function toLocalISOString(date) {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDate(date) {
    const d = new Date(date);
    const month = '' + (d.getMonth() + 1);
    const day = '' + d.getDate();
    const year = d.getFullYear();
    return [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-');
}

function showError(message) {
    if (houseDetailLoadingDiv) houseDetailLoadingDiv.style.display = 'none';
    if (houseDetailContentDiv) {
        houseDetailContentDiv.innerHTML = `<div class="alert alert-danger text-center">${message}</div>`;
        houseDetailContentDiv.style.display = 'block';
    }
}

function showToast(title, message, type = 'success') {
    if (!responseToastInstance || !toastIcon || !toastTitle || !toastBody) return;
    
    responseToastElement.classList.remove('bg-success', 'bg-danger', 'bg-warning');
    toastIcon.className = 'ph me-2 fs-5';
    
    toastTitle.textContent = title;
    toastBody.textContent = message;
    
    if (type === 'success') {
        responseToastElement.classList.add('bg-success', 'text-white');
        toastIcon.classList.add('ph-check-circle', 'text-white');
    } else if (type === 'error') {
        responseToastElement.classList.add('bg-danger', 'text-white');
        toastIcon.classList.add('ph-x-circle', 'text-white');
    } else if (type === 'warning') {
        responseToastElement.classList.add('bg-warning', 'text-dark');
        toastIcon.classList.add('ph-warning-circle', 'text-dark');
    }
    
    responseToastInstance.show();
}

// --- Data Fetching and Population ---
async function fetchHouseDetails(houseId) {
    if (!houseDetailLoadingDiv || !houseDetailContentDiv) return;
    
    houseDetailLoadingDiv.style.display = 'block';
    houseDetailContentDiv.style.display = 'none';

    try {
        const response = await fetch(`${API_URL_DETAIL}/houses.php?action=getById&id=${houseId}`);
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorData?.error || 'Failed to fetch details'}`);
        }
        
        currentHouseData = await response.json();
        
        if (currentHouseData.error) {
            throw new Error(currentHouseData.error);
        }
        
        populateHouseDetails(currentHouseData);
        setupGallery(currentHouseData.images_data);
        initBookingCalendar(currentHouseData.price_per_night, currentHouseData.confirmed_reservations);
        fetchSimilarHouses(currentHouseData.bedrooms, currentHouseData.house_id);

        houseDetailLoadingDiv.style.display = 'none';
        houseDetailContentDiv.style.display = 'block';

    } catch (error) {
        console.error("Could not fetch house details:", error);
        showError(`Error loading details: ${error.message}`);
    }
}

function populateHouseDetails(house) {
    if (!house) return;
    
    document.title = `${house.title || 'Propriété'} - Dari`;

    if (houseTitleElement) houseTitleElement.textContent = house.title || 'Titre non disponible';
    if (houseLocationSpan) houseLocationSpan.textContent = house.location || 'Lieu non disponible';
    if (housePriceElement) housePriceElement.textContent = `${house.price_per_night || 'N/A'} TND`;
    if (bedroomsSpan) bedroomsSpan.textContent = house.bedrooms || 'N/A';
    if (bathroomsSpan) bathroomsSpan.textContent = house.bathrooms || 'N/A';
    if (surfaceSpan) surfaceSpan.textContent = house.surface_area_sqm || 'N/A';
    if (houseDescriptionP) {
        houseDescriptionP.innerHTML = house.description ? house.description.replace(/\n/g, '<br>') : 'Aucune description disponible.';
    }

    // NEW: Populate minimum stay information
    if (house.min_stay_nights && house.min_stay_nights > 1) {
        if (minStayInfoDiv) {
            minStayInfoDiv.textContent = `Séjour de ${house.min_stay_nights} nuits minimum`;
            minStayInfoDiv.style.display = 'block';
        }
    } else {
        if (minStayInfoDiv) minStayInfoDiv.style.display = 'none';
    }
    
    setupStaticMap(house);
    populateFeatures(house);
    
    if (bookingHouseIdInput) bookingHouseIdInput.value = house.house_id;
    if (bookingTotalPriceInput) bookingTotalPriceInput.value = house.price_per_night;
}

function setupStaticMap(house) {
    if (staticMapLink && staticMapImage && house.latitude && house.longitude) {
        const lat = parseFloat(house.latitude);
        const lon = parseFloat(house.longitude);
        
        const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;
        staticMapLink.href = googleMapsUrl;
        
        if (Maps_API_KEY && Maps_API_KEY !== 'YOUR_API_KEY_HERE' && Maps_API_KEY !== 'YOUR_Maps_API_KEY') {
            const staticMapUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${lat},${lon}&zoom=15&size=600x300&maptype=roadmap&markers=color:red%7C${lat},${lon}&key=${Maps_API_KEY}`;
            staticMapImage.src = staticMapUrl;
        } else {
            console.error("Google Maps API Key is missing or placeholder.");
            staticMapImage.alt = "Google Maps API Key is required.";
            staticMapImage.style.display = 'none';
        }
    } else if (staticMapLink) {
        staticMapLink.style.display = 'none';
    }
}

function populateFeatures(house) {
    if (!featuresGridDiv) return;
    featuresGridDiv.innerHTML = '';
    
    const featuresString = house.features_list || house.features;
    if (featuresString) {
        const features = featuresString.split(';;');
        features.forEach(f => {
            const [name, icon] = f.split('::');
            if (name) {
                const featureDiv = document.createElement('div');
                featureDiv.className = 'feature-item-reworked';
                featureDiv.innerHTML = `<i class="ph ${icon || 'ph-tag'}"></i><span>${name}</span>`;
                featuresGridDiv.appendChild(featureDiv);
            }
        });
    }
    
    if (featuresGridDiv.innerHTML === '') {
        featuresGridDiv.innerHTML = '<div class="col-12"><p class="text-muted small">Aucun équipement spécifique listé.</p></div>';
    }
}

function setupGallery(images) {
    if (!images || images.length === 0) {
        if (mainImageElement) {
            mainImageElement.src = `${ASSETS_URL_DETAIL}/images/placeholder-house.png`;
        }
        if (thumbnailsContainer) thumbnailsContainer.style.display = 'none';
        return;
    }
    
    allImages = images;
    currentImageIndex = 0;
    
    if (thumbnailsContainer) {
        thumbnailsContainer.innerHTML = '';
        images.forEach((image, index) => {
            const thumbImg = document.createElement('img');
            const imageUrl = image.image_url || image.url;
            thumbImg.src = imageUrl;
            thumbImg.alt = image.alt_text || `Thumbnail ${index + 1}`;
            thumbImg.className = `img-thumbnail p-0 ${index === 0 ? 'active' : ''}`;
            thumbImg.style.width = '80px';
            thumbImg.style.height = '60px';
            thumbImg.style.objectFit = 'cover';
            thumbImg.style.cursor = 'pointer';
            thumbImg.addEventListener('click', () => updateImage(index));
            thumbnailsContainer.appendChild(thumbImg);
        });
    }
    
    if (nextImageArrow) nextImageArrow.addEventListener('click', () => updateImage((currentImageIndex + 1) % images.length));
    if (prevImageArrow) prevImageArrow.addEventListener('click', () => updateImage((currentImageIndex - 1 + images.length) % images.length));
    
    updateImage(0);
}

function updateImage(index) {
    if (index < 0 || index >= allImages.length || !mainImageElement) return;
    
    const image = allImages[index];
    const imageUrl = image.image_url || image.url;
    mainImageElement.src = imageUrl;
    currentImageIndex = index;

    if (thumbnailsContainer) {
        const allThumbnails = thumbnailsContainer.querySelectorAll('img');
        allThumbnails.forEach((thumb, i) => thumb.classList.toggle('active', i === index));
    }
}

// --- Booking Functionality ---
function initBookingCalendar(pricePerNight, confirmedReservations = []) {
    if (!bookingDatePickerContainer || !bookNowBtn) return;
    
    if (flatpickrBookingInstance) flatpickrBookingInstance.destroy();
    
    let visibleInput = document.getElementById('booking-calendar-input');
    if (!visibleInput) {
        bookingDatePickerContainer.innerHTML = '<input type="text" id="booking-calendar-input" class="form-control" placeholder="Sélectionnez vos dates...">';
        visibleInput = document.getElementById('booking-calendar-input');
    }
    
    const reservedDates = confirmedReservations.map(res => ({ from: res.start_date, to: res.end_date }));

    flatpickrBookingInstance = flatpickr(visibleInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j M Y",
        minDate: "today",
        locale: "fr",
        disable: reservedDates,
        onChange: function(selectedDates) {
            selectedBookingDates = selectedDates;
            const [start, end] = selectedDates;

            // Hide warning by default on each change
            if (minNightsWarningDiv) minNightsWarningDiv.style.display = 'none';

            if (start && end) {
                const nights = Math.max(0, Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)));
                const minNights = currentHouseData.min_stay_nights || 1;

                if (nights < minNights) {
                    bookNowBtn.disabled = true;
                    bookNowBtn.innerHTML = `Séjour de ${minNights} nuits minimum`;
                    if (minNightsWarningDiv && minNightsValueSpan) {
                        minNightsValueSpan.textContent = minNights;
                        minNightsWarningDiv.style.display = 'block';
                    }
                } else if (nights > 0 && pricePerNight) {
                    const totalPrice = nights * pricePerNight;
                    bookNowBtn.innerHTML = `Réserver pour ${totalPrice.toFixed(0)} TND`;
                    bookNowBtn.disabled = false;
                    
                    if (bookingStartDateInput) bookingStartDateInput.value = formatDate(start);
                    if (bookingEndDateInput) bookingEndDateInput.value = formatDate(end);
                } else {
                    bookNowBtn.textContent = 'Dates invalides';
                    bookNowBtn.disabled = true;
                }
            } else {
                bookNowBtn.innerHTML = '<i class="ph ph-calendar-check me-1"></i>Vérifier la disponibilité';
                bookNowBtn.disabled = true;
            }
        }
    });
}

function handleBookNowClick() {
    if (selectedBookingDates.length === 2 && currentHouseData && bookingModalInstance) {
        const startDate = selectedBookingDates[0];
        const endDate = selectedBookingDates[1];
        const nights = Math.max(0, Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)));
        const minNights = currentHouseData.min_stay_nights || 1;

        if (nights < minNights) {
            showToast("Séjour minimum", `Cette propriété requiert un séjour de ${minNights} nuits minimum.`, "warning");
            return;
        }

        if (nights <= 0) {
            showToast("Erreur de dates", "La date de départ doit être après la date d'arrivée.", "error");
            return;
        }
        
        const totalPrice = nights * currentHouseData.price_per_night;

        if (bookingSummaryContentDiv) {
            bookingSummaryContentDiv.innerHTML = `
                <div class="d-flex justify-content-between py-1"><small class="text-muted">Propriété:</small> <strong>${currentHouseData.title}</strong></div>
                <div class="d-flex justify-content-between py-1"><small class="text-muted">Dates:</small> <strong>${startDate.toLocaleDateString('fr-FR', {day:'numeric', month:'short', year:'numeric'})} - ${endDate.toLocaleDateString('fr-FR', {day:'numeric', month:'short', year:'numeric'})}</strong></div>
                <div class="d-flex justify-content-between py-1"><small class="text-muted">Durée:</small> <strong>${nights} nuits</strong></div>
                <div class="d-flex justify-content-between py-1 border-top mt-1 pt-1"><small class="text-muted">Prix total:</small> <strong class="text-primary fs-5">${totalPrice.toFixed(0)} TND</strong></div>
            `;
        }

        if (bookingHouseIdInput) bookingHouseIdInput.value = currentHouseData.house_id;
        if (bookingStartDateInput) bookingStartDateInput.value = toLocalISOString(startDate);
        if (bookingEndDateInput) bookingEndDateInput.value = toLocalISOString(endDate);
        if (bookingTotalPriceInput) bookingTotalPriceInput.value = totalPrice;

        bookingModalInstance.show();
    } else {
        showToast("Sélectionnez les dates", "Veuillez d'abord sélectionner vos dates de séjour.", "warning");
    }
}

async function handleBookingFormSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!bookingForm.checkValidity()) {
        bookingForm.classList.add('was-validated');
        return;
    }
    
    bookingForm.classList.add('was-validated');
    const submitButton = bookingForm.querySelector('.submitBookingBtn');
    const originalButtonText = submitButton.innerHTML;
    
    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...`;
    
    const formData = new FormData(bookingForm);

    try {
        const response = await fetch(`${API_URL_DETAIL}/reservations.php`, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showToast("Demande envoyée!", result.message, "success");
            
            if (bookingModalInstance) {
                setTimeout(() => {
                    bookingModalInstance.hide();
                    bookingForm.reset();
                    bookingForm.classList.remove('was-validated');
                    
                    if (flatpickrBookingInstance) flatpickrBookingInstance.clear();
                    if (bookNowBtn) {
                        bookNowBtn.innerHTML = '<i class="ph ph-calendar-check me-1"></i>Vérifier la disponibilité';
                        bookNowBtn.disabled = true;
                    }
                    
                    location.reload();
                }, 2000);
            }
        } else {
            showToast("Erreur", result.message || "Une erreur s'est produite.", "error");
        }
    } catch (error) {
        console.error('Booking submission error:', error);
        showToast("Erreur réseau", "Impossible d'envoyer la demande.", "error");
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

async function fetchSimilarHouses(bedrooms, excludeId) {
    if (!similarHousesWrapper) return;
    
    similarHousesWrapper.innerHTML = '<div class="swiper-slide text-center p-5"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

    try {
        const apiUrl = `${API_URL_DETAIL}/houses.php?action=getAll&bedrooms_similar=${bedrooms}&exclude_id=${excludeId}&limit=6`;
        const response = await fetch(apiUrl);
        
        if (!response.ok) throw new Error('Failed to fetch similar houses');
        
        const similarHouses = await response.json();
        const similarHousesContainer = document.querySelector('.similar-houses');

        if (similarHouses.length > 0) {
            similarHousesWrapper.innerHTML = '';
            if (similarHousesContainer) similarHousesContainer.style.display = 'block';

            similarHouses.forEach(house => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide h-auto';
                const imagePath = house.image || `${ASSETS_URL_DETAIL}/images/placeholder-house.png`;
                
                slide.innerHTML = `
                    <div class="card h-100 shadow-sm overflow-hidden">
                        <a href="house-detail.php?id=${house.id}" class="text-decoration-none text-dark">
                            <div class="card-img-top-container" style="height: 180px; overflow: hidden;">
                                <img src="${imagePath}" onerror="this.onerror=null;this.src='${ASSETS_URL_DETAIL}/images/placeholder-house.png';" alt="${house.title}" class="img-fluid w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h5 class="card-title h6 small mb-1 text-truncate">${house.title}</h5>
                                <p class="card-text text-muted very-small mb-2 text-truncate"><i class="ph ph-map-pin me-1"></i>${house.location}</p>
                                <p class="card-text fw-bold text-primary small mt-auto mb-0">${house.price_per_night || house.price} TND/jour</p>
                            </div>
                        </a>
                    </div>`;
                similarHousesWrapper.appendChild(slide);
            });

            new Swiper('#similarHousesSwiper', {
                slidesPerView: 1.5, 
                spaceBetween: 15,
                pagination: { el: '.swiper-pagination', clickable: true },
                breakpoints: {
                    576: { slidesPerView: 2, spaceBetween: 20 },
                    992: { slidesPerView: 3, spaceBetween: 20 },
                },
                grabCursor: true,
            });

        } else {
            if (similarHousesContainer) similarHousesContainer.style.display = 'none';
        }
    } catch (error) {
        console.error("Could not fetch similar houses:", error);
        if (similarHousesWrapper) similarHousesWrapper.innerHTML = '';
        const similarHousesContainer = document.querySelector('.similar-houses');
        if (similarHousesContainer) similarHousesContainer.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const houseId = params.get('id');

    if (!houseId) {
        showError('Could not load property details: Invalid ID.');
        return;
    }

    fetchHouseDetails(houseId);
    
    if (bookNowBtn) bookNowBtn.addEventListener('click', handleBookNowClick);
    if (bookingForm) bookingForm.addEventListener('submit', handleBookingFormSubmit);
});