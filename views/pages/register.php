<?php
declare(strict_types=1);

$errors = $errors ?? [];
$selectedCountry = old('country_code', '+1');
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong">
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('user-plus') ?>
      </div>
      <h1>Create an Account</h1>
      <p>Join GlassCart for swift checkout and real-time order tracking.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error glass--strong" style="margin-bottom: var(--s-5);">
        <div style="font-weight: 600; margin-bottom: 4px;">Please review the following:</div>
        <ul style="margin: 0; padding-left: 20px; font-size: 0.88rem; display: flex; flex-direction: column; gap: 4px;">
          <?php foreach ($errors as $field => $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= url('/register') ?>" method="POST" novalidate>
      <?= csrf_field() ?>

      <!-- 1. Full Name (Alphabets only per Teacher Requirement #6) -->
      <div class="form-group">
        <label for="reg-name" class="form-label">
          <span>Full Name</span>
          <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Alphabets only)</span>
        </label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('user') ?></span>
          <input
            type="text"
            id="reg-name"
            name="full_name"
            class="form-input form-input--with-icon"
            value="<?= ea(old('full_name')) ?>"
            required
            autocomplete="name"
            placeholder="Jane Doe"
            data-alpha-only="true"
            pattern="[A-Za-z\s\-']+"
            title="Alphabets only, no numbers allowed"
            autofocus
          >
        </div>
        <?php if (!empty($errors['full_name'])): ?>
          <span class="form-error"><?= e($errors['full_name']) ?></span>
        <?php else: ?>
          <span class="form-hint">Alphabets only. Numbers and special symbols are prohibited.</span>
        <?php endif; ?>
      </div>

      <!-- 2. Username (Separate from Name per Teacher Requirement #1) -->
      <div class="form-group">
        <label for="reg-username" class="form-label">Username</label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('at-sign') ?></span>
          <input
            type="text"
            id="reg-username"
            name="username"
            class="form-input form-input--with-icon"
            value="<?= ea(old('username')) ?>"
            required
            autocomplete="username"
            placeholder="janedoe99"
            pattern="[A-Za-z0-9_]{3,30}"
            title="3-30 letters, numbers, or underscores"
          >
        </div>
        <?php if (!empty($errors['username'])): ?>
          <span class="form-error"><?= e($errors['username']) ?></span>
        <?php else: ?>
          <span class="form-hint">Unique handle for signing in (3–30 letters, numbers, or underscores).</span>
        <?php endif; ?>
      </div>

      <!-- 3. Email Address -->
      <div class="form-group">
        <label for="reg-email" class="form-label">Email Address</label>
        <div class="input-with-icon-wrap">
          <span class="input-icon-left"><?= icon('mail') ?></span>
          <input
            type="email"
            id="reg-email"
            name="email"
            class="form-input form-input--with-icon"
            value="<?= ea(old('email')) ?>"
            required
            autocomplete="email"
            placeholder="jane@example.com"
          >
        </div>
        <?php if (!empty($errors['email'])): ?>
          <span class="form-error"><?= e($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <!-- 4. Phone Number (Country Code + Exactly 11 Digits per Teacher Requirement #2) -->
      <div class="form-group">
        <label for="reg-phone" class="form-label">
          <span>Phone Number</span>
          <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Country code + exactly 11 digits)</span>
        </label>
        <div class="phone-input-group">
          <select name="country_code" id="reg-country-code" class="form-select phone-country-code" aria-label="Country Code">
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
              id="reg-phone"
              name="phone"
              class="form-input form-input--with-icon"
              value="<?= ea(old('phone')) ?>"
              required
              data-phone-11="true"
              maxlength="11"
              minlength="11"
              pattern="[0-9]{11}"
              placeholder="03001234567"
              autocomplete="tel-national"
            >
          </div>
        </div>
        <?php if (!empty($errors['phone'])): ?>
          <span class="form-error"><?= e($errors['phone']) ?></span>
        <?php else: ?>
          <span class="form-hint">Enter your country code and exactly 11 digits (e.g. 03001234567).</span>
        <?php endif; ?>
      </div>

      <!-- 5. Password (Hide/Unhide toggle & Alphabet/Number/Sign complexity per Teacher Requirement #3) -->
      <div class="form-group">
        <label for="reg-password" class="form-label">Password</label>
        <div class="password-input-wrap">
          <input
            type="password"
            id="reg-password"
            name="password"
            class="form-input"
            required
            autocomplete="new-password"
            placeholder="Create a strong password"
          >
          <button
            type="button"
            class="btn-toggle-password"
            data-action="toggle-password"
            aria-label="Toggle password visibility"
            tabindex="-1"
          >
            <span class="icon-eye"><?= icon('eye') ?></span>
            <span class="icon-eye-off" style="display: none;"><?= icon('eye-off') ?></span>
          </button>
        </div>

        <div class="password-reqs">
          <span class="password-req-badge"><?= icon('check') ?> Alphabet</span>
          <span class="password-req-badge"><?= icon('check') ?> Number</span>
          <span class="password-req-badge"><?= icon('check') ?> Any sign/symbol</span>
          <span class="password-req-badge"><?= icon('check') ?> 6+ chars</span>
        </div>

        <?php if (!empty($errors['password'])): ?>
          <span class="form-error"><?= e($errors['password']) ?></span>
        <?php else: ?>
          <span class="form-hint">Password must contain at least one letter, one number, and one special character (e.g. !@#$%).</span>
        <?php endif; ?>
      </div>

      <!-- 6. Confirm Password with Hide/Unhide toggle -->
      <div class="form-group">
        <label for="reg-password-confirm" class="form-label">Confirm Password</label>
        <div class="password-input-wrap">
          <input
            type="password"
            id="reg-password-confirm"
            name="password_confirmation"
            class="form-input"
            required
            autocomplete="new-password"
            placeholder="Repeat your password"
          >
          <button
            type="button"
            class="btn-toggle-password"
            data-action="toggle-password"
            aria-label="Toggle password visibility"
            tabindex="-1"
          >
            <span class="icon-eye"><?= icon('eye') ?></span>
            <span class="icon-eye-off" style="display: none;"><?= icon('eye-off') ?></span>
          </button>
        </div>
        <?php if (!empty($errors['password_confirmation'])): ?>
          <span class="form-error"><?= e($errors['password_confirmation']) ?></span>
        <?php endif; ?>
      </div>

      <div style="margin-top: var(--s-5);">
        <button type="submit" class="btn btn--primary btn--block btn--lg">
          <span>Create Account</span>
          <?= icon('arrow-right') ?>
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Already have an account?
      <a href="<?= url('/login') ?>">Sign In instead</a>
    </div>
  </div>
</div>
