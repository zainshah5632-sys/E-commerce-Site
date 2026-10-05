<?php
declare(strict_types=1);

namespace App\Supabase;

use App\Core\Env;
use App\Core\Logger;
use CurlHandle;

final class Result
{
    public function __construct(
        public readonly bool $ok,
        public readonly int $status,
        public readonly mixed $data = null,
        public readonly ?int $count = null,
        public readonly ?string $error = null
    ) {}
}

final class Client
{
    private static ?CurlHandle $curlHandle = null;
    private string $baseUrl;
    private string $secretKey;

    public function __construct(?string $baseUrl = null, ?string $secretKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? Env::require('SUPABASE_URL'), '/');
        $this->secretKey = $secretKey ?? Env::require('SUPABASE_SECRET_KEY');
    }

    private function getCurl(): CurlHandle
    {
        if (self::$curlHandle === null) {
            self::$curlHandle = curl_init();
        } else {
            curl_reset(self::$curlHandle);
        }
        return self::$curlHandle;
    }

    public function get(string $table, array $query = [], array $opts = []): Result
    {
        $queryString = http_build_query($query);
        $url = "{$this->baseUrl}/rest/v1/{$table}" . ($queryString !== '' ? "?{$queryString}" : '');

        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];

        // Compatibility rule: only send Authorization Bearer if legacy JWT (starts with eyJ)
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        if (!empty($opts['count'])) {
            $headers[] = 'Prefer: count=exact';
        }
        if (!empty($opts['single'])) {
            $headers[] = 'Accept: application/vnd.pgrst.object+json';
        }

        // Retry GET once on timeout / 5xx
        $res = $this->execute('GET', $url, $headers, null);
        if (!$res->ok && ($res->status >= 500 || $res->status === 0)) {
            $res = $this->execute('GET', $url, $headers, null);
        }

        return $res;
    }

    public function insert(string $table, array $row, bool $returning = true): Result
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
            'Prefer: return=' . ($returning ? 'representation' : 'minimal'),
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $body = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this->execute('POST', $url, $headers, $body !== false ? $body : '{}');
    }

    public function update(string $table, array $filters, array $patch): Result
    {
        $query = http_build_query($filters);
        $url = "{$this->baseUrl}/rest/v1/{$table}" . ($query !== '' ? "?{$query}" : '');

        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $body = json_encode($patch, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this->execute('PATCH', $url, $headers, $body !== false ? $body : '{}');
    }

    public function delete(string $table, array $filters): Result
    {
        $query = http_build_query($filters);
        $url = "{$this->baseUrl}/rest/v1/{$table}" . ($query !== '' ? "?{$query}" : '');

        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        return $this->execute('DELETE', $url, $headers, null);
    }

    public function rpc(string $fn, array $args = []): Result
    {
        $url = "{$this->baseUrl}/rest/v1/rpc/{$fn}";
        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: application/json',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $body = json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this->execute('POST', $url, $headers, $body !== false ? $body : '{}');
    }

    public function execute(string $method, string $url, array $headers, ?string $body): Result
    {
        $ch = $this->getCurl();

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => (Env::get('APP_ENV') !== 'local'),
            CURLOPT_SSL_VERIFYHOST => (Env::get('APP_ENV') !== 'local' ? 2 : 0),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $len = strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $rawOutput = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($rawOutput === false || $status === 0) {
            Logger::error("Supabase request failed: {$method} {$url}", ['error' => $curlError, 'status' => $status]);
            return new Result(false, $status, null, null, 'Unable to connect to database service.');
        }

        $parsedData = null;
        if (is_string($rawOutput) && $rawOutput !== '') {
            $parsedData = json_decode($rawOutput, true);
        }

        // Parse content-range header for exact count: e.g. "0-11/48"
        $count = null;
        if (isset($responseHeaders['content-range'])) {
            $cr = $responseHeaders['content-range'];
            if (preg_match('/\/(\d+)$/', $cr, $m)) {
                $count = (int)$m[1];
            }
        }

        $ok = ($status >= 200 && $status < 300);
        $error = null;
        if (!$ok) {
            $msg = is_array($parsedData) ? ($parsedData['message'] ?? $parsedData['error'] ?? 'API error') : 'API error';
            Logger::error("Supabase error response: {$method} {$url}", [
                'status' => $status,
                'error'  => $msg,
                'data'   => $parsedData
            ]);
            $error = (string)$msg;
        }

        return new Result($ok, $status, $parsedData, $count, $error);
    }
}
