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

        // PostgREST upsert with Prefer: resolution=merge-duplicates & on_conflict=key
        $res = $client->upsert('site_settings', $payload, 'key');
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
            $res = $client->upsert('site_settings', $payload, 'key');
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
            'currency_code'                 => 'PKR',
            'currency_symbol'               => 'Rs',
            'flat_shipping_cents'           => 25000, // Rs 250 in paise
            'free_shipping_threshold_cents' => 500000, // Rs 5,000 in paise
            'contact_email'                 => 'hello@example.com',
            'announcement'                  => 'Free delivery across Pakistan on orders over Rs 5,000',

            // Automated Payment Methods Activation Toggles
            'payment_cod_enabled'           => true,
            'payment_bank_enabled'          => true,
            'payment_easypaisa_enabled'     => true,
            'payment_jazzcash_enabled'      => true,
            'payment_card_enabled'          => true,

            // Automated JazzCash Gateway Credentials (MWALLET & Hosted)
            'jazzcash_mode'                 => 'sandbox', // 'sandbox' or 'live'
            'jazzcash_merchant_id'          => '',
            'jazzcash_password'             => '',
            'jazzcash_integrity_salt'       => '',

            // Automated Easypaisa Gateway Credentials (Direct MA API & Hosted)
            'easypaisa_mode'                => 'sandbox', // 'sandbox' or 'live'
            'easypaisa_store_id'            => '',
            'easypaisa_hash_key'            => '',

            // Automated Bank / Raast Gateway Credentials (PayFast / SBP Aggregator)
            'payfast_mode'                  => 'sandbox', // 'sandbox' or 'live'
            'payfast_merchant_id'           => '',
            'payfast_secured_key'           => '',

            // Automated Visa / Mastercard Gateway Credentials (Stripe / 3DS)
            'stripe_mode'                   => 'test', // 'test' or 'live'
            'stripe_publishable_key'        => '',
            'stripe_secret_key'             => '',
            'stripe_webhook_secret'         => '',

            // Merchant Settlement Details
            'easypaisa_merchant_number'     => '03120246309',
            'bank_name'                     => 'United Bank Limited (UBL)',
            'bank_account_title'            => 'GlassCart Retail',
            'bank_account_number'           => '4214 6182 4622 1918',

            // Notification Preferences
            'notif_order_confirm'           => true,
            'notif_shipping_update'         => true,
            'notif_refund_advisory'         => true,
            'notif_low_stock'               => true,
            'notif_low_stock_threshold'     => 3,
            'notif_daily_digest'            => false,
            'notif_admin_email'             => 'admin@glasscart.local',

            // Security & Session Policies
            'security_session_timeout'      => 120, // minutes
            'security_max_login_attempts'   => 5,
            'security_maintenance_mode'     => false,
            'security_strict_ssl'           => true,
            'api_webhook_secret'            => 'whsec_98fbc184013490aa8efb019349cde012',
        ];
    }

    public static function isPaymentMethodEnabled(string $method): bool
    {
        $key = match($method) {
            'cod'           => 'payment_cod_enabled',
            'bank_transfer' => 'payment_bank_enabled',
            'easypaisa'     => 'payment_easypaisa_enabled',
            'jazzcash'      => 'payment_jazzcash_enabled',
            'card'          => 'payment_card_enabled',
            default         => '',
        };

        if ($key === '') {
            return false;
        }

        $val = self::get($key, true);
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function enabledPaymentMethods(): array
    {
        $all = ['cod', 'bank_transfer', 'easypaisa', 'jazzcash', 'card'];
        return array_values(array_filter($all, [self::class, 'isPaymentMethodEnabled']));
    }
}
