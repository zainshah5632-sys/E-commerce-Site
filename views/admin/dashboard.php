<?php
declare(strict_types=1);

$stats = $stats ?? [];
$recentOrders = $recentOrders ?? [];
$lowStockProducts = $lowStockProducts ?? [];
?>

<div class="admin-header">
  <div>
    <h1>Dashboard</h1>
    <p style="color: var(--text-3); margin-top: 4px;">Real-time metrics from your Supabase database.</p>
  </div>
  <a href="<?= url('/admin/products/new') ?>" class="btn btn--primary btn--sm">
    <?= icon('plus') ?>
    <span>New Product</span>
  </a>
</div>

<!-- 4 Stats Cards (admin_stats RPC) -->
<div class="stat-grid">
  <div class="stat-card glass">
    <span class="stat-card__label">Orders Today</span>
    <span class="stat-card__value"><?= (int)($stats['orders_today'] ?? 0) ?></span>
  </div>

  <div class="stat-card glass">
    <span class="stat-card__label">Pending Orders</span>
    <span class="stat-card__value" style="color: var(--warning);"><?= (int)($stats['pending_orders'] ?? 0) ?></span>
  </div>

  <div class="stat-card glass">
    <span class="stat-card__label">Revenue (30d)</span>
    <span class="stat-card__value" style="color: var(--success);"><?= money((int)($stats['revenue_30d_cents'] ?? 0)) ?></span>
  </div>

  <div class="stat-card glass">
    <span class="stat-card__label">Low Stock Alerts</span>
    <span class="stat-card__value" style="color: <?= ((int)($stats['low_stock'] ?? 0) > 0) ? 'var(--danger)' : 'var(--text-1)' ?>;">
      <?= (int)($stats['low_stock'] ?? 0) ?>
    </span>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--s-6);">
  <!-- Recent Orders -->
  <div class="glass--strong" style="padding: var(--s-5);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-4);">
      <h2 style="font-size: 1.15rem;">Recent Orders</h2>
      <a href="<?= url('/admin/orders') ?>" class="btn btn--ghost btn--sm">View All</a>
    </div>

    <?php if (empty($recentOrders)): ?>
      <p style="color: var(--text-3); font-size: 0.9rem;">No orders placed yet.</p>
    <?php else: ?>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Status</th>
              <th style="text-align: right;">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td>
                  <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" style="font-weight: 700; color: var(--brand-600);">
                    <?= e($ord['order_number']) ?>
                  </a>
                </td>
                <td><?= e($ord['customer_name']) ?></td>
                <td>
                  <span class="badge badge--neutral"><?= e($ord['status']) ?></span>
                </td>
                <td style="text-align: right; font-weight: 700;"><?= money((int)$ord['total_cents']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Low Stock Warning Box -->
  <div class="glass--strong" style="padding: var(--s-5);">
    <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4);">Low Stock Watch</h2>

    <?php if (empty($lowStockProducts)): ?>
      <p style="color: var(--success); font-size: 0.9rem;">All active products have adequate stock levels.</p>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: var(--s-3);">
        <?php foreach ($lowStockProducts as $prod): ?>
          <div class="glass" style="padding: var(--s-3); display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 600; font-size: 0.9rem;"><?= e($prod['name']) ?></div>
              <div style="font-size: 0.8rem; color: var(--text-3);">SKU: <?= e($prod['sku'] ?? 'N/A') ?></div>
            </div>
            <div>
              <span class="badge <?= (int)$prod['stock'] === 0 ? 'badge--danger' : 'badge--warning' ?>">
                <?= (int)$prod['stock'] ?> left
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
