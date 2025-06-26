// assets/js/admin/voucher.js
document.addEventListener('DOMContentLoaded', function () {
    // API Endpoints
    const RESERVATIONS_API_URL = window.basePath + 'api/admin_reservations_api.php';
    const HOUSES_API_URL = window.basePath + 'api/admin_houses_api.php';
    const VOUCHER_API_URL = window.basePath + 'api/send_voucher.php';

    // Form Elements
    const voucherForm = document.getElementById('voucherForm');
    const propertySelect = document.getElementById('propertySelect');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const pricePerNightInput = document.getElementById('pricePerNight');
    const totalPriceInput = document.getElementById('totalPrice');
    const sendVoucherBtn = document.getElementById('sendVoucherBtn');

    // NEW: History Table Body
    const historyTableBody = document.getElementById('vouchersHistoryTableBody');

    // --- Data Loading and Initialization ---
    
    async function loadHouses() {
        try {
            const response = await fetch(`${HOUSES_API_URL}?action=list`);
            const result = await response.json();
            if (result.success && propertySelect) {
                propertySelect.innerHTML = '<option value="">Sélectionnez une propriété</option>';
                result.houses.forEach(house => {
                    propertySelect.innerHTML += `<option value="${house.house_id}" data-price="${house.price_per_night}">${house.title}</option>`;
                });
            }
        } catch (error) {
            console.error("Failed to load houses:", error);
        }
    }

    async function loadReservationDetails(reservationId) {
        try {
            const response = await fetch(`${RESERVATIONS_API_URL}?action=get&id=${reservationId}`);
            const result = await response.json();

            if (result.success && result.reservation) {
                const res = result.reservation;
                document.getElementById('reservationId').value = res.reservation_id;
                document.getElementById('clientName').value = res.client_name;
                document.getElementById('clientEmail').value = res.client_email;
                document.getElementById('clientPhone').value = res.client_phone;
                
                propertySelect.value = res.house_id;
                const selectedOption = propertySelect.options[propertySelect.selectedIndex];
                if(selectedOption) {
                    pricePerNightInput.value = parseFloat(selectedOption.dataset.price).toFixed(2);
                }
                
                startDateInput.value = res.start_date;
                endDateInput.value = res.end_date;
                totalPriceInput.value = parseFloat(res.total_price).toFixed(2);
                document.getElementById('advancePayment').value = parseFloat(res.advance_payment || 0).toFixed(2);
                
                calculateTotalPrice();
            } else {
                showSimpleAlert("Impossible de charger les détails de la réservation.", "danger");
            }
        } catch (error) {
            console.error("Error fetching reservation details:", error);
        }
    }
    
    // NEW: Function to load and display voucher history
    async function loadVoucherHistory() {
        if (!historyTableBody) return;
        try {
            const response = await fetch(`${VOUCHER_API_URL}?action=list`);
            const result = await response.json();
            
            historyTableBody.innerHTML = ''; // Clear existing
            if (result.success && result.vouchers.length > 0) {
                result.vouchers.forEach(v => {
                    const row = historyTableBody.insertRow();
                    row.innerHTML = `
                        <td>${v.client_name}</td>
                        <td>${v.house_title}</td>
                        <td>${v.client_email}</td>
                        <td>${new Date(v.sent_at).toLocaleString('fr-FR')}</td>
                        <td>${v.admin_username || 'N/A'}</td>
                    `;
                });
            } else {
                 historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center p-4">Aucun voucher n\'a encore été envoyé.</td></tr>';
            }
        } catch (error) {
            console.error("Failed to load voucher history:", error);
            historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger p-4">Erreur de chargement de l\'historique.</td></tr>';
        }
    }


    // --- Event Handlers & Calculations ---
    function calculateTotalPrice() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        const pricePerNight = parseFloat(pricePerNightInput.value) || 0;

        if (startDate && endDate && endDate > startDate && pricePerNight > 0) {
            const timeDiff = endDate.getTime() - startDate.getTime();
            const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
            if (nights > 0) {
                totalPriceInput.value = (nights * pricePerNight).toFixed(2);
            }
        } else {
            totalPriceInput.value = '0.00';
        }
    }

    propertySelect.addEventListener('change', () => {
        const selectedOption = propertySelect.options[propertySelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.price) {
            pricePerNightInput.value = parseFloat(selectedOption.dataset.price).toFixed(2);
            calculateTotalPrice();
        }
    });

    [startDateInput, endDateInput, pricePerNightInput].forEach(input => {
        input.addEventListener('change', calculateTotalPrice);
    });

    voucherForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        sendVoucherBtn.disabled = true;
        sendVoucherBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Envoi en cours...`;

        const formData = new FormData(voucherForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(VOUCHER_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if(result.success) {
                showSimpleAlert("Voucher envoyé avec succès!", "success");
                voucherForm.reset();
                loadVoucherHistory(); // Refresh the history table
            } else {
                showSimpleAlert(result.message || "Erreur lors de l'envoi du voucher.", "danger");
            }

        } catch (error) {
             showSimpleAlert("Erreur de communication avec le serveur.", "danger");
        } finally {
            sendVoucherBtn.disabled = false;
            sendVoucherBtn.innerHTML = `<i class="ph ph-paper-plane-tilt me-2"></i>Envoyer le Voucher par Email`;
        }
    });


    // --- Initial Load ---
    async function initializePage() {
        await loadHouses();
        const urlParams = new URLSearchParams(window.location.search);
        const reservationId = urlParams.get('reservation_id');
        if (reservationId) {
            loadReservationDetails(reservationId);
        }
        loadVoucherHistory(); // Also load history on init
    }

    initializePage();

    // --- Utility Functions ---
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
});
