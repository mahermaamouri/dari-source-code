// assets/js/admin/houses.js
document.addEventListener('DOMContentLoaded', function () {
    const housesGridContainer = document.getElementById('housesGridContainer');
    const housesLoadingPlaceholder = document.getElementById('housesLoadingPlaceholder');
    
    const addHouseBtn = document.getElementById('addHouseBtn');
    
    const houseModalElement = document.getElementById('houseModal');
    const houseModalInstance = houseModalElement ? new bootstrap.Modal(houseModalElement) : null;
    const houseModalLabel = document.getElementById('modalTitle');
    const houseForm = document.getElementById('houseForm');
    const houseIdInput = document.getElementById('houseId');

    const titleInput = document.getElementById('title');
    const locationInput = document.getElementById('location');
    const descriptionTextarea = document.getElementById('description');
    const priceInput = document.getElementById('price');
    const surfaceInput = document.getElementById('surface');
    const bedroomsInput = document.getElementById('bedrooms');
    const bathroomsInput = document.getElementById('bathrooms');
    const maxGuestsInput = document.getElementById('max_guests');
    const availabilityStatusSelect = document.getElementById('availability_status');

    const imageUploadInput = document.getElementById('imageUploadInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const existingImagesContainer = document.getElementById('existingImagesContainer');
    const featuresGridContainerModal = houseModalElement ? houseModalElement.querySelector('.features-grid-container') : null;
    const featuresLoadingPlaceholderModal = document.getElementById('featuresLoadingPlaceholder');
    
    const saveHouseBtn = document.getElementById('saveHouseBtn');

    // NEW: Map-related elements
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    let map = null;
    let marker = null;
	const defaultCoords = [36.8440, 11.0937]; // Kelibia coordinates

    const deleteHouseModalElement = document.getElementById('deleteHouseModal');
    const deleteHouseModalInstance = deleteHouseModalElement ? new bootstrap.Modal(deleteHouseModalElement) : null;
    const houseNameToDeleteSpan = document.getElementById('houseNameToDelete');
    const confirmDeleteHouseBtn = document.getElementById('confirmDeleteHouseBtn');
    let houseIdToDelete = null;

    const API_URL = window.basePath + 'api/admin_houses_api.php';
    const ASSETS_URL_ADMIN = window.basePath + 'assets';

    const getImagePath = (url) => {
        if (!url) return `${ASSETS_URL_ADMIN}/images/placeholder-house.png`;
        if (url.startsWith('http') || url.startsWith('/')) return url;
        // The API now provides the full path, but this is a good fallback.
        return `${ASSETS_URL_ADMIN}/images/houses/${url}`;
    };

    async function loadHouses() {
        if (!housesGridContainer || !housesLoadingPlaceholder) return;
        housesGridContainer.innerHTML = `<div class="col-12 text-center p-5" id="housesLoadingPlaceholder">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Chargement des propriétés...</p>
        </div>`;

        try {
            const response = await fetch(`${API_URL}?action=list`);
            const result = await response.json();

            if (result.success && result.houses) {
                renderHousesGrid(result.houses);
            } else {
                housesGridContainer.innerHTML = `<div class="col-12 text-center text-danger p-3">${result.message || "Impossible de charger les propriétés."}</div>`;
            }
        } catch (error) {
            console.error('Error loading houses:', error);
            housesGridContainer.innerHTML = `<div class="col-12 text-center text-danger p-3">Erreur de chargement: ${error.message}</div>`;
        }
    }

    function renderHousesGrid(houses) {
        if (!housesGridContainer) return;
        housesGridContainer.innerHTML = '';
        if (houses.length === 0) {
            housesGridContainer.innerHTML = `<div class="col-12 text-center text-muted p-4">Aucune propriété trouvée. Cliquez sur "Nouvelle propriété" pour en ajouter une.</div>`;
            return;
        }
        houses.forEach(house => {
            const col = document.createElement('div');
            col.className = 'col';
            const imageUrl = house.main_image_url ? getImagePath(house.main_image_url) : `${ASSETS_URL_ADMIN}/images/placeholder-house.png`;
            col.innerHTML = `
                <div class="card h-100 shadow-sm">
                    <img src="${imageUrl}" onerror="this.onerror=null;this.src='${ASSETS_URL_ADMIN}/images/placeholder-house.png';" class="card-img-top" alt="${house.title}" style="height: 180px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title h6 mb-1 text-truncate" title="${house.title}">${house.title}</h5>
                        <p class="card-text text-muted small mb-2 text-truncate" title="${house.location}"><i class="ph ph-map-pin me-1"></i>${house.location}</p>
                        <p class="card-text small mb-1"><strong>Prix:</strong> ${house.price_per_night} TND</p>
                        <p class="card-text small mb-1"><strong>Statut:</strong> <span class="badge bg-${house.availability_status === 'available' ? 'success' : 'warning'}-subtle text-${house.availability_status === 'available' ? 'success' : 'warning'}-emphasis rounded-pill">${house.availability_status}</span></p>
                        <p class="card-text small text-muted mb-2">Équipements: ${house.features_summary ? house.features_summary.split(',').length : 0}</p>
                        <div class="mt-auto d-flex justify-content-end gap-2">
                            <button class="btn btn-sm btn-outline-primary edit-house-btn" data-id="${house.house_id}" title="Modifier"><i class="ph ph-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-house-btn" data-id="${house.house_id}" data-name="${house.title}" title="Supprimer"><i class="ph ph-trash"></i></button>
                        </div>
                    </div>
                </div>`;
            housesGridContainer.appendChild(col);
        });
        attachActionListeners();
    }
    
    function attachActionListeners() {
        document.querySelectorAll('.edit-house-btn').forEach(button => button.addEventListener('click', handleEditHouse));
        document.querySelectorAll('.delete-house-btn').forEach(button => button.addEventListener('click', handleDeleteHousePrompt));
    }

    function initMap(lat, lng) {
        if (!map) {
            map = L.map('map').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        } else {
            map.setView([lat, lng], 13);
        }

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                latitudeInput.value = position.lat.toFixed(8);
                longitudeInput.value = position.lng.toFixed(8);
            });
        }
        latitudeInput.value = lat.toFixed(8);
        longitudeInput.value = lng.toFixed(8);
    }

    async function loadFeaturesForModal(selectedFeatureIds = []) {
        if (!featuresGridContainerModal || !featuresLoadingPlaceholderModal) return;
        featuresGridContainerModal.innerHTML = '';
        featuresLoadingPlaceholderModal.style.display = 'block';

        try {
            const response = await fetch(`${API_URL}?action=get_features`);
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const result = await response.json();

            featuresLoadingPlaceholderModal.style.display = 'none';
            if (result.success && result.features) {
                result.features.forEach(feature => {
                    const isChecked = selectedFeatureIds.includes(String(feature.feature_id)) || selectedFeatureIds.includes(Number(feature.feature_id));
                    const col = document.createElement('div');
                    col.className = 'col';
                    col.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${feature.feature_id}" id="feature_${feature.feature_id}" name="feature_ids[]" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label small" for="feature_${feature.feature_id}"><i class="ph ${feature.icon_class || 'ph-tag'} me-1"></i> ${feature.feature_name}</label>
                        </div>
                    `;
                    featuresGridContainerModal.appendChild(col);
                });
            } else {
                featuresGridContainerModal.innerHTML = `<div class="col-12 text-danger small">${result.message || "Impossible de charger les équipements."}</div>`;
            }
        } catch (error) {
            console.error('Error loading features for modal:', error);
            featuresLoadingPlaceholderModal.style.display = 'none';
            featuresGridContainerModal.innerHTML = `<div class="col-12 text-danger small">Erreur: ${error.message}</div>`;
        }
    }

    function openAddModal() {
        if (!houseModalInstance || !houseForm || !houseModalLabel) return;
        houseForm.reset();
        houseForm.classList.remove('was-validated');
        houseIdInput.value = '';
        houseModalLabel.textContent = 'Ajouter une nouvelle propriété';
        imagePreviewContainer.innerHTML = '';
        existingImagesContainer.innerHTML = '';
        imageUploadInput.value = '';
        loadFeaturesForModal([]);
        houseModalInstance.show();
        setTimeout(() => initMap(defaultCoords[0], defaultCoords[1]), 200);
    }

    async function handleEditHouse(event) {
        const houseId = event.currentTarget.dataset.id;
        if (!houseId) return;

        houseModalLabel.textContent = 'Chargement...';
        houseForm.reset();
        imagePreviewContainer.innerHTML = '';
        existingImagesContainer.innerHTML = '';
        imageUploadInput.value = '';
        featuresGridContainerModal.innerHTML = `<div class="col-12 text-muted small">Chargement des équipements...</div>`;
        houseModalInstance.show();

        try {
            const response = await fetch(`${API_URL}?action=get_house_details&house_id=${houseId}`);
            const result = await response.json();

            if (result.success && result.house) {
                const house = result.house;
                houseModalLabel.textContent = `Modifier: ${house.title}`;
                houseIdInput.value = house.house_id;
                if(titleInput) titleInput.value = house.title;
                if(locationInput) locationInput.value = house.location;
                if(descriptionTextarea) descriptionTextarea.value = house.description || '';
                if(priceInput) priceInput.value = house.price_per_night;
                if(surfaceInput) surfaceInput.value = house.surface_area_sqm || '';
                if(bedroomsInput) bedroomsInput.value = house.bedrooms;
                if(bathroomsInput) bathroomsInput.value = house.bathrooms;
                if(maxGuestsInput) maxGuestsInput.value = house.max_guests || '';
                if(availabilityStatusSelect) availabilityStatusSelect.value = house.availability_status;
                if(latitudeInput) latitudeInput.value = house.latitude;
                if(longitudeInput) longitudeInput.value = house.longitude;
                
                if (house.images_data && existingImagesContainer) {
                    existingImagesContainer.innerHTML = ''; // Clear previous images
                    house.images_data.forEach(img => {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'existing-image-item position-relative d-inline-block me-2 mb-2 border rounded p-1';
                        imgDiv.innerHTML = `<img src="${getImagePath(img.full_url)}" alt="Image" style="width: 80px; height: 80px; object-fit: cover;"><button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 m-1 delete-existing-image-btn" data-image-id="${img.image_id}" style="line-height: 1; width: 20px; height:20px;">&times;</button>`;
                        existingImagesContainer.appendChild(imgDiv);
                    });
                    document.querySelectorAll('.delete-existing-image-btn').forEach(btn => {
                        btn.addEventListener('click', handleDeleteExistingImage);
                    });
                }
                loadFeaturesForModal(house.feature_ids || []);
                
                const lat = parseFloat(house.latitude) || defaultCoords[0];
                const lng = parseFloat(house.longitude) || defaultCoords[1];
                setTimeout(() => initMap(lat, lng), 200);

            } else {
                showSimpleAlert(result.message || "Impossible de charger les détails.", 'danger');
                houseModalInstance.hide();
            }
        } catch (error) {
            console.error('Error fetching house for edit:', error);
            showSimpleAlert(`Erreur: ${error.message}`, 'danger');
            houseModalInstance.hide();
        }
    }
    
    function handleImagePreview(event) {
        if (!imagePreviewContainer) return;
        imagePreviewContainer.innerHTML = '';
        const files = event.target.files;
        for (const file of files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    const imgDiv = document.createElement('div');
                    imgDiv.className = 'new-image-preview position-relative d-inline-block me-2 mb-2 border rounded p-1';
                    imgDiv.innerHTML = `<img src="${e.target.result}" alt="${file.name}" style="width: 80px; height: 80px; object-fit: cover;">`;
                    imagePreviewContainer.appendChild(imgDiv);
                }
                reader.readAsDataURL(file);
            }
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        if (!houseForm.checkValidity()) {
            event.stopPropagation();
            houseForm.classList.add('was-validated');
            return;
        }

        const originalButtonText = saveHouseBtn.innerHTML;
        saveHouseBtn.disabled = true;
        saveHouseBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Enregistrement...`;

        const formData = new FormData(houseForm);
        const isUpdate = !!houseIdInput.value;
        formData.append('action', isUpdate ? 'update_house' : 'add_house');
        if (!imageUploadInput.files || imageUploadInput.files.length === 0) {
            formData.delete('images[]');
        }
        const selectedFeatureIds = Array.from(featuresGridContainerModal.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
        formData.append('feature_ids_string', selectedFeatureIds.join(','));
        
        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                houseModalInstance.hide();
                loadHouses();
                showSimpleAlert(result.message, 'success');
            } else {
                showSimpleAlert(`Échec: ${result.message || 'Erreur inconnue.'}`, 'danger');
            }
        } catch (error) {
            console.error('Error saving house:', error);
            showSimpleAlert(`Erreur de communication: ${error.message}`, 'danger');
        } finally {
            saveHouseBtn.disabled = false;
            saveHouseBtn.innerHTML = originalButtonText;
        }
    }

    async function handleDeleteExistingImage(event) {
       const button = event.currentTarget;
        const imageId = button.dataset.imageId;
        if (!imageId || !confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) return;

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const formData = new FormData();
        formData.append('action', 'delete_image');
        formData.append('image_id', imageId);

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                button.closest('.existing-image-item').remove();
                showSimpleAlert(result.message, 'success');
                loadHouses(); 
            } else {
                showSimpleAlert(`Échec de la suppression: ${result.message}`, 'danger');
                button.disabled = false;
                button.innerHTML = '&times;';
            }
        } catch (error) {
            showSimpleAlert(`Erreur: ${error.message}`, 'danger');
            button.disabled = false;
            button.innerHTML = '&times;';
        }
    }
    
    function handleDeleteHousePrompt(event) {
        houseIdToDelete = event.currentTarget.dataset.id;
        const houseName = event.currentTarget.dataset.name;
        if (!houseIdToDelete || !deleteHouseModalInstance || !houseNameToDeleteSpan) return;
        houseNameToDeleteSpan.textContent = houseName;
        deleteHouseModalInstance.show();
    }

    async function confirmDelete() {
        if (!houseIdToDelete) return;
        confirmDeleteHouseBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'delete_house');
        formData.append('house_id', houseIdToDelete);
        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                deleteHouseModalInstance.hide();
                loadHouses();
                showSimpleAlert(result.message, 'success');
            } else {
                showSimpleAlert(`Échec: ${result.message}`, 'danger');
            }
        } catch (error) {
            showSimpleAlert(`Erreur: ${error.message}`, 'danger');
        } finally {
            houseIdToDelete = null;
            confirmDeleteHouseBtn.disabled = false;
        }
    }

    function showSimpleAlert(message, type = 'success') {
        const alertContainer = document.createElement('div');
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '2000';

        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        alertContainer.appendChild(alertEl);
        document.body.appendChild(alertContainer);
        
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertEl);
            bsAlert.close();
            setTimeout(() => alertContainer.remove(), 150);
        }, 5000);
    }

    if(addHouseBtn) addHouseBtn.addEventListener('click', openAddModal);
    if(imageUploadInput) imageUploadInput.addEventListener('change', handleImagePreview);
    if(houseForm) houseForm.addEventListener('submit', handleFormSubmit);
    if(confirmDeleteHouseBtn) confirmDeleteHouseBtn.addEventListener('click', confirmDelete);
    
    houseModalElement.addEventListener('shown.bs.modal', function () {
        if (map) {
            map.invalidateSize();
        }
    });

    loadHouses();
});
