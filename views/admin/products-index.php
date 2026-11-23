<?php
declare(strict_types=1);

$products = $products ?? [];
$categories = $categories ?? [];
$filters = $filters ?? [];
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;

// Calculate active & low stock stats from the loaded products
$activeCount = 0;
$lowStockCount = 0;
foreach ($products as $p) {
    if (!empty($p['is_active'])) $activeCount++;
    if ((int)($p['stock'] ?? 0) <= 5) $lowStockCount++;
}
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <span class="current">Products &amp; Catalog</span>
  </div>

  <!-- Page Header -->
  <div class="admin-header-row">
    <div>
      <h1 class="admin-title">Products</h1>
      <p class="admin-subtitle">Manage catalog inventory, pricing, multi-image assets, and storefront availability.</p>
    </div>
    <div class="admin-header-actions">
      <a href="<?= url('/admin/products/new') ?>" class="btn-admin-primary">
        <?= icon('plus') ?>
        <span>Add Product</span>
      </a>
    </div>
  </div>

  <!-- Metrics Summary Strip -->
  <div class="admin-stats-strip">
    <div class="stat-chip">
      <span class="label">Total In Catalog</span>
      <span class="val"><?= (int)$total ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Filtered Active</span>
      <span class="val text-emerald"><?= $activeCount ?></span>
    </div>
    <div class="stat-divider"></div>
    <div class="stat-chip">
      <span class="label">Low / Out of Stock</span>
      <span class="val <?= $lowStockCount > 0 ? 'text-amber' : '' ?>"><?= $lowStockCount ?></span>
    </div>
  </div>

  <!-- Filter Toolbar Card -->
  <div class="admin-card filter-card">
    <form action="<?= url('/admin/products') ?>" method="GET" class="filter-form">
      <div class="filter-input-wrap">
        <span class="search-icon"><?= icon('search') ?></span>
        <input
          type="search"
          name="q"
          value="<?= ea($filters['q'] ?? '') ?>"
          placeholder="Search product title, SKU, or keywords..."
          class="admin-input admin-input--search"
        >
      </div>

      <div class="filter-select-wrap">
        <select name="category_id" class="admin-select" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= ea($cat['id']) ?>" <?= ($filters['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-select-wrap">
        <select name="is_active" class="admin-select" onchange="this.form.submit()">
          <option value="">All Statuses</option>
          <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active Only</option>
          <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Draft / Inactive</option>
        </select>
      </div>

      <button type="submit" class="btn-admin-secondary">
        <?= icon('filter') ?>
        <span>Filter</span>
      </button>

      <?php if (!empty($filters['q']) || !empty($filters['category_id']) || $filters['is_active'] !== '' && isset($filters['is_active'])): ?>
        <a href="<?= url('/admin/products') ?>" class="btn-admin-ghost">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Products Data Table Card -->
  <div class="admin-card table-card">
    <div class="admin-table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 72px;">Image</th>
            <th>Product Title &amp; SKU</th>
            <th>Category</th>
            <th>Price</th>
            <th>Inventory Stock</th>
            <th>Status</th>
            <th>Featured</th>
            <th style="text-align: right; width: 120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="8" class="empty-table-cell">
                <div class="empty-state-wrap">
                  <div class="empty-state-icon"><?= icon('folder') ?></div>
                  <h3>No products found</h3>
                  <p>Try adjusting your search criteria or add a new product to your catalog.</p>
                  <a href="<?= url('/admin/products/new') ?>" class="btn-admin-primary btn-admin-sm">
                    <?= icon('plus') ?>
                    <span>Create First Product</span>
                  </a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $prod): ?>
              <?php
                $imgs = $prod['images'] ?? [];
                $img = is_array($imgs) && count($imgs) > 0 ? $imgs[0] : asset('assets/img/placeholder.svg');
                $stock = (int)($prod['stock'] ?? 0);
                $isActive = !empty($prod['is_active']);
                $isFeatured = !empty($prod['is_featured']);
              ?>
              <tr class="admin-table-row">
                <td>
                  <div class="prod-thumb-box">
                    <img src="<?= e($img) ?>" alt="<?= ea($prod['name']) ?>" loading="lazy">
                  </div>
                </td>
                <td>
                  <div class="prod-info-cell">
                    <a href="<?= url('/admin/products/' . ea($prod['id']) . '/edit') ?>" class="prod-title-link">
                      <?= e($prod['name']) ?>
                    </a>
                    <div class="prod-sku-tag">
                      SKU: <code><?= e($prod['sku'] ?? 'N/A') ?></code>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="admin-tag-badge">
                    <?= e($prod['category']['name'] ?? 'Unassigned') ?>
                  </span>
                </td>
                <td>
                  <div class="prod-price-cell">
                    <span class="price-val"><?= money((int)$prod['price_cents']) ?></span>
                    <?php if (!empty($prod['compare_at_cents']) && (int)$prod['compare_at_cents'] > (int)$prod['price_cents']): ?>
                      <span class="compare-val"><?= money((int)$prod['compare_at_cents']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="stock-indicator-wrap">
                    <span class="stock-dot <?= $stock === 0 ? 'stock-dot--empty' : ($stock <= 5 ? 'stock-dot--low' : 'stock-dot--ok') ?>"></span>
                    <span class="stock-num font-semibold"><?= $stock ?></span>
                    <span class="stock-label text-xs">
                      <?= $stock === 0 ? 'Out of stock' : ($stock <= 5 ? 'Low stock' : 'in stock') ?>
                    </span>
                  </div>
                </td>
                <td>
                  <form action="<?= url('/admin/products/' . ea($prod['id']) . '/toggle') ?>" method="POST" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field" value="is_active">
                    <button type="submit" class="status-pill-btn <?= $isActive ? 'status-pill--active' : 'status-pill--inactive' ?>" title="Click to toggle status">
                      <span class="pill-dot"></span>
                      <span><?= $isActive ? 'Active' : 'Draft' ?></span>
                    </button>
                  </form>
                </td>
                <td>
                  <form action="<?= url('/admin/products/' . ea($prod['id']) . '/toggle') ?>" method="POST" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="field" value="is_featured">
                    <button type="submit" class="status-pill-btn <?= $isFeatured ? 'status-pill--featured' : 'status-pill--inactive' ?>" title="Click to toggle homepage featured status">
                      <span><?= $isFeatured ? '★ Featured' : 'Standard' ?></span>
                    </button>
                  </form>
                </td>
                <td style="text-align: right;">
                  <div class="admin-row-actions">
                    <a href="<?= url('/product/' . rawurlencode($prod['slug'])) ?>" target="_blank" class="action-icon-btn" title="View live product" aria-label="View live product">
                      <?= icon('external-link') ?>
                    </a>
                    <a href="<?= url('/admin/products/' . ea($prod['id']) . '/edit') ?>" class="action-icon-btn" title="Edit product" aria-label="Edit product">
                      <?= icon('pencil') ?>
                    </a>
                    <form action="<?= url('/admin/products/' . ea($prod['id']) . '/delete') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete <?= ea($prod['name']) ?> and its photos?');">
                      <?= csrf_field() ?>
                      <button type="submit" class="action-icon-btn action-icon-btn--danger" title="Delete product" aria-label="Delete product">
                        <?= icon('trash') ?>
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

    <!-- Table Footer with Pagination -->
    <?php if ($pages > 1): ?>
      <div class="admin-table-footer">
        <?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
