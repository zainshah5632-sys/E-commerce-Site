<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private ?array $json;
    private array $files;
    private array $server;
    private array $routeParams = [];

    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $post = [],
        array $files = [],
        array $server = [],
        ?string $rawBody = null
    ) {
        $this->method = strtoupper($method);
        $this->path = $path === '' ? '/' : $path;
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->server = $server;

        if ($rawBody !== null && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            $this->json = is_array($decoded) ? $decoded : null;
        } else {
            $this->json = null;
        }
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $rawBody = null;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $rawBody = (string)file_get_contents('php://input');
        }

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $rawBody
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($this->json !== null) {
            if ($key === null) {
                return $this->json;
            }
            return $this->json[$key] ?? $default;
        }

        if ($key === null) {
            return array_merge($this->query, $this->post);
        }

        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $val = $this->input($key, $default);
        if (!is_scalar($val)) {
            return $default;
        }
        return trim((string)$val);
    }

    public function int(string $key, int $default = 0): int
    {
        $val = $this->input($key, $default);
        return is_numeric($val) ? (int)$val : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->input($key, null);
        if ($val === null) {
            return $default;
        }
        return in_array($val, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$normalized])) {
            return (string)$this->server[$normalized];
        }
        if ($name === 'Content-Type' && isset($this->server['CONTENT_TYPE'])) {
            return (string)$this->server['CONTENT_TYPE'];
        }
        return $default;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json');
    }

    public function ip(): string
    {
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return (string)($this->server['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->routeParams;
    }
}
