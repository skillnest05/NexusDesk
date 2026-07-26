<?php
/**
 * Mail Configuration — Resend HTTP API
 * 
 * Uses Resend.com HTTP API instead of SMTP (Railway blocks SMTP ports).
 * Free tier: 100 emails/day.
 * 
 * Fallback: tries PHPMailer SMTP for local development (XAMPP).
 */

// Load PHPMailer classes for local fallback
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

// Email credentials
$gmailUser = getenv('GMAIL_USER') ?: ($env['GMAIL_USER'] ?? '');
$gmailPass = getenv('GMAIL_APP_PASSWORD') ?: ($env['GMAIL_APP_PASSWORD'] ?? '');
$resendKey = getenv('RESEND_API_KEY') ?: ($env['RESEND_API_KEY'] ?? '');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', $gmailUser);
define('SMTP_PASS', $gmailPass);
define('SMTP_FROM_EMAIL', $gmailUser);
define('SMTP_FROM_NAME', 'NexusDesk System');
define('RESEND_API_KEY', $resendKey);

/**
 * Send email via Resend HTTP API
 */
function sendViaResend($to, $subject, $htmlBody) {
    $apiKey = RESEND_API_KEY;
    if (empty($apiKey)) return false;

    // Resend unverified domains must use onboarding@resend.dev as sender
    $from = "NexusDesk <onboarding@resend.dev>";

    $data = json_encode([
        'from'    => $from,
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[NexusDesk Mail] Resend cURL error: {$curlErr}");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("[NexusDesk Mail] Resend: Email sent to {$to}");
        return true;
    }

    error_log("[NexusDesk Mail] Resend HTTP {$httpCode}: {$response}");
    return false;
}

/**
 * Send email via PHPMailer SMTP (local/XAMPP fallback)
 */
function sendViaSMTP($to, $subject, $htmlBody) {
    if (empty(SMTP_USER) || empty(SMTP_PASS)) return false;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        error_log("[NexusDesk Mail] SMTP: Email sent to {$to}");
        return true;
    } catch (Exception $e) {
        error_log("[NexusDesk Mail] SMTP failed for {$to}: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email — tries Resend first, falls back to SMTP
 * 
 * @param string $to Email address of recipient
 * @param string $subject Subject of the email
 * @param string $htmlBody HTML content of the email
 * @return bool True on success, False on failure
 */
function sendSystemEmail($to, $subject, $htmlBody) {
    // Try Resend HTTP API first (works on Railway)
    if (!empty(RESEND_API_KEY)) {
        $result = sendViaResend($to, $subject, $htmlBody);
        if ($result) return true;
    }

    // Fallback to SMTP (works on local XAMPP)
    return sendViaSMTP($to, $subject, $htmlBody);
}
