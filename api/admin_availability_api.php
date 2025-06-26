<?php
// api/admin_availability_api.php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../admin/includes/auth_check.php';

global $pdo;
if (!$pdo) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? null;

try {
    if ($action === 'get_for_house') {
        $house_id = filter_input(INPUT_GET, 'house_id', FILTER_VALIDATE_INT);
        if (!$house_id) throw new Exception("House ID manquant.", 400);

        // Fetch custom daily prices and statuses from House_Availability
        $stmt_avail = $pdo->prepare("SELECT available_date, price, status FROM House_Availability WHERE house_id = ?");
        $stmt_avail->execute([$house_id]);
        $availability_rows = $stmt_avail->fetchAll(PDO::FETCH_ASSOC);

        $availability = [];
        foreach($availability_rows as $row) {
            $availability[$row['available_date']] = [
                'price' => $row['price'],
                'status' => $row['status']
            ];
        }

        // Fetch both 'confirmed' and 'pending' reservations and include their status
        $stmt_reservations = $pdo->prepare("SELECT start_date, end_date, status FROM Reservations WHERE house_id = ? AND (status = 'confirmed' OR status = 'pending')");
        $stmt_reservations->execute([$house_id]);
        $reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);
        
        $response_data = ['availability' => $availability, 'reservations' => $reservations];

    } elseif ($action === 'save') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) throw new Exception("Invalid input data.", 400);

        $house_id = filter_var($input['house_id'], FILTER_VALIDATE_INT);
        $start_date_str = $input['start_date'];
        $end_date_str = $input['end_date'];
        $price = !empty($input['price']) ? filter_var($input['price'], FILTER_VALIDATE_FLOAT) : null;
        $status = in_array($input['status'], ['available', 'unavailable']) ? $input['status'] : 'available';

        if (!$house_id || !$start_date_str || !$end_date_str) {
            throw new Exception("Paramètres requis manquants.", 400);
        }

        if ($price === null) {
            $stmt_default = $pdo->prepare("SELECT price_per_night FROM Houses WHERE house_id = ?");
            $stmt_default->execute([$house_id]);
            $price = $stmt_default->fetchColumn();
        }
        
        $current_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);

        $pdo->beginTransaction();
        
        $sql = "INSERT INTO House_Availability (house_id, available_date, price, status) 
                VALUES (:house_id, :available_date, :price, :status)
                ON DUPLICATE KEY UPDATE price = VALUES(price), status = VALUES(status)";
        $stmt = $pdo->prepare($sql);

        while ($current_date <= $end_date) {
            $stmt->execute([
                ':house_id' => $house_id,
                ':available_date' => $current_date->format('Y-m-d'),
                ':price' => $price,
                ':status' => $status
            ]);
            $current_date->modify('+1 day');
        }
        
        $pdo->commit();
        
        $response_data = ['success' => true];
    
    } else {
        throw new Exception('Action non reconnue.', 400);
    }

    ob_clean();
    echo json_encode($response_data);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    error_log("Admin Availability API Exception: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    ob_end_flush();
}