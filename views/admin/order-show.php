<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];
$status = $order['status'] ?? 'pending';
$paymentStatus = $order['payment_status'] ?? 'pending';

$statusBadge = match($status) {
    'confirmed' => 'badge--brand',
    'shipped' => 'badge--neutral',
    'delivered' => 'badge--success',
    'cancelled' => 'badge--danger',
    default => 'badge--warning',
};
?>

<div class="admin-header">
  <div>
    <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-2);">
      <a href="<?= url('/admin/orders') ?>">Orders</a>
      <span>/</span>
      <span style="color: var(--text-1);"><?= e($order['order_number'] ?? '') ?></span>
    </div>
    <h1><?= e($order['order_number'] ?? '') ?></h1>
  </div>

  <span class="badge <?= $statusBadge ?>" style="font-size: 0.95rem; padding: 6px 16px;">
    <?= e($status) ?>
  </span>
</div>

<!-- Workflow Transitions Card -->
<div class="glass" style="padding: var(--s-4); margin-bottom: var(--s-6); display: flex; gap: var(--s-4); flex-wrap: wrap; align-items: center; justify-content: space-between;">
  <!-- Forward Fulfillment Pipeline -->
  <div style="display: flex; align-items: center; gap: var(--s-2); flex-wrap: wrap;">
    <span style="font-size: 0.88rem; font-weight: 600; color: var(--text-2);">Fulfillment Step:</span>

    <?php if ($status === 'pending'): ?>
      <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="confirmed">
        <button type="submit" class="btn btn--primary btn--sm">Confirm Order</button>
      </form>
    <?php elseif ($status === 'confirmed'): ?>
      <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="shipped">
        <button type="submit" class="btn btn--primary btn--sm">Mark as Shipped</button>
      </form>
    <?php elseif ($status === 'shipped'): ?>
      <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="delivered">
        <button type="submit" class="btn btn--primary btn--sm">Mark as Delivered</button>
      </form>
    <?php else: ?>
      <span style="font-size: 0.88rem; color: var(--text-3);">Fulfillment cycle complete.</span>
    <?php endif; ?>
  </div>

  <!-- Payment Status Step -->
  <div style="display: flex; align-items: center; gap: var(--s-2);">
    <span style="font-size: 0.88rem; font-weight: 600; color: var(--text-2);">Payment:</span>
    <span class="badge badge--neutral" style="text-transform: capitalize;"><?= e($paymentStatus) ?></span>

    <?php if ($paymentStatus !== 'paid'): ?>
      <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="payment_status" value="paid">
        <button type="submit" class="btn btn--ghost btn--sm">Mark Paid</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Cancel Action -->
  <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
    <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/cancel') ?>" method="POST" style="display:inline;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Cancel this order? This will immediately restock reserved inventory items." style="color: var(--danger);">
        Cancel &amp; Restock
      </button>
    </form>
  <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--s-6);">
  <!-- Items Table -->
  <div class="glass--strong" style="padding: var(--s-5);">
    <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4);">Ordered Line Items</h2>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th style="text-align: right;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <div style="display: flex; gap: var(--s-3); align-items: center;">
                  <div style="width: 44px; height: 44px; border-radius: var(--r-sm); overflow: hidden; background: var(--glass-subtle); flex-shrink: 0;">
                    <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="" width="44" height="44" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                  <strong><?= e($item['product_name']) ?></strong>
                </div>
              </td>
              <td><?= money((int)$item['unit_price_cents']) ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td style="text-align: right; font-weight: 700;">
                <?= money((int)$item['unit_price_cents'] * (int)$item['quantity']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display: flex; flex-direction: column; gap: var(--s-2); max-width: 260px; margin-left: auto; border-top: 1px solid var(--line); padding-top: var(--s-4); margin-top: var(--s-4);">
      <div style="display: flex; justify-content: space-between; color: var(--text-2);">
        <span>Subtotal</span>
        <span><?= money((int)($order['subtotal_cents'] ?? 0)) ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; color: var(--text-2);">
        <span>Shipping</span>
        <span><?= money((int)($order['shipping_cents'] ?? 0)) ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 1.25rem; font-family: var(--font-display); border-top: 1px solid var(--line); padding-top: var(--s-2);">
        <span>Total</span>
        <span><?= money((int)($order['total_cents'] ?? 0)) ?></span>
      </div>
    </div>
  </div>

  <!-- Customer & Destination Details -->
  <div style="display: flex; flex-direction: column; gap: var(--s-4);">
    <div class="glass--strong" style="padding: var(--s-5);">
      <h3 style="font-size: 1.05rem; margin-bottom: var(--s-3);">Customer</h3>
      <p style="font-size: 0.92rem; line-height: 1.6;">
        <strong><?= e($order['customer_name'] ?? '') ?></strong><br>
        <a href="mailto:<?= ea($order['email'] ?? '') ?>" style="color: var(--brand-600);"><?= e($order['email'] ?? '') ?></a><br>
        <?= e($order['phone'] ?? '') ?>
      </p>
    </div>

    <div class="glass--strong" style="padding: var(--s-5);">
      <h3 style="font-size: 1.05rem; margin-bottom: var(--s-3);">Shipping Destination</h3>
      <p style="font-size: 0.92rem; line-height: 1.6;">
        <?= e($shippingAddress['address_line_1'] ?? '') ?><br>
        <?php if (!empty($shippingAddress['address_line_2'])): ?>
          <?= e($shippingAddress['address_line_2']) ?><br>
        <?php endif; ?>
        <?= e($shippingAddress['city'] ?? '') ?>, <?= e($shippingAddress['state'] ?? '') ?> <?= e($shippingAddress['postal_code'] ?? '') ?><br>
        <?= e($shippingAddress['country'] ?? 'US') ?>
      </p>
    </div>

    <?php if (!empty($order['notes'])): ?>
      <div class="glass--strong" style="padding: var(--s-5);">
        <h3 style="font-size: 1.05rem; margin-bottom: var(--s-2);">Customer Notes</h3>
        <p style="font-size: 0.9rem; color: var(--text-2);"><?= nl2br(e($order['notes'])) ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>
