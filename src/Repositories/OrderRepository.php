<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Supabase\Client;

final class OrderRepository
{
    public static function createViaRpc(array $args): array
    {
        $client = new Client();
        $res = $client->rpc('place_order', [
            'p_user_id'        => $args['user_id'] ?? null,
            'p_email'          => $args['email'],
            'p_name'           => $args['customer_name'],
            'p_phone'          => $args['phone'],
            'p_address'        => $args['shipping_address'],
            'p_payment_method' => $args['payment_method'],
            'p_notes'          => $args['notes'] ?? '',
            'p_items'          => $args['items'],
            'p_shipping_cents' => $args['shipping_cents'],
        ]);

        if ($res->ok && is_array($res->data)) {
            return ['ok' => true, 'order' => $res->data];
        }

        return ['ok' => false, 'error' => $res->error ?? 'Could not place order.'];
    }

    public static function findByNumber(string $orderNumber): ?array
    {
        $client = new Client();
        $res = $client->get('orders', [
            'order_number' => 'eq.' . $orderNumber,
            'select'       => 'id,order_number,user_id,email,customer_name,phone,shipping_address,payment_method,payment_status,status,subtotal_cents,shipping_cents,total_cents,notes,created_at,updated_at',
            'limit'        => '1',
        ], ['single' => true]);

        if (!$res->ok || !is_array($res->data)) {
            return null;
        }

        $order = $res->data;
        // Fetch order items
        $itemsRes = $client->get('order_items', [
            'order_id' => 'eq.' . $order['id'],
            'select'   => 'id,product_id,product_name,unit_price_cents,quantity,image_url',
        ]);

        $order['items'] = ($itemsRes->ok && is_array($itemsRes->data)) ? $itemsRes->data : [];
        return $order;
    }

    public static function listByUser(string $userId, int $page = 1, int $perPage = 10): array
    {
        $client = new Client();
        $res = $client->get('orders', [
            'user_id' => 'eq.' . $userId,
            'select'  => 'id,order_number,status,payment_status,payment_method,total_cents,created_at',
            'order'   => 'created_at.desc',
            'limit'   => (string)$perPage,
            'offset'  => (string)(($page - 1) * $perPage),
        ], ['count' => true]);

        $orders = ($res->ok && is_array($res->data)) ? $res->data : [];
        $total = $res->count ?? count($orders);

        return [
            'orders' => $orders,
            'total'  => $total,
            'page'   => $page,
            'pages'  => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $client = new Client();
        $query = [
            'select' => 'id,order_number,customer_name,email,phone,status,payment_status,payment_method,total_cents,created_at',
            'order'  => 'created_at.desc',
            'limit'  => (string)$perPage,
            'offset' => (string)(($page - 1) * $perPage),
        ];

        if (!empty($filters['status'])) {
            $query['status'] = 'eq.' . $filters['status'];
        }

        if (!empty($filters['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$filters['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(order_number.ilike.*{$clean}*,email.ilike.*{$clean}*,customer_name.ilike.*{$clean}*)";
            }
        }

        $res = $client->get('orders', $query, ['count' => true]);
        $orders = ($res->ok && is_array($res->data)) ? $res->data : [];
        $total = $res->count ?? count($orders);

        return [
            'orders' => $orders,
            'total'  => $total,
            'page'   => $page,
            'pages'  => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function updateStatus(string $orderNumber, string $newStatus): bool
    {
        $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        $client = new Client();
        $res = $client->update('orders', ['order_number' => 'eq.' . $orderNumber], [
            'status' => $newStatus,
        ]);
        return $res->ok;
    }

    public static function updatePayment(string $orderNumber, string $newPaymentStatus): bool
    {
        $allowed = ['pending', 'paid', 'failed', 'refunded'];
        if (!in_array($newPaymentStatus, $allowed, true)) {
            return false;
        }

        $client = new Client();
        $res = $client->update('orders', ['order_number' => 'eq.' . $orderNumber], [
            'payment_status' => $newPaymentStatus,
        ]);
        return $res->ok;
    }

    public static function cancel(string $orderId): array
    {
        $client = new Client();
        $res = $client->rpc('cancel_order', ['p_order_id' => $orderId]);
        if ($res->ok) {
            return ['ok' => true];
        }

        $err = $res->error ?? 'Cannot cancel order.';
        if (str_contains($err, 'ORDER_NOT_CANCELLABLE')) {
            $err = 'This order has already progressed beyond the cancellable stage.';
        } elseif (str_contains($err, 'ORDER_NOT_FOUND')) {
            $err = 'Order not found.';
        }

        return ['ok' => false, 'error' => $err];
    }

    public static function stats(): array
    {
        $client = new Client();
        $res = $client->rpc('admin_stats');
        if ($res->ok && is_array($res->data)) {
            return $res->data;
        }
        return [
            'orders_today'      => 0,
            'pending_orders'    => 0,
            'revenue_30d_cents' => 0,
            'low_stock'         => 0,
            'products_total'    => 0,
        ];
    }
}
