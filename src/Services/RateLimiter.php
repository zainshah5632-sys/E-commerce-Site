<?php
declare(strict_types=1);

namespace App\Services;

final class RateLimiter
{
    private static string $storageDir = '';

    private static function getStorageDir(): string
    {
        if (self::$storageDir === '') {
            self::$storageDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'glasscart_ratelimit';
            if (!is_dir(self::$storageDir)) {
                @mkdir(self::$storageDir, 0755, true);
            }
        }
        return self::$storageDir;
    }

    private static function getFilePath(string $key): string
    {
        return self::getStorageDir() . DIRECTORY_SEPARATOR . 'rl_' . sha1($key) . '.json';
    }

    /**
     * Checks if the key has exceeded max attempts.
     */
    public static function tooManyAttempts(string $key, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        $file = self::getFilePath($key);
        if (!is_file($file)) {
            return false;
        }

        $data = @json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) {
            return false;
        }

        $now = time();
        $lockedUntil = (int)($data['locked_until'] ?? 0);
        if ($lockedUntil > $now) {
            return true;
        }

        // Check if window has expired
        $firstAttempt = (int)($data['first_attempt'] ?? $now);
        if (($now - $firstAttempt) > $decaySeconds) {
            @unlink($file);
            return false;
        }

        $attempts = (int)($data['attempts'] ?? 0);
        return $attempts >= $maxAttempts;
    }

    /**
     * Records a failed attempt, applying a progressive delay and locking if max attempts reached.
     */
    public static function hit(string $key, int $maxAttempts = 5, int $decaySeconds = 900): int
    {
        $file = self::getFilePath($key);
        $now = time();

        $data = [
            'attempts'      => 0,
            'first_attempt' => $now,
            'locked_until'  => 0,
        ];

        if (is_file($file)) {
            $parsed = @json_decode((string)@file_get_contents($file), true);
            if (is_array($parsed)) {
                $firstAttempt = (int)($parsed['first_attempt'] ?? $now);
                if (($now - $firstAttempt) <= $decaySeconds) {
                    $data = $parsed;
                }
            }
        }

        $data['attempts'] = (int)($data['attempts'] ?? 0) + 1;
        $attempts = $data['attempts'];

        if ($attempts >= $maxAttempts) {
            $data['locked_until'] = $now + $decaySeconds;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);

        // Progressive anti-automation delay: 100ms * attempt count (max 800ms)
        $delayMs = min(800, $attempts * 100);
        usleep($delayMs * 1000);

        return $attempts;
    }

    /**
     * Returns remaining lockout duration in seconds.
     */
    public static function availableIn(string $key, int $decaySeconds = 900): int
    {
        $file = self::getFilePath($key);
        if (!is_file($file)) {
            return 0;
        }

        $data = @json_decode((string)@file_get_contents($file), true);
        if (!is_array($data)) {
            return 0;
        }

        $now = time();
        $lockedUntil = (int)($data['locked_until'] ?? 0);
        if ($lockedUntil > $now) {
            return $lockedUntil - $now;
        }

        $firstAttempt = (int)($data['first_attempt'] ?? $now);
        $remaining = $decaySeconds - ($now - $firstAttempt);
        return max(0, $remaining);
    }

    /**
     * Clears rate limit record upon successful login.
     */
    public static function clear(string $key): void
    {
        $file = self::getFilePath($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

