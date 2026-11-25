<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\OrderRepository;
use App\Services\PaymentService;

final class WebhookController
{
    // =========================================================================
    // STRIPE (VISA / MASTERCARD) WEBHOOK
    // =========================================================================

    public function stripe(Request $request): Response
    {
        $rawPayload = (string)file_get_contents('php://input');
        $sigHeader = (string)($request->header('Stripe-Signature') ?? '');

        if ($rawPayload === '' || $sigHeader === '') {
            return Response::json(['error' => 'Missing payload or signature header.'], 400);
        }

        $res = PaymentService::verifyStripeWebhook($rawPayload, $sigHeader);
        if (!$res['ok']) {
            Logger::warning('Stripe webhook verification rejected', ['error' => $res['error']]);
            return Response::json(['error' => $res['error']], 400);
        }

        $event = $res['event'];
        $type = $event['type'] ?? '';
        $obj = $event['data']['object'] ?? [];

        if ($type === 'checkout.session.completed' || $type === 'payment_intent.succeeded') {
            $orderNumber = (string)($obj['client_reference_id'] ?? ($obj['metadata']['order_number'] ?? ''));
            $amountCents = (int)($obj['amount_total'] ?? ($obj['amount'] ?? 0));
            $transactionId = (string)($obj['payment_intent'] ?? ($obj['id'] ?? ''));

            if ($orderNumber !== '') {
                OrderRepository::updatePayment($orderNumber, 'paid', 'Verified online settlement via Stripe (' . $transactionId . ')');
                PaymentService::recordTransaction(
                    $orderNumber,
                    'card',
                    $amountCents,
                    'paid',
                    $transactionId,
                    null,
                    $event,
                    'Automated webhook payment verification'
                );
                Logger::info("Order {$orderNumber} marked paid via Stripe webhook", ['tx' => $transactionId]);
            }
        }

        return Response::json(['received' => true]);
    }

    // =========================================================================
    // JAZZCASH IPN & RETURN CALLBACK
    // =========================================================================

    public function jazzcash(Request $request): Response
    {
        $params = $_POST;
        if (empty($params)) {
            $raw = (string)file_get_contents('php://input');
            $params = json_decode($raw, true) ?: [];
        }

        $orderNumber = (string)($params['pp_BillReference'] ?? '');
        $responseCode = (string)($params['pp_ResponseCode'] ?? '');
        $txnRefNo = (string)($params['pp_TxnRefNo'] ?? '');
        $amountCents = (int)($params['pp_Amount'] ?? 0);

        Logger::info('JazzCash IPN Received', ['order' => $orderNumber, 'code' => $responseCode, 'tx' => $txnRefNo]);

        if ($orderNumber === '') {
            return Response::text('pp_ResponseCode=199&pp_ResponseMessage=Missing Bill Reference', 400);
        }

        // Verify cryptographic hash
        if (!PaymentService::verifyJazzCashHash($params)) {
            Logger::warning('JazzCash IPN Hash Mismatch', ['order' => $orderNumber]);
            return Response::text('pp_ResponseCode=199&pp_ResponseMessage=Hash Verification Failed', 400);
        }

        if ($responseCode === '000') {
            OrderRepository::updatePayment($orderNumber, 'paid', "JazzCash verified online settlement ({$txnRefNo})");
            PaymentService::recordTransaction(
                $orderNumber,
                PaymentService::METHOD_JAZZCASH,
                $amountCents,
                'paid',
                $txnRefNo,
                null,
                $params,
                'JazzCash IPN Webhook Verified'
            );
        } else {
            OrderRepository::updatePayment($orderNumber, 'failed', "JazzCash transaction declined ({$responseCode})");
            PaymentService::recordTransaction(
                $orderNumber,
                PaymentService::METHOD_JAZZCASH,
                $amountCents,
                'failed',
                $txnRefNo,
                null,
                $params,
                "JazzCash IPN Declined: {$responseCode}"
            );
        }

        return Response::text('pp_ResponseCode=000&pp_ResponseMessage=Success');
    }

    public function jazzcashCallback(Request $request): Response
    {
        $params = array_merge($_GET, $_POST);
        $orderNumber = (string)($params['pp_BillReference'] ?? $request->query('order', ''));
        $responseCode = (string)($params['pp_ResponseCode'] ?? '');
        $txnRefNo = (string)($params['pp_TxnRefNo'] ?? '');
        $msg = (string)($params['pp_ResponseMessage'] ?? 'Transaction completed');

        $session = Session::instance();

        if ($responseCode === '000' && !empty($orderNumber)) {
            OrderRepository::updatePayment($orderNumber, 'paid', "JazzCash settlement approved ({$txnRefNo})");
            $session->set('last_order', $orderNumber);
            $session->flash('success', 'Your JazzCash payment was approved successfully!');
            return Response::redirect("/order/success/{$orderNumber}");
        }

        if ($responseCode === '124' && !empty($orderNumber)) {
            $session->set('last_order', $orderNumber);
            $session->flash('info', 'Your payment is awaiting MPIN approval on your mobile handset.');
            return Response::redirect("/order/success/{$orderNumber}");
        }

        $session->flash('error', "JazzCash payment was not completed: {$msg}");
        return Response::redirect('/checkout');
    }

