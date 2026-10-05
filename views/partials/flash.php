<?php
declare(strict_types=1);

use App\Core\Session;

$session = Session::instance();
$allFlashes = $session->allFlash();
?>
<?php if (!empty($allFlashes)): ?>
  <div class="container" style="padding-top: var(--s-4);">
    <?php foreach ($allFlashes as $type => $message): ?>
      <?php
        $alertClass = match($type) {
            'success' => 'alert--success',
            'error', 'danger' => 'alert--error',
            'warning' => 'alert--warning',
            default => 'alert--info',
        };
      ?>
      <div class="alert <?= $alertClass ?> glass--strong" role="alert">
        <span><?= e($message) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
