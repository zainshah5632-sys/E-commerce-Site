<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];

$statusBadge = match($order['status'] ?? 'pending') {
    'confirmed' => 'badge--brand',
    'shipped' => 'badge--neutral',
    'delivered' => 'badge--success',
    'cancelled' => 'badge--danger',
    default => 'badge--warning',
};
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9); max-width: 820px;">
  <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-4);">
    <a href="<?= url('/account') ?>">Account</a>
    <span>/</span>
    <a href="<?= url('/account/orders') ?>">Orders</a>
    <span>/</span>
    <span style="color: var(--text-1);"><?= e($order['order_number'] ?? '') ?></span>
  </div>

  <div class="glass--strong" style="padding: var(--s-6);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--line); padding-bottom: var(--s-4); margin-bottom: var(--s-5); flex-wrap: wrap; gap: var(--s-3);">
      <div>
        <h1 style="font-size: 1.75rem;"><?= e($order['order_number'] ?? '') ?></h1>
        <div style="font-size: 0.88rem; color: var(--text-3); margin-top: 2px;">
          Placed on <?= date('j M Y, H:i', strtotime($order['created_at'])) ?>
        </div>
      </div>
      <div>
        <span class="badge <?= $statusBadge ?>" style="font-size: 0.85rem; padding: 6px 14px;">
          Status: <?= e($order['status'] ?? '') ?>
        </span>
      </div>
    </div>

    <!-- Items list -->
    <h2 style="font-size: 1.2rem; margin-bottom: var(--s-4);">Items</h2>
    <div style="display: flex; flex-direction: column; gap: var(--s-3); margin-bottom: var(--s-6);">
      <?php foreach ($items as $item): ?>
        <div class="glass" style="display: flex; gap: var(--s-4); padding: var(--s-3); border-radius: var(--r-md); align-items: center;">
          <div style="width: 56px; height: 56px; border-radius: var(--r-sm); overflow: hidden; background: var(--glass-subtle); flex-shrink: 0;">
            <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="" width="56" height="56" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <div style="flex-grow: 1;">
            <div style="font-weight: 600;"><?= e($item['product_name']) ?></div>
            <div style="font-size: 0.85rem; color: var(--text-3);">Qty: <?= $item['quantity'] ?> &times; <?= money((int)$item['unit_price_cents']) ?></div>
          </div>
          <div style="font-weight: 700;">
            <?= money((int)$item['unit_price_cents'] * (int)$item['quantity']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Details Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-5); border-top: 1px solid var(--line); padding-top: var(--s-5);">
      <div>
        <h3 style="font-size: 1rem; margin-bottom: var(--s-2);">Delivery Address</h3>
        <p style="font-size: 0.9rem; line-height: 1.6;">
          <?= e($order['customer_name'] ?? '') ?><br>
          <?= e($shippingAddress['address_line_1'] ?? '') ?><br>
          <?php if (!empty($shippingAddress['address_line_2'])): ?>
            <?= e($shippingAddress['address_line_2']) ?><br>
          <?php endif; ?>
          <?= e($shippingAddress['city'] ?? '') ?>, <?= e($shippingAddress['state'] ?? '') ?> <?= e($shippingAddress['postal_code'] ?? '') ?><br>
          <?= e($order['phone'] ?? '') ?>
        </p>
      </div>

      <div>
        <h3 style="font-size: 1rem; margin-bottom: var(--s-2);">Payment &amp; Financials</h3>
        <div style="display: flex; flex-direction: column; gap: var(--s-2); font-size: 0.92rem;">
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Method</span>
            <span style="text-transform: uppercase; font-weight: 600;"><?= e($order['payment_method'] ?? '') ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Payment Status</span>
            <span style="text-transform: capitalize;"><?= e($order['payment_status'] ?? '') ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Subtotal</span>
            <span><?= money((int)($order['subtotal_cents'] ?? 0)) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; color: var(--text-2);">
            <span>Shipping</span>
            <span><?= money((int)($order['shipping_cents'] ?? 0)) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 1.15rem; border-top: 1px dashed var(--line); padding-top: var(--s-2); margin-top: var(--s-1);">
            <span>Total</span>
            <span><?= money((int)($order['total_cents'] ?? 0)) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