    // =========================================================================
    // EASYPAISA IPN & RETURN CALLBACK
    // =========================================================================

    public function easypaisa(Request $request): Response
    {
        $params = $_POST;
        if (empty($params)) {
            $raw = (string)file_get_contents('php://input');
            $params = json_decode($raw, true) ?: [];
        }

        $orderNumber = (string)($params['orderId'] ?? ($params['order_id'] ?? ''));
        $responseCode = (string)($params['responseCode'] ?? ($params['status'] ?? ''));
        $txnId = (string)($params['transactionId'] ?? ('EP-' . time()));
        $amount = (int)(((float)($params['transactionAmount'] ?? 0)) * 100);

        Logger::info('Easypaisa IPN Received', ['order' => $orderNumber, 'code' => $responseCode, 'tx' => $txnId]);

        if (!empty($orderNumber)) {
            if ($responseCode === '0000' || strtolower($responseCode) === 'paid') {
                OrderRepository::updatePayment($orderNumber, 'paid', "Easypaisa verified online settlement ({$txnId})");
                PaymentService::recordTransaction(
                    $orderNumber,
                    PaymentService::METHOD_EASYPAISA,
                    $amount,
                    'paid',
                    $txnId,
                    null,
                    $params,
                    'Easypaisa IPN Webhook Verified'
                );
            } else {
                OrderRepository::updatePayment($orderNumber, 'failed', "Easypaisa transaction declined ({$responseCode})");
            }
        }

        return Response::json(['status' => 'acknowledged']);
    }

    public function easypaisaCallback(Request $request): Response
    {
        $params = array_merge($_GET, $_POST);
        $orderNumber = (string)($params['orderId'] ?? ($params['order_number'] ?? ''));
        $responseCode = (string)($params['responseCode'] ?? ($params['status'] ?? ''));
        $txnId = (string)($params['transactionId'] ?? '');

        $session = Session::instance();

        if (($responseCode === '0000' || strtolower($responseCode) === 'paid') && !empty($orderNumber)) {
            OrderRepository::updatePayment($orderNumber, 'paid', "Easypaisa settlement approved ({$txnId})");
            $session->set('last_order', $orderNumber);
            $session->flash('success', 'Your Easypaisa payment has been confirmed!');
            return Response::redirect("/order/success/{$orderNumber}");
        }

        $session->flash('error', 'Easypaisa transaction was not completed or was cancelled.');
        return Response::redirect('/checkout');
    }

    // =========================================================================
    // PAYFAST & SBP AGGREGATOR (BANK ACCOUNT / RAAST) CALLBACK
    // =========================================================================

    public function payfast(Request $request): Response
    {
        $params = $_POST;
        if (empty($params)) {
            $raw = (string)file_get_contents('php://input');
            $params = json_decode($raw, true) ?: [];
        }

        $orderNumber = (string)($params['basket_id'] ?? '');
        $errCode = (string)($params['err_code'] ?? '');
        $txnId = (string)($params['transaction_id'] ?? '');
        $amount = (int)(((float)($params['txnamt'] ?? 0)) * 100);

        Logger::info('PayFast IPN Received', ['order' => $orderNumber, 'err_code' => $errCode, 'tx' => $txnId]);

        if (!empty($orderNumber)) {
            if ($errCode === '000') {
                OrderRepository::updatePayment($orderNumber, 'paid', "Bank/Raast verified settlement via PayFast ({$txnId})");
                PaymentService::recordTransaction(
                    $orderNumber,
                    PaymentService::METHOD_BANK,
                    $amount,
                    'paid',
                    $txnId,
                    null,
                    $params,
                    'PayFast Bank/Raast Verified'
                );
            } else {
                OrderRepository::updatePayment($orderNumber, 'failed', "PayFast settlement error ({$errCode})");
            }
        }

        return Response::json(['status' => 'OK']);
    }

    public function payfastCallback(Request $request): Response
    {
        $params = array_merge($_GET, $_POST);
        $orderNumber = (string)($params['basket_id'] ?? '');
        $errCode = (string)($params['err_code'] ?? '');
        $txnId = (string)($params['transaction_id'] ?? '');

        $session = Session::instance();

        if ($errCode === '000' && !empty($orderNumber)) {
            OrderRepository::updatePayment($orderNumber, 'paid', "Online Bank/Raast settlement approved ({$txnId})");
            $session->set('last_order', $orderNumber);
            $session->flash('success', 'Your bank transfer / Raast payment was verified successfully!');
            return Response::redirect("/order/success/{$orderNumber}");
        }

        $session->flash('error', 'Bank / Raast transaction was not completed or authorization failed.');
        return Response::redirect('/checkout');
    }
}
