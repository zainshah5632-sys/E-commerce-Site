<?php
declare(strict_types=1);

/**
 * Verification Test: Strict Separation of Admin Login and Customer Login Systems
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

echo "===============================================================\n";
echo "   GLASSCART AUTHENTICATION SEPARATION VERIFICATION SUITE      \n";
echo "===============================================================\n\n";

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

// -------------------------------------------------------------
// Test 1: Navigation & Customer Surface Isolation
// -------------------------------------------------------------
echo "--- 1. Testing Customer Surface Isolation ---\n";
$home = testGet("{$baseUrl}/");
assertCheck("Home navbar does not link to /admin", !str_contains($home['body'], 'href="/admin"') && !str_contains($home['body'], "href='/admin'"));
assertCheck("Home navbar does not link to /admin/login", !str_contains($home['body'], '/admin/login'));

$login = testGet("{$baseUrl}/login");
assertCheck("Customer login page does not link to /admin", !str_contains($login['body'], '/admin'));

$register = testGet("{$baseUrl}/register");
assertCheck("Customer registration page does not have role selection input", !str_contains($register['body'], 'name="role"'));

// -------------------------------------------------------------
// Test 2: Unauthenticated Direct Access to Admin Routes
// -------------------------------------------------------------
echo "\n--- 2. Testing Unauthenticated Access Redirects to /admin/login ---\n";
$unauthRoutes = [
    '/admin',
    '/admin/products',
    '/admin/categories',
    '/admin/orders',
    '/admin/users',
    '/admin/settings',
];

foreach ($unauthRoutes as $route) {
    $res = testGet("{$baseUrl}{$route}");
    $isRedirect = ($res['status'] === 302);
    $hasAdminLoginLocation = str_contains($res['headers'], 'Location: /admin/login');
    assertCheck("Unauthenticated access to {$route} redirects to /admin/login (HTTP 302)", $isRedirect && $hasAdminLoginLocation);
}

// -------------------------------------------------------------
// Test 3: Standalone Admin Login Page UI
// -------------------------------------------------------------
echo "\n--- 3. Testing Admin Login Page (/admin/login) ---\n";
$adminLoginPage = testGet("{$baseUrl}/admin/login");
assertCheck("Admin login returns HTTP 200", $adminLoginPage['status'] === 200);
assertCheck("Admin login displays GlassCart Control Center brand", str_contains($adminLoginPage['body'], 'Control Center'));
assertCheck("Admin login displays Restricted Gateway badge", str_contains($adminLoginPage['body'], 'Restricted Admin Gateway'));
assertCheck("Admin login has Show/Hide password toggle", str_contains($adminLoginPage['body'], 'data-action="toggle-password"'));
assertCheck("Admin login has CSRF token", str_contains($adminLoginPage['body'], 'name="_csrf"'));

// -------------------------------------------------------------
// Test 4: Authenticated Customer Access Denied (HTTP 403)
// -------------------------------------------------------------
echo "\n--- 4. Testing Authenticated Customer RBAC Restriction (HTTP 403) ---\n";
// Create a customer session cookie
require_once dirname(__DIR__) . '/src/bootstrap.php';
use App\Core\Session;

// Simulate customer session by encrypting session data
$reflector = new ReflectionClass(Session::class);
$session = Session::instance();

// Create customer session
$session->setAuth('test-cust-id-001', 'cust@example.com', 'Jane Customer', 'customer');
$customerCookieHeader = $session->writeCookieHeader();
preg_match('/(gc_s=[^;]+)/', $customerCookieHeader, $cookieMatch);
$customerCookie = $cookieMatch[1] ?? '';

// Try accessing /admin routes as customer
$custAdminRes = testGet("{$baseUrl}/admin", $customerCookie);
assertCheck("Customer accessing /admin receives HTTP 403 Forbidden", $custAdminRes['status'] === 403);
assertCheck("Customer accessing /admin sees 403 Access Denied view", str_contains($custAdminRes['body'], 'Administrative Clearance Required'));

$custProdRes = testGet("{$baseUrl}/admin/products", $customerCookie);
assertCheck("Customer accessing /admin/products receives HTTP 403 Forbidden", $custProdRes['status'] === 403);

$custSettingsRes = testGet("{$baseUrl}/admin/settings", $customerCookie);
assertCheck("Customer accessing /admin/settings receives HTTP 403 Forbidden", $custSettingsRes['status'] === 403);

// -------------------------------------------------------------
// Test 5: Customer Login Redirection Protection
// -------------------------------------------------------------
echo "\n--- 5. Testing Customer Login Redirection Sanitization ---\n";
// Test customer login sanitized redirect when someone passes ?next=/admin
$custLoginNext = testGet("{$baseUrl}/login?next=/admin");
// Check hidden input in customer login form
preg_match('/name="next" value="([^"]+)"/', $custLoginNext['body'], $nextMatch);
$nextVal = $nextMatch[1] ?? '';
assertCheck("Customer login form sanitizes next=/admin to /account", $nextVal === '/account');

// -------------------------------------------------------------
// Test 6: Authorized Admin Access
// -------------------------------------------------------------
echo "\n--- 6. Testing Authorized Admin Access ---\n";
// Create admin session
$session->setAuth('test-admin-id-001', 'admin@glasscart.local', 'Admin User', 'admin');
$adminCookieHeader = $session->writeCookieHeader();
preg_match('/(gc_s=[^;]+)/', $adminCookieHeader, $cookieMatch2);
$adminCookie = $cookieMatch2[1] ?? '';

$adminRes = testGet("{$baseUrl}/admin", $adminCookie);
assertCheck("Authorized Admin receives HTTP 200 on /admin", $adminRes['status'] === 200);

$adminProdRes = testGet("{$baseUrl}/admin/products", $adminCookie);
assertCheck("Authorized Admin receives HTTP 200 on /admin/products", $adminProdRes['status'] === 200);

$adminOrderRes = testGet("{$baseUrl}/admin/orders", $adminCookie);
assertCheck("Authorized Admin receives HTTP 200 on /admin/orders", $adminOrderRes['status'] === 200);

// Admin logout
preg_match('/name="_csrf" value="([^"]+)"/', $adminRes['body'], $adminCsrf);
$adminToken = $adminCsrf[1] ?? '';

$logoutRes = testPost("{$baseUrl}/admin/logout", ['_csrf' => $adminToken], $adminCookie);
assertCheck("Admin logout redirects to /admin/login (HTTP 302)", $logoutRes['status'] === 302 && str_contains($logoutRes['headers'], 'Location: /admin/login'));

echo "\n===============================================================\n";
echo "   VERIFICATION SUMMARY: {$passes} PASSED, {$fails} FAILED\n";
echo "===============================================================\n";

exit($fails === 0 ? 0 : 1);

