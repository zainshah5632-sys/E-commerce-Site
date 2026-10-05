<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

final class ShopController
{
    public function index(Request $request): Response
    {
        return $this->renderCatalog($request);
    }

    public function category(Request $request): Response
    {
        $slug = (string)$request->param('slug');
        $cat = CategoryRepository::findBySlug($slug);
        if (!$cat) {
            return Response::html(View::render('pages/404', ['title' => 'Category Not Found']), 404);
        }

        return $this->renderCatalog($request, $cat);
    }

    private function renderCatalog(Request $request, ?array $activeCategory = null): Response
    {
        $categories = CategoryRepository::allActive();

        $page = max(1, $request->int('page', 1));
        $q = trim((string)$request->query('q', ''));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = (string)$request->query('sort', 'newest');

        $selectedCategorySlug = $activeCategory['slug'] ?? (string)$request->query('category', '');
        $selectedCategoryId = null;

        if ($activeCategory) {
            $selectedCategoryId = $activeCategory['id'];
        } elseif ($selectedCategorySlug !== '') {
            foreach ($categories as $c) {
                if ($c['slug'] === $selectedCategorySlug) {
                    $selectedCategoryId = $c['id'];
                    $activeCategory = $c;
                    break;
                }
            }
        }

        $filters = [
            'q'           => $q,
            'category_id' => $selectedCategoryId,
            'min_price'   => $minPrice,
            'max_price'   => $maxPrice,
            'sort'        => $sort,
        ];

        $catalog = ProductRepository::list($filters, $page, 12);

        $title = $activeCategory ? "{$activeCategory['name']} — GlassCart" : 'All Products — GlassCart';

        $counts = CategoryRepository::counts();

        return Response::html(View::render('pages/shop', [
            'title'          => $title,
            'categories'     => $categories,
            'activeCategory' => $activeCategory,
            'categoryCounts' => $counts,
            'allTotal'       => array_sum($counts),
            'products'       => $catalog['products'],
            'total'          => $catalog['total'],
            'page'           => $catalog['page'],
            'pages'          => $catalog['pages'],
            'q'              => $q,
            'minPrice'       => $minPrice,
            'maxPrice'       => $maxPrice,
            'sort'           => $sort,
        ]));
    }
}
