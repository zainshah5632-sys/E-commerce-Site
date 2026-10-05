<?php
declare(strict_types=1);

$orders = $orders ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9);">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--s-5);">
    <div>
      <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-2);">
        <a href="<?= url('/account') ?>">Account</a>
        <span>/</span>
        <span style="color: var(--text-1);">Orders</span>
      </div>
      <h1>Order History</h1>
    </div>
    <a href="<?= url('/account') ?>" class="btn btn--ghost btn--sm">Profile Settings</a>
  </div>

  <?php if (empty($orders)): ?>
    <div class="glass" style="text-align: center; padding: var(--s-8) var(--s-4);">
      <div style="color: var(--text-3); margin-bottom: var(--s-3);"><?= icon('package') ?></div>
      <h2>No orders yet</h2>
      <p style="margin-top: var(--s-2); margin-bottom: var(--s-5);">When you place orders, they will appear here with real-time tracking.</p>
      <a href="<?= url('/shop') ?>" class="btn btn--primary btn--sm">Start Shopping</a>
    </div>
  <?php else: ?>
    <div class="glass--strong data-table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Date</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Total</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $ord): ?>
            <?php
              $statusBadge = match($ord['status'] ?? 'pending') {
                  'confirmed' => 'badge--brand',
                  'shipped' => 'badge--neutral',
                  'delivered' => 'badge--success',
                  'cancelled' => 'badge--danger',
                  default => 'badge--warning',
              };
            ?>
            <tr>
              <td>
                <a href="<?= url('/account/orders/' . rawurlencode($ord['order_number'])) ?>" style="font-weight: 700; color: var(--brand-600);">
                  <?= e($ord['order_number']) ?>
                </a>
              </td>
              <td><?= date('j M Y, H:i', strtotime($ord['created_at'])) ?></td>
              <td>
                <span class="badge <?= $statusBadge ?>"><?= e($ord['status']) ?></span>
              </td>
              <td style="text-transform: capitalize;"><?= e($ord['payment_status'] ?? 'pending') ?></td>
              <td style="font-weight: 700;"><?= money((int)$ord['total_cents']) ?></td>
              <td style="text-align: right;">
                <a href="<?= url('/account/orders/' . rawurlencode($ord['order_number'])) ?>" class="btn btn--ghost btn--sm">
                  <span>View Details</span>
                  <?= icon('arrow-right') ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
  <?php endif; ?>
</div>
