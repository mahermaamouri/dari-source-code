<?php
// api/features_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

global $pdo;
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? 'getAll'; // Default action

try {
    if ($action === 'getAll') {
        // CORRECTED: Table name is capitalized
        $stmt = $pdo->query("SELECT feature_id, feature_name, icon_class FROM Features ORDER BY feature_name ASC");
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($features);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action for features.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    $error_detail = DEVELOPMENT_MODE ? $e->getMessage() : 'A database error occurred fetching features.';
    echo json_encode(['error' => 'Database query failed.', 'detail' => $error_detail]);
}
?>
