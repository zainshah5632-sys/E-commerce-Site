<?php
declare(strict_types=1);

$settings = $settings ?? [];
$errors = $errors ?? [];

$flatShippingMajor = isset($settings['flat_shipping_cents'])
    ? number_format((float)$settings['flat_shipping_cents'] / 100, 2, '.', '')
    : '5.00';

$freeThresholdMajor = isset($settings['free_shipping_threshold_cents'])
    ? number_format((float)$settings['free_shipping_threshold_cents'] / 100, 2, '.', '')
    : '75.00';
?>

<div class="settings-page-container">
  <!-- Page Header -->
  <header class="settings-page-header">
    <div class="settings-page-header__meta">
      <nav class="settings-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= url('/admin') ?>">Admin</a>
        <span class="settings-breadcrumbs__sep">/</span>
        <span class="settings-breadcrumbs__current">Store Settings</span>
      </nav>
      <h1 class="settings-page-title">Store Settings</h1>
      <p class="settings-page-desc">
        Global commerce configuration, multi-currency formatting, fulfillment logistics, alerts, and security infrastructure.
      </p>
    </div>

    <div class="settings-page-header__actions">
      <!-- Status Badge -->
      <span class="settings-stat-pill settings-stat-pill--active" title="Configuration live on storefront">
        <span class="settings-stat-pill__dot"></span>
        <span class="settings-stat-pill__label">Environment:</span>
        <strong class="settings-stat-pill__val">Production</strong>
      </span>

      <!-- Action Buttons (Connected to #settings-form via form attribute) -->
      <button
        type="reset"
        form="settings-form"
        class="settings-btn-secondary"
        id="btn-settings-discard-top"
      >
        Discard
      </button>

      <button
        type="submit"
        form="settings-form"
        class="settings-btn-primary"
        id="btn-settings-save-top"
      >
        <?= icon('check', 16) ?>
        <span>Save Changes</span>
      </button>
    </div>
  </header>

  <!-- Server Validation Errors (if any) -->
  <?php if (!empty($errors)): ?>
    <div class="settings-alert settings-alert--error" role="alert">
      <div class="settings-alert__icon">
        <?= icon('shield', 18) ?>
      </div>
      <div class="settings-alert__content">
        <h4 class="settings-alert__title">Please review the following configuration errors:</h4>
        <ul class="settings-alert__list">
          <?php foreach ($errors as $field => $err): ?>
            <li><?= e(is_array($err) ? implode(', ', $err) : $err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Settings Layout Grid: Vertical Sidebar Navigation + Content Panes -->
  <div class="settings-layout-grid">
    
    <!-- Left Column: Modern Vertical Sidebar Navigation -->
    <aside class="settings-sidebar" aria-label="Settings Categories">
      <div class="settings-sidebar__card">
        <nav class="settings-sidebar__nav" id="settings-nav" role="tablist">
          
          <!-- Tab 1: General Info -->
          <button
            type="button"
            class="settings-nav-item settings-nav-item--active"
            data-tab-target="general"
            role="tab"
            aria-selected="true"
            aria-controls="pane-general"
            id="tab-general"
          >
            <span class="settings-nav-item__icon" aria-hidden="true">
              <?= icon('store', 18) ?>
            </span>
            <div class="settings-nav-item__text">
              <span class="settings-nav-item__title">General Info</span>
              <span class="settings-nav-item__desc">Brand identity &amp; banner</span>
            </div>
          </button>

          <!-- Tab 2: Payments & Currency -->
          <button
            type="button"
            class="settings-nav-item"
            data-tab-target="payments"
            role="tab"
            aria-selected="false"
            aria-controls="pane-payments"
            id="tab-payments"
          >
            <span class="settings-nav-item__icon" aria-hidden="true">
              <?= icon('credit-card', 18) ?>
            </span>
            <div class="settings-nav-item__text">
              <span class="settings-nav-item__title">Payments &amp; Currency</span>
              <span class="settings-nav-item__desc">Currencies &amp; wire settlement</span>
            </div>
          </button>

          <!-- Tab 3: Shipping & Delivery -->
          <button
            type="button"
            class="settings-nav-item"
            data-tab-target="shipping"
            role="tab"
            aria-selected="false"
            aria-controls="pane-shipping"
            id="tab-shipping"
          >
            <span class="settings-nav-item__icon" aria-hidden="true">
              <?= icon('truck', 18) ?>
            </span>
            <div class="settings-nav-item__text">
              <span class="settings-nav-item__title">Shipping &amp; Delivery</span>
              <span class="settings-nav-item__desc">Flat rates &amp; free tier</span>
            </div>
          </button>

          <!-- Tab 4: Notifications -->
          <button
            type="button"
            class="settings-nav-item"
            data-tab-target="notifications"
            role="tab"
            aria-selected="false"
            aria-controls="pane-notifications"
            id="tab-notifications"
          >
            <span class="settings-nav-item__icon" aria-hidden="true">
              <?= icon('bell', 18) ?>
            </span>
            <div class="settings-nav-item__text">
              <span class="settings-nav-item__title">Notifications</span>
              <span class="settings-nav-item__desc">Customer receipts &amp; alerts</span>
            </div>
          </button>

          <!-- Tab 5: API & Security -->
          <button
            type="button"
            class="settings-nav-item"
            data-tab-target="security"
            role="tab"
            aria-selected="false"
            aria-controls="pane-security"
            id="tab-security"
          >
            <span class="settings-nav-item__icon" aria-hidden="true">
              <?= icon('shield', 18) ?>
            </span>
            <div class="settings-nav-item__text">
              <span class="settings-nav-item__title">API &amp; Security</span>
              <span class="settings-nav-item__desc">Infrastructure &amp; encryption</span>
            </div>
          </button>

        </nav>

        <!-- Sidebar Info Widget -->
        <div class="settings-sidebar__footer">
          <div class="settings-sys-status">
            <span class="settings-sys-status__indicator"></span>
            <div class="settings-sys-status__content">
              <span class="settings-sys-status__title">Cloud Infrastructure</span>
              <span class="settings-sys-status__sub">All systems operational</span>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Right Column: Settings Content Panes (Form) -->
    <main class="settings-content-column">
      <form action="<?= url('/admin/settings') ?>" method="POST" id="settings-form" class="settings-form">
        <?= csrf_field() ?>

        <!-- =====================================================================
             TAB 1: General Info
             ===================================================================== -->
        <div data-tab-pane="general" id="pane-general" class="settings-pane" role="tabpanel" aria-labelledby="tab-general">
          
          <!-- Card: Storefront Identity -->
          <section class="settings-card" aria-labelledby="heading-identity">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-identity" class="settings-card__title">Storefront Identity</h2>
                <p class="settings-card__desc">
                  Public brand name and official correspondence email shown on customer order receipts and storefront headers.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <!-- Store Name -->
                <div class="settings-form-group">
                  <label for="set-store-name" class="settings-form-label">
                    Store Name <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="set-store-name"
                    name="store_name"
                    class="settings-form-input <?= !empty($errors['store_name']) ? 'settings-form-input--error' : '' ?>"
                    required
                    maxlength="50"
                    placeholder="GlassCart"
                    value="<?= ea($settings['store_name'] ?? 'GlassCart') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">The official store identity rendered across navigation, email headers, and SEO titles.</p>
                  <?php if (!empty($errors['store_name'])): ?>
                    <span class="settings-field-error"><?= e($errors['store_name']) ?></span>
                  <?php endif; ?>
                </div>

                <!-- Contact Email -->
                <div class="settings-form-group">
                  <label for="set-contact-email" class="settings-form-label">
                    Support / Contact Email <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="email"
                    id="set-contact-email"
                    name="contact_email"
                    class="settings-form-input <?= !empty($errors['contact_email']) ? 'settings-form-input--error' : '' ?>"
                    required
                    placeholder="support@glasscart.local"
                    value="<?= ea($settings['contact_email'] ?? 'support@glasscart.local') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Customer reply-to destination for automatic order confirmation invoices.</p>
                  <?php if (!empty($errors['contact_email'])): ?>
                    <span class="settings-field-error"><?= e($errors['contact_email']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Announcement Banner -->
          <section class="settings-card" aria-labelledby="heading-announcement">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-announcement" class="settings-card__title">Announcement Banner</h2>
                <p class="settings-card__desc">
                  Broadcast persistent notification banners across top storefront headers for seasonal campaigns, free shipping incentives, or store updates.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-form-group">
                <label for="set-announcement" class="settings-form-label">
                  Banner Text Message
                </label>
                <input
                  type="text"
                  id="set-announcement"
                  name="announcement"
                  class="settings-form-input"
                  placeholder="e.g. Complimentary worldwide express shipping on orders over $75"
                  value="<?= ea($settings['announcement'] ?? '') ?>"
                  autocomplete="off"
                >
                <p class="settings-form-helper">Leave empty to conceal the storefront announcement bar entirely.</p>
              </div>

              <!-- Interactive Live Preview Box -->
              <div class="settings-preview-box">
                <div class="settings-preview-box__label">
                  <?= icon('eye', 14) ?>
                  <span>Storefront Header Banner Preview</span>
                </div>
                <div class="settings-preview-banner" id="announcement-preview-banner">
                  <span id="announcement-preview-text">
                    <?= !empty($settings['announcement']) ? e($settings['announcement']) : 'Complimentary worldwide express shipping on orders over $75' ?>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Regional Preferences -->
          <section class="settings-card" aria-labelledby="heading-regional">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-regional" class="settings-card__title">Localization &amp; Region</h2>
                <p class="settings-card__desc">
                  Primary store locale, timezone, and calendar standard for transactions.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label class="settings-form-label">Primary Store Locale</label>
                  <div class="settings-form-static">
                    <span class="settings-badge-neutral">English (United States) — en_US</span>
                    <span class="text-xs text-slate-500">Default standard for dates, number punctuation, and currency formats.</span>
                  </div>
                </div>

                <div class="settings-form-group">
                  <label class="settings-form-label">Server Reference Timezone</label>
                  <div class="settings-form-static">
                    <span class="settings-badge-neutral">UTC (Coordinated Universal Time)</span>
                    <span class="text-xs text-slate-500">Order timestamps, audit trails, and inventory sync schedules.</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 2: Payments & Currency
             ===================================================================== -->
        <div data-tab-pane="payments" id="pane-payments" class="settings-pane hidden" role="tabpanel" aria-labelledby="tab-payments">
          
          <!-- Card: Currency & Unit Pricing -->
          <section class="settings-card" aria-labelledby="heading-currency">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-currency" class="settings-card__title">Currency &amp; Unit Pricing</h2>
                <p class="settings-card__desc">
                  Configure how monetary values, pricing tags, and order receipts are formatted storewide.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <!-- Currency Code -->
                <div class="settings-form-group">
                  <label for="set-currency-code" class="settings-form-label">
                    Currency Code (ISO 4217) <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="set-currency-code"
                    name="currency_code"
                    maxlength="5"
                    class="settings-form-input font-mono uppercase <?= !empty($errors['currency_code']) ? 'settings-form-input--error' : '' ?>"
                    required
                    placeholder="USD"
                    value="<?= ea($settings['currency_code'] ?? 'USD') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">3-letter standard ISO currency code (e.g. USD, EUR, GBP, JPY).</p>
                  <?php if (!empty($errors['currency_code'])): ?>
                    <span class="settings-field-error"><?= e($errors['currency_code']) ?></span>
                  <?php endif; ?>
                </div>

                <!-- Currency Symbol -->
                <div class="settings-form-group">
                  <label for="set-currency-symbol" class="settings-form-label">
                    Currency Symbol <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="set-currency-symbol"
                    name="currency_symbol"
                    maxlength="5"
                    class="settings-form-input <?= !empty($errors['currency_symbol']) ? 'settings-form-input--error' : '' ?>"
                    required
                    placeholder="$"
                    value="<?= ea($settings['currency_symbol'] ?? '$') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Character symbol prefixed to product prices (e.g. $, &euro;, &pound;).</p>
                  <?php if (!empty($errors['currency_symbol'])): ?>
                    <span class="settings-field-error"><?= e($errors['currency_symbol']) ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Live Price Format Demonstration -->
              <div class="settings-callout settings-callout--brand mt-4">
                <div class="settings-callout__icon">
                  <?= icon('check', 16) ?>
                </div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">Storefront Price Preview:</strong>
                  <span class="settings-callout__desc">
                    Products will display as: <strong id="price-preview-demo" class="font-mono text-indigo-700 font-bold"><?= ea($settings['currency_symbol'] ?? '$') ?>149.00 <?= ea($settings['currency_code'] ?? 'USD') ?></strong>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Direct Bank Settlement -->
          <section class="settings-card" aria-labelledby="heading-bank">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-bank" class="settings-card__title">Direct Bank Settlement (Wire / BACS)</h2>
                <p class="settings-card__desc">
                  Custom wire transfer instructions, IBAN numbers, and routing details presented to buyers choosing manual bank settlement.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-form-group">
                <label for="set-bank" class="settings-form-label">
                  Wire Instructions &amp; Bank Account Credentials
                </label>
                <textarea
                  id="set-bank"
                  name="bank_instructions"
                  rows="4"
                  class="settings-form-textarea font-mono text-xs"
                  placeholder="Account Holder: GlassCart Inc&#10;IBAN: US98BANK000123456789&#10;Swift / BIC: GCARTUS33&#10;Routing: 021000021"
                ><?= ea($settings['bank_instructions'] ?? '') ?></textarea>
                <p class="settings-form-helper">Rendered directly on checkout receipt pages for wire payment selection.</p>
              </div>
            </div>
          </section>

          <!-- Card: Active Payment Gateways -->
          <section class="settings-card" aria-labelledby="heading-gateways">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-gateways" class="settings-card__title">Configured Payment Rails</h2>
                <p class="settings-card__desc">Active settlement channels ready for customer checkouts.</p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-rail-list">
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon"><?= icon('credit-card', 16) ?></span>
                    <div>
                      <strong class="settings-rail-item__name">Direct Credit &amp; Debit Cards</strong>
                      <p class="settings-rail-item__desc">Visa, Mastercard, American Express via secure tokenization.</p>
                    </div>
                  </div>
                  <span class="settings-status-pill settings-status-pill--active">
                    <span class="settings-status-dot"></span>
                    <span>Ready</span>
                  </span>
                </div>

                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon"><?= icon('truck', 16) ?></span>
                    <div>
                      <strong class="settings-rail-item__name">Express Digital Wallets</strong>
                      <p class="settings-rail-item__desc">Apple Pay and Google Pay accelerated mobile sheet checkouts.</p>
                    </div>
                  </div>
                  <span class="settings-status-pill settings-status-pill--active">
                    <span class="settings-status-dot"></span>
                    <span>Active</span>
                  </span>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 3: Shipping & Delivery
             ===================================================================== -->
        <div data-tab-pane="shipping" id="pane-shipping" class="settings-pane hidden" role="tabpanel" aria-labelledby="tab-shipping">
          
          <!-- Card: Shipping Rates & Free Tier -->
          <section class="settings-card" aria-labelledby="heading-shipping">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-shipping" class="settings-card__title">Shipping Rates &amp; Free Delivery Tier</h2>
                <p class="settings-card__desc">
                  Define standard flat-rate delivery fees and the cart qualifying threshold required for complimentary free shipping.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <!-- Standard Flat Shipping -->
                <div class="settings-form-group">
                  <label for="set-flat-shipping" class="settings-form-label">
                    Standard Flat Shipping Fee ($) <span class="text-rose-500">*</span>
                  </label>
                  <div class="settings-input-group">
                    <span class="settings-input-affix" aria-hidden="true">$</span>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      id="set-flat-shipping"
                      name="flat_shipping"
                      class="settings-input-group__field"
                      value="<?= ea($flatShippingMajor) ?>"
                      required
                    >
                  </div>
                  <p class="settings-form-helper">Applied to checkout carts when subtotal is below the free tier threshold.</p>
                </div>

                <!-- Free Shipping Threshold -->
                <div class="settings-form-group">
                  <label for="set-free-threshold" class="settings-form-label">
                    Free Shipping Cart Threshold ($) <span class="text-rose-500">*</span>
                  </label>
                  <div class="settings-input-group">
                    <span class="settings-input-affix" aria-hidden="true">$</span>
                    <input
                      type="number"
                      step="0.01"
                      min="0"
                      id="set-free-threshold"
                      name="free_shipping_threshold"
                      class="settings-input-group__field"
                      value="<?= ea($freeThresholdMajor) ?>"
                      required
                    >
                  </div>
                  <p class="settings-form-helper">Orders with subtotal equal or above this qualify for 100% free delivery.</p>
                </div>
              </div>

              <!-- Calculation Explainer Callout (Shopify/Stripe style) -->
              <div class="settings-callout settings-callout--shipping mt-4">
                <div class="settings-callout__icon">
                  <?= icon('truck', 18) ?>
                </div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">Automatic Cart Calculation Rule:</strong>
                  <p class="settings-callout__desc">
                    Cart Subtotal &ge; <span id="threshold-demo" class="font-semibold text-emerald-700 font-mono">$<?= ea($freeThresholdMajor) ?></span> &rarr; Shipping = <strong class="text-emerald-700">$0.00 (FREE)</strong>.
                    <br>
                    Cart Subtotal &lt; <span class="font-semibold font-mono">$<?= ea($freeThresholdMajor) ?></span> &rarr; Shipping = <strong class="text-slate-800 font-mono">$<?= ea($flatShippingMajor) ?> Flat Rate</strong>.
                  </p>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Fulfillment Logistics -->
          <section class="settings-card" aria-labelledby="heading-logistics">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-logistics" class="settings-card__title">Fulfillment &amp; Carrier SLA</h2>
                <p class="settings-card__desc">Handling duration and verified courier integration.</p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label class="settings-form-label">Dispatch Handling Standard</label>
                  <div class="settings-form-static">
                    <span class="settings-badge-neutral">1 &ndash; 2 Business Days</span>
                    <span class="text-xs text-slate-500">Standard lead time from order payment verification to carrier scan.</span>
                  </div>
                </div>

                <div class="settings-form-group">
                  <label class="settings-form-label">Integrated Courier Routing</label>
                  <div class="settings-form-static">
                    <span class="settings-badge-neutral">DHL Express &bull; FedEx Priority &bull; UPS Ground</span>
                    <span class="text-xs text-slate-500">Domestic &amp; international end-to-end tracked parcels.</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 4: Notifications
             ===================================================================== -->
        <div data-tab-pane="notifications" id="pane-notifications" class="settings-pane hidden" role="tabpanel" aria-labelledby="tab-notifications">
          
          <!-- Card: Customer Transactional Alerts -->
          <section class="settings-card" aria-labelledby="heading-cust-notif">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-cust-notif" class="settings-card__title">Customer Transactional Notifications</h2>
                <p class="settings-card__desc">
                  Real-time automated emails triggered on customer lifecycle events and checkout milestones.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-toggle-stack">
                
                <!-- Toggle 1: Order Confirmation -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <span class="settings-toggle-card__title">Customer Order Confirmations</span>
                    <p class="settings-toggle-card__desc">Dispatch itemized digital invoices and settlement confirmation immediately upon payment completion.</p>
                  </div>
                  <label class="category-switch" for="notif-order-confirm">
                    <input type="checkbox" id="notif-order-confirm" name="notif_order_confirm" value="1" checked>
                    <span class="category-switch__slider" aria-hidden="true"></span>
                    <span class="sr-only">Toggle order confirmation emails</span>
                  </label>
                </div>

                <!-- Toggle 2: Shipment Dispatched -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <span class="settings-toggle-card__title">Shipment Tracking &amp; Dispatch Notices</span>
                    <p class="settings-toggle-card__desc">Automatically notify buyers with their courier tracking code when orders transition to dispatched status.</p>
                  </div>
                  <label class="category-switch" for="notif-shipping-update">
                    <input type="checkbox" id="notif-shipping-update" name="notif_shipping_update" value="1" checked>
                    <span class="category-switch__slider" aria-hidden="true"></span>
                    <span class="sr-only">Toggle shipment tracking notices</span>
                  </label>
                </div>

                <!-- Toggle 3: Refund Notifications -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <span class="settings-toggle-card__title">Refund &amp; Cancellation Advisories</span>
                    <p class="settings-toggle-card__desc">Transmit credit adjustment documentation when an order is cancelled or refunded by administrators.</p>
                  </div>
                  <label class="category-switch" for="notif-refund-advisory">
                    <input type="checkbox" id="notif-refund-advisory" name="notif_refund_advisory" value="1" checked>
                    <span class="category-switch__slider" aria-hidden="true"></span>
                    <span class="sr-only">Toggle refund advisories</span>
                  </label>
                </div>

              </div>
            </div>
          </section>

          <!-- Card: Administrator Telemetry Alerts -->
          <section class="settings-card" aria-labelledby="heading-admin-notif">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-admin-notif" class="settings-card__title">Store Operations &amp; Inventory Telemetry</h2>
                <p class="settings-card__desc">
                  Administrative warning thresholds and automated operational digest alerts.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-toggle-stack">
                
                <!-- Toggle 4: Low Stock Telemetry -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <span class="settings-toggle-card__title">Low Stock Telemetry Alerts</span>
                    <p class="settings-toggle-card__desc">Notify administrative staff immediately when any product SKU stock falls below 3 units in inventory.</p>
                  </div>
                  <label class="category-switch" for="notif-low-stock">
                    <input type="checkbox" id="notif-low-stock" name="notif_low_stock" value="1" checked>
                    <span class="category-switch__slider" aria-hidden="true"></span>
                    <span class="sr-only">Toggle low stock alerts</span>
                  </label>
                </div>

                <!-- Toggle 5: Daily Order Summary -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <span class="settings-toggle-card__title">Daily Morning Order Digest</span>
                    <p class="settings-toggle-card__desc">Compile previous 24-hour gross volume, orders completed, and fulfillment queues into a daily digest.</p>
                  </div>
                  <label class="category-switch" for="notif-daily-digest">
                    <input type="checkbox" id="notif-daily-digest" name="notif_daily_digest" value="1">
                    <span class="category-switch__slider" aria-hidden="true"></span>
                    <span class="sr-only">Toggle daily morning order digest</span>
                  </label>
                </div>

              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 5: API & Security
             ===================================================================== -->
        <div data-tab-pane="security" id="pane-security" class="settings-pane hidden" role="tabpanel" aria-labelledby="tab-security">
          
          <!-- Card: Cloud Infrastructure -->
          <section class="settings-card" aria-labelledby="heading-infra">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-infra" class="settings-card__title">Backend &amp; Cloud Infrastructure</h2>
                <p class="settings-card__desc">
                  Database connectivity, connection pooling, and authenticated REST endpoint telemetry.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-status-list">
                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">Supabase REST PostgREST Tunnel</strong>
                    <span class="settings-status-row__desc">Server-side authenticated HTTPS connection active with low-latency DNS resolution.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--active">
                    <span class="settings-status-dot"></span>
                    <span>Connected (200 OK)</span>
                  </span>
                </div>

                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">Database Connection Pooling</strong>
                    <span class="settings-status-row__desc">Supavisor transaction pooler with persistent connection reuse.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--active">
                    <span class="settings-status-dot"></span>
                    <span>Active (Pool Mode)</span>
                  </span>
                </div>

                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">Catalog Fallback Failover</strong>
                    <span class="settings-status-row__desc">In-memory seed resilience mechanism if upstream cloud pauses or undergoes maintenance.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--active">
                    <span class="settings-status-dot"></span>
                    <span>Armed &amp; Ready</span>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Cryptographic Security -->
          <section class="settings-card" aria-labelledby="heading-crypto">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-crypto" class="settings-card__title">Cryptographic Security Standards</h2>
                <p class="settings-card__desc">
                  Session authentication, AEAD encryption, and content security policy boundaries.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-status-list">
                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">Libsodium AEAD Cookie Encryption</strong>
                    <span class="settings-status-row__desc">Stateless XChaCha20-Poly1305 tamper-proof session tokens signed at the cryptographic layer.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--indigo">
                    <?= icon('shield', 13) ?>
                    <span>Active (256-bit AEAD)</span>
                  </span>
                </div>

                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">Strict Content Security Policy (CSP)</strong>
                    <span class="settings-status-row__desc">Directives enforced for script execution, frame ancestors, and object sources.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--indigo">
                    <?= icon('shield', 13) ?>
                    <span>Enforced</span>
                  </span>
                </div>

                <div class="settings-status-row">
                  <div class="settings-status-row__meta">
                    <strong class="settings-status-row__title">CSRF Protection Standard</strong>
                    <span class="settings-status-row__desc">Cryptographically random Synchronizer Token Pattern verified on all state-changing POST requests.</span>
                  </div>
                  <span class="settings-status-pill settings-status-pill--indigo">
                    <?= icon('shield', 13) ?>
                    <span>Active</span>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: API Endpoint & Developer Keys -->
          <section class="settings-card" aria-labelledby="heading-api-keys">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-api-keys" class="settings-card__title">Developer Endpoints &amp; Webhook Credentials</h2>
                <p class="settings-card__desc">API endpoint references for programmatic storefront integrations.</p>
              </div>
            </div>

            <div class="settings-card__body">
              <!-- API Base URL -->
              <div class="settings-form-group">
                <label for="api-base-url" class="settings-form-label">Storefront REST API Base URL</label>
                <div class="settings-copy-input-wrap">
                  <input
                    type="text"
                    id="api-base-url"
                    class="settings-form-input font-mono text-xs"
                    readonly
                    value="<?= e(url('/api/v1')) ?>"
                  >
                  <button
                    type="button"
                    class="settings-copy-btn"
                    data-copy-target="api-base-url"
                    title="Copy API Base URL"
                  >
                    <?= icon('copy', 14) ?>
                    <span>Copy</span>
                  </button>
                </div>
                <p class="settings-form-helper">Public headless storefront JSON endpoint for catalog and inventory queries.</p>
              </div>

              <!-- Webhook Secret -->
              <div class="settings-form-group mt-4">
                <label for="api-webhook-secret" class="settings-form-label">Webhook HMAC Signing Key</label>
                <div class="settings-copy-input-wrap">
                  <input
                    type="password"
                    id="api-webhook-secret"
                    class="settings-form-input font-mono text-xs"
                    readonly
                    value="whsec_98fbc184013490aa8efb019349cde012"
                  >
                  <button
                    type="button"
                    class="settings-copy-btn"
                    data-copy-target="api-webhook-secret"
                    title="Copy Webhook Secret"
                  >
                    <?= icon('copy', 14) ?>
                    <span>Copy</span>
                  </button>
                </div>
                <p class="settings-form-helper">Key used to sign payload signatures for downstream dispatch webhooks.</p>
              </div>
            </div>
          </section>

        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="settings-footer-bar">
          <div class="settings-footer-bar__note">
            <span class="settings-footer-bar__icon"><?= icon('shield', 15) ?></span>
            <span>Configuration changes synchronize immediately across all storefront endpoints and active customer sessions.</span>
          </div>
          <div class="settings-footer-bar__actions">
            <button
              type="reset"
              class="settings-btn-secondary"
              id="btn-settings-discard-bottom"
            >
              Discard
            </button>
            <button
              type="submit"
              class="settings-btn-primary"
              id="btn-settings-save-bottom"
            >
              <?= icon('check', 16) ?>
              <span>Save Changes</span>
            </button>
          </div>
        </div>

      </form>
    </main>
  </div>
</div>
