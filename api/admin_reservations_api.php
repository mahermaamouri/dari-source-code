<?php
// api/admin_reservations_api.php

ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

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

$action = $_REQUEST['action'] ?? null;

try {
    $response_data = [];

    switch ($action) {
        case 'list':
            $stmt = $pdo->query(
                "SELECT r.*, h.title as house_title 
                 FROM Reservations r 
                 JOIN Houses h ON r.house_id = h.house_id 
                 ORDER BY r.created_at DESC"
            );
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response_data = ['success' => true, 'reservations' => $reservations];
            break;
        
        // --- NEW CASE TO GET A SINGLE RESERVATION ---
        case 'get':
            $reservation_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$reservation_id) {
                throw new Exception("ID de réservation invalide.", 400);
            }
            $stmt = $pdo->prepare(
                "SELECT r.*, h.title as house_title, h.price_per_night 
                 FROM Reservations r 
                 JOIN Houses h ON r.house_id = h.house_id 
                 WHERE r.reservation_id = :id"
            );
            $stmt->execute([':id' => $reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reservation) {
                throw new Exception("Réservation non trouvée.", 404);
            }
            $response_data = ['success' => true, 'reservation' => $reservation];
            break;


        case 'add':
        case 'update':
            $isUpdate = ($action === 'update');
            $reservation_id = $isUpdate ? filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT) : null;
            if ($isUpdate && !$reservation_id) {
                throw new Exception("ID de réservation manquant pour la mise à jour.", 400);
            }

            // Sanitize all inputs
            $house_id = filter_input(INPUT_POST, 'house_id', FILTER_VALIDATE_INT);
            $client_name = trim(filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_STRING));
            $client_email = filter_input(INPUT_POST, 'client_email', FILTER_VALIDATE_EMAIL);
            $client_phone = trim(filter_input(INPUT_POST, 'client_phone', FILTER_SANITIZE_STRING));
            $start_date = trim(filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING));
            $end_date = trim(filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING));
            $total_price = filter_input(INPUT_POST, 'total_price', FILTER_VALIDATE_FLOAT);
            $advance_payment = filter_input(INPUT_POST, 'advance_payment', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.00;
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

            if (!$house_id || empty($client_name) || empty($client_email) || empty($start_date) || empty($end_date) || $total_price === false || empty($status)) {
                throw new Exception("Champs obligatoires manquants ou invalides.", 400);
            }

            $params = [
                ':house_id' => $house_id,
                ':client_name' => $client_name,
                ':client_email' => $client_email,
                ':client_phone' => $client_phone,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':total_price' => $total_price,
                ':advance_payment' => $advance_payment,
                ':status' => $status
            ];

            if ($isUpdate) {
                $params[':reservation_id'] = $reservation_id;
                $params[':admin_id'] = ($status === 'confirmed') ? $_SESSION['admin_id'] : null;
                $sql = "UPDATE Reservations SET house_id=:house_id, client_name=:client_name, client_email=:client_email, client_phone=:client_phone, start_date=:start_date, end_date=:end_date, total_price=:total_price, advance_payment=:advance_payment, status=:status, confirmed_by_admin_id=:admin_id WHERE reservation_id=:reservation_id";
            } else {
                $sql = "INSERT INTO Reservations (house_id, client_name, client_email, client_phone, start_date, end_date, total_price, advance_payment, status) VALUES (:house_id, :client_name, :client_email, :client_phone, :start_date, :end_date, :total_price, :advance_payment, :status)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $response_data = ['success' => true, 'message' => 'Réservation enregistrée avec succès.'];
            break;

        case 'delete':
            $reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
            if (!$reservation_id) {
                throw new Exception("ID de réservation invalide.", 400);
            }
            $stmt = $pdo->prepare("DELETE FROM Reservations WHERE reservation_id = :id");
            $stmt->execute([':id' => $reservation_id]);
            if ($stmt->rowCount() > 0) {
                $response_data = ['success' => true, 'message' => 'Réservation supprimée avec succès.'];
            } else {
                throw new Exception("Réservation non trouvée ou déjà supprimée.", 404);
            }
            break;

        default:
            throw new Exception('Action non reconnue.', 400);
    }
    
    ob_clean();
    echo json_encode($response_data);

} catch (Exception $e) {
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    error_log("Admin Reservations API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    ob_end_flush();
}
