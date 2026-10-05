<?php
declare(strict_types=1);

$user = $user ?? null;
$errors = $errors ?? [];
$isEdit = !empty($user['id']);
$selectedCountry = old('country_code', $user['country_code'] ?? '+1');
?>

<div class="admin-header">
  <div>
    <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-2);">
      <a href="<?= url('/admin/users') ?>">Users &amp; Customers</a>
      <span>/</span>
      <span style="color: var(--text-1);"><?= $isEdit ? 'Edit User' : 'New User' ?></span>
    </div>
    <h1><?= $isEdit ? 'Edit ' . e($user['full_name'] ?: 'User') : 'Create User Account' ?></h1>
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert--error glass--strong">
    <span><?= e($errors['general']) ?></span>
  </div>
<?php endif; ?>

<form
  action="<?= $isEdit ? url('/admin/users/' . ea($user['id'])) : url('/admin/users') ?>"
  method="POST"
  class="glass--strong"
  style="padding: var(--s-6); max-width: 780px;"
  novalidate
>
  <?= csrf_field() ?>

  <!-- 1. Full Name (Alphabets only) -->
  <div class="form-group">
    <label for="usr-name" class="form-label">
      <span>Full Name</span>
      <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Alphabets only)</span>
    </label>
    <div class="input-with-icon-wrap">
      <span class="input-icon-left"><?= icon('user') ?></span>
      <input
        type="text"
        id="usr-name"
        name="full_name"
        class="form-input form-input--with-icon"
        value="<?= ea(old('full_name', $user['full_name'] ?? '')) ?>"
        required
        data-alpha-only="true"
        pattern="[A-Za-z\s\-']+"
        title="Alphabets only, no numbers allowed"
        placeholder="Jane Doe"
        autofocus
      >
    </div>
    <?php if (!empty($errors['full_name'])): ?>
      <span class="form-error"><?= e($errors['full_name']) ?></span>
    <?php else: ?>
      <span class="form-hint">Alphabets only. Numbers and special symbols are prohibited.</span>
    <?php endif; ?>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4);">
    <!-- 2. Username -->
    <div class="form-group">
      <label for="usr-username" class="form-label">Username</label>
      <div class="input-with-icon-wrap">
        <span class="input-icon-left"><?= icon('at-sign') ?></span>
        <input
          type="text"
          id="usr-username"
          name="username"
          class="form-input form-input--with-icon"
          value="<?= ea(old('username', $user['username'] ?? '')) ?>"
          required
          pattern="[A-Za-z0-9_]{3,30}"
          title="3-30 letters, numbers, or underscores"
          placeholder="janedoe99"
        >
      </div>
      <?php if (!empty($errors['username'])): ?>
        <span class="form-error"><?= e($errors['username']) ?></span>
      <?php endif; ?>
    </div>

    <!-- 3. System Role -->
    <div class="form-group">
      <label for="usr-role" class="form-label">System Role</label>
      <select id="usr-role" name="role" class="form-select">
        <option value="customer" <?= (old('role', $user['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Customer</option>
        <option value="admin" <?= (old('role', $user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrator</option>
      </select>
      <span class="form-hint">Administrators have full access to this control center.</span>
    </div>
  </div>

  <!-- 4. Email Address -->
  <div class="form-group">
    <label for="usr-email" class="form-label">Email Address</label>
    <div class="input-with-icon-wrap">
      <span class="input-icon-left"><?= icon('mail') ?></span>
      <input
        type="email"
        id="usr-email"
        name="email"
        class="form-input form-input--with-icon"
        value="<?= ea(old('email', $user['email'] ?? '')) ?>"
        <?= $isEdit ? 'readonly style="opacity: 0.8; cursor: not-allowed;"' : 'required' ?>
        placeholder="user@example.com"
      >
    </div>
    <?php if (!empty($errors['email'])): ?>
      <span class="form-error"><?= e($errors['email']) ?></span>
    <?php elseif ($isEdit): ?>
      <span class="form-hint">Email address is linked to auth credentials and cannot be edited directly.</span>
    <?php endif; ?>
  </div>

  <!-- 5. Phone Number (Country Code + 11 Digits) -->
  <div class="form-group">
    <label for="usr-phone" class="form-label">
      <span>Phone Number</span>
      <span style="font-size: 0.75rem; color: var(--text-3); font-weight: 400;">(Country code + exactly 11 digits)</span>
    </label>
    <div class="phone-input-group">
      <select name="country_code" id="usr-country-code" class="form-select phone-country-code" aria-label="Country Code">
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
          id="usr-phone"
          name="phone"
          class="form-input form-input--with-icon"
          value="<?= ea(old('phone', $user['phone'] ?? '')) ?>"
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
      <span class="form-hint">Enter country code and exactly 11 digits (e.g. 03001234567).</span>
    <?php endif; ?>
  </div>

  <!-- 6. Password (with Toggle Button & Complexity Requirements) -->
  <div class="form-group" style="border-top: 1px solid var(--line); padding-top: var(--s-4); margin-top: var(--s-5);">
    <label for="usr-password" class="form-label">
      <span><?= $isEdit ? 'Change Password (Leave blank to keep current)' : 'Password' ?></span>
    </label>
    <div class="password-input-wrap">
      <input
        type="password"
        id="usr-password"
        name="password"
        class="form-input"
        <?= $isEdit ? '' : 'required' ?>
        autocomplete="new-password"
        placeholder="<?= $isEdit ? 'Enter new password only if changing' : 'Minimum 8 characters' ?>"
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
      <span class="password-req-badge"><?= icon('check') ?> 8+ chars</span>
    </div>

    <?php if (!empty($errors['password'])): ?>
      <span class="form-error"><?= e($errors['password']) ?></span>
    <?php else: ?>
      <span class="form-hint">Must include at least one letter, one number, and one special character (e.g. !@#$%).</span>
    <?php endif; ?>
  </div>

  <div style="margin-top: var(--s-6); display: flex; gap: var(--s-3);">
    <button type="submit" class="btn btn--primary btn--lg">
      <span><?= $isEdit ? 'Update User' : 'Create User Account' ?></span>
    </button>
    <a href="<?= url('/admin/users') ?>" class="btn btn--ghost btn--lg">Cancel</a>
  </div>
</form>
