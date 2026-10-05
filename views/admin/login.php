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
  <title><?= e($title ?? 'Admin Sign In — GlassCart Control Center') ?></title>
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
</head>
<body class="admin-login-body">
  <div class="admin-login-backdrop min-h-screen flex items-center justify-center p-4 bg-slate-50/60">
    <div class="admin-login-card w-full max-w-md bg-white/90 backdrop-blur-md rounded-2xl border border-slate-200/80 p-8 shadow-xl shadow-slate-200/50">
      
      <!-- Header Hierarchy -->
      <div class="text-center">
        <span class="admin-badge-pill text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full inline-block mb-3">Admin Portal</span>
        <h1 class="admin-login-title text-2xl font-bold tracking-tight text-slate-900 mb-2">Admin Sign In</h1>
        <p class="admin-login-subtitle text-sm text-slate-500 leading-normal mb-6">
          Enter your administrative credentials to access the GlassCart control center.
        </p>
      </div>

      <?php if (!empty($errors['login'])): ?>
        <div class="admin-login-alert mb-4">
          <span><?= e($errors['login']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Form Fields & Separation -->
      <form action="<?= url('/admin/login') ?>" method="POST" class="admin-login-form space-y-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= ea($next) ?>">

        <!-- Admin Email or Username Field -->
        <div class="admin-form-group">
          <label for="admin-identifier" class="admin-form-label text-xs font-bold uppercase tracking-wider text-slate-700 block mb-1.5">
            Admin Email or Username
          </label>
          <input
            type="text"
            id="admin-identifier"
            name="identifier"
            class="admin-form-input w-full h-11 px-3.5 border border-slate-300 rounded-xl bg-white text-sm shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
            value="<?= ea($oldIdentifier) ?>"
            required
            autocomplete="username"
            placeholder="admin@glasscart.local"
            autofocus
          >
          <?php if (!empty($errors['identifier'])): ?>
            <span class="admin-field-error"><?= e($errors['identifier']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Password Field -->
        <div class="admin-form-group">
          <div class="password-label-row flex justify-between items-center mb-1.5">
            <label for="admin-password" class="admin-form-label text-xs font-bold uppercase tracking-wider text-slate-700">
              Password
            </label>
            <a href="mailto:support@glasscart.local?subject=Admin%20Password%20Reset" class="admin-forgot-link text-xs font-semibold text-indigo-600 hover:text-indigo-700">
              Forgot Password?
            </a>
          </div>
          <div class="password-input-wrap relative w-full">
            <input
              type="password"
              id="admin-password"
              name="password"
              class="admin-form-input w-full h-11 px-3.5 border border-slate-300 rounded-xl bg-white text-sm shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 pr-11"
              required
              autocomplete="current-password"
              placeholder="••••••••••••"
            >
            <button
              type="button"
              class="admin-toggle-pass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none"
              data-action="toggle-password"
              aria-label="Toggle password visibility"
              tabindex="-1"
            >
              <span class="icon-eye"><?= icon('eye', 18) ?></span>
              <span class="icon-eye-off hidden"><?= icon('eye-off', 18) ?></span>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
            <span class="admin-field-error"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Sign In Button -->
        <button type="submit" class="admin-submit-btn w-full h-11 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl transition shadow-md shadow-slate-900/10 flex items-center justify-center gap-2 mt-4">
          <span>Sign In to Admin Portal</span>
        </button>
      </form>

      <!-- Return to Storefront Link -->
      <div class="admin-return-wrap mt-6 text-center">
        <a href="<?= url('/') ?>" class="admin-return-link inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition mt-6 justify-center w-full">
          <?= icon('arrow-left', 14) ?>
          <span>Return to Storefront</span>
        </a>
      </div>

    </div>
  </div>

  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
</body>
</html>
