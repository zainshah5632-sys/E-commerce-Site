<?php
declare(strict_types=1);

// Built-in server static file guard (Architecture.md §4)
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($p !== '/' && is_file(__DIR__ . $p)) {
        return false;
    }
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AccountController;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\CategoryAdminController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderAdminController;
use App\Controllers\Admin\ProductAdminController;
use App\Controllers\Admin\SettingsAdminController;
use App\Controllers\Admin\UserAdminController;


use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ProductController;
use App\Controllers\SeoController;
use App\Controllers\ShopController;
use App\Controllers\WebhookController;
use App\Core\Request;
use App\Core\Router;

$router = new Router();

// Storefront routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/shop', [ShopController::class, 'index']);
$router->get('/category/{slug}', [ShopController::class, 'category']);
$router->get('/product/{slug}', [ProductController::class, 'show']);

// Cart routes
$router->get('/cart', [CartController::class, 'index']);
$router->get('/cart/mini', [CartController::class, 'mini']);
$router->post('/cart/add', [CartController::class, 'add'], ['csrf']);
$router->post('/cart/update', [CartController::class, 'update'], ['csrf']);
$router->post('/cart/remove', [CartController::class, 'remove'], ['csrf']);

// Checkout routes
$router->get('/checkout', [CheckoutController::class, 'show']);
$router->post('/checkout', [CheckoutController::class, 'place'], ['csrf']);
$router->get('/order/success/{number}', [CheckoutController::class, 'success']);
$router->post('/webhook/stripe', [WebhookController::class, 'stripe']);

// Auth routes
$router->get('/login', [AuthController::class, 'loginForm'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
$router->get('/register', [AuthController::class, 'registerForm'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest', 'csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['csrf']);

// Password Recovery routes
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'sendPasswordReset'], ['guest', 'csrf']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordForm'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'updatePassword'], ['guest', 'csrf']);

// Social OAuth routes
$router->get('/auth/oauth/callback', [AuthController::class, 'oauthCallback']);
$router->post('/auth/oauth/session', [AuthController::class, 'oauthSession']);
$router->get('/auth/oauth/{provider}', [AuthController::class, 'oauth']);

// Customer account routes
$router->get('/account', [AccountController::class, 'index'], ['auth']);
$router->post('/account', [AccountController::class, 'update'], ['auth', 'csrf']);
$router->get('/account/orders', [AccountController::class, 'orders'], ['auth']);
$router->get('/account/orders/{number}', [AccountController::class, 'order'], ['auth']);

// Static & SEO routes
$router->get('/page/{slug}', [PageController::class, 'show']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/health', [HealthController::class, 'index']);

// Admin routes
$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login'], ['csrf']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout'], ['csrf']);

$router->get('/admin', [DashboardController::class, 'index'], ['admin']);


$router->get('/admin/products', [ProductAdminController::class, 'index'], ['admin']);
$router->get('/admin/products/new', [ProductAdminController::class, 'create'], ['admin']);
$router->post('/admin/products', [ProductAdminController::class, 'store'], ['admin', 'csrf']);
$router->get('/admin/products/{id}/edit', [ProductAdminController::class, 'edit'], ['admin']);
$router->post('/admin/products/{id}', [ProductAdminController::class, 'update'], ['admin', 'csrf']);
$router->post('/admin/products/{id}/delete', [ProductAdminController::class, 'destroy'], ['admin', 'csrf']);
$router->post('/admin/products/{id}/toggle', [ProductAdminController::class, 'toggle'], ['admin', 'csrf']);

$router->get('/admin/categories', [CategoryAdminController::class, 'index'], ['admin']);
$router->post('/admin/categories', [CategoryAdminController::class, 'store'], ['admin', 'csrf']);
$router->post('/admin/categories/{id}', [CategoryAdminController::class, 'update'], ['admin', 'csrf']);
$router->post('/admin/categories/{id}/delete', [CategoryAdminController::class, 'destroy'], ['admin', 'csrf']);

$router->get('/admin/orders', [OrderAdminController::class, 'index'], ['admin']);
$router->get('/admin/orders/{number}', [OrderAdminController::class, 'show'], ['admin']);
$router->post('/admin/orders/{number}/status', [OrderAdminController::class, 'status'], ['admin', 'csrf']);
$router->post('/admin/orders/{number}/payment', [OrderAdminController::class, 'payment'], ['admin', 'csrf']);
$router->post('/admin/orders/{number}/cancel', [OrderAdminController::class, 'cancel'], ['admin', 'csrf']);

$router->get('/admin/users', [UserAdminController::class, 'index'], ['admin']);
$router->get('/admin/users/new', [UserAdminController::class, 'create'], ['admin']);
$router->post('/admin/users', [UserAdminController::class, 'store'], ['admin', 'csrf']);
$router->get('/admin/users/{id}/edit', [UserAdminController::class, 'edit'], ['admin']);
$router->post('/admin/users/{id}', [UserAdminController::class, 'update'], ['admin', 'csrf']);
$router->post('/admin/users/{id}/delete', [UserAdminController::class, 'destroy'], ['admin', 'csrf']);
$router->post('/admin/users/{id}/role', [UserAdminController::class, 'toggleRole'], ['admin', 'csrf']);

$router->get('/admin/settings', [SettingsAdminController::class, 'index'], ['admin']);
$router->post('/admin/settings', [SettingsAdminController::class, 'update'], ['admin', 'csrf']);


// Dispatch request
$request = Request::capture();
$response = $router->dispatch($request);
$response->send();
