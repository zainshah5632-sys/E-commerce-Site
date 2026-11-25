<?php
declare(strict_types=1);

$products = $products ?? [];
$categories = $categories ?? [];
$activeCategory = $activeCategory ?? null;
$q = $q ?? '';
$minPrice = $minPrice ?? '';
$maxPrice = $maxPrice ?? '';
$sort = $sort ?? 'newest';
$page = $page ?? 1;
$pages = $pages ?? 1;
$total = $total ?? 0;
$categoryCounts = $categoryCounts ?? [];
$allTotal = $allTotal ?? $total;
?>

<div class="container">
  <div style="padding-top: var(--s-6); margin-bottom: var(--s-3);">
    <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-2);">
      <a href="<?= url('/') ?>">Home</a>
      <span>/</span>
      <?php if ($activeCategory): ?>
        <a href="<?= url('/shop') ?>">Shop</a>
        <span>/</span>
        <span style="color: var(--text-1);"><?= e($activeCategory['name']) ?></span>
      <?php else: ?>
        <span style="color: var(--text-1);">Shop</span>
      <?php endif; ?>
    </div>

    <h1 style="font-size: 2.25rem;">
      <?= e($activeCategory ? $activeCategory['name'] : 'All Hardware & Accessories') ?>
    </h1>
    <?php if ($activeCategory && !empty($activeCategory['description'])): ?>
      <p style="margin-top: var(--s-1); max-width: 680px;"><?= e($activeCategory['description']) ?></p>
    <?php endif; ?>
  </div>

  <div class="shop-layout">
    <!-- Filters Sidebar -->
    <aside class="shop-sidebar glass">
      <div class="shop-sidebar__title">
        <strong>Filters</strong>
      </div>

      <form action="<?= url('/shop') ?>" method="GET">
        <!-- Search query -->
        <div class="filter-group">
          <label for="shop-q" class="filter-group__title">Search</label>
          <div style="position: relative;">
            <input
              type="search"
              id="shop-q"
              name="q"
              class="form-input"
              value="<?= ea($q) ?>"
              placeholder="Search products..."
            >
          </div>
        </div>

        <!-- Categories Section (Interactive Vertical Navigation) -->
        <div class="filter-group">
          <div class="category-filter-header">Category</div>
          <div class="category-nav" role="radiogroup" aria-label="Product Categories">
            <!-- All Categories -->
            <label class="category-nav__item <?= empty($activeCategory) ? 'is-active' : '' ?>">
              <input
                type="radio"
                name="category"
                value=""
                class="category-nav__radio"
                <?= empty($activeCategory) ? 'checked' : '' ?>
              >
              <span class="category-nav__label">All Categories</span>
              <span class="category-nav__badge"><?= (int)$allTotal ?></span>
            </label>

            <!-- Dynamic Categories (Audio, Wearables, Desk & Home, Accessories) -->
            <?php foreach ($categories as $cat): ?>
              <?php
                $isActive = ($activeCategory && $activeCategory['slug'] === $cat['slug']);
                $catCount = $categoryCounts[$cat['id']] ?? null;
              ?>
              <label class="category-nav__item <?= $isActive ? 'is-active' : '' ?>">
                <input
                  type="radio"
                  name="category"
                  value="<?= ea($cat['slug']) ?>"
                  class="category-nav__radio"
                  <?= $isActive ? 'checked' : '' ?>
                >
                <span class="category-nav__label"><?= e($cat['name']) ?></span>
                <?php if ($catCount !== null): ?>
                  <span class="category-nav__badge"><?= (int)$catCount ?></span>
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range -->
        <div class="filter-group">
          <div class="filter-group__title">Price Range (<?= e(settings('currency_symbol', 'Rs')) ?>)</div>
          <div style="display: flex; gap: var(--s-2); align-items: center;">
            <input type="number" name="min_price" class="form-input" placeholder="Min (2000)" value="<?= ea($minPrice) ?>" min="0" step="100" style="padding: 8px 12px;">
            <span style="color: var(--text-3);">&ndash;</span>
            <input type="number" name="max_price" class="form-input" placeholder="Max (5000)" value="<?= ea($maxPrice) ?>" min="0" step="100" style="padding: 8px 12px;">
          </div>
        </div>

        <!-- Preserved Sort -->
        <input type="hidden" name="sort" value="<?= ea($sort) ?>">

        <div style="display: flex; flex-direction: column; gap: var(--s-2); margin-top: var(--s-4);">
          <button type="submit" class="btn btn--primary btn--block btn--sm">Apply Filters</button>
          <?php if (!empty($q) || !empty($minPrice) || !empty($maxPrice) || $activeCategory): ?>
            <a href="<?= url('/shop') ?>" class="btn btn--ghost btn--block btn--sm" style="text-align: center;">Reset Filters</a>
          <?php endif; ?>
        </div>
      </form>
    </aside>

    <!-- Main Catalog View -->
    <section>
      <!-- Controls Topbar -->
      <div class="glass--subtle" style="padding: var(--s-3) var(--s-4); display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-4); flex-wrap: wrap; gap: var(--s-3);">
        <div style="font-size: 0.9rem; color: var(--text-2);">
          Showing <strong><?= count($products) ?></strong> of <strong><?= $total ?></strong> products
        </div>

        <!-- Sort Select -->
        <form action="<?= url('/shop') ?>" method="GET" style="display: flex; align-items: center; gap: var(--s-2);">
          <?php if (!empty($q)): ?><input type="hidden" name="q" value="<?= ea($q) ?>"><?php endif; ?>
          <?php if ($activeCategory): ?><input type="hidden" name="category" value="<?= ea($activeCategory['slug']) ?>"><?php endif; ?>
          <?php if (!empty($minPrice)): ?><input type="hidden" name="min_price" value="<?= ea($minPrice) ?>"><?php endif; ?>
          <?php if (!empty($maxPrice)): ?><input type="hidden" name="max_price" value="<?= ea($maxPrice) ?>"><?php endif; ?>

          <label for="shop-sort" style="font-size: 0.85rem; font-weight: 600; color: var(--text-2);">Sort By:</label>
          <select id="shop-sort" name="sort" class="form-select" data-auto-submit style="padding: 6px 12px; font-size: 0.85rem; width: auto;">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Alphabetical (A–Z)</option>
          </select>
        </form>
      </div>

      <!-- Active Filter Chips -->
      <?php if (!empty($q) || !empty($minPrice) || !empty($maxPrice) || $activeCategory): ?>
        <div style="display: flex; flex-wrap: wrap; gap: var(--s-2); margin-bottom: var(--s-4); align-items: center;">
          <span style="font-size: 0.82rem; color: var(--text-3);">Active Filters:</span>
          <?php if (!empty($q)): ?>
            <span class="badge badge--brand">Search: "<?= e($q) ?>"</span>
          <?php endif; ?>
          <?php if ($activeCategory): ?>
            <span class="badge badge--brand"><?= e($activeCategory['name']) ?></span>
          <?php endif; ?>
          <?php if (!empty($minPrice) || !empty($maxPrice)): ?>
            <span class="badge badge--brand">Price: $<?= e($minPrice ?: '0') ?> &ndash; $<?= e($maxPrice ?: '&infin;') ?></span>
          <?php endif; ?>
          <a href="<?= url('/shop') ?>" style="font-size: 0.82rem; color: var(--brand-500); font-weight: 600; margin-left: var(--s-2);">Clear all</a>
        </div>
      <?php endif; ?>

      <!-- Products Grid -->
      <?php if (empty($products)): ?>
        <div class="glass" style="text-align: center; padding: var(--s-8) var(--s-4);">
          <div style="color: var(--text-3); margin-bottom: var(--s-3);"><?= icon('search') ?></div>
          <h2>No matching products found</h2>
          <p style="margin-top: var(--s-2); margin-bottom: var(--s-5);">Try adjusting your search criteria or resetting filters.</p>
          <a href="<?= url('/shop') ?>" class="btn btn--primary btn--sm">View All Products</a>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php require dirname(__DIR__) . '/partials/pagination.php'; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
