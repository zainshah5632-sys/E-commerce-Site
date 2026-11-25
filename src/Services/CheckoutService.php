<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\OrderRepository;

final class CheckoutService
{
    public static function process(array $data): array
    {
        $cartSummary = CartService::summary();
        $items = $cartSummary['items'];

        if (empty($items)) {
            return ['ok' => false, 'error' => 'Your cart is empty.'];
        }

        // Sort items by product_id to prevent database deadlocks (AR §7)
        usort($items, static fn($a, $b) => strcmp($a['product_id'], $b['product_id']));

        // Prepare order items payload for RPC
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'quantity'   => (int)$item['quantity'],
            ];
        }

        $session = Session::instance();
        $userId = $session->userId();

        $shippingAddress = [
            'address_line_1' => trim((string)($data['address_line_1'] ?? '')),
            'address_line_2' => trim((string)($data['address_line_2'] ?? '')),
            'city'           => trim((string)($data['city'] ?? '')),
            'state'          => trim((string)($data['state'] ?? '')),
            'postal_code'    => trim((string)($data['postal_code'] ?? '')),
            'country'        => trim((string)($data['country'] ?? 'US')),
        ];

        $receiptUrl = null;
        if (!empty($data['payment_receipt_file']) && is_array($data['payment_receipt_file']) && !empty($data['payment_receipt_file']['tmp_name'])) {
            $storage = new \App\Supabase\Storage();
            $tempRef = 'ORD-' . date('ymd') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            $receiptUrl = $storage->uploadPaymentReceipt($tempRef, $data['payment_receipt_file']);
        }

        $rpcArgs = [
            'user_id'             => $userId,
            'email'               => trim((string)($data['email'] ?? '')),
            'customer_name'       => trim((string)($data['customer_name'] ?? '')),
            'phone'               => trim((string)($data['phone'] ?? '')),
            'shipping_address'    => $shippingAddress,
            'payment_method'      => $data['payment_method'] ?? 'cod',
            'notes'               => trim((string)($data['notes'] ?? '')),
            'items'               => $orderItems,
            'shipping_cents'      => (int)$cartSummary['shipping_cents'],
            'transaction_id'      => !empty($data['transaction_id']) ? trim((string)$data['transaction_id']) : null,
            'payment_receipt_url' => $receiptUrl,
        ];

        $res = OrderRepository::createViaRpc($rpcArgs);

        if (!$res['ok']) {
            $err = $res['error'] ?? 'Could not place order.';
            // Map RPC error codes to friendly customer messages
            if (str_contains($err, 'EMPTY_CART')) {
                $friendly = 'Your cart is empty.';
            } elseif (preg_match('/OUT_OF_STOCK:(.+)/', $err, $m)) {
                $friendly = "One or more items are out of stock: " . trim($m[1]);
            } elseif (preg_match('/PRODUCT_UNAVAILABLE:(.+)/', $err, $m)) {
                $friendly = 'A selected product is no longer available.';
            } else {
                $friendly = 'We were unable to process your order. Please check your cart and try again.';
            }

            return ['ok' => false, 'error' => $friendly];
        }

        // On success: clear cart and store last_order number in session for success screen guard
        $orderData = $res['order'];
        $orderNumber = (string)($orderData['order_number'] ?? '');
        $totalCents = (int)($orderData['total_cents'] ?? 0);

        CartService::clear();
        $session->set('last_order', $orderNumber);

        // Record initial payment transaction log
        PaymentService::recordTransaction(
            $orderNumber,
            $data['payment_method'] ?? 'cod',
            $totalCents,
            'pending',
            $rpcArgs['transaction_id'],
            $receiptUrl,
            ['notes' => $rpcArgs['notes']]
        );

        // If card checkout selected, check if Stripe hosted checkout should be initiated
        $redirectUrl = null;
        if (($data['payment_method'] ?? '') === 'card') {
            $stripeRes = PaymentService::createStripeCheckoutSession([
                'order_number' => $orderNumber,
                'email'        => $rpcArgs['email'],
                'total_cents'  => $totalCents,
            ]);
            if ($stripeRes['ok'] && !empty($stripeRes['redirect_url'])) {
                $redirectUrl = $stripeRes['redirect_url'];
            }
        }

        return [
            'ok'           => true,
            'order_number' => $orderNumber,
            'order_id'     => $orderData['order_id'] ?? null,
            'total_cents'  => $totalCents,
            'redirect_url' => $redirectUrl,
        ];
    }
}
