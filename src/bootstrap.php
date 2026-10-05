<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Logger;
use App\Core\Response;
use App\Core\View;

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/Support/helpers.php';

// Load environment variables
Env::load(dirname(__DIR__) . '/.env');

// Error handling configuration
$isLocal = (Env::get('APP_ENV', 'production') === 'local');
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? '1' : '0');

set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    Logger::error("PHP Notice/Warning: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
        'code' => $errno,
    ]);
    return true;
});

set_exception_handler(static function (Throwable $e) use ($isLocal): void {
    Logger::error("Uncaught exception: " . $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $isLocal ? $e->getTraceAsString() : 'redacted',
    ]);

    if (!headers_sent()) {
        http_response_code(500);
    }

    try {
        $html = View::render('pages/500', [
            'title'   => 'Server Error',
            'message' => $isLocal ? $e->getMessage() : 'An unexpected error occurred. Please try again.',
            'trace'   => $isLocal ? $e->getTraceAsString() : null,
        ]);
        echo $html;
    } catch (Throwable) {
        echo '<h1>500 Server Error</h1><p>An unexpected error occurred.</p>';
    }
    exit;
});

// Send security headers (Architecture.md §10)
if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.supabase.co; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("X-Frame-Options: DENY");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

    $appUrl = Env::get('APP_URL', '');
    if (str_starts_with($appUrl, 'https://')) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
}
