<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    private const COOKIE_NAME = 'gc_s';
    private const MAX_LIFETIME = 604800; // 7 days in seconds
    private const MAX_COOKIE_BYTES = 3800;

    private static ?self $instance = null;
    private array $data = [];
    private bool $isDirty = false;
    private array $flashNow = [];

    private function __construct()
    {
        $this->load();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load(): void
    {
        $rawCookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($rawCookie !== null && is_string($rawCookie) && $rawCookie !== '') {
            $decrypted = $this->decrypt($rawCookie);
            if ($decrypted !== null && is_array($decrypted)) {
                $iat = $decrypted['iat'] ?? 0;
                if ((time() - $iat) <= self::MAX_LIFETIME) {
                    $this->data = $decrypted;
                }
            }
        }

        // Initialize defaults
        if (!isset($this->data['csrf']) || !is_string($this->data['csrf'])) {
            $this->data['csrf'] = bin2hex(random_bytes(32));
            $this->isDirty = true;
        }

        if (!isset($this->data['cart']) || !is_array($this->data['cart'])) {
            $this->data['cart'] = [];
        }

        // Read and clear flash messages for next request
        if (isset($this->data['flash']) && is_array($this->data['flash'])) {
            $this->flashNow = $this->data['flash'];
            $this->data['flash'] = [];
            $this->isDirty = true;
        } else {
            $this->data['flash'] = [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->isDirty = true;
    }

    public function remove(string $key): void
    {
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
            $this->isDirty = true;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    // Flash messages
    public function flash(string $type, string $message): void
    {
        $this->data['flash'][$type] = $message;
        $this->isDirty = true;
    }

    public function getFlash(string $type): ?string
    {
        return $this->flashNow[$type] ?? null;
    }

    public function allFlash(): array
    {
        return $this->flashNow;
    }

    // User authentication helpers
    public function setAuth(string $uid, string $email, string $name, string $role): void
    {
        $this->data['uid'] = $uid;
        $this->data['email'] = $email;
        $this->data['name'] = $name;
        $this->data['role'] = $role;
        $this->isDirty = true;
    }

    public function clearAuth(): void
    {
        unset($this->data['uid'], $this->data['email'], $this->data['name'], $this->data['role']);
        $this->isDirty = true;
    }

    public function isAuthenticated(): bool
    {
        return !empty($this->data['uid']);
    }

    public function userId(): ?string
    {
        return $this->data['uid'] ?? null;
    }

    public function userRole(): string
    {
        return $this->data['role'] ?? 'customer';
    }

    public function role(): string
    {
        return $this->userRole();
    }

    public function userName(): string
    {
        return $this->data['name'] ?? '';
    }

    public function userEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    public function isAdmin(): bool
    {
        return ($this->data['role'] ?? '') === 'admin';
    }

    // CSRF
    public function csrfToken(): string
    {
        return $this->data['csrf'];
    }

    public function validateCsrf(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }
        return hash_equals($this->data['csrf'], $token);
    }

    // Cart: [{p: 'uuid', q: int}]
    public function cart(): array
    {
        return $this->data['cart'] ?? [];
    }

    public function setCart(array $cart): bool
    {
        $backup = $this->data['cart'] ?? [];
        $this->data['cart'] = $cart;
        $this->isDirty = true;

        // Size check
        $candidate = $this->encrypt($this->data);
        if ($candidate !== null && strlen($candidate) > self::MAX_COOKIE_BYTES) {
            $this->data['cart'] = $backup;
            return false; // Cart full
        }

        return true;
    }

    public function isDirty(): bool
    {
        return $this->isDirty;
    }

    public function writeCookieHeader(): ?string
    {
        if (!$this->isDirty) {
            return null;
        }

        $this->data['iat'] = time();
        $encrypted = $this->encrypt($this->data);
        if ($encrypted === null) {
            return null;
        }

        $appUrl = Env::get('APP_URL', '');
        $isSecure = str_starts_with($appUrl, 'https://') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $expires = gmdate('D, d M Y H:i:s T', time() + self::MAX_LIFETIME);

        $cookie = self::COOKIE_NAME . '=' . $encrypted . '; Expires=' . $expires . '; Max-Age=' . self::MAX_LIFETIME . '; Path=/; SameSite=Lax; HttpOnly';
        if ($isSecure) {
            $cookie .= '; Secure';
        }

        $this->isDirty = false;
        return $cookie;
    }

    private function getEncryptionKey(): string
    {
        $secret = Env::require('APP_SECRET');
        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    private function encrypt(array $payload): ?string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }

        $key = $this->getEncryptionKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($json, $nonce, $key);

        return self::base64UrlEncode($nonce . $cipher);
    }

    private function decrypt(string $encoded): ?array
    {
        $raw = self::base64UrlDecode($encoded);
        if ($raw === null || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = $this->getEncryptionKey();

        $decrypted = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        if ($decrypted === false) {
            return null;
        }

        $parsed = json_decode($decrypted, true);
        return is_array($parsed) ? $parsed : null;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded !== false ? $decoded : null;
    }
}
