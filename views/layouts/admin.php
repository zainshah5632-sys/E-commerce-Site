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
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <a href="<?= url('/admin') ?>" class="admin-sidebar__logo">
          GlassCart Admin
        </a>
        <button type="button" class="btn btn--ghost btn--icon" data-action="toggle-theme" aria-label="Toggle Theme">
          <?= icon('sun') ?>
        </button>
      </div>

      <nav aria-label="Admin Navigation" style="flex-grow: 1;">
        <ul class="admin-nav">
          <li>
            <a href="<?= url('/admin') ?>" class="admin-nav__link <?= is_active('/admin', 'admin-nav__link--active') ?>">
              <?= icon('truck') ?>
              <span>Dashboard</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/products') ?>" class="admin-nav__link <?= is_active('/admin/products', 'admin-nav__link--active') ?>">
              <?= icon('package') ?>
              <span>Products</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/categories') ?>" class="admin-nav__link <?= is_active('/admin/categories', 'admin-nav__link--active') ?>">
              <?= icon('menu') ?>
              <span>Categories</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/orders') ?>" class="admin-nav__link <?= is_active('/admin/orders', 'admin-nav__link--active') ?>">
              <?= icon('cart') ?>
              <span>Orders</span>
            </a>
          </li>
          <li>
            <a href="<?= url('/admin/settings') ?>" class="admin-nav__link <?= is_active('/admin/settings', 'admin-nav__link--active') ?>">
              <?= icon('settings') ?>
              <span>Settings</span>
            </a>
          </li>
        </ul>
      </nav>

      <!-- Sidebar Footer -->
      <div style="border-top: 1px solid var(--line); padding-top: var(--s-4); display: flex; flex-direction: column; gap: var(--s-2);">
        <div style="font-size: 0.85rem; color: var(--text-3); font-weight: 500;">
          Signed in as <strong><?= e($session->userName() ?: 'Admin') ?></strong>
        </div>
        <div style="display: flex; gap: var(--s-2);">
          <a href="<?= url('/') ?>" class="btn btn--ghost btn--sm" style="flex-grow: 1;">View Store</a>
          <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost btn--icon" title="Sign Out">
              <?= icon('logout') ?>
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
