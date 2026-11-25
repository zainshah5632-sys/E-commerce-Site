<?php
declare(strict_types=1);

$orders = $orders ?? [];
$filters = $filters ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;

$pendingCount = 0;
$deliveredCount = 0;
$totalRevenueCents = 0;
foreach ($orders as $o) {
    if (($o['status'] ?? '') === 'pending') $pendingCount++;
    if (($o['status'] ?? '') === 'delivered') $deliveredCount++;
    $totalRevenueCents += (int)($o['total_cents'] ?? 0);
}
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <span class="current">Orders &amp; Fulfillment</span>
  </div>

  <!-- Header -->
  <div class="admin-header-row">
    <div>
      <h1 class="admin-title">Orders &amp; Fulfillment</h1>
      <p class="admin-subtitle">Track customer orders, manage processing pipelines, and update fulfillment milestones.</p>
    </div>
  </div>

  <!-- Metrics Strip -->
  <div class="admin-stats-strip">
    <div class="stat-chip">
      <span class="label">Total Orders</span>
      <span class="val"><?= (int)$total ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Awaiting Fulfillment</span>
      <span class="val <?= $pendingCount > 0 ? 'text-amber' : '' ?>"><?= $pendingCount ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Delivered (Page)</span>
      <span class="val text-emerald"><?= $deliveredCount ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Page Volume</span>
      <span class="val text-indigo"><?= money($totalRevenueCents) ?></span>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="admin-card filter-card">
    <form action="<?= url('/admin/orders') ?>" method="GET" class="filter-form">
      <div class="filter-input-wrap">
        <span class="search-icon"><?= icon('search') ?></span>
        <input
          type="search"
          name="q"
          value="<?= ea($filters['q'] ?? '') ?>"
          placeholder="Search by order #, customer name, or email..."
          class="admin-input admin-input--search"
        >
      </div>

      <div class="filter-select-wrap">
        <select name="status" class="admin-select" onchange="this.form.submit()">
          <option value="">All Fulfillment</option>
          <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
          <option value="shipped" <?= ($filters['status'] ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="filter-select-wrap">
        <select name="payment_status" class="admin-select" onchange="this.form.submit()">
          <option value="">All Payment Statuses</option>
          <option value="pending" <?= ($filters['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="paid" <?= ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="rejected" <?= ($filters['payment_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
          <option value="failed" <?= ($filters['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
          <option value="refunded" <?= ($filters['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
      </div>

      <div class="filter-select-wrap">
        <select name="payment_method" class="admin-select" onchange="this.form.submit()">
          <option value="">All Payment Methods</option>
          <option value="cod" <?= ($filters['payment_method'] ?? '') === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
          <option value="easypaisa" <?= ($filters['payment_method'] ?? '') === 'easypaisa' ? 'selected' : '' ?>>Easypaisa</option>
          <option value="jazzcash" <?= ($filters['payment_method'] ?? '') === 'jazzcash' ? 'selected' : '' ?>>JazzCash</option>
          <option value="bank_transfer" <?= ($filters['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
          <option value="card" <?= ($filters['payment_method'] ?? '') === 'card' ? 'selected' : '' ?>>Debit / Credit Card</option>
        </select>
      </div>

      <button type="submit" class="btn-admin-secondary">
        <?= icon('filter') ?>
        <span>Filter</span>
      </button>

      <?php if (!empty($filters['q']) || !empty($filters['status']) || !empty($filters['payment_status']) || !empty($filters['payment_method'])): ?>
        <a href="<?= url('/admin/orders') ?>" class="btn-admin-ghost">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Orders Table Card -->
  <div class="admin-card table-card">
    <div class="admin-table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer &amp; Contact</th>
            <th>Placed Date</th>
            <th>Fulfillment Status</th>
            <th>Payment</th>
            <th>Total Amount</th>
            <th style="text-align: right; width: 100px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="7" class="empty-table-cell">
                <div class="empty-state-wrap">
                  <div class="empty-state-icon"><?= icon('cart') ?></div>
                  <h3>No orders found</h3>
                  <p>No transactions match the selected filter criteria.</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $ord): ?>
              <?php
                $statusClass = match($ord['status'] ?? 'pending') {
                    'confirmed' => 'status-pill--confirmed',
                    'shipped'   => 'status-pill--shipped',
                    'delivered' => 'status-pill--delivered',
                    'cancelled' => 'status-pill--cancelled',
                    default     => 'status-pill--pending',
                };
                $pStatus = $ord['payment_status'] ?? 'pending';
                $pColor = match($pStatus) {
                    'paid'     => 'text-emerald',
                    'rejected', 'failed' => 'text-danger',
                    'refunded' => 'text-indigo',
                    default    => 'text-amber',
                };
                $pMethodLabel = match($ord['payment_method'] ?? 'cod') {
                    'easypaisa'     => 'Easypaisa',
                    'jazzcash'      => 'JazzCash',
                    'bank_transfer' => 'Bank Transfer',
                    'card'          => 'Card',
                    default         => 'COD',
                };
              ?>
              <tr class="admin-table-row">
                <td>
                  <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" class="order-num-link font-semibold">
                    <?= e($ord['order_number']) ?>
                  </a>
                </td>
                <td>
                  <div>
                    <div class="font-semibold text-slate-900"><?= e($ord['customer_name']) ?></div>
                    <div class="text-xs text-slate-500"><?= e($ord['email']) ?></div>
                  </div>
                </td>
                <td>
                  <span class="text-sm text-slate-600">
                    <?= date('M j, Y &bull; H:i', strtotime($ord['created_at'])) ?>
                  </span>
                </td>
                <td>
                  <span class="order-status-pill <?= $statusClass ?>">
                    <span class="pill-dot"></span>
                    <span><?= ucfirst(e($ord['status'])) ?></span>
                  </span>
                </td>
                <td>
                  <div style="display: flex; flex-direction: column; gap: 2px;">
                    <span class="text-xs font-semibold <?= $pColor ?>" style="text-transform: capitalize;">
                      <?= e($pStatus) ?>
                    </span>
                    <span class="text-xs text-slate-500 font-medium">
                      <?= e($pMethodLabel) ?>
                    </span>
                  </div>
                </td>
                <td>
                  <span class="font-bold text-slate-900"><?= money((int)$ord['total_cents']) ?></span>
                </td>
                <td style="text-align: right;">
                  <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" class="btn-admin-ghost btn-admin-sm">
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

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div class="admin-table-footer">
        <?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
