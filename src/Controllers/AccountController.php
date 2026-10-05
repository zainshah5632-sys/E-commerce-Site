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

final class AccountController
{
    public function index(Request $request): Response
    {
        $session = Session::instance();
        $uid = (string)$session->userId();
        $profile = ProfileRepository::find($uid) ?? [
            'id'        => $uid,
            'full_name' => $session->userName(),
            'phone'     => '',
            'role'      => $session->userRole(),
        ];

        return Response::html(View::render('pages/account', [
            'title'   => 'My Account — GlassCart',
            'profile' => $profile,
            'email'   => $session->userEmail(),
            'errors'  => [],
            'noindex' => true,
        ]));
    }

    public function update(Request $request): Response
    {
        $session = Session::instance();
        $uid = (string)$session->userId();
        $fullName = $request->string('full_name');
        $username = strtolower(trim($request->string('username')));
        $countryCode = trim($request->string('country_code', '+1'));
        $rawPhone = $request->string('phone');
        $phone = preg_replace('/[^0-9]/', '', $rawPhone);

        $rules = [
            'full_name'    => 'required|alpha_spaces|min:2|max:100',
            'country_code' => 'max:6',
        ];

        if ($username !== '') {
            $rules['username'] = 'username';
        }

        if ($rawPhone !== '') {
            $rules['phone'] = 'phone_11';
        }

        $validator = Validator::make([
            'full_name'    => $fullName,
            'username'     => $username,
            'country_code' => $countryCode,
            'phone'        => $phone,
        ], $rules);

        if ($validator->fails()) {
            return Response::html(View::render('pages/account', [
                'title'   => 'My Account | GlassCart',
                'profile' => [
                    'id'           => $uid,
                    'full_name'    => $fullName,
                    'username'     => $username,
                    'country_code' => $countryCode,
                    'phone'        => $phone,
                ],
                'email'   => $session->userEmail(),
                'errors'  => $validator->errors(),
            ]), 422);
        }

        // Check unique username if altered
        if ($username !== '') {
            $existing = ProfileRepository::findByUsername($username);
            if ($existing && ($existing['id'] ?? '') !== $uid) {
                return Response::html(View::render('pages/account', [
                    'title'   => 'My Account | GlassCart',
                    'profile' => [
                        'id'           => $uid,
                        'full_name'    => $fullName,
                        'username'     => $username,
                        'country_code' => $countryCode,
                        'phone'        => $phone,
                    ],
                    'email'   => $session->userEmail(),
                    'errors'  => ['username' => 'This username is already taken.'],
                ]), 422);
            }
        }

        ProfileRepository::update($uid, [
            'full_name'    => $fullName,
            'username'     => $username,
            'country_code' => $countryCode,
            'phone'        => $phone,
        ]);

        $session->set('name', $fullName);
        $session->flash('success', 'Profile updated successfully.');
        return Response::redirect('/account');

    }

    public function orders(Request $request): Response
    {
        $session = Session::instance();
        $uid = (string)$session->userId();
        $page = max(1, $request->int('page', 1));

        $data = OrderRepository::listByUser($uid, $page, 10);

        return Response::html(View::render('pages/account-orders', [
            'title'      => 'Order History — GlassCart',
            'orders'     => $data['orders'],
            'total'      => $data['total'],
            'page'       => $data['page'],
            'pages'      => $data['pages'],
            'noindex'    => true,
        ]));
    }

    public function order(Request $request): Response
    {
        $session = Session::instance();
        $uid = (string)$session->userId();
        $orderNumber = (string)$request->param('number');

        $order = OrderRepository::findByNumber($orderNumber);

        // Security check: only allow order owner (or admin) to view order detail
        if (!$order || ($order['user_id'] !== $uid && !$session->isAdmin())) {
            return Response::html(View::render('pages/404', ['title' => 'Order Not Found']), 404);
        }

        return Response::html(View::render('pages/order-detail', [
            'title'   => "Order {$orderNumber} — GlassCart",
            'order'   => $order,
            'noindex' => true,
        ]));
    }
}
