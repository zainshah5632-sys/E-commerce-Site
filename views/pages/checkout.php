<?php
declare(strict_types=1);

use App\Core\Session;

$summary = $summary ?? [];
$items = $summary['items'] ?? [];
$subtotal = $summary['subtotal_cents'] ?? 0;
$shipping = $summary['shipping_cents'] ?? 0;
$total = $summary['total_cents'] ?? 0;
$errors = $errors ?? [];
$userProfile = $userProfile ?? null;

$defaultName = old('customer_name', $userProfile['full_name'] ?? Session::instance()->userName());
$defaultEmail = old('email', Session::instance()->userEmail());
$defaultPhone = old('phone', $userProfile['phone'] ?? '');
$defaultCountryCode = old('country_code', $userProfile['country_code'] ?? '+1');
?>

<div class="checkout-container max-w-6xl mx-auto px-4 py-8">
  
  <!-- Header with Security Shield Icon -->
  <h1 class="checkout-page-title text-3xl font-extrabold tracking-tight text-slate-900 mb-8 flex items-center gap-2.5">
    <?= icon('shield', 28) ?>
    <span>Secure Checkout</span>
  </h1>

  <?php if (!empty($errors['checkout'])): ?>
    <div class="alert alert--error glass--strong mb-6 rounded-xl p-4">
      <span><?= e($errors['checkout']) ?></span>
    </div>
  <?php endif; ?>

  <div class="checkout-grid-layout grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- Left Column: Form Steps -->
    <div class="checkout-main-col lg:col-span-7">
      <form action="<?= url('/checkout') ?>" method="POST" id="checkout-form">
        <?= csrf_field() ?>

        <!-- Step 1: Contact Details -->
        <div class="checkout-step-card bg-white rounded-2xl border border-slate-200/80 p-6 md:p-8 shadow-sm mb-6">
          <h2 class="checkout-step-title text-lg font-bold text-slate-900 flex items-center gap-3 border-b border-slate-100 pb-3.5">
            <span class="checkout-step-badge">1</span>
            <span>Contact Details</span>
          </h2>

          <div class="space-y-5">
            <!-- Full Name -->
            <div>
              <label for="cust-name" class="checkout-field-label">
                Full Name
              </label>
              <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 flex items-center pointer-events-none"><?= icon('user', 18) ?></span>
                <input
                  type="text"
                  id="cust-name"
                  name="customer_name"
                  class="checkout-field-input w-full h-11 pl-9 pr-3.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
                  value="<?= ea($defaultName) ?>"
                  required
                  autocomplete="name"
                  data-alpha-only="true"
                  pattern="[A-Za-z\s\-']+"
                  title="Alphabets only, no numbers allowed"
                  placeholder="e.g. Eleanor Vance"
                >
              </div>
              <p class="checkout-field-helper">Alphabets only. Numbers are prohibited.</p>
              <?php if (!empty($errors['customer_name'])): ?>
                <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['customer_name']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Email Address -->
            <div>
              <label for="cust-email" class="checkout-field-label">
                Email Address
              </label>
              <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 flex items-center pointer-events-none"><?= icon('mail', 18) ?></span>
                <input
                  type="email"
                  id="cust-email"
                  name="email"
                  class="checkout-field-input w-full h-11 pl-9 pr-3.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
                  value="<?= ea($defaultEmail) ?>"
                  required
                  autocomplete="email"
                  placeholder="eleanor@example.com"
                >
              </div>
              <p class="checkout-field-helper">Order receipt and shipment tracking will be sent here.</p>
              <?php if (!empty($errors['email'])): ?>
                <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['email']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Phone Number (Merged Group) -->
            <div>
              <label for="cust-phone" class="checkout-field-label">
                Phone Number
              </label>
              <div class="phone-merged-group">
                <select name="country_code" id="cust-country-code" class="phone-merged-select" aria-label="Country Code">
                  <option value="+1" <?= $defaultCountryCode === '+1' ? 'selected' : '' ?>>+1 (US/CA)</option>
                  <option value="+44" <?= $defaultCountryCode === '+44' ? 'selected' : '' ?>>+44 (UK)</option>
                  <option value="+92" <?= $defaultCountryCode === '+92' ? 'selected' : '' ?>>+92 (PK)</option>
                  <option value="+91" <?= $defaultCountryCode === '+91' ? 'selected' : '' ?>>+91 (IN)</option>
                  <option value="+971" <?= $defaultCountryCode === '+971' ? 'selected' : '' ?>>+971 (AE)</option>
                  <option value="+966" <?= $defaultCountryCode === '+966' ? 'selected' : '' ?>>+966 (SA)</option>
                  <option value="+61" <?= $defaultCountryCode === '+61' ? 'selected' : '' ?>>+61 (AU)</option>
                  <option value="+49" <?= $defaultCountryCode === '+49' ? 'selected' : '' ?>>+49 (DE)</option>
                  <option value="+33" <?= $defaultCountryCode === '+33' ? 'selected' : '' ?>>+33 (FR)</option>
                  <option value="+81" <?= $defaultCountryCode === '+81' ? 'selected' : '' ?>>+81 (JP)</option>
                  <option value="+86" <?= $defaultCountryCode === '+86' ? 'selected' : '' ?>>+86 (CN)</option>
                  <option value="+90" <?= $defaultCountryCode === '+90' ? 'selected' : '' ?>>+90 (TR)</option>
                  <option value="+60" <?= $defaultCountryCode === '+60' ? 'selected' : '' ?>>+60 (MY)</option>
                  <option value="+65" <?= $defaultCountryCode === '+65' ? 'selected' : '' ?>>+65 (SG)</option>
                </select>
                <div class="phone-merged-input-wrap">
                  <span class="input-icon"><?= icon('phone', 16) ?></span>
                  <input
                    type="tel"
                    id="cust-phone"
                    name="phone"
                    class="phone-merged-input"
                    value="<?= ea($defaultPhone) ?>"
                    required
                    data-phone-11="true"
                    maxlength="11"
                    minlength="11"
                    pattern="[0-9]{11}"
                    placeholder="03001234567"
                    autocomplete="tel"
                  >
                </div>
              </div>
              <p class="checkout-field-helper">Country code + exactly 11 digits (e.g. 03001234567).</p>
              <?php if (!empty($errors['phone'])): ?>
                <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['phone']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Step 2: Shipping Address -->
        <div class="checkout-step-card bg-white rounded-2xl border border-slate-200/80 p-6 md:p-8 shadow-sm mb-6">
          <h2 class="checkout-step-title text-lg font-bold text-slate-900 flex items-center gap-3 border-b border-slate-100 pb-3.5">
            <span class="checkout-step-badge">2</span>
            <span>Shipping Address</span>
          </h2>

          <div class="space-y-5">
            <!-- Street Address Line 1 -->
            <div>
              <label for="addr-1" class="checkout-field-label">
                Street Address
              </label>
              <input
                type="text"
                id="addr-1"
                name="address_line_1"
                class="checkout-field-input"
                value="<?= ea(old('address_line_1')) ?>"
                required
                placeholder="123 Innovation Blvd, Suite 400"
                autocomplete="address-line1"
              >
              <?php if (!empty($errors['address_line_1'])): ?>
                <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['address_line_1']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Street Address Line 2 (Optional) -->
            <div>
              <label for="addr-2" class="checkout-field-label">
                Apartment, Suite, Unit <span class="text-slate-400 normal-case font-normal">(Optional)</span>
              </label>
              <input
                type="text"
                id="addr-2"
                name="address_line_2"
                class="checkout-field-input"
                value="<?= ea(old('address_line_2')) ?>"
                placeholder="Apt 4B or Building C"
                autocomplete="address-line2"
              >
            </div>

            <!-- City, State, Postal Code (3-column responsive row) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label for="addr-city" class="checkout-field-label">
                  City
                </label>
                <input
                  type="text"
                  id="addr-city"
                  name="city"
                  class="checkout-field-input"
                  value="<?= ea(old('city')) ?>"
                  required
                  placeholder="New York"
                  autocomplete="address-level2"
                >
                <?php if (!empty($errors['city'])): ?>
                  <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['city']) ?></span>
                <?php endif; ?>
              </div>

              <div>
                <label for="addr-state" class="checkout-field-label">
                  State / Prov
                </label>
                <input
                  type="text"
                  id="addr-state"
                  name="state"
                  class="checkout-field-input"
                  value="<?= ea(old('state')) ?>"
                  required
                  placeholder="NY"
                  autocomplete="address-level1"
                >
                <?php if (!empty($errors['state'])): ?>
                  <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['state']) ?></span>
                <?php endif; ?>
              </div>

              <div>
                <label for="addr-zip" class="checkout-field-label">
                  Postal Code
                </label>
                <input
                  type="text"
                  id="addr-zip"
                  name="postal_code"
                  class="checkout-field-input"
                  value="<?= ea(old('postal_code')) ?>"
                  required
                  placeholder="10001"
                  autocomplete="postal-code"
                >
                <?php if (!empty($errors['postal_code'])): ?>
                  <span class="form-error text-xs text-rose-600 mt-1 block"><?= e($errors['postal_code']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Payment Method -->
        <div class="checkout-step-card bg-white rounded-2xl border border-slate-200/80 p-6 md:p-8 shadow-sm mb-6">
          <h2 class="checkout-step-title text-lg font-bold text-slate-900 flex items-center gap-3 border-b border-slate-100 pb-3.5">
            <span class="checkout-step-badge">3</span>
            <span>Payment Method</span>
          </h2>

          <div class="space-y-5">
            <div class="checkout-payment-grid">
              <label class="checkout-payment-card">
                <input type="radio" name="payment_method" value="cod" <?= old('payment_method', 'cod') === 'cod' ? 'checked' : '' ?> class="mt-0.5 text-indigo-600">
                <div>
                  <div class="text-sm font-bold text-slate-900">Cash on Delivery (COD)</div>
                  <div class="text-xs text-slate-500 mt-0.5">Pay securely with cash upon package receipt.</div>
                </div>
              </label>

              <label class="checkout-payment-card">
                <input type="radio" name="payment_method" value="bank_transfer" <?= old('payment_method') === 'bank_transfer' ? 'checked' : '' ?> class="mt-0.5 text-indigo-600">
                <div>
                  <div class="text-sm font-bold text-slate-900">Bank Wire Transfer</div>
                  <div class="text-xs text-slate-500 mt-0.5">Direct deposit instructions provided on order receipt.</div>
                </div>
              </label>
            </div>

            <!-- Optional Order Notes -->
            <div>
              <label for="order-notes" class="checkout-field-label">
                Order Notes <span class="text-slate-400 normal-case font-normal">(Optional)</span>
              </label>
              <textarea
                id="order-notes"
                name="notes"
                rows="2"
                class="checkout-field-input checkout-field-textarea"
                placeholder="Special delivery instructions or gate access code..."
              ><?= e(old('notes')) ?></textarea>
            </div>

            <!-- Place Order CTA Button -->
            <button type="submit" class="checkout-submit-btn">
              <span>Place Order (<?= money($total) ?>)</span>
              <?= icon('arrow-right', 16) ?>
            </button>
          </div>
        </div>

      </form>
    </div>

    <!-- Right Column: Sticky Order Summary Card -->
    <div class="checkout-sidebar-col lg:col-span-5">
      <div class="checkout-summary-card">
        
        <!-- Summary Header -->
        <div class="checkout-summary-header">
          <span>Order Summary</span>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
            <?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?>
          </span>
        </div>

        <!-- Products List -->
        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto pr-1 my-1">
          <?php foreach ($items as $item): ?>
            <div class="checkout-item-row">
              <img
                src="<?= e($item['image_url'] ?? asset('assets/img/placeholder.svg')) ?>"
                alt=""
                width="56"
                height="56"
                class="checkout-item-thumb"
              >
              <div class="flex-1 min-w-0 pr-2">
                <div class="checkout-item-title" title="<?= e($item['name']) ?>">
                  <?= e($item['name']) ?>
                </div>
                <div class="checkout-item-qty">
                  Qty: <?= (int)$item['quantity'] ?>
                </div>
              </div>
              <div class="checkout-item-price">
                <?= money($item['line_total_cents']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Totals Breakdown -->
        <div class="pt-4 border-t border-slate-100 space-y-1">
          <div class="checkout-total-row">
            <span>Subtotal</span>
            <span class="font-medium text-slate-800"><?= money($subtotal) ?></span>
          </div>
          <div class="checkout-total-row">
            <span>Shipping</span>
            <span>
              <?= $shipping === 0 ? '<strong class="text-emerald-600 font-semibold">Free</strong>' : money($shipping) ?>
            </span>
          </div>
          <div class="checkout-grand-total">
            <span>Total</span>
            <span><?= money($total) ?></span>
          </div>
        </div>

        <!-- Security Guarantee Microcopy -->
        <div class="checkout-security-badge">
          <?= icon('shield', 14) ?>
          <span>256-Bit SSL Encrypted Checkout</span>
        </div>

      </div>
    </div>

  </div>
</div>
