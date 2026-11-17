<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Supabase\Client;

final class CategoryRepository
{
    private const CACHE_KEY = 'categories_active';
    private const TTL = 60;

    /**
     * Seed fallback categories when database is unreachable.
     */
    public static function defaults(): array
    {
        return [
            [
                'id'          => 'c1000000-0000-0000-0000-000000000001',
                'name'        => 'Audio',
                'slug'        => 'audio',
                'description' => 'Premium acoustic transducers, studio headphones, and desktop monitors.',
                'image_url'   => null,
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'id'          => 'c1000000-0000-0000-0000-000000000002',
                'name'        => 'Wearables',
                'slug'        => 'wearables',
                'description' => 'Next-generation biometric timepieces and everyday optical accessories.',
                'image_url'   => null,
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'id'          => 'c1000000-0000-0000-0000-000000000003',
                'name'        => 'Desk & Home',
                'slug'        => 'desk-home',
                'description' => 'Sculptural desk lighting, acrylic stands, and workspace ergonomics.',
                'image_url'   => null,
                'sort_order'  => 3,
                'is_active'   => true,
            ],
            [
                'id'          => 'c1000000-0000-0000-0000-000000000004',
                'name'        => 'Accessories',
                'slug'        => 'accessories',
                'description' => 'Braided power cables, aluminum hubs, and portable protective sleeves.',
                'image_url'   => null,
                'sort_order'  => 4,
                'is_active'   => true,
            ],
        ];
    }

    public static function allActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, static function (): array {
            $client = new Client();
            $res = $client->get('categories', [
                'is_active' => 'eq.true',
                'order'     => 'sort_order.asc,name.asc',
                'select'    => 'id,name,slug,description,image_url,sort_order',
            ]);
            if ($res->ok && is_array($res->data) && !empty($res->data)) {
                return $res->data;
            }
            return self::defaults();
        });
    }

    public static function counts(): array
    {
        return Cache::remember('categories_product_counts', self::TTL, static function (): array {
            $client = new Client();
            $res = $client->get('products', [
                'is_active' => 'eq.true',
                'select'    => 'category_id',
            ]);
            $counts = [];
            if ($res->ok && is_array($res->data) && !empty($res->data)) {
                foreach ($res->data as $p) {
                    $cid = $p['category_id'] ?? '';
                    if ($cid !== '') {
                        $counts[$cid] = ($counts[$cid] ?? 0) + 1;
                    }
                }
                return $counts;
            }

            // Fallback product counts from default products
            foreach (ProductRepository::defaults() as $p) {
                if (!empty($p['is_active'])) {
                    $cid = $p['category_id'] ?? '';
                    if ($cid !== '') {
                        $counts[$cid] = ($counts[$cid] ?? 0) + 1;
                    }
                }
            }
            return $counts;
        });
    }

    public static function allAdmin(): array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'order'  => 'sort_order.asc,name.asc',
            'select' => 'id,name,slug,description,image_url,sort_order,is_active,created_at',
        ]);
        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return $res->data;
        }
        return self::defaults();
    }

    public static function findBySlug(string $slug): ?array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'slug'   => 'eq.' . $slug,
            'select' => 'id,name,slug,description,image_url,sort_order,is_active',
            'limit'  => '1',
        ], ['single' => true]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return $res->data;
        }

        foreach (self::defaults() as $cat) {
            if ($cat['slug'] === $slug) {
                return $cat;
            }
        }

        return null;
    }

    public static function find(string $id): ?array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'id'     => 'eq.' . $id,
            'select' => 'id,name,slug,description,image_url,sort_order,is_active',
            'limit'  => '1',
        ], ['single' => true]);

        if ($res->ok && is_array($res->data) && !empty($res->data)) {
            return $res->data;
        }

        foreach (self::defaults() as $cat) {
            if ($cat['id'] === $id) {
                return $cat;
            }
        }

        return null;
    }

    public static function create(array $data): bool
    {
        $client = new Client();
        $res = $client->insert('categories', $data);
        Cache::forget(self::CACHE_KEY);
        Cache::forget('categories_product_counts');
        return $res->ok;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('categories', ['id' => 'eq.' . $id], $patch);
        Cache::forget(self::CACHE_KEY);
        Cache::forget('categories_product_counts');
        return $res->ok;
    }

    public static function delete(string $id): bool
    {
        $client = new Client();
        $res = $client->delete('categories', ['id' => 'eq.' . $id]);
        Cache::forget(self::CACHE_KEY);
        Cache::forget('categories_product_counts');
        return $res->ok;
    }
}
