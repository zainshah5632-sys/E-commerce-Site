<?php
declare(strict_types=1);

$orders = $orders ?? [];
$filters = $filters ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>

<div class="admin-header">
  <div>
    <h1>Orders (<?= $total ?>)</h1>
    <p style="color: var(--text-3); margin-top: 4px;">Track fulfillment pipelines and customer payments.</p>
  </div>
</div>

<!-- Filters Bar -->
<div class="glass" style="padding: var(--s-4); margin-bottom: var(--s-5);">
  <form action="<?= url('/admin/orders') ?>" method="GET" style="display: flex; gap: var(--s-3); flex-wrap: wrap; align-items: center;">
    <div style="flex-grow: 1; min-width: 220px;">
      <input type="search" name="q" value="<?= ea($filters['q'] ?? '') ?>" class="form-input" placeholder="Search by order #, email, customer..." style="padding: 8px 12px;">
    </div>

    <div>
      <select name="status" class="form-select" style="padding: 8px 12px; width: auto;">
        <option value="">All Fulfillment Statuses</option>
        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="shipped" <?= ($filters['status'] ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
        <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
        <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <button type="submit" class="btn btn--ghost btn--sm">Search Orders</button>
  </form>
</div>

<!-- Orders Table -->
<div class="glass--strong data-table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Order Number</th>
        <th>Customer</th>
        <th>Date</th>
        <th>Fulfillment</th>
        <th>Payment</th>
        <th>Total</th>
        <th style="text-align: right;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr>
          <td colspan="7" style="text-align: center; color: var(--text-3); padding: var(--s-6);">
            No orders match the filter criteria.
          </td>
        </tr>
      <?php else: ?>
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
              <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" style="font-weight: 700; color: var(--brand-600);">
                <?= e($ord['order_number']) ?>
              </a>
            </td>
            <td>
              <div style="font-weight: 600;"><?= e($ord['customer_name']) ?></div>
              <div style="font-size: 0.8rem; color: var(--text-3);"><?= e($ord['email']) ?></div>
            </td>
            <td><?= date('j M Y, H:i', strtotime($ord['created_at'])) ?></td>
            <td>
              <span class="badge <?= $statusBadge ?>"><?= e($ord['status']) ?></span>
            </td>
            <td>
              <span style="text-transform: capitalize; font-size: 0.88rem;"><?= e($ord['payment_status'] ?? 'pending') ?></span>
              <span style="font-size: 0.75rem; color: var(--text-3); text-transform: uppercase;">(<?= e($ord['payment_method']) ?>)</span>
            </td>
            <td style="font-weight: 700;"><?= money((int)$ord['total_cents']) ?></td>
            <td style="text-align: right;">
              <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" class="btn btn--ghost btn--sm">
                <span>Manage</span>
                <?= icon('arrow-right') ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
