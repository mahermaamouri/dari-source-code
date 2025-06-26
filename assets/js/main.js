// assets/js/main.js

// --- Configuration from window object ---
const API_URL = window.basePath + 'api';
const ASSETS_URL = window.basePath + 'assets';
const ANALYTICS_API_URL = window.basePath + 'api/analytics_tracker.php';

// --- DOM Elements ---
const houseGrid = document.getElementById('houseGrid');
const houseGridLoading = document.getElementById('houseGridLoading');
const calendarBtn = document.getElementById('calendarBtn');
const dateDisplay = document.getElementById('dateDisplay');
const filtersOffcanvasElement = document.getElementById('filtersOffcanvas');
const searchTriggerButtons = document.querySelectorAll('.search-trigger, #bottomNavSearchTrigger');
const searchOverlay = document.getElementById('searchOverlay');
const searchInput = document.querySelector('.search-input');
const searchResultsContainer = document.getElementById('searchResults');
const closeSearchBtn = document.getElementById('closeSearch');
const amenitiesFilterContainer = document.getElementById('amenitiesFilterContainer');
const applyFiltersBtn = document.getElementById('applyFiltersBtn');
const resetFiltersBtn = document.getElementById('resetFiltersBtn');
const filterForm = document.getElementById('filterForm');
const priceSliderElement = document.getElementById('priceSlider');
const minPriceDisplay = document.getElementById('minPriceDisplay');
const maxPriceDisplay = document.getElementById('maxPriceDisplay');

// --- State ---
let allHouses = []; // This will now be populated on all pages for search
let flatpickrInstanceDateRange = null;
let priceSliderInstance = null;
let currentFilters = {
    bedrooms: [],
    bathrooms: [],
    amenities: [],
    startDate: null,
    endDate: null,
    sortBy: 'popularity',
    maxPrice: 1000,
    minPrice: 50
};
let userSessionId = null;
let viewTrackerObserver = null;

// --- Analytics Tracking ---
function getOrCreateSessionId() {
    let sessionId = sessionStorage.getItem('dariUserSessionId');
    if (!sessionId) {
        sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        sessionStorage.setItem('dariUserSessionId', sessionId);
    }
    return sessionId;
}

async function trackEvent(eventType, payload) {
    if (!userSessionId) {
        console.error("Session ID not set, cannot track event.");
        return;
    }
    try {
        await fetch(ANALYTICS_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ eventType, payload, sessionId: userSessionId }),
        });
    } catch (error) {
        console.error(`Failed to track event ${eventType}:`, error);
    }
}

function setupHouseViewTracking() {
    if (viewTrackerObserver) {
        viewTrackerObserver.disconnect();
    }

    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.5
    };

    viewTrackerObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const houseId = entry.target.dataset.houseId;
                if (houseId) {
                    trackEvent('house_view', { houseId });
                    observer.unobserve(entry.target);
                }
            }
        });
    }, options);

    document.querySelectorAll('.house-card-trackable').forEach(card => {
        viewTrackerObserver.observe(card);
    });
}

// --- Initialization Functions ---
function initDateRangePicker() {
    const calendarContainerWrapper = document.getElementById('calendarContainerWrapper');
    const calendarContainer = document.getElementById('calendarContainer');

    if (calendarBtn && dateDisplay && calendarContainer && calendarContainerWrapper) {
        calendarBtn.addEventListener('click', () => {
            const isVisible = calendarContainerWrapper.style.display === 'block';
            calendarContainerWrapper.style.display = isVisible ? 'none' : 'block';
        });

        flatpickrInstanceDateRange = flatpickr(calendarContainer, {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: new Date().fp_incr(365),
            locale: "fr",
            inline: true,
            showMonths: window.innerWidth < 768 ? 1 : 2,
            onClose: function(selectedDates) {
                if (selectedDates.length === 2) {
                    currentFilters.startDate = selectedDates[0].toISOString().split('T')[0];
                    currentFilters.endDate = selectedDates[1].toISOString().split('T')[0];
                    const start = selectedDates[0].toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
                    const end = selectedDates[1].toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
                    dateDisplay.textContent = `${start} - ${end}`;
                    dateDisplay.classList.remove('text-muted');
                    applyAllFilters();
                    
                    calendarContainerWrapper.style.display = 'none';
                }
            }
        });
    }
}

