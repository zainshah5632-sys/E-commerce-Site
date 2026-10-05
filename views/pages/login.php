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

    <div class="auth-footer">
      Don't have an account yet?
      <a href="<?= url('/register') ?>">Create an Account</a>
    </div>
  </div>
</div>
