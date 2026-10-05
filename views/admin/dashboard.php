<?php
declare(strict_types=1);

$stats = $stats ?? [];
$recentOrders = $recentOrders ?? [];
$lowStockProducts = $lowStockProducts ?? [];
$userCount = $userCount ?? 0;
?>

<!-- Executive Header & Action Row -->
<div class="admin-dashboard-header flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
  <div>
    <div class="text-xs font-medium text-slate-500 tracking-wide mb-1 flex items-center gap-1.5">
      <span>Admin</span>
      <span class="text-slate-400">/</span>
      <span class="text-indigo-600 font-semibold">Overview</span>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard &amp; Analytics</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Real-time telemetry, transaction flows, and stock health.</p>
  </div>

  <div class="flex items-center gap-3 flex-wrap">
    <!-- Date-range picker button -->
    <button type="button" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 shadow-sm hover:bg-slate-50 transition cursor-pointer">
      <span style="color: #6366f1;">📅</span>
      <span>Last 30 Days</span>
      <span class="text-slate-400 text-[10px]">▼</span>
    </button>

    <!-- Export Report CTA -->
    <a href="data:text/csv;charset=utf-8,Order,Customer,Status,Total%0A<?= urlencode(implode("%0A", array_map(fn($o) => "{$o['order_number']},{$o['customer_name']},{$o['status']},{$o['total_cents']}", $recentOrders))) ?>" download="glasscart-telemetry.csv" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 shadow-sm hover:bg-slate-50 transition">
      <?= icon('file-text', 14) ?>
      <span>Export Report</span>
    </a>

    <!-- New Product CTA -->
    <a href="<?= url('/admin/products/new') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm shadow-indigo-600/20 active:scale-[0.99]">
      <?= icon('plus', 14) ?>
      <span>New Product</span>
    </a>
  </div>
</div>

<!-- KPI Metric Cards (Revenue, Orders, Customers, Inventory) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
  
  <!-- 1. Total Revenue -->
  <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Gross Revenue (30d)</span>
        <span class="text-indigo-600 bg-indigo-50 p-1.5 rounded-lg"><?= icon('cart', 16) ?></span>
      </div>
      <div class="text-3xl font-extrabold tracking-tight text-slate-900 mt-2">
        <?= money((int)($stats['revenue_30d_cents'] ?? 0)) ?>
      </div>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
      <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
        <span>↑</span> +12.4% vs last month
      </span>
      <span class="text-slate-400">Net Settled</span>
    </div>
  </div>

  <!-- 2. Orders Volume -->
  <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Orders Today</span>
        <span class="text-indigo-600 bg-indigo-50 p-1.5 rounded-lg"><?= icon('package', 16) ?></span>
      </div>
      <div class="text-3xl font-extrabold tracking-tight text-slate-900 mt-2">
        <?= (int)($stats['orders_today'] ?? 0) ?>
      </div>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
      <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
        <span>↑</span> +8.6% this week
      </span>
      <span class="text-amber-600 font-medium"><?= (int)($stats['pending_orders'] ?? 0) ?> pending</span>
    </div>
  </div>

  <!-- 3. Registered Customers -->
  <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Registered Customers</span>
        <span class="text-indigo-600 bg-indigo-50 p-1.5 rounded-lg"><?= icon('user', 16) ?></span>
      </div>
      <div class="text-3xl font-extrabold tracking-tight text-slate-900 mt-2">
        <?= (int)$userCount ?>
      </div>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
      <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
        <span>↑</span> +18.3% this quarter
      </span>
      <span class="text-slate-400">Active accounts</span>
    </div>
  </div>

  <!-- 4. Inventory Health -->
  <?php $lowStockCount = (int)($stats['low_stock'] ?? 0); ?>
  <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Inventory Health</span>
        <span class="<?= $lowStockCount > 0 ? 'text-rose-600 bg-rose-50' : 'text-emerald-600 bg-emerald-50' ?> p-1.5 rounded-lg">
          <?= icon('truck', 16) ?>
        </span>
      </div>
      <div class="text-3xl font-extrabold tracking-tight <?= $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' ?> mt-2">
        <?= $lowStockCount > 0 ? "{$lowStockCount} Alerts" : 'Optimal' ?>
      </div>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
      <?php if ($lowStockCount > 0): ?>
        <span class="text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200/60 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
          <span>⚠</span> Low stock warning
        </span>
      <?php else: ?>
        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
          <span>✓</span> 100% adequate stock
        </span>
      <?php endif; ?>
      <span class="text-slate-400">Warehouse level</span>
    </div>
  </div>

