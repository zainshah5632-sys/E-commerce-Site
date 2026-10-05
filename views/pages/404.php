<?php
declare(strict_types=1);
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong" style="text-align: center;">
    <h1 style="font-size: 3.5rem; font-family: var(--font-display); background: var(--grad-brand); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">404</h1>
    <h2 style="font-size: 1.5rem; margin-top: var(--s-2); margin-bottom: var(--s-3);">Object Not Found</h2>
    <p style="margin-bottom: var(--s-5);">The hardware piece or page you are looking for does not exist or has been relocated.</p>
    <a href="<?= url('/') ?>" class="btn btn--primary">
      <span>Return Home</span>
      <?= icon('arrow-right') ?>
    </a>
  </div>
</div>
