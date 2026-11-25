<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Logger;
use App\Repositories\OrderRepository;
use App\Services\PaymentService;

final class WebhookController
{
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
}
