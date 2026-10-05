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

  <style>
    .admin-login-backdrop {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.05), transparent 45%),
                  radial-gradient(circle at 85% 85%, rgba(236, 72, 153, 0.03), transparent 40%),
                  #f8fafc;
      padding: 1.5rem 1rem;
      position: relative;
    }

    [data-theme="dark"] .admin-login-backdrop {
      background: radial-gradient(circle at 50% 15%, rgba(99, 102, 241, 0.12), transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.06), transparent 45%),
                  #0c0f17;
    }

    .admin-login-card {
      width: 100%;
      max-width: 28rem; /* max-w-md (448px) */
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.85);
      border-radius: 1rem; /* rounded-2xl */
      padding: 2.25rem 2rem; /* p-8 */
      box-shadow: 0 20px 25px -5px rgba(226, 232, 240, 0.6), 0 8px 10px -6px rgba(226, 232, 240, 0.4);
      position: relative;
      z-index: 2;
    }

    [data-theme="dark"] .admin-login-card {
      background: rgba(18, 24, 38, 0.9);
      border-color: rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
    }

    .admin-badge-pill {
      font-size: 0.75rem; /* text-xs */
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #4f46e5;
      background-color: #eef2ff;
      border: 1px solid rgba(224, 231, 255, 0.8);
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      margin-bottom: 0.75rem;
    }

    .admin-badge-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background-color: #10b981;
      box-shadow: 0 0 6px rgba(16, 185, 129, 0.8);
    }

    [data-theme="dark"] .admin-badge-pill {
      background-color: rgba(99, 102, 241, 0.15);
      border-color: rgba(99, 102, 241, 0.3);
      color: #a5b4fc;
    }

    .admin-login-title {
      font-size: 1.5rem; /* text-2xl */
      font-weight: 700;
      letter-spacing: -0.025em;
      color: #0f172a; /* text-slate-900 */
      line-height: 1.25;
      margin: 0;
    }

    [data-theme="dark"] .admin-login-title {
      color: #f8fafc;
    }

    .admin-login-subtitle {
      font-size: 0.875rem; /* text-sm */
      color: #64748b; /* text-slate-500 */
      margin-top: 0.25rem; /* mt-1 */
      margin-bottom: 1.5rem; /* mb-6 */
      line-height: 1.5;
    }

    [data-theme="dark"] .admin-login-subtitle {
      color: #94a3b8;
    }

    .admin-form-label {
      display: block;
      font-size: 0.75rem; /* text-xs */
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #334155; /* text-slate-700 */
      margin-bottom: 0.375rem; /* mb-1.5 */
    }

    [data-theme="dark"] .admin-form-label {
      color: #cbd5e1;
    }

    .admin-form-input {
      width: 100%;
      height: 2.75rem; /* h-11 */
      background-color: #ffffff;
      border: 1px solid #cbd5e1; /* border-slate-300 */
      border-radius: 0.75rem; /* rounded-xl */
      padding: 0 0.875rem; /* px-3.5 */
      font-size: 0.875rem; /* text-sm */
      color: #0f172a; /* text-slate-900 */
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      transition: all 0.15s ease;
    }

    .admin-form-input:focus {
      outline: none;
      border-color: #4f46e5; /* border-indigo-600 */
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); /* focus:ring-2 focus:ring-indigo-500/20 */
    }

    .admin-form-input::placeholder {
      color: #94a3b8; /* placeholder-slate-400 */
    }

    [data-theme="dark"] .admin-form-input {
      background-color: rgba(15, 23, 42, 0.65);
      border-color: rgba(255, 255, 255, 0.14);
      color: #f8fafc;
    }

    [data-theme="dark"] .admin-form-input:focus {
      border-color: #818cf8;
      box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
    }

    .admin-login-btn {
      width: 100%;
      height: 2.75rem; /* h-11 */
      background-color: #0f172a; /* bg-slate-900 */
      color: #ffffff;
      font-weight: 600;
      font-size: 0.875rem; /* text-sm */
      border-radius: 0.75rem; /* rounded-xl */
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 0.5rem; /* mt-2 */
      box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.1), 0 2px 4px -2px rgba(15, 23, 42, 0.1);
      transition: all 0.15s ease;
      cursor: pointer;
      user-select: none;
    }

    .admin-login-btn:hover {
      background-color: #1e293b; /* hover:bg-slate-800 */
    }

    .admin-login-btn:active {
      transform: scale(0.99); /* active:scale-[0.99] */
    }

    [data-theme="dark"] .admin-login-btn {
      background-color: #4f46e5;
      box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    }

    [data-theme="dark"] .admin-login-btn:hover {
      background-color: #4338ca;
    }

    .admin-form-stack {
      display: flex;
      flex-direction: column;
      gap: 1rem; /* space-y-4 */
    }

    .password-wrap {
      position: relative;
    }

    .password-wrap .admin-form-input {
      padding-right: 2.75rem;
    }

    .toggle-pass-btn {
      position: absolute;
      right: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      padding: 0.25rem;
      color: #94a3b8;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .toggle-pass-btn:hover {
      color: #475569;
    }

    [data-theme="dark"] .toggle-pass-btn:hover {
      color: #e2e8f0;
    }
  </style>
