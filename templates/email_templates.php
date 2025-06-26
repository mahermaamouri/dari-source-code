<?php
// templates/email_templates.php

/**
 * Returns the HTML email body for the user's reservation request confirmation.
 *
 * @param array $data An associative array containing reservation details:
 * 'client_name', 'house_title', 'start_date', 'end_date', 'total_price', 'client_phone'
 * @return string The HTML email body.
 */
function get_user_reservation_email_body($data) {
    $name = htmlspecialchars($data['client_name']);
    $houseTitle = htmlspecialchars($data['house_title']);
    $startDate = htmlspecialchars($data['start_date']);
    $endDate = htmlspecialchars($data['end_date']);
    $totalPrice = number_format($data['total_price'], 2, ',', ' ');
    $phone = htmlspecialchars($data['client_phone']);
    $logoUrl = rtrim($_SERVER['HTTP_HOST'], '/') . BASE_PATH . 'assets/images/logo.png';


    $nights = (new DateTime($startDate))->diff(new DateTime($endDate))->days;

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; background-color: #f9fafb; color: #1F2937; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .content { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header { text-align: center; margin-bottom: 30px; }
    .logo-container { margin-bottom: 10px; }
    .logo { color: #2A6478; font-size: 24px; font-weight: bold; }
    .details { background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .footer { font-size: 14px; line-height: 1.5; color: #6B7280; }
</style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="header">
                <div class="logo-container">
                     <img src="http://{$logoUrl}" alt="Dari Logo" style="width: 50px; height: 50px; border-radius: 50%;" />
                </div>
                <div class="logo">Dari</div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p style="margin: 0 0 15px 0; font-size: 18px; font-weight: bold;">Bonjour {$name},</p>
                <p style="margin: 0 0 20px 0; line-height: 1.5;">Nous avons bien reçu votre demande de réservation. Notre équipe va traiter votre demande et vous contactera par téléphone dans les plus brefs délais pour confirmer votre réservation.</p>
            </div>

            <div class="details">
                <div style="font-weight: bold; margin-bottom: 15px;">Détails de la réservation :</div>
                <p><strong>Propriété:</strong> {$houseTitle}</p>
                <p><strong>Dates:</strong> {$startDate} - {$endDate} ({$nights} nuits)</p>
                <p><strong>Prix total:</strong> {$totalPrice} TND</p>
            </div>

            <div class="footer">
                <p>Nous vous contacterons au <strong>{$phone}</strong> pour finaliser votre réservation.</p>
                <p>Si vous avez des questions, n'hésitez pas à nous contacter à mahdi.bouafif@gmail.com</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Returns the HTML email body for the admin notification of a new reservation.
 *
 * @param array $data An associative array containing reservation details:
 * 'client_name', 'client_email', 'client_phone', 'house_title', 'start_date', 'end_date', 'total_price', 'reservation_id'
 * @return string The HTML email body.
 */
function get_admin_notification_email_body($data) {
    $name = htmlspecialchars($data['client_name']);
    $email = htmlspecialchars($data['client_email']);
    $phone = htmlspecialchars($data['client_phone']);
    $houseTitle = htmlspecialchars($data['house_title']);
    $startDate = htmlspecialchars($data['start_date']);
    $endDate = htmlspecialchars($data['end_date']);
    $totalPrice = number_format($data['total_price'], 2, ',', ' ');
    $reservationLink = BASE_PATH . 'admin/reservations.php';
    $logoUrl = rtrim($_SERVER['HTTP_HOST'], '/') . BASE_PATH . 'assets/images/logo.png';


    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; background-color: #f9fafb; color: #1F2937; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .content { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .header { text-align: center; margin-bottom: 20px; }
    .header-title { font-size: 20px; font-weight: bold; color: #2A6478; margin-top: 10px; }
    .details { background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .button { display: inline-block; padding: 10px 20px; background-color: #2A6478; color: #ffffff; text-decoration: none; border-radius: 5px; }
</style>
</head>
<body>
    <div class="container">
        <div class="content">
             <div class="header">
                <img src="http://{$logoUrl}" alt="Dari Logo" style="width: 50px; height: 50px; border-radius: 50%;" />
                <div class="header-title">Nouvelle Demande de Réservation!</div>
            </div>
            <p>Une nouvelle demande de réservation a été placée sur le site.</p>
            
            <div class="details">
                <div style="font-weight: bold; margin-bottom: 15px;">Détails du Client:</div>
                <p><strong>Nom:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Téléphone:</strong> {$phone}</p>
            </div>
            
            <div class="details">
                <div style="font-weight: bold; margin-bottom: 15px;">Détails de la Réservation:</div>
                <p><strong>Propriété:</strong> {$houseTitle}</p>
                <p><strong>Dates:</strong> {$startDate} - {$endDate}</p>
                <p><strong>Prix total:</strong> {$totalPrice} TND</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{$reservationLink}" class="button">Voir dans l'Admin</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}
/**
 * NEW: Returns the HTML email body for the official Reservation Voucher.
 *
 * @param array $data An associative array containing voucher details.
 * @return string The HTML email body.
 */
function get_voucher_email_body($data) {
    // Sanitize and format data
    $clientName = htmlspecialchars($data['client_name']);
    $houseTitle = htmlspecialchars($data['house_title']);
    $startDate = (new DateTime($data['start_date']))->format('d/m/Y');
    $endDate = (new DateTime($data['end_date']))->format('d/m/Y');
    $nights = (new DateTime($data['start_date']))->diff(new DateTime($data['end_date']))->days;
    $totalPrice = number_format($data['total_price'], 2, ',', ' ');
    $advancePayment = number_format($data['advance_payment'], 2, ',', ' ');
    $remainingPayment = number_format($data['total_price'] - $data['advance_payment'], 2, ',', ' ');
    $paymentStatus = htmlspecialchars($data['payment_status']);
    $logoUrl = rtrim($_SERVER['HTTP_HOST'], '/') . BASE_PATH . 'assets/images/logo.png';
    $reservationId = htmlspecialchars($data['reservation_id']);

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f0f2f5; color: #1c1e21; margin: 0; padding: 20px; }
    .voucher-container { max-width: 680px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dddfe2; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background-color: #2A6478; color: #ffffff; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .header h1 { margin: 0; font-size: 24px; }
    .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; }
    .content { padding: 30px; }
    .section { margin-bottom: 25px; }
    .section-title { font-size: 16px; font-weight: 600; color: #2A6478; border-bottom: 2px solid #f0f2f5; padding-bottom: 8px; margin-bottom: 15px; }
    .detail-item { display: flex; justify-content: space-between; padding: 8px 0; font-size: 15px; border-bottom: 1px solid #f0f2f5; }
    .detail-item:last-child { border-bottom: none; }
    .detail-item .label { color: #606770; }
    .detail-item .value { font-weight: 500; color: #1c1e21; }
    .summary-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .summary-table td { padding: 10px; border: 1px solid #dddfe2; }
    .summary-table .total { font-weight: bold; font-size: 16px; }
    .footer { text-align: center; font-size: 12px; color: #606770; padding: 20px; }
</style>
</head>
<body>
    <div class="voucher-container">
        <div class="header">
            <img src="http://{$logoUrl}" alt="Dari Logo" style="width: 60px; height: 60px; border-radius: 50%; margin-bottom: 10px;" />
            <h1>Voucher de Réservation</h1>
            <p>Référence #{$reservationId}</p>
        </div>
        <div class="content">
            <div class="section">
                <p>Bonjour <strong>{$clientName}</strong>,</p>
                <p>Merci pour votre réservation. Veuillez trouver ci-dessous les détails de votre séjour. Ce document sert de confirmation.</p>
            </div>

            <div class="section">
                <div class="section-title">Détails du Séjour</div>
                <div class="detail-item"><span class="label">Propriété : </span><span class="value">{$houseTitle}</span></div>
                <div class="detail-item"><span class="label">Arrivée : </span><span class="value">{$startDate}</span></div>
                <div class="detail-item"><span class="label">Départ : </span><span class="value">{$endDate}</span></div>
                <div class="detail-item"><span class="label">Nombre de nuits : </span><span class="value">{$nights}</span></div>
            </div>

            <div class="section">
                <div class="section-title">Résumé Financier</div>
                <table class="summary-table">
                    <tr>
                        <td class="label">Prix Total du Séjour</td>
                        <td class="value" style="text-align:right;">{$totalPrice} TND</td>
                    </tr>
                    <tr>
                        <td class="label">Acompte Payé</td>
                        <td class="value" style="text-align:right;">{$advancePayment} TND</td>
                    </tr>
                    <tr class="total">
                        <td class="label">Solde à Payer à l'Arrivée</td>
                        <td class="value" style="text-align:right;">{$remainingPayment} TND</td>
                    </tr>
                </table>
                 <p style="text-align:center; margin-top:15px; font-weight:bold; color:#1c1e21;">Statut : {$paymentStatus}</p>
            </div>

        </div>
        <div class="footer">
            Dari &copy; 2025 | mahdi.bouafif@gmail.com
        </div>
    </div>
</body>
</html>
HTML;
}

?>