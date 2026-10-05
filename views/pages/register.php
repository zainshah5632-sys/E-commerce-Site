<?php
declare(strict_types=1);

$errors = $errors ?? [];
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('user') ?>
      </div>
      <h1>Create an Account</h1>
      <p>Join GlassCart for swift checkout and real-time order tracking.</p>
    </div>

    <?php if (!empty($errors['registration'])): ?>
      <div class="alert alert--error glass--strong">
        <span><?= e($errors['registration']) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= url('/register') ?>" method="POST" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="reg-name" class="form-label">Full Name</label>
        <input
          type="text"
          id="reg-name"
          name="full_name"
          class="form-input"
          value="<?= ea(old('full_name')) ?>"
          required
          autocomplete="name"
          placeholder="Jane Doe"
          autofocus
        >
        <?php if (!empty($errors['full_name'])): ?>
          <span class="form-error"><?= e($errors['full_name']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="reg-email" class="form-label">Email Address</label>
        <input
          type="email"
          id="reg-email"
          name="email"
          class="form-input"
          value="<?= ea(old('email')) ?>"
          required
          autocomplete="email"
          placeholder="name@example.com"
        >
        <?php if (!empty($errors['email'])): ?>
          <span class="form-error"><?= e($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="reg-password" class="form-label">Password</label>
        <input
          type="password"
          id="reg-password"
          name="password"
          class="form-input"
          required
          autocomplete="new-password"
          placeholder="Minimum 8 characters"
        >
        <?php if (!empty($errors['password'])): ?>
          <span class="form-error"><?= e($errors['password']) ?></span>
        <?php else: ?>
          <span class="form-hint">Must be at least 8 characters long.</span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="reg-password-confirm" class="form-label">Confirm Password</label>
        <input
          type="password"
          id="reg-password-confirm"
          name="password_confirmation"
          class="form-input"
          required
          autocomplete="new-password"
          placeholder="Repeat your password"
        >
        <?php if (!empty($errors['password_confirmation'])): ?>
          <span class="form-error"><?= e($errors['password_confirmation']) ?></span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5);">
        <button type="submit" class="btn btn--primary btn--block btn--lg">
          <span>Create Account</span>
          <?= icon('arrow-right') ?>
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Already have an account?
      <a href="<?= url('/login') ?>">Sign In instead</a>
    </div>
  </div>
</div>
