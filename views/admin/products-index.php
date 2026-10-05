<?php
declare(strict_types=1);

$products = $products ?? [];
$categories = $categories ?? [];
$filters = $filters ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
?>

<div class="admin-header">
  <div>
    <h1>Products (<?= $total ?>)</h1>
    <p style="color: var(--text-3); margin-top: 4px;">Manage catalog inventory, pricing, and visual assets.</p>
  </div>
  <a href="<?= url('/admin/products/new') ?>" class="btn btn--primary btn--sm">
    <?= icon('plus') ?>
    <span>Add Product</span>
  </a>
</div>

<!-- Filters Bar -->
<div class="glass" style="padding: var(--s-4); margin-bottom: var(--s-5);">
  <form action="<?= url('/admin/products') ?>" method="GET" style="display: flex; gap: var(--s-3); flex-wrap: wrap; align-items: center;">
    <div style="flex-grow: 1; min-width: 200px;">
      <input type="search" name="q" value="<?= ea($filters['q'] ?? '') ?>" class="form-input" placeholder="Search by name or SKU..." style="padding: 8px 12px;">
    </div>

    <div>
      <select name="category_id" class="form-select" style="padding: 8px 12px; width: auto;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= ea($cat['id']) ?>" <?= ($filters['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <select name="is_active" class="form-select" style="padding: 8px 12px; width: auto;">
        <option value="">All Statuses</option>
        <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active Only</option>
        <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive Only</option>
      </select>
    </div>

    <button type="submit" class="btn btn--ghost btn--sm">Filter</button>
  </form>
</div>

<!-- Products Table -->
<div class="glass--strong data-table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Image</th>
        <th>Name / SKU</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Active</th>
        <th>Featured</th>
        <th style="text-align: right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
        <tr>
          <td colspan="8" style="text-align: center; color: var(--text-3); padding: var(--s-6);">
            No products found matching the criteria.
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($products as $prod): ?>
          <?php
            $imgs = $prod['images'] ?? [];
            $img = is_array($imgs) && count($imgs) > 0 ? $imgs[0] : asset('assets/img/placeholder.svg');
            $stock = (int)$prod['stock'];
          ?>
          <tr>
            <td>
              <div style="width: 44px; height: 44px; border-radius: var(--r-sm); overflow: hidden; background: var(--glass-subtle);">
                <img src="<?= e($img) ?>" alt="" width="44" height="44" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
            </td>
            <td>
              <a href="<?= url('/product/' . rawurlencode($prod['slug'])) ?>" target="_blank" style="font-weight: 600;">
                <?= e($prod['name']) ?>
              </a>
              <div style="font-size: 0.8rem; color: var(--text-3);">SKU: <?= e($prod['sku'] ?? 'None') ?></div>
            </td>
            <td><?= e($prod['category']['name'] ?? 'None') ?></td>
            <td style="font-weight: 700;"><?= money((int)$prod['price_cents']) ?></td>
            <td>
              <span class="badge <?= $stock === 0 ? 'badge--danger' : ($stock <= 5 ? 'badge--warning' : 'badge--neutral') ?>">
                <?= $stock ?>
              </span>
            </td>
            <td>
              <form action="<?= url('/admin/products/' . ea($prod['id']) . '/toggle') ?>" method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="field" value="is_active">
                <button type="submit" class="badge <?= !empty($prod['is_active']) ? 'badge--success' : 'badge--neutral' ?>" style="cursor: pointer; border: none;">
                  <?= !empty($prod['is_active']) ? 'Active' : 'Disabled' ?>
                </button>
              </form>
            </td>
            <td>
              <form action="<?= url('/admin/products/' . ea($prod['id']) . '/toggle') ?>" method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="field" value="is_featured">
                <button type="submit" class="badge <?= !empty($prod['is_featured']) ? 'badge--brand' : 'badge--neutral' ?>" style="cursor: pointer; border: none;">
                  <?= !empty($prod['is_featured']) ? 'Yes' : 'No' ?>
                </button>
              </form>
            </td>
            <td style="text-align: right;">
              <div class="table-actions" style="justify-content: flex-end;">
                <a href="<?= url('/admin/products/' . ea($prod['id']) . '/edit') ?>" class="btn btn--ghost btn--sm">Edit</a>
                <form action="<?= url('/admin/products/' . ea($prod['id']) . '/delete') ?>" method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn--ghost btn--sm" data-confirm="Permanently delete <?= ea($prod['name']) ?> and all its images?" style="color: var(--danger);">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
