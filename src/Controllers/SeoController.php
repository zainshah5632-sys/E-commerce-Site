<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;

final class SeoController
{
    public function sitemap(Request $request): Response
    {
        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');

        $staticPages = ['/', '/shop', '/page/about', '/page/contact', '/page/shipping', '/page/returns', '/page/privacy', '/page/terms'];
        $categories = CategoryRepository::allActive();
        $catalog = ProductRepository::list([], 1, 300);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($staticPages as $path) {
            $xml .= "  <url>\n    <loc>{$appUrl}{$path}</loc>\n    <changefreq>daily</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
        }

        foreach ($categories as $cat) {
            $loc = "{$appUrl}/category/" . rawurlencode($cat['slug']);
            $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.7</priority>\n  </url>\n";
        }

        foreach ($catalog['products'] as $prod) {
            $loc = "{$appUrl}/product/" . rawurlencode($prod['slug']);
            $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <changefreq>weekly</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
        }

        $xml .= '</urlset>';

        return (new Response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']));
    }

    public function robots(Request $request): Response
    {
        $appUrl = rtrim(Env::get('APP_URL', 'http://localhost:8080'), '/');

        $content = "User-agent: *\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /cart\n";
        $content .= "Disallow: /checkout\n";
        $content .= "Disallow: /account\n";
        $content .= "Disallow: /account/\n\n";
        $content .= "Sitemap: {$appUrl}/sitemap.xml\n";

        return (new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']));
    }
}
