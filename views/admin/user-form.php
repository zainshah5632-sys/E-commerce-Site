<?php
declare(strict_types=1);

$user = $user ?? null;
$errors = $errors ?? [];
$isEdit = !empty($user['id']);
$selectedCountry = old('country_code', $user['country_code'] ?? '+1');
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <a href="<?= url('/admin/users') ?>">Users</a>
    <span class="sep">/</span>
    <span class="current"><?= $isEdit ? 'Edit User' : 'New User' ?></span>
  </div>

  <!-- Header -->
  <div class="admin-header-row">
    <div>
      <h1 class="admin-title"><?= $isEdit ? 'Edit ' . e($user['full_name'] ?: 'User') : 'Create User Account' ?></h1>
      <p class="admin-subtitle">Configure user authentication identity, contact details, and administrative permissions.</p>
    </div>
    <div class="admin-header-actions">
      <a href="<?= url('/admin/users') ?>" class="btn-admin-ghost">
        <?= icon('arrow-left') ?>
        <span>Back to Directory</span>
      </a>
    </div>
  </div>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert alert--error" style="margin-bottom: var(--s-4);">
      <span><?= e($errors['general']) ?></span>
    </div>
  <?php endif; ?>

  <form
    action="<?= $isEdit ? url('/admin/users/' . ea($user['id'])) : url('/admin/users') ?>"
    method="POST"
    class="admin-editor-layout"
    style="max-width: 900px;"
    novalidate
  >
    <?= csrf_field() ?>

    <div class="admin-editor-main" style="flex: 1;">
      <div class="admin-card">
        <h2 class="card-section-title">Identity &amp; Profile Details</h2>
        <p class="card-section-desc">Personal details associated with this account.</p>

        <!-- Full Name -->
        <div class="form-group">
          <label for="usr-name" class="admin-label">
            <span>Full Name <span class="required">*</span></span>
            <span class="text-xs text-slate-400 font-normal">(Alphabets only)</span>
          </label>
          <div class="admin-input-icon-wrap">
            <span class="icon-left"><?= icon('user') ?></span>
            <input
              type="text"
              id="usr-name"
              name="full_name"
              class="admin-input admin-input--iconed"
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
            <span class="admin-field-error"><?= e($errors['full_name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-grid-2">
          <!-- Username -->
          <div class="form-group">
            <label for="usr-username" class="admin-label">Username <span class="required">*</span></label>
            <div class="admin-input-icon-wrap">
              <span class="icon-left"><?= icon('at-sign') ?></span>
              <input
                type="text"
                id="usr-username"
                name="username"
                class="admin-input admin-input--iconed"
                value="<?= ea(old('username', $user['username'] ?? '')) ?>"
                required
                pattern="[A-Za-z0-9_]{3,30}"
                title="3-30 letters, numbers, or underscores"
                placeholder="janedoe99"
              >
            </div>
            <?php if (!empty($errors['username'])): ?>
              <span class="admin-field-error"><?= e($errors['username']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Role -->
          <div class="form-group">
            <label for="usr-role" class="admin-label">System Role <span class="required">*</span></label>
            <select id="usr-role" name="role" class="admin-select">
              <option value="customer" <?= (old('role', $user['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Customer</option>
              <option value="admin" <?= (old('role', $user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrator</option>
            </select>
          </div>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="usr-email" class="admin-label">Email Address <span class="required">*</span></label>
          <div class="admin-input-icon-wrap">
            <span class="icon-left"><?= icon('mail') ?></span>
            <input
              type="email"
              id="usr-email"
              name="email"
              class="admin-input admin-input--iconed"
              value="<?= ea(old('email', $user['email'] ?? '')) ?>"
              <?= $isEdit ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : 'required' ?>
              placeholder="user@example.com"
            >
          </div>
          <?php if (!empty($errors['email'])): ?>
            <span class="admin-field-error"><?= e($errors['email']) ?></span>
          <?php elseif ($isEdit): ?>
            <span class="admin-help-text">Email address is bound to Supabase auth identity and cannot be edited directly.</span>
          <?php endif; ?>
        </div>

        <!-- Phone -->
        <div class="form-group" style="margin-bottom: 0;">
          <label for="usr-phone" class="admin-label">
            <span>Phone Number</span>
            <span class="text-xs text-slate-400 font-normal">(Country code + 11 digits)</span>
          </label>
          <div class="form-grid-2" style="grid-template-columns: 140px 1fr;">
            <select name="country_code" id="usr-country-code" class="admin-select" aria-label="Country Code">
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
            <div class="admin-input-icon-wrap">
              <span class="icon-left"><?= icon('phone') ?></span>
              <input
                type="tel"
                id="usr-phone"
                name="phone"
                class="admin-input admin-input--iconed"
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
            <span class="admin-field-error"><?= e($errors['phone']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Password Card -->
      <div class="admin-card">
        <h2 class="card-section-title"><?= $isEdit ? 'Update Security Credentials' : 'Password &amp; Security' ?></h2>
        <p class="card-section-desc"><?= $isEdit ? 'Leave password blank if you do not wish to change it.' : 'Enter a strong initial password with letter, number, and symbol.' ?></p>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="usr-password" class="admin-label">
            <span><?= $isEdit ? 'New Password' : 'Password <span class="required">*</span>' ?></span>
          </label>
          <div class="password-input-wrap">
            <input
              type="password"
              id="usr-password"
              name="password"
              class="admin-input"
              <?= $isEdit ? '' : 'required' ?>
              autocomplete="new-password"
              placeholder="<?= $isEdit ? 'Enter only if replacing current password' : 'Minimum 8 characters' ?>"
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
          <?php if (!empty($errors['password'])): ?>
            <span class="admin-field-error"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Action Card -->
      <div class="admin-card" style="display: flex; gap: var(--s-3); align-items: center;">
        <button type="submit" class="btn-admin-primary">
          <?= icon('check') ?>
          <span><?= $isEdit ? 'Update User Account' : 'Create User Account' ?></span>
        </button>
        <a href="<?= url('/admin/users') ?>" class="btn-admin-ghost">Cancel</a>
      </div>
    </div>
  </form>
</div>
