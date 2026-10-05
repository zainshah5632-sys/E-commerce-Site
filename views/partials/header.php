<?php
declare(strict_types=1);

use App\Core\Session;
use App\Services\CartService;

$session = Session::instance();
$cartCount = CartService::count();
$announcement = settings('announcement', 'Free shipping on orders over $75');
?>
<?php if ($announcement): ?>
  <div class="announcement-bar">
    <?= e($announcement) ?>
  </div>
<?php endif; ?>

<header class="site-header">
  <div class="container header__inner">
    <!-- Brand Logo -->
    <a href="<?= url('/') ?>" class="header__logo" aria-label="GlassCart Home">
      <img src="<?= asset('assets/img/logo.svg') ?>" alt="" width="28" height="28">
      <span>GlassCart</span>
    </a>

    <!-- Navigation -->
    <nav class="header__nav" aria-label="Main Navigation">
      <ul class="nav__list">
        <li><a href="<?= url('/') ?>" class="nav__link <?= is_active('/') ?>">Home</a></li>
        <li><a href="<?= url('/shop') ?>" class="nav__link <?= is_active('/shop') ?>">Shop</a></li>
        <li><a href="<?= url('/category/audio') ?>" class="nav__link <?= is_active('/category/audio') ?>">Audio</a></li>
        <li><a href="<?= url('/category/wearables') ?>" class="nav__link <?= is_active('/category/wearables') ?>">Wearables</a></li>
        <li><a href="<?= url('/category/desk-home') ?>" class="nav__link <?= is_active('/category/desk-home') ?>">Desk &amp; Home</a></li>
      </ul>
    </nav>

    <!-- Header Actions -->
    <div class="header__actions">
      <!-- Theme Switcher -->
      <button type="button" class="btn btn--ghost btn--icon" data-action="toggle-theme" aria-label="Toggle Light/Dark Theme">
        <span class="theme-icon-light"><?= icon('sun') ?></span>
      </button>

      <!-- User Auth State -->
      <?php if ($session->isAuthenticated()): ?>
        <a href="<?= url('/account') ?>" class="btn btn--ghost btn--sm" aria-label="My Account">
          <?= icon('user') ?>
          <span><?= e($session->userName() ?: 'Account') ?></span>
        </a>

        <?php if ($session->isAdmin()): ?>
          <a href="<?= url('/admin') ?>" class="btn btn--ghost btn--sm badge--brand">Admin</a>
        <?php endif; ?>

        <!-- Sign Out Form (POST per AR §5 & T5.1) -->
        <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--ghost btn--icon" title="Sign Out" aria-label="Sign Out">
            <?= icon('logout') ?>
          </button>
        </form>
      <?php else: ?>
        <a href="<?= url('/login') ?>" class="btn btn--ghost btn--sm">Sign In</a>
        <a href="<?= url('/register') ?>" class="btn btn--primary btn--sm">Register</a>
      <?php endif; ?>

      <!-- Shopping Cart Trigger -->
      <button type="button" class="btn btn--ghost btn--icon cart-btn" data-action="open-cart" aria-label="Open Shopping Cart">
        <?= icon('cart') ?>
        <span class="cart-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>">
          <?= $cartCount ?>
        </span>
      </button>
    </div>
  </div>
</header>
