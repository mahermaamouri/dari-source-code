// assets/js/admin/reservations.js
document.addEventListener('DOMContentLoaded', function () {
    // API Endpoints
    const RESERVATIONS_API_URL = window.basePath + 'api/admin_reservations_api.php';
    const HOUSES_API_URL = window.basePath + 'api/admin_houses_api.php';

    // Global state arrays
    let reservations = [];
    let houses = [];

    // DOM Elements
    const reservationsTableBody = document.getElementById('reservationsTableBody');
    const propertySelect = document.getElementById('propertySelect');
    
    // Modals
    const reservationModalElement = document.getElementById('reservationModal');
    const reservationModalInstance = reservationModalElement ? new bootstrap.Modal(reservationModalElement) : null;
    const confirmationModalElement = document.getElementById('confirmationModal');
    const confirmationModalInstance = confirmationModalElement ? new bootstrap.Modal(confirmationModalElement) : null;
    const deleteModalElement = document.getElementById('deleteModal');
    const deleteModalInstance = deleteModalElement ? new bootstrap.Modal(deleteModalElement) : null;

    // Forms & Buttons
    const reservationForm = document.getElementById('reservationForm');
    const modalTitleElement = reservationModalElement ? reservationModalElement.querySelector('.modal-title') : null;
    const confirmationSummaryDiv = confirmationModalElement ? confirmationModalElement.querySelector('.confirmation-summary') : null;
    const addReservationBtn = document.getElementById('addReservationBtn');
    const confirmReservationBtn = document.getElementById('confirmReservation');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const tabButtons = document.querySelectorAll('.original-tab-button');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const totalPriceInput = document.getElementById('totalPrice');
    const advancePaymentInput = document.getElementById('advancePayment');


    // State variables
    let currentReservationId = null;
    let activeTabFilter = 'pending';

    // --- Data Loading ---
    async function loadDataFromAPI() {
        try {
            const [reservationsResponse, housesResponse] = await Promise.all([
                fetch(`${RESERVATIONS_API_URL}?action=list`),
                fetch(`${HOUSES_API_URL}?action=list`)
            ]);

            const reservationsResult = await reservationsResponse.json();
            const housesResult = await housesResponse.json();

            if (reservationsResult.success) {
                reservations = reservationsResult.reservations;
            } else {
                showSimpleAlert(reservationsResult.message || 'Erreur de chargement des réservations.', 'danger');
            }

            if (housesResult.success) {
                houses = housesResult.houses;
            } else {
                showSimpleAlert(housesResult.message || 'Erreur de chargement des propriétés.', 'danger');
            }
            
            initPropertySelect();
            updateReservationsTable(activeTabFilter);

        } catch (error) {
            console.error("Failed to load data from API:", error);
            showSimpleAlert("Erreur de communication avec le serveur.", 'danger');
        }
    }

    // --- UI Rendering ---
    function initPropertySelect() {
        if (!propertySelect) return;
        if (houses && houses.length > 0) {
            propertySelect.innerHTML = '<option value="">Sélectionnez une propriété</option>';
            propertySelect.innerHTML += houses.map(house =>
                `<option value="${house.house_id}" data-price="${house.price_per_night}">${house.title}</option>`
            ).join('');
        } else {
            propertySelect.innerHTML = '<option value="">Aucune propriété chargée</option>';
        }
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function getStatusBadge(status) {
        const statusClasses = {
            pending: 'bg-warning-subtle text-warning-emphasis',
            confirmed: 'bg-success-subtle text-success-emphasis',
            cancelled: 'bg-danger-subtle text-danger-emphasis'
        };
        const statusLabels = { pending: 'En attente', confirmed: 'Confirmée', cancelled: 'Annulée' };
        return `<span class="badge rounded-pill ${statusClasses[status] || 'bg-secondary-subtle text-secondary-emphasis'}">${statusLabels[status] || status}</span>`;
    }

    function updateReservationsTable(filter) {
        if (!reservationsTableBody) return;
        const filteredReservations = reservations.filter(r => r.status === filter);
        reservationsTableBody.innerHTML = '';

        if (filteredReservations.length === 0) {
            reservationsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Aucune réservation à afficher dans cette catégorie.</td></tr>`;
            return;
        }

        filteredReservations.forEach(reservation => {
            const row = reservationsTableBody.insertRow();
            row.dataset.id = reservation.reservation_id;
            
            let voucherButtonHtml = '';
            if (reservation.status === 'confirmed') {
                if (reservation.voucher_sent_at) {
                    voucherButtonHtml = `
                        <button class="btn btn-sm btn-success" disabled title="Voucher envoyé le ${new Date(reservation.voucher_sent_at).toLocaleString('fr-FR')}">
                            <i class="ph ph-check-circle"></i>
                        </button>
                    `;
                } else {
                    voucherButtonHtml = `
                        <a href="voucher.php?reservation_id=${reservation.reservation_id}" class="btn btn-sm btn-outline-info" title="Envoyer Voucher">
                            <i class="ph ph-receipt"></i>
                        </a>
                    `;
                }
            }
            
            row.innerHTML = `
                <td>
                    <div class="fw-medium">${reservation.client_name}</div>
                    <div class="small text-muted">${reservation.client_phone}</div>
                </td>
                <td>${reservation.house_title || 'N/A'}</td>
                <td>${formatDate(reservation.start_date)} - ${formatDate(reservation.end_date)}</td>
                <td>${parseFloat(reservation.total_price).toFixed(2)} TND</td>
                <td>${getStatusBadge(reservation.status)}</td>
                <td class="text-nowrap">
                    ${reservation.status === 'pending' ? `
                        <button class="btn btn-sm btn-outline-success confirm-btn" data-id="${reservation.reservation_id}" title="Confirmer">
                            <i class="ph ph-check"></i>
                        </button>
                    ` : ''}
                    ${voucherButtonHtml}
                    <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${reservation.reservation_id}" title="Modifier">
                        <i class="ph ph-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${reservation.reservation_id}" title="Supprimer">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            `;
        });
        attachActionListeners();
    }
    
    function attachActionListeners() {
        document.querySelectorAll('.confirm-btn').forEach(btn => btn.addEventListener('click', () => openConfirmationModal(parseInt(btn.dataset.id))));
        document.querySelectorAll('.edit-btn').forEach(btn => btn.addEventListener('click', () => openEditModal(parseInt(btn.dataset.id))));
        document.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', () => openDeleteModal(parseInt(btn.dataset.id))));
    }

    // --- Modal Handling & Actions ---
    function openAddModal() {
        if (!reservationForm || !modalTitleElement || !reservationModalInstance) return;
        currentReservationId = null;
        modalTitleElement.textContent = 'Nouvelle réservation';
        reservationForm.reset();
        reservationForm.classList.remove('was-validated');
        reservationModalInstance.show();
    }

    function openEditModal(reservationId) {
        if (!reservationForm || !modalTitleElement || !reservationModalInstance) return;
        const reservation = reservations.find(r => r.reservation_id === reservationId);
        if (!reservation) return;

        currentReservationId = reservationId;
        modalTitleElement.textContent = 'Modifier la réservation';
        reservationForm.reset();
        reservationForm.classList.remove('was-validated');

        document.getElementById('clientName').value = reservation.client_name;
        document.getElementById('clientEmail').value = reservation.client_email;
        document.getElementById('clientPhone').value = reservation.client_phone;
        document.getElementById('propertySelect').value = reservation.house_id;
        document.getElementById('startDate').value = reservation.start_date;
        document.getElementById('endDate').value = reservation.end_date;
        document.getElementById('totalPrice').value = parseFloat(reservation.total_price).toFixed(2);
        document.getElementById('advancePayment').value = parseFloat(reservation.advance_payment || 0).toFixed(2);
        document.getElementById('statusSelect').value = reservation.status;

        reservationModalInstance.show();
    }

    function openConfirmationModal(reservationId) {
        if (!confirmationSummaryDiv || !confirmationModalInstance) return;
        const reservation = reservations.find(r => r.reservation_id === reservationId);
        if (!reservation) return;

        currentReservationId = reservationId;
        confirmationSummaryDiv.innerHTML = `
            <div class="d-flex justify-content-between py-1"><small class="text-muted">Client:</small> <strong>${reservation.client_name}</strong></div>
            <div class="d-flex justify-content-between py-1"><small class="text-muted">Propriété:</small> <strong>${reservation.house_title}</strong></div>
            <div class="d-flex justify-content-between py-1"><small class="text-muted">Dates:</small> <strong>${formatDate(reservation.start_date)} - ${formatDate(reservation.end_date)}</strong></div>
            <div class="d-flex justify-content-between py-1"><small class="text-muted">Prix total:</small> <strong>${parseFloat(reservation.total_price).toFixed(2)} TND</strong></div>
        `;
        confirmationModalInstance.show();
    }

    function openDeleteModal(reservationId) {
        if (!deleteModalInstance) return;
        currentReservationId = reservationId;
        deleteModalInstance.show();
    }

    async function handleFormSubmit(e) {
        e.preventDefault();
        if (!reservationForm.checkValidity()) {
            e.stopPropagation();
            reservationForm.classList.add('was-validated');
            return;
        }

        const formData = new FormData(reservationForm);
        const action = currentReservationId ? 'update' : 'add';
        formData.append('action', action);
        if (currentReservationId) {
            formData.append('reservation_id', currentReservationId);
        }

        try {
            const response = await fetch(RESERVATIONS_API_URL, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showSimpleAlert(result.message, 'success');
                reservationModalInstance.hide();
                loadDataFromAPI();
            } else {
                showSimpleAlert(result.message || 'Erreur lors de la sauvegarde.', 'danger');
            }
        } catch (error) {
            showSimpleAlert('Erreur de communication.', 'danger');
        }
    }

    function isDateRangeOverlap(startA, endA, startB, endB) {
        const startDateA = new Date(startA);
        const endDateA = new Date(endA);
        const startDateB = new Date(startB);
        const endDateB = new Date(endB);
        return startDateA < endDateB && endDateA > startDateB;
    }

    async function handleConfirm() {
        const reservationToConfirm = reservations.find(r => r.reservation_id === currentReservationId);
        if (!reservationToConfirm) return;

        const conflictingReservations = reservations.filter(r =>
            r.house_id === reservationToConfirm.house_id &&
            r.status === 'confirmed' &&
            r.reservation_id != reservationToConfirm.reservation_id
        );

        for (const confirmedRes of conflictingReservations) {
            if (isDateRangeOverlap(
                reservationToConfirm.start_date, reservationToConfirm.end_date,
                confirmedRes.start_date, confirmedRes.end_date
            )) {
                showSimpleAlert(
                    `Conflit! Propriété déjà réservée du ${formatDate(confirmedRes.start_date)} au ${formatDate(confirmedRes.end_date)}.`,
                    'danger'
                );
                confirmationModalInstance.hide();
                return;
            }
        }

        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('reservation_id', currentReservationId);
        formData.append('house_id', reservationToConfirm.house_id);
        formData.append('client_name', reservationToConfirm.client_name);
        formData.append('client_email', reservationToConfirm.client_email);
        formData.append('client_phone', reservationToConfirm.client_phone);
        formData.append('start_date', reservationToConfirm.start_date);
        formData.append('end_date', reservationToConfirm.end_date);
        formData.append('total_price', reservationToConfirm.total_price);
        formData.append('advance_payment', reservationToConfirm.advance_payment);
        formData.append('status', 'confirmed');

        try {
            const response = await fetch(RESERVATIONS_API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showSimpleAlert('Réservation confirmée.', 'success');
                confirmationModalInstance.hide();
                loadDataFromAPI();
            } else {
                showSimpleAlert(result.message || 'Erreur lors de la confirmation.', 'danger');
            }
        } catch (error) {
            showSimpleAlert('Erreur de communication.', 'danger');
        }
    }

    async function handleDelete() {
        if (!currentReservationId) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('reservation_id', currentReservationId);

        try {
            const response = await fetch(RESERVATIONS_API_URL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showSimpleAlert(result.message, 'success');
                deleteModalInstance.hide();
                loadDataFromAPI();
            } else {
                showSimpleAlert(result.message || 'Erreur lors de la suppression.', 'danger');
            }
        } catch (error) {
            showSimpleAlert('Erreur de communication.', 'danger');
        }
    }

    // --- Utility Functions ---
    function updatePrice() {
        if (!startDateInput || !endDateInput || !propertySelect || !totalPriceInput || !advancePaymentInput) return;
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        const selectedOption = propertySelect.options[propertySelect.selectedIndex];
        const pricePerNight = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;

        if (startDateInput.value && endDateInput.value && endDate > startDate && pricePerNight > 0) {
            const nights = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24));
            if (nights > 0) {
                const total = nights * pricePerNight;
                totalPriceInput.value = total.toFixed(2);
                advancePaymentInput.value = (total * 0.3).toFixed(2);
            }
        } else {
            totalPriceInput.value = '';
            advancePaymentInput.value = '';
        }
    }

    function showSimpleAlert(message, type = 'success') {
        const alertContainer = document.createElement('div');
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '2050';

        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type} alert-dismissible fade show`;
        alertEl.role = 'alert';
        alertEl.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        alertContainer.appendChild(alertEl);
        document.body.appendChild(alertContainer);
        
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertEl);
            bsAlert.close();
            setTimeout(() => alertContainer.remove(), 150);
        }, 5000);
    }

    // --- Event Listeners & Initial Load ---
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', event => {
            activeTabFilter = event.target.dataset.tabFilter;
            updateReservationsTable(activeTabFilter);
        });
    });
    
    if (addReservationBtn) addReservationBtn.addEventListener('click', openAddModal);
    if (reservationForm) reservationForm.addEventListener('submit', handleFormSubmit);
    if (confirmReservationBtn) confirmReservationBtn.addEventListener('click', handleConfirm);
    if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', handleDelete);
    
    if (startDateInput) startDateInput.addEventListener('change', updatePrice);
    if (endDateInput) endDateInput.addEventListener('change', updatePrice);
    if (propertySelect) propertySelect.addEventListener('change', updatePrice);
    
    const initialActiveTab = document.querySelector('.original-tab-button.active');
    if (initialActiveTab) {
        activeTabFilter = initialActiveTab.dataset.tabFilter;
    }
    
    loadDataFromAPI();
});
