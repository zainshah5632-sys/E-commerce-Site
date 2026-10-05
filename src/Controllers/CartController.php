<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CartService;

final class CartController
{
    public function index(Request $request): Response
    {
        $cartSummary = CartService::summary();

        return Response::html(View::render('pages/cart', [
            'title'   => 'Shopping Cart — GlassCart',
            'summary' => $cartSummary,
            'noindex' => true,
        ]));
    }

    public function mini(Request $request): Response
    {
        $cartSummary = CartService::summary();
        $html = View::render('partials/mini-cart', ['summary' => $cartSummary], null);

        return Response::json([
            'ok'                      => true,
            'count'                   => $cartSummary['count'],
            'subtotal_cents'          => $cartSummary['subtotal_cents'],
            'total_cents'             => $cartSummary['total_cents'],
            'free_shipping_remaining' => $cartSummary['free_shipping_remaining'],
            'html'                    => $html,
        ]);
    }

    public function add(Request $request): Response
    {
        $productId = $request->string('product_id');
        $qty = max(1, $request->int('quantity', 1));

        $res = CartService::add($productId, $qty);

        if ($request->wantsJson()) {
            $cartSummary = CartService::summary();
            $miniHtml = View::render('partials/mini-cart', ['summary' => $cartSummary], null);
            return Response::json([
                'ok'      => $res['ok'],
                'message' => $res['message'],
                'count'   => $cartSummary['count'],
                'html'    => $miniHtml,
            ], $res['ok'] ? 200 : 400);
        }

        if ($res['ok']) {
            Session::instance()->flash('success', $res['message']);
        } else {
            Session::instance()->flash('error', $res['message']);
        }

        return Response::redirect('/cart');
    }

    public function update(Request $request): Response
    {
        $productId = $request->string('product_id');
        $qty = max(0, $request->int('quantity', 1));

        $res = CartService::update($productId, $qty);

        if ($request->wantsJson()) {
            $cartSummary = CartService::summary();
            $miniHtml = View::render('partials/mini-cart', ['summary' => $cartSummary], null);
            return Response::json([
                'ok'      => $res['ok'],
                'message' => $res['message'],
                'count'   => $cartSummary['count'],
                'html'    => $miniHtml,
            ]);
        }

        Session::instance()->flash('info', $res['message']);
        return Response::redirect('/cart');
    }

    public function remove(Request $request): Response
    {
        $productId = $request->string('product_id');
        CartService::remove($productId);

        if ($request->wantsJson()) {
            $cartSummary = CartService::summary();
            $miniHtml = View::render('partials/mini-cart', ['summary' => $cartSummary], null);
            return Response::json([
                'ok'      => true,
                'message' => 'Item removed from cart.',
                'count'   => $cartSummary['count'],
                'html'    => $miniHtml,
            ]);
        }

        Session::instance()->flash('info', 'Item removed from cart.');
        return Response::redirect('/cart');
    }
}