</head>
<body>
  <div class="admin-login-backdrop min-h-screen flex items-center justify-center bg-slate-50/70 p-4">
    <div class="admin-login-card max-w-md w-full bg-white rounded-2xl border border-slate-200/80 p-8 shadow-xl shadow-slate-200/40">
      
      <!-- Brand & Micro-badge -->
      <div style="text-align: center; margin-bottom: 1.25rem;">
        <span class="admin-badge-pill text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full inline-block mb-3">
          <span class="admin-badge-dot"></span>
          <span>Admin Portal</span>
        </span>
        <h1 class="admin-login-title text-2xl font-bold tracking-tight text-slate-900">Admin Sign In</h1>
        <p class="admin-login-subtitle text-sm text-slate-500 mt-1 mb-6 leading-normal">
          Enter your administrative credentials to access the GlassCart control center.
        </p>
      </div>

      <?php if (!empty($errors['login'])): ?>
        <div class="alert alert--error glass--strong" style="margin-bottom: 1.25rem; font-size: 0.85rem; padding: 0.75rem 1rem; border-radius: 0.75rem;">
          <span><?= e($errors['login']) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= url('/admin/login') ?>" method="POST" class="admin-form-stack space-y-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= ea($next) ?>">

        <!-- Identifier Input -->
        <div>
          <label for="admin-identifier" class="admin-form-label block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Admin Email or Username
          </label>
          <input
            type="text"
            id="admin-identifier"
            name="identifier"
            class="admin-form-input w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
            value="<?= ea($oldIdentifier) ?>"
            required
            autocomplete="username"
            placeholder="admin@glasscart.local"
            autofocus
          >
          <?php if (!empty($errors['identifier'])): ?>
            <span class="form-error" style="font-size: 0.78rem; color: var(--danger); margin-top: 0.25rem; display: block;"><?= e($errors['identifier']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Password Input -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.375rem;">
            <label for="admin-password" class="admin-form-label block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5" style="margin-bottom: 0;">
              Password
            </label>
            <a href="mailto:support@glasscart.local?subject=Admin%20Password%20Reset" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700" style="text-decoration: none;">
              Forgot Password?
            </a>
          </div>
          <div class="password-wrap">
            <input
              type="password"
              id="admin-password"
              name="password"
              class="admin-form-input w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
              required
              autocomplete="current-password"
              placeholder="••••••••••••"
            >
            <button
              type="button"
              class="toggle-pass-btn"
              data-action="toggle-password"
              aria-label="Toggle password visibility"
              tabindex="-1"
            >
              <span class="icon-eye"><?= icon('eye', 16) ?></span>
              <span class="icon-eye-off" style="display: none;"><?= icon('eye-off', 16) ?></span>
            </button>
          </div>
          <?php if (!empty($errors['password'])): ?>
            <span class="form-error" style="font-size: 0.78rem; color: var(--danger); margin-top: 0.25rem; display: block;"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <!-- CTA Sign In Button -->
        <button type="submit" class="admin-login-btn w-full h-11 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl transition shadow-md shadow-slate-900/10 active:scale-[0.99] flex items-center justify-center gap-2 mt-2">
          <?= icon('lock', 16) ?>
          <span>Sign In to Admin Portal</span>
        </button>
      </form>

      <!-- Sub-footer Return Link -->
      <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; text-align: center;">
        <a href="<?= url('/') ?>" style="color: #64748b; font-size: 0.8125rem; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 0.375rem;">
          <?= icon('arrow-left', 14) ?>
          <span>Return to Storefront</span>
        </a>
      </div>
    </div>
  </div>

  <script src="<?= asset('assets/js/ui.js') ?>" defer></script>
</body>
</html>
