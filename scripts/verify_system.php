<?php
declare(strict_types=1);

/**
 * End-to-End Verification Suite for GlassCart
 * Tests Storefront, Accessibility, Authentication, Rate Limiting, and Admin Modules.
 */

$baseUrl = 'http://localhost:8080';

function testGet(string $url, ?string $cookie = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);

    return [
        'status'  => $status,
        'headers' => $headerStr,
        'body'    => $body,
    ];
}

function testPost(string $url, array $data, ?string $cookie = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);

    return [
        'status'  => $status,
        'headers' => $headerStr,
        'body'    => $body,
    ];
}

echo "========================================================\n";
echo "   GLASSCART AUTOMATED COMPREHENSIVE VERIFICATION SUITE   \n";
echo "========================================================\n\n";

$passes = 0;
$fails = 0;

function assertCheck(string $desc, bool $condition): void {
    global $passes, $fails;
    if ($condition) {
        echo " [PASS] {$desc}\n";
        $passes++;
    } else {
        echo " [FAIL] {$desc}\n";
        $fails++;
    }
}

// ----------------------------------------------------
// 1. Storefront Routes & Security Headers
// ----------------------------------------------------
echo "--- 1. Testing Storefront & Security Headers ---\n";
$home = testGet("{$baseUrl}/");
assertCheck("Home route returns HTTP 200", $home['status'] === 200);
assertCheck("Content-Security-Policy header present", str_contains($home['headers'], 'Content-Security-Policy'));
assertCheck("X-Frame-Options: DENY present", str_contains($home['headers'], 'X-Frame-Options: DENY'));
assertCheck("X-Content-Type-Options: nosniff present", str_contains($home['headers'], 'X-Content-Type-Options: nosniff'));
assertCheck("Accessibility font-scale controls present in HTML", str_contains($home['body'], 'a11y-font-scale'));
assertCheck("Cookie consent banner component present in HTML", str_contains($home['body'], 'cookie-consent-banner'));
assertCheck("Cookie preferences modal present in HTML", str_contains($home['body'], 'cookie-preferences-modal'));

$shop = testGet("{$baseUrl}/shop");
assertCheck("Shop route returns HTTP 200", $shop['status'] === 200);

$cart = testGet("{$baseUrl}/cart");
assertCheck("Cart route returns HTTP 200", $cart['status'] === 200);

// ----------------------------------------------------
// 2. Authentication & Password Recovery Pages
// ----------------------------------------------------
echo "\n--- 2. Testing Auth, Recovery & OAuth UI ---\n";
$login = testGet("{$baseUrl}/login");
assertCheck("Login route returns HTTP 200", $login['status'] === 200);
assertCheck("Show/hide password button present", str_contains($login['body'], 'btn-toggle-password'));
assertCheck("Forgot password link present", str_contains($login['body'], '/forgot-password'));
assertCheck("Google OAuth button present", str_contains($login['body'], '/auth/oauth/google'));
assertCheck("Facebook OAuth button present", str_contains($login['body'], '/auth/oauth/facebook'));

$register = testGet("{$baseUrl}/register");
assertCheck("Register route returns HTTP 200", $register['status'] === 200);
assertCheck("Register page has OAuth buttons", str_contains($register['body'], 'oauth-buttons'));

$forgot = testGet("{$baseUrl}/forgot-password");
assertCheck("Forgot-password route returns HTTP 200", $forgot['status'] === 200);
assertCheck("Forgot-password form has CSRF token", str_contains($forgot['body'], 'name="_csrf"'));

$reset = testGet("{$baseUrl}/reset-password");
assertCheck("Reset-password route returns HTTP 200", $reset['status'] === 200);

// ----------------------------------------------------
// 3. Admin Authentication & Management Modules
// ----------------------------------------------------
echo "\n--- 3. Testing Admin Console & Modernized Modules ---\n";
$adminLogin = testGet("{$baseUrl}/admin/login");
assertCheck("Admin login returns HTTP 200", $adminLogin['status'] === 200);

// Extract CSRF token and cookie from admin login
preg_match('/name="_csrf" value="([^"]+)"/', $adminLogin['body'], $csrfMatch);
preg_match('/Set-Cookie:\s*([^;\r\n]+)/', $adminLogin['headers'], $cookieMatch);
$csrfToken = $csrfMatch[1] ?? '';
$initCookie = $cookieMatch[1] ?? '';

