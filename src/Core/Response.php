<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    private int $status;
    private array $headers;
    private string $body;

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new self($html, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new self($json !== false ? $json : '{}', $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        // Prevent open redirect: must start with single slash, not //
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/';
        }

        $headers['Location'] = $url;
        return new self('', $status, $headers);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            // Write session cookie if needed
            $cookie = Session::instance()->writeCookieHeader();
            if ($cookie !== null) {
                header('Set-Cookie: ' . $cookie, false);
            }

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}", false);
            }
        }

        echo $this->body;
        exit;
    }
}
