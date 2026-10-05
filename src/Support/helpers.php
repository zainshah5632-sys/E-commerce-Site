<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Session;
use App\Repositories\SettingsRepository;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('ea')) {
    function ea(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/', array $query = []): string
    {
        $path = '/' . ltrim($path, '/');
        if (!empty($query)) {
            $queryString = http_build_query($query);
            return $path . (str_contains($path, '?') ? '&' : '?') . $queryString;
        }
        return $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $trimmed = ltrim($path, '/');
        $filePath = dirname(__DIR__, 2) . '/public/' . $trimmed;
        $v = is_file($filePath) ? (string)filemtime($filePath) : '1';
        return '/' . $trimmed . '?v=' . $v;
    }
}

if (!function_exists('money')) {
    function money(int $cents): string
    {
        $symbol = settings('currency_symbol', '$');
        $formatted = number_format($cents / 100, 2);
        return $symbol . $formatted;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $oldInput = Session::instance()->get('_old_input', []);
        return $oldInput[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::instance()->csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = e(csrf_token());
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        Session::instance()->flash($type, $message);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? '';
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text) ?? '';
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text) ?? '';
        return strtolower($text);
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path, string $activeClass = 'nav__link--active'): string
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($path === '/' && $currentPath === '/') {
            return $activeClass;
        }
        if ($path !== '/' && str_starts_with($currentPath, $path)) {
            return $activeClass;
        }
        return '';
    }
}

if (!function_exists('settings')) {
    function settings(string $key, mixed $default = null): mixed
    {
        return SettingsRepository::get($key, $default);
    }
}

if (!function_exists('icon')) {
    function icon(string $name, string|int $classOrSize = ''): string
    {
        $iconsPath = dirname(__DIR__, 2) . '/views/partials/icons.php';
        if (!is_file($iconsPath)) {
            return '';
        }
        static $icons = null;
        if ($icons === null) {
            $icons = require $iconsPath;
        }
        $svg = $icons[$name] ?? '';
        if ($svg === '') {
            return '';
        }
        if (is_int($classOrSize)) {
            $size = (string)$classOrSize;
            $svg = preg_replace('/(?<!-)width="[0-9]+"/i', 'width="' . $size . '"', $svg);
            $svg = preg_replace('/(?<!-)height="[0-9]+"/i', 'height="' . $size . '"', $svg);
        } elseif ($classOrSize !== '') {
            $svg = str_replace('<svg ', '<svg class="' . e((string)$classOrSize) . '" ', $svg);
        }
        return $svg;
    }
}
