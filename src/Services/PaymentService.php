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
                'description' => 'Pay in cash upon physical package delivery to your doorstep. Zero advance payment required.',
                'automated'   => false,
            ],
            self::METHOD_EASYPAISA => [
                'id'          => self::METHOD_EASYPAISA,
                'name'        => 'Easypaisa Mobile Wallet',
                'badge'       => 'Instant App Approval / Push',
                'description' => 'Direct automated wallet debit. An authorization prompt or OTP will be sent to your registered Easypaisa app/phone.',
                'automated'   => true,
            ],
            self::METHOD_JAZZCASH => [
                'id'          => self::METHOD_JAZZCASH,
                'name'        => 'JazzCash Mobile Account',
                'badge'       => 'Real-Time MPIN Prompt',
                'description' => 'Direct automated wallet debit. Receive an instant USSD popup or mobile notification on your handset to approve with your MPIN.',
                'automated'   => true,
            ],
            self::METHOD_BANK => [
                'id'          => self::METHOD_BANK,
                'name'        => 'Bank Account / Raast',
                'badge'       => '1LINK & SBP Raast Real-Time',
                'description' => 'Instant bank settlement across 40+ Pakistani banks via SBP-regulated 1LINK & Raast gateway.',
                'automated'   => true,
            ],
            self::METHOD_CARD => [
                'id'          => self::METHOD_CARD,
                'name'        => 'Visa & Mastercard (Debit / Credit)',
                'badge'       => '3D Secure 2.0 Encrypted',
                'description' => 'Official hosted card gateway with two-factor SMS OTP authentication. Zero card numbers or CVVs stored on server.',
                'automated'   => true,
            ],
        ];
    }

    public static function getMethodDetails(string $methodId): ?array
    {
        $all = self::supportedMethods();
        return $all[$methodId] ?? null;
    }

    /**
     * Initiates automated real-time payment with the respective official payment gateway.
     *
     * @param array $order Order record containing order_number, total_cents, email, etc.
     * @param string $method Selected payment method (easypaisa, jazzcash, bank_transfer, card, cod)
     * @param array $paymentInput Customer inputs (mobile numbers, CNIC last digits)
     * @return array{ok: bool, status?: string, redirect_url?: string, awaiting_approval?: bool, message?: string, transaction_id?: string, error?: string}
     */
    public static function initiateAutomatedPayment(array $order, string $method, array $paymentInput = []): array
    {
        return match ($method) {
            self::METHOD_JAZZCASH   => self::initiateJazzCash($order, $paymentInput),
            self::METHOD_EASYPAISA  => self::initiateEasypaisa($order, $paymentInput),
            self::METHOD_BANK       => self::initiatePayFast($order, 'bank'),
            self::METHOD_CARD       => self::initiateCardPayment($order),
            self::METHOD_COD        => ['ok' => true, 'status' => self::STATUS_PENDING],
            default                 => ['ok' => false, 'error' => 'Unsupported payment method.'],
        };
    }

    // =========================================================================
    // JAZZCASH AUTOMATED GATEWAY (MWALLET & MPIN PUSH)
    // =========================================================================

    /**
     * Initiates a real-time JazzCash Mobile Account transaction.
     * Triggers an MPIN approval prompt directly on the customer's mobile phone.
     */
    public static function initiateJazzCash(array $order, array $input): array
    {
        $mode = (string)SettingsRepository::get('jazzcash_mode', Env::get('JAZZCASH_MODE', 'sandbox'));
        $merchantId = (string)SettingsRepository::get('jazzcash_merchant_id', Env::get('JAZZCASH_MERCHANT_ID', ''));
        $password = (string)SettingsRepository::get('jazzcash_password', Env::get('JAZZCASH_PASSWORD', ''));
        $salt = (string)SettingsRepository::get('jazzcash_integrity_salt', Env::get('JAZZCASH_INTEGRITY_SALT', ''));

        $mobile = preg_replace('/[^0-9]/', '', (string)($input['jazzcash_mobile'] ?? ''));
        $cnic = preg_replace('/[^0-9]/', '', (string)($input['jazzcash_cnic'] ?? ''));

        if (!preg_match('/^03[0-9]{9}$/', $mobile)) {
            return ['ok' => false, 'error' => 'Please provide a valid 11-digit JazzCash mobile number (e.g., 03001234567).'];
        }

        if (!preg_match('/^[0-9]{6}$/', $cnic)) {
            return ['ok' => false, 'error' => 'Please provide the last 6 digits of your registered CNIC for JazzCash authentication.'];
        }

        // Endpoint selection
        $endpoint = ($mode === 'live')
            ? 'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/'
            : 'https://sandbox.jazzcash.com.pk/Application/index.php/RestApi/';

        $orderNumber = (string)($order['order_number'] ?? '');
        $totalCents = (int)($order['total_cents'] ?? 0);
        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');
        $txnRefNo = 'T' . date('YmdHis') . random_int(10, 99);

        // If credentials are empty, inform merchant cleanly
        if (empty($merchantId) || empty($password) || empty($salt)) {
            return [
                'ok'    => false,
                'error' => 'JazzCash merchant gateway credentials are not configured. Please configure your Merchant ID, Password, and Integrity Salt in Admin Settings -> Payments, or check sandbox settings.',
            ];
        }

        $params = [
            'pp_Version'            => '1.1',
            'pp_TxnType'            => 'MWALLET',
            'pp_Language'           => 'EN',
            'pp_MerchantID'         => $merchantId,
            'pp_Password'           => $password,
            'pp_TxnRefNo'           => $txnRefNo,
            'pp_Amount'             => (string)$totalCents, // PKR in paise/cents
            'pp_TxnCurrency'        => 'PKR',
            'pp_TxnDateTime'        => date('YmdHis'),
            'pp_BillReference'      => $orderNumber,
            'pp_Description'        => "GlassCart Order {$orderNumber}",
            'pp_TxnExpiryDateTime'  => date('YmdHis', time() + 3600),
            'pp_ReturnURL'          => "{$appUrl}/payment/callback/jazzcash",
            'pp_MobileNumber'       => $mobile,
            'pp_CNIC'               => $cnic,
        ];

        // Calculate HMAC-SHA256 signature
        $params['pp_SecureHash'] = self::generateJazzCashSecureHash($params, $salt);

        // Execute API call
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode((string)$raw, true) ?: [];
        $respCode = (string)($response['pp_ResponseCode'] ?? '');
        $respMsg = (string)($response['pp_ResponseMessage'] ?? 'Unable to communicate with JazzCash.');

        Logger::info('JazzCash MWALLET API Response', [
            'order' => $orderNumber,
            'code'  => $respCode,
            'msg'   => $respMsg,
            'http'  => $status,
        ]);

        // '000' = Success, '124' = Customer MPIN approval prompt dispatched to mobile handset
        if ($respCode === '000') {
            OrderRepository::updatePayment($orderNumber, self::STATUS_PAID, "JazzCash real-time settlement approved ({$txnRefNo})");
            self::recordTransaction($orderNumber, self::METHOD_JAZZCASH, $totalCents, self::STATUS_PAID, $txnRefNo, null, $response, 'JazzCash MWALLET API Approval');
            return [
                'ok'             => true,
                'status'         => self::STATUS_PAID,
                'transaction_id' => $txnRefNo,
                'message'        => 'Payment successfully authorized and confirmed by JazzCash.',
            ];
        }

        if ($respCode === '124' || $respCode === '001') {
            // Awaiting customer MPIN input on their phone
            self::recordTransaction($orderNumber, self::METHOD_JAZZCASH, $totalCents, self::STATUS_PENDING, $txnRefNo, null, $response, 'Awaiting customer MPIN authorization');
            return [
                'ok'                => true,
                'status'            => self::STATUS_PENDING,
                'awaiting_approval' => true,
                'transaction_id'    => $txnRefNo,
                'message'           => 'A payment prompt has been sent to your phone! Please enter your 4-digit JazzCash MPIN on your mobile screen.',
            ];
        }

        // Failure
        Logger::error('JazzCash MWALLET Transaction Failed', ['order' => $orderNumber, 'error' => $respMsg, 'code' => $respCode]);
        return [
            'ok'    => false,
            'error' => "JazzCash Gateway Error ({$respCode}): {$respMsg}",
        ];
    }

    /**
     * Calculates official JazzCash HMAC-SHA256 signature.
     */
    public static function generateJazzCashSecureHash(array $params, string $integritySalt): string
    {
        ksort($params);
        $hashStr = $integritySalt;
        foreach ($params as $k => $v) {
            if ($k !== 'pp_SecureHash' && $v !== '' && $v !== null) {
                $hashStr .= '&' . (string)$v;
            }
        }
        return strtoupper(hash_hmac('sha256', $hashStr, $integritySalt));
    }

    /**
     * Verifies return response signature from JazzCash.
     */
    public static function verifyJazzCashHash(array $params): bool
    {
        $receivedHash = (string)($params['pp_SecureHash'] ?? '');
        if ($receivedHash === '') {
            return false;
        }

        $salt = (string)SettingsRepository::get('jazzcash_integrity_salt', Env::get('JAZZCASH_INTEGRITY_SALT', ''));
        if (empty($salt)) {
            return false;
        }

        $calculated = self::generateJazzCashSecureHash($params, $salt);
        return hash_equals(strtoupper($calculated), strtoupper($receivedHash));
    }

    // =========================================================================
    // EASYPAISA AUTOMATED GATEWAY (DIRECT MA API & APP PUSH)
    // =========================================================================

    /**
     * Initiates a real-time Easypaisa Mobile Account transaction.
     * Triggers an in-app approval notification or USSD prompt to the customer's Easypaisa app.
     */
    public static function initiateEasypaisa(array $order, array $input): array
    {
        $mode = (string)SettingsRepository::get('easypaisa_mode', Env::get('EASYPAISA_MODE', 'sandbox'));
        $storeId = (string)SettingsRepository::get('easypaisa_store_id', Env::get('EASYPAISA_STORE_ID', ''));
        $hashKey = (string)SettingsRepository::get('easypaisa_hash_key', Env::get('EASYPAISA_HASH_KEY', ''));

        $mobile = preg_replace('/[^0-9]/', '', (string)($input['easypaisa_mobile'] ?? ''));
        if (!preg_match('/^03[0-9]{9}$/', $mobile)) {
            return ['ok' => false, 'error' => 'Please provide a valid 11-digit Easypaisa mobile number (e.g., 03120246309).'];
        }

        $orderNumber = (string)($order['order_number'] ?? '');
        $totalCents = (int)($order['total_cents'] ?? 0);
        $totalRupees = number_format($totalCents / 100, 2, '.', '');
        $email = (string)($order['email'] ?? 'customer@example.com');

        if (empty($storeId) || empty($hashKey)) {
            return [
                'ok'    => false,
                'error' => 'Easypaisa merchant gateway credentials are not configured. Please configure your Store ID and Hash Key in Admin Settings -> Payments.',
            ];
        }

        $endpoint = ($mode === 'live')
            ? 'https://easypay.easypaisa.com.pk/easypay-service/rest/v4/initiate-ma-transaction'
            : 'https://easypaystg.easypaisa.com.pk/easypay-service/rest/v4/initiate-ma-transaction';

        $payload = [
            'storeId'           => $storeId,
            'orderId'           => $orderNumber,
            'transactionAmount' => $totalRupees,
            'mobileAccountNo'   => $mobile,
            'emailAddress'      => $email,
            'transactionType'   => 'MA',
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Credentials: ' . base64_encode("{$storeId}:{$hashKey}"),
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode((string)$raw, true) ?: [];
        $respCode = (string)($response['responseCode'] ?? '');
        $respDesc = (string)($response['responseDesc'] ?? 'Failed to communicate with Easypaisa gateway.');
        $transactionId = (string)($response['transactionId'] ?? ('EP-' . date('YmdHis')));

        Logger::info('Easypaisa MA API Response', [
            'order' => $orderNumber,
            'code'  => $respCode,
            'desc'  => $respDesc,
            'http'  => $status,
        ]);

        if ($respCode === '0000') {
            OrderRepository::updatePayment($orderNumber, self::STATUS_PAID, "Easypaisa instant debit approved ({$transactionId})");
            self::recordTransaction($orderNumber, self::METHOD_EASYPAISA, $totalCents, self::STATUS_PAID, $transactionId, null, $response, 'Easypaisa MA Approval');
            return [
                'ok'             => true,
                'status'         => self::STATUS_PAID,
                'transaction_id' => $transactionId,
                'message'        => 'Payment authorized successfully by Easypaisa.',
            ];
        }

        if ($respCode === '0001' || $respCode === '0010') {
            self::recordTransaction($orderNumber, self::METHOD_EASYPAISA, $totalCents, self::STATUS_PENDING, $transactionId, null, $response, 'Awaiting customer PIN on Easypaisa app');
            return [
                'ok'                => true,
                'status'            => self::STATUS_PENDING,
                'awaiting_approval' => true,
                'transaction_id'    => $transactionId,
                'message'           => 'An approval prompt was sent to your Easypaisa app! Please open your app and enter your 5-digit PIN.',
            ];
        }

        Logger::error('Easypaisa MA Transaction Failed', ['order' => $orderNumber, 'error' => $respDesc, 'code' => $respCode]);
        return [
            'ok'    => false,
            'error' => "Easypaisa Gateway Error ({$respCode}): {$respDesc}",
        ];
    }

    // =========================================================================
    // PAYFAST & SBP AGGREGATOR (BANK ACCOUNT, RAAST & MULTI-RAIL)
    // =========================================================================

    /**
     * Initiates automated Bank Account Transfer or Raast checkout via PayFast SBP gateway.
     */
    public static function initiatePayFast(array $order, string $instrument = 'bank'): array
    {
        $mode = (string)SettingsRepository::get('payfast_mode', Env::get('PAYFAST_MODE', 'sandbox'));
        $merchantId = (string)SettingsRepository::get('payfast_merchant_id', Env::get('PAYFAST_MERCHANT_ID', ''));
        $securedKey = (string)SettingsRepository::get('payfast_secured_key', Env::get('PAYFAST_SECURED_KEY', ''));

        $orderNumber = (string)($order['order_number'] ?? '');
        $totalCents = (int)($order['total_cents'] ?? 0);
        $totalRupees = number_format($totalCents / 100, 2, '.', '');
        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');

        if (empty($merchantId) || empty($securedKey)) {
            return [
                'ok'    => false,
                'error' => 'Bank / Raast automated gateway credentials are not configured. Please enter your Merchant ID and Secured Key in Admin Settings -> Payments.',
            ];
        }

        $baseUrl = ($mode === 'live') ? 'https://ipg.apps.net.pk' : 'https://ipgtest.apps.net.pk';

        // Step 1: Request Access Token
        $tokenUrl = "{$baseUrl}/api/GetAccessToken?merchant_id=" . urlencode($merchantId) . "&secured_key=" . urlencode($securedKey);
        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $tokenRaw = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode((string)$tokenRaw, true) ?: [];
        $accessToken = (string)($tokenData['ACCESS_TOKEN'] ?? '');

        if ($accessToken === '') {
            return [
                'ok'    => false,
                'error' => 'Could not authenticate with Bank / Raast payment gateway. Please check PayFast credentials.',
            ];
        }

        // Return hosted payment checkout URL
        $checkoutUrl = "{$baseUrl}/ecommerce/process?token=" . urlencode($accessToken) .
            "&basket_id=" . urlencode($orderNumber) .
            "&txnamt=" . urlencode($totalRupees) .
            "&customer_email_address=" . urlencode((string)($order['email'] ?? '')) .
            "&order_date=" . urlencode(date('Y-m-d H:i:s')) .
            "&success_url=" . urlencode("{$appUrl}/payment/callback/payfast") .
            "&checkout_url=" . urlencode("{$appUrl}/payment/callback/payfast");

        self::recordTransaction($orderNumber, self::METHOD_BANK, $totalCents, self::STATUS_PENDING, null, null, ['token' => $accessToken], 'PayFast session initialized');

        return [
            'ok'           => true,
            'status'       => self::STATUS_PENDING,
            'redirect_url' => $checkoutUrl,
        ];
    }

    // =========================================================================
    // CARD PAYMENTS (VISA / MASTERCARD VIA STRIPE OR SBP 3DS)
    // =========================================================================

    /**
     * Initiates official 3D Secure hosted checkout for Visa and Mastercard.
     */
    public static function initiateCardPayment(array $order): array
    {
        // First check Stripe configuration
        $stripeSecret = Env::get('STRIPE_SECRET_KEY', (string)SettingsRepository::get('stripe_secret_key', ''));
        if (!empty($stripeSecret)) {
            return self::createStripeCheckoutSession($order);
        }

        // Fallback to PayFast Card processing if configured
        $payfastMerchant = (string)SettingsRepository::get('payfast_merchant_id', Env::get('PAYFAST_MERCHANT_ID', ''));
        if (!empty($payfastMerchant)) {
            return self::initiatePayFast($order, 'card');
        }

        return [
            'ok'    => false,
            'error' => 'No online card payment gateway has been configured. Please configure Stripe or PayFast in Store Settings -> Payments.',
        ];
    }

    /**
     * Creates a Stripe Checkout Session for Card payments (PCI-DSS compliant).
     */
    public static function createStripeCheckoutSession(array $order): array
    {
        $secretKey = Env::get('STRIPE_SECRET_KEY', (string)SettingsRepository::get('stripe_secret_key', ''));
        if (empty($secretKey)) {
            return [
                'ok'    => false,
                'error' => 'Stripe Secret Key is not configured in Store Settings -> Payments.',
            ];
        }

        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');
        $currency = strtolower((string)SettingsRepository::get('currency_code', 'PKR'));
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
            CURLOPT_TIMEOUT        => 15,
        ]);

        $rawResponse = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string)$rawResponse, true);
        if ($status >= 200 && $status < 300 && !empty($data['url'])) {
            return [
                'ok'           => true,
                'status'       => self::STATUS_PENDING,
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
     */
    public static function verifyStripeWebhook(string $payload, string $sigHeader): array
    {
        $webhookSecret = Env::get('STRIPE_WEBHOOK_SECRET', (string)SettingsRepository::get('stripe_webhook_secret', ''));
        if (empty($webhookSecret)) {
            return ['ok' => false, 'error' => 'Stripe webhook secret is not configured.'];
        }

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

        if (abs(time() - (int)$timestamp) > 300) {
            return ['ok' => false, 'error' => 'Webhook signature timestamp expired.'];
        }

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

        $res = $client->insert('payment_transactions', $payload, false);
        return $res->ok;
    }
}
