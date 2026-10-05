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
        ], ['single' => true]);

        if ($res->ok && is_array($res->data)) {
            return $res->data;
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
        ], ['single' => true]);

        if ($res->ok && is_array($res->data)) {
            return $res->data;
        }

        return null;
    }

    public static function upsert(string $id, array $data): bool
    {
        $client = new Client();
        $payload = array_merge($data, ['id' => $id]);
        $res = $client->insert('profiles', $payload, true);
        return $res->ok;
    }

    public static function update(string $id, array $patch): bool
    {
        $client = new Client();
        $res = $client->update('profiles', ['id' => 'eq.' . $id], $patch);
        return $res->ok;
    }
}
