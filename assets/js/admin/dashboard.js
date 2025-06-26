// assets/js/admin/dashboard.js

// Chart instances
let bookingsChart = null;
let propertiesChart = null;

function formatCurrency(value) {
    const num = parseFloat(value) || 0;
    return num.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' TND';
}

function initDashboardCharts() {
    const bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx) {
        bookingsChart = new Chart(bookingsCtx.getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Réservations', data: [], borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: true }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }

    const propertiesCtx = document.getElementById('propertiesChart');
    if (propertiesCtx) {
        propertiesChart = new Chart(propertiesCtx.getContext('2d'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Réservations', data: [], backgroundColor: '#14B8A6' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }
}

function populateStatCards(stats) {
    if (!stats) return;
    document.getElementById('statTotalProperties').textContent = stats.total_properties || '0';
    document.getElementById('statTotalReservations').textContent = stats.total_reservations || '0';
    document.getElementById('statPendingReservations').textContent = stats.pending_reservations || '0';
    document.getElementById('statTotalRevenue').textContent = formatCurrency(stats.confirmed_revenue);
    // You can add a new card for potential revenue if you like
    // document.getElementById('statPotentialRevenue').textContent = formatCurrency(stats.potential_revenue);
}

function populateRecentBookings(reservations) {
    const tableBody = document.getElementById('recentBookingsTableBody');
    if (!tableBody) return;
    tableBody.innerHTML = '';

    if (!reservations || reservations.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">Aucune réservation récente.</td></tr>`;
        return;
    }

    const statusClasses = {
        pending: 'bg-warning-subtle text-warning-emphasis',
        confirmed: 'bg-success-subtle text-success-emphasis',
        cancelled: 'bg-danger-subtle text-danger-emphasis'
    };
    const statusLabels = { pending: 'En attente', confirmed: 'Confirmée', cancelled: 'Annulée' };

    reservations.forEach(res => {
        const row = tableBody.insertRow();
        row.innerHTML = `
            <td>${res.client_name}</td>
            <td class="text-truncate" style="max-width: 150px;">${res.house_title}</td>
            <td>${new Date(res.start_date).toLocaleDateString('fr-FR')} - ${new Date(res.end_date).toLocaleDateString('fr-FR')}</td>
            <td><span class="badge rounded-pill ${statusClasses[res.status] || ''}">${statusLabels[res.status] || res.status}</span></td>
            <td>${formatCurrency(res.total_price)}</td>
            <td>
                <a href="${window.basePath}admin/reservations.php" class="btn btn-sm btn-outline-primary">
                    <i class="ph ph-eye"></i>
                </a>
            </td>
        `;
    });
}

function updateCharts(chartData) {
    if (!chartData) return;

    if (bookingsChart && chartData.monthly_bookings) {
        bookingsChart.data.labels = chartData.monthly_bookings.labels;
        bookingsChart.data.datasets[0].data = chartData.monthly_bookings.data;
        bookingsChart.update();
    }

    if (propertiesChart && chartData.top_properties) {
        propertiesChart.data.labels = chartData.top_properties.labels;
        propertiesChart.data.datasets[0].data = chartData.top_properties.data;
        propertiesChart.update();
    }
}

async function loadDashboardData() {
    try {
        const response = await fetch(window.basePath + 'api/admin_dashboard_api.php');
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const result = await response.json();

        if (result.success) {
            populateStatCards(result.stats);
            populateRecentBookings(result.recent_reservations);
            updateCharts(result.charts);
        } else {
            console.error("Failed to load dashboard data:", result.message);
        }
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
        document.getElementById('recentBookingsTableBody').innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Erreur de chargement des données.</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initDashboardCharts();
    loadDashboardData();
});
