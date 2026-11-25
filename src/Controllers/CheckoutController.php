<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\OrderRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\SettingsRepository;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;

final class CheckoutController
{
    public function show(Request $request): Response
    {
        $summary = CartService::summary();
        if (empty($summary['items'])) {
            Session::instance()->flash('info', 'Your cart is empty. Please add items before checking out.');
            return Response::redirect('/shop');
        }

        $session = Session::instance();
        $userProfile = null;
        if ($session->isAuthenticated()) {
            $userProfile = ProfileRepository::find((string)$session->userId());
        }

        $settings = SettingsRepository::all();
        $enabledMethods = SettingsRepository::enabledPaymentMethods();
        $paymentMethodsInfo = PaymentService::supportedMethods();

        return Response::html(View::render('pages/checkout', [
            'title'              => 'Secure Checkout — GlassCart',
            'summary'            => $summary,
            'userProfile'        => $userProfile,
            'settings'           => $settings,
            'enabledMethods'     => $enabledMethods,
            'paymentMethodsInfo' => $paymentMethodsInfo,
            'errors'             => [],
            'noindex'            => true,
        ]));
    }

    public function place(Request $request): Response
    {
        $summary = CartService::summary();
        if (empty($summary['items'])) {
            return Response::redirect('/shop');
        }

        $session = Session::instance();
        $rawPhone = $request->string('phone');
        $phone = preg_replace('/[^0-9]/', '', $rawPhone);
        $countryCode = trim($request->string('country_code', '+1'));

        $paymentMethod = $request->string('payment_method', 'cod');
        $transactionId = $request->string('transaction_id');
        $receiptFile = $request->file('payment_receipt');

        $data = [
            'customer_name'        => $request->string('customer_name'),
            'email'                => $request->string('email'),
            'phone'                => $phone,
            'country_code'         => $countryCode,
            'address_line_1'       => $request->string('address_line_1'),
            'address_line_2'       => $request->string('address_line_2'),
            'city'                 => $request->string('city'),
            'state'                => $request->string('state'),
            'postal_code'          => $request->string('postal_code'),
            'country'              => $request->string('country', 'US'),
            'payment_method'       => $paymentMethod,
            'transaction_id'       => $transactionId !== '' ? $transactionId : null,
            'payment_receipt_file' => $receiptFile,
            'notes'                => $request->string('notes'),
        ];

        $validator = Validator::make($data, [
            'customer_name'  => 'required|alpha_spaces|min:2|max:100',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|phone_11',
            'address_line_1' => 'required|min:3|max:150',
            'city'           => 'required|min:2|max:80',
            'state'          => 'required|min:2|max:80',
            'postal_code'    => 'required|min:3|max:20',
            'payment_method' => 'required|in:cod,bank_transfer,easypaisa,jazzcash,card',
        ]);

        $settings = SettingsRepository::all();
        $enabledMethods = SettingsRepository::enabledPaymentMethods();
        $paymentMethodsInfo = PaymentService::supportedMethods();
        $userProfile = $session->isAuthenticated() ? ProfileRepository::find((string)$session->userId()) : null;

        if ($validator->fails()) {
            return Response::html(View::render('pages/checkout', [
                'title'              => 'Secure Checkout — GlassCart',
                'summary'            => $summary,
                'userProfile'        => $userProfile,
                'settings'           => $settings,
                'enabledMethods'     => $enabledMethods,
                'paymentMethodsInfo' => $paymentMethodsInfo,
                'errors'             => $validator->errors(),
            ]), 422);
        }

        if (!SettingsRepository::isPaymentMethodEnabled($paymentMethod)) {
            return Response::html(View::render('pages/checkout', [
                'title'              => 'Secure Checkout — GlassCart',
                'summary'            => $summary,
                'userProfile'        => $userProfile,
                'settings'           => $settings,
                'enabledMethods'     => $enabledMethods,
                'paymentMethodsInfo' => $paymentMethodsInfo,
                'errors'             => ['payment_method' => 'The selected payment method is currently disabled. Please choose another option.'],
            ]), 422);
        }

        if ($receiptFile && !empty($receiptFile['tmp_name']) && is_file($receiptFile['tmp_name'])) {
            if ($receiptFile['size'] > 5242880) {
                return Response::html(View::render('pages/checkout', [
                    'title'              => 'Secure Checkout — GlassCart',
                    'summary'            => $summary,
                    'userProfile'        => $userProfile,
                    'settings'           => $settings,
                    'enabledMethods'     => $enabledMethods,
                    'paymentMethodsInfo' => $paymentMethodsInfo,
                    'errors'             => ['payment_receipt' => 'Receipt file must not exceed 5 MB.'],
                ]), 422);
            }
        }

        $res = CheckoutService::process($data);
        if (!$res['ok']) {
            return Response::html(View::render('pages/checkout', [
                'title'              => 'Secure Checkout — GlassCart',
                'summary'            => $summary,
                'userProfile'        => $userProfile,
                'settings'           => $settings,
                'enabledMethods'     => $enabledMethods,
                'paymentMethodsInfo' => $paymentMethodsInfo,
                'errors'             => ['checkout' => $res['error']],
            ]), 400);
        }

        if (!empty($res['redirect_url'])) {
            return Response::redirect($res['redirect_url']);
        }

        return Response::redirect('/order/success/' . $res['order_number']);
    }

    public function success(Request $request): Response
    {
        $session = Session::instance();
        $orderNumber = (string)$request->param('number');
        $lastOrder = $session->get('last_order');

        $order = OrderRepository::findByNumber($orderNumber);
        if (!$order) {
            return Response::html(View::render('pages/404', ['title' => 'Order Not Found']), 404);
        }

        // Session guard: user must either match last_order or be the authenticated owner
        $isOwner = $session->isAuthenticated() && ($order['user_id'] === $session->userId());
        $isCurrentSessionOrder = ($lastOrder === $orderNumber);

        if (!$isOwner && !$isCurrentSessionOrder && !$session->isAdmin()) {
            return Response::html(View::render('pages/404', ['title' => 'Order Not Found']), 404);
        }

        return Response::html(View::render('pages/order-success', [
            'title'   => 'Order Confirmed — GlassCart',
            'order'   => $order,
            'noindex' => true,
        ]));
    }
}
