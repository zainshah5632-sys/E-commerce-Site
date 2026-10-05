<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Supabase\Client;

final class CategoryRepository
{
    private const CACHE_KEY = 'categories_active';
    private const TTL = 60;

    public static function allActive(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, static function (): array {
            $client = new Client();
            $res = $client->get('categories', [
                'is_active' => 'eq.true',
                'order'     => 'sort_order.asc,name.asc',
                'select'    => 'id,name,slug,description,image_url,sort_order',
            ]);
            return ($res->ok && is_array($res->data)) ? $res->data : [];
        });
    }

    public static function allAdmin(): array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'order'  => 'sort_order.asc,name.asc',
            'select' => 'id,name,slug,description,image_url,sort_order,is_active,created_at',
        ]);
        return ($res->ok && is_array($res->data)) ? $res->data : [];
    }

    public static function findBySlug(string $slug): ?array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'slug'   => 'eq.' . $slug,
            'select' => 'id,name,slug,description,image_url,sort_order,is_active',
            'limit'  => '1',
        ], ['single' => true]);

        return ($res->ok && is_array($res->data)) ? $res->data : null;
    }

    public static function find(string $id): ?array
    {
        $client = new Client();
        $res = $client->get('categories', [
            'id'     => 'eq.' . $id,
            'select' => 'id,name,slug,description,image_url,sort_order,is_active',
            'limit'  => '1',
        ], ['single' => true]);

        return ($res->ok && is_array($res->data)) ? $res->data : null;
    }

    public static function create(array $data): bool
    {
        $client = new Client();
        $res = $client->insert('categories', $data);
        Cache::forget(self::CACHE_KEY);
        return $res->ok;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('categories', ['id' => 'eq.' . $id], $patch);
        Cache::forget(self::CACHE_KEY);
        return $res->ok;
    }

    public static function delete(string $id): bool
    {
        $client = new Client();
        $res = $client->delete('categories', ['id' => 'eq.' . $id]);
        Cache::forget(self::CACHE_KEY);
        return $res->ok;
    }
}
