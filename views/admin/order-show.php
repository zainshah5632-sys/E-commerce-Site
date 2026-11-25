<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];
$status = $order['status'] ?? 'pending';
$paymentStatus = $order['payment_status'] ?? 'pending';
$method = $order['payment_method'] ?? 'cod';
$transactionId = $order['transaction_id'] ?? null;
$paymentReceiptUrl = $order['payment_receipt_url'] ?? null;
$paymentDetails = is_array($order['payment_details'] ?? null)
    ? $order['payment_details']
    : (json_decode($order['payment_details'] ?? '[]', true) ?: []);

$statusClass = match($status) {
    'confirmed' => 'status-pill--confirmed',
    'shipped'   => 'status-pill--shipped',
    'delivered' => 'status-pill--delivered',
    'cancelled' => 'status-pill--cancelled',
    default     => 'status-pill--pending',
};
$paymentPaid = ($paymentStatus === 'paid');

$methodLabel = match($method) {
    'easypaisa'     => 'Easypaisa Mobile Wallet',
    'jazzcash'      => 'JazzCash Mobile Account',
    'bank_transfer' => 'Bank Transfer / IBAN',
    'card'          => 'Credit / Debit Card (Stripe)',
    default         => 'Cash on Delivery (COD)',
};

$paymentStatusBadgeClass = match($paymentStatus) {
    'paid'     => 'text-emerald',
    'rejected', 'failed' => 'text-danger',
    'refunded' => 'text-indigo',
    default    => 'text-amber',
};
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
      <span class="admin-tag-badge <?= $paymentStatusBadgeClass ?>" style="text-transform: capitalize; font-weight: 700;">
        <?= e($paymentStatus) ?>
      </span>

      <?php if (!$paymentPaid): ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="payment_status" value="paid">
          <input type="hidden" name="admin_notes" value="Verified by administrator">
          <button type="submit" class="btn-admin-primary btn-admin-sm" style="background: #059669; border-color: #059669;">
            <?= icon('check') ?>
            <span>Verify &amp; Mark Paid</span>
          </button>
        </form>

        <?php if ($paymentStatus !== 'rejected'): ?>
          <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Reject this payment verification? Customer will see this in their order status.');">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_status" value="rejected">
            <input type="hidden" name="admin_notes" value="Payment rejected during administrative audit">
            <button type="submit" class="btn-admin-ghost btn-admin-sm text-danger" style="color: #dc2626; border: 1px solid rgba(220,38,38,0.3);">
              <span>Reject</span>
            </button>
          </form>
        <?php endif; ?>
      <?php else: ?>
        <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Mark this paid order as Refunded?');">
          <?= csrf_field() ?>
          <input type="hidden" name="payment_status" value="refunded">
          <input type="hidden" name="admin_notes" value="Refund issued by administrator">
          <button type="submit" class="btn-admin-ghost btn-admin-sm" style="color: #6366f1; border: 1px solid rgba(99,102,241,0.3);">
            <span>Issue Refund</span>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-3);">
          <h3 class="card-section-title" style="margin: 0;">Payment &amp; Settlement</h3>
          <span class="admin-tag-badge <?= $paymentStatusBadgeClass ?>" style="text-transform: capitalize; font-weight: 700;">
            <?= e($paymentStatus) ?>
          </span>
        </div>

        <div style="display: flex; flex-direction: column; gap: var(--s-3); font-size: 0.9rem;">
          <!-- Method Rail -->
          <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--line); border-radius: var(--r-md); padding: var(--s-3);">
            <div class="text-xs text-slate-400 font-semibold uppercase" style="margin-bottom: 4px;">Payment Method</div>
            <div style="display: flex; align-items: center; gap: var(--s-2);">
              <span class="font-semibold text-slate-900"><?= e($methodLabel) ?></span>
              <span class="text-xs font-mono text-slate-500">(<?= strtoupper(e($method)) ?>)</span>
            </div>
          </div>

          <!-- Transaction ID (TID) -->
          <?php if (!empty($transactionId)): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--line); border-radius: var(--r-md); padding: var(--s-3);">
              <div class="text-xs text-slate-400 font-semibold uppercase" style="margin-bottom: 4px;">Customer Transaction ID (TID)</div>
              <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--s-2);">
                <code style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: var(--text-1); word-break: break-all;"><?= e($transactionId) ?></code>
                <button type="button" class="btn-admin-ghost btn-admin-sm" style="padding: 2px 8px; font-size: 0.75rem;" onclick="navigator.clipboard.writeText('<?= ea($transactionId) ?>'); alert('Transaction ID copied to clipboard!');">
                  Copy
                </button>
              </div>
            </div>
          <?php endif; ?>

          <!-- Receipt Proof -->
          <?php if (!empty($paymentReceiptUrl)): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--line); border-radius: var(--r-md); padding: var(--s-3);">
              <div class="text-xs text-slate-400 font-semibold uppercase" style="margin-bottom: 6px;">Customer Uploaded Receipt</div>
              <?php
                $isPdf = str_ends_with(strtolower(parse_url($paymentReceiptUrl, PHP_URL_PATH) ?? ''), '.pdf');
              ?>
              <?php if (!$isPdf): ?>
                <div style="margin-bottom: var(--s-2); max-height: 220px; overflow: hidden; border-radius: var(--r-sm); border: 1px solid var(--line);">
                  <a href="<?= ea($paymentReceiptUrl) ?>" target="_blank" rel="noopener">
                    <img src="<?= ea($paymentReceiptUrl) ?>" alt="Payment Receipt" style="width: 100%; height: auto; display: block; object-fit: contain; max-height: 200px; background: rgba(0,0,0,0.03);">
                  </a>
                </div>
              <?php endif; ?>
              <a href="<?= ea($paymentReceiptUrl) ?>" target="_blank" rel="noopener" class="btn-admin-secondary btn-admin-sm" style="width: 100%; justify-content: center;">
                <?= icon('arrow-right') ?>
                <span><?= $isPdf ? 'Open PDF Receipt Document' : 'View Full Image Receipt' ?></span>
              </a>
            </div>
          <?php endif; ?>

          <!-- Stripe Session Info (if card) -->
          <?php if (!empty($paymentDetails['stripe_session_id'])): ?>
            <div style="font-size: 0.8rem; color: var(--text-3); word-break: break-all;">
              Stripe Session: <code><?= e($paymentDetails['stripe_session_id']) ?></code>
            </div>
          <?php endif; ?>

          <!-- Payment Audit Trail -->
          <?php if (!empty($paymentDetails['audit_trail']) && is_array($paymentDetails['audit_trail'])): ?>
            <div style="border-top: 1px solid var(--line); padding-top: var(--s-2);">
              <div class="text-xs text-slate-400 font-semibold uppercase" style="margin-bottom: 6px;">Audit Trail</div>
              <div style="display: flex; flex-direction: column; gap: 6px; font-size: 0.8rem;">
                <?php foreach (array_reverse($paymentDetails['audit_trail']) as $log): ?>
                  <div style="background: rgba(0,0,0,0.02); padding: 6px 8px; border-radius: 4px; border: 1px solid var(--line);">
                    <div style="display: flex; justify-content: space-between; color: var(--text-3); font-size: 0.75rem;">
                      <span class="font-semibold" style="text-transform: capitalize;"><?= e($log['status'] ?? '') ?></span>
                      <span><?= !empty($log['timestamp']) ? date('M j, H:i', strtotime($log['timestamp'])) : '' ?></span>
                    </div>
                    <?php if (!empty($log['notes'])): ?>
                      <div style="color: var(--text-2); margin-top: 2px;"><?= e($log['notes']) ?></div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Update Payment Status Form -->
          <div style="border-top: 1px solid var(--line); padding-top: var(--s-3); margin-top: var(--s-1);">
            <div class="text-xs text-slate-500 font-semibold uppercase" style="margin-bottom: var(--s-2);">Update Status &amp; Notes</div>
            <form action="<?= url('/admin/orders/' . rawurlencode($order['order_number']) . '/payment') ?>" method="POST" style="display: flex; flex-direction: column; gap: var(--s-2);">
              <?= csrf_field() ?>
              <div>
                <label class="text-xs text-slate-400">Payment Status</label>
                <select name="payment_status" class="admin-select" style="width: 100%; margin-top: 2px;">
                  <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Paid (Verified)</option>
                  <option value="rejected" <?= $paymentStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                  <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                  <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-slate-400">Admin Verification Notes</label>
                <input type="text" name="admin_notes" class="admin-input" placeholder="e.g. Verified in Bank/Easypaisa statement" style="width: 100%; margin-top: 2px; font-size: 0.85rem;">
              </div>
              <button type="submit" class="btn-admin-secondary btn-admin-sm" style="margin-top: 4px; justify-content: center;">
                Save Payment Update
              </button>
            </form>
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
