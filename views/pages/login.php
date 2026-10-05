<?php
declare(strict_types=1);

$next = $next ?? '/account';
$errors = $errors ?? [];
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('user') ?>
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

      <div class="form-group">
        <label for="login-email" class="form-label">Email Address</label>
        <input
          type="email"
          id="login-email"
          name="email"
          class="form-input"
          value="<?= ea(old('email')) ?>"
          required
          autocomplete="email"
          placeholder="name@example.com"
          autofocus
        >
        <?php if (!empty($errors['email'])): ?>
          <span class="form-error"><?= e($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
          <label for="login-password" class="form-label">Password</label>
        </div>
        <input
          type="password"
          id="login-password"
          name="password"
          class="form-input"
          required
          autocomplete="current-password"
          placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
        >
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
