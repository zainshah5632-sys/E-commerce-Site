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
            'transaction_id'      => null,
            'payment_receipt_url' => null,
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

        // On success: clear cart and store last_order number in session
        $orderData = $res['order'];
        $orderNumber = (string)($orderData['order_number'] ?? '');
        $totalCents = (int)($orderData['total_cents'] ?? 0);
        $orderData['email'] = $rpcArgs['email'];

        CartService::clear();
        $session->set('last_order', $orderNumber);

        $paymentMethod = (string)($data['payment_method'] ?? 'cod');

        // Initiate real-time payment through official payment gateway
        if ($paymentMethod !== 'cod') {
            $gwRes = PaymentService::initiateAutomatedPayment($orderData, $paymentMethod, $data);
            if (!$gwRes['ok']) {
                return ['ok' => false, 'error' => $gwRes['error'] ?? 'Could not initialize payment with official gateway.'];
            }

            return [
                'ok'                => true,
                'order_number'      => $orderNumber,
                'order_id'          => $orderData['order_id'] ?? null,
                'total_cents'       => $totalCents,
                'status'            => $gwRes['status'] ?? 'pending',
                'redirect_url'      => $gwRes['redirect_url'] ?? null,
                'awaiting_approval' => $gwRes['awaiting_approval'] ?? false,
                'message'           => $gwRes['message'] ?? null,
                'transaction_id'    => $gwRes['transaction_id'] ?? null,
            ];
        }

        // For Cash on Delivery (COD)
        PaymentService::recordTransaction(
            $orderNumber,
            'cod',
            $totalCents,
            'pending',
            null,
            null,
            [],
            'Order placed with Cash on Delivery'
        );

        return [
            'ok'           => true,
            'order_number' => $orderNumber,
            'order_id'     => $orderData['order_id'] ?? null,
            'total_cents'  => $totalCents,
            'status'       => 'pending',
        ];
    }
}
