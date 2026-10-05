<?php
declare(strict_types=1);

$settings = $settings ?? [];
$errors = $errors ?? [];

$flatShippingMajor = isset($settings['flat_shipping_cents'])
    ? number_format((float)$settings['flat_shipping_cents'] / 100, 2, '.', '')
    : '5.00';

$freeThresholdMajor = isset($settings['free_shipping_threshold_cents'])
    ? number_format((float)$settings['free_shipping_threshold_cents'] / 100, 2, '.', '')
    : '75.00';
?>

<div class="admin-header">
  <div>
    <h1>Store Settings</h1>
    <p style="color: var(--text-3); margin-top: 4px;">Global configuration, currency, shipping rules, and payment instructions.</p>
  </div>
</div>

<form action="<?= url('/admin/settings') ?>" method="POST" style="max-width: 900px;">
  <?= csrf_field() ?>

  <div style="display: flex; flex-direction: column; gap: var(--s-6);">
    <!-- General Settings -->
    <div class="glass--strong" style="padding: var(--s-6);">
      <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4); display: flex; align-items: center; gap: var(--s-2);">
        <?= icon('sliders', 18) ?>
        <span>General Store Info</span>
      </h2>

      <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4);">
        <div class="form-group">
          <label for="set-store-name" class="form-label">Store Name</label>
          <input type="text" id="set-store-name" name="store_name" class="form-input" required value="<?= ea($settings['store_name'] ?? 'GlassCart') ?>">
          <?php if (!empty($errors['store_name'])): ?>
            <span class="form-error"><?= e($errors['store_name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="set-contact-email" class="form-label">Support / Contact Email</label>
          <input type="email" id="set-contact-email" name="contact_email" class="form-input" required value="<?= ea($settings['contact_email'] ?? '') ?>">
          <?php if (!empty($errors['contact_email'])): ?>
            <span class="form-error"><?= e($errors['contact_email']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group" style="margin-top: var(--s-4);">
        <label for="set-announcement" class="form-label">Top Banner Announcement</label>
        <input type="text" id="set-announcement" name="announcement" class="form-input" placeholder="e.g. Free express shipping on orders over $75" value="<?= ea($settings['announcement'] ?? '') ?>">
        <small style="color: var(--text-3); font-size: 0.8rem; display: block; margin-top: 4px;">Displayed in the persistent top banner across all storefront pages when non-empty.</small>
      </div>
    </div>

    <!-- Currency & Shipping -->
    <div class="glass--strong" style="padding: var(--s-6);">
      <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4); display: flex; align-items: center; gap: var(--s-2);">
        <?= icon('truck', 18) ?>
        <span>Currency & Shipping Policy</span>
      </h2>

      <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4);">
        <div class="form-group">
          <label for="set-currency-code" class="form-label">Currency Code (ISO)</label>
          <input type="text" id="set-currency-code" name="currency_code" class="form-input" required value="<?= ea($settings['currency_code'] ?? 'USD') ?>" maxlength="5">
          <?php if (!empty($errors['currency_code'])): ?>
            <span class="form-error"><?= e($errors['currency_code']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="set-currency-symbol" class="form-label">Currency Symbol</label>
          <input type="text" id="set-currency-symbol" name="currency_symbol" class="form-input" required value="<?= ea($settings['currency_symbol'] ?? '$') ?>" maxlength="5">
        </div>
      </div>

      <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4); margin-top: var(--s-4);">
        <div class="form-group">
          <label for="set-flat-shipping" class="form-label">Standard Flat Shipping (in major units)</label>
          <input type="number" step="0.01" min="0" id="set-flat-shipping" name="flat_shipping" class="form-input" value="<?= ea($flatShippingMajor) ?>">
          <small style="color: var(--text-3); font-size: 0.8rem; display: block; margin-top: 4px;">Applied when subtotal is below the free shipping threshold.</small>
        </div>

        <div class="form-group">
          <label for="set-free-threshold" class="form-label">Free Shipping Minimum Threshold</label>
          <input type="number" step="0.01" min="0" id="set-free-threshold" name="free_shipping_threshold" class="form-input" value="<?= ea($freeThresholdMajor) ?>">
          <small style="color: var(--text-3); font-size: 0.8rem; display: block; margin-top: 4px;">Orders equal to or above this amount receive free standard shipping.</small>
        </div>
      </div>
    </div>

    <!-- Payment & Instructions -->
    <div class="glass--strong" style="padding: var(--s-6);">
      <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4); display: flex; align-items: center; gap: var(--s-2);">
        <?= icon('credit-card', 18) ?>
        <span>Bank Transfer & Payment Instructions</span>
      </h2>

      <div class="form-group">
        <label for="set-bank-instructions" class="form-label">Payment Instructions for Customers</label>
        <textarea id="set-bank-instructions" name="bank_instructions" class="form-textarea" rows="4" placeholder="e.g. Please transfer to Account #1234-5678, Routing #987654321 with your order number as reference."><?= e($settings['bank_instructions'] ?? '') ?></textarea>
        <small style="color: var(--text-3); font-size: 0.8rem; display: block; margin-top: 4px;">Displayed to customers on the checkout page and order confirmation for manual payment processing.</small>
      </div>
    </div>

    <!-- Actions -->
    <div style="display: flex; justify-content: flex-end; gap: var(--s-3);">
      <a href="<?= url('/admin') ?>" class="btn btn--secondary">Cancel</a>
      <button type="submit" class="btn btn--primary">
        <?= icon('check', 16) ?>
        <span>Save Store Settings</span>
      </button>
    </div>
  </div>
</form>
