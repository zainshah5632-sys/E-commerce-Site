<?php
declare(strict_types=1);

$settings = $settings ?? [];
$errors = $errors ?? [];
$activeTab = $activeTab ?? 'general';

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
            class="settings-nav-item <?= $activeTab === 'general' ? 'settings-nav-item--active' : '' ?>"
            data-tab-target="general"
            role="tab"
            aria-selected="<?= $activeTab === 'general' ? 'true' : 'false' ?>"
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
            class="settings-nav-item <?= $activeTab === 'payments' ? 'settings-nav-item--active' : '' ?>"
            data-tab-target="payments"
            role="tab"
            aria-selected="<?= $activeTab === 'payments' ? 'true' : 'false' ?>"
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
            class="settings-nav-item <?= $activeTab === 'shipping' ? 'settings-nav-item--active' : '' ?>"
            data-tab-target="shipping"
            role="tab"
            aria-selected="<?= $activeTab === 'shipping' ? 'true' : 'false' ?>"
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
            class="settings-nav-item <?= $activeTab === 'notifications' ? 'settings-nav-item--active' : '' ?>"
            data-tab-target="notifications"
            role="tab"
            aria-selected="<?= $activeTab === 'notifications' ? 'true' : 'false' ?>"
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
            class="settings-nav-item <?= $activeTab === 'security' ? 'settings-nav-item--active' : '' ?>"
            data-tab-target="security"
            role="tab"
            aria-selected="<?= $activeTab === 'security' ? 'true' : 'false' ?>"
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
        <input type="hidden" name="active_tab" id="settings-active-tab" value="<?= ea($activeTab ?? 'general') ?>">

        <!-- =====================================================================
             TAB 1: General Info
             ===================================================================== -->
        <div data-tab-pane="general" id="pane-general" class="settings-pane <?= $activeTab === 'general' ? '' : 'hidden' ?>" role="tabpanel" aria-labelledby="tab-general">
          
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
        <div data-tab-pane="payments" id="pane-payments" class="settings-pane <?= $activeTab === 'payments' ? '' : 'hidden' ?>" role="tabpanel" aria-labelledby="tab-payments">
          
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
                    placeholder="PKR"
                    value="<?= ea($settings['currency_code'] ?? 'PKR') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">3-letter standard ISO currency code (e.g. PKR, USD, EUR, GBP).</p>
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
                    placeholder="Rs"
                    value="<?= ea($settings['currency_symbol'] ?? 'Rs') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Character symbol prefixed to product prices (e.g. Rs, $, &euro;).</p>
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
                    Products will display as: <strong id="price-preview-demo" class="font-mono text-indigo-700 font-bold"><?= ea($settings['currency_symbol'] ?? 'Rs') ?> 2,500.00 <?= ea($settings['currency_code'] ?? 'PKR') ?></strong>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Payment Methods Enable / Disable Toggles -->
          <section class="settings-card" aria-labelledby="heading-payment-methods">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-payment-methods" class="settings-card__title">Payment Methods Activation</h2>
                <p class="settings-card__desc">
                  Toggle supported customer payment channels on or off. Disabled methods are automatically hidden from checkout.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-rail-list">
                <!-- COD -->
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon"><?= icon('truck', 16) ?></span>
                    <div class="settings-rail-item__text">
                      <strong class="settings-rail-item__name">Cash on Delivery (COD)</strong>
                      <p class="settings-rail-item__desc">Collect physical cash upon doorstep package delivery.</p>
                    </div>
                  </div>
                  <label class="settings-switch" for="pay-cod">
                    <input type="checkbox" id="pay-cod" name="payment_cod_enabled" value="1" <?= !empty($settings['payment_cod_enabled']) ? 'checked' : '' ?> aria-label="Cash on Delivery (COD)">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Easypaisa -->
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon" style="color: #00B140; font-weight: 800; font-size: 0.8rem;">EP</span>
                    <div class="settings-rail-item__text">
                      <strong class="settings-rail-item__name">Easypaisa Mobile Wallet</strong>
                      <p class="settings-rail-item__desc">Receive instant wallet transfers via Easypaisa mobile account with TID confirmation.</p>
                    </div>
                  </div>
                  <label class="settings-switch" for="pay-easypaisa">
                    <input type="checkbox" id="pay-easypaisa" name="payment_easypaisa_enabled" value="1" <?= !empty($settings['payment_easypaisa_enabled']) ? 'checked' : '' ?> aria-label="Easypaisa Mobile Wallet">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- JazzCash -->
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon" style="color: #FEBE10; background: #000; padding: 2px 4px; border-radius: 4px; font-weight: 800; font-size: 0.75rem;">JC</span>
                    <div class="settings-rail-item__text">
                      <strong class="settings-rail-item__name">JazzCash Mobile Account</strong>
                      <p class="settings-rail-item__desc">Receive instant wallet transfers via JazzCash account with TID confirmation.</p>
                    </div>
                  </div>
                  <label class="settings-switch" for="pay-jazzcash">
                    <input type="checkbox" id="pay-jazzcash" name="payment_jazzcash_enabled" value="1" <?= !empty($settings['payment_jazzcash_enabled']) ? 'checked' : '' ?> aria-label="JazzCash Mobile Account">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Bank Wire -->
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon"><?= icon('store', 16) ?></span>
                    <div class="settings-rail-item__text">
                      <strong class="settings-rail-item__name">Direct Bank Transfer / IBAN / Raast</strong>
                      <p class="settings-rail-item__desc">Allow online banking, Raast, and wire transfer with reference and receipt upload.</p>
                    </div>
                  </div>
                  <label class="settings-switch" for="pay-bank">
                    <input type="checkbox" id="pay-bank" name="payment_bank_enabled" value="1" <?= !empty($settings['payment_bank_enabled']) ? 'checked' : '' ?> aria-label="Direct Bank Transfer / IBAN / Raast">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Visa / Mastercard Cards -->
                <div class="settings-rail-item">
                  <div class="settings-rail-item__info">
                    <span class="settings-rail-item__icon"><?= icon('credit-card', 16) ?></span>
                    <div class="settings-rail-item__text">
                      <strong class="settings-rail-item__name">Visa &amp; Mastercard (Cards)</strong>
                      <p class="settings-rail-item__desc">Official PCI-DSS compliant credit and debit card settlement.</p>
                    </div>
                  </div>
                  <label class="settings-switch" for="pay-card">
                    <input type="checkbox" id="pay-card" name="payment_card_enabled" value="1" <?= !empty($settings['payment_card_enabled']) ? 'checked' : '' ?> aria-label="Visa &amp; Mastercard (Cards)">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Merchant Bank Settlement & Mobile Wallet Accounts -->
          <section class="settings-card" aria-labelledby="heading-settlement-accounts">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-settlement-accounts" class="settings-card__title">Merchant Settlement &amp; Payout Accounts</h2>
                <p class="settings-card__desc">
                  Designated official bank accounts and mobile wallet numbers used for customer transfers, reconciliations, and payout sweeps.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <!-- Easypaisa Settlement Wallet Number -->
                <div class="settings-form-group">
                  <label for="set-ep-merchant-number" class="settings-form-label">
                    Easypaisa Settlement Mobile Account
                  </label>
                  <input
                    type="text"
                    id="set-ep-merchant-number"
                    name="easypaisa_merchant_number"
                    class="settings-form-input font-mono"
                    placeholder="03120246309"
                    value="<?= ea($settings['easypaisa_merchant_number'] ?? '03120246309') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Official registered Easypaisa merchant wallet number for customer peer-to-merchant settlements.</p>
                </div>

                <!-- Settlement Bank Name -->
                <div class="settings-form-group">
                  <label for="set-bank-name" class="settings-form-label">
                    Settlement Bank Institution
                  </label>
                  <input
                    type="text"
                    id="set-bank-name"
                    name="bank_name"
                    class="settings-form-input"
                    placeholder="United Bank Limited (UBL)"
                    value="<?= ea($settings['bank_name'] ?? 'United Bank Limited (UBL)') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Scheduled clearing bank for direct customer IBAN / Raast deposits.</p>
                </div>
              </div>

              <div class="settings-grid-2 mt-3">
                <!-- Settlement Account Title -->
                <div class="settings-form-group">
                  <label for="set-bank-title" class="settings-form-label">
                    Bank Account Title / Beneficiary Name
                  </label>
                  <input
                    type="text"
                    id="set-bank-title"
                    name="bank_account_title"
                    class="settings-form-input"
                    placeholder="GlassCart Retail"
                    value="<?= ea($settings['bank_account_title'] ?? 'GlassCart Retail') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Official business or entity account name verified on bank statements.</p>
                </div>

                <!-- Settlement Account Number / IBAN -->
                <div class="settings-form-group">
                  <label for="set-bank-account-num" class="settings-form-label">
                    Bank Account Number / IBAN
                  </label>
                  <input
                    type="text"
                    id="set-bank-account-num"
                    name="bank_account_number"
                    class="settings-form-input font-mono"
                    placeholder="4214 6182 4622 1918"
                    value="<?= ea($settings['bank_account_number'] ?? '4214 6182 4622 1918') ?>"
                    autocomplete="off"
                  >
                  <p class="settings-form-helper">Official UBL / Raast clearing account number for wire transfers.</p>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Easypaisa Official Gateway Credentials -->
          <section class="settings-card" aria-labelledby="heading-easypaisa">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-easypaisa" class="settings-card__title">Easypaisa Automated Gateway Credentials</h2>
                <p class="settings-card__desc">Official Easypaisa Open API credentials for automated mobile wallet debits and in-app PIN prompts.</p>
              </div>
            </div>
            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label for="set-ep-mode" class="settings-form-label">Gateway Environment</label>
                  <select id="set-ep-mode" name="easypaisa_mode" class="settings-form-input">
                    <option value="sandbox" <?= ($settings['easypaisa_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox / Staging (easypaystg.easypaisa.com.pk)</option>
                    <option value="live" <?= ($settings['easypaisa_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Production / Live (easypay.easypaisa.com.pk)</option>
                  </select>
                </div>
                <div class="settings-form-group">
                  <label for="set-ep-store" class="settings-form-label">Easypaisa Store ID</label>
                  <input type="text" id="set-ep-store" name="easypaisa_store_id" class="settings-form-input font-mono" placeholder="e.g. 12345" value="<?= ea($settings['easypaisa_store_id'] ?? '') ?>">
                </div>
              </div>
              <div class="settings-form-group mt-3">
                <label for="set-ep-key" class="settings-form-label">Easypaisa Hash Key / API Credentials</label>
                <input type="password" id="set-ep-key" name="easypaisa_hash_key" class="settings-form-input font-mono text-xs" placeholder="Secret Hash Key from Easypaisa Merchant Portal" value="<?= ea($settings['easypaisa_hash_key'] ?? '') ?>">
              </div>
              <div class="settings-callout settings-callout--brand mt-3">
                <div class="settings-callout__icon"><?= icon('shield', 16) ?></div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">Easypaisa IPN Callback URL:</strong>
                  <span class="settings-callout__desc">
                    Register in your Easypaisa Merchant Portal: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-700"><?= ea(\App\Core\Env::get('APP_URL', 'https://glass-cart.onrender.com')) ?>/webhook/easypaisa</code>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: JazzCash Official Gateway Credentials -->
          <section class="settings-card" aria-labelledby="heading-jazzcash">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-jazzcash" class="settings-card__title">JazzCash Automated Gateway Credentials (MWALLET)</h2>
                <p class="settings-card__desc">Official JazzCash HTTP POST / REST API credentials for automated mobile account MPIN push prompts.</p>
              </div>
            </div>
            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label for="set-jc-mode" class="settings-form-label">Gateway Environment</label>
                  <select id="set-jc-mode" name="jazzcash_mode" class="settings-form-input">
                    <option value="sandbox" <?= ($settings['jazzcash_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox / Test (sandbox.jazzcash.com.pk)</option>
                    <option value="live" <?= ($settings['jazzcash_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Production / Live (payments.jazzcash.com.pk)</option>
                  </select>
                </div>
                <div class="settings-form-group">
                  <label for="set-jc-mid" class="settings-form-label">JazzCash Merchant ID</label>
                  <input type="text" id="set-jc-mid" name="jazzcash_merchant_id" class="settings-form-input font-mono" placeholder="e.g. MC12345" value="<?= ea($settings['jazzcash_merchant_id'] ?? '') ?>">
                </div>
              </div>
              <div class="settings-grid-2 mt-3">
                <div class="settings-form-group">
                  <label for="set-jc-pwd" class="settings-form-label">Merchant Password</label>
                  <input type="password" id="set-jc-pwd" name="jazzcash_password" class="settings-form-input font-mono text-xs" placeholder="API Password" value="<?= ea($settings['jazzcash_password'] ?? '') ?>">
                </div>
                <div class="settings-form-group">
                  <label for="set-jc-salt" class="settings-form-label">Integrity Salt (HMAC-SHA256 Hash Key)</label>
                  <input type="password" id="set-jc-salt" name="jazzcash_integrity_salt" class="settings-form-input font-mono text-xs" placeholder="Secret Integrity Salt from JazzCash Portal" value="<?= ea($settings['jazzcash_integrity_salt'] ?? '') ?>">
                </div>
              </div>
              <div class="settings-callout settings-callout--brand mt-3">
                <div class="settings-callout__icon"><?= icon('shield', 16) ?></div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">JazzCash IPN &amp; Return Callback URLs:</strong>
                  <span class="settings-callout__desc">
                    IPN URL: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-700"><?= ea(\App\Core\Env::get('APP_URL', 'https://glass-cart.onrender.com')) ?>/webhook/jazzcash</code><br>
                    Return URL: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-700"><?= ea(\App\Core\Env::get('APP_URL', 'https://glass-cart.onrender.com')) ?>/payment/callback/jazzcash</code>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Bank Account & Raast Gateway Credentials (PayFast / SBP Aggregator) -->
          <section class="settings-card" aria-labelledby="heading-bank">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-bank" class="settings-card__title">Bank Account &amp; Raast Gateway Credentials</h2>
                <p class="settings-card__desc">
                  SBP-regulated online banking &amp; Raast real-time settlement gateway (PayFast / 1LINK).
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label for="set-pf-mode" class="settings-form-label">Gateway Environment</label>
                  <select id="set-pf-mode" name="payfast_mode" class="settings-form-input">
                    <option value="sandbox" <?= ($settings['payfast_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox / Staging (ipgtest.apps.net.pk)</option>
                    <option value="live" <?= ($settings['payfast_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Production / Live (ipg.apps.net.pk)</option>
                  </select>
                </div>
                <div class="settings-form-group">
                  <label for="set-pf-mid" class="settings-form-label">PayFast Merchant ID</label>
                  <input type="text" id="set-pf-mid" name="payfast_merchant_id" class="settings-form-input font-mono" placeholder="e.g. 10001" value="<?= ea($settings['payfast_merchant_id'] ?? '') ?>">
                </div>
              </div>

              <div class="settings-form-group mt-3">
                <label for="set-pf-key" class="settings-form-label">Secured API Key</label>
                <input type="password" id="set-pf-key" name="payfast_secured_key" class="settings-form-input font-mono text-xs" placeholder="Secured Key from PayFast Portal" value="<?= ea($settings['payfast_secured_key'] ?? '') ?>">
              </div>

              <div class="settings-callout settings-callout--brand mt-3">
                <div class="settings-callout__icon"><?= icon('shield', 16) ?></div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">PayFast Webhook Endpoint:</strong>
                  <span class="settings-callout__desc">
                    IPN URL: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-700"><?= ea(\App\Core\Env::get('APP_URL', 'https://glass-cart.onrender.com')) ?>/webhook/payfast</code>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Card: Card Payment Gateway (Stripe) -->
          <section class="settings-card" aria-labelledby="heading-stripe">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-stripe" class="settings-card__title">Visa &amp; Mastercard Payment Gateway</h2>
                <p class="settings-card__desc">Official PCI-DSS compliant card processing via Stripe hosted checkout.</p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <div class="settings-form-group">
                  <label for="set-stripe-mode" class="settings-form-label">Gateway Mode</label>
                  <select id="set-stripe-mode" name="stripe_mode" class="settings-form-input">
                    <option value="test" <?= ($settings['stripe_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test / Sandbox (Mock &amp; Safe Test Cards)</option>
                    <option value="live" <?= ($settings['stripe_mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live / Production (Real Card Processing)</option>
                  </select>
                </div>
                <div class="settings-form-group">
                  <label for="set-stripe-pub" class="settings-form-label">Stripe Publishable Key</label>
                  <input type="text" id="set-stripe-pub" name="stripe_publishable_key" class="settings-form-input font-mono text-xs" placeholder="pk_test_..." value="<?= ea($settings['stripe_publishable_key'] ?? '') ?>">
                </div>
              </div>

              <div class="settings-grid-2 mt-3">
                <div class="settings-form-group">
                  <label for="set-stripe-sec" class="settings-form-label">Stripe Secret Key</label>
                  <input type="password" id="set-stripe-sec" name="stripe_secret_key" class="settings-form-input font-mono text-xs" placeholder="sk_test_..." value="<?= ea($settings['stripe_secret_key'] ?? '') ?>">
                </div>
                <div class="settings-form-group">
                  <label for="set-stripe-wh" class="settings-form-label">Stripe Webhook Signing Secret</label>
                  <input type="password" id="set-stripe-wh" name="stripe_webhook_secret" class="settings-form-input font-mono text-xs" placeholder="whsec_..." value="<?= ea($settings['stripe_webhook_secret'] ?? '') ?>">
                </div>
              </div>

              <div class="settings-callout settings-callout--brand mt-4">
                <div class="settings-callout__icon"><?= icon('shield', 16) ?></div>
                <div class="settings-callout__content">
                  <strong class="settings-callout__title">Webhook Endpoint URL:</strong>
                  <span class="settings-callout__desc">
                    Register in your Stripe Dashboard &rarr; Webhooks: <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-700"><?= ea(\App\Core\Env::get('APP_URL', 'https://glass-cart.onrender.com')) ?>/webhook/stripe</code>
                  </span>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 3: Shipping & Delivery
             ===================================================================== -->
        <div data-tab-pane="shipping" id="pane-shipping" class="settings-pane <?= $activeTab === 'shipping' ? '' : 'hidden' ?>" role="tabpanel" aria-labelledby="tab-shipping">
          
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
                    Standard Flat Shipping Fee (<?= ea($settings['currency_symbol'] ?? 'Rs') ?>) <span class="text-rose-500">*</span>
                  </label>
                  <div class="settings-input-group">
                    <span class="settings-input-affix" aria-hidden="true"><?= ea($settings['currency_symbol'] ?? 'Rs') ?></span>
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
                    Free Shipping Cart Threshold (<?= ea($settings['currency_symbol'] ?? 'Rs') ?>) <span class="text-rose-500">*</span>
                  </label>
                  <div class="settings-input-group">
                    <span class="settings-input-affix" aria-hidden="true"><?= ea($settings['currency_symbol'] ?? 'Rs') ?></span>
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
        <div data-tab-pane="notifications" id="pane-notifications" class="settings-pane <?= $activeTab === 'notifications' ? '' : 'hidden' ?>" role="tabpanel" aria-labelledby="tab-notifications">
          
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
                    <strong class="settings-toggle-card__title">Customer Order Confirmations</strong>
                    <p class="settings-toggle-card__desc">Dispatch itemized digital invoices and settlement confirmation immediately upon payment completion.</p>
                  </div>
                  <label class="settings-switch" for="notif-order-confirm">
                    <input type="checkbox" id="notif-order-confirm" name="notif_order_confirm" value="1" <?= !empty($settings['notif_order_confirm']) ? 'checked' : '' ?> aria-label="Customer Order Confirmations">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Toggle 2: Shipment Dispatched -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <strong class="settings-toggle-card__title">Shipment Tracking &amp; Dispatch Notices</strong>
                    <p class="settings-toggle-card__desc">Automatically notify buyers with their courier tracking code when orders transition to dispatched status.</p>
                  </div>
                  <label class="settings-switch" for="notif-shipping-update">
                    <input type="checkbox" id="notif-shipping-update" name="notif_shipping_update" value="1" <?= !empty($settings['notif_shipping_update']) ? 'checked' : '' ?> aria-label="Shipment Tracking &amp; Dispatch Notices">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Toggle 3: Refund Notifications -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <strong class="settings-toggle-card__title">Refund &amp; Cancellation Advisories</strong>
                    <p class="settings-toggle-card__desc">Transmit credit adjustment documentation when an order is cancelled or refunded by administrators.</p>
                  </div>
                  <label class="settings-switch" for="notif-refund-advisory">
                    <input type="checkbox" id="notif-refund-advisory" name="notif_refund_advisory" value="1" <?= !empty($settings['notif_refund_advisory']) ? 'checked' : '' ?> aria-label="Refund &amp; Cancellation Advisories">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
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
                    <strong class="settings-toggle-card__title">Low Stock Telemetry Alerts</strong>
                    <p class="settings-toggle-card__desc">Notify administrative staff immediately when any product SKU stock falls below low inventory limit.</p>
                  </div>
                  <label class="settings-switch" for="notif-low-stock">
                    <input type="checkbox" id="notif-low-stock" name="notif_low_stock" value="1" <?= !empty($settings['notif_low_stock']) ? 'checked' : '' ?> aria-label="Low Stock Telemetry Alerts">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Toggle 5: Daily Order Summary -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <strong class="settings-toggle-card__title">Daily Morning Order Digest</strong>
                    <p class="settings-toggle-card__desc">Compile previous 24-hour gross volume, orders completed, and fulfillment queues into a daily digest.</p>
                  </div>
                  <label class="settings-switch" for="notif-daily-digest">
                    <input type="checkbox" id="notif-daily-digest" name="notif_daily_digest" value="1" <?= !empty($settings['notif_daily_digest']) ? 'checked' : '' ?> aria-label="Daily Morning Order Digest">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

              </div>

              <!-- Admin Operational Alerts Config -->
              <div class="settings-grid-2 mt-4 pt-4 border-t border-slate-100">
                <div class="settings-form-group">
                  <label for="set-low-stock-thresh" class="settings-form-label">
                    Low Stock Threshold (Units)
                  </label>
                  <input
                    type="number"
                    min="1"
                    max="100"
                    id="set-low-stock-thresh"
                    name="notif_low_stock_threshold"
                    class="settings-form-input font-mono"
                    value="<?= ea($settings['notif_low_stock_threshold'] ?? 3) ?>"
                  >
                  <p class="settings-form-helper">Dispatches stock restock alerts when available inventory drops to or below this quantity.</p>
                </div>

                <div class="settings-form-group">
                  <label for="set-notif-admin-email" class="settings-form-label">
                    Alert Dispatch Email Address
                  </label>
                  <input
                    type="email"
                    id="set-notif-admin-email"
                    name="notif_admin_email"
                    class="settings-form-input <?= !empty($errors['notif_admin_email']) ? 'settings-form-input--error' : '' ?>"
                    placeholder="admin@glasscart.local"
                    value="<?= ea($settings['notif_admin_email'] ?? ($settings['contact_email'] ?? '')) ?>"
                  >
                  <p class="settings-form-helper">Primary inbox where stock threshold warnings and operational reports are routed.</p>
                  <?php if (!empty($errors['notif_admin_email'])): ?>
                    <span class="settings-field-error"><?= e($errors['notif_admin_email']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- =====================================================================
             TAB 5: API & Security
             ===================================================================== -->
        <div data-tab-pane="security" id="pane-security" class="settings-pane <?= $activeTab === 'security' ? '' : 'hidden' ?>" role="tabpanel" aria-labelledby="tab-security">
          
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

          <!-- Card: Security & Session Policies -->
          <section class="settings-card" aria-labelledby="heading-policies">
            <div class="settings-card__header">
              <div>
                <h2 id="heading-policies" class="settings-card__title">Storefront Security &amp; Access Policies</h2>
                <p class="settings-card__desc">
                  Authentication limits, session lifecycle expiration, maintenance mode, and transport encryption.
                </p>
              </div>
            </div>

            <div class="settings-card__body">
              <div class="settings-grid-2">
                <!-- Session Timeout -->
                <div class="settings-form-group">
                  <label for="set-session-timeout" class="settings-form-label">Admin Session Inactivity Timeout</label>
                  <select id="set-session-timeout" name="security_session_timeout" class="settings-form-input">
                    <option value="30" <?= ((int)($settings['security_session_timeout'] ?? 120)) === 30 ? 'selected' : '' ?>>30 Minutes</option>
                    <option value="60" <?= ((int)($settings['security_session_timeout'] ?? 120)) === 60 ? 'selected' : '' ?>>1 Hour</option>
                    <option value="120" <?= ((int)($settings['security_session_timeout'] ?? 120)) === 120 ? 'selected' : '' ?>>2 Hours (Recommended)</option>
                    <option value="240" <?= ((int)($settings['security_session_timeout'] ?? 120)) === 240 ? 'selected' : '' ?>>4 Hours</option>
                    <option value="480" <?= ((int)($settings['security_session_timeout'] ?? 120)) === 480 ? 'selected' : '' ?>>8 Hours</option>
                  </select>
                  <p class="settings-form-helper">Inactivity interval before an administrator session requires re-authentication.</p>
                </div>

                <!-- Max Login Attempts -->
                <div class="settings-form-group">
                  <label for="set-max-attempts" class="settings-form-label">Max Login Attempts Rate Limiting</label>
                  <input
                    type="number"
                    min="3"
                    max="20"
                    id="set-max-attempts"
                    name="security_max_login_attempts"
                    class="settings-form-input font-mono"
                    value="<?= ea($settings['security_max_login_attempts'] ?? 5) ?>"
                  >
                  <p class="settings-form-helper">Failed authentication trials before an IP is throttled for 15 minutes.</p>
                </div>
              </div>

              <div class="settings-toggle-stack mt-4 pt-4 border-t border-slate-100">
                <!-- Maintenance Mode -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <strong class="settings-toggle-card__title">Storefront Maintenance Mode</strong>
                    <p class="settings-toggle-card__desc">Temporarily pause public checkout and show a scheduled maintenance advisory to visitors.</p>
                  </div>
                  <label class="settings-switch" for="set-maint-mode">
                    <input type="checkbox" id="set-maint-mode" name="security_maintenance_mode" value="1" <?= !empty($settings['security_maintenance_mode']) ? 'checked' : '' ?> aria-label="Toggle Storefront Maintenance Mode">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
                </div>

                <!-- Enforce Strict SSL & Secure Cookies -->
                <div class="settings-toggle-card">
                  <div class="settings-toggle-card__info">
                    <strong class="settings-toggle-card__title">Strict HTTPS &amp; Cookie Security Flags</strong>
                    <p class="settings-toggle-card__desc">Enforce HSTS, SameSite=Lax, and Secure cookie attributes across all customer requests.</p>
                  </div>
                  <label class="settings-switch" for="set-strict-ssl">
                    <input type="checkbox" id="set-strict-ssl" name="security_strict_ssl" value="1" <?= !empty($settings['security_strict_ssl']) ? 'checked' : '' ?> aria-label="Toggle Strict HTTPS &amp; Cookie Security Flags">
                    <span class="settings-switch__slider" aria-hidden="true"></span>
                  </label>
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
                    type="text"
                    id="api-webhook-secret"
                    name="api_webhook_secret"
                    class="settings-form-input font-mono text-xs"
                    value="<?= ea($settings['api_webhook_secret'] ?? 'whsec_98fbc184013490aa8efb019349cde012') ?>"
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

<script>
(function() {
  function activateSettingsTab(targetId) {
    if (!targetId) return;
    targetId = targetId.replace(/^#/, '');

    const panes = document.querySelectorAll('.settings-pane');
    const navItems = document.querySelectorAll('.settings-nav-item, .settings-tab');
    if (!panes.length || !navItems.length) return;

    let matched = false;
    panes.forEach(function(pane) {
      if (pane.getAttribute('data-tab-pane') === targetId) {
        pane.classList.remove('hidden');
        pane.style.display = 'block';
        matched = true;
      } else {
        pane.classList.add('hidden');
        pane.style.display = 'none';
      }
    });

    if (matched) {
      navItems.forEach(function(item) {
        const itemTarget = item.getAttribute('data-tab-target');
        const isActive = (itemTarget === targetId);

        if (item.classList.contains('settings-nav-item')) {
          item.classList.toggle('settings-nav-item--active', isActive);
          item.setAttribute('aria-selected', isActive ? 'true' : 'false');
          if (isActive && window.innerWidth <= 992) {
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
          }
        }

        if (item.classList.contains('settings-tab')) {
          if (isActive) {
            item.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
            item.classList.remove('text-slate-600', 'font-medium');
          } else {
            item.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
            item.classList.add('text-slate-600', 'hover:text-slate-900', 'font-medium');
          }
        }
      });

      const activeInput = document.getElementById('settings-active-tab');
      if (activeInput) {
        activeInput.value = targetId;
      }

      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '#' + targetId);
      }
    }
  }

  // Make globally available
  window.switchSettingsTab = activateSettingsTab;

  function initSettingsTabs() {
    // Attach click handlers to all settings tabs
    document.querySelectorAll('.settings-nav-item, .settings-tab').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const target = this.getAttribute('data-tab-target');
        activateSettingsTab(target);
      });
    });

    // Check URL hash or server-rendered active tab
    const urlHash = (window.location.hash || '').replace('#', '');
    const defaultTab = '<?= ea($activeTab ?? "general") ?>';
    const initialTarget = urlHash || defaultTab;
    if (initialTarget) {
      activateSettingsTab(initialTarget);
    }

    // Listen for browser forward/back buttons
    window.addEventListener('hashchange', function() {
      const hash = (window.location.hash || '').replace('#', '');
      if (hash) {
        activateSettingsTab(hash);
      }
    });

    // Copy to clipboard helpers
    document.querySelectorAll('[data-copy-target]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-copy-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const val = input.value || input.innerText;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(val).then(function() {
            const orig = btn.innerHTML;
            btn.innerHTML = '<span>Copied!</span>';
            btn.style.color = '#10b981';
            btn.style.borderColor = '#10b981';
            setTimeout(function() {
              btn.innerHTML = orig;
              btn.style.color = '';
              btn.style.borderColor = '';
            }, 2000);
          });
        } else {
          input.select();
          document.execCommand('copy');
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSettingsTabs);
  } else {
    initSettingsTabs();
  }
})();
</script>
