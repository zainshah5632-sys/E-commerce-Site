<?php
declare(strict_types=1);

use App\Core\Session;

$summary = $summary ?? [];
$items = $summary['items'] ?? [];
$subtotal = $summary['subtotal_cents'] ?? 0;
$shipping = $summary['shipping_cents'] ?? 0;
$total = $summary['total_cents'] ?? 0;
$errors = $errors ?? [];
$userProfile = $userProfile ?? null;

$defaultName = old('customer_name', $userProfile['full_name'] ?? Session::instance()->userName());
$defaultEmail = old('email', Session::instance()->userEmail());
$defaultPhone = old('phone', $userProfile['phone'] ?? '');
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9);">
  <h1 style="margin-bottom: var(--s-5);">Secure Checkout</h1>

  <?php if (!empty($errors['checkout'])): ?>
    <div class="alert alert--error glass--strong">
      <span><?= e($errors['checkout']) ?></span>
    </div>
  <?php endif; ?>

  <div class="checkout-grid">
    <!-- Checkout Form -->
    <div>
      <form action="<?= url('/checkout') ?>" method="POST" class="glass--strong" style="padding: var(--s-6);">
        <?= csrf_field() ?>

        <h2 style="font-size: 1.35rem; margin-bottom: var(--s-4);">1. Contact Details</h2>

        <div class="form-group">
          <label for="cust-name" class="form-label">Full Name</label>
          <input
            type="text"
            id="cust-name"
            name="customer_name"
            class="form-input"
            value="<?= ea($defaultName) ?>"
            required
            autocomplete="name"
          >
          <?php if (!empty($errors['customer_name'])): ?>
            <span class="form-error"><?= e($errors['customer_name']) ?></span>
          <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-3);">
          <div class="form-group">
            <label for="cust-email" class="form-label">Email Address</label>
            <input
              type="email"
              id="cust-email"
              name="email"
              class="form-input"
              value="<?= ea($defaultEmail) ?>"
              required
              autocomplete="email"
            >
            <?php if (!empty($errors['email'])): ?>
              <span class="form-error"><?= e($errors['email']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="cust-phone" class="form-label">Phone Number</label>
            <input
              type="tel"
              id="cust-phone"
              name="phone"
              class="form-input"
              value="<?= ea($defaultPhone) ?>"
              required
              autocomplete="tel"
            >
            <?php if (!empty($errors['phone'])): ?>
              <span class="form-error"><?= e($errors['phone']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <h2 style="font-size: 1.35rem; margin-top: var(--s-6); margin-bottom: var(--s-4);">2. Shipping Address</h2>

        <div class="form-group">
          <label for="addr-1" class="form-label">Street Address</label>
          <input
            type="text"
            id="addr-1"
            name="address_line_1"
            class="form-input"
            value="<?= ea(old('address_line_1')) ?>"
            required
            placeholder="123 Innovation Blvd, Suite 400"
          >
          <?php if (!empty($errors['address_line_1'])): ?>
            <span class="form-error"><?= e($errors['address_line_1']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="addr-2" class="form-label">Apartment, Suite, Unit (Optional)</label>
          <input
            type="text"
            id="addr-2"
            name="address_line_2"
            class="form-input"
            value="<?= ea(old('address_line_2')) ?>"
          >
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: var(--s-3);">
          <div class="form-group">
            <label for="addr-city" class="form-label">City</label>
            <input type="text" id="addr-city" name="city" class="form-input" value="<?= ea(old('city')) ?>" required>
            <?php if (!empty($errors['city'])): ?>
              <span class="form-error"><?= e($errors['city']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="addr-state" class="form-label">State / Prov</label>
            <input type="text" id="addr-state" name="state" class="form-input" value="<?= ea(old('state')) ?>" required>
            <?php if (!empty($errors['state'])): ?>
              <span class="form-error"><?= e($errors['state']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="addr-zip" class="form-label">Postal Code</label>
            <input type="text" id="addr-zip" name="postal_code" class="form-input" value="<?= ea(old('postal_code')) ?>" required>
            <?php if (!empty($errors['postal_code'])): ?>
              <span class="form-error"><?= e($errors['postal_code']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <h2 style="font-size: 1.35rem; margin-top: var(--s-6); margin-bottom: var(--s-4);">3. Payment Method</h2>

        <div class="payment-methods">
          <label class="payment-card">
            <div style="display: flex; align-items: center; gap: var(--s-2);">
              <input type="radio" name="payment_method" value="cod" <?= old('payment_method', 'cod') === 'cod' ? 'checked' : '' ?>>
              <strong>Cash on Delivery (COD)</strong>
            </div>
            <span style="font-size: 0.82rem; color: var(--text-3); margin-left: 24px;">Pay securely with cash upon package receipt.</span>
          </label>

          <label class="payment-card">
            <div style="display: flex; align-items: center; gap: var(--s-2);">
              <input type="radio" name="payment_method" value="bank_transfer" <?= old('payment_method') === 'bank_transfer' ? 'checked' : '' ?>>
              <strong>Bank Transfer</strong>
            </div>
            <span style="font-size: 0.82rem; color: var(--text-3); margin-left: 24px;">Direct bank deposit instructions provided upon order creation.</span>
          </label>
        </div>

        <div class="form-group" style="margin-top: var(--s-5);">
          <label for="order-notes" class="form-label">Order Notes (Optional)</label>
          <textarea id="order-notes" name="notes" class="form-textarea" rows="2" placeholder="Special delivery instructions..."><?= e(old('notes')) ?></textarea>
        </div>

        <div style="margin-top: var(--s-6);">
          <button type="submit" class="btn btn--primary btn--block btn--lg">
            <span>Place Order (<?= money($total) ?>)</span>
            <?= icon('arrow-right') ?>
          </button>
        </div>
      </form>
    </div>

    <!-- Order Review Column -->
    <div>
      <div class="glass" style="padding: var(--s-6); position: sticky; top: calc(var(--header-h) + var(--s-4));">
        <h2 style="font-size: 1.25rem; margin-bottom: var(--s-4);">Order Summary (<?= count($items) ?> items)</h2>

        <div style="display: flex; flex-direction: column; gap: var(--s-3); max-height: 320px; overflow-y: auto; padding-right: var(--s-2); margin-bottom: var(--s-4);">
          <?php foreach ($items as $item): ?>
            <div style="display: flex; gap: var(--s-3); align-items: center;">
              <div style="width: 48px; height: 48px; border-radius: var(--r-sm); overflow: hidden; background: var(--glass-subtle); flex-shrink: 0;">
                <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="" width="48" height="48" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <div style="flex-grow: 1; min-width: 0;">
                <div style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?= e($item['name']) ?>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-3);">Qty: <?= $item['quantity'] ?></div>
              </div>
              <div style="font-weight: 700; font-size: 0.92rem;"><?= money($item['line_total_cents']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="border-top: 1px solid var(--line); padding-top: var(--s-3); display: flex; flex-direction: column; gap: var(--s-2); font-size: 0.92rem;">
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Subtotal</span>
            <span><?= money($subtotal) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Shipping</span>
            <span><?= $shipping === 0 ? '<strong style="color: var(--success);">Free</strong>' : money($shipping) ?></span>
          </div>
          <div style="border-top: 1px solid var(--line); padding-top: var(--s-3); margin-top: var(--s-2); display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: 800; font-family: var(--font-display);">
            <span>Total</span>
            <span><?= money($total) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
