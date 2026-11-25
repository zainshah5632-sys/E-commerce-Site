<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Logger;
use App\Repositories\OrderRepository;
use App\Repositories\SettingsRepository;
use App\Supabase\Client;

final class PaymentService
{
    public const METHOD_COD = 'cod';
    public const METHOD_BANK = 'bank_transfer';
    public const METHOD_EASYPAISA = 'easypaisa';
    public const METHOD_JAZZCASH = 'jazzcash';
    public const METHOD_CARD = 'card';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REFUNDED = 'refunded';

    public static function supportedMethods(): array
    {
        return [
            self::METHOD_COD => [
                'id'          => self::METHOD_COD,
                'name'        => 'Cash on Delivery (COD)',
                'badge'       => 'Pay on Delivery',
                'description' => 'Pay in cash upon physical package delivery to your doorstep. No advance payment required.',
                'requires_proof' => false,
            ],
            self::METHOD_EASYPAISA => [
                'id'          => self::METHOD_EASYPAISA,
                'name'        => 'Easypaisa Mobile Wallet',
                'badge'       => 'Instant Mobile Transfer',
                'description' => 'Transfer directly from your Easypaisa app or USSD to our verified business wallet.',
                'requires_proof' => true,
            ],
            self::METHOD_JAZZCASH => [
                'id'          => self::METHOD_JAZZCASH,
                'name'        => 'JazzCash Mobile Account',
                'badge'       => 'Instant Mobile Transfer',
                'description' => 'Transfer directly from your JazzCash app or *786# to our merchant account.',
                'requires_proof' => true,
            ],
            self::METHOD_BANK => [
                'id'          => self::METHOD_BANK,
                'name'        => 'Direct Bank Transfer / IBAN',
                'badge'       => 'Online Banking & Raast',
                'description' => 'Transfer funds securely via any online banking app, Raast ID, ATM, or over-the-counter deposit.',
                'requires_proof' => true,
            ],
            self::METHOD_CARD => [
                'id'          => self::METHOD_CARD,
                'name'        => 'Visa & Mastercard (Debit / Credit)',
                'badge'       => 'Official Encrypted Gateway',
                'description' => 'Instant, 256-bit encrypted card settlement powered by PCI-DSS compliant checkout.',
                'requires_proof' => false,
            ],
        ];
    }

    public static function getMethodDetails(string $methodId): ?array
    {
        $all = self::supportedMethods();
        return $all[$methodId] ?? null;
    }

    /**
     * Creates a Stripe Checkout Session for Card payments (PCI-DSS compliant).
     *
     * @return array{ok: bool, redirect_url?: string, error?: string}
     */
    public static function createStripeCheckoutSession(array $order): array
    {
        $secretKey = Env::get('STRIPE_SECRET_KEY', (string)SettingsRepository::get('stripe_secret_key', ''));
        if (empty($secretKey)) {
            return [
                'ok'    => false,
                'error' => 'Stripe card gateway is not yet configured with an API Secret Key. Please configure your Stripe Secret Key in Store Settings -> Payments, or choose another payment method.',
            ];
        }

        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');
        $currency = strtolower((string)SettingsRepository::get('currency_code', 'USD'));
        $orderNumber = $order['order_number'];
        $totalCents = (int)($order['total_cents'] ?? 0);

        $payload = [
            'payment_method_types[0]' => 'card',
            'mode'                    => 'payment',
            'client_reference_id'     => $orderNumber,
            'customer_email'          => $order['email'],
            'success_url'             => "{$appUrl}/order/success/{$orderNumber}?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url'              => "{$appUrl}/checkout?cancelled=1",
            'metadata[order_number]'  => $orderNumber,
            'line_items[0][price_data][currency]'               => $currency,
            'line_items[0][price_data][unit_amount]'            => (string)$totalCents,
            'line_items[0][price_data][product_data][name]'     => "GlassCart Order #{$orderNumber}",
            'line_items[0][price_data][product_data][description]' => "Payment for order {$orderNumber}",
            'line_items[0][quantity]'                           => '1',
        ];

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $rawResponse = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string)$rawResponse, true);
        if ($status >= 200 && $status < 300 && !empty($data['url'])) {
            return [
                'ok'           => true,
                'redirect_url' => $data['url'],
                'session_id'   => $data['id'] ?? null,
            ];
        }

        $errMessage = $data['error']['message'] ?? 'Failed to initiate card checkout session with Stripe.';
        Logger::error('Stripe Checkout Error', ['status' => $status, 'error' => $errMessage]);

        return [
            'ok'    => false,
            'error' => $errMessage,
        ];
    }

    /**
     * Verifies Stripe Webhook signature and event payload.
     *
     * @return array{ok: bool, event?: array, error?: string}
     */
    public static function verifyStripeWebhook(string $payload, string $sigHeader): array
    {
        $webhookSecret = Env::get('STRIPE_WEBHOOK_SECRET', (string)SettingsRepository::get('stripe_webhook_secret', ''));
        if (empty($webhookSecret)) {
            return ['ok' => false, 'error' => 'Stripe webhook secret is not configured.'];
        }

        // Parse signature header: t=...,v1=...
        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $sigHeader) as $item) {
            $parts = explode('=', trim($item), 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }
        }

        if ($timestamp === '' || empty($signatures)) {
            return ['ok' => false, 'error' => 'Invalid signature header format.'];
        }

        // Tolerance check: 5 minutes (300s)
        if (abs(time() - (int)$timestamp) > 300) {
            return ['ok' => false, 'error' => 'Webhook signature timestamp expired.'];
        }

        // Calculate expected signature
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        $matched = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return ['ok' => false, 'error' => 'Webhook signature mismatch.'];
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type'])) {
            return ['ok' => false, 'error' => 'Invalid webhook payload JSON.'];
        }

        return ['ok' => true, 'event' => $event];
    }

    /**
     * Records a payment transaction log entry in Supabase.
     */
    public static function recordTransaction(
        string $orderNumber,
        string $method,
        int $amountCents,
        string $status,
        ?string $transactionId = null,
        ?string $receiptUrl = null,
        array $gatewayResponse = [],
        ?string $adminNotes = null
    ): bool {
        $client = new Client();
        $payload = [
            'order_number'     => $orderNumber,
            'payment_method'   => $method,
            'amount_cents'     => $amountCents,
            'status'           => $status,
            'transaction_id'   => $transactionId,
            'receipt_url'      => $receiptUrl,
            'gateway_response' => json_encode($gatewayResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'admin_notes'      => $adminNotes,
        ];

        // Attempt insert into payment_transactions
        $res = $client->insert('payment_transactions', $payload, false);
        return $res->ok;
    }
}
