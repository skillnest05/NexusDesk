<?php
/**
 * Mail Configuration using PHPMailer
 * Uses port 465 (SMTPS/SSL) for cloud deployment compatibility.
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

// Fallbacks: getenv() (Railway) -> .env file -> empty
$gmailUser = getenv('GMAIL_USER') ?: ($env['GMAIL_USER'] ?? '');
$gmailPass = getenv('GMAIL_APP_PASSWORD') ?: ($env['GMAIL_APP_PASSWORD'] ?? '');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
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
    // Skip if credentials are not configured
    if (empty(SMTP_USER) || empty(SMTP_PASS)) {
        error_log('[NexusDesk Mail] SMTP credentials not configured. Skipping email to: ' . $to);
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL on port 465
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 15;

        // SSL options for cloud environments
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
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
        error_log('[NexusDesk Mail] Email sent successfully to: ' . $to);
        return true;
    } catch (Exception $e) {
        error_log("[NexusDesk Mail] Failed to send to {$to}. Error: {$mail->ErrorInfo}");
        return false;
    }
}
