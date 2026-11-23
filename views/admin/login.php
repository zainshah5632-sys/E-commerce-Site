<?php
declare(strict_types=1);

$next = $next ?? '/admin';
$errors = $errors ?? [];
$oldIdentifier = old('identifier', old('email', ''));
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Admin Console Access | GlassCart') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= ea(csrf_token()) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">

  <script src="<?= asset('assets/js/theme-init.js') ?>"></script>
</head>
<body class="admin-login-body">
  <div class="admin-login-backdrop">
    <div class="admin-login-container">
      
      <!-- Brand & Security Header -->
      <div class="admin-login-brand">
        <div class="admin-login-logo">
          <span>G</span>
        </div>
        <div class="admin-login-brand-text">
          <span class="admin-brand-title">GlassCart</span>
          <span class="admin-brand-edition">Control Center &bull; Enterprise</span>
        </div>
      </div>

      <!-- Main Login Card -->
      <div class="admin-login-card">
        <div class="admin-login-header">
          <div class="admin-gateway-badge">
            <?= icon('shield') ?>
            <span>Restricted Admin Gateway</span>
          </div>
          <h1 class="admin-login-title">Administrator Sign In</h1>
          <p class="admin-login-subtitle">
            Authorized personnel only. Enter your administrative credentials to manage store operations.
          </p>
        </div>

        <?php if (!empty($errors['login'])): ?>
          <div class="admin-login-alert" role="alert">
            <span class="alert-icon"><?= icon('alert-triangle') ?></span>
            <span class="alert-text"><?= e($errors['login']) ?></span>
          </div>
        <?php endif; ?>

        <form action="<?= url('/admin/login') ?>" method="POST" class="admin-login-form" novalidate id="admin-login-form">
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="<?= ea($next) ?>">

          <!-- Admin Identifier (Email or Username) -->
          <div class="form-group">
            <label for="admin-identifier" class="admin-label">
              <span>Admin Email or Handle</span>
              <span class="required">*</span>
            </label>
            <div class="admin-input-icon-wrap">
              <span class="icon-left"><?= icon('user') ?></span>
              <input
                type="text"
                id="admin-identifier"
                name="identifier"
                class="admin-input admin-input--iconed"
                value="<?= ea($oldIdentifier) ?>"
                required
                autocomplete="username"
                placeholder="admin@glasscart.local"
                autofocus
              >
            </div>
            <?php if (!empty($errors['identifier'])): ?>
              <span class="admin-field-error"><?= e($errors['identifier']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Password with Toggle -->
          <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
              <label for="admin-password" class="admin-label" style="margin-bottom: 0;">
                <span>Security Password</span>
                <span class="required">*</span>
              </label>
            </div>
            <div class="password-input-wrap admin-input-icon-wrap">
              <span class="icon-left"><?= icon('lock') ?></span>
              <input
                type="password"
                id="admin-password"
                name="password"
                class="admin-input admin-input--iconed"
                required
                autocomplete="current-password"
                placeholder="••••••••••••"
                style="padding-right: 42px;"
              >
              <button
                type="button"
                class="btn-toggle-password"
                data-action="toggle-password"
                aria-label="Toggle password visibility"
                tabindex="-1"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center;"
              >
                <span class="icon-eye"><?= icon('eye', 18) ?></span>
                <span class="icon-eye-off" style="display: none;"><?= icon('eye-off', 18) ?></span>
              </button>
            </div>
            <?php if (!empty($errors['password'])): ?>
              <span class="admin-field-error"><?= e($errors['password']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Security Notice -->
          <div class="admin-security-note">
            <?= icon('shield') ?>
            <span>Protected by Libsodium AEAD encryption and anti-brute-force rate limiting.</span>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-admin-primary btn-block admin-submit-btn" id="btn-admin-submit">
            <?= icon('log-in') ?>
            <span class="btn-text">Authenticate &amp; Enter Console</span>
          </button>
        </form>

        <!-- Footer Return Link -->
        <div class="admin-login-footer">
          <a href="<?= url('/') ?>" class="admin-return-link">
            <?= icon('arrow-left', 14) ?>
            <span>Return to Customer Storefront</span>
          </a>
        </div>
      </div>

      <div class="admin-copyright-notice">
        &copy; <?= date('Y') ?> GlassCart Systems &bull; Role-Based Access Control Active
      </div>

    </div>
  </div>

  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('admin-login-form');
      var submitBtn = document.getElementById('btn-admin-submit');
      if (form && submitBtn) {
        form.addEventListener('submit', function () {
          submitBtn.disabled = true;
          submitBtn.classList.add('is-loading');
          var textSpan = submitBtn.querySelector('.btn-text');
          if (textSpan) textSpan.textContent = 'Verifying Clearance...';
        });
      }
    });
  </script>
</body>
</html>
