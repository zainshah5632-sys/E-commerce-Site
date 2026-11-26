<?php
declare(strict_types=1);

/**
 * GlassCart Email Verification View
 *
 * @var string      $email
 * @var array       $errors
 * @var string|null $successMessage
 * @var int         $remainingCooldown
 */
$email = $email ?? '';
$errors = $errors ?? [];
$remainingCooldown = $remainingCooldown ?? 0;
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong" style="max-width: 480px;">
    <!-- Header -->
    <div class="auth-header">
      <div style="display: inline-flex; padding: var(--s-3); border-radius: var(--r-pill); background: var(--grad-brand-soft); margin-bottom: var(--s-3); color: var(--brand-600);">
        <?= icon('mail') ?>
      </div>
      <h1>Verify Your Email</h1>
      <p style="margin-top: var(--s-1);">
        We have sent a 6-digit verification code to:
      </p>
      <div style="margin-top: 6px; font-weight: 700; color: var(--text-1); font-size: 0.98rem; word-break: break-all;">
        <span class="badge badge--neutral" style="font-size: 0.9rem; padding: 4px 12px; border-radius: var(--r-pill);">
          <?= e($email) ?>
        </span>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($errors['otp'])): ?>
      <div class="alert alert--error glass--strong" style="margin-bottom: var(--s-4);">
        <span><?= e($errors['otp']) ?></span>
      </div>
    <?php elseif (!empty($errors['general'])): ?>
      <div class="alert alert--error glass--strong" style="margin-bottom: var(--s-4);">
        <span><?= e($errors['general']) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
      <div class="alert alert--success glass--strong" style="margin-bottom: var(--s-4);">
        <span><?= e($successMessage) ?></span>
      </div>
    <?php endif; ?>

    <!-- 6-Digit OTP Form -->
    <form action="<?= url('/verify-email') ?>" method="POST" id="verify-otp-form" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="email" value="<?= ea($email) ?>">
      <!-- Combined hidden input submitted to backend -->
      <input type="hidden" name="otp" id="otp-combined-input" value="">

      <div class="form-group" style="text-align: center; margin-bottom: var(--s-5);">
        <label class="form-label" style="text-align: center; margin-bottom: var(--s-3);">
          Enter 6-Digit Code
        </label>
        
        <!-- 6 Individual Accessible Digit Inputs -->
        <div class="otp-inputs-wrap" style="display: flex; justify-content: center; gap: 8px; margin-inline: auto;">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <input
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="1"
              class="form-input otp-digit-box"
              data-index="<?= $i ?>"
              autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
              required
              style="width: 48px; height: 56px; text-align: center; font-size: 1.5rem; font-weight: 700; font-family: monospace; padding: 0; border-radius: var(--r-md);"
              <?= $i === 0 ? 'autofocus' : '' ?>
            >
          <?php endfor; ?>
        </div>
        <span class="form-hint" style="display: block; margin-top: var(--s-2); font-size: 0.8rem; color: var(--text-3);">
          ⏱ Code expires in <strong>5 minutes</strong>. Paste supported.
        </span>
      </div>

      <div style="margin-top: var(--s-5);">
        <button type="submit" id="btn-verify-submit" class="btn btn--primary btn--block btn--lg">
          <span>Verify &amp; Activate Account</span>
          <?= icon('arrow-right') ?>
        </button>
      </div>
    </form>

    <!-- Resend Code Section with 60-Second Cooldown -->
    <div style="margin-top: var(--s-5); padding-top: var(--s-4); border-top: 1px solid var(--line); text-align: center;">
      <p style="font-size: 0.88rem; color: var(--text-2); margin-bottom: var(--s-2);">
        Didn't receive the email? Check your spam folder or request a new code.
      </p>

      <form action="<?= url('/verify-email/resend') ?>" method="POST" id="resend-otp-form" style="display: inline-block;">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= ea($email) ?>">
        <button
          type="submit"
          id="btn-resend-otp"
          class="btn btn--ghost btn--sm"
          <?= $remainingCooldown > 0 ? 'disabled' : '' ?>
          data-cooldown="<?= (int)$remainingCooldown ?>"
          style="font-weight: 600;"
        >
          <span id="resend-btn-label">
            <?= $remainingCooldown > 0 ? "Resend Code ({$remainingCooldown}s)" : "Resend Code" ?>
          </span>
        </button>
      </form>
    </div>

    <!-- Auth Footer Navigation Links -->
    <div class="auth-footer" style="margin-top: var(--s-4); display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem;">
      <div>
        <span>Wrong email address?</span>
        <a href="<?= url('/register') ?>" style="color: var(--brand-600); font-weight: 600; text-decoration: none;">Register Again</a>
      </div>
      <div>
        <span>Already verified?</span>
        <a href="<?= url('/login') ?>" style="color: var(--brand-600); font-weight: 600; text-decoration: none;">Sign In</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const digitBoxes = Array.from(document.querySelectorAll('.otp-digit-box'));
  const combinedInput = document.getElementById('otp-combined-input');
  const verifyForm = document.getElementById('verify-otp-form');
  const resendBtn = document.getElementById('btn-resend-otp');
  const resendLabel = document.getElementById('resend-btn-label');

  // Sync digit boxes with combined input value
  const updateCombined = () => {
    const val = digitBoxes.map(b => b.value).join('');
    combinedInput.value = val;
    return val;
  };

  // Focus management and auto-advance
  digitBoxes.forEach((box, idx) => {
    box.addEventListener('input', (e) => {
      const val = e.target.value.replace(/[^0-9]/g, '');
      box.value = val ? val[val.length - 1] : '';

      if (box.value && idx < digitBoxes.length - 1) {
        digitBoxes[idx + 1].focus();
        digitBoxes[idx + 1].select();
      }

      const total = updateCombined();
      if (total.length === 6) {
        // Auto submit if all 6 digits entered
        verifyForm.requestSubmit();
      }
    });

    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && idx > 0) {
        digitBoxes[idx - 1].focus();
        digitBoxes[idx - 1].select();
      } else if (e.key === 'ArrowLeft' && idx > 0) {
        e.preventDefault();
        digitBoxes[idx - 1].focus();
        digitBoxes[idx - 1].select();
      } else if (e.key === 'ArrowRight' && idx < digitBoxes.length - 1) {
        e.preventDefault();
        digitBoxes[idx + 1].focus();
        digitBoxes[idx + 1].select();
      }
    });

    // Support pasting complete 6-digit code
    box.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData('text');
      const cleanDigits = pasteData.replace(/[^0-9]/g, '').slice(0, 6);

      if (cleanDigits.length > 0) {
        cleanDigits.split('').forEach((d, i) => {
          if (digitBoxes[i]) {
            digitBoxes[i].value = d;
          }
        });
        const targetIndex = Math.min(cleanDigits.length, digitBoxes.length - 1);
        digitBoxes[targetIndex].focus();
        updateCombined();

        if (cleanDigits.length === 6) {
          verifyForm.requestSubmit();
        }
      }
    });
  });

  // Verify form submission validation
  verifyForm.addEventListener('submit', (e) => {
    const code = updateCombined();
    if (!/^[0-9]{6}$/.test(code)) {
      e.preventDefault();
      alert('Please enter all 6 digits of the verification code.');
      const firstEmpty = digitBoxes.find(b => !b.value);
      if (firstEmpty) firstEmpty.focus();
    }
  });

  // Live 60-Second Cooldown Countdown Timer
  if (resendBtn) {
    let remaining = parseInt(resendBtn.getAttribute('data-cooldown') || '0', 10);

    const updateTimer = () => {
      if (remaining > 0) {
        resendBtn.disabled = true;
        resendLabel.textContent = `Resend Code (${remaining}s)`;
        remaining--;
        setTimeout(updateTimer, 1000);
      } else {
        resendBtn.disabled = false;
        resendLabel.textContent = 'Resend Code';
      }
    };

    if (remaining > 0) {
      updateTimer();
    }
  }
});
</script>
