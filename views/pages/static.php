<?php
declare(strict_types=1);

$page = $page ?? ['title' => 'Information', 'summary' => '', 'content' => []];

$iconMap = [
    'Data Collection'     => 'database',
    'Security Storage'    => 'lock',
    'Your Rights'         => 'shield',
    'Domestic Rates'      => 'truck',
    'Fulfillment Times'   => 'package',
    'Tracking & Transit'  => 'globe',
    'Return Window'       => 'arrow-left',
    'How to Initiate'     => 'mail',
    'Refund Processing'   => 'check',
    'Account Terms'       => 'user',
    'Product Inventory'   => 'package',
    'Governing Law'       => 'shield',
];
?>
<div class="container content-card-shell">
  <article class="content-card">
    <header class="content-card__header">
      <h1 class="content-card__title"><?= e($page['title']) ?></h1>
      <?php if (!empty($page['summary'])): ?>
        <p class="content-card__summary"><?= e($page['summary']) ?></p>
      <?php endif; ?>
    </header>

    <div class="content-card__grid">
      <?php foreach ($page['content'] as $sectionTitle => $sectionBody): ?>
        <?php $iconName = $iconMap[$sectionTitle] ?? 'file-text'; ?>
        <section class="content-subcard">
          <div class="content-subcard__icon" aria-hidden="true">
            <?= icon($iconName) ?>
          </div>
          <h2 class="content-subcard__title"><?= e($sectionTitle) ?></h2>
          <p class="content-subcard__body"><?= e($sectionBody) ?></p>
        </section>
      <?php endforeach; ?>
    </div>
  </article>
</div>
