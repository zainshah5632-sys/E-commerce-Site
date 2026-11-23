<?php
declare(strict_types=1);

$errors = $errors ?? [];
$success = $success ?? false;
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('shield') ?>
      </div>
      <h1>Reset Password</h1>
      <p>Enter your verified email address to receive password recovery instructions.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert--success glass--strong" style="margin-bottom: var(--s-5);">
        <span>If an account exists for that email, recovery instructions have been dispatched. Please check your inbox (and spam folder).</span>
      </div>
      <div style="text-align: center; margin-top: var(--s-4);">
        <a href="<?= url('/login') ?>" class="btn btn--primary btn--block">Return to Sign In</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors['email'])): ?>
        <div class="alert alert--error glass--strong">
          <span><?= e($errors['email']) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= url('/forgot-password') ?>" method="POST" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="reset-email" class="form-label">Email Address</label>
          <div class="input-with-icon-wrap">
            <span class="input-icon-left"><?= icon('at-sign') ?></span>
            <input
              type="email"
              id="reset-email"
              name="email"
              class="form-input form-input--with-icon"
              value="<?= ea(old('email')) ?>"
              required
              autocomplete="email"
              placeholder="you@example.com"
              autofocus
            >
          </div>
        </div>

        <div style="margin-top: var(--s-5);">
          <button type="submit" class="btn btn--primary btn--block btn--lg">
            <span>Send Recovery Instructions</span>
            <?= icon('arrow-right') ?>
          </button>
        </div>
      </form>

      <div class="auth-footer">
        Remember your password?
        <a href="<?= url('/login') ?>">Sign In</a>
      </div>
    <?php endif; ?>
  </div>
</div>

