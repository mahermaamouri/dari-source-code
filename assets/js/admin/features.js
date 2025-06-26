// assets/js/admin/features.js
document.addEventListener('DOMContentLoaded', function () {
    const featuresTableBody = document.getElementById('featuresTableBody');
    const addFeatureBtn = document.getElementById('addFeatureBtn');
    
    const featureModalElement = document.getElementById('featureModal');
    const featureModalInstance = featureModalElement ? new bootstrap.Modal(featureModalElement) : null;
    const featureModalLabel = document.getElementById('featureModalLabel');
    const featureForm = document.getElementById('featureForm');
    const featureIdInput = document.getElementById('featureId');
    const featureNameInput = document.getElementById('featureName');
    const featureIconClassInput = document.getElementById('featureIconClass');
    const iconPreview = document.getElementById('iconPreview');
    const saveFeatureBtn = document.getElementById('saveFeatureBtn');

    const deleteFeatureModalElement = document.getElementById('deleteFeatureModal');
    const deleteFeatureModalInstance = deleteFeatureModalElement ? new bootstrap.Modal(deleteFeatureModalElement) : null;
    const featureNameToDeleteSpan = document.getElementById('featureNameToDelete');
    const confirmDeleteFeatureBtn = document.getElementById('confirmDeleteFeatureBtn');
    let featureIdToDelete = null;

    const API_URL = window.basePath + 'api/admin_features_api.php';

    // Function to fetch and display features
    async function loadFeatures() {
        if (!featuresTableBody) return;
        featuresTableBody.innerHTML = `<tr><td colspan="4" class="text-center p-5"><div class="spinner-border spinner-border-sm text-primary"></div> Chargement...</td></tr>`;

        try {
            const response = await fetch(`${API_URL}?action=list`);
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const result = await response.json();

            if (result.success && result.features) {
                renderFeaturesTable(result.features);
            } else {
                featuresTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger p-3">${result.message || "Impossible de charger les équipements."}</td></tr>`;
            }
        } catch (error) {
            console.error('Error loading features:', error);
            featuresTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger p-3">Erreur de chargement: ${error.message}</td></tr>`;
        }
    }

    // Function to render the features table
    function renderFeaturesTable(features) {
        if (!featuresTableBody) return;
        featuresTableBody.innerHTML = '';

        if (features.length === 0) {
            featuresTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-4">Aucun équipement trouvé.</td></tr>`;
            return;
        }

        features.forEach(feature => {
            const row = featuresTableBody.insertRow();
            row.innerHTML = `
                <td>${feature.feature_id}</td>
                <td><i class="ph ${feature.icon_class || 'ph-tag'} me-2 text-muted"></i> ${feature.feature_name}</td>
                <td><code>${feature.icon_class || '-'}</code></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary edit-feature-btn" data-id="${feature.feature_id}" title="Modifier"><i class="ph ph-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-feature-btn" data-id="${feature.feature_id}" data-name="${feature.feature_name}" title="Supprimer"><i class="ph ph-trash"></i></button>
                </td>
            `;
        });
        attachTableActionListeners();
    }

    function attachTableActionListeners() {
        document.querySelectorAll('.edit-feature-btn').forEach(button => {
            button.addEventListener('click', handleEditFeature);
        });
        document.querySelectorAll('.delete-feature-btn').forEach(button => {
            button.addEventListener('click', handleDeleteFeaturePrompt);
        });
    }

    function openAddModal() {
        if (!featureModalInstance || !featureForm || !featureModalLabel) return;
        featureForm.reset();
        featureForm.classList.remove('was-validated');
        featureIdInput.value = '';
        featureModalLabel.textContent = 'Ajouter un équipement';
        if(iconPreview) iconPreview.className = 'ph ph-tag';
        featureModalInstance.show();
    }
    
    async function handleEditFeature(event) {
        const featureId = event.currentTarget.dataset.id;
        if (!featureId || !featureModalInstance) return;
        try {
            const response = await fetch(`${API_URL}?action=get&feature_id=${featureId}`);
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const result = await response.json();
            if (result.success && result.feature) {
                featureForm.reset();
                featureForm.classList.remove('was-validated');
                featureIdInput.value = result.feature.feature_id;
                featureNameInput.value = result.feature.feature_name;
                featureIconClassInput.value = result.feature.icon_class || '';
                if(iconPreview) iconPreview.className = `ph ${result.feature.icon_class || 'ph-tag'}`;
                featureModalLabel.textContent = 'Modifier l\'équipement';
                featureModalInstance.show();
            } else {
                alert(result.message || "Impossible de récupérer les détails.");
            }
        } catch (error) {
            console.error('Error fetching feature for edit:', error);
            alert(`Erreur: ${error.message}`);
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!featureForm.checkValidity()) {
            featureForm.classList.add('was-validated');
            return;
        }

        const originalButtonText = saveFeatureBtn.innerHTML;
        saveFeatureBtn.disabled = true;
        saveFeatureBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Enregistrement...`;

        const formData = new FormData(featureForm);
        const isUpdate = !!featureIdInput.value;
        formData.append('action', isUpdate ? 'update' : 'add');

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                featureModalInstance.hide();
                loadFeatures();
                alert(result.message);
            } else {
                alert(`Échec: ${result.message || 'Erreur inconnue'}`);
            }
        } catch (error) {
            alert(`Erreur: ${error.message}`);
        } finally {
            saveFeatureBtn.disabled = false;
            saveFeatureBtn.innerHTML = originalButtonText;
        }
    }

    function handleDeleteFeaturePrompt(event) {
        featureIdToDelete = event.currentTarget.dataset.id;
        const featureName = event.currentTarget.dataset.name;
        if (!featureIdToDelete || !deleteFeatureModalInstance) return;
        if(featureNameToDeleteSpan) featureNameToDeleteSpan.textContent = featureName;
        deleteFeatureModalInstance.show();
    }

    async function confirmDelete() {
        if (!featureIdToDelete) return;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('feature_id', featureIdToDelete);

        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                deleteFeatureModalInstance.hide();
                loadFeatures();
                alert(result.message);
            } else {
                alert(`Échec: ${result.message}`);
            }
        } catch (error) {
            alert(`Erreur: ${error.message}`);
        } finally {
            featureIdToDelete = null;
        }
    }

    // Event Listeners
    if (addFeatureBtn) addFeatureBtn.addEventListener('click', openAddModal);
    if (featureIconClassInput && iconPreview) {
        featureIconClassInput.addEventListener('input', function() {
            iconPreview.className = `ph ${this.value || 'ph-tag'}`;
        });
    }
    if (featureForm) featureForm.addEventListener('submit', handleFormSubmit);
    if (confirmDeleteFeatureBtn) confirmDeleteFeatureBtn.addEventListener('click', confirmDelete);

    loadFeatures();
});
