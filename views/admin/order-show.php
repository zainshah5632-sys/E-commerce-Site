<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];
$status = $order['status'] ?? 'pending';
$paymentStatus = $order['payment_status'] ?? 'pending';

$statusClass = match($status) {
    'confirmed' => 'status-pill--confirmed',
    'shipped'   => 'status-pill--shipped',
    'delivered' => 'status-pill--delivered',
    'cancelled' => 'status-pill--cancelled',
    default     => 'status-pill--pending',
};
$paymentPaid = ($paymentStatus === 'paid');
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <a href="<?= url('/admin/orders') ?>">Orders</a>
    <span class="sep">/</span>
    <span class="current"><?= e($order['order_number'] ?? '') ?></span>
  </div>

  <!-- Header -->
  <div class="admin-header-row">
    <div>
      <div style="display: flex; align-items: center; gap: var(--s-3);">
        <h1 class="admin-title">Order <?= e($order['order_number'] ?? '') ?></h1>
        <span class="order-status-pill <?= $statusClass ?>" style="padding: 4px 12px; font-size: 0.82rem;">
          <span class="pill-dot"></span>
          <span><?= ucfirst(e($status)) ?></span>
        </span>
      </div>
      <p class="admin-subtitle">
        Placed on <?= !empty($order['created_at']) ? date('M j, Y &bull; H:i', strtotime($order['created_at'])) : '—' ?>
      </p>
    </div>
    <div class="admin-header-actions">
      <a href="<?= url('/admin/orders') ?>" class="btn-admin-ghost">
        <?= icon('arrow-left') ?>
        <span>Back to Orders</span>
      </a>
    </div>
  </div>

  <!-- Fulfillment & Payment Action Bar -->
  <div class="admin-card" style="padding: var(--s-4) var(--s-5); margin-bottom: var(--s-5); display: flex; flex-wrap: wrap; gap: var(--s-4); align-items: center; justify-content: space-between;">
    <!-- Step Actions -->
    <div style="display: flex; align-items: center; gap: var(--s-3); flex-wrap: wrap;">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Fulfillment:</span>

      <?php if ($status === 'pending'): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="confirmed">
          <button type="submit" class="btn-admin-primary btn-admin-sm">
            <?= icon('check') ?>
            <span>Confirm Order</span>
          </button>
        </form>
      <?php elseif ($status === 'confirmed'): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="shipped">
          <button type="submit" class="btn-admin-primary btn-admin-sm">
            <?= icon('truck') ?>
            <span>Mark as Shipped</span>
          </button>
        </form>
      <?php elseif ($status === 'shipped'): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/status') ?>" method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="status" value="delivered">
          <button type="submit" class="btn-admin-primary btn-admin-sm">
            <?= icon('check') ?>
            <span>Mark as Delivered</span>
          </button>
        </form>
      <?php else: ?>
        <span class="text-sm text-slate-500">Order lifecycle finalized.</span>
      <?php endif; ?>
    </div>

    <!-- Payment Actions -->
    <div style="display: flex; align-items: center; gap: var(--s-3); flex-wrap: wrap;">
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Payment:</span>
      <span class="admin-tag-badge <?= $paymentPaid ? 'text-emerald' : 'text-amber' ?>" style="text-transform: capitalize;">
        <?= e($paymentStatus) ?>
      </span>

      <?php if (!$paymentPaid): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="payment_status" value="paid">
          <button type="submit" class="btn-admin-secondary btn-admin-sm">
            <?= icon('check') ?>
            <span>Record as Paid</span>
          </button>
        </form>
      <?php endif; ?>

      <!-- Cancel Action -->
      <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/cancel') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Cancel this order? This will restock all reserved inventory items.');">
          <?= csrf_field() ?>
          <button type="submit" class="btn-admin-ghost btn-admin-sm text-danger" style="color: #dc2626;">
            Cancel &amp; Restock
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Order Content 2-Column Grid -->
  <div class="admin-editor-layout">
    <!-- Left Column (Items & Financials) -->
    <div class="admin-editor-main">
      <div class="admin-card">
        <h2 class="card-section-title">Order Line Items (<?= count($items) ?>)</h2>
        <p class="card-section-desc">Products reserved and purchased by the customer.</p>

        <div class="admin-table-responsive" style="margin-bottom: var(--s-4);">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width: 60px;">Photo</th>
                <th>Item Details</th>
                <th>Unit Price</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Line Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr class="admin-table-row">
                  <td>
                    <div class="prod-thumb-box" style="width: 44px; height: 44px;">
                      <img src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>" alt="<?= ea($item['product_name']) ?>" loading="lazy">
                    </div>
                  </td>
                  <td>
                    <div class="font-semibold text-slate-900"><?= e($item['product_name']) ?></div>
                  </td>
                  <td>
                    <span class="text-sm text-slate-700"><?= money((int)$item['unit_price_cents']) ?></span>
                  </td>
                  <td style="text-align: center;">
                    <span class="admin-tag-badge"><?= (int)$item['quantity'] ?></span>
                  </td>
                  <td style="text-align: right;">
                    <span class="font-bold text-slate-900"><?= money((int)$item['unit_price_cents'] * (int)$item['quantity']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Financial Summary -->
        <div style="max-width: 300px; margin-left: auto; border-top: 1px solid var(--line); padding-top: var(--s-3); display: flex; flex-direction: column; gap: var(--s-2);">
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-2);">
            <span>Subtotal</span>
            <span class="font-semibold"><?= money((int)($order['subtotal_cents'] ?? 0)) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-2);">
            <span>Shipping</span>
            <span class="font-semibold"><?= money((int)($order['shipping_cents'] ?? 0)) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 700; color: var(--text-1); border-top: 1px solid var(--line-strong); padding-top: var(--s-2); margin-top: 2px;">
            <span>Total Amount</span>
            <span class="text-indigo"><?= money((int)($order['total_cents'] ?? 0)) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column (Customer, Shipping & Payment) -->
    <div class="admin-editor-side">
      <!-- Customer Card -->
      <div class="admin-card">
        <h3 class="card-section-title">Customer Information</h3>
        <div style="display: flex; flex-direction: column; gap: var(--s-2); font-size: 0.9rem;">
          <div>
            <div class="text-xs text-slate-400 font-semibold uppercase">Customer Name</div>
            <div class="font-semibold text-slate-900"><?= e($order['customer_name'] ?? '') ?></div>
          </div>
          <div>
            <div class="text-xs text-slate-400 font-semibold uppercase">Email Address</div>
            <a href="mailto:<?= ea($order['email'] ?? '') ?>" class="text-indigo hover:underline"><?= e($order['email'] ?? '') ?></a>
          </div>
          <div>
            <div class="text-xs text-slate-400 font-semibold uppercase">Phone Number</div>
            <div class="text-slate-700"><?= e($order['phone'] ?? '—') ?></div>
          </div>
        </div>
      </div>

      <!-- Shipping Destination Card -->
      <div class="admin-card">
        <h3 class="card-section-title">Shipping Destination</h3>
        <div style="font-size: 0.88rem; line-height: 1.55; color: var(--text-2);">
          <div class="font-semibold text-slate-900"><?= e($order['customer_name'] ?? '') ?></div>
          <div><?= e($shippingAddress['address_line_1'] ?? '') ?></div>
          <?php if (!empty($shippingAddress['address_line_2'])): ?>
            <div><?= e($shippingAddress['address_line_2']) ?></div>
          <?php endif; ?>
          <div><?= e($shippingAddress['city'] ?? '') ?>, <?= e($shippingAddress['state'] ?? '') ?> <?= e($shippingAddress['postal_code'] ?? '') ?></div>
          <div><?= e($shippingAddress['country'] ?? 'US') ?></div>
        </div>
      </div>

      <!-- Payment & Method Details -->
      <div class="admin-card">
        <h3 class="card-section-title">Payment Transaction</h3>
        <div style="display: flex; flex-direction: column; gap: var(--s-2); font-size: 0.9rem;">
          <div style="display: flex; justify-content: space-between;">
            <span class="text-slate-500">Method</span>
            <span class="font-semibold uppercase text-slate-800"><?= e($order['payment_method'] ?? 'COD') ?></span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span class="text-slate-500">Status</span>
            <span class="font-semibold <?= $paymentPaid ? 'text-emerald' : 'text-amber' ?>" style="text-transform: capitalize;">
              <?= e($paymentStatus) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Customer Notes (if present) -->
      <?php if (!empty($order['notes'])): ?>
        <div class="admin-card">
          <h3 class="card-section-title">Customer Instructions</h3>
          <p style="font-size: 0.88rem; color: var(--text-2); line-height: 1.5; margin: 0;">
            <?= nl2br(e($order['notes'])) ?>
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