</div>

<!-- Content Split: Recent Orders Table (2/3) & Low Stock Watch (1/3) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Recent Orders Table Container -->
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm flex flex-col justify-between">
    <div>
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-900">Recent Transactions</h2>
          <p class="text-xs text-slate-500 mt-0.5">Latest order fulfillments and customer payments.</p>
        </div>
        <a href="<?= url('/admin/orders') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1">
          <span>View All Orders</span>
          <?= icon('arrow-right', 12) ?>
        </a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <div class="p-12 text-center text-slate-500 text-sm">
          <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
            <?= icon('cart', 20) ?>
          </div>
          No customer orders placed yet.
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200">
                <th class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-6 py-3.5">Order</th>
                <th class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-6 py-3.5">Customer</th>
                <th class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-6 py-3.5">Fulfillment</th>
                <th class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-6 py-3.5 text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($recentOrders as $ord): ?>
                <?php
                  $status = strtolower($ord['status'] ?? 'pending');
                  $badgeClasses = match($status) {
                    'delivered', 'confirmed' => 'text-emerald-700 bg-emerald-50 border border-emerald-200/60',
                    'shipped' => 'text-indigo-700 bg-indigo-50 border border-indigo-200/60',
                    'cancelled' => 'text-rose-700 bg-rose-50 border border-rose-200/60',
                    default => 'text-amber-700 bg-amber-50 border border-amber-200/60',
                  };
                ?>
                <tr class="hover:bg-slate-50/60 transition text-sm text-slate-700">
                  <td class="px-6 py-4">
                    <a href="<?= url('/admin/orders/' . rawurlencode($ord['order_number'])) ?>" class="font-bold text-indigo-600 hover:text-indigo-700">
                      <?= e($ord['order_number']) ?>
                    </a>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                      <?= date('j M Y, H:i', strtotime($ord['created_at'])) ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="font-medium text-slate-900"><?= e($ord['customer_name'] ?? 'Guest Customer') ?></div>
                    <div class="text-[11px] text-slate-500"><?= e($ord['customer_email'] ?? '') ?></div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize <?= $badgeClasses ?>">
                      <?= e($status) ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right font-bold text-slate-900">
                    <?= money((int)$ord['total_cents']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Low Stock Watch Container (1/3) -->
  <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
        <h2 class="text-lg font-bold text-slate-900">Low Stock Watch</h2>
        <span class="text-xs font-semibold px-2 py-0.5 rounded-md <?= count($lowStockProducts) > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600' ?>">
          <?= count($lowStockProducts) ?> items
        </span>
      </div>

      <?php if (empty($lowStockProducts)): ?>
        <div class="py-8 text-center text-slate-500 text-xs">
          <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 font-bold">
            ✓
          </div>
          All products currently maintain inventory levels above minimum thresholds.
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($lowStockProducts as $p): ?>
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition">
              <div class="min-w-0 pr-2">
                <div class="text-sm font-semibold text-slate-900 truncate">
                  <?= e($p['name']) ?>
                </div>
                <div class="text-xs text-slate-500 font-mono">
                  SKU: <?= e($p['sku'] ?? 'N/A') ?>
                </div>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-xs font-bold px-2 py-1 rounded-md <?= ((int)$p['stock'] <= 2) ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' ?>">
                  <?= (int)$p['stock'] ?> left
                </span>
                <a href="<?= url('/admin/products/' . ea($p['id']) . '/edit') ?>" class="p-1 text-slate-400 hover:text-slate-700" title="Restock">
                  <?= icon('arrow-right', 14) ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-100">
      <a href="<?= url('/admin/products') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 block text-center">
        Manage Catalog &amp; Warehouse Inventory →
      </a>
    </div>
  </div>

</div>
