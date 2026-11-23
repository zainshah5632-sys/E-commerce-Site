<?php
declare(strict_types=1);

$token = $token ?? '';
$errors = $errors ?? [];
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('shield') ?>
      </div>
      <h1>Set New Password</h1>
      <p>Enter a secure, unique new password for your account.</p>
    </div>

    <?php if (!empty($errors['reset'])): ?>
      <div class="alert alert--error glass--strong">
        <span><?= e($errors['reset']) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= url('/reset-password') ?>" method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= ea($token) ?>">

      <div class="form-group">
        <label for="new-password" class="form-label">New Password</label>
        <div class="password-input-wrap">
          <input
            type="password"
            id="new-password"
            name="password"
            class="form-input"
            required
            autocomplete="new-password"
            placeholder="Minimum 8 characters"
            autofocus
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

      <div class="form-group">
        <label for="confirm-new-password" class="form-label">Confirm New Password</label>
        <div class="password-input-wrap">
          <input
            type="password"
            id="confirm-new-password"
            name="password_confirmation"
            class="form-input"
            required
            autocomplete="new-password"
            placeholder="Repeat your new password"
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
        <?php if (!empty($errors['password_confirmation'])): ?>
          <span class="form-error"><?= e($errors['password_confirmation']) ?></span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5);">
        <button type="submit" class="btn btn--primary btn--block btn--lg">
          <span>Update Password &amp; Sign In</span>
          <?= icon('arrow-right') ?>
        </button>
      </div>
    </form>
  </div>
</div>

