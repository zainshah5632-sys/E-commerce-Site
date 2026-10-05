<?php
declare(strict_types=1);

$profile = $profile ?? [];
$email = $email ?? '';
$errors = $errors ?? [];
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9); max-width: 680px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--s-5);">
    <h1>Customer Account</h1>
    <a href="<?= url('/account/orders') ?>" class="btn btn--ghost btn--sm">
      <?= icon('cart') ?>
      <span>View Orders</span>
    </a>
  </div>

  <div class="glass--strong" style="padding: var(--s-6);">
    <h2 style="font-size: 1.25rem; margin-bottom: var(--s-4);">Personal Profile</h2>

    <form action="<?= url('/account') ?>" method="POST">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="acc-email" class="form-label">Email Address (Account Identifier)</label>
        <input
          type="email"
          id="acc-email"
          class="form-input"
          value="<?= ea($email) ?>"
          disabled
          style="opacity: 0.7; cursor: not-allowed;"
        >
        <span class="form-hint">Email address cannot be changed directly.</span>
      </div>

      <div class="form-group">
        <label for="acc-name" class="form-label">Full Name</label>
        <input
          type="text"
          id="acc-name"
          name="full_name"
          class="form-input"
          value="<?= ea(old('full_name', $profile['full_name'] ?? '')) ?>"
          required
        >
        <?php if (!empty($errors['full_name'])): ?>
          <span class="form-error"><?= e($errors['full_name']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="acc-phone" class="form-label">Phone Number</label>
        <input
          type="tel"
          id="acc-phone"
          name="phone"
          class="form-input"
          value="<?= ea(old('phone', $profile['phone'] ?? '')) ?>"
          placeholder="+1 (555) 000-0000"
        >
        <?php if (!empty($errors['phone'])): ?>
          <span class="form-error"><?= e($errors['phone']) ?></span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5); display: flex; justify-content: space-between; align-items: center;">
        <button type="submit" class="btn btn--primary">
          <span>Save Changes</span>
        </button>

        <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--ghost btn--sm" style="color: var(--danger);">
            <?= icon('logout') ?>
            <span>Sign Out</span>
          </button>
        </form>
      </div>
    </form>
  </div>
</div>
