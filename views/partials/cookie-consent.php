<?php
declare(strict_types=1);
?>
<!-- Cookie Consent Banner -->
<aside id="cookie-consent-banner" class="cookie-banner is-hidden" aria-label="Cookie and Privacy Preferences" role="region">
  <div class="cookie-banner__header">
    <div class="cookie-banner__icon">
      <?= icon('shield') ?>
    </div>
    <div>
      <h3 class="cookie-banner__title">Privacy &amp; Cookie Preferences</h3>
    </div>
  </div>
  <p class="cookie-banner__desc">
    We use essential encrypted cookies to secure your shopping cart and session. Optional analytics help us refine your experience.
  </p>
  <div class="cookie-banner__actions">
    <button type="button" class="btn btn--primary btn--sm" data-action="accept-all-cookies">
      <span>Accept All</span>
    </button>
    <button type="button" class="btn btn--ghost btn--sm" data-action="reject-optional-cookies">
      <span>Essential Only</span>
    </button>
    <button type="button" class="btn btn--ghost btn--sm" data-action="open-cookie-preferences">
      <span>Customize</span>
    </button>
  </div>
</aside>

<!-- Cookie Preferences Modal -->
<div id="cookie-modal-backdrop" class="cookie-modal-backdrop" aria-hidden="true">
  <div id="cookie-preferences-modal" class="cookie-modal" role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title">
    <div class="cookie-modal__header">
      <div style="display: flex; align-items: center; gap: var(--s-3);">
        <div class="cookie-banner__icon">
          <?= icon('shield') ?>
        </div>
        <h3 id="cookie-modal-title" style="font-size: 1.15rem; font-weight: 700; color: var(--text-1);">Cookie Settings</h3>
      </div>
      <button type="button" class="btn btn--ghost btn--icon" data-action="close-cookie-preferences" aria-label="Close Cookie Settings">
        <?= icon('close') ?>
      </button>
    </div>

    <p style="font-size: 0.86rem; color: var(--text-2); margin-bottom: var(--s-4); line-height: 1.5;">
      Select which cookies you allow us to use. Essential cookies cannot be disabled as they are required for secure authenticated sessions, CSRF prevention, and cart persistence.
    </p>

    <div class="cookie-modal__list">
      <!-- 1. Strictly Necessary -->
      <div class="cookie-option-card">
        <div>
          <h4>Strictly Necessary Cookies</h4>
          <p>Required for Libsodium AEAD session security, CSRF protection, and cart functionality.</p>
        </div>
        <span class="badge badge--success" style="font-size: 0.72rem; padding: 4px 8px; flex-shrink: 0;">Always Active</span>
      </div>

      <!-- 2. Functional & UI Personalization -->
      <div class="cookie-option-card">
        <div>
          <h4>Personalization &amp; Accessibility</h4>
          <p>Remembers your text font-scaling preferences and dark/light color theme choices.</p>
        </div>
        <label class="switch" style="flex-shrink: 0;">
          <input type="checkbox" id="cookie-pref-personalization" checked>
          <span class="slider"></span>
        </label>
      </div>

      <!-- 3. Performance & Anonymous Analytics -->
      <div class="cookie-option-card">
        <div>
          <h4>Anonymous Performance Metrics</h4>
          <p>Helps us analyze aggregate browsing speeds and catalog performance with zero personal tracking.</p>
        </div>
        <label class="switch" style="flex-shrink: 0;">
          <input type="checkbox" id="cookie-pref-analytics" checked>
          <span class="slider"></span>
        </label>
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: var(--s-2);">
      <button type="button" class="btn btn--ghost btn--sm" data-action="close-cookie-preferences">Cancel</button>
      <button type="button" class="btn btn--primary btn--sm" data-action="save-cookie-preferences">Save Preferences</button>
    </div>
  </div>
</div>

