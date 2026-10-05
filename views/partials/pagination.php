<?php
declare(strict_types=1);

$currentPage = $page ?? 1;
$totalPages = $pages ?? 1;

if ($totalPages <= 1) {
    return;
}

$query = $_GET;
?>
<nav class="pagination" aria-label="Pagination" style="display: flex; justify-content: center; gap: var(--s-2); margin-top: var(--s-6);">
  <?php if ($currentPage > 1): ?>
    <?php $query['page'] = $currentPage - 1; ?>
    <a href="<?= url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', $query) ?>" class="btn btn--ghost btn--sm" aria-label="Previous Page">
      <?= icon('arrow-left') ?>
      <span>Prev</span>
    </a>
  <?php endif; ?>

  <div style="display: flex; gap: var(--s-1); align-items: center;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php if ($i === $currentPage): ?>
        <span class="btn btn--primary btn--sm" style="min-width: 36px; padding: 8px;"><?= $i ?></span>
      <?php elseif ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
        <?php $query['page'] = $i; ?>
        <a href="<?= url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', $query) ?>" class="btn btn--ghost btn--sm" style="min-width: 36px; padding: 8px;">
          <?= $i ?>
        </a>
      <?php elseif ($i == 2 || $i == $totalPages - 1): ?>
        <span style="padding-inline: 4px; color: var(--text-3);">&hellip;</span>
      <?php endif; ?>
    <?php endfor; ?>
  </div>

  <?php if ($currentPage < $totalPages): ?>
    <?php $query['page'] = $currentPage + 1; ?>
    <a href="<?= url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', $query) ?>" class="btn btn--ghost btn--sm" aria-label="Next Page">
      <span>Next</span>
      <?= icon('arrow-right') ?>
    </a>
  <?php endif; ?>
</nav>
