<?php
declare(strict_types=1);

$next = $next ?? '/admin';
$errors = $errors ?? [];
$oldIdentifier = old('identifier', old('email', ''));
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Admin Portal | GlassCart Control Center') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= ea(csrf_token()) ?>">
  <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">

  <style>
    .admin-auth-shell {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 20%, rgba(99, 102, 241, 0.12), transparent 60%),
                  radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.08), transparent 50%),
                  #0c0f17;
      padding: var(--s-5);
      position: relative;
      overflow: hidden;
    }

    .admin-auth-card {
      width: 100%;
      max-width: 440px;
      background: rgba(18, 24, 38, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px rgba(99, 102, 241, 0.15);
      backdrop-filter: blur(24px);
      border-radius: var(--r-xl);
      padding: var(--s-7);
      position: relative;
      z-index: 2;
    }

    .admin-portal-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.72rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: var(--r-pill);
      background: rgba(99, 102, 241, 0.15);
      border: 1px solid rgba(99, 102, 241, 0.3);
      color: #a5b4fc;
      margin-bottom: var(--s-3);
    }

    .admin-status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #10b981;
      box-shadow: 0 0 8px #10b981;
      animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }

    .admin-auth-header {
      text-align: center;
      margin-bottom: var(--s-6);
    }

    .admin-auth-header h1 {
      font-family: var(--font-display);
      font-size: 1.5rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: var(--s-1);
      letter-spacing: -0.02em;
    }

    .admin-auth-header p {
      font-size: 0.85rem;
      color: #94a3b8;
    }

    .admin-icon-avatar {
      width: 52px;
      height: 52px;
      border-radius: var(--r-lg);
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(236, 72, 153, 0.25));
      border: 1px solid rgba(255, 255, 255, 0.15);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #818cf8;
      margin-bottom: var(--s-3);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
    }
  </style>
</head>
<body>
  <div class="admin-auth-shell">
    <div class="admin-auth-card">
      <div class="admin-auth-header">
        <div class="admin-icon-avatar">
          <?= icon('shield') ?>
        </div>
        <div>
          <span class="admin-portal-badge">
            <span class="admin-status-dot"></span>
            Restricted Control Center
          </span>
        </div>
        <h1>GlassCart Admin</h1>
        <p>Enterprise System Administration Portal</p>
      </div>

      <?php if (!empty($errors['login'])): ?>
        <div class="alert alert--error glass--strong" style="margin-bottom: var(--s-4); font-size: 0.85rem;">
          <span><?= e($errors['login']) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= url('/admin/login') ?>" method="POST" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= ea($next) ?>">

        <!-- Administrator Identity -->
        <div class="form-group">
          <label for="admin-identifier" class="form-label" style="color: #cbd5e1;">Admin Email or Username</label>
          <div class="input-with-icon-wrap">
            <span class="input-icon-left" style="color: #94a3b8;"><?= icon('at-sign') ?></span>
            <input
              type="text"
              id="admin-identifier"
              name="identifier"
              class="form-input form-input--with-icon"
              style="background: rgba(15, 23, 42, 0.6); border-color: rgba(255, 255, 255, 0.12); color: #f8fafc;"
              value="<?= ea($oldIdentifier) ?>"
              required
              autocomplete="username"
              placeholder="admin@glasscart.local"
              autofocus
            >
          </div>
          <?php if (!empty($errors['identifier'])): ?>
            <span class="form-error"><?= e($errors['identifier']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Admin Secret Key / Password with Toggle Button -->
        <div class="form-group">
          <label for="admin-password" class="form-label" style="color: #cbd5e1;">Admin Password</label>
          <div class="password-input-wrap">
            <input
              type="password"
              id="admin-password"
              name="password"
              class="form-input"
              style="background: rgba(15, 23, 42, 0.6); border-color: rgba(255, 255, 255, 0.12); color: #f8fafc;"
              required
              autocomplete="current-password"
              placeholder="••••••••••••"
            >
            <button
              type="button"
              class="btn-toggle-password"
              data-action="toggle-password"
              aria-label="Toggle password visibility"
              tabindex="-1"
              style="color: #94a3b8;"
            >
              <span class="icon-eye"><?= icon('eye') ?></span>
              <span class="icon-eye-off" style="display: none;"><?= icon('eye-off') ?></span>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
            <span class="form-error"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <div style="margin-top: var(--s-6);">
          <button type="submit" class="btn btn--primary btn--block btn--lg" style="box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
            <?= icon('key') ?>
            <span>Authenticate Admin Session</span>
          </button>
        </div>
      </form>

      <div style="margin-top: var(--s-6); padding-top: var(--s-4); border-top: 1px solid rgba(255, 255, 255, 0.08); text-align: center;">
        <a href="<?= url('/') ?>" style="color: #94a3b8; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
          <?= icon('arrow-left') ?>
          <span>Return to Storefront</span>
        </a>
      </div>
    </div>
  </div>

  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
</body>
</html>
