<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\OrderRepository;

final class OrderAdminController
{
    public function index(Request $request): Response
    {
        $page = max(1, $request->int('page', 1));
        $filters = [
            'status' => $request->query('status', ''),
            'q'      => $request->query('q', ''),
        ];

        $data = OrderRepository::adminList($filters, $page, 20);

        return Response::html(View::render('admin/orders-index', [
            'title'   => 'Orders — GlassCart Admin',
            'orders'  => $data['orders'],
            'total'   => $data['total'],
            'page'    => $data['page'],
            'pages'   => $data['pages'],
            'filters' => $filters,
        ], layout: 'admin'));
    }

    public function show(Request $request): Response
    {
        $number = (string)$request->param('number');
        $order = OrderRepository::findByNumber($number);

        if (!$order) {
            return Response::html(View::render('pages/404', ['title' => 'Order Not Found']), 404);
        }

        return Response::html(View::render('admin/order-show', [
            'title' => "Order {$number} — GlassCart Admin",
            'order' => $order,
        ], layout: 'admin'));
    }

    public function status(Request $request): Response
    {
        $number = (string)$request->param('number');
        $newStatus = $request->string('status');

        $ok = OrderRepository::updateStatus($number, $newStatus);
        if ($ok) {
            Session::instance()->flash('success', "Order status updated to {$newStatus}.");
        } else {
            Session::instance()->flash('error', 'Invalid status transition.');
        }

        return Response::redirect("/admin/orders/{$number}");
    }

    public function payment(Request $request): Response
    {
        $number = (string)$request->param('number');
        $newPaymentStatus = $request->string('payment_status');

        $ok = OrderRepository::updatePayment($number, $newPaymentStatus);
        if ($ok) {
            Session::instance()->flash('success', "Payment status marked as {$newPaymentStatus}.");
        } else {
            Session::instance()->flash('error', 'Invalid payment status.');
        }

        return Response::redirect("/admin/orders/{$number}");
    }

    public function cancel(Request $request): Response
    {
        $number = (string)$request->param('number');
        $order = OrderRepository::findByNumber($number);

        if (!$order) {
            return Response::html(View::render('pages/404', ['title' => 'Order Not Found']), 404);
        }

        $res = OrderRepository::cancel((string)$order['id']);
        if ($res['ok']) {
            Session::instance()->flash('success', "Order {$number} has been cancelled and inventory restocked.");
        } else {
            Session::instance()->flash('error', $res['error']);
        }

        return Response::redirect("/admin/orders/{$number}");
    }
}
