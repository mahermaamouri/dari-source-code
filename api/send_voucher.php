<?php
// api/send_voucher.php
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
$admin_id = $_SESSION['admin_id'];
$action = $_REQUEST['action'] ?? 'send'; // Default action is 'send'

try {
    if ($action === 'send') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['client_email']) || empty($data['house_id'])) {
            throw new Exception('Email du client et propriété sont requis.', 400);
        }

        // Fetch house title to include in the email
        $stmt_house = $pdo->prepare("SELECT title FROM Houses WHERE house_id = ?");
        $stmt_house->execute([$data['house_id']]);
        $house = $stmt_house->fetch();
        $data['house_title'] = $house['title'] ?? 'Propriété non trouvée';

        // Prepare email
        $to = $data['client_email'];
        $subject = 'Votre Voucher de Réservation pour ' . $data['house_title'];
        $body = get_voucher_email_body($data);
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Dari - Service Réservation <reservation@dari.tn>" . "\r\n";

        if (mail($to, $subject, $body, $headers)) {
            // --- SAVE TO DATABASE ---
            $pdo->beginTransaction();

            // 1. Insert into Vouchers table
            $sql_voucher = "INSERT INTO Vouchers (reservation_id, client_name, client_email, house_id, total_price, advance_payment, payment_status, sent_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_voucher = $pdo->prepare($sql_voucher);
            $stmt_voucher->execute([
                $data['reservation_id'] ?: null,
                $data['client_name'],
                $data['client_email'],
                $data['house_id'],
                $data['total_price'],
                $data['advance_payment'],
                $data['payment_status'],
                $admin_id
            ]);

            // 2. Update Reservations table if reservation_id exists
            if (!empty($data['reservation_id'])) {
                $sql_res = "UPDATE Reservations SET voucher_sent_at = CURRENT_TIMESTAMP WHERE reservation_id = ?";
                $stmt_res = $pdo->prepare($sql_res);
                $stmt_res->execute([$data['reservation_id']]);
            }

            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Voucher envoyé et enregistré avec succès.']);
        } else {
            throw new Exception("Le serveur n'a pas pu envoyer l'email.", 500);
        }

    } elseif ($action === 'list') {
        $stmt = $pdo->query("
            SELECT v.*, h.title as house_title, a.username as admin_username
            FROM Vouchers v
            JOIN Houses h ON v.house_id = h.house_id
            LEFT JOIN Admins a ON v.sent_by_admin_id = a.admin_id
            ORDER BY v.sent_at DESC
            LIMIT 50
        ");
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'vouchers' => $vouchers]);
    } else {
        throw new Exception("Action non reconnue.", 400);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    http_response_code(500);
    error_log("Voucher API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    ob_end_flush();
}
