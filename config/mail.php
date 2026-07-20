<?php
/**
 * Mail Configuration using PHPMailer
 */

// Load PHPMailer classes manually since we didn't use Composer
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Parse .env file
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

// Fallbacks: getenv() (Railway) -> .env file -> placeholder
$gmailUser = getenv('GMAIL_USER') ?: ($env['GMAIL_USER'] ?? '');
$gmailPass = getenv('GMAIL_APP_PASSWORD') ?: ($env['GMAIL_APP_PASSWORD'] ?? '');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', $gmailUser);
define('SMTP_PASS', $gmailPass);
define('SMTP_FROM_EMAIL', $gmailUser);
define('SMTP_FROM_NAME', 'NexusDesk System');

/**
 * Sends an email using PHPMailer
 * 
 * @param string $to Email address of recipient
 * @param string $subject Subject of the email
 * @param string $htmlBody HTML content of the email
 * @return bool True on success, False on failure
 */
function sendSystemEmail($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Disable SSL certificate verification (useful for XAMPP localhost)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
