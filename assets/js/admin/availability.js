// /assets/js/admin/availability.js
document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Element Selectors ---
    const houseSelector = document.getElementById('houseSelector');
    const calendarContainer = document.getElementById('calendar-container');
    const calendarPlaceholder = document.getElementById('calendar-placeholder');
    const calendarEl = document.getElementById('availability-calendar');
    const calendarHouseTitle = document.getElementById('calendar-house-title');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    // Edit Form Elements (formerly in modal)
    const editFormContainer = document.getElementById('edit-form-container');
    const availabilityForm = document.getElementById('availabilityForm');
    const houseIdInput = document.getElementById('houseId');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const priceInput = document.getElementById('price');
    const statusInput = document.getElementById('status');
    const selectedDatesDisplay = document.getElementById('selected-dates');
    const saveAvailabilityBtn = document.getElementById('saveAvailability');
    const cancelEditBtn = document.getElementById('cancelEdit');

    let flatpickrInstance = null;
    
    const hideEditForm = () => {
        editFormContainer.classList.remove('visible');
        availabilityForm.reset();
    };

    // --- Core Functions ---
    const loadAvailability = async (houseId) => {
        if (!houseId) {
            calendarContainer.style.display = 'none';
            calendarPlaceholder.style.display = 'block';
            hideEditForm();
            return;
        }

        calendarPlaceholder.style.display = 'none';
        calendarContainer.style.display = 'block';
        calendarEl.style.display = 'none';
        loadingSpinner.style.display = 'block';
        hideEditForm();
        
        const selectedHouseText = houseSelector.options[houseSelector.selectedIndex].text;
        calendarHouseTitle.textContent = `Calendrier pour: ${selectedHouseText}`;

        try {
            const response = await fetch(`../api/admin_availability_api.php?action=get_for_house&house_id=${houseId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            
            initializeCalendar(houseId, data.availability, data.reservations);
            calendarEl.style.display = 'block';

        } catch (error) {
            console.error('Error fetching availability:', error);
            calendarEl.innerHTML = '<div class="alert alert-danger">Impossible de charger les données du calendrier.</div>';
            calendarEl.style.display = 'block';
        } finally {
            loadingSpinner.style.display = 'none';
        }
    };

    const initializeCalendar = (houseId, availabilityData, reservationData) => {
        if (flatpickrInstance) {
            flatpickrInstance.destroy();
        }

        flatpickrInstance = flatpickr(calendarEl, {
            mode: "range",
            inline: true,
            showMonths: 2,
            dateFormat: "Y-m-d",
            locale: "fr",
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const date = dayElem.dateObj.toISOString().split('T')[0];

                // Check for reservations first (they have higher priority)
                for (const res of reservationData) {
                    const startDate = new Date(res.start_date + 'T00:00:00');
                    const endDate = new Date(res.end_date + 'T00:00:00');
                    const currentDate = dayElem.dateObj;
                    
                    if (currentDate >= startDate && currentDate < endDate) {
                        if (res.status === 'confirmed') {
                            dayElem.classList.add('day-confirmed');
                        } else if (res.status === 'pending') {
                            dayElem.classList.add('day-pending');
                        }
                        return; // Day is colored, no need to check further
                    }
                }

                // If no reservation, check for custom availability from admin
                if (availabilityData[date]) {
                    const dayData = availabilityData[date];
                    if (dayData.status === 'unavailable') {
                        dayElem.classList.add('day-unavailable');
                    }
                    
                    const priceTag = document.createElement('span');
                    priceTag.className = 'day-price';
                    priceTag.textContent = `${parseInt(dayData.price)} TND`;
                    dayElem.appendChild(priceTag);
                }
            },
            onClose: (selectedDates) => {
                // When a date range is selected, show the edit form
                if (selectedDates.length === 2) {
                    const start = selectedDates[0];
                    const end = selectedDates[1];

                    houseIdInput.value = houseId;
                    startDateInput.value = start.toISOString().split('T')[0];
                    endDateInput.value = end.toISOString().split('T')[0];
                    
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    selectedDatesDisplay.textContent = `${start.toLocaleDateString('fr-FR', options)} - ${end.toLocaleDateString('fr-FR', options)}`;

                    editFormContainer.classList.add('visible');
                    priceInput.focus();
                }
            }
        });
    };

    // --- Event Listeners ---
    houseSelector.addEventListener('change', () => {
        loadAvailability(houseSelector.value);
    });

    saveAvailabilityBtn.addEventListener('click', async () => {
        const formData = new FormData(availabilityForm);
        const data = Object.fromEntries(formData.entries());
        saveAvailabilityBtn.disabled = true;
        saveAvailabilityBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';

        try {
            const response = await fetch('../api/admin_availability_api.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            
            const result = await response.json();
            if(result.success) {
                hideEditForm();
                loadAvailability(data.house_id); // Reload calendar to show changes
            } else {
                alert('Error saving data: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Failed to save availability:', error);
            alert('An error occurred while saving.');
        } finally {
            saveAvailabilityBtn.disabled = false;
            saveAvailabilityBtn.textContent = 'Enregistrer les Modifications';
        }
    });
    
    cancelEditBtn.addEventListener('click', () => {
        hideEditForm();
        if (flatpickrInstance) {
            flatpickrInstance.clear(); // Clears the selection on the calendar
        }
    });
});