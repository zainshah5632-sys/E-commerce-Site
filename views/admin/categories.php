<?php
declare(strict_types=1);

$categories = $categories ?? [];
$errors = $errors ?? [];
?>

<div class="admin-header">
  <div>
    <h1>Categories (<?= count($categories) ?>)</h1>
    <p style="color: var(--text-3); margin-top: 4px;">Organize product groupings and navigation hierarchy.</p>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--s-6);">
  <!-- New Category Form -->
  <div class="glass--strong" style="padding: var(--s-5); height: fit-content;">
    <h2 style="font-size: 1.15rem; margin-bottom: var(--s-4);">Create Category</h2>

    <form action="<?= url('/admin/categories') ?>" method="POST">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="cat-name" class="form-label">Category Name</label>
        <input type="text" id="cat-name" name="name" class="form-input" required placeholder="e.g. Ergonomics">
        <?php if (!empty($errors['name'])): ?>
          <span class="form-error"><?= e($errors['name']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="cat-slug" class="form-label">Slug</label>
        <input type="text" id="cat-slug" name="slug" class="form-input" placeholder="e.g. ergonomics">
        <?php if (!empty($errors['slug'])): ?>
          <span class="form-error"><?= e($errors['slug']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="cat-desc" class="form-label">Description</label>
        <textarea id="cat-desc" name="description" class="form-textarea" rows="3" placeholder="Brief summary..."></textarea>
      </div>

      <div class="form-group">
        <label for="cat-sort" class="form-label">Sort Order</label>
        <input type="number" id="cat-sort" name="sort_order" class="form-input" value="0">
      </div>

      <div class="form-group">
        <label style="display: flex; align-items: center; gap: var(--s-2); cursor: pointer;">
          <input type="checkbox" name="is_active" value="1" checked>
          <span>Active in Navigation</span>
        </label>
      </div>

      <button type="submit" class="btn btn--primary btn--block btn--sm" style="margin-top: var(--s-4);">
        <span>Create Category</span>
      </button>
    </form>
  </div>

  <!-- Categories Table -->
  <div class="glass--strong data-table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Slug</th>
          <th>Order</th>
          <th>Active</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($categories)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: var(--text-3); padding: var(--s-6);">
              No categories created yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <div style="font-weight: 700;"><?= e($cat['name']) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-3);"><?= e($cat['description'] ?? '') ?></div>
              </td>
              <td><code><?= e($cat['slug']) ?></code></td>
              <td><?= (int)$cat['sort_order'] ?></td>
              <td>
                <span class="badge <?= !empty($cat['is_active']) ? 'badge--success' : 'badge--neutral' ?>">
                  <?= !empty($cat['is_active']) ? 'Active' : 'Hidden' ?>
                </span>
              </td>
              <td style="text-align: right;">
                <form action="<?= url('/admin/categories/' . ea($cat['id']) . '/delete') ?>" method="POST" style="display: inline;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Delete category <?= ea($cat['name']) ?>? Products in this category will become unassigned." style="color: var(--danger);">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
