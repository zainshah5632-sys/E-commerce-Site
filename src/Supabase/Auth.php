<?php
declare(strict_types=1);

namespace App\Supabase;

use App\Core\Env;
use App\Core\Logger;
use App\Repositories\ProfileRepository;

final class Auth
{
    private string $baseUrl;
    private string $publishableKey;
    private string $secretKey;
    private Client $client;

    public function __construct()
    {
        $this->baseUrl = rtrim(Env::require('SUPABASE_URL'), '/');
        $this->publishableKey = Env::require('SUPABASE_PUBLISHABLE_KEY');
        $this->secretKey = Env::require('SUPABASE_SECRET_KEY');
        $this->client = new Client($this->baseUrl, $this->secretKey);
    }

    /**
     * Registers a new user via Supabase Admin API with auto-confirmed email (Decision D-007).
     *
     * @return array{ok: bool, user?: array, error?: string}
     */
    public function register(string $email, string $password, string $fullName, array $extraMetadata = []): array
    {
        $url = "{$this->baseUrl}/auth/v1/admin/users";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $meta = array_merge([
            'full_name' => trim($fullName),
        ], $extraMetadata);

        $body = [
            'email'         => strtolower(trim($email)),
            'password'      => $password,
            'email_confirm' => true,
            'user_metadata' => $meta,
        ];

        $res = $this->client->execute('POST', $url, $headers, json_encode($body));
        if ($res->ok && is_array($res->data)) {
            $user = $res->data;
            // Ensure profile exists
            $uid = $user['id'] ?? null;
            if ($uid !== null) {
                ProfileRepository::upsert($uid, array_merge($meta, [
                    'role' => 'customer',
                ]));
            }
            return ['ok' => true, 'user' => $user];
        }

        $rawError = strtolower($res->error ?? '');
        if (str_contains($rawError, 'already registered') || str_contains($rawError, 'already exists')) {
            return ['ok' => false, 'error' => 'An account with this email already exists.'];
        }

        return ['ok' => false, 'error' => 'Unable to complete registration. Please check your details and try again.'];
    }

    /**
     * Logs in a user with password grant using the publishable/anon key.
     *
     * @return array{ok: bool, user?: array{uid: string, email: string, name: string, role: string}, error?: string}
     */
    public function login(string $email, string $password): array
    {
        $url = "{$this->baseUrl}/auth/v1/token?grant_type=password";
        $headers = [
            'apikey: ' . $this->publishableKey,
            'Content-Type: application/json',
        ];

        $body = [
            'email'    => strtolower(trim($email)),
            'password' => $password,
        ];

        $res = $this->client->execute('POST', $url, $headers, json_encode($body));

        if (!$res->ok || !is_array($res->data) || empty($res->data['user']['id'])) {
            // Anti-brute-force delay per AR §8.3
            usleep(400000); // 400ms delay
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        $userData = $res->data['user'];
        $uid = (string)$userData['id'];
        $userEmail = (string)($userData['email'] ?? $email);

        // Fetch user profile from DB to get verified role and full_name
        $profile = ProfileRepository::find($uid);
        $role = $profile['role'] ?? 'customer';
        $name = $profile['full_name'] ?? ($userData['user_metadata']['full_name'] ?? '');

        return [
            'ok'   => true,
            'user' => [
                'uid'   => $uid,
                'email' => $userEmail,
                'name'  => (string)$name,
                'role'  => (string)$role,
            ],
        ];
    }

    /**
     * Deletes a user account from Supabase Auth admin API.
     */
    public function deleteUser(string $uid): bool
    {
        $url = "{$this->baseUrl}/auth/v1/admin/users/{$uid}";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $res = $this->client->execute('DELETE', $url, $headers, null);
        return $res->ok;
    }

    /**
     * Updates user credentials (e.g. password, email) via Supabase Auth admin API.
     */
    public function updateUser(string $uid, array $attributes): bool
    {
        $url = "{$this->baseUrl}/auth/v1/admin/users/{$uid}";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $body = json_encode($attributes);
        $res = $this->client->execute('PUT', $url, $headers, $body !== false ? $body : '{}');
        return $res->ok;
    }
}

