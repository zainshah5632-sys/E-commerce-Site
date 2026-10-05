<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Env
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path)) {
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            // Remove surrounding single or double quotes
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last = $val[strlen($val) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            self::$cache[$key] = $val;
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string)$val;
        }

        return $default;
    }

    public static function require(string $key): string
    {
        $val = self::get($key);
        if ($val === null || $val === '') {
            throw new RuntimeException("Missing required environment variable: {$key}");
        }

        return $val;
    }
}
