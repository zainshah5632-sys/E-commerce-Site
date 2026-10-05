<?php
declare(strict_types=1);

$order = $order ?? [];
$items = $order['items'] ?? [];
$shippingAddress = $order['shipping_address'] ?? [];
$bankInstructions = settings('bank_instructions', 'Transfer total to Example Bank.');
?>

<div class="container" style="padding-top: var(--s-7); padding-bottom: var(--s-9); max-width: 720px;">
  <div class="glass--strong" style="padding: var(--s-7); text-align: center;">
    <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: rgba(4,120,87,.15); color: var(--success); margin-bottom: var(--s-4);">
      <?= icon('check') ?>
    </div>

    <h1 style="font-size: 2.2rem; margin-bottom: var(--s-2);">Order Confirmed!</h1>
    <p style="font-size: 1.05rem; color: var(--text-2); margin-bottom: var(--s-4);">
      Thank you for your order, <strong><?= e($order['customer_name'] ?? 'Customer') ?></strong>. We have sent a confirmation receipt to <strong><?= e($order['email'] ?? '') ?></strong>.
    </p>

    <div class="glass" style="display: inline-block; padding: var(--s-3) var(--s-5); border-radius: var(--r-md); margin-bottom: var(--s-5);">
      <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-3); font-weight: 700; letter-spacing: 0.05em;">Order Reference</div>
      <div style="font-family: var(--font-display); font-size: 1.35rem; font-weight: 800; color: var(--brand-600); margin-top: 2px;">
        <?= e($order['order_number'] ?? '') ?>
      </div>
    </div>

    <!-- Bank Transfer Instructions if applicable -->
    <?php if (($order['payment_method'] ?? '') === 'bank_transfer'): ?>
      <div class="alert alert--info glass--strong" style="text-align: left; margin-bottom: var(--s-5);">
        <div>
          <strong>Bank Transfer Payment Instructions:</strong>
          <p style="margin-top: 4px; font-size: 0.9rem;"><?= nl2br(e($bankInstructions)) ?></p>
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
        <span>Total Paid / Due</span>
        <span><?= money((int)($order['total_cents'] ?? 0)) ?></span>
      </div>
    </div>

    <!-- Shipping Address Summary -->
    <div style="text-align: left; font-size: 0.9rem; color: var(--text-2); margin-bottom: var(--s-6);">
      <strong>Shipping to:</strong>
      <p style="margin-top: 2px;">
        <?= e($order['customer_name'] ?? '') ?><br>
        <?= e($shippingAddress['address_line_1'] ?? '') ?><br>
        <?php if (!empty($shippingAddress['address_line_2'])): ?>
          <?= e($shippingAddress['address_line_2']) ?><br>
        <?php endif; ?>
        <?= e($shippingAddress['city'] ?? '') ?>, <?= e($shippingAddress['state'] ?? '') ?> <?= e($shippingAddress['postal_code'] ?? '') ?>
      </p>
    </div>

    <div>
      <a href="<?= url('/shop') ?>" class="btn btn--primary btn--lg">
        <span>Continue Exploring</span>
        <?= icon('arrow-right') ?>
      </a>
    </div>
  </div>
</div>
