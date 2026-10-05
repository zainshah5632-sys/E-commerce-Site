<?php
declare(strict_types=1);

$orders = $orders ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>

<div class="container orders-shell max-w-4xl mx-auto">
  <!-- Header Row: Micro-nav, Title & Profile Settings aligned on same horizontal line -->
  <div class="orders-header-row flex items-center justify-between mb-8">
    <div>
      <nav class="orders-breadcrumb text-xs font-medium text-slate-500 tracking-wide mb-2" aria-label="Breadcrumb">
        <a href="<?= url('/account') ?>" class="orders-breadcrumb__link">Account</a>
        <span class="orders-breadcrumb__sep">/</span>
        <span class="orders-breadcrumb__current">Orders</span>
      </nav>
      <h1 class="orders-title text-3xl font-extrabold tracking-tight text-slate-900">Order History</h1>
    </div>
    <a href="<?= url('/account') ?>" class="orders-settings-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white/80 border border-slate-200 hover:bg-slate-50 transition shadow-sm">
      <?= icon('user', 14) ?>
      <span>Profile Settings</span>
    </a>
  </div>

  <?php if (empty($orders)): ?>
    <!-- Centered Empty State Card Layout -->
    <div class="orders-empty-card max-w-4xl mx-auto bg-white/80 backdrop-blur-md rounded-2xl border border-slate-200/80 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6 mt-6">
      <div class="orders-empty__badge w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 mb-4">
        <?= icon('package', 24) ?>
      </div>
      <h2 class="orders-empty__title text-xl font-bold text-slate-900 mb-2">No orders yet</h2>
      <p class="orders-empty__desc text-sm text-slate-500 max-w-md leading-relaxed mb-6">When you place orders, they will appear here with real-time tracking.</p>
      <a href="<?= url('/shop') ?>" class="orders-empty__cta inline-flex items-center justify-center px-6 py-2.5 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-indigo-600 to-fuchsia-600 hover:opacity-95 shadow-md shadow-indigo-500/20 transition active:scale-95">
        <span>Start Shopping</span>
      </a>
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
