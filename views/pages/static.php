<?php
declare(strict_types=1);

$page = $page ?? ['title' => 'Information', 'summary' => '', 'content' => []];
?>
<div class="container" style="padding-top: var(--s-7); padding-bottom: var(--s-9); max-width: 800px;">
  <div class="glass--strong" style="padding: var(--s-7);">
    <h1 style="font-size: 2.25rem; margin-bottom: var(--s-2);"><?= e($page['title']) ?></h1>
    <?php if (!empty($page['summary'])): ?>
      <p style="font-size: 1.15rem; color: var(--text-2); margin-bottom: var(--s-6); border-bottom: 1px solid var(--line); padding-bottom: var(--s-4);">
        <?= e($page['summary']) ?>
      </p>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: var(--s-5);">
      <?php foreach ($page['content'] as $sectionTitle => $sectionBody): ?>
        <div>
          <h2 style="font-size: 1.35rem; margin-bottom: var(--s-2);"><?= e($sectionTitle) ?></h2>
          <p style="line-height: 1.7;"><?= e($sectionBody) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
