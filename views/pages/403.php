<?php
declare(strict_types=1);
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong" style="text-align: center;">
    <h1 style="font-size: 3.5rem; font-family: var(--font-display); color: var(--warning);">403</h1>
    <h2 style="font-size: 1.5rem; margin-top: var(--s-2); margin-bottom: var(--s-3);">Access Denied</h2>
    <p style="margin-bottom: var(--s-5);">You do not have administrative privileges to view this area.</p>
    <a href="<?= url('/') ?>" class="btn btn--primary">
      <span>Return Home</span>
      <?= icon('arrow-right') ?>
    </a>
  </div>
</div>
