<?php
declare(strict_types=1);

use App\Core\Session;

$session = Session::instance();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Admin — GlassCart') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= ea(csrf_token()) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">

  <script src="<?= asset('assets/js/theme-init.js') ?>"></script>
</head>
<body>
  <div class="admin-shell">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-sidebar__header">
        <a href="<?= url('/admin') ?>" class="admin-sidebar__brand-link">
          <span class="admin-sidebar__logo-mark">G</span>
          <div class="admin-sidebar__logo-text">
            <span class="admin-sidebar__logo-title">GlassCart</span>
            <span class="admin-sidebar__logo-sub">Console v2.4</span>
          </div>
        </a>
        <div style="display: flex; align-items: center; gap: 6px;">
          <div class="a11y-font-scale" role="group" aria-label="Text size scaling controls">
            <button type="button" class="btn-a11y-scale" data-action="font-scale" data-scale="sm" title="Decrease font size" aria-label="Decrease font size">A-</button>
            <button type="button" class="btn-a11y-scale is-active" data-action="font-scale" data-scale="md" title="Default font size" aria-label="Default font size">A</button>
            <button type="button" class="btn-a11y-scale" data-action="font-scale" data-scale="lg" title="Increase font size" aria-label="Increase font size">A+</button>
          </div>
          <button type="button" class="admin-sidebar__theme-btn" data-action="toggle-theme" aria-label="Toggle Theme" title="Toggle Theme">
            <?= icon('sun', 16) ?>
          </button>
        </div>
      </div>

      <nav aria-label="Admin Navigation" class="admin-sidebar__nav-wrap">
        <div class="admin-nav__group-label">Platform Core</div>
        <ul class="admin-nav">
          <li>
            <a href="<?= url('/admin') ?>" class="admin-nav__link <?= is_active('/admin', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('layout-dashboard', 18) ?></span>
              <span class="admin-nav__label">Overview</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/products') ?>" class="admin-nav__link <?= is_active('/admin/products', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('package', 18) ?></span>
              <span class="admin-nav__label">Products</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/categories') ?>" class="admin-nav__link <?= is_active('/admin/categories', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('folder', 18) ?></span>
              <span class="admin-nav__label">Categories</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/orders') ?>" class="admin-nav__link <?= is_active('/admin/orders', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('cart', 18) ?></span>
              <span class="admin-nav__label">Orders</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/users') ?>" class="admin-nav__link <?= is_active('/admin/users', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('user', 18) ?></span>
              <span class="admin-nav__label">Customers &amp; Staff</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/settings') ?>" class="admin-nav__link <?= is_active('/admin/settings', 'admin-nav__link--active') ?>">
              <span class="admin-nav__icon"><?= icon('settings', 18) ?></span>
              <span class="admin-nav__label">Store Settings</span>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Sidebar User Section & Footer -->
      <div class="admin-sidebar__user-box">
        <div class="admin-sidebar__user-meta">
          <div class="admin-sidebar__user-avatar">
            <?= strtoupper(substr($session->userName() ?: 'A', 0, 1)) ?>
          </div>
          <div class="admin-sidebar__user-info">
            <span class="admin-sidebar__user-name"><?= e($session->userName() ?: 'Administrator') ?></span>
            <span class="admin-sidebar__user-role">Super Admin</span>
          </div>
        </div>
        <div class="admin-sidebar__user-actions">
          <a href="<?= url('/') ?>" target="_blank" class="admin-sidebar__btn-store">
            <?= icon('external-link', 13) ?>
            <span>Storefront</span>
          </a>
          <form action="<?= url('/admin/logout') ?>" method="POST" style="display: inline; margin: 0;">
            <?= csrf_field() ?>
            <button type="submit" class="admin-sidebar__btn-logout" title="Sign Out" aria-label="Sign Out">
              <?= icon('logout', 14) ?>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="admin-main">
      <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
      <?= $content ?? '' ?>
    </main>
  </div>

  <div class="toast-container" aria-live="polite"></div>

  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
  <script src="<?= asset('assets/js/admin.js') ?>" defer></script>
</body>
</html>
