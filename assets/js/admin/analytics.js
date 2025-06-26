// assets/js/admin/analytics.js

document.addEventListener('DOMContentLoaded', function () {

    /**
     * Populates the main statistics cards at the top of the page.
     * @param {object} stats - The statistics object from the API.
     */
    function populateAnalyticsCards(stats) {
        if(!stats || !stats.analytics) return;
        
        const totalViewsEl = document.getElementById('statTotalViews');
        const totalClicksEl = document.getElementById('statTotalClicks');

        // Use nullish coalescing '??' to correctly display 0 values
        if(totalViewsEl) totalViewsEl.textContent = stats.analytics.total_views ?? '0';
        if(totalClicksEl) totalClicksEl.textContent = stats.analytics.total_clicks ?? '0';
    }

    /**
     * A robust function to populate any table with data.
     * @param {string} tableBodyId - The ID of the <tbody> element.
     * @param {Array<object>} data - The array of data objects to display.
     * @param {Array<object>} columns - An array defining the columns {key, formatter}.
     */
    function populateAnalyticsTable(tableBodyId, data, columns) {
        const tableBody = document.getElementById(tableBodyId);
        if (!tableBody) {
            console.error(`Table body with ID '${tableBodyId}' not found.`);
            return;
        }

        tableBody.innerHTML = ''; // Clear previous content

        if (!data || data.length === 0) {
            const colSpan = columns.length;
            tableBody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-3">Aucune donnée disponible pour le moment.</td></tr>`;
            return;
        }

        data.forEach(item => {
            const row = tableBody.insertRow();
            columns.forEach(col => {
                const cell = row.insertCell();
                let value = item[col.key];
                
                // Use a formatter function for special data types like dates
                if (col.formatter) {
                    value = col.formatter(value);
                }
                
                // Use nullish coalescing '??' to correctly display 0 values
                cell.textContent = value ?? 'N/A';
            });
        });
    }

    /**
     * Main function to fetch all analytics data from the API and populate the page.
     */
    async function loadAnalyticsData() {
        try {
            const response = await fetch(window.basePath + 'api/admin_dashboard_api.php');
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                // Populate the main stat cards
                populateAnalyticsCards(result.stats);
                
                // Populate the "Top Viewed" table
                populateAnalyticsTable('topViewedTable', result.analytics_tables.top_viewed, [
                    { key: 'title' },
                    { key: 'view_count' }
                ]);

                // Populate the "Top Clicked" table
                populateAnalyticsTable('topClickedTable', result.analytics_tables.top_clicked, [
                    { key: 'title' },
                    { key: 'click_count' }
                ]);

                // Populate the "Top Searches" table with a date formatter
                populateAnalyticsTable('topSearchesTable', result.analytics_tables.top_searches, [
                    { key: 'start_date', formatter: (d) => d ? new Date(d).toLocaleDateString('fr-CA') : 'N/A' },
                    { key: 'end_date', formatter: (d) => d ? new Date(d).toLocaleDateString('fr-CA') : 'N/A' },
                    { key: 'search_count' }
                ]);
                
            } else {
                console.error("Failed to load analytics data:", result.message);
            }
        } catch (error) {
            console.error("Error fetching analytics data:", error);
            // Display an error in one of the tables as a visual indicator
            const errorTable = document.getElementById('topViewedTable');
            if(errorTable) {
                errorTable.innerHTML = `<tr><td colspan="2" class="text-center text-danger py-3">Erreur de chargement des données.</td></tr>`;
            }
        }
    }

    // Initial load
    loadAnalyticsData();
});
