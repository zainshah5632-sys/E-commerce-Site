<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Supabase\Client;

final class ProductRepository
{
    private const CACHE_FEATURED = 'products_featured';
    private const CACHE_NEWEST = 'products_newest';
    private const TTL = 60;

    public static function list(array $params = [], int $page = 1, int $perPage = 12): array
    {
        $client = new Client();
        $query = [
            'select'    => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,is_featured,category_id,category:categories(name,slug)',
            'is_active' => 'eq.true',
            'limit'     => (string)$perPage,
            'offset'    => (string)(($page - 1) * $perPage),
        ];

        // Category filter
        if (!empty($params['category_id'])) {
            $query['category_id'] = 'eq.' . $params['category_id'];
        }

        // Min price
        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $query['price_cents'] = 'gte.' . ((int)$params['min_price'] * 100);
        }

        // Max price
        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $maxCents = (int)$params['max_price'] * 100;
            if (isset($query['price_cents'])) {
                // If min also present, use and=()
                $minCents = (int)$params['min_price'] * 100;
                unset($query['price_cents']);
                $query['and'] = "(price_cents.gte.{$minCents},price_cents.lte.{$maxCents})";
            } else {
                $query['price_cents'] = 'lte.' . $maxCents;
            }
        }

        // Search term (sanitize characters per AR §8.2)
        if (!empty($params['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$params['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(name.ilike.*{$clean}*,short_description.ilike.*{$clean}*)";
            }
        }

        // Sorting whitelist
        $sort = $params['sort'] ?? 'newest';
        $orderMap = [
            'newest'     => 'created_at.desc',
            'price_asc'  => 'price_cents.asc',
            'price_desc' => 'price_cents.desc',
            'name'       => 'name.asc',
        ];
        $query['order'] = $orderMap[$sort] ?? 'created_at.desc';

        $res = $client->get('products', $query, ['count' => true]);

        $products = ($res->ok && is_array($res->data)) ? $res->data : [];
        $total = $res->count ?? count($products);
        $pages = (int)ceil($total / $perPage);

        return [
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, $pages),
        ];
    }

    public static function featured(int $limit = 3): array
    {
        return Cache::remember(self::CACHE_FEATURED, self::TTL, static function () use ($limit): array {
            $client = new Client();
            $res = $client->get('products', [
                'is_active'   => 'eq.true',
                'is_featured' => 'eq.true',
                'select'      => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
                'order'       => 'created_at.desc',
                'limit'       => (string)$limit,
            ]);
            return ($res->ok && is_array($res->data)) ? $res->data : [];
        });
    }

    public static function newest(int $limit = 4): array
    {
        return Cache::remember(self::CACHE_NEWEST, self::TTL, static function () use ($limit): array {
            $client = new Client();
            $res = $client->get('products', [
                'is_active' => 'eq.true',
                'select'    => 'id,name,slug,short_description,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
                'order'     => 'created_at.desc',
                'limit'     => (string)$limit,
            ]);
            return ($res->ok && is_array($res->data)) ? $res->data : [];
        });
    }

    public static function findBySlug(string $slug): ?array
    {
        $client = new Client();
        $res = $client->get('products', [
            'slug'      => 'eq.' . $slug,
            'is_active' => 'eq.true',
            'select'    => 'id,category_id,name,slug,short_description,description,price_cents,compare_at_cents,sku,stock,images,is_active,is_featured,created_at,category:categories(id,name,slug)',
            'limit'     => '1',
        ], ['single' => true]);

        return ($res->ok && is_array($res->data)) ? $res->data : null;
    }

    public static function findById(string $id): ?array
    {
        $client = new Client();
        $res = $client->get('products', [
            'id'     => 'eq.' . $id,
            'select' => 'id,category_id,name,slug,short_description,description,price_cents,compare_at_cents,sku,stock,images,is_active,is_featured,created_at,updated_at,category:categories(id,name,slug)',
            'limit'  => '1',
        ], ['single' => true]);

        return ($res->ok && is_array($res->data)) ? $res->data : null;
    }

    public static function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Validate all are valid UUIDs
        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $validIds = array_filter($ids, static fn($id) => is_string($id) && preg_match($uuidRegex, $id));
        if (empty($validIds)) {
            return [];
        }

        $client = new Client();
        $res = $client->get('products', [
            'id'        => 'in.(' . implode(',', $validIds) . ')',
            'is_active' => 'eq.true',
            'select'    => 'id,name,slug,price_cents,compare_at_cents,images,stock,category_id',
        ]);

        return ($res->ok && is_array($res->data)) ? $res->data : [];
    }

    public static function related(string $categoryId, string $excludeId, int $limit = 4): array
    {
        $client = new Client();
        $res = $client->get('products', [
            'category_id' => 'eq.' . $categoryId,
            'id'          => 'neq.' . $excludeId,
            'is_active'   => 'eq.true',
            'select'      => 'id,name,slug,price_cents,compare_at_cents,images,stock,category:categories(name,slug)',
            'order'       => 'created_at.desc',
            'limit'       => (string)$limit,
        ]);

        return ($res->ok && is_array($res->data)) ? $res->data : [];
    }

    public static function adminList(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $client = new Client();
        $query = [
            'select' => 'id,category_id,name,slug,price_cents,sku,stock,images,is_active,is_featured,created_at,category:categories(name,slug)',
            'order'  => 'created_at.desc',
            'limit'  => (string)$perPage,
            'offset' => (string)(($page - 1) * $perPage),
        ];

        if (!empty($filters['category_id'])) {
            $query['category_id'] = 'eq.' . $filters['category_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query['is_active'] = 'eq.' . ($filters['is_active'] ? 'true' : 'false');
        }

        if (!empty($filters['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$filters['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(name.ilike.*{$clean}*,sku.ilike.*{$clean}*)";
            }
        }

        $res = $client->get('products', $query, ['count' => true]);
        $products = ($res->ok && is_array($res->data)) ? $res->data : [];
        $total = $res->count ?? count($products);

        return [
            'products' => $products,
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function create(array $data): ?array
    {
        $client = new Client();
        $res = $client->insert('products', $data, true);
        self::forgetCache();
        return ($res->ok && is_array($res->data)) ? ($res->data[0] ?? $res->data) : null;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('products', ['id' => 'eq.' . $id], $patch);
        self::forgetCache();
        return $res->ok;
    }

    public static function delete(string $id): bool
    {
        $client = new Client();
        $res = $client->delete('products', ['id' => 'eq.' . $id]);
        self::forgetCache();
        return $res->ok;
    }

    public static function toggle(string $id, string $field): bool
    {
        if (!in_array($field, ['is_active', 'is_featured'], true)) {
            return false;
        }

        $prod = self::findById($id);
        if (!$prod) {
            return false;
        }

        $newVal = !($prod[$field] ?? false);
        return self::update($id, [$field => $newVal]);
    }

    public static function lowStock(int $threshold = 5): array
    {
        $client = new Client();
        $res = $client->get('products', [
            'is_active' => 'eq.true',
            'stock'     => 'lte.' . $threshold,
            'select'    => 'id,name,slug,sku,stock,price_cents',
            'order'     => 'stock.asc',
            'limit'     => '10',
        ]);
        return ($res->ok && is_array($res->data)) ? $res->data : [];
    }

    private static function forgetCache(): void
    {
        Cache::forget(self::CACHE_FEATURED);
        Cache::forget(self::CACHE_NEWEST);
    }
}
