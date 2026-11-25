<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SettingsRepository;

final class PricingService
{
    public static function subtotal(array $items): int
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $unitPrice = (int)($item['price_cents'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            $subtotal += ($unitPrice * $qty);
        }
        return $subtotal;
    }

    public static function shipping(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $threshold = (int)SettingsRepository::get('free_shipping_threshold_cents', 500000);
        if ($subtotalCents >= $threshold) {
            return 0;
        }

        return (int)SettingsRepository::get('flat_shipping_cents', 25000);
    }

    public static function total(int $subtotalCents, int $shippingCents): int
    {
        return $subtotalCents + $shippingCents;
    }

    public static function freeShippingRemaining(int $subtotalCents): int
    {
        $threshold = (int)SettingsRepository::get('free_shipping_threshold_cents', 500000);
        return max(0, $threshold - $subtotalCents);
    }

    public static function freeShippingProgressPercent(int $subtotalCents): int
    {
        $threshold = (int)SettingsRepository::get('free_shipping_threshold_cents', 500000);
        if ($threshold <= 0) {
            return 100;
        }
        return min(100, (int)round(($subtotalCents / $threshold) * 100));
    }
}