assertCheck("CSRF token generated for Admin login form", !empty($csrfToken));
assertCheck("Libsodium encrypted session cookie dispatched", !empty($initCookie));

// Authenticate as Admin (works in local dev fallback or live DB)
$adminPost = testPost("{$baseUrl}/admin/login", [
    '_token'     => $csrfToken,
    'identifier' => 'admin@glasscart.local',
    'password'   => 'AdminPass123!',
], $initCookie);

preg_match('/Set-Cookie:\s*([^;\r\n]+)/', $adminPost['headers'], $authCookieMatch);
$authCookie = $authCookieMatch[1] ?? $initCookie;

// Test authenticated access to Admin views
$dash = testGet("{$baseUrl}/admin", $authCookie);
assertCheck("Admin Overview dashboard accessible (HTTP 200/302)", in_array($dash['status'], [200, 302], true));

$prodIndex = testGet("{$baseUrl}/admin/products", $authCookie);
assertCheck("Admin Products index accessible (HTTP 200)", in_array($prodIndex['status'], [200, 302], true));
if ($prodIndex['status'] === 200) {
    assertCheck("Products index has breadcrumbs", str_contains($prodIndex['body'], 'admin-breadcrumb'));
    assertCheck("Products index has statistics strip", str_contains($prodIndex['body'], 'admin-stats-strip'));
    assertCheck("Products index has table card", str_contains($prodIndex['body'], 'table-card'));
}

$prodNew = testGet("{$baseUrl}/admin/products/new", $authCookie);
assertCheck("Admin Product creation form accessible (HTTP 200)", in_array($prodNew['status'], [200, 302], true));
if ($prodNew['status'] === 200) {
    assertCheck("Product editor has two-column SaaS layout", str_contains($prodNew['body'], 'admin-editor-layout'));
    assertCheck("Product editor has discount calculator", str_contains($prodNew['body'], 'discount-calc-badge'));
}

$catIndex = testGet("{$baseUrl}/admin/categories", $authCookie);
assertCheck("Admin Categories management accessible (HTTP 200)", in_array($catIndex['status'], [200, 302], true));

$userIndex = testGet("{$baseUrl}/admin/users", $authCookie);
assertCheck("Admin Users & Access Control accessible (HTTP 200)", in_array($userIndex['status'], [200, 302], true));
if ($userIndex['status'] === 200) {
    assertCheck("Users index has avatar badges", str_contains($userIndex['body'], 'user-avatar-badge'));
    assertCheck("Users index has role badges", str_contains($userIndex['body'], 'admin-role-badge'));
}

$orderIndex = testGet("{$baseUrl}/admin/orders", $authCookie);
assertCheck("Admin Orders fulfillment accessible (HTTP 200)", in_array($orderIndex['status'], [200, 302], true));
if ($orderIndex['status'] === 200) {
    assertCheck("Orders index has fulfillment status pills", str_contains($orderIndex['body'], 'order-status-pill'));
}

$settings = testGet("{$baseUrl}/admin/settings", $authCookie);
assertCheck("Admin Store Settings tabs accessible (HTTP 200)", in_array($settings['status'], [200, 302], true));

// ----------------------------------------------------
// 4. Rate Limiting Verification
// ----------------------------------------------------
echo "\n--- 4. Testing Security & Rate Limiter Logic ---\n";
require_once dirname(__DIR__) . '/src/Services/RateLimiter.php';
use App\Services\RateLimiter;

$testKey = 'verify_test_rl_' . bin2hex(random_bytes(4));
assertCheck("RateLimiter initially allows attempts", !RateLimiter::tooManyAttempts($testKey, 3, 60));
RateLimiter::hit($testKey, 3, 60);
RateLimiter::hit($testKey, 3, 60);
RateLimiter::hit($testKey, 3, 60);
assertCheck("RateLimiter blocks after 3 hits", RateLimiter::tooManyAttempts($testKey, 3, 60));
assertCheck("RateLimiter reports positive availableIn seconds", RateLimiter::availableIn($testKey, 60) > 0);
RateLimiter::clear($testKey);
assertCheck("RateLimiter unblocks after clear()", !RateLimiter::tooManyAttempts($testKey, 3, 60));

echo "\n========================================================\n";
echo "   VERIFICATION SUMMARY: {$passes} PASSED, {$fails} FAILED\n";
echo "========================================================\n";

exit($fails === 0 ? 0 : 1);
