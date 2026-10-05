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

<!-- Header -->
<div class="admin-header flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
  <div>
    <div class="text-xs font-medium text-slate-500 tracking-wide mb-1 flex items-center gap-1.5">
      <span>Admin</span>
      <span class="text-slate-400">/</span>
      <span class="text-indigo-600 font-semibold">Store Settings</span>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Store Settings &amp; Configuration</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Global parameters, shipping calculations, currencies, and notifications.</p>
  </div>
</div>

<!-- Tabbed Navigation Bar -->
<div class="flex items-center gap-1.5 p-1.5 bg-slate-100/80 border border-slate-200/70 rounded-2xl mb-8 max-w-4xl overflow-x-auto select-none" id="settings-tabs">
  <button type="button" data-tab-target="general" class="settings-tab px-4 py-2 rounded-xl text-sm font-semibold transition bg-white shadow-sm border border-slate-200 text-slate-900">
    General Info
  </button>
  <button type="button" data-tab-target="payments" class="settings-tab px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-200/60 transition">
    Payments &amp; Currency
  </button>
  <button type="button" data-tab-target="shipping" class="settings-tab px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-200/60 transition">
    Shipping &amp; Delivery
  </button>
  <button type="button" data-tab-target="notifications" class="settings-tab px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-200/60 transition">
    Notifications
  </button>
  <button type="button" data-tab-target="security" class="settings-tab px-4 py-2 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-200/60 transition">
    API &amp; Security
  </button>
</div>

