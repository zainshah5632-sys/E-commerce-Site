<?php
declare(strict_types=1);

$categories = $categories ?? [];
$errors = $errors ?? [];
$activeCount = count(array_filter($categories, fn($c) => !empty($c['is_active'])));
?>

<div class="category-page-container">
  <!-- Page Header -->
  <header class="category-page-header">
    <div class="category-page-header__meta">
      <nav class="category-breadcrumbs" aria-label="Breadcrumb">
        <a href="<?= url('/admin') ?>">Admin</a>
        <span class="category-breadcrumbs__sep">/</span>
        <span class="category-breadcrumbs__current">Categories</span>
      </nav>
      <h1 class="category-page-title">Categories &amp; Navigation</h1>
      <p class="category-page-desc">Organize catalog structure, product groupings, and storefront navigation menus.</p>
    </div>

    <div class="category-page-header__actions">
      <!-- Summary Badges -->
      <div class="category-stat-pills" aria-label="Category Statistics">
        <span class="category-stat-pill">
          <span class="category-stat-pill__label">Total:</span>
          <strong class="category-stat-pill__val" id="cat-stat-total"><?= count($categories) ?></strong>
        </span>
        <span class="category-stat-pill category-stat-pill--active">
          <span class="category-stat-pill__dot"></span>
          <span class="category-stat-pill__label">Active:</span>
          <strong class="category-stat-pill__val" id="cat-stat-active"><?= $activeCount ?></strong>
        </span>
      </div>

      <!-- Add Category Button -->
      <button
        type="button"
        class="category-btn-primary"
        data-action="add-category"
        aria-haspopup="dialog"
      >
        <?= icon('plus', 16) ?>
        <span>Add Category</span>
      </button>
    </div>
  </header>

  <!-- Server Validation Errors (if any) -->
  <?php if (!empty($errors)): ?>
    <div class="category-alert category-alert--error" role="alert">
      <div class="category-alert__icon">
        <?= icon('shield', 18) ?>
      </div>
      <div class="category-alert__content">
        <h4 class="category-alert__title">Please review the following errors:</h4>
        <ul class="category-alert__list">
          <?php foreach ($errors as $field => $err): ?>
            <li><?= e(is_array($err) ? implode(', ', $err) : $err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <!-- Active Categories Card & Table -->
  <section class="category-table-card" aria-label="Categories Directory">
    <!-- Toolbar -->
    <div class="category-toolbar">
      <div class="category-toolbar__left">
        <!-- Live Search -->
        <div class="category-search-wrap">
          <span class="category-search-icon" aria-hidden="true">
            <?= icon('search', 16) ?>
          </span>
          <input
            type="search"
            id="category-search"
            class="category-search-input"
            placeholder="Search categories by name, slug or description..."
            autocomplete="off"
            aria-label="Search categories"
          >
          <button type="button" id="category-search-clear" class="category-search-clear" style="display: none;" aria-label="Clear search">
            <?= icon('close', 14) ?>
          </button>
        </div>

        <!-- Status Filter -->
        <div class="category-filter-wrap">
          <label for="category-status-filter" class="sr-only">Filter by status</label>
          <select id="category-status-filter" class="category-select" aria-label="Filter by status">
            <option value="all">All Statuses</option>
            <option value="active">Active in Navigation</option>
            <option value="hidden">Hidden from Navigation</option>
          </select>
        </div>
      </div>

      <div class="category-toolbar__right">
        <span class="category-count-badge" id="category-records-count">
          Showing <strong id="category-visible-count"><?= count($categories) ?></strong> of <?= count($categories) ?> categories
        </span>
      </div>
    </div>

    <!-- Categories Data Table -->
    <div class="category-table-wrapper">
      <table class="category-table" id="category-table">
        <thead>
          <tr>
            <th scope="col" class="th-category">Category</th>
            <th scope="col" class="th-slug">URL Slug</th>
            <th scope="col" class="th-order text-center">Sort Priority</th>
            <th scope="col" class="th-status">Status</th>
            <th scope="col" class="th-actions text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="category-table-body">
          <?php if (empty($categories)): ?>
            <tr id="category-empty-initial">
              <td colspan="5" class="category-empty-cell">
                <div class="category-empty-state">
                  <div class="category-empty-icon">
                    <?= icon('folder', 28) ?>
                  </div>
                  <h3 class="category-empty-title">No categories found</h3>
                  <p class="category-empty-text">Organize your store catalog by creating your first product category.</p>
                  <button type="button" class="category-btn-primary category-btn-sm mt-3" data-action="add-category">
                    <?= icon('plus', 14) ?>
                    <span>Add First Category</span>
                  </button>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <?php
                $isActive = !empty($cat['is_active']);
                $sortOrder = (int)($cat['sort_order'] ?? 0);
                $catId = (string)($cat['id'] ?? '');
                $catName = (string)($cat['name'] ?? '');
                $catSlug = (string)($cat['slug'] ?? '');
                $catDesc = (string)($cat['description'] ?? '');
              ?>
              <tr
                class="category-row"
                data-category-row
                data-id="<?= ea($catId) ?>"
                data-name="<?= ea($catName) ?>"
                data-slug="<?= ea($catSlug) ?>"
                data-desc="<?= ea($catDesc) ?>"
                data-sort="<?= $sortOrder ?>"
                data-status="<?= $isActive ? 'active' : 'hidden' ?>"
              >
                <!-- Category Name & Description -->
                <td class="td-category">
                  <div class="category-name-cell">
                    <div class="category-avatar-badge" aria-hidden="true">
                      <?= icon('folder', 16) ?>
                    </div>
                    <div class="category-name-info">
                      <span class="category-row-name"><?= e($catName) ?></span>
                      <?php if ($catDesc !== ''): ?>
                        <p class="category-row-desc" title="<?= ea($catDesc) ?>"><?= e($catDesc) ?></p>
                      <?php else: ?>
                        <span class="category-row-desc category-row-desc--empty">No description provided</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>

                <!-- URL Slug -->
                <td class="td-slug">
                  <code class="category-slug-badge" title="Storefront path: /category/<?= ea($catSlug) ?>">
                    /<?= e($catSlug) ?>
                  </code>
                </td>

                <!-- Sort Priority -->
                <td class="td-order text-center">
                  <span class="category-order-badge" title="Sort order index">
                    #<?= $sortOrder ?>
                  </span>
                </td>

                <!-- Status Badge -->
                <td class="td-status">
                  <?php if ($isActive): ?>
                    <span class="category-status-pill category-status-pill--active">
                      <span class="category-status-dot"></span>
                      <span>Active</span>
                    </span>
                  <?php else: ?>
                    <span class="category-status-pill category-status-pill--hidden">
                      <span class="category-status-dot"></span>
                      <span>Hidden</span>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Actions -->
                <td class="td-actions text-right">
                  <div class="category-actions-group">
                    <!-- View Storefront Link -->
                    <a
                      href="<?= url('/category/' . rawurlencode($catSlug)) ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="category-action-btn"
                      title="View category page on storefront"
                      aria-label="View <?= ea($catName) ?> on storefront"
                    >
                      <?= icon('external-link', 15) ?>
                    </a>

                    <!-- Edit Drawer Trigger -->
                    <button
                      type="button"
                      class="category-action-btn category-action-btn--edit"
                      data-action="edit-category"
                      title="Edit category"
                      aria-label="Edit <?= ea($catName) ?>"
                    >
                      <?= icon('pencil', 15) ?>
                    </button>

                    <!-- Delete Category Form -->
                    <form action="<?= url('/admin/categories/' . ea($catId) . '/delete') ?>" method="POST" class="category-delete-form">
                      <?= csrf_field() ?>
                      <button
                        type="submit"
                        class="category-action-btn category-action-btn--delete"
                        data-confirm="Are you sure you want to delete category &quot;<?= ea($catName) ?>&quot;? Products in this category will remain available in the store as unassigned."
                        title="Delete category"
                        aria-label="Delete <?= ea($catName) ?>"
                      >
                        <?= icon('trash', 15) ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Empty Search Filter State (Dynamic) -->
          <tr id="category-empty-filter" style="display: none;">
            <td colspan="5" class="category-empty-cell">
              <div class="category-empty-state">
                <div class="category-empty-icon">
                  <?= icon('search', 24) ?>
                </div>
                <h3 class="category-empty-title">No matching categories</h3>
                <p class="category-empty-text">No categories match your search or filter criteria. Try adjusting your query.</p>
                <button type="button" id="category-reset-filters" class="category-btn-secondary category-btn-sm mt-3">
                  Reset Filters
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</div>

<!-- =====================================================================
     Modern Right-Side Slide-Over Drawer: Add / Edit Category
     ===================================================================== -->
<div id="category-drawer-backdrop" class="category-drawer-backdrop" aria-hidden="true"></div>

<aside
  id="category-drawer"
  class="category-drawer"
  role="dialog"
  aria-modal="true"
  aria-labelledby="category-drawer-title"
  aria-describedby="category-drawer-desc"
  tabindex="-1"
>
  <!-- Drawer Header (Sticky Top) -->
  <div class="category-drawer__header">
    <div class="category-drawer__header-content">
      <div class="category-drawer__tag" id="category-drawer-tag">Storefront Taxonomy</div>
      <h2 id="category-drawer-title" class="category-drawer__title">Add Category</h2>
      <p id="category-drawer-desc" class="category-drawer__desc">Configure category identity, URL path, and navigation visibility.</p>
    </div>
    <button
      type="button"
      id="category-drawer-close"
      class="category-drawer__close-btn"
      aria-label="Close drawer"
    >
      <?= icon('close', 18) ?>
    </button>
  </div>

  <!-- Drawer Body (Scrollable Form) -->
  <form id="category-drawer-form" action="<?= url('/admin/categories') ?>" method="POST" class="category-drawer__form">
    <?= csrf_field() ?>
    <input type="hidden" id="category-id" name="id" value="">

    <div class="category-drawer__body">
      <!-- Category Name -->
      <div class="category-form-group">
        <label for="cat-name" class="category-form-label">
          Category Name <span class="text-rose-500">*</span>
        </label>
        <input
          type="text"
          id="cat-name"
          name="name"
          class="category-form-input"
          placeholder="e.g. Studio Monitors"
          required
          maxlength="80"
          autocomplete="off"
        >
        <p class="category-form-helper">The public category title shown in navigation menus, headings, and breadcrumbs.</p>
      </div>

      <!-- URL Slug -->
      <div class="category-form-group">
        <div class="flex items-center justify-between mb-1">
          <label for="cat-slug" class="category-form-label mb-0">
            URL Slug <span class="text-rose-500">*</span>
          </label>
          <button
            type="button"
            id="cat-slug-regenerate"
            class="category-slug-auto-btn"
            title="Generate slug from name"
          >
            Auto-generate
          </button>
        </div>
        <div class="category-slug-input-group">
          <span class="category-slug-prefix" aria-hidden="true">/category/</span>
          <input
            type="text"
            id="cat-slug"
            name="slug"
            class="category-slug-input"
            placeholder="studio-monitors"
            required
            pattern="[a-z0-9\-]+"
            title="Lowercase letters, numbers, and hyphens only"
            autocomplete="off"
          >
        </div>
        <p class="category-form-helper">Permanent URL path. Lowercase letters, numbers, and hyphens only.</p>
      </div>

      <!-- Description -->
      <div class="category-form-group">
        <label for="cat-desc" class="category-form-label">
          Description
        </label>
        <textarea
          id="cat-desc"
          name="description"
          rows="3"
          class="category-form-textarea"
          placeholder="Brief summary displayed on category banners, filter sidebars, and SEO metadata cards..."
        ></textarea>
        <p class="category-form-helper">Optional. Context for catalog headers and search engine descriptions.</p>
      </div>

      <!-- Sort Priority -->
      <div class="category-form-group">
        <label for="cat-sort" class="category-form-label">
          Sort Priority
        </label>
        <div class="category-sort-input-wrap">
          <input
            type="number"
            id="cat-sort"
            name="sort_order"
            value="0"
            min="0"
            max="9999"
            class="category-form-input category-form-input--sort"
          >
          <div class="category-sort-stepper">
            <button type="button" id="cat-sort-inc" class="category-sort-btn" aria-label="Increase sort order">+</button>
            <button type="button" id="cat-sort-dec" class="category-sort-btn" aria-label="Decrease sort order">-</button>
          </div>
        </div>
        <p class="category-form-helper">Ordering weight in storefront menus. Lower numbers appear first (e.g. 0 before 10).</p>
      </div>

      <!-- Display in Storefront Navigation (Modern SaaS Toggle Switch) -->
      <div class="category-toggle-card">
        <div class="category-toggle-card__info">
          <span class="category-toggle-card__title">Display in Storefront Navigation</span>
          <p class="category-toggle-card__desc">When enabled, this category is exposed in header menus, breadcrumbs, and catalog discovery filters.</p>
        </div>
        <label class="category-switch" for="cat-active">
          <input
            type="checkbox"
            id="cat-active"
            name="is_active"
            value="1"
            checked
          >
          <span class="category-switch__slider" aria-hidden="true"></span>
          <span class="sr-only">Display in storefront navigation</span>
        </label>
      </div>
    </div>

    <!-- Drawer Footer (Sticky Bottom) -->
    <div class="category-drawer__footer">
      <button
        type="button"
        id="category-drawer-cancel"
        class="category-btn-secondary"
      >
        Cancel
      </button>
      <button
        type="submit"
        id="category-drawer-submit"
        class="category-btn-primary"
      >
        <?= icon('check', 16) ?>
        <span id="category-drawer-submit-label">Save Category</span>
      </button>
    </div>
  </form>
</aside>
