<?php
// api/analytics_tracker.php
// This new endpoint handles logging user interactions for analytics.

ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

// We don't need admin auth for this public-facing tracker.

global $pdo;
if (!$pdo) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$eventType = $data['eventType'] ?? null;
$payload = $data['payload'] ?? [];
$sessionId = $data['sessionId'] ?? null;

if (!$eventType || !$sessionId) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing eventType or sessionId.']);
    exit;
}

try {
    // First, ensure the session exists in the User_Sessions table
    $stmt_session = $pdo->prepare("INSERT IGNORE INTO User_Sessions (session_identifier, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt_session->execute([
        $sessionId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    switch ($eventType) {
        case 'house_view':
        case 'house_click':
            $houseId = filter_var($payload['houseId'] ?? null, FILTER_VALIDATE_INT);
            if ($houseId) {
                $sql = "INSERT INTO House_Analytics (house_id, session_id, event_type) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$houseId, $sessionId, $eventType]);
            }
            break;

        case 'search':
            $sql = "INSERT INTO Search_Analytics (session_id, start_date, end_date, bedrooms, bathrooms, amenities) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $sessionId,
                $payload['startDate'] ?? null,
                $payload['endDate'] ?? null,
                !empty($payload['bedrooms']) ? implode(',', $payload['bedrooms']) : null,
                !empty($payload['bathrooms']) ? implode(',', $payload['bathrooms']) : null,
                !empty($payload['amenities']) ? implode(',', $payload['amenities']) : null,
            ]);
            break;

        default:
            throw new Exception('Unknown event type.', 400);
    }
    
    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    error_log("Analytics Tracker API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    ob_end_flush();
}
