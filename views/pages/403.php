<?php
declare(strict_types=1);
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong" style="text-align: center; max-width: 500px; margin: 40px auto; padding: var(--s-6);">
    <div style="width: 56px; height: 56px; border-radius: var(--r-pill); background: rgba(220, 38, 38, 0.1); color: var(--danger); display: inline-flex; align-items: center; justify-content: center; margin-bottom: var(--s-3);">
      <?= icon('shield', 28) ?>
    </div>
    
    <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--danger); margin-bottom: var(--s-1);">
      HTTP 403 &bull; Forbidden
    </div>

    <h1 style="font-size: 1.75rem; font-family: var(--font-display); font-weight: 700; color: var(--text-1); margin-bottom: var(--s-2);">
      Administrative Clearance Required
    </h1>

    <p style="font-size: 0.92rem; color: var(--text-2); line-height: 1.55; margin-bottom: var(--s-5);">
      Your current account is authenticated with customer permissions. Access to the GlassCart Administrative Console and internal management APIs is strictly restricted to verified administrators.
    </p>

    <div style="display: flex; gap: var(--s-3); justify-content: center; flex-wrap: wrap;">
      <a href="<?= url('/') ?>" class="btn btn--primary">
        <?= icon('arrow-left') ?>
        <span>Return to Storefront</span>
      </a>

      <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--ghost" style="color: var(--danger);">
          <span>Sign Out</span>
        </button>
      </form>
    </div>
  </div>
</div>
