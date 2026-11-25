<?php
declare(strict_types=1);

namespace App\Supabase;

use App\Core\Env;
use App\Core\Logger;

final class Storage
{
    private string $baseUrl;
    private string $secretKey;
    private string $bucket;
    private Client $client;

    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const ALLOWED_RECEIPT_MIMES = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(Env::require('SUPABASE_URL'), '/');
        $this->secretKey = Env::require('SUPABASE_SECRET_KEY');
        $this->bucket = Env::get('SUPABASE_STORAGE_BUCKET', 'product-images');
        $this->client = new Client($this->baseUrl, $this->secretKey);
    }

    public function uploadProductImage(string $productId, array $file): ?string
    {
        if (empty($file['tmp_name']) || !is_file($file['tmp_name'])) {
            return null;
        }

        // File size cap: 2 MB (2 * 1024 * 1024 bytes)
        if (($file['size'] ?? 0) > 2097152) {
            Logger::warning('Upload rejected: file size exceeds 2 MB limit', ['size' => $file['size']]);
            return null;
        }

        // Content-based MIME inspection
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_MIMES[$mime])) {
            Logger::warning('Upload rejected: unsupported MIME type', ['mime' => $mime]);
            return null;
        }

        $ext = self::ALLOWED_MIMES[$mime];
        $filename = bin2hex(random_bytes(10)) . '.' . $ext;
        $path = "products/{$productId}/{$filename}";

        $url = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";
        $rawBytes = file_get_contents($file['tmp_name']);
        if ($rawBytes === false) {
            return null;
        }

        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: ' . $mime,
            'x-upsert: false',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $res = $this->client->execute('POST', $url, $headers, $rawBytes);
        if ($res->ok) {
            return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$path}";
        }

        Logger::error('Failed to upload image to Supabase storage', ['status' => $res->status, 'error' => $res->error]);
        return null;
    }

    public function deleteImage(string $publicUrl): bool
    {
        $prefix = "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/";
        if (!str_starts_with($publicUrl, $prefix)) {
            return false;
        }

        $path = substr($publicUrl, strlen($prefix));
        $url = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";

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

    public function uploadPaymentReceipt(string $orderNumber, array $file): ?string
    {
        if (empty($file['tmp_name']) || !is_file($file['tmp_name'])) {
            return null;
        }

        // File size cap: 5 MB (5 * 1024 * 1024 bytes)
        if (($file['size'] ?? 0) > 5242880) {
            Logger::warning('Receipt upload rejected: file size exceeds 5 MB limit', ['size' => $file['size']]);
            return null;
        }

        // Content-based MIME inspection
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_RECEIPT_MIMES[$mime])) {
            Logger::warning('Receipt upload rejected: unsupported MIME type', ['mime' => $mime]);
            return null;
        }

        $ext = self::ALLOWED_RECEIPT_MIMES[$mime];
        $filename = 'receipt_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $path = "receipts/{$orderNumber}/{$filename}";

        $url = "{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}";
        $rawBytes = file_get_contents($file['tmp_name']);
        if ($rawBytes === false) {
            return null;
        }

        $headers = [
            'apikey: ' . $this->secretKey,
            'Content-Type: ' . $mime,
            'x-upsert: true',
        ];
        if (str_starts_with($this->secretKey, 'eyJ')) {
            $headers[] = 'Authorization: Bearer ' . $this->secretKey;
        }

        $res = $this->client->execute('POST', $url, $headers, $rawBytes);
        if ($res->ok) {
            return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$path}";
        }

        Logger::error('Failed to upload receipt to Supabase storage', ['status' => $res->status, 'error' => $res->error]);
        return null;
    }
}
