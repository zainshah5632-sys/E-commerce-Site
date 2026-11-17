<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\ProductRepository;

final class CartService
{
    private const MAX_LINES = 20;
    private const MAX_QTY_PER_ITEM = 10;

    public static function items(): array
    {
        $raw = Session::instance()->cart();
        if (empty($raw)) {
            return [];
        }

        $productIds = array_column($raw, 'p');
        $products = ProductRepository::findByIds($productIds);
        $productMap = [];
        foreach ($products as $prod) {
            $productMap[$prod['id']] = $prod;
        }

        $hydrated = [];
        $cleanedRaw = [];

        foreach ($raw as $entry) {
            $pid = $entry['p'] ?? '';
            $qty = (int)($entry['q'] ?? 1);

            if (!isset($productMap[$pid])) {
                continue; // Product deleted or inactive
            }

            $prod = $productMap[$pid];
            $availableStock = (int)($prod['stock'] ?? 0);
            if ($availableStock <= 0) {
                continue; // Out of stock
            }

            // Clamp quantity to min(10, availableStock)
            $clampedQty = min(self::MAX_QTY_PER_ITEM, min($availableStock, max(1, $qty)));
            $images = $prod['images'] ?? [];
            $imgUrl = is_array($images) && count($images) > 0 ? $images[0] : null;
            if ($imgUrl === null && !empty($prod['slug'])) {
                $localRel = 'assets/img/products/' . $prod['slug'] . '.jpg';
                if (is_file(dirname(__DIR__, 2) . '/public/' . $localRel)) {
                    $imgUrl = '/' . $localRel;
                }
            }

            $hydrated[] = [
                'product_id'        => $prod['id'],
                'name'              => $prod['name'],
                'slug'              => $prod['slug'],
                'price_cents'       => (int)$prod['price_cents'],
                'compare_at_cents'  => !empty($prod['compare_at_cents']) ? (int)$prod['compare_at_cents'] : null,
                'quantity'          => $clampedQty,
                'stock'             => $availableStock,
                'image_url'         => $imgUrl,
                'line_total_cents'  => (int)$prod['price_cents'] * $clampedQty,
            ];

            $cleanedRaw[] = ['p' => $pid, 'q' => $clampedQty];
        }

        // Keep raw cart synchronized if anything was clamped or purged
        if (count($cleanedRaw) !== count($raw) || $cleanedRaw !== $raw) {
            Session::instance()->setCart($cleanedRaw);
        }

        return $hydrated;
    }

    public static function count(): int
    {
        $raw = Session::instance()->cart();
        $total = 0;
        foreach ($raw as $entry) {
            $total += (int)($entry['q'] ?? 0);
        }
        return $total;
    }

    public static function add(string $productId, int $quantity = 1): array
    {
        $prod = ProductRepository::findById($productId);
        if (!$prod || !($prod['is_active'] ?? false)) {
            return ['ok' => false, 'message' => 'Product is currently unavailable.'];
        }

        $stock = (int)($prod['stock'] ?? 0);
        if ($stock <= 0) {
            return ['ok' => false, 'message' => 'Sorry, this product is out of stock.'];
        }

        $session = Session::instance();
        $cart = $session->cart();

        // Check if item already in cart
        $found = false;
        foreach ($cart as &$entry) {
            if ($entry['p'] === $productId) {
                $found = true;
                $newQty = min(self::MAX_QTY_PER_ITEM, min($stock, $entry['q'] + $quantity));
                $entry['q'] = $newQty;
                break;
            }
        }
        unset($entry);

        if (!$found) {
            if (count($cart) >= self::MAX_LINES) {
                return ['ok' => false, 'message' => 'Cart is full (maximum 20 unique products allowed).'];
            }
            $initQty = min(self::MAX_QTY_PER_ITEM, min($stock, max(1, $quantity)));
            $cart[] = ['p' => $productId, 'q' => $initQty];
        }

        $saved = $session->setCart($cart);
        if (!$saved) {
            return ['ok' => false, 'message' => 'Unable to add item: cart cookie storage limit reached.'];
        }

        return ['ok' => true, 'message' => "Added {$prod['name']} to cart."];
    }

    public static function update(string $productId, int $quantity): array
    {
        $session = Session::instance();
        $cart = $session->cart();

        if ($quantity <= 0) {
            self::remove($productId);
            return ['ok' => true, 'message' => 'Item removed from cart.'];
        }

        $prod = ProductRepository::findById($productId);
        if (!$prod) {
            self::remove($productId);
            return ['ok' => false, 'message' => 'Product not found.'];
        }

        $stock = (int)($prod['stock'] ?? 0);
        $clamped = min(self::MAX_QTY_PER_ITEM, min($stock, $quantity));

        foreach ($cart as &$entry) {
            if ($entry['p'] === $productId) {
                $entry['q'] = $clamped;
                break;
            }
        }
        unset($entry);

        $session->setCart($cart);
        return ['ok' => true, 'message' => 'Cart updated.'];
    }

    public static function remove(string $productId): void
    {
        $session = Session::instance();
        $cart = array_values(array_filter($session->cart(), static fn($e) => ($e['p'] ?? '') !== $productId));
        $session->setCart($cart);
    }

    public static function clear(): void
    {
        Session::instance()->setCart([]);
    }

    public static function summary(): array
    {
        $items = self::items();
        $subtotal = PricingService::subtotal($items);
        $shipping = PricingService::shipping($subtotal);
        $total = PricingService::total($subtotal, $shipping);
        $count = self::count();

        return [
            'items'                     => $items,
            'count'                     => $count,
            'subtotal_cents'            => $subtotal,
            'shipping_cents'            => $shipping,
            'total_cents'               => $total,
            'free_shipping_remaining'   => PricingService::freeShippingRemaining($subtotal),
            'free_shipping_percent'     => PricingService::freeShippingProgressPercent($subtotal),
        ];
    }
}
