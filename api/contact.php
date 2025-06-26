<?php
// api/contact.php
ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    // Collect and sanitize data
    $name = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim(htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'));
    $subject = trim(htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8'));
    $message_body = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));

    // Basic Validation
    $errors = [];
    if (empty($name)) $errors[] = "Le nom est requis.";
    if (empty($email)) $errors[] = "L'adresse email est invalide.";
    if (empty($subject)) $errors[] = "Le sujet est requis.";
    if (empty($message_body)) $errors[] = "Le message ne peut pas être vide.";

    if (!empty($errors)) {
        throw new Exception(implode(' ', $errors), 400);
    }

    // --- Attempt to send email ---
    $to_email = "admin@dari.tn"; // REPLACE WITH YOUR ADMIN EMAIL ADDRESS
    $email_subject = "Nouveau Message de Contact (Dari): " . $subject;

    $headers = "From: " . $name . " <" . $email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $email_content = "Vous avez reçu un nouveau message depuis le formulaire de contact de Dari :\n\n";
    $email_content .= "Nom: " . $name . "\n";
    $email_content .= "Email: " . $email . "\n";
    if (!empty($phone)) {
        $email_content .= "Téléphone: " . $phone . "\n";
    }
    $email_content .= "Sujet: " . $subject . "\n";
    $email_content .= "Message:\n" . $message_body . "\n\n";
    $email_content .= "----------------------\n";
    $email_content .= "Envoyé le: " . date('d/m/Y H:i:s') . "\n";

    if (mail($to_email, $email_subject, $email_content, $headers)) {
        $response_data = ['success' => true, 'message' => 'Merci! Votre message a été envoyé avec succès.'];
    } else {
        throw new Exception('Désolé, une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer plus tard.', 500);
    }
    
    ob_clean();
    echo json_encode($response_data);

} catch (Exception $e) {
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    error_log("Contact Form API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    ob_end_flush();
}
?>
