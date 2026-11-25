<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];
$method = $order['payment_method'] ?? 'cod';
$paymentStatus = $order['payment_status'] ?? 'pending';

$methodLabel = match($method) {
    'easypaisa'     => 'Easypaisa Mobile Wallet',
    'jazzcash'      => 'JazzCash Mobile Account',
    'bank_transfer' => 'Direct Bank Transfer / IBAN',
    'card'          => 'Visa / Mastercard (Credit & Debit)',
    default         => 'Cash on Delivery (COD)',
};

$statusBadge = match($paymentStatus) {
    'paid'     => ['label' => 'Payment Verified & Confirmed', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
    'rejected' => ['label' => 'Payment Verification Rejected', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
    'failed'   => ['label' => 'Payment Failed', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
    'refunded' => ['label' => 'Payment Refunded', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
    default    => ['label' => 'Payment Pending Verification', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
};
?>

<div class="container" style="padding-top: var(--s-7); padding-bottom: var(--s-9); max-width: 760px;">
  <div class="glass--strong" style="padding: var(--s-7); text-align: center; border-radius: var(--r-xl);">
    <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: rgba(4,120,87,.15); color: var(--success); margin-bottom: var(--s-4);">
      <?= icon('check') ?>
    </div>

    <h1 style="font-size: 2.2rem; margin-bottom: var(--s-2);">Order Confirmed!</h1>
    <p style="font-size: 1.05rem; color: var(--text-2); margin-bottom: var(--s-4);">
      Thank you for your order, <strong><?= e($order['customer_name'] ?? 'Customer') ?></strong>. An official invoice has been dispatched to <strong><?= e($order['email'] ?? '') ?></strong>.
    </p>

    <!-- Reference & Payment Method Header -->
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: var(--s-3); margin-bottom: var(--s-5);">
      <div class="glass" style="display: inline-block; padding: var(--s-3) var(--s-5); border-radius: var(--r-md);">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-3); font-weight: 700; letter-spacing: 0.05em;">Order Reference</div>
        <div style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 800; color: var(--brand-600); margin-top: 2px;">
          <?= e($order['order_number'] ?? '') ?>
        </div>
      </div>

      <div class="glass" style="display: inline-block; padding: var(--s-3) var(--s-5); border-radius: var(--r-md); text-align: left;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-3); font-weight: 700; letter-spacing: 0.05em;">Payment Rail</div>
        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-1); margin-top: 2px;">
          <?= e($methodLabel) ?>
        </div>
      </div>
    </div>

    <!-- Payment Status Alert Pill -->
    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 9999px; font-size: 0.85rem; font-weight: 700; border: 1px solid; margin-bottom: var(--s-5);" class="<?= $statusBadge['class'] ?>">
      <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: currentColor;"></span>
      <span><?= e($statusBadge['label']) ?></span>
    </div>

    <!-- Settlement Verification & Proof Details -->
    <?php if (!empty($order['transaction_id']) || !empty($order['payment_receipt_url'])): ?>
      <div class="glass" style="text-align: left; padding: var(--s-4); border-radius: var(--r-md); margin-bottom: var(--s-5); border-left: 4px solid var(--brand-600);">
        <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-1); margin-bottom: 6px;">Submitted Settlement Proof:</div>
        <?php if (!empty($order['transaction_id'])): ?>
          <div style="font-size: 0.88rem; color: var(--text-2);">
            Transaction ID (TID): <strong class="font-mono" style="color: var(--text-1);"><?= e($order['transaction_id']) ?></strong>
          </div>
        <?php endif; ?>
        <?php if (!empty($order['payment_receipt_url'])): ?>
          <div style="margin-top: 6px; font-size: 0.88rem;">
            Receipt Document: <a href="<?= ea($order['payment_receipt_url']) ?>" target="_blank" rel="noopener" style="color: var(--brand-600); font-weight: 600; text-decoration: underline;">View Attached Receipt &rarr;</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Method Specific Reassurance -->
    <?php if (in_array($method, ['easypaisa', 'jazzcash', 'bank_transfer'], true)): ?>
      <div class="alert alert--info glass--strong" style="text-align: left; margin-bottom: var(--s-5);">
        <div style="font-size: 0.88rem; line-height: 1.5;">
          <strong>Manual Transfer Settlement Notice:</strong><br>
          Our finance department verifies incoming manual transfers within business hours. Once verified against your submitted transaction ID, your order fulfillment will begin immediately.
        </div>
      </div>
    <?php elseif ($method === 'cod'): ?>
      <div class="alert alert--info glass--strong" style="text-align: left; margin-bottom: var(--s-5);">
        <div style="font-size: 0.88rem; line-height: 1.5;">
          <strong>Cash on Delivery:</strong> Please ensure you have the exact amount of <strong><?= money((int)($order['total_cents'] ?? 0)) ?></strong> ready upon package arrival.
        </div>
      </div>
    <?php endif; ?>

    <!-- Order Items Summary -->
    <div style="text-align: left; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); padding-block: var(--s-4); margin-block: var(--s-5);">
      <h3 style="font-size: 1.05rem; margin-bottom: var(--s-3);">Items in this Shipment</h3>
      <div style="display: flex; flex-direction: column; gap: var(--s-3);">
        <?php foreach ($items as $item): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem;">
            <div>
              <strong><?= e($item['product_name']) ?></strong> &times; <?= $item['quantity'] ?>
            </div>
            <div style="font-weight: 700;">
              <?= money((int)$item['unit_price_cents'] * (int)$item['quantity']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="border-top: 1px dashed var(--line); margin-top: var(--s-4); padding-top: var(--s-3); display: flex; justify-content: space-between; font-weight: 800; font-size: 1.15rem;">
        <span>Total Amount</span>
        <span><?= money((int)($order['total_cents'] ?? 0)) ?></span>
      </div>
    </div>

    <!-- Shipping Address Summary -->
    <div style="text-align: left; font-size: 0.9rem; color: var(--text-2); margin-bottom: var(--s-6);">
      <strong>Shipping Address:</strong>
      <p style="margin-top: 2px;">
        <?= e($order['customer_name'] ?? '') ?><br>
        <?= e($shippingAddress['address_line_1'] ?? '') ?><br>
        <?php if (!empty($shippingAddress['address_line_2'])): ?>
          <?= e($shippingAddress['address_line_2']) ?><br>
        <?php endif; ?>
        <?= e($shippingAddress['city'] ?? '') ?>, <?= e($shippingAddress['state'] ?? '') ?> <?= e($shippingAddress['postal_code'] ?? '') ?>
      </p>
    </div>

    <div style="display: flex; justify-content: center; gap: var(--s-3); flex-wrap: wrap;">
      <a href="<?= url('/shop') ?>" class="btn btn--primary btn--lg">
        <span>Continue Exploring</span>
        <?= icon('arrow-right') ?>
      </a>
      <button type="button" class="btn btn--ghost btn--lg" onclick="window.print()">
        <?= icon('printer') ?>
        <span>Print Receipt</span>
      </button>
    </div>
  </div>
</div>
