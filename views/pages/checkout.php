<?php
declare(strict_types=1);

use App\Core\Session;
use App\Repositories\SettingsRepository;

$summary = $summary ?? [];
$items = $summary['items'] ?? [];
$subtotal = $summary['subtotal_cents'] ?? 0;
$shipping = $summary['shipping_cents'] ?? 0;
$total = $summary['total_cents'] ?? 0;
$errors = $errors ?? [];
$userProfile = $userProfile ?? null;

$settings = $settings ?? SettingsRepository::all();
$enabledMethods = $enabledMethods ?? SettingsRepository::enabledPaymentMethods();
if (empty($enabledMethods)) {
    $enabledMethods = ['cod'];
}

$defaultName = old('customer_name', $userProfile['full_name'] ?? Session::instance()->userName());
$defaultEmail = old('email', Session::instance()->userEmail());
$defaultPhone = old('phone', $userProfile['phone'] ?? '');
$defaultCountryCode = old('country_code', $userProfile['country_code'] ?? '+1');
$selectedPayment = old('payment_method', in_array('cod', $enabledMethods, true) ? 'cod' : $enabledMethods[0]);
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
      <form action="<?= url('/checkout') ?>" method="POST" enctype="multipart/form-data" id="checkout-form">
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
            <?php if (!empty($errors['payment_method'])): ?>
              <div class="alert alert--error mb-2 p-3 text-xs rounded-lg">
                <span><?= e($errors['payment_method']) ?></span>
              </div>
            <?php endif; ?>

            <!-- Payment Methods Grid with Official Brand Badges -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="payment-methods-grid">
              
              <!-- 1. Cash on Delivery (COD) -->
              <?php if (in_array('cod', $enabledMethods, true)): ?>
                <label class="payment-rail-card">
                  <input type="radio" name="payment_method" value="cod" <?= $selectedPayment === 'cod' ? 'checked' : '' ?> class="mt-1 text-indigo-600 focus:ring-indigo-500">
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <strong class="text-sm font-bold text-slate-900">Cash on Delivery</strong>
                      <span class="payment-brand-badge payment-brand-badge--cod" title="COD Delivery">
                        <?= icon('truck', 16) ?>
                      </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Pay in cash directly upon physical delivery at your door.</p>
                  </div>
                </label>
              <?php endif; ?>

              <!-- 2. Easypaisa Mobile Wallet -->
              <?php if (in_array('easypaisa', $enabledMethods, true)): ?>
                <label class="payment-rail-card">
                  <input type="radio" name="payment_method" value="easypaisa" <?= $selectedPayment === 'easypaisa' ? 'checked' : '' ?> class="mt-1 text-indigo-600 focus:ring-indigo-500">
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <strong class="text-sm font-bold text-slate-900">Easypaisa</strong>
                      <span class="payment-brand-badge payment-brand-badge--easypaisa" title="Easypaisa">EP</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Instant transfer via Easypaisa Mobile App or USSD.</p>
                  </div>
                </label>
              <?php endif; ?>

              <!-- 3. JazzCash Mobile Account -->
              <?php if (in_array('jazzcash', $enabledMethods, true)): ?>
                <label class="payment-rail-card">
                  <input type="radio" name="payment_method" value="jazzcash" <?= $selectedPayment === 'jazzcash' ? 'checked' : '' ?> class="mt-1 text-indigo-600 focus:ring-indigo-500">
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <strong class="text-sm font-bold text-slate-900">JazzCash</strong>
                      <span class="payment-brand-badge payment-brand-badge--jazzcash" title="JazzCash">JC</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Instant transfer via JazzCash App or *786# menu.</p>
                  </div>
                </label>
              <?php endif; ?>

              <!-- 4. Direct Bank Transfer / IBAN -->
              <?php if (in_array('bank_transfer', $enabledMethods, true)): ?>
                <label class="payment-rail-card">
                  <input type="radio" name="payment_method" value="bank_transfer" <?= $selectedPayment === 'bank_transfer' ? 'checked' : '' ?> class="mt-1 text-indigo-600 focus:ring-indigo-500">
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <strong class="text-sm font-bold text-slate-900">Bank Transfer / Raast</strong>
                      <span class="payment-brand-badge payment-brand-badge--bank" title="Bank Transfer & Raast">
                        <?= icon('store', 16) ?>
                      </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Direct wire transfer via Online Banking, ATM, or Raast.</p>
                  </div>
                </label>
              <?php endif; ?>

              <!-- 5. Visa and Mastercard (Debit/Credit Cards) -->
              <?php if (in_array('card', $enabledMethods, true)): ?>
                <label class="payment-rail-card">
                  <input type="radio" name="payment_method" value="card" <?= $selectedPayment === 'card' ? 'checked' : '' ?> class="mt-1 text-indigo-600 focus:ring-indigo-500">
                  <div class="flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <strong class="text-sm font-bold text-slate-900">Cards (Visa / Mastercard)</strong>
                      <div class="flex items-center gap-1">
                        <svg width="24" height="15" viewBox="0 0 32 20" fill="none"><rect width="32" height="20" rx="3" fill="#1A1F71"/><path d="M12.5 14.5L14.8 5.5H16.9L14.6 14.5H12.5ZM21.3 5.7C20.9 5.5 20.2 5.3 19.3 5.3C17.2 5.3 15.7 6.4 15.7 7.9C15.7 9.1 16.8 9.7 17.6 10.1C18.4 10.5 18.7 10.8 18.7 11.2C18.7 11.8 18 12.2 17.1 12.2C16.1 12.2 15.5 12 14.7 11.6L14.4 11.5L14.1 13.5C14.7 13.8 15.8 14.1 16.9 14.1C19.2 14.1 20.7 13 20.7 11.3C20.7 9.8 19.6 9.3 18.6 8.8C18 8.5 17.6 8.3 17.6 7.9C17.6 7.5 18 7.1 18.8 7.1C19.6 7.1 20.2 7.3 20.7 7.5L20.9 7.6L21.3 5.7ZM25.4 5.5H23.7C23.1 5.5 22.7 5.7 22.4 6.3L19.3 14.5H21.5L22 13.1H24.6L24.9 14.5H26.9L25.4 5.5ZM22.5 11.5L23.6 8.2L24.2 11.5H22.5ZM11.9 5.5L9.8 11.7L9.5 10.4C9.1 9.1 7.9 7.7 6.6 7L8.5 14.5H10.7L14.1 5.5H11.9Z" fill="white"/><path d="M7.7 5.5H4.5L4.5 5.7C7 6.3 9.1 8 9.5 10.4L8.8 6.3C8.6 5.7 8.2 5.5 7.7 5.5Z" fill="#F7B600"/></svg>
                        <svg width="24" height="15" viewBox="0 0 32 20" fill="none"><rect width="32" height="20" rx="3" fill="#0A0A0A"/><circle cx="12" cy="10" r="6" fill="#EB001B"/><circle cx="20" cy="10" r="6" fill="#F79E1B" fill-opacity="0.9"/></svg>
                      </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Official encrypted payment gateway. Zero card numbers stored.</p>
                  </div>
                </label>
              <?php endif; ?>

            </div>

            <!-- Dynamic Payment Details Panels -->
            
            <!-- Panel: Easypaisa (Automated Mobile Wallet Debit) -->
            <div id="pane-easypaisa" class="payment-detail-pane <?= $selectedPayment === 'easypaisa' ? 'is-active' : '' ?>">
              <div class="payment-info-box">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 font-mono">Easypaisa Real-Time Wallet Debit</span>
                  <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold flex items-center gap-1">
                    <?= icon('shield', 12) ?>
                    <span>Official Gateway</span>
                  </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-3">
                  Direct automated wallet settlement. Enter your registered Easypaisa mobile number below. When you click <strong>Pay via Easypaisa</strong>, an automated approval push notification or OTP request will be dispatched to your Easypaisa mobile app.
                </p>
                <div class="payment-data-row">
                  <span class="text-slate-600">Payable Amount:</span>
                  <strong class="text-slate-900 font-mono text-sm"><?= money($total) ?></strong>
                </div>
              </div>

              <!-- Automated Mobile Number Input -->
              <div class="mb-3">
                <label for="ep-mobile" class="checkout-field-label">
                  Registered Easypaisa Mobile Number <span class="text-rose-500">*</span>
                </label>
                <input
                  type="tel"
                  id="ep-mobile"
                  name="easypaisa_mobile"
                  class="checkout-field-input font-mono"
                  placeholder="03XXXXXXXXX"
                  maxlength="11"
                  value="<?= ea(old('easypaisa_mobile')) ?>"
                >
                <span class="checkout-field-helper">Enter the 11-digit mobile number associated with your active Easypaisa account.</span>
              </div>

              <div class="rounded-lg bg-emerald-50 border border-emerald-200/80 p-3 text-xs text-emerald-950 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-emerald-800">
                  <?= icon('check', 14) ?>
                  <span>Automated Instant Settlement Flow:</span>
                </div>
                <ol class="list-decimal list-inside space-y-1 text-slate-700 pl-1">
                  <li>Click <strong>Pay via Easypaisa</strong> below.</li>
                  <li>An authorization push prompt will pop up on your Easypaisa phone/app.</li>
                  <li>Enter your 5-digit secret PIN on your mobile screen to approve.</li>
                  <li>Your order will instantly confirm online without manual verification.</li>
                </ol>
              </div>
            </div>

            <!-- Panel: JazzCash (Automated MWALLET & MPIN Push) -->
            <div id="pane-jazzcash" class="payment-detail-pane <?= $selectedPayment === 'jazzcash' ? 'is-active' : '' ?>">
              <div class="payment-info-box">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-amber-700 font-mono">JazzCash Mobile Account (MWALLET)</span>
                  <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-semibold flex items-center gap-1">
                    <?= icon('shield', 12) ?>
                    <span>Official Gateway</span>
                  </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-3">
                  Real-time direct mobile account debit. Enter your registered JazzCash mobile number and the last 6 digits of your CNIC. An automated USSD pop-up will appear directly on your phone requesting your MPIN.
                </p>
                <div class="payment-data-row">
                  <span class="text-slate-600">Payable Amount:</span>
                  <strong class="text-slate-900 font-mono text-sm"><?= money($total) ?></strong>
                </div>
              </div>

              <!-- Automated Inputs: Mobile & CNIC -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <div>
                  <label for="jc-mobile" class="checkout-field-label">
                    Registered JazzCash Mobile Number <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="tel"
                    id="jc-mobile"
                    name="jazzcash_mobile"
                    class="checkout-field-input font-mono"
                    placeholder="03XXXXXXXXX"
                    maxlength="11"
                    value="<?= ea(old('jazzcash_mobile')) ?>"
                  >
                  <span class="checkout-field-helper">11-digit JazzCash mobile number.</span>
                </div>
                <div>
                  <label for="jc-cnic" class="checkout-field-label">
                    CNIC (Last 6 Digits) <span class="text-rose-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="jc-cnic"
                    name="jazzcash_cnic"
                    class="checkout-field-input font-mono"
                    placeholder="e.g. 123456"
                    maxlength="6"
                    value="<?= ea(old('jazzcash_cnic')) ?>"
                  >
                  <span class="checkout-field-helper">State Bank requirement for MPIN 2FA.</span>
                </div>
              </div>

              <div class="rounded-lg bg-amber-50 border border-amber-200/80 p-3 text-xs text-amber-950 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-amber-800">
                  <?= icon('check', 14) ?>
                  <span>Automated MPIN Approval Flow:</span>
                </div>
                <ol class="list-decimal list-inside space-y-1 text-slate-700 pl-1">
                  <li>Click <strong>Pay via JazzCash</strong> below.</li>
                  <li>A USSD prompt or push notification will pop up on your mobile handset.</li>
                  <li>Enter your 4-digit MPIN on your handset screen to authorize.</li>
                  <li>Your payment is verified and confirmed automatically in real-time.</li>
                </ol>
              </div>
            </div>

            <!-- Panel: Direct Bank Transfer / Raast (Real-Time Online Banking) -->
            <div id="pane-bank_transfer" class="payment-detail-pane <?= $selectedPayment === 'bank_transfer' ? 'is-active' : '' ?>">
              <div class="payment-info-box">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 font-mono">Real-Time Online Banking &amp; Raast</span>
                  <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded font-semibold flex items-center gap-1">
                    <?= icon('shield', 12) ?>
                    <span>SBP Regulated Gateway</span>
                  </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-3">
                  Direct automated bank debit supporting 40+ Pakistani commercial and Islamic banks via 1LINK &amp; SBP Raast. No manual bank numbers to copy and zero receipt uploads.
                </p>
                <div class="payment-data-row">
                  <span class="text-slate-600">Payable Amount:</span>
                  <strong class="text-slate-900 font-mono text-sm"><?= money($total) ?></strong>
                </div>
              </div>

              <div class="rounded-lg bg-indigo-50 border border-indigo-200/80 p-3 text-xs text-indigo-950 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-indigo-800">
                  <?= icon('check', 14) ?>
                  <span>Automated Online Banking Process:</span>
                </div>
                <ol class="list-decimal list-inside space-y-1 text-slate-700 pl-1">
                  <li>Click <strong>Pay via Bank / Raast</strong> below.</li>
                  <li>You will be securely redirected to the SBP-regulated gateway portal.</li>
                  <li>Authorize the transaction with your bank's instant SMS / email OTP.</li>
                  <li>Settlement is verified automatically and your order confirms immediately.</li>
                </ol>
              </div>
            </div>

            <!-- Panel: Visa & Mastercard (Official 3D Secure Hosted Checkout) -->
            <div id="pane-card" class="payment-detail-pane <?= $selectedPayment === 'card' ? 'is-active' : '' ?>">
              <div class="payment-info-box">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-700 font-mono">Visa &amp; Mastercard (Debit &amp; Credit)</span>
                  <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold flex items-center gap-1">
                    <?= icon('shield', 12) ?>
                    <span>PCI-DSS Level 1</span>
                  </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-3">
                  Fast and secure online card checkout. You will be redirected to the official encrypted checkout gateway to authenticate with your bank's 3D Secure SMS OTP.
                </p>
                <div class="payment-data-row">
                  <span class="text-slate-600">Payable Amount:</span>
                  <strong class="text-slate-900 font-mono text-sm"><?= money($total) ?></strong>
                </div>
              </div>

              <div class="rounded-lg bg-slate-50 border border-slate-200/80 p-3 text-xs text-slate-700 space-y-1">
                <div class="font-bold flex items-center gap-1.5 text-slate-900">
                  <?= icon('lock', 14) ?>
                  <span>Bank-Grade Card Security:</span>
                </div>
                <p class="text-slate-600 mt-1 leading-relaxed">
                  GlassCart never collects or stores card numbers, expiration dates, PINs, or CVV security codes. Transactions are processed over end-to-end 256-bit encrypted channels directly with your card issuer.
                </p>
              </div>
            </div>

            <!-- Panel: Cash on Delivery (COD) -->
            <div id="pane-cod" class="payment-detail-pane <?= $selectedPayment === 'cod' ? 'is-active' : '' ?>">
              <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200/60 text-xs text-emerald-900 leading-relaxed">
                <div class="flex items-center gap-2 font-bold text-emerald-800 mb-1">
                  <?= icon('check', 16) ?>
                  <span>Cash on Delivery Selected</span>
                </div>
                <p>No advance online payment required. You will pay the courier in cash when your package arrives at your delivery destination.</p>
                <div class="payment-data-row mt-2">
                  <span class="text-slate-600">Total Payable upon Delivery:</span>
                  <strong class="text-slate-900 font-mono text-sm"><?= money($total) ?></strong>
                </div>
              </div>
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
            <button type="submit" class="checkout-submit-btn" id="checkout-submit-btn">
              <span id="submit-btn-text">Pay Now (<?= money($total) ?>)</span>
              <?= icon('arrow-right', 16) ?>
            </button>
          </div>
        </div>

        <!-- Dynamic Payment Client-Side Switching & Submit Label Handler -->
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            var radios = document.querySelectorAll('input[name="payment_method"]');
            var btnText = document.getElementById('submit-btn-text');
            var totalStr = '<?= ea(money($total)) ?>';

            var panes = {
              'cod': document.getElementById('pane-cod'),
              'easypaisa': document.getElementById('pane-easypaisa'),
              'jazzcash': document.getElementById('pane-jazzcash'),
              'bank_transfer': document.getElementById('pane-bank_transfer'),
              'card': document.getElementById('pane-card')
            };

            var labels = {
              'cod': 'Place Order & Pay on Delivery (' + totalStr + ')',
              'easypaisa': 'Pay via Easypaisa (' + totalStr + ')',
              'jazzcash': 'Pay via JazzCash (' + totalStr + ')',
              'bank_transfer': 'Pay via Bank / Raast (' + totalStr + ')',
              'card': 'Pay with Card (' + totalStr + ')'
            };

            function updatePanes() {
              var selected = document.querySelector('input[name="payment_method"]:checked');
              var val = selected ? selected.value : 'cod';

              for (var key in panes) {
                if (panes[key]) {
                  if (key === val) {
                    panes[key].classList.add('is-active');
                  } else {
                    panes[key].classList.remove('is-active');
                  }
                }
              }

              if (btnText && labels[val]) {
                btnText.textContent = labels[val];
              }
            }

            radios.forEach(function(r) {
              r.addEventListener('change', updatePanes);
            });

            // Initial call to set initial button label and pane
            updatePanes();
          });
        </script>

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
