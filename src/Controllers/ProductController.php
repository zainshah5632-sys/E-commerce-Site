<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\ProductRepository;

final class ProductController
{
    public function show(Request $request): Response
    {
        $slug = (string)$request->param('slug');
        $product = ProductRepository::findBySlug($slug);

        if (!$product) {
            return Response::html(View::render('pages/404', ['title' => 'Product Not Found']), 404);
        }

        $categoryId = $product['category_id'] ?? '';
        $related = $categoryId !== '' ? ProductRepository::related($categoryId, $product['id'], 4) : [];

        return Response::html(View::render('pages/product', [
            'title'   => "{$product['name']} — GlassCart",
            'product' => $product,
            'related' => $related,
        ]));
    }
}
