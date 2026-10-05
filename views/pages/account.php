<?php
declare(strict_types=1);

use App\Core\Session;

$profile = $profile ?? [];
$email = $email ?? '';
$errors = $errors ?? [];
$session = Session::instance();
$displayName = $profile['full_name'] ?? $session->userName() ?? 'Customer';
$selectedCountry = old('country_code', $profile['country_code'] ?? '+1');
?>
<div class="container account-shell">
  <!-- Header row with aligned actions -->
  <div class="account-header">
    <div class="account-header__titles">
      <h1 class="account-title">Account Settings</h1>
      <p class="account-subtitle">
        Welcome back, <strong style="color: var(--text-1);"><?= e($displayName) ?></strong>! Manage your personal details and preferences here.
      </p>
    </div>
    <div class="account-header__actions">
      <a href="<?= url('/account/orders') ?>" class="btn btn--secondary btn--sm">
        <?= icon('cart') ?>
        <span>View Orders</span>
      </a>
      <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--ghost btn--sm" style="color: var(--danger);" title="Sign Out">
          <?= icon('logout') ?>
          <span>Sign Out</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Main Profile Card Container -->
  <div class="account-card">
    <div class="account-card__header">
      <h2 class="account-card__title">Personal Profile</h2>
      <p class="account-card__desc">Update your personal contact details and handle preferences.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error glass--strong" style="margin-bottom: var(--s-5);">
        <div style="font-weight: 600; margin-bottom: 4px;">Please correct the following:</div>
        <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem; display: flex; flex-direction: column; gap: 4px;">
          <?php foreach ($errors as $field => $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= url('/account') ?>" method="POST" class="account-form" novalidate>
      <?= csrf_field() ?>

      <!-- 2-Column Responsive Form Grid -->
      <div class="account-grid">
        <!-- 1. Username (Handle) -->
        <div class="form-group">
          <label for="acc-username" class="form-label">
            <span>Username</span>
            <span class="form-label-muted">Handle</span>
          </label>
          <div class="input-with-icon-wrap">
            <span class="input-icon-left"><?= icon('at-sign') ?></span>
            <input
              type="text"
              id="acc-username"
              name="username"
              class="form-input form-input--with-icon"
              value="<?= ea(old('username', $profile['username'] ?? '')) ?>"
              placeholder="e.g. zainshah"
              pattern="[A-Za-z0-9_]{3,30}"
              title="3-30 letters, numbers, or underscores"
            >
          </div>
          <?php if (!empty($errors['username'])): ?>
            <span class="form-error"><?= e($errors['username']) ?></span>
          <?php else: ?>
            <span class="form-hint">3–30 letters, numbers, or underscores.</span>
          <?php endif; ?>
        </div>

        <!-- 2. Full Name (Alphabets only) -->
        <div class="form-group">
          <label for="acc-name" class="form-label">
            <span>Full Name</span>
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
              placeholder="e.g. Zain Shah"
            >
          </div>
          <?php if (!empty($errors['full_name'])): ?>
            <span class="form-error"><?= e($errors['full_name']) ?></span>
          <?php else: ?>
            <span class="form-hint">Alphabets only. Numbers and special symbols are prohibited.</span>
          <?php endif; ?>
        </div>

        <!-- 3. Email Address (Fixed / Read-only) -->
        <div class="form-group">
          <label for="acc-email" class="form-label">
            <span>Email Address</span>
            <span class="form-label-muted">Primary Identifier</span>
          </label>
          <div class="input-with-icon-wrap">
            <span class="input-icon-left"><?= icon('mail') ?></span>
            <input
              type="email"
              id="acc-email"
              class="form-input form-input--with-icon form-input--disabled"
              value="<?= ea($email) ?>"
              disabled
              readonly
            >
          </div>
          <span class="form-hint">Email address cannot be changed directly.</span>
        </div>

        <!-- 4. Phone Number (Country Code + 11 Digits) -->
        <div class="form-group">
          <label for="acc-phone" class="form-label">
            <span>Phone Number</span>
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
            <span class="form-hint">Country code + exactly 11 digits (e.g. 03001234567).</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="account-card__footer">
        <button type="submit" class="btn btn--primary btn--md">
          <?= icon('check') ?>
          <span>Save Changes</span>
        </button>
      </div>
    </form>
  </div>
</div>
