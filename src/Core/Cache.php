<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Cache
{
    private static ?string $dir = null;

    private static function getDir(): string
    {
        if (self::$dir === null) {
            self::$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gc_cache';
            if (!is_dir(self::$dir)) {
                @mkdir(self::$dir, 0777, true);
            }
        }
        return self::$dir;
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $file = self::getDir() . DIRECTORY_SEPARATOR . md5($key) . '.cache';

        if (is_file($file)) {
            $mtime = filemtime($file);
            if ($mtime !== false && (time() - $mtime) < $ttlSeconds) {
                $raw = @file_get_contents($file);
                if ($raw !== false) {
                    $decoded = @json_decode($raw, true);
                    if ($decoded !== null && isset($decoded['data'])) {
                        return $decoded['data'];
                    }
                }
            }
        }

        try {
            $data = $callback();
            $payload = json_encode(['data' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($payload !== false) {
                @file_put_contents($file, $payload, LOCK_EX);
            }
            return $data;
        } catch (Throwable $e) {
            Logger::error("Cache computation failed for key: {$key}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public static function forget(string $key): void
    {
        $file = self::getDir() . DIRECTORY_SEPARATOR . md5($key) . '.cache';
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function flush(): void
    {
        $dir = self::getDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
