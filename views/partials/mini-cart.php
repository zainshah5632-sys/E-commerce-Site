<?php
declare(strict_types=1);

use App\Services\CartService;

$summary = $summary ?? CartService::summary();
$items = $summary['items'];
$count = $summary['count'];
$subtotal = $summary['subtotal_cents'];
$remaining = $summary['free_shipping_remaining'];
$percent = $summary['free_shipping_percent'];
?>
<div class="mini-cart">
  <?php if (empty($items)): ?>
    <div style="text-align: center; padding-block: var(--s-6);">
      <div style="color: var(--text-3); margin-bottom: var(--s-3);">
        <?= icon('cart') ?>
      </div>
      <h3>Your cart is empty</h3>
      <p style="margin-top: var(--s-2); margin-bottom: var(--s-4);">Explore our collection of precision hardware.</p>
      <a href="<?= url('/shop') ?>" class="btn btn--primary btn--sm" data-action="close-cart">Browse Products</a>
    </div>
  <?php else: ?>
    <!-- Free Shipping Progress -->
    <div style="margin-bottom: var(--s-4);">
      <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-2); display: flex; justify-content: space-between;">
        <?php if ($remaining > 0): ?>
          <span>Add <?= money($remaining) ?> more for free shipping</span>
        <?php else: ?>
          <span style="color: var(--success); font-weight: 700;">You unlocked free shipping!</span>
        <?php endif; ?>
        <span><?= $percent ?>%</span>
      </div>
      <div class="shipping-bar">
        <div class="shipping-bar__fill" style="width: <?= $percent ?>%;"></div>
      </div>
    </div>

    <!-- Items List -->
    <div class="mini-cart__items" style="display: flex; flex-direction: column; gap: var(--s-3); margin-bottom: var(--s-5);">
      <?php foreach ($items as $item): ?>
        <div class="mini-cart__item glass--flat" style="display: flex; gap: var(--s-3); padding: var(--s-3); border-radius: var(--r-md); align-items: center;">
          <div style="width: 56px; height: 56px; border-radius: var(--r-sm); overflow: hidden; background: var(--glass-subtle); flex-shrink: 0;">
            <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="<?= ea($item['name']) ?>" width="56" height="56" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <div style="flex-grow: 1; min-width: 0;">
            <div style="font-weight: 600; font-size: 0.92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              <?= e($item['name']) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-3); margin-top: 2px;">
              <?= $item['quantity'] ?> &times; <?= money($item['price_cents']) ?>
            </div>
          </div>
          <div>
            <button type="button" class="btn btn--ghost btn--icon js-cart-remove" data-product-id="<?= ea($item['product_id']) ?>" aria-label="Remove <?= ea($item['name']) ?>" title="Remove item">
              <?= icon('trash') ?>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Mini-Cart Subtotal & Actions -->
    <div style="border-top: 1px solid var(--line); padding-top: var(--s-4);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--s-4);">
        <span style="font-weight: 600; color: var(--text-2);">Subtotal</span>
        <span style="font-size: 1.35rem; font-weight: 800; font-family: var(--font-display);"><?= money($subtotal) ?></span>
      </div>
      <div style="display: flex; flex-direction: column; gap: var(--s-2);">
        <a href="<?= url('/checkout') ?>" class="btn btn--primary btn--block">Proceed to Checkout</a>
        <a href="<?= url('/cart') ?>" class="btn btn--ghost btn--block" data-action="close-cart">View Full Cart</a>
      </div>
    </div>
  <?php endif; ?>
</div>
