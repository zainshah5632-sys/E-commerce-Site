<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

final class DashboardController
{
    public function index(Request $request): Response
    {
        $stats = OrderRepository::stats();
        $recentOrders = OrderRepository::adminList([], 1, 8);
        $lowStockProducts = ProductRepository::lowStock(5);

        return Response::html(View::render('admin/dashboard', [
            'title'            => 'Admin Dashboard — GlassCart',
            'stats'            => $stats,
            'recentOrders'     => $recentOrders['orders'],
            'lowStockProducts' => $lowStockProducts,
        ], layout: 'admin'));
    }
}
