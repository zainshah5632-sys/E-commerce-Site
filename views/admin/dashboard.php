<?php
declare(strict_types=1);

$stats = $stats ?? [];
$recentOrders = $recentOrders ?? [];
$lowStockProducts = $lowStockProducts ?? [];
$userCount = $userCount ?? 0;
$lowStockCount = (int)($stats['low_stock'] ?? 0);
?>

<div class="dashboard-page-container">
  
  <!-- Executive Page Header -->
  <header class="dashboard-header">
    <div class="dashboard-header__meta">
      <nav class="dashboard-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= url('/admin') ?>">Admin</a>
        <span class="dashboard-breadcrumbs__sep">/</span>
        <span class="dashboard-breadcrumbs__current">Overview</span>
      </nav>
      <h1 class="dashboard-title">Dashboard &amp; Analytics</h1>
      <p class="dashboard-subtitle">
        Real-time telemetry, transaction flows, customer acquisition, and warehouse stock health.
      </p>
    </div>

    <div class="dashboard-header__actions">
      <!-- Date Range Filter Pill -->
      <div class="dashboard-date-filter" id="dashboard-date-picker">
        <span class="dashboard-date-filter__icon" aria-hidden="true">
          <?= icon('calendar', 15) ?>
        </span>
        <span class="dashboard-date-filter__label">Last 30 Days</span>
        <span class="dashboard-date-filter__arrow" aria-hidden="true">
          <?= icon('chevron-down', 13) ?>
        </span>
      </div>

      <!-- Export CSV Report -->
      <a
        href="data:text/csv;charset=utf-8,Order,Customer,Status,Total%0A<?= urlencode(implode("%0A", array_map(fn($o) => "{$o['order_number']},{$o['customer_name']},{$o['status']},{$o['total_cents']}", $recentOrders))) ?>"
        download="glasscart-telemetry-report.csv"
        class="dashboard-btn-secondary"
        title="Download CSV transaction export"
      >
        <?= icon('download', 15) ?>
        <span>Export Report</span>
      </a>

      <!-- New Product CTA -->
      <a
        href="<?= url('/admin/products/new') ?>"
        class="dashboard-btn-primary"
      >
        <?= icon('plus', 16) ?>
        <span>New Product</span>
      </a>
    </div>
  </header>

  <!-- 4 KPI Metric Cards -->
  <section class="dashboard-kpi-grid" aria-label="Key Performance Indicators">
    
    <!-- 1. Gross Revenue -->
    <div class="dashboard-kpi-card">
      <div class="dashboard-kpi-card__top">
        <span class="dashboard-kpi-card__label">Gross Revenue (30d)</span>
        <div class="dashboard-kpi-card__icon dashboard-kpi-card__icon--indigo" aria-hidden="true">
          <?= icon('cart', 18) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__middle">
        <div class="dashboard-kpi-card__value">
          <?= money((int)($stats['revenue_30d_cents'] ?? 0)) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__bottom">
        <span class="dashboard-kpi-trend dashboard-kpi-trend--up">
          <?= icon('trending-up', 13) ?>
          <span>+12.4% vs last period</span>
        </span>
        <span class="dashboard-kpi-card__context">Net settled</span>
      </div>
    </div>

    <!-- 2. Orders Volume -->
    <div class="dashboard-kpi-card">
      <div class="dashboard-kpi-card__top">
        <span class="dashboard-kpi-card__label">Orders Today</span>
        <div class="dashboard-kpi-card__icon dashboard-kpi-card__icon--violet" aria-hidden="true">
          <?= icon('package', 18) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__middle">
        <div class="dashboard-kpi-card__value">
          <?= (int)($stats['orders_today'] ?? 0) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__bottom">
        <span class="dashboard-kpi-trend dashboard-kpi-trend--up">
          <?= icon('trending-up', 13) ?>
          <span>+8.6% this week</span>
        </span>
        <span class="dashboard-kpi-card__context">
          <strong class="text-amber-600 font-semibold"><?= (int)($stats['pending_orders'] ?? 0) ?></strong> pending
        </span>
      </div>
    </div>

    <!-- 3. Registered Customers -->
    <div class="dashboard-kpi-card">
      <div class="dashboard-kpi-card__top">
        <span class="dashboard-kpi-card__label">Registered Customers</span>
        <div class="dashboard-kpi-card__icon dashboard-kpi-card__icon--blue" aria-hidden="true">
          <?= icon('user', 18) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__middle">
        <div class="dashboard-kpi-card__value">
          <?= (int)$userCount ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__bottom">
        <span class="dashboard-kpi-trend dashboard-kpi-trend--up">
          <?= icon('trending-up', 13) ?>
          <span>+18.3% this quarter</span>
        </span>
        <span class="dashboard-kpi-card__context">Active accounts</span>
      </div>
    </div>

    <!-- 4. Inventory Health -->
    <div class="dashboard-kpi-card">
      <div class="dashboard-kpi-card__top">
        <span class="dashboard-kpi-card__label">Inventory Health</span>
        <div class="dashboard-kpi-card__icon <?= $lowStockCount > 0 ? 'dashboard-kpi-card__icon--rose' : 'dashboard-kpi-card__icon--emerald' ?>" aria-hidden="true">
          <?= $lowStockCount > 0 ? icon('alert-triangle', 18) : icon('truck', 18) ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__middle">
        <div class="dashboard-kpi-card__value <?= $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' ?>">
          <?= $lowStockCount > 0 ? "{$lowStockCount} Items Low" : 'Optimal' ?>
        </div>
      </div>
      <div class="dashboard-kpi-card__bottom">
        <?php if ($lowStockCount > 0): ?>
          <span class="dashboard-kpi-trend dashboard-kpi-trend--down">
            <?= icon('trending-down', 13) ?>
            <span>Restock required</span>
          </span>
          <span class="dashboard-kpi-card__context text-rose-600 font-medium">Critical tier</span>
        <?php else: ?>
          <span class="dashboard-kpi-trend dashboard-kpi-trend--up">
            <?= icon('check', 13) ?>
            <span>100% Stocked</span>
          </span>
          <span class="dashboard-kpi-card__context">Healthy levels</span>
        <?php endif; ?>
      </div>
    </div>

  </section>

  <!-- Content Split: Recent Transactions (2/3) + Low Stock Watch (1/3) -->
  <div class="dashboard-main-grid">

    <!-- Recent Transactions Table Card (2/3) -->
    <section class="dashboard-card dashboard-orders-card" aria-labelledby="heading-recent-orders">
      <div class="dashboard-card__header">
        <div>
          <h2 id="heading-recent-orders" class="dashboard-card__title">Recent Transactions</h2>
          <p class="dashboard-card__desc">Latest customer orders, fulfillment workflows, and payment captures.</p>
        </div>
        <a href="<?= url('/admin/orders') ?>" class="dashboard-card__link">
          <span>View All Orders</span>
          <?= icon('arrow-right', 13) ?>
        </a>
      </div>

      <div class="dashboard-card__body p-0">
        <?php if (empty($recentOrders)): ?>
          <div class="dashboard-empty-state">
            <div class="dashboard-empty-icon" aria-hidden="true">
              <?= icon('cart', 24) ?>
            </div>
            <h3 class="dashboard-empty-title">No transactions recorded yet</h3>
            <p class="dashboard-empty-desc">When buyers complete checkout, order invoices and delivery statuses will populate here in real-time.</p>
            <a href="<?= url('/admin/products') ?>" class="dashboard-btn-secondary dashboard-btn-sm mt-3">
              Explore Products Catalog
            </a>
          </div>
        <?php else: ?>
          <div class="dashboard-table-wrap">
            <table class="dashboard-table">
              <thead>
                <tr>
                  <th scope="col" class="th-order">Order Reference</th>
                  <th scope="col" class="th-customer">Customer</th>
                  <th scope="col" class="th-status">Fulfillment Status</th>
                  <th scope="col" class="th-amount text-right">Total Amount</th>
                  <th scope="col" class="th-action text-right"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $ord): ?>
                  <?php
                    $status = strtolower($ord['status'] ?? 'pending');
                    $orderNum = (string)$ord['order_number'];
                    $custName = (string)($ord['customer_name'] ?? 'Guest Customer');
                    $custEmail = (string)($ord['customer_email'] ?? '');
                    $totalCents = (int)($ord['total_cents'] ?? 0);
                    $createdAt = !empty($ord['created_at']) ? strtotime($ord['created_at']) : time();
                  ?>
                  <tr class="dashboard-table-row">
                    <!-- Order Reference & Date -->
                    <td class="td-order">
                      <div class="dashboard-order-cell">
                        <a href="<?= url('/admin/orders/' . rawurlencode($orderNum)) ?>" class="dashboard-order-link font-mono">
                          #<?= e($orderNum) ?>
                        </a>
                        <span class="dashboard-order-date">
                          <?= date('M j, Y', $createdAt) ?> &bull; <?= date('H:i', $createdAt) ?>
                        </span>
                      </div>
                    </td>

                    <!-- Customer -->
                    <td class="td-customer">
                      <div class="dashboard-customer-cell">
                        <span class="dashboard-customer-name"><?= e($custName) ?></span>
                        <span class="dashboard-customer-email"><?= e($custEmail !== '' ? $custEmail : 'No email provided') ?></span>
                      </div>
                    </td>

                    <!-- Fulfillment Status -->
                    <td class="td-status">
                      <?php if ($status === 'delivered' || $status === 'confirmed'): ?>
                        <span class="dashboard-badge dashboard-badge--delivered">
                          <span class="dashboard-badge__dot"></span>
                          <span>Delivered</span>
                        </span>
                      <?php elseif ($status === 'shipped'): ?>
                        <span class="dashboard-badge dashboard-badge--shipped">
                          <span class="dashboard-badge__dot"></span>
                          <span>In Transit</span>
                        </span>
                      <?php elseif ($status === 'cancelled'): ?>
                        <span class="dashboard-badge dashboard-badge--cancelled">
                          <span class="dashboard-badge__dot"></span>
                          <span>Cancelled</span>
                        </span>
                      <?php else: ?>
                        <span class="dashboard-badge dashboard-badge--pending">
                          <span class="dashboard-badge__dot"></span>
                          <span>Pending Payment</span>
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Total Amount -->
                    <td class="td-amount text-right">
                      <span class="dashboard-order-total font-mono">
                        <?= money($totalCents) ?>
                      </span>
                    </td>

                    <!-- View Action -->
                    <td class="td-action text-right">
                      <a
                        href="<?= url('/admin/orders/' . rawurlencode($orderNum)) ?>"
                        class="dashboard-row-action"
                        title="View order details"
                        aria-label="View order <?= ea($orderNum) ?>"
                      >
                        <?= icon('external-link', 14) ?>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Low Stock Watch Card (1/3) -->
    <aside class="dashboard-card dashboard-stock-card" aria-labelledby="heading-stock-watch">
      <div class="dashboard-card__header">
        <div>
          <h2 id="heading-stock-watch" class="dashboard-card__title">Low Stock Watch</h2>
          <p class="dashboard-card__desc">Inventory items approaching or below alert threshold (5 units).</p>
        </div>
        <span class="dashboard-stock-count-pill <?= count($lowStockProducts) > 0 ? 'dashboard-stock-count-pill--alert' : 'dashboard-stock-count-pill--ok' ?>">
          <?= count($lowStockProducts) ?> Alert<?= count($lowStockProducts) === 1 ? '' : 's' ?>
        </span>
      </div>

      <div class="dashboard-card__body">
        <?php if (empty($lowStockProducts)): ?>
          <div class="dashboard-empty-state py-6">
            <div class="dashboard-empty-icon dashboard-empty-icon--emerald" aria-hidden="true">
              <?= icon('check', 20) ?>
            </div>
            <h3 class="dashboard-empty-title">Stock Levels Optimal</h3>
            <p class="dashboard-empty-desc">All products across the warehouse currently maintain sufficient inventory levels above minimum thresholds.</p>
          </div>
        <?php else: ?>
          <div class="dashboard-stock-list">
            <?php foreach ($lowStockProducts as $p): ?>
              <?php
                $stock = (int)($p['stock'] ?? 0);
                $isCritical = $stock <= 2;
                $pct = min(100, max(10, (int)round(($stock / 5) * 100)));
              ?>
              <div class="dashboard-stock-item">
                <div class="dashboard-stock-item__meta">
                  <div class="dashboard-stock-item__header">
                    <span class="dashboard-stock-item__name" title="<?= ea($p['name']) ?>">
                      <?= e($p['name']) ?>
                    </span>
                    <span class="dashboard-stock-badge <?= $isCritical ? 'dashboard-stock-badge--critical' : 'dashboard-stock-badge--warning' ?>">
                      <?= $stock ?> left
                    </span>
                  </div>
                  <div class="dashboard-stock-item__sub">
                    <span class="dashboard-stock-sku font-mono">SKU: <?= e($p['sku'] ?? 'N/A') ?></span>
                    <a
                      href="<?= url('/admin/products/' . ea($p['id']) . '/edit') ?>"
                      class="dashboard-stock-restock-link"
                      title="Adjust inventory in product editor"
                    >
                      <span>Restock</span>
                      <?= icon('arrow-right', 11) ?>
                    </a>
                  </div>
                  <!-- Mini Progress Bar Indicator -->
                  <div class="dashboard-stock-progress" aria-hidden="true">
                    <div
                      class="dashboard-stock-progress__bar <?= $isCritical ? 'dashboard-stock-progress__bar--critical' : 'dashboard-stock-progress__bar--warning' ?>"
                      style="width: <?= $pct ?>%;"
                    ></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="dashboard-card__footer">
        <a href="<?= url('/admin/products') ?>" class="dashboard-card__footer-link">
          <span>Manage Full Catalog &amp; Warehouse Inventory</span>
          <?= icon('arrow-right', 13) ?>
        </a>
      </div>
    </aside>

  </div>

</div>
