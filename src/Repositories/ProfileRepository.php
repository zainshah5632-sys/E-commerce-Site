<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Supabase\Client;

final class ProfileRepository
{
    public static function find(string $id): ?array
    {
        $client = new Client();
        $res = $client->get('profiles', [
            'id'     => 'eq.' . $id,
            'select' => 'id,email,full_name,username,phone,country_code,role,created_at',
            'limit'  => '1',
        ]);

        if ($res->ok && is_array($res->data) && !empty($res->data[0])) {
            return $res->data[0];
        }

        return null;
    }

    public static function findByUsername(string $username): ?array
    {
        $client = new Client();
        $res = $client->get('profiles', [
            'username' => 'eq.' . $username,
            'select'   => 'id,email,full_name,username,phone,country_code,role,created_at',
            'limit'    => '1',
        ]);

        if ($res->ok && is_array($res->data) && !empty($res->data[0])) {
            return $res->data[0];
        }

        return null;
    }

    public static function findByEmail(string $email): ?array
    {
        $client = new Client();
        $res = $client->get('profiles', [
            'email'  => 'eq.' . strtolower(trim($email)),
            'select' => 'id,email,full_name,username,phone,country_code,role,created_at',
            'limit'  => '1',
        ]);

        if ($res->ok && is_array($res->data) && !empty($res->data[0])) {
            return $res->data[0];
        }

        return null;
    }

    public static function upsert(string $id, array $data): bool
    {
        $client = new Client();
        $payload = array_merge($data, ['id' => $id]);
        $res = $client->upsert('profiles', $payload, 'id');
        if (!$res->ok) {
            $res = $client->update('profiles', ['id' => 'eq.' . $id], $data);
        }
        return $res->ok;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('profiles', ['id' => 'eq.' . $id], $patch);
        return $res->ok;
    }

    public static function delete(string $id): bool
    {
        $client = new Client();
        $res = $client->delete('profiles', ['id' => 'eq.' . $id]);
        return $res->ok;
    }

    public static function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $client = new Client();
        $query = [
            'select' => 'id,email,full_name,username,phone,country_code,role,created_at',
            'order'  => 'created_at.desc',
            'limit'  => (string)$perPage,
            'offset' => (string)(($page - 1) * $perPage),
        ];

        if (!empty($filters['role'])) {
            $query['role'] = 'eq.' . $filters['role'];
        }

        if (!empty($filters['q'])) {
            $clean = preg_replace('/[,()*%\\\\]/', '', (string)$filters['q']);
            $clean = mb_substr(trim($clean ?? ''), 0, 60);
            if ($clean !== '') {
                $query['or'] = "(full_name.ilike.*{$clean}*,email.ilike.*{$clean}*,username.ilike.*{$clean}*)";
            }
        }

        $res = $client->get('profiles', $query, ['count' => true]);
        $users = ($res->ok && is_array($res->data)) ? $res->data : [];
        $total = $res->count ?? count($users);

        return [
            'users' => $users,
            'total' => $total,
            'page'  => $page,
            'pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public static function count(): int
    {
        $client = new Client();
        $res = $client->get('profiles', ['select' => 'id', 'limit' => '1'], ['count' => true]);
        return $res->count ?? 0;
    }
}

