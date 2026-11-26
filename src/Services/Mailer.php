<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Logger;

/**
 * Production-grade SMTP Mailer with zero external dependencies.
 *
 * Supports standard SMTP with TLS/STARTTLS (port 587) or SSL (port 465),
 * AUTH LOGIN authentication (Gmail, Brevo, SendGrid, Mailgun, Amazon SES, etc.),
 * and graceful local logging fallback when credentials are not yet configured.
 */
final class Mailer
{
    /**
     * Sends an email via SMTP or logs to file if SMTP is unconfigured.
     *
     * @return array{ok: bool, sent: bool, error?: string}
     */
    public static function send(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): array
    {
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'sent' => false, 'error' => 'Invalid recipient email address.'];
        }

        $host = Env::get('SMTP_HOST');
        $user = Env::get('SMTP_USER');
        $pass = Env::get('SMTP_PASS');
        $port = (int)(Env::get('SMTP_PORT', '587'));
        $secure = strtolower(Env::get('SMTP_SECURE', 'tls') ?? 'tls');
        $fromEmail = Env::get('SMTP_FROM_EMAIL') ?: ($user ?: 'noreply@glasscart.local');
        $fromName = Env::get('SMTP_FROM_NAME', 'GlassCart');

        // Graceful fallback for local development or when SMTP is not configured yet
        if (empty($host) || empty($user)) {
            self::logEmailLocally($toEmail, $subject, $htmlBody, $textBody);
            Logger::info("SMTP Mailer: Credentials not configured. Verification email was logged to development storage.", [
                'to'      => $toEmail,
                'subject' => $subject,
            ]);
            return ['ok' => true, 'sent' => false, 'logged' => true];
        }

        try {
            self::dispatchSmtp($host, $port, $secure, $user, $pass ?: '', $fromEmail, $fromName, $toEmail, $subject, $htmlBody, $textBody);
            Logger::info("SMTP Mailer: Successfully dispatched email via {$host}:{$port} to {$toEmail}", [
                'to'      => $toEmail,
                'subject' => $subject,
            ]);
            return ['ok' => true, 'sent' => true];
        } catch (\Throwable $e) {
            Logger::error("SMTP Mailer dispatch failed: " . $e->getMessage(), [
                'to'    => $toEmail,
                'host'  => $host,
                'port'  => $port,
                'error' => $e->getMessage(),
            ]);

            // Also keep local log on failure so developer is never locked out
            self::logEmailLocally($toEmail, $subject, $htmlBody, $textBody);

            return ['ok' => false, 'sent' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Convenience method to render and dispatch the branded GlassCart verification email.
     *
     * @return array{ok: bool, sent: bool, error?: string}
     */
    public static function sendVerificationEmail(string $toEmail, string $recipientName, string $otp, string $linkToken): array
    {
        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080') ?? 'http://localhost:8080', '/');
        $verifyUrl = "{$appUrl}/verify-email/confirm?token=" . urlencode($linkToken) . "&email=" . urlencode($toEmail);

        $templateData = [
            'name'             => $recipientName,
            'email'            => $toEmail,
            'otp'              => $otp,
            'verifyUrl'        => $verifyUrl,
            'expiresInMinutes' => 5,
            'appUrl'           => $appUrl,
        ];

        $htmlBody = self::renderTemplate('verify-email', $templateData);
        $textBody = self::renderTextBody($templateData);

        $subject = "Verify Your GlassCart Account — Code: {$otp}";

        return self::send($toEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Pure PHP SMTP socket communication.
     */
    private static function dispatchSmtp(
        string $host,
        int $port,
        string $secure,
        string $user,
        string $pass,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $timeout = 15;
        $connectionPrefix = ($secure === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
        $target = $connectionPrefix . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \RuntimeException("Unable to connect to SMTP server ({$target}): [{$errno}] {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        try {
            self::expectCode($socket, 220, "Connection Greeting");

            $clientHost = gethostname() ?: 'glasscart.local';
            self::sendCommand($socket, "EHLO {$clientHost}");
            $ehloResponse = self::readMultilineResponse($socket);

            // STARTTLS handshake for port 587 or tls mode
            if (($secure === 'tls' || $port === 587) && str_contains(strtoupper($ehloResponse), 'STARTTLS')) {
                self::sendCommand($socket, "STARTTLS");
                self::expectCode($socket, 220, "STARTTLS");

                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }

                $cryptoOk = stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if (!$cryptoOk) {
                    throw new \RuntimeException("Failed to establish TLS encryption with SMTP server.");
                }

                // Re-send EHLO after TLS handshake per RFC 3207
                self::sendCommand($socket, "EHLO {$clientHost}");
                self::readMultilineResponse($socket);
            }

            // Authentication via AUTH LOGIN
            if (!empty($user)) {
                self::sendCommand($socket, "AUTH LOGIN");
                self::expectCode($socket, 334, "AUTH LOGIN");

                self::sendCommand($socket, base64_encode($user));
                self::expectCode($socket, 334, "AUTH Username");

                self::sendCommand($socket, base64_encode($pass));
                self::expectCode($socket, 235, "AUTH Password");
            }

            // Mail transaction
            self::sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            self::expectCode($socket, 250, "MAIL FROM");

            self::sendCommand($socket, "RCPT TO:<{$toEmail}>");
            self::expectCode($socket, 250, "RCPT TO");

            self::sendCommand($socket, "DATA");
            self::expectCode($socket, 354, "DATA");

            // Build multipart/alternative MIME message
            $boundary = "----=_Part_" . bin2hex(random_bytes(16));
            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "To: <{$toEmail}>";
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "Message-ID: <" . bin2hex(random_bytes(16)) . "@" . ($host ?: 'glasscart.local') . ">";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $headers[] = "X-Mailer: GlassCart Security Mailer";

            $mimeMessage = implode("\r\n", $headers) . "\r\n\r\n";

            // Plain text part
            $mimeMessage .= "--{$boundary}\r\n";
            $mimeMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $mimeMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mimeMessage .= chunk_split(base64_encode($textBody)) . "\r\n";

            // HTML part
            $mimeMessage .= "--{$boundary}\r\n";
            $mimeMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $mimeMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $mimeMessage .= chunk_split(base64_encode($htmlBody)) . "\r\n";

            // End boundary
            $mimeMessage .= "--{$boundary}--\r\n";

            // Period on a line by itself terminates DATA
            $mimeMessage .= ".\r\n";

            // Send payload (chunked for stability)
            fwrite($socket, $mimeMessage);
            self::expectCode($socket, 250, "End of DATA");

            self::sendCommand($socket, "QUIT");
        } finally {
            fclose($socket);
        }
    }

    private static function sendCommand($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function expectCode($socket, int $expectedCode, string $context): void
    {
        $line = fgets($socket, 1024);
        if ($line === false) {
            throw new \RuntimeException("SMTP connection unexpectedly closed during {$context}.");
        }

        $code = (int)substr($line, 0, 3);
        if ($code !== $expectedCode) {
            throw new \RuntimeException("SMTP Error during {$context}: Expected {$expectedCode}, received: " . trim($line));
        }
    }

    private static function readMultilineResponse($socket): string
    {
        $full = '';
        while ($line = fgets($socket, 1024)) {
            $full .= $line;
            // In RFC 821/2821, the last line of a multiline response has a space after the code (e.g. "250 OK"), while continuation lines have a hyphen ("250-SIZE")
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $full;
    }

    private static function renderTemplate(string $name, array $data): string
    {
        $file = dirname(__DIR__, 2) . "/views/emails/{$name}.php";
        if (!is_file($file)) {
            return "<p>Your GlassCart verification code is: <strong>{$data['otp']}</strong></p>";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    private static function renderTextBody(array $data): string
    {
        return <<<TEXT
Hello {$data['name']},

Thank you for registering at GlassCart!

Your 6-digit email verification code is:
{$data['otp']}

This code will expire in {$data['expiresInMinutes']} minutes.

Alternatively, you can verify your account directly by visiting the following link:
{$data['verifyUrl']}

If you did not sign up for a GlassCart account, you can safely ignore this email.

— The GlassCart Team
{$data['appUrl']}
TEXT;
    }

    private static function logEmailLocally(string $toEmail, string $subject, string $htmlBody, string $textBody): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/email_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $entry = <<<ENTRY
================================================================================
TIMESTAMP : {$timestamp}
TO        : {$toEmail}
SUBJECT   : {$subject}
--------------------------------------------------------------------------------
TEXT BODY:
{$textBody}
================================================================================

ENTRY;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
