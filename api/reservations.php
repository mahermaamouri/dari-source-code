<?php
// api/reservations.php
ob_start(); // Start output buffering

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/init.php';
require_once TEMPLATES_PATH . '/email_templates.php'; 

try {
    $errors = [];

    // Handle both JSON and FormData requests
    $input_data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $house_id = isset($input_data['house_id']) ? filter_var($input_data['house_id'], FILTER_VALIDATE_INT) : null;
    $client_name = trim(htmlspecialchars($input_data['client_name'] ?? $input_data['name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $client_email = trim($input_data['client_email'] ?? $input_data['email'] ?? '');
    $client_phone = trim(preg_replace('/[^0-9+\-\s()]/', '', $input_data['client_phone'] ?? $input_data['phone'] ?? ''));
    $start_date_str = trim($input_data['start_date'] ?? '');
    $end_date_str = trim($input_data['end_date'] ?? '');
    $total_price = isset($input_data['total_price']) ? floatval($input_data['total_price']) : null;

    error_log("Received data: " . print_r($input_data, true));

    // Basic validation
    if (!$house_id) $errors[] = "ID de propriété invalide.";
    if (strlen($client_name) < 2) $errors[] = "Le nom complet est requis.";
    if (!filter_var($client_email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
    if (strlen(preg_replace('/\D/', '', $client_phone)) < 8) $errors[] = "Le numéro de téléphone est invalide.";
    if (!$start_date_str || !$end_date_str) $errors[] = "Les dates de début et de fin sont requises.";
    if ($total_price === null || $total_price <= 0) $errors[] = "Le prix total est invalide.";

    // Date validation
    if (empty($errors)) {
        $startDateObj = DateTime::createFromFormat('Y-m-d', $start_date_str);
        $endDateObj = DateTime::createFromFormat('Y-m-d', $end_date_str);
        if (!$startDateObj || !$endDateObj) {
            $errors[] = "Format de date invalide.";
        } else {
            $startDateObj->setTime(0, 0, 0);
            $endDateObj->setTime(0, 0, 0);
            $today = new DateTime('today');

            if ($startDateObj >= $endDateObj) {
                $errors[] = "La date de fin doit être après la date de début.";
            } elseif ($startDateObj < $today) {
                $errors[] = "La date de début ne peut pas être dans le passé.";
            }
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode(' ', $errors), 400);
    }

    // Use parsed dates
    $start_date = $startDateObj->format('Y-m-d');
    $end_date = $endDateObj->format('Y-m-d');

    global $pdo;

    // Check for overlapping confirmed reservations
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Reservations WHERE house_id = :house_id AND status = 'confirmed' AND (:start_date < end_date) AND (:end_date > start_date)");
    $stmt_check->execute([
        ':house_id' => $house_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("Désolé, cette propriété est déjà réservée pour les dates sélectionnées.", 409);
    }

    // Insert reservation
    $stmt = $pdo->prepare("INSERT INTO Reservations (house_id, client_name, client_email, client_phone, start_date, end_date, total_price, status) VALUES (:house_id, :client_name, :client_email, :client_phone, :start_date, :end_date, :total_price, 'pending')");
    $stmt->execute([
        ':house_id' => $house_id,
        ':client_name' => $client_name,
        ':client_email' => $client_email,
        ':client_phone' => $client_phone,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
        ':total_price' => $total_price
    ]);
    $reservation_id = $pdo->lastInsertId();

    // Fetch house title
    $stmt_house = $pdo->prepare("SELECT title FROM Houses WHERE house_id = :house_id");
    $stmt_house->execute([':house_id' => $house_id]);
    $house_title = $stmt_house->fetchColumn();

    // Format dates for email
    $formatted_start_date = $startDateObj->format('d/m/Y');
    $formatted_end_date = $endDateObj->format('d/m/Y');

    // --- MODIFICATION ---
    // The mail() functions below are commented out as they are likely causing the error message on form submission.
    // This happens if the mail server is not configured on your hosting (Plesk).
    // To re-enable emails, configure your server's mail settings in php.ini or use a library like PHPMailer.
    
    // // Send confirmation email to client
    // $email_data_user = [
    //     'client_name' => $client_name,
    //     'house_title' => $house_title,
    //     'start_date' => $formatted_start_date,
    //     'end_date' => $formatted_end_date,
    //     'total_price' => $total_price,
    //     'client_phone' => $client_phone
    // ];
    // $user_email_body = get_user_reservation_email_body($email_data_user);
    // @mail($client_email, "Confirmation de votre demande de réservation", $user_email_body, "From: no-reply@dari.tn\r\nContent-Type: text/html; charset=UTF-8");

    // // Send notification email to admin
    // $email_data_admin = [
    //     'client_name' => $client_name,
    //     'client_email' => $client_email,
    //     'client_phone' => $client_phone,
    //     'house_title' => $house_title,
    //     'start_date' => $formatted_start_date,
    //     'end_date' => $formatted_end_date,
    //     'total_price' => $total_price,
    //     'reservation_id' => $reservation_id
    // ];
    // $admin_email_body = get_admin_notification_email_body($email_data_admin);
    // @mail("admin@dari.tn", "Nouvelle demande de réservation (#$reservation_id)", $admin_email_body, "From: no-reply@dari.tn\r\nContent-Type: text/html; charset=UTF-8");

    error_log("Reservation successful: ID " . $reservation_id);
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Votre demande de réservation a été envoyée avec succès! Nous vous contacterons bientôt.',
        'reservation_id' => $reservation_id
    ]);
} catch (Exception $e) {
    ob_clean();
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    error_log("Reservation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}