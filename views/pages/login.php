<?php
declare(strict_types=1);

$next = $next ?? '/account';
$errors = $errors ?? [];
$oldIdentifier = old('identifier', old('email', ''));
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('log-in') ?>
      </div>
      <h1>Welcome Back</h1>
      <p>Enter your credentials to access your account and orders.</p>
    </div>

    <?php if (!empty($errors['login'])): ?>
      <div class="alert alert--error glass--strong">
        <span><?= e($errors['login']) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= url('/login') ?>" method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= ea($next) ?>">

      <!-- Identifier: Email or Username per Teacher Requirement #1 -->
      <div class="form-group">
        <label for="login-identifier" class="form-label">Email Address or Username</label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('at-sign') ?></span>
          <input
            type="text"
            id="login-identifier"
            name="identifier"
            class="form-input form-input--with-icon"
            value="<?= ea($oldIdentifier) ?>"
            required
            autocomplete="username"
            placeholder="you@example.com or username"
            autofocus
          >
        </div>
        <?php if (!empty($errors['identifier'])): ?>
          <span class="form-error"><?= e($errors['identifier']) ?></span>
        <?php elseif (!empty($errors['email'])): ?>
          <span class="form-error"><?= e($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Password with Hide/Unhide toggle per Teacher Requirement #3 -->
      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <label for="login-password" class="form-label">Password</label>
          <a href="<?= url('/forgot-password') ?>" style="font-size: 0.8rem; color: var(--brand-600); text-decoration: none; font-weight: 500;">Forgot password?</a>
        </div>
        <div class="password-input-wrap">
          <input
            type="password"
            id="login-password"
            name="password"
            class="form-input"
            required
            autocomplete="current-password"
            placeholder="Enter your password"
          >
          <button
            type="button"
            class="btn-toggle-password"
            data-action="toggle-password"
            aria-label="Toggle password visibility"
            tabindex="-1"
          >
            <span class="icon-eye"><?= icon('eye') ?></span>
            <span class="icon-eye-off" style="display: none;"><?= icon('eye-off') ?></span>
          </button>
        </div>
        <?php if (!empty($errors['password'])): ?>
          <span class="form-error"><?= e($errors['password']) ?></span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5);">
        <button type="submit" class="btn btn--primary btn--block btn--lg">
          <span>Sign In</span>
          <?= icon('arrow-right') ?>
        </button>
      </div>
    </form>

    <!-- Social OAuth Authentication -->
    <div class="oauth-divider">
      <span>or continue with</span>
    </div>

    <div class="oauth-buttons">
      <a href="<?= url('/auth/oauth/google') ?>" class="btn-oauth">
        <svg width="18" height="18" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <span>Google</span>
      </a>
      <a href="<?= url('/auth/oauth/facebook') ?>" class="btn-oauth">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2">
          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
        <span>Facebook</span>
      </a>
    </div>

    <div class="auth-footer">
      Don't have an account yet?
      <a href="<?= url('/register') ?>">Create an Account</a>
    </div>
  </div>
</div>
