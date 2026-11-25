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
        $easypaisaMobile = preg_replace('/[^0-9]/', '', (string)$request->string('easypaisa_mobile'));
        $jazzcashMobile = preg_replace('/[^0-9]/', '', (string)$request->string('jazzcash_mobile'));
        $jazzcashCnic = preg_replace('/[^0-9]/', '', (string)$request->string('jazzcash_cnic'));

        $data = [
            'customer_name'    => $request->string('customer_name'),
            'email'            => $request->string('email'),
            'phone'            => $phone,
            'country_code'     => $countryCode,
            'address_line_1'   => $request->string('address_line_1'),
            'address_line_2'   => $request->string('address_line_2'),
            'city'             => $request->string('city'),
            'state'            => $request->string('state'),
            'postal_code'      => $request->string('postal_code'),
            'country'          => $request->string('country', 'PK'),
            'payment_method'   => $paymentMethod,
            'easypaisa_mobile' => $easypaisaMobile,
            'jazzcash_mobile'  => $jazzcashMobile,
            'jazzcash_cnic'    => $jazzcashCnic,
            'notes'            => $request->string('notes'),
        ];

        $rules = [
            'customer_name'  => 'required|alpha_spaces|min:2|max:100',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|phone_11',
            'address_line_1' => 'required|min:3|max:150',
            'city'           => 'required|min:2|max:80',
            'state'          => 'required|min:2|max:80',
            'postal_code'    => 'required|min:3|max:20',
            'payment_method' => 'required|in:cod,bank_transfer,easypaisa,jazzcash,card',
        ];

        $validator = Validator::make($data, $rules);

        $customErrors = [];
        if ($paymentMethod === 'easypaisa') {
            if (!preg_match('/^03[0-9]{9}$/', $easypaisaMobile)) {
                $customErrors['easypaisa_mobile'] = 'Please enter a valid 11-digit registered Easypaisa mobile number (e.g., 03120246309).';
            }
        } elseif ($paymentMethod === 'jazzcash') {
            if (!preg_match('/^03[0-9]{9}$/', $jazzcashMobile)) {
                $customErrors['jazzcash_mobile'] = 'Please enter a valid 11-digit registered JazzCash mobile number (e.g., 03001234567).';
            }
            if (!preg_match('/^[0-9]{6}$/', $jazzcashCnic)) {
                $customErrors['jazzcash_cnic'] = 'Please enter the last 6 digits of your registered CNIC.';
            }
        }

        $allErrors = array_merge($validator->errors(), $customErrors);

        $settings = SettingsRepository::all();
        $enabledMethods = SettingsRepository::enabledPaymentMethods();
        $paymentMethodsInfo = PaymentService::supportedMethods();
        $userProfile = $session->isAuthenticated() ? ProfileRepository::find((string)$session->userId()) : null;

        if (!empty($allErrors)) {
            return Response::html(View::render('pages/checkout', [
                'title'              => 'Secure Checkout — GlassCart',
                'summary'            => $summary,
                'userProfile'        => $userProfile,
                'settings'           => $settings,
                'enabledMethods'     => $enabledMethods,
                'paymentMethodsInfo' => $paymentMethodsInfo,
                'errors'             => $allErrors,
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

        if (!empty($res['awaiting_approval'])) {
            $session->flash('info', $res['message'] ?? 'A payment approval notification was sent to your phone! Please enter your PIN to authorize.');
        } elseif (($res['status'] ?? '') === 'paid') {
            $session->flash('success', 'Your online payment was verified and confirmed!');
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
