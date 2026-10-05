<?php
declare(strict_types=1);

$summary = $summary ?? [];
$items = $summary['items'] ?? [];
$subtotal = $summary['subtotal_cents'] ?? 0;
$shipping = $summary['shipping_cents'] ?? 0;
$total = $summary['total_cents'] ?? 0;
$remaining = $summary['free_shipping_remaining'] ?? 0;
$percent = $summary['free_shipping_percent'] ?? 0;
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9);">
  <h1 style="margin-bottom: var(--s-5);">Shopping Cart</h1>

  <?php if (empty($items)): ?>
    <div class="glass" style="text-align: center; padding: var(--s-8) var(--s-4);">
      <div style="color: var(--text-3); margin-bottom: var(--s-3);"><?= icon('cart') ?></div>
      <h2>Your cart is currently empty</h2>
      <p style="margin-top: var(--s-2); margin-bottom: var(--s-5);">You haven't added any products to your bag yet.</p>
      <a href="<?= url('/shop') ?>" class="btn btn--primary">Browse Our Catalog</a>
    </div>
  <?php else: ?>
    <!-- Free Shipping Progress -->
    <div class="glass" style="padding: var(--s-4); margin-bottom: var(--s-5);">
      <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 0.95rem; margin-bottom: var(--s-2);">
        <?php if ($remaining > 0): ?>
          <span>Add <strong><?= money($remaining) ?></strong> more to unlock Free Worldwide Shipping!</span>
        <?php else: ?>
          <span style="color: var(--success); font-weight: 700;">Congratulations! You have unlocked Free Shipping.</span>
        <?php endif; ?>
        <span><?= $percent ?>%</span>
      </div>
      <div class="shipping-bar" style="height: 10px;">
        <div class="shipping-bar__fill" style="width: <?= $percent ?>%;"></div>
      </div>
    </div>

    <div class="checkout-grid">
      <!-- Items Table -->
      <div class="glass--strong data-table-wrap">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th style="text-align: center;">Quantity</th>
              <th style="text-align: right;">Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div style="display: flex; gap: var(--s-3); align-items: center;">
                    <div style="width: 64px; height: 64px; border-radius: var(--r-md); overflow: hidden; background: var(--glass-subtle); flex-shrink: 0;">
                      <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="<?= ea($item['name']) ?>" width="64" height="64" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                      <a href="<?= url('/product/' . rawurlencode($item['slug'])) ?>" style="font-weight: 600; color: var(--text-1);">
                        <?= e($item['name']) ?>
                      </a>
                    </div>
                  </div>
                </td>
                <td><?= money($item['price_cents']) ?></td>
                <td style="text-align: center;">
                  <form action="<?= url('/cart/update') ?>" method="POST" style="display: inline-flex; align-items: center; gap: 4px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= ea($item['product_id']) ?>">
                    <input
                      type="number"
                      name="quantity"
                      value="<?= $item['quantity'] ?>"
                      min="1"
                      max="<?= min(10, $item['stock']) ?>"
                      class="form-input js-cart-qty"
                      data-product-id="<?= ea($item['product_id']) ?>"
                      style="width: 58px; text-align: center; padding: 6px 4px;"
                    >
                    <noscript>
                      <button type="submit" class="btn btn--ghost btn--sm">Update</button>
                    </noscript>
                  </form>
                </td>
                <td style="text-align: right; font-weight: 700;"><?= money($item['line_total_cents']) ?></td>
                <td style="text-align: right;">
                  <form action="<?= url('/cart/remove') ?>" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= ea($item['product_id']) ?>">
                    <button type="submit" class="btn btn--ghost btn--icon" title="Remove Item" aria-label="Remove <?= ea($item['name']) ?>">
                      <?= icon('trash') ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Order Summary Card -->
      <div>
        <div class="glass--strong" style="padding: var(--s-6); position: sticky; top: calc(var(--header-h) + var(--s-4));">
          <h2 style="font-size: 1.35rem; margin-bottom: var(--s-4);">Summary</h2>

          <div style="display: flex; flex-direction: column; gap: var(--s-3); border-bottom: 1px solid var(--line); padding-bottom: var(--s-4); margin-bottom: var(--s-4);">
            <div style="display: flex; justify-content: space-between; color: var(--text-2);">
              <span>Subtotal</span>
              <span><?= money($subtotal) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; color: var(--text-2);">
              <span>Estimated Shipping</span>
              <span><?= $shipping === 0 ? '<strong style="color: var(--success);">Free</strong>' : money($shipping) ?></span>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 1.35rem; font-weight: 800; font-family: var(--font-display); margin-bottom: var(--s-5);">
            <span>Estimated Total</span>
            <span><?= money($total) ?></span>
          </div>

          <a href="<?= url('/checkout') ?>" class="btn btn--primary btn--block btn--lg">
            <span>Proceed to Checkout</span>
            <?= icon('arrow-right') ?>
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