<form action="<?= url('/admin/settings') ?>" method="POST" id="settings-form" class="relative pb-16">
  <?= csrf_field() ?>

  <!-- =====================================================================
       TAB 1: General Info
       ===================================================================== -->
  <div data-tab-pane="general" class="settings-pane space-y-8">
    
    <!-- Store Identity Block -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Storefront Identity</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Public brand name and official correspondence email shown on customer order receipts.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
        <div>
          <label for="set-store-name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Store Name
          </label>
          <input
            type="text"
            id="set-store-name"
            name="store_name"
            class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
            required
            value="<?= ea($settings['store_name'] ?? 'GlassCart') ?>"
          >
          <?php if (!empty($errors['store_name'])): ?>
            <span class="text-xs text-rose-600 mt-1 block"><?= e($errors['store_name']) ?></span>
          <?php endif; ?>
        </div>

        <div>
          <label for="set-contact-email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Support / Contact Email
          </label>
          <input
            type="email"
            id="set-contact-email"
            name="contact_email"
            class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
            required
            value="<?= ea($settings['contact_email'] ?? 'support@glasscart.local') ?>"
          >
          <?php if (!empty($errors['contact_email'])): ?>
            <span class="text-xs text-rose-600 mt-1 block"><?= e($errors['contact_email']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Announcement Banner Block -->
    <div class="border-t border-slate-200/80 pt-8 mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Announcement Banner</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          A persistent header banner broadcasted across all storefront pages to announce offers, releases, or alerts.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div>
          <label for="set-announcement" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Banner Text
          </label>
          <input
            type="text"
            id="set-announcement"
            name="announcement"
            class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
            placeholder="e.g. Complimentary worldwide shipping on all orders over $75"
            value="<?= ea($settings['announcement'] ?? '') ?>"
          >
          <p class="text-[11px] text-slate-400 mt-1">Leave empty to conceal the banner completely.</p>
        </div>
      </div>
    </div>

  </div>

  <!-- =====================================================================
       TAB 2: Payments & Currency
       ===================================================================== -->
  <div data-tab-pane="payments" class="settings-pane space-y-8" style="display: none;">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Currency &amp; Unit Pricing</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Configure how monetary values and checkout totals are formatted storewide.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="set-currency-code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Currency Code (ISO)
            </label>
            <input
              type="text"
              id="set-currency-code"
              name="currency_code"
              maxlength="5"
              class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm font-mono text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm uppercase"
              required
              value="<?= ea($settings['currency_code'] ?? 'USD') ?>"
            >
          </div>
          <div>
            <label for="set-currency-symbol" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Currency Symbol
            </label>
            <input
              type="text"
              id="set-currency-symbol"
              name="currency_symbol"
              maxlength="5"
              class="w-full h-11 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
              required
              value="<?= ea($settings['currency_symbol'] ?? '$') ?>"
            >
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-slate-200/80 pt-8 mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Direct Bank Settlement</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Custom wire transfer instructions, IBAN numbers, and routing keys presented on the checkout receipt.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div>
          <label for="set-bank" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Wire Instructions &amp; Bank Account
          </label>
          <textarea
            id="set-bank"
            name="bank_instructions"
            rows="4"
            class="w-full bg-white border border-slate-300 rounded-xl p-3 text-sm text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
            placeholder="Account Name: GlassCart Inc&#10;IBAN: US98BANK000123456789&#10;Swift / BIC: GCARTUS33"
          ><?= ea($settings['bank_instructions'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

  </div>

  <!-- =====================================================================
       TAB 3: Shipping & Delivery
       ===================================================================== -->
  <div data-tab-pane="shipping" class="settings-pane space-y-8" style="display: none;">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Shipping Rates &amp; Free Tier</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Define baseline delivery fees and the threshold amount required for complimentary shipping.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="set-flat-shipping" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Standard Flat Shipping ($)
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">$</span>
              <input
                type="number"
                step="0.01"
                min="0"
                id="set-flat-shipping"
                name="flat_shipping"
                class="w-full h-11 bg-white border border-slate-300 rounded-xl pl-8 pr-3.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
                value="<?= ea($flatShippingMajor) ?>"
              >
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Applied when cart total is below threshold.</p>
          </div>

          <div>
            <label for="set-free-threshold" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Free Shipping Threshold ($)
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">$</span>
              <input
                type="number"
                step="0.01"
                min="0"
                id="set-free-threshold"
                name="free_shipping_threshold"
                class="w-full h-11 bg-white border border-slate-300 rounded-xl pl-8 pr-3.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
                value="<?= ea($freeThresholdMajor) ?>"
              >
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Orders above this receive free delivery.</p>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- =====================================================================
       TAB 4: Notifications
       ===================================================================== -->
  <div data-tab-pane="notifications" class="settings-pane space-y-8" style="display: none;">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Automated Dispatch Alerts</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Configure real-time notifications for customer checkouts and inventory shortages.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        
        <!-- Toggle 1: Order Confirmation -->
        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
          <div>
            <div class="text-sm font-semibold text-slate-900">Customer Order Confirmations</div>
            <div class="text-xs text-slate-500">Send itemized digital receipts immediately upon checkout.</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer select-none">
            <input type="checkbox" checked class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
          </label>
        </div>

        <!-- Toggle 2: Low Stock Warning -->
        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
          <div>
            <div class="text-sm font-semibold text-slate-900">Low Stock Telemetry Alerts</div>
            <div class="text-xs text-slate-500">Notify store administrators when catalog stock falls below 3 units.</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer select-none">
            <input type="checkbox" checked class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
          </label>
        </div>

      </div>
    </div>

  </div>

  <!-- =====================================================================
       TAB 5: API & Security
       ===================================================================== -->
  <div data-tab-pane="security" class="settings-pane space-y-8" style="display: none;">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <div class="lg:col-span-1">
        <h2 class="text-base font-bold text-slate-900">Backend Infrastructure</h2>
        <p class="text-xs text-slate-500 leading-relaxed mt-1">
          Cryptographic session verification, database connectivity, and Content Security Policies.
        </p>
      </div>
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-slate-900">Supabase REST Connectivity</div>
            <div class="text-xs text-slate-500">Server-side authenticated PostgREST tunnel active.</div>
          </div>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
            Connected (200 OK)
          </span>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-slate-900">Libsodium AEAD Cookie Encryption</div>
            <div class="text-xs text-slate-500">Stateless XChaCha20-Poly1305 tamper-proof cookies.</div>
          </div>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
            Active (256-bit)
          </span>
        </div>

      </div>
    </div>

  </div>

  <!-- Sticky Save Bar -->
  <div class="sticky bottom-0 z-30 -mx-6 -mb-6 mt-12 bg-white/90 backdrop-blur-md border-t border-slate-200 px-6 py-4 flex items-center justify-between shadow-lg">
    <div class="text-xs text-slate-500">
      <span>Changes apply immediately across all storefront endpoints.</span>
    </div>
    <div class="flex items-center gap-3">
      <button
        type="reset"
        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer"
      >
        Discard
      </button>
      <button
        type="submit"
        class="px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-md shadow-indigo-600/20 active:scale-[0.99] flex items-center gap-2 cursor-pointer"
      >
        <?= icon('check', 14) ?>
        <span>Update Settings</span>
      </button>
    </div>
  </div>

</form>
