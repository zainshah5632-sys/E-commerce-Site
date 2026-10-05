<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

final class HomeController
{
    public function index(Request $request): Response
    {
        $categories = CategoryRepository::allActive();
        $featured = ProductRepository::featured(3);
        $newArrivals = ProductRepository::newest(4);

        return Response::html(View::render('pages/home', [
            'title'       => 'GlassCart — Modern Minimalist Hardware & Audio',
            'categories'  => $categories,
            'featured'    => $featured,
            'newArrivals' => $newArrivals,
        ]));
    }
}
