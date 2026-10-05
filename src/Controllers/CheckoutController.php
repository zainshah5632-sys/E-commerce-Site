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
use App\Services\CartService;
use App\Services\CheckoutService;

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

        return Response::html(View::render('pages/checkout', [
            'title'       => 'Secure Checkout — GlassCart',
            'summary'     => $summary,
            'userProfile' => $userProfile,
            'errors'      => [],
            'noindex'     => true,
        ]));
    }

    public function place(Request $request): Response
    {
        $summary = CartService::summary();
        if (empty($summary['items'])) {
            return Response::redirect('/shop');
        }

        $session = Session::instance();
        $data = [
            'customer_name'  => $request->string('customer_name'),
            'email'          => $request->string('email'),
            'phone'          => $request->string('phone'),
            'address_line_1' => $request->string('address_line_1'),
            'address_line_2' => $request->string('address_line_2'),
            'city'           => $request->string('city'),
            'state'          => $request->string('state'),
            'postal_code'    => $request->string('postal_code'),
            'country'        => $request->string('country', 'US'),
            'payment_method' => $request->string('payment_method', 'cod'),
            'notes'          => $request->string('notes'),
        ];

        $validator = Validator::make($data, [
            'customer_name'  => 'required|min:2|max:100',
            'email'          => 'required|email|max:150',
            'phone'          => 'required|min:6|max:30',
            'address_line_1' => 'required|min:3|max:150',
            'city'           => 'required|min:2|max:80',
            'state'          => 'required|min:2|max:80',
            'postal_code'    => 'required|min:3|max:20',
            'payment_method' => 'required|in:cod,bank_transfer',
        ]);

        if ($validator->fails()) {
            $userProfile = $session->isAuthenticated() ? ProfileRepository::find((string)$session->userId()) : null;
            return Response::html(View::render('pages/checkout', [
                'title'       => 'Secure Checkout — GlassCart',
                'summary'     => $summary,
                'userProfile' => $userProfile,
                'errors'      => $validator->errors(),
            ]), 422);
        }

        $res = CheckoutService::process($data);
        if (!$res['ok']) {
            $userProfile = $session->isAuthenticated() ? ProfileRepository::find((string)$session->userId()) : null;
            return Response::html(View::render('pages/checkout', [
                'title'       => 'Secure Checkout — GlassCart',
                'summary'     => $summary,
                'userProfile' => $userProfile,
                'errors'      => ['checkout' => $res['error']],
            ]), 400);
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
