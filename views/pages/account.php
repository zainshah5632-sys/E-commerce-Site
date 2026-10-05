<?php
declare(strict_types=1);

$profile = $profile ?? [];
$email = $email ?? '';
$errors = $errors ?? [];
$selectedCountry = old('country_code', $profile['country_code'] ?? '+1');
?>

<div class="container" style="padding-top: var(--s-6); padding-bottom: var(--s-9); max-width: 680px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--s-5);">
    <h1>Customer Account</h1>
    <a href="<?= url('/account/orders') ?>" class="btn btn--ghost btn--sm">
      <?= icon('cart') ?>
      <span>View Orders</span>
    </a>
  </div>

  <div class="glass--strong" style="padding: var(--s-6);">
    <h2 style="font-size: 1.25rem; margin-bottom: var(--s-4);">Personal Profile</h2>

    <form action="<?= url('/account') ?>" method="POST">
      <?= csrf_field() ?>

      <!-- Email Address (Fixed) -->
      <div class="form-group">
        <label for="acc-email" class="form-label">Email Address (Account Identifier)</label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('mail') ?></span>
          <input
            type="email"
            id="acc-email"
            class="form-input form-input--with-icon"
            value="<?= ea($email) ?>"
            disabled
            style="opacity: 0.7; cursor: not-allowed;"
          >
        </div>
        <span class="form-hint">Email address cannot be changed directly.</span>
      </div>

      <!-- Username (Separate Handle) -->
      <div class="form-group">
        <label for="acc-username" class="form-label">Username</label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('at-sign') ?></span>
          <input
            type="text"
            id="acc-username"
            name="username"
            class="form-input form-input--with-icon"
            value="<?= ea(old('username', $profile['username'] ?? '')) ?>"
            placeholder="username"
            pattern="[A-Za-z0-9_]{3,30}"
            title="3-30 letters, numbers, or underscores"
          >
        </div>
        <?php if (!empty($errors['username'])): ?>
          <span class="form-error"><?= e($errors['username']) ?></span>
        <?php else: ?>
          <span class="form-hint">Your unique username handle.</span>
        <?php endif; ?>
      </div>

      <!-- Full Name (Alphabets only) -->
      <div class="form-group">
        <label for="acc-name" class="form-label">
          <span>Full Name</span>
          <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Alphabets only)</span>
        </label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('user') ?></span>
          <input
            type="text"
            id="acc-name"
            name="full_name"
            class="form-input form-input--with-icon"
            value="<?= ea(old('full_name', $profile['full_name'] ?? '')) ?>"
            required
            data-alpha-only="true"
            pattern="[A-Za-z\s\-']+"
            title="Alphabets only, no numbers allowed"
          >
        </div>
        <?php if (!empty($errors['full_name'])): ?>
          <span class="form-error"><?= e($errors['full_name']) ?></span>
        <?php else: ?>
          <span class="form-hint">Alphabets only. Numbers and special symbols are prohibited.</span>
        <?php endif; ?>
      </div>

      <!-- Phone Number (Country code + exactly 11 digits) -->
      <div class="form-group">
        <label for="acc-phone" class="form-label">
          <span>Phone Number</span>
          <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Country code + exactly 11 digits)</span>
        </label>
        <div class="phone-input-group">
          <select name="country_code" id="acc-country-code" class="form-select phone-country-code" aria-label="Country Code">
            <option value="+1" <?= $selectedCountry === '+1' ? 'selected' : '' ?>>+1 (US/CA)</option>
            <option value="+44" <?= $selectedCountry === '+44' ? 'selected' : '' ?>>+44 (UK)</option>
            <option value="+92" <?= $selectedCountry === '+92' ? 'selected' : '' ?>>+92 (PK)</option>
            <option value="+91" <?= $selectedCountry === '+91' ? 'selected' : '' ?>>+91 (IN)</option>
            <option value="+971" <?= $selectedCountry === '+971' ? 'selected' : '' ?>>+971 (AE)</option>
            <option value="+966" <?= $selectedCountry === '+966' ? 'selected' : '' ?>>+966 (SA)</option>
            <option value="+61" <?= $selectedCountry === '+61' ? 'selected' : '' ?>>+61 (AU)</option>
            <option value="+49" <?= $selectedCountry === '+49' ? 'selected' : '' ?>>+49 (DE)</option>
            <option value="+33" <?= $selectedCountry === '+33' ? 'selected' : '' ?>>+33 (FR)</option>
            <option value="+81" <?= $selectedCountry === '+81' ? 'selected' : '' ?>>+81 (JP)</option>
            <option value="+86" <?= $selectedCountry === '+86' ? 'selected' : '' ?>>+86 (CN)</option>
            <option value="+90" <?= $selectedCountry === '+90' ? 'selected' : '' ?>>+90 (TR)</option>
            <option value="+60" <?= $selectedCountry === '+60' ? 'selected' : '' ?>>+60 (MY)</option>
            <option value="+65" <?= $selectedCountry === '+65' ? 'selected' : '' ?>>+65 (SG)</option>
          </select>
          <div class="input-with-icon-wrap phone-digits">
            <span class="input-icon-left"><?= icon('phone') ?></span>
            <input
              type="tel"
              id="acc-phone"
              name="phone"
              class="form-input form-input--with-icon"
              value="<?= ea(old('phone', $profile['phone'] ?? '')) ?>"
              data-phone-11="true"
              maxlength="11"
              minlength="11"
              pattern="[0-9]{11}"
              placeholder="03001234567"
            >
          </div>
        </div>
        <?php if (!empty($errors['phone'])): ?>
          <span class="form-error"><?= e($errors['phone']) ?></span>
        <?php else: ?>
          <span class="form-hint">Enter your country code and exactly 11 digits (e.g. 03001234567).</span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5); display: flex; justify-content: space-between; align-items: center;">
        <button type="submit" class="btn btn--primary">
          <span>Save Changes</span>
        </button>

        <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--ghost btn--sm" style="color: var(--danger);">
            <?= icon('logout') ?>
            <span>Sign Out</span>
          </button>
        </form>
      </div>
    </form>
  </div>
</div>
