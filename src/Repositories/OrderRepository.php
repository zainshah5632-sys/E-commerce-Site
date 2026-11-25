<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Env;
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

        // Graceful compatibility fallback if Supabase check constraint hasn't been migrated yet
        if (!$res->ok && str_contains(strtolower($res->error ?? ''), 'orders_payment_method_check')) {
            $safeAddress = $args['shipping_address'];
            $safeAddress['payment_meta'] = [
                'actual_method'  => $args['payment_method'],
                'transaction_id' => $args['transaction_id'] ?? null,
                'receipt_url'    => $args['payment_receipt_url'] ?? null,
            ];

            $res = $client->rpc('place_order', [
                'p_user_id'        => $args['user_id'] ?? null,
                'p_email'          => $args['email'],
                'p_name'           => $args['customer_name'],
                'p_phone'          => $args['phone'],
                'p_address'        => $safeAddress,
                'p_payment_method' => ($args['payment_method'] === 'cod') ? 'cod' : 'bank_transfer',
                'p_notes'          => trim(($args['notes'] ?? '') . "\n[Payment: " . ucfirst($args['payment_method']) . (!empty($args['transaction_id']) ? " | TID: {$args['transaction_id']}" : '') . "]"),
                'p_items'          => $args['items'],
                'p_shipping_cents' => $args['shipping_cents'],
            ]);
        }

        if ($res->ok && is_array($res->data)) {
            $order = $res->data;
            $orderNumber = $order['order_number'] ?? '';

            // Update order with payment proof if provided
            if ($orderNumber !== '' && (!empty($args['transaction_id']) || !empty($args['payment_receipt_url']))) {
                $client->update('orders', ['order_number' => 'eq.' . $orderNumber], [
                    'transaction_id'      => $args['transaction_id'] ?? null,
                    'payment_receipt_url' => $args['payment_receipt_url'] ?? null,
                ]);
            }

            return ['ok' => true, 'order' => $order];
        }

        // Local fallback order generation when database service is unreachable
        if (Env::get('APP_ENV') === 'local' && ($res->status === 0 || str_contains($res->error ?? '', 'connect'))) {
            $orderNumber = 'ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $subtotalCents = 0;
            $items = [];
            foreach ($args['items'] ?? [] as $item) {
                $pid = $item['product_id'] ?? '';
                $prod = ProductRepository::findById($pid);
                $price = (int)($prod['price_cents'] ?? 0);
                $qty = (int)($item['quantity'] ?? 1);
                $subtotalCents += $price * $qty;
                $items[] = [
                    'id'                => 'item-' . bin2hex(random_bytes(6)),
                    'product_id'        => $pid,
                    'product_name'      => $prod['name'] ?? 'Hardware Item',
                    'unit_price_cents'  => $price,
                    'quantity'          => $qty,
                    'image_url'         => $prod['images'][0] ?? null,
                ];
            }
            $shippingCents = (int)($args['shipping_cents'] ?? 0);
            $totalCents = $subtotalCents + $shippingCents;

            $localOrder = [
                'id'               => 'ord-' . bin2hex(random_bytes(8)),
                'order_number'     => $orderNumber,
                'user_id'          => $args['user_id'] ?? null,
                'email'            => $args['email'] ?? '',
                'customer_name'    => $args['customer_name'] ?? '',
                'phone'            => $args['phone'] ?? '',
                'shipping_address' => $args['shipping_address'] ?? [],
                'payment_method'   => $args['payment_method'] ?? 'cod',
                'payment_status'   => 'pending',
                'status'           => 'pending',
                'subtotal_cents'   => $subtotalCents,
                'shipping_cents'   => $shippingCents,
                'total_cents'      => $totalCents,
                'notes'            => $args['notes'] ?? '',
                'created_at'       => date('c'),
                'updated_at'       => date('c'),
                'items'            => $items,
            ];

            // Store in cache for order success page retrieval
            Cache::remember('local_order_' . $orderNumber, 3600, static fn() => $localOrder);

            return ['ok' => true, 'order' => $localOrder];
        }

        return ['ok' => false, 'error' => $res->error ?? 'Could not place order.'];
    }

    public static function findByNumber(string $orderNumber): ?array
    {
        $local = Cache::remember('local_order_' . $orderNumber, 3600, static fn() => null);
        if ($local !== null) {
            return $local;
        }

        $client = new Client();
        $res = $client->get('orders', [
            'order_number' => 'eq.' . $orderNumber,
            'select'       => 'id,order_number,user_id,email,customer_name,phone,shipping_address,payment_method,payment_status,status,subtotal_cents,shipping_cents,total_cents,notes,transaction_id,payment_receipt_url,payment_details,created_at,updated_at',
            'limit'        => '1',
        ], ['single' => true]);

        // Graceful fallback if transaction_id columns are not yet migrated in Supabase
        if (!$res->ok && str_contains(strtolower($res->error ?? ''), 'does not exist')) {
            $res = $client->get('orders', [
                'order_number' => 'eq.' . $orderNumber,
                'select'       => 'id,order_number,user_id,email,customer_name,phone,shipping_address,payment_method,payment_status,status,subtotal_cents,shipping_cents,total_cents,notes,created_at,updated_at',
                'limit'        => '1',
            ], ['single' => true]);
        }

        if (!$res->ok || !is_array($res->data)) {
            return null;
        }

        $order = $res->data;

        // Hydrate from shipping_address payment_meta fallback if present
        $addr = is_array($order['shipping_address'] ?? null) ? $order['shipping_address'] : [];
        if (!empty($addr['payment_meta'])) {
            $meta = $addr['payment_meta'];
            if (!empty($meta['actual_method'])) {
                $order['payment_method'] = $meta['actual_method'];
            }
            if (empty($order['transaction_id']) && !empty($meta['transaction_id'])) {
                $order['transaction_id'] = $meta['transaction_id'];
            }
            if (empty($order['payment_receipt_url']) && !empty($meta['receipt_url'])) {
                $order['payment_receipt_url'] = $meta['receipt_url'];
            }
        }

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

        if (!empty($filters['payment_status'])) {
            $query['payment_status'] = 'eq.' . $filters['payment_status'];
        }

        if (!empty($filters['payment_method'])) {
            $query['payment_method'] = 'eq.' . $filters['payment_method'];
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

    public static function updatePayment(string $orderNumber, string $newPaymentStatus, ?string $adminNotes = null): bool
    {
        $allowed = ['pending', 'paid', 'failed', 'rejected', 'refunded'];
        if (!in_array($newPaymentStatus, $allowed, true)) {
            return false;
        }

        $client = new Client();
        $payload = [
            'payment_status' => $newPaymentStatus,
            'updated_at'     => date('c'),
        ];

        if ($adminNotes !== null && $adminNotes !== '') {
            $existing = self::findByNumber($orderNumber);
            $details = is_array($existing['payment_details'] ?? null) ? $existing['payment_details'] : [];
            $details['audit_trail'][] = [
                'status'    => $newPaymentStatus,
                'notes'     => $adminNotes,
                'timestamp' => date('c'),
            ];
            $payload['payment_details'] = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $res = $client->update('orders', ['order_number' => 'eq.' . $orderNumber], $payload);
        if (!$res->ok && isset($payload['payment_details'])) {
            unset($payload['payment_details']);
            $res = $client->update('orders', ['order_number' => 'eq.' . $orderNumber], $payload);
        }

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
