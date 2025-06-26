<?php
// api/admin_features_api.php

// --- Start Output Buffering ---
// This captures all script output to prevent any stray characters or
// PHP notices from breaking the JSON response.
ob_start();

// --- Header and Initialization ---
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

// --- Admin Authentication Check ---
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    ob_clean(); // Clean buffer before sending error
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}


global $pdo;
if (!$pdo) {
    ob_clean(); // Clean buffer before sending error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_REQUEST['action'] ?? null;

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT feature_id, feature_name, icon_class FROM Features ORDER BY feature_name ASC");
            $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response_data = ['success' => true, 'features' => $features];
            break;

        case 'get':
            $feature_id = filter_input(INPUT_GET, 'feature_id', FILTER_VALIDATE_INT);
            if (!$feature_id) {
                throw new Exception("ID d'équipement invalide.", 400);
            }
            $stmt = $pdo->prepare("SELECT feature_id, feature_name, icon_class FROM Features WHERE feature_id = :id");
            $stmt->execute([':id' => $feature_id]);
            $feature = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$feature) {
                throw new Exception("Équipement non trouvé.", 404);
            }
            $response_data = ['success' => true, 'feature' => $feature];
            break;

        case 'add':
            $feature_name = trim(filter_input(INPUT_POST, 'feature_name', FILTER_SANITIZE_STRING));
            $icon_class = trim(filter_input(INPUT_POST, 'icon_class', FILTER_SANITIZE_STRING)) ?: null;

            if (empty($feature_name)) {
                throw new Exception("Le nom de l'équipement est requis.", 400);
            }

            $checkStmt = $pdo->prepare("SELECT feature_id FROM Features WHERE feature_name = :name");
            $checkStmt->execute([':name' => $feature_name]);
            if ($checkStmt->fetch()) {
                 throw new Exception("Un équipement avec ce nom existe déjà.", 409); // Conflict
            }

            $stmt = $pdo->prepare("INSERT INTO Features (feature_name, icon_class) VALUES (:name, :icon)");
            $stmt->execute([':name' => $feature_name, ':icon' => $icon_class]);
            $new_id = $pdo->lastInsertId();
            $response_data = ['success' => true, 'message' => 'Équipement ajouté avec succès.', 'feature_id' => $new_id];
            break;

        case 'update':
            $feature_id = filter_input(INPUT_POST, 'feature_id', FILTER_VALIDATE_INT);
            $feature_name = trim(filter_input(INPUT_POST, 'feature_name', FILTER_SANITIZE_STRING));
            $icon_class = trim(filter_input(INPUT_POST, 'icon_class', FILTER_SANITIZE_STRING)) ?: null;

            if (empty($feature_id) || empty($feature_name)) {
                throw new Exception("ID et nom de l'équipement sont requis.", 400);
            }

            $checkStmt = $pdo->prepare("SELECT feature_id FROM Features WHERE feature_name = :name AND feature_id != :id");
            $checkStmt->execute([':name' => $feature_name, ':id' => $feature_id]);
            if ($checkStmt->fetch()) {
                 throw new Exception("Un autre équipement avec ce nom existe déjà.", 409); // Conflict
            }

            $stmt = $pdo->prepare("UPDATE Features SET feature_name = :name, icon_class = :icon WHERE feature_id = :id");
            $stmt->execute([':name' => $feature_name, ':icon' => $icon_class, ':id' => $feature_id]);
            $response_data = ['success' => true, 'message' => 'Équipement mis à jour avec succès.'];
            break;

        case 'delete':
            $feature_id = filter_input(INPUT_POST, 'feature_id', FILTER_VALIDATE_INT);
            if (empty($feature_id)) {
                throw new Exception("ID d'équipement requis pour la suppression.", 400);
            }

            $stmt = $pdo->prepare("DELETE FROM Features WHERE feature_id = :id");
            $stmt->execute([':id' => $feature_id]);
            if ($stmt->rowCount() > 0) {
                $response_data = ['success' => true, 'message' => 'Équipement supprimé avec succès.'];
            } else {
                throw new Exception("Équipement non trouvé ou déjà supprimé.", 404);
            }
            break;

        default:
            throw new Exception('Action non reconnue.', 400);
    }
    
    // --- Send final, clean response ---
    ob_clean(); // Clear the buffer of any potential notices or whitespace
    echo json_encode($response_data); // Echo the clean JSON

} catch (Exception $e) {
    ob_clean(); // Also clean buffer on error
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    $error_detail = (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) ? $e->getMessage() : 'Une erreur est survenue.';
    error_log("Admin Features API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'detail' => $error_detail]);
} finally {
    ob_end_flush(); // Send the output and turn off buffering
}
?>
