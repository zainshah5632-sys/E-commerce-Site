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

            // Payment Methods Activation Toggles
            'payment_cod_enabled'           => true,
            'payment_bank_enabled'          => true,
            'payment_easypaisa_enabled'     => true,
            'payment_jazzcash_enabled'      => true,
            'payment_card_enabled'          => true,

            // Bank Account Transfer Settings
            'bank_name'                     => 'Meezan Bank Ltd',
            'bank_account_title'            => 'GlassCart Official',
            'bank_account_number'           => '01020304050607',
            'bank_iban'                     => 'PK36MEZN0001020304050607',
            'bank_branch'                   => 'Main Corporate Branch (0101)',
            'bank_instructions'             => 'Transfer the total to: Meezan Bank, Title: GlassCart Official, Account: 01020304050607, IBAN: PK36MEZN0001020304050607. Enter your Transaction Reference ID below and upload your receipt screenshot.',

            // Easypaisa Settings
            'easypaisa_title'               => 'GlassCart Official',
            'easypaisa_number'              => '03236701030',
            'easypaisa_instructions'        => '1. Open Easypaisa App. 2. Select Send Money -> Easypaisa Account. 3. Enter Mobile Number: 0323-6701030. 4. Enter the order total. 5. Complete payment and input your 3737 Transaction ID (TID) below with screenshot receipt.',

            // JazzCash Settings
            'jazzcash_title'                => 'GlassCart Official',
            'jazzcash_number'               => '03001234567',
            'jazzcash_instructions'         => '1. Open JazzCash App or dial *786#. 2. Select Send Money to JazzCash Account. 3. Enter Mobile Number: 0300-1234567. 4. Complete transaction and enter the 8585 Transaction ID (TID) below with screenshot receipt.',

            // Visa / Mastercard (Card) Settings
            'card_instructions'             => 'Pay securely using any Visa or Mastercard debit/credit card via official encrypted hosted payment checkout.',
            'stripe_mode'                   => 'test',
            'stripe_publishable_key'        => '',
            'stripe_secret_key'             => '',
            'stripe_webhook_secret'         => '',
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
