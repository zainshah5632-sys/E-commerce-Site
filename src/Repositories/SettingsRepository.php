<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Supabase\Client;

final class SettingsRepository
{
    private const CACHE_KEY = 'site_settings';
    private const TTL = 60;

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, static function (): array {
            $client = new Client();
            $res = $client->get('site_settings', ['select' => 'key,value']);
            if (!$res->ok || !is_array($res->data)) {
                return self::defaults();
            }

            $settings = [];
            foreach ($res->data as $row) {
                $val = $row['value'];
                // Clean json string if doubly wrapped
                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    $val = ($decoded !== null) ? $decoded : $val;
                }
                $settings[$row['key']] = $val;
            }

            return array_merge(self::defaults(), $settings);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): bool
    {
        $client = new Client();
        $payload = [
            'key'   => $key,
            'value' => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        // PostgREST upsert with Prefer: resolution=merge-duplicates
        $res = $client->insert('site_settings', $payload, false);
        Cache::forget(self::CACHE_KEY);
        return $res->ok;
    }

    public static function saveMany(array $settings): bool
    {
        $client = new Client();
        $success = true;
        foreach ($settings as $k => $v) {
            $payload = [
                'key'   => $k,
                'value' => json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
            $res = $client->insert('site_settings', $payload, false);
            if (!$res->ok) {
                $success = false;
            }
        }
        Cache::forget(self::CACHE_KEY);
        return $success;
    }

    public static function defaults(): array
    {
        return [
            'store_name'                    => 'GlassCart',
            'currency_code'                 => 'USD',
            'currency_symbol'               => '$',
            'flat_shipping_cents'           => 500,
            'free_shipping_threshold_cents' => 7500,
            'contact_email'                 => 'hello@example.com',
            'announcement'                  => 'Free shipping on orders over $75',
            'bank_instructions'             => 'Transfer the total to: Example Bank, Account 0000 0000. Use your order number as the reference.',
        ];
    }
}
