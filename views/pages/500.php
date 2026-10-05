<?php
declare(strict_types=1);

$message = $message ?? 'An unexpected system error occurred. Please try again.';
$trace = $trace ?? null;
?>
<div class="container auth-wrapper">
  <div class="auth-card glass--strong" style="text-align: center; max-width: 600px;">
    <h1 style="font-size: 3.5rem; font-family: var(--font-display); color: var(--danger);">500</h1>
    <h2 style="font-size: 1.5rem; margin-top: var(--s-2); margin-bottom: var(--s-3);">System Error</h2>
    <p style="margin-bottom: var(--s-5);"><?= e($message) ?></p>

    <?php if ($trace): ?>
      <pre style="text-align: left; background: var(--bg-base); padding: var(--s-4); border-radius: var(--r-md); overflow-x: auto; font-size: 0.8rem; margin-bottom: var(--s-4);"><?= e($trace) ?></pre>
    <?php endif; ?>

    <a href="<?= url('/') ?>" class="btn btn--primary">
      <span>Return to Safety</span>
      <?= icon('arrow-right') ?>
    </a>
  </div>
</div>
