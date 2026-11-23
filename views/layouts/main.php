<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <?php require dirname(__DIR__) . '/partials/head-meta.php'; ?>
</head>
<body>
  <!-- Accessibility Skip Link (Design.md §2) -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Global Header -->
  <?php require dirname(__DIR__) . '/partials/header.php'; ?>

  <!-- Flash Messages -->
  <?php require dirname(__DIR__) . '/partials/flash.php'; ?>

  <!-- Main Content Anchor -->
  <main id="main-content">
    <?= $content ?? '' ?>
  </main>

  <!-- Global Footer -->
  <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

  <!-- Cart Drawer Slide-out & Backdrop -->
  <div id="drawer-backdrop" class="drawer-backdrop" aria-hidden="true"></div>
  <aside id="cart-drawer" class="drawer" aria-label="Shopping Cart Drawer" role="dialog" aria-modal="true">
    <div class="drawer__header">
      <div style="display: flex; align-items: center; gap: var(--s-2); font-family: var(--font-display); font-weight: 700; font-size: 1.15rem;">
        <?= icon('cart') ?>
        <span>Your Shopping Cart</span>
      </div>
      <button type="button" class="btn btn--ghost btn--icon" data-action="close-cart" aria-label="Close Shopping Cart">
        <?= icon('close') ?>
      </button>
    </div>
    <div id="mini-cart-container" class="drawer__body">
      <?php require dirname(__DIR__) . '/partials/mini-cart.php'; ?>
    </div>
  </aside>

  <!-- Toast Container -->
  <div class="toast-container" aria-live="polite"></div>

  <!-- Cookie & Privacy Consent Component -->
  <?php require dirname(__DIR__) . '/partials/cookie-consent.php'; ?>

  <!-- Elfsight AI Chatbot | Untitled AI Chatbot -->
  <script src="https://elfsightcdn.com/platform.js" async></script>
  <div class="elfsight-app-b88fa5ae-6b55-4839-9f95-72bf026a2089" data-elfsight-app-lazy></div>

  <!-- Deferred Vanilla Scripts (CSP compliant, no inline scripts per D-012) -->
  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
  <script src="<?= asset('assets/js/cart.js') ?>" defer></script>
</body>
</html>
