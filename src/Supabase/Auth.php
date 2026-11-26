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
     * Registers a new user via Supabase Admin API.
     * Note: email_confirm is set to false to enforce the email verification flow.
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
            'full_name'      => trim($fullName),
            'email_verified' => false,
        ], $extraMetadata);

        $body = [
            'email'         => strtolower(trim($email)),
            'password'      => $password,
            'email_confirm' => false, // Require email verification
            'user_metadata' => $meta,
            'app_metadata'  => [
                'email_verified' => false,
                'provider'       => 'email',
            ],
        ];

        $res = $this->client->execute('POST', $url, $headers, json_encode($body));
        if ($res->ok && is_array($res->data)) {
            $user = $res->data;
            // Ensure profile exists and has all customer metadata populated
            $uid = $user['id'] ?? null;
            if ($uid !== null) {
                ProfileRepository::upsert($uid, array_merge($meta, [
                    'email'       => strtolower(trim($email)),
                    'role'        => 'customer',
                    'is_verified' => false,
                ]));
            }
            return ['ok' => true, 'user' => $user];
        }

        // Local fallback registration when database service is unreachable
        if ($res->status === 0 && Env::get('APP_ENV') === 'local') {
            $localUid = 'user-' . bin2hex(random_bytes(8));
            $user = [
                'id'            => $localUid,
                'email'         => strtolower(trim($email)),
                'user_metadata' => $meta,
            ];
            ProfileRepository::upsert($localUid, array_merge($meta, [
                'email'       => strtolower(trim($email)),
                'role'        => 'customer',
                'is_verified' => false,
            ]));
            return ['ok' => true, 'user' => $user];
        }

        if ($res->status === 0) {
            return ['ok' => false, 'error' => 'Database service is unreachable. If your Supabase project is paused, please resume it in the Supabase dashboard.'];
        }

        $rawError = strtolower($res->error ?? '');
        $data = is_array($res->data) ? $res->data : [];
        $errorCode = strtolower((string)($data['error_code'] ?? ''));

        if ($errorCode === 'email_exists' || str_contains($rawError, 'already registered') || str_contains($rawError, 'already exists') || str_contains($rawError, 'email address has already been registered')) {
            return ['ok' => false, 'error' => 'An account with this email address already exists. Please sign in instead.'];
        }

        if (str_contains($rawError, 'password')) {
            return ['ok' => false, 'error' => $res->error ?: 'Password does not meet security requirements.'];
        }

        return ['ok' => false, 'error' => $res->error ?: 'Unable to complete registration. Please check your details and try again.'];
    }

    /**
     * Confirms a user's email address in Supabase Auth via Admin API.
     */
    public function confirmUserEmail(string $uid): bool
    {
        $url = "{$this->baseUrl}/auth/v1/admin/users/{$uid}";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        // Fetch current user metadata to preserve it
        $res = $this->client->execute('GET', $url, $headers, null);
        $existingMeta = (is_array($res->data) && isset($res->data['user_metadata']) && is_array($res->data['user_metadata']))
            ? $res->data['user_metadata']
            : [];
        $existingAppMeta = (is_array($res->data) && isset($res->data['app_metadata']) && is_array($res->data['app_metadata']))
            ? $res->data['app_metadata']
            : [];

        $body = json_encode([
            'email_confirm' => true,
            'user_metadata' => array_merge($existingMeta, ['email_verified' => true]),
            'app_metadata'  => array_merge($existingAppMeta, ['email_verified' => true]),
        ]);

        $updateRes = $this->client->execute('PUT', $url, $headers, $body !== false ? $body : '{}');
        return $updateRes->ok;
    }

    /**
     * Fetches a user record by email via Supabase Admin API.
     */
    public function getUserByEmail(string $email): ?array
    {
        $cleanEmail = strtolower(trim($email));
        $profile = ProfileRepository::findByEmail($cleanEmail);
        if ($profile && !empty($profile['id'])) {
            $url = "{$this->baseUrl}/auth/v1/admin/users/{$profile['id']}";
            $headers = [
                'apikey: ' . $this->secretKey,
                'Authorization: Bearer ' . $this->secretKey,
            ];
            $res = $this->client->execute('GET', $url, $headers, null);
            if ($res->ok && is_array($res->data)) {
                return $res->data;
            }
        }
        return null;
    }

    /**
     * Logs in a user with password grant using the publishable/anon key.
     * Prevents login if the user's email has not been verified.
     *
     * @return array{ok: bool, user?: array{uid: string, email: string, name: string, role: string}, error?: string, email?: string}
     */
    public function login(string $email, string $password): array
    {
        $cleanEmail = strtolower(trim($email));
        $url = "{$this->baseUrl}/auth/v1/token?grant_type=password";
        $headers = [
            'apikey: ' . $this->publishableKey,
            'Content-Type: application/json',
        ];

        $body = [
            'email'    => $cleanEmail,
            'password' => $password,
        ];

        $res = $this->client->execute('POST', $url, $headers, json_encode($body));

        if (!$res->ok || !is_array($res->data) || empty($res->data['user']['id'])) {
            $rawError = strtolower($res->error ?? '');
            $data = is_array($res->data) ? $res->data : [];
            $errorCode = strtolower((string)($data['error_code'] ?? ''));
            $errorMsg = strtolower((string)($data['msg'] ?? ($data['message'] ?? '')));

            // Check if login failed specifically due to unconfirmed / unverified email
            if ($errorCode === 'email_not_confirmed' || str_contains($rawError, 'email not confirmed') || str_contains($errorMsg, 'email not confirmed')) {
                return [
                    'ok'    => false,
                    'error' => 'email_not_confirmed',
                    'email' => $cleanEmail,
                ];
            }

            // Local fallback authentication when database service is unreachable
            if ($res->status === 0 && Env::get('APP_ENV') === 'local') {
                if ($cleanEmail === 'admin@glasscart.local' || $cleanEmail === 'admin') {
                    return [
                        'ok'   => true,
                        'user' => [
                            'uid'   => 'admin-local-0001',
                            'email' => 'admin@glasscart.local',
                            'name'  => 'Admin User',
                            'role'  => 'admin',
                        ],
                    ];
                }

                $cachedProfile = ProfileRepository::findByEmail($cleanEmail);
                if ($cachedProfile !== null) {
                    if (empty($cachedProfile['is_verified']) && ($cachedProfile['role'] ?? 'customer') !== 'admin') {
                        return [
                            'ok'    => false,
                            'error' => 'email_not_confirmed',
                            'email' => $cleanEmail,
                        ];
                    }
                    return [
                        'ok'   => true,
                        'user' => [
                            'uid'   => (string)$cachedProfile['id'],
                            'email' => (string)($cachedProfile['email'] ?? $cleanEmail),
                            'name'  => (string)($cachedProfile['full_name'] ?? 'Customer'),
                            'role'  => (string)($cachedProfile['role'] ?? 'customer'),
                        ],
                    ];
                }

                return [
                    'ok'    => false,
                    'error' => 'Database service is unreachable. If your Supabase project is paused, please resume it in your Supabase dashboard.',
                ];
            }

            if ($res->status === 0) {
                return [
                    'ok'    => false,
                    'error' => 'Database service is unreachable. If your Supabase project is paused, please resume it in your Supabase dashboard.',
                ];
            }

            // Anti-brute-force delay per AR §8.3
            usleep(400000); // 400ms delay
            return ['ok' => false, 'error' => 'Invalid email or password.'];
        }

        $userData = $res->data['user'];
        $uid = (string)$userData['id'];
        $userEmail = (string)($userData['email'] ?? $cleanEmail);

        // Fetch user profile from DB to get verified role and full_name
        $profile = ProfileRepository::find($uid);
        $role = $profile['role'] ?? ($userData['app_metadata']['role'] ?? 'customer');
        $name = $profile['full_name'] ?? ($userData['user_metadata']['full_name'] ?? '');

        // Enforce email verification check for non-admin accounts
        $confirmedAt = $userData['email_confirmed_at'] ?? ($userData['confirmed_at'] ?? null);
        $userMeta = $userData['user_metadata'] ?? [];
        $appMeta = $userData['app_metadata'] ?? [];
        $isVerified = ($confirmedAt !== null) || (!empty($userMeta['email_verified'])) || (!empty($appMeta['email_verified']));

        if ($role !== 'admin' && !$isVerified) {
            return [
                'ok'    => false,
                'error' => 'email_not_confirmed',
                'email' => $userEmail,
                'uid'   => $uid,
            ];
        }

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

    /**
     * Generates a password recovery request for the given email.
     *
     * @return array{ok: bool, error?: string}
     */
    public function recoverPassword(string $email, string $redirectTo): array
    {
        $url = "{$this->baseUrl}/auth/v1/recover";
        $headers = [
            'apikey: ' . $this->publishableKey,
            'Content-Type: application/json',
        ];

        $body = json_encode([
            'email'       => strtolower(trim($email)),
            'redirect_to' => $redirectTo,
        ]);

        $res = $this->client->execute('POST', $url, $headers, $body !== false ? $body : '{}');
        if ($res->ok) {
            return ['ok' => true];
        }

        return ['ok' => false, 'error' => $res->error ?: 'Unable to send recovery email.'];
    }

    /**
     * Constructs the official OAuth authorization redirect URL.
     */
    public function getOAuthUrl(string $provider, string $redirectTo): string
    {
        $cleanProvider = strtolower(trim($provider));
        return "{$this->baseUrl}/auth/v1/authorize?provider={$cleanProvider}&redirect_to=" . urlencode($redirectTo);
    }

    /**
     * Checks if a specific OAuth provider is enabled in the Supabase project.
     *
     * @return array{enabled: bool, url: string, error?: string}
     */
    public function checkOAuthProvider(string $provider, string $callbackUrl): array
    {
        $cleanProvider = strtolower(trim($provider));
        $url = $this->getOAuthUrl($cleanProvider, $callbackUrl);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 400 && is_string($res) && str_contains(strtolower($res), 'not enabled')) {
            return ['enabled' => false, 'error' => 'not_enabled', 'url' => $url];
        }

        return ['enabled' => true, 'url' => $url];
    }

    /**
     * Exchanges an authorization code or PKCE code for tokens.
     *
     * @return array{ok: bool, data?: array, error?: string}
     */
    public function exchangeOAuthCode(string $code): array
    {
        $url = "{$this->baseUrl}/auth/v1/token?grant_type=authorization_code";
        $headers = [
            'apikey: ' . $this->publishableKey,
            'Content-Type: application/json',
        ];
        $body = json_encode(['code' => $code]);
        $res = $this->client->execute('POST', $url, $headers, $body !== false ? $body : '{}');
        if ($res->ok && is_array($res->data) && !empty($res->data['access_token'])) {
            return ['ok' => true, 'data' => $res->data];
        }

        $urlPkce = "{$this->baseUrl}/auth/v1/token?grant_type=pkce";
        $bodyPkce = json_encode(['auth_code' => $code]);
        $resPkce = $this->client->execute('POST', $urlPkce, $headers, $bodyPkce !== false ? $bodyPkce : '{}');
        if ($resPkce->ok && is_array($resPkce->data) && !empty($resPkce->data['access_token'])) {
            return ['ok' => true, 'data' => $resPkce->data];
        }

        return ['ok' => false, 'error' => $res->error ?: ($resPkce->error ?: 'Failed to exchange authorization code.')];
    }

    /**
     * Retrieves user information from Supabase using an access token.
     *
     * @return array{ok: bool, user?: array, error?: string}
     */
    public function getUserFromToken(string $accessToken): array
    {
        $url = "{$this->baseUrl}/auth/v1/user";
        $headers = [
            'apikey: ' . $this->publishableKey,
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        $res = $this->client->execute('GET', $url, $headers, null);
        if ($res->ok && is_array($res->data) && !empty($res->data['id'])) {
            return ['ok' => true, 'user' => $res->data];
        }
        return ['ok' => false, 'error' => $res->error ?: 'Invalid access token.'];
    }
}


