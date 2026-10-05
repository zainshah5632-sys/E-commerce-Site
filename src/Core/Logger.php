<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'secret', 'key', 'token',
        'APP_SECRET', 'SUPABASE_SECRET_KEY', 'cookie', 'authorization'
    ];

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        $sanitized = self::sanitize($context);
        $payload = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'level'     => $level,
            'message'   => $message,
            'context'   => $sanitized,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            error_log($json);
        }
    }

    private static function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $k => $v) {
            $lower = strtolower((string)$k);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lower, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$k] = '***REDACTED***';
            } elseif (is_array($v)) {
                $clean[$k] = self::sanitize($v);
            } elseif (is_scalar($v) || $v === null) {
                $clean[$k] = $v;
            } else {
                $clean[$k] = (string)$v;
            }
        }
        return $clean;
    }
}
