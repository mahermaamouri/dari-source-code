<?php
// api/admin_dashboard_api.php
// This endpoint provides aggregated data for the admin dashboard and analytics.

ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

// Admin Authentication Check
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}

global $pdo;
if (!$pdo) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

try {
    $response = [
        'success' => true,
        'stats' => [],
        'charts' => [],
        'recent_reservations' => [],
        'analytics_tables' => [] // Ensure this key always exists
    ];

    // --- 1. Key Performance Indicators (KPIs) ---
    $stmt_kpi = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM Houses) as total_properties,
            (SELECT COUNT(*) FROM Reservations) as total_reservations,
            (SELECT COUNT(*) FROM Reservations WHERE status = 'pending') as pending_reservations,
            (SELECT SUM(total_price) FROM Reservations WHERE status = 'confirmed') as confirmed_revenue,
            (SELECT SUM(total_price) FROM Reservations WHERE status = 'pending') as potential_revenue
    ");
    $response['stats'] = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

    // --- 2. Recent Reservations ---
    $stmt_recent = $pdo->query("
        SELECT r.reservation_id, r.client_name, r.start_date, r.end_date, r.status, r.total_price, h.title as house_title
        FROM Reservations r
        JOIN Houses h ON r.house_id = h.house_id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $response['recent_reservations'] = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. Monthly Bookings Chart ---
    $stmt_monthly = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM Reservations
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $monthly_bookings = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);
    $response['charts']['monthly_bookings'] = [
        'labels' => array_column($monthly_bookings, 'month'),
        'data' => array_column($monthly_bookings, 'count')
    ];
    
    // --- 4. Most Booked Properties Chart ---
    $stmt_top_props = $pdo->query("
        SELECT h.title, COUNT(r.reservation_id) as booking_count
        FROM Reservations r
        JOIN Houses h ON r.house_id = h.house_id
        GROUP BY r.house_id, h.title
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $top_properties = $stmt_top_props->fetchAll(PDO::FETCH_ASSOC);
    $response['charts']['top_properties'] = [
        'labels' => array_column($top_properties, 'title'),
        'data' => array_column($top_properties, 'booking_count')
    ];


    // --- Analytics Page Data ---
    // Total views and clicks - FIXED event_type names
    $stmt_views_clicks = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM House_Analytics WHERE event_type = 'house_view') as total_views,
            (SELECT COUNT(*) FROM House_Analytics WHERE event_type = 'house_click') as total_clicks
    ");
    $response['stats']['analytics'] = $stmt_views_clicks->fetch(PDO::FETCH_ASSOC);

    // Top Viewed Houses - FIXED event_type name
    $stmt_top_viewed = $pdo->query("
        SELECT h.title, COUNT(ha.analytic_id) as view_count
        FROM House_Analytics ha
        JOIN Houses h ON ha.house_id = h.house_id
        WHERE ha.event_type = 'house_view'
        GROUP BY ha.house_id, h.title
        ORDER BY view_count DESC
        LIMIT 10
    ");
    $response['analytics_tables']['top_viewed'] = $stmt_top_viewed->fetchAll(PDO::FETCH_ASSOC);

    // Top Clicked Houses - FIXED event_type name
    $stmt_top_clicked = $pdo->query("
        SELECT h.title, COUNT(ha.analytic_id) as click_count
        FROM House_Analytics ha
        JOIN Houses h ON ha.house_id = h.house_id
        WHERE ha.event_type = 'house_click'
        GROUP BY ha.house_id, h.title
        ORDER BY click_count DESC
        LIMIT 10
    ");
    $response['analytics_tables']['top_clicked'] = $stmt_top_clicked->fetchAll(PDO::FETCH_ASSOC);

    // Top Searched Date Ranges
    $stmt_top_searches = $pdo->query("
        SELECT start_date, end_date, COUNT(*) as search_count
        FROM Search_Analytics
        WHERE start_date IS NOT NULL AND end_date IS NOT NULL
        GROUP BY start_date, end_date
        ORDER BY search_count DESC
        LIMIT 10
    ");
     $response['analytics_tables']['top_searches'] = $stmt_top_searches->fetchAll(PDO::FETCH_ASSOC);


    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("Admin Dashboard API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An internal error occurred.']);
} finally {
    ob_end_flush();
}