function initPriceSlider() {
    if (!priceSliderElement) return;

    priceSliderInstance = noUiSlider.create(priceSliderElement, {
        start: [currentFilters.minPrice, currentFilters.maxPrice],
        connect: true,
        step: 10,
        range: {
            'min': 50,
            'max': 1000
        },
        format: {
            to: function (value) {
                return Math.round(value);
            },
            from: function (value) {
                return Number(value);
            }
        }
    });

    priceSliderInstance.on('update', function (values, handle) {
        const [minVal, maxVal] = values;
        if (minPriceDisplay) {
            minPriceDisplay.innerHTML = `${minVal} TND`;
        }
        if (maxPriceDisplay) {
            maxPriceDisplay.innerHTML = `${maxVal} TND`;
        }
    });
}


async function populateAmenityFilters() {
    if (!amenitiesFilterContainer) return;
    try {
        const response = await fetch(`${API_URL}/features_api.php?action=getAll`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const features = await response.json();

        amenitiesFilterContainer.innerHTML = '';
        features.forEach(feature => {
            const col = document.createElement('div');
            col.className = 'col';
            const iconClass = feature.icon_class || 'ph-tag';
            col.innerHTML = `
                <div class="form-check form-check-btn">
                    <input class="form-check-input" type="checkbox" value="${feature.feature_name}" id="amenity_${feature.feature_id}" name="amenities[]">
                    <label class="form-check-label amenity-box-label" for="amenity_${feature.feature_id}">
                        <i class="ph ${iconClass}"></i>
                        <span>${feature.feature_name}</span>
                    </label>
                </div>
            `;
            amenitiesFilterContainer.appendChild(col);
        });
    } catch (error) {
        console.error("Could not fetch features:", error);
        amenitiesFilterContainer.innerHTML = '<p class="text-danger small">Erreur de chargement des équipements.</p>';
    }
}

// --- House Display Functions ---
function createHouseCard(house) {
    const col = document.createElement('div');
    col.className = 'col house-card-trackable';
    col.dataset.houseId = house.id;

    const imagePath = house.image ? house.image : `${ASSETS_URL}/images/placeholder-house.png`;
    const detailPageUrl = `${window.basePath}pages/house-detail.php?id=${house.id}`;
    const rating = parseFloat(house.rating) || 0;

    let featuresHtml = '';
    if (house.features && typeof house.features === 'string') {
        const featuresArray = house.features.split(';;');
        featuresHtml = featuresArray.map(featureString => {
            if (!featureString) return '';
            const parts = featureString.split('::');
            const name = parts[0];
            const icon = parts[1] || 'ph-tag';
            return `<span class="me-3" title="${name}"><i class="ph ${icon}"></i></span>`;
        }).join('');
    }

    col.innerHTML = `
        <div class="card h-100 shadow-sm border-0">
            <a href="${detailPageUrl}" class="text-decoration-none text-dark d-flex flex-column h-100 house-link" data-house-id="${house.id}">
                <div class="position-relative">
                    <div class="card-img-top-container" style="aspect-ratio: 4 / 3; overflow: hidden;">
                        <img src="${imagePath}" onerror="this.onerror=null;this.src='${ASSETS_URL}/images/placeholder-house.png';" alt="${house.title}" class="img-fluid w-100 h-100" style="object-fit: cover;">
                    </div>
                    ${rating > 0 ? `<div class="position-absolute top-0 end-0 p-2"><span class="badge bg-dark bg-opacity-75 text-white d-flex align-items-center gap-1"><i class="ph-fill ph-star text-warning"></i> ${rating.toFixed(1)}</span></div>` : ''}
                </div>
                <div class="card-body d-flex flex-column p-3">
                    <h5 class="card-title h6 mb-1 fw-bold text-truncate">${house.title}</h5>
                    <p class="card-text text-muted small mb-2 text-truncate"><i class="ph ph-map-pin me-1"></i>${house.location}</p>
                    
                    <div class="house-card-features small text-muted my-2">
                        ${featuresHtml}
                    </div>

                    <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3 small text-muted">
                            <span title="${house.bedrooms} chambres"><i class="ph ph-bed"></i> ${house.bedrooms || 'N/A'}</span>
                            <span title="${house.bathrooms} salles de bain"><i class="ph ph-bathtub"></i> ${house.bathrooms || 'N/A'}</span>
                            <span title="${house.surface} m²"><i class="ph ph-ruler"></i> ${house.surface || 'N/A'}m²</span>
                        </div>
                        <p class="mb-0"><strong class="text-primary h5">${house.price}</strong><span class="text-muted small">/nuit</span></p>
                    </div>
                </div>
            </a>
        </div>`;
    return col;
}

function displayHousesOnGrid(housesToDisplay) {
    if (!houseGrid || !houseGridLoading) return;
    houseGrid.innerHTML = ''; // Clear previous results
    houseGridLoading.style.display = 'none';

    if (housesToDisplay.length > 0) {
        housesToDisplay.forEach(house => {
            const card = createHouseCard(house);
            houseGrid.appendChild(card);
        });
        setupHouseViewTracking();
        document.querySelectorAll('.house-link').forEach(link => {
            link.addEventListener('click', (e) => {
                trackEvent('house_click', { houseId: link.dataset.houseId });
            });
        });
    } else {
        houseGrid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted fs-5">Aucune propriété ne correspond à vos critères.</p></div>';
    }
}

// --- NEW FUNCTION: Fetches all houses just for the search functionality ---
async function loadAllHousesForSearch() {
    try {
        const response = await fetch(`${API_URL}/houses.php?action=getAll`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const responseData = await response.json();
        allHouses = Array.isArray(responseData) ? responseData : [];
    } catch (error) {
        console.error("Could not fetch houses for search:", error);
    }
}


// --- MODIFIED FUNCTION: Now only fetches and displays for the main grid ---
async function fetchAndDisplayFilteredHouses() {
    if (!houseGrid || !houseGridLoading) return;

    houseGridLoading.style.display = 'block';

    try {
        let queryParams = new URLSearchParams({ action: 'getAll' });
        
        // Append all filters to the query
        if (currentFilters.bedrooms && currentFilters.bedrooms.length > 0) {
            queryParams.append('bedrooms', currentFilters.bedrooms.join(','));
        }
        if (currentFilters.bathrooms && currentFilters.bathrooms.length > 0) {
             queryParams.append('bathrooms', currentFilters.bathrooms.join(','));
        }
        if (currentFilters.amenities && currentFilters.amenities.length > 0) {
            currentFilters.amenities.forEach(am => queryParams.append('amenities[]', am));
        }
        if (currentFilters.startDate && currentFilters.endDate) {
            queryParams.append('startDate', currentFilters.startDate);
            queryParams.append('endDate', currentFilters.endDate);
        }
        if (currentFilters.sortBy) {
            queryParams.append('sortBy', currentFilters.sortBy);
        }
        if (currentFilters.maxPrice) {
            queryParams.append('maxPrice', currentFilters.maxPrice);
        }
        if (currentFilters.minPrice) {
            queryParams.append('minPrice', currentFilters.minPrice);
        }

        const response = await fetch(`${API_URL}/houses.php?${queryParams.toString()}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText}`);
        }
        const filteredHouses = await response.json();
        displayHousesOnGrid(Array.isArray(filteredHouses) ? filteredHouses : []);
        
    } catch (error) {
        console.error("Could not fetch filtered houses:", error);
        houseGridLoading.style.display = 'none';
        houseGrid.innerHTML = `<div class="col-12 text-center py-5"><p class="text-danger fs-5">Erreur lors du chargement des propriétés.</p></div>`;
    }
}

// --- Search Functionality ---
function toggleSearchOverlay(show) {
    if (searchOverlay) {
        if (show) {
            searchOverlay.style.display = 'block';
            setTimeout(() => {
                searchOverlay.style.transform = 'translateY(0)';
                if (searchInput) searchInput.focus();
            }, 10);
        } else {
            searchOverlay.style.transform = 'translateY(100%)';
            setTimeout(() => {
                searchOverlay.style.display = 'none';
            }, 300);
            if (searchInput) searchInput.value = '';
            if (searchResultsContainer) searchResultsContainer.style.display = 'none';
        }
    }
}

function handleSearchInput(e) { 
    if (!searchInput || !searchResultsContainer) return;
    const query = e.target.value.toLowerCase().trim();
    if (query.length < 2) {
        searchResultsContainer.innerHTML = '';
        searchResultsContainer.style.display = 'none';
        return;
    }

    const filteredHouses = allHouses.filter(house =>
        house.title.toLowerCase().includes(query) ||
        house.location.toLowerCase().includes(query)
    );

    searchResultsContainer.innerHTML = '';
    if (filteredHouses.length > 0) {
        filteredHouses.forEach(house => {
            const resultItem = document.createElement('a');
            resultItem.href = `${window.basePath}pages/house-detail.php?id=${house.id}`;
            resultItem.className = 'search-result-item d-flex align-items-center p-2 text-decoration-none text-dark border-bottom';
            const imagePath = house.image ? house.image : `${ASSETS_URL}/images/placeholder-house.png`;
            resultItem.innerHTML = `
                <img src="${imagePath}" alt="${house.title}" class="me-2 rounded" style="width: 50px; height: 50px; object-fit: cover;">
                <div>
                    <div class="fw-medium small">${house.title}</div>
                    <div class="text-muted very-small">${house.location}</div>
                </div>
            `;
            searchResultsContainer.appendChild(resultItem);
        });
        searchResultsContainer.style.display = 'block';
    } else {
        searchResultsContainer.innerHTML = '<p class="p-3 text-muted small text-center">Aucun résultat trouvé.</p>';
        searchResultsContainer.style.display = 'block';
    }
}


// --- Filter Logic ---
function applyAllFilters() {
    if (!filterForm) return;

    currentFilters.bedrooms = Array.from(filterForm.querySelectorAll('input[name="bedrooms[]"]:checked')).map(cb => cb.value);
    currentFilters.bathrooms = Array.from(filterForm.querySelectorAll('input[name="bathrooms[]"]:checked')).map(cb => cb.value);
    currentFilters.amenities = Array.from(filterForm.querySelectorAll('input[name="amenities[]"]:checked')).map(cb => cb.value);
    currentFilters.sortBy = document.getElementById('sortBy').value;
    
    if (priceSliderInstance) {
        const [minVal, maxVal] = priceSliderInstance.get();
        currentFilters.minPrice = minVal;
        currentFilters.maxPrice = maxVal;
    }
    
    trackEvent('search', currentFilters);

    fetchAndDisplayFilteredHouses();

    if (filtersOffcanvasElement) {
        const offcanvasInstance = bootstrap.Offcanvas.getInstance(filtersOffcanvasElement);
        if (offcanvasInstance) {
            offcanvasInstance.hide();
        }
    }
}

function resetAllFilters() {
    if (!filterForm) return;
    filterForm.reset();

    if (flatpickrInstanceDateRange) {
        flatpickrInstanceDateRange.clear(); 
        dateDisplay.textContent = 'Sélectionnez vos dates';
        dateDisplay.classList.add('text-muted');
    }
    
    if (priceSliderInstance) {
        priceSliderInstance.set([50, 1000]);
    }
    
    currentFilters = {
        bedrooms: [], bathrooms: [], amenities: [],
        startDate: null, endDate: null,
        sortBy: 'popularity', maxPrice: 1000, minPrice: 50
    };
    applyAllFilters();
}

// --- Initial Page Load & Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    userSessionId = getOrCreateSessionId();
    loadAllHousesForSearch();

    if (houseGrid) {
        initDateRangePicker();
        initPriceSlider();
        populateAmenityFilters();
        fetchAndDisplayFilteredHouses();
        
        if(applyFiltersBtn) applyFiltersBtn.addEventListener('click', applyAllFilters);
        if(resetFiltersBtn) resetFiltersBtn.addEventListener('click', resetAllFilters);
    }

    // Global listeners
    if(searchTriggerButtons) searchTriggerButtons.forEach(trigger => trigger.addEventListener('click', () => toggleSearchOverlay(true)));
    if(closeSearchBtn) closeSearchBtn.addEventListener('click', () => toggleSearchOverlay(false));
    if(searchInput) searchInput.addEventListener('input', handleSearchInput);
});
