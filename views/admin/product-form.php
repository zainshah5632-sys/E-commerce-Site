<?php
declare(strict_types=1);

$product = $product ?? null;
$categories = $categories ?? [];
$errors = $errors ?? [];
$isEdit = !empty($product['id']);

$priceMajor = $product ? number_format(((int)($product['price_cents'] ?? 0)) / 100, 2, '.', '') : '';
$compareMajor = (!empty($product['compare_at_cents'])) ? number_format(((int)$product['compare_at_cents']) / 100, 2, '.', '') : '';
$currentImages = is_array($product['images'] ?? null) ? $product['images'] : [];
?>

<div class="admin-page-container">
  <!-- Breadcrumb -->
  <div class="admin-breadcrumb">
    <a href="<?= url('/admin') ?>">Admin</a>
    <span class="sep">/</span>
    <a href="<?= url('/admin/products') ?>">Products</a>
    <span class="sep">/</span>
    <span class="current"><?= $isEdit ? 'Edit ' . e($product['name']) : 'New Product' ?></span>
  </div>

  <!-- Header -->
  <div class="admin-header-row">
    <div>
      <h1 class="admin-title"><?= $isEdit ? 'Edit ' . e($product['name']) : 'Create New Product' ?></h1>
      <p class="admin-subtitle">Set up catalog details, pricing structures, inventory tracking, and high-resolution assets.</p>
    </div>
    <div class="admin-header-actions">
      <a href="<?= url('/admin/products') ?>" class="btn-admin-ghost">
        <?= icon('arrow-left') ?>
        <span>Back to Products</span>
      </a>
      <?php if ($isEdit && !empty($product['slug'])): ?>
        <a href="<?= url('/product/' . rawurlencode($product['slug'])) ?>" target="_blank" class="btn-admin-secondary">
          <?= icon('external-link') ?>
          <span>View on Storefront</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert alert--error" style="margin-bottom: var(--s-4);">
      <span><?= e($errors['general']) ?></span>
    </div>
  <?php endif; ?>

  <form action="<?= $isEdit ? url('/admin/products/' . ea($product['id'])) : url('/admin/products') ?>" method="POST" enctype="multipart/form-data" class="admin-editor-layout">
    <?= csrf_field() ?>

    <!-- Main Column (Left: 68%) -->
    <div class="admin-editor-main">
      <!-- Card 1: Core Information -->
      <div class="admin-card">
        <h2 class="card-section-title">Product Details</h2>
        <p class="card-section-desc">The primary identification information displayed across search engines and catalog listings.</p>

        <div class="form-group">
          <label for="prod-name" class="admin-label">Product Title <span class="required">*</span></label>
          <input
            type="text"
            id="prod-name"
            name="name"
            class="admin-input"
            value="<?= ea(old('name', $product['name'] ?? '')) ?>"
            placeholder="e.g. Prism Acoustic Studio Monitors"
            required
            autofocus
          >
          <?php if (!empty($errors['name'])): ?>
            <span class="admin-field-error"><?= e($errors['name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="prod-slug" class="admin-label">URL Slug</label>
            <input
              type="text"
              id="prod-slug"
              name="slug"
              class="admin-input"
              value="<?= ea(old('slug', $product['slug'] ?? '')) ?>"
              placeholder="auto-generated-from-title"
            >
            <span class="admin-help-text">Unique URL identifier on the storefront. Leave blank to auto-generate.</span>
            <?php if (!empty($errors['slug'])): ?>
              <span class="admin-field-error"><?= e($errors['slug']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="prod-sku" class="admin-label">SKU (Stock Keeping Unit)</label>
            <input
              type="text"
              id="prod-sku"
              name="sku"
              class="admin-input"
              value="<?= ea(old('sku', $product['sku'] ?? '')) ?>"
              placeholder="e.g. GC-PRISM-01"
            >
            <span class="admin-help-text">Unique barcode or warehouse tracking code.</span>
          </div>
        </div>

        <div class="form-group">
          <label for="prod-short" class="admin-label">Short Summary (1–2 sentences for cards &amp; meta preview)</label>
          <input
            type="text"
            id="prod-short"
            name="short_description"
            class="admin-input"
            value="<?= ea(old('short_description', $product['short_description'] ?? '')) ?>"
            maxlength="255"
            placeholder="e.g. Precision tuned acoustic transducers enclosed in frosted acrylic chamber."
          >
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label for="prod-desc" class="admin-label">Full Product Description</label>
          <textarea
            id="prod-desc"
            name="description"
            class="admin-textarea"
            rows="5"
            placeholder="Comprehensive description of build materials, specifications, and acoustic profile..."
          ><?= e(old('description', $product['description'] ?? '')) ?></textarea>
        </div>
      </div>

      <!-- Card 2: Pricing & Promotional Discounts -->
      <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <h2 class="card-section-title" style="margin-bottom: 0;">Pricing &amp; Discounts</h2>
          <span class="admin-tag-badge text-emerald" id="discount-calc-badge" style="display: none;">Active Discount</span>
        </div>
        <p class="card-section-desc">Manage customer retail pricing, compare-at promotional anchors, and calculated margins.</p>

        <div class="form-grid-3">
          <div class="form-group">
            <label for="prod-price" class="admin-label">
              <span>Selling Price ($ USD) <span class="required">*</span></span>
            </label>
            <div class="admin-input-prefix-wrap">
              <span class="prefix">$</span>
              <input
                type="number"
                id="prod-price"
                name="price"
                class="admin-input admin-input--prefixed"
                value="<?= ea(old('price', $priceMajor)) ?>"
                step="0.01"
                min="0"
                placeholder="0.00"
                required
              >
            </div>
            <?php if (!empty($errors['price_cents'])): ?>
              <span class="admin-field-error"><?= e($errors['price_cents']) ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="prod-compare" class="admin-label">
              <span>Compare-At Price ($ USD)</span>
            </label>
            <div class="admin-input-prefix-wrap">
              <span class="prefix">$</span>
              <input
                type="number"
                id="prod-compare"
                name="compare_at_price"
                class="admin-input admin-input--prefixed"
                value="<?= ea(old('compare_at_price', $compareMajor)) ?>"
                step="0.01"
                min="0"
                placeholder="0.00"
              >
            </div>
            <span class="admin-help-text">Shows crossed-out original price.</span>
          </div>

          <div class="form-group">
            <label for="prod-stock" class="admin-label">
              <span>Inventory Stock <span class="required">*</span></span>
            </label>
            <input
              type="number"
              id="prod-stock"
              name="stock"
              class="admin-input"
              value="<?= ea(old('stock', (string)($product['stock'] ?? 10))) ?>"
              min="0"
              required
            >
            <?php if (!empty($errors['stock'])): ?>
              <span class="admin-field-error"><?= e($errors['stock']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Discount Presets -->
        <div class="discount-presets-strip">
          <span class="label">Quick Discount Presets:</span>
          <button type="button" class="btn-preset" data-action="apply-discount" data-pct="10">10% Off</button>
          <button type="button" class="btn-preset" data-action="apply-discount" data-pct="20">20% Off</button>
          <button type="button" class="btn-preset" data-action="apply-discount" data-pct="30">30% Off</button>
          <button type="button" class="btn-preset" data-action="apply-discount" data-pct="50">50% Off</button>
        </div>
      </div>

      <!-- Card 3: Media Gallery -->
      <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
          <h2 class="card-section-title" style="margin-bottom: 0;">Product Gallery</h2>
          <span class="admin-help-text">Slot count: <strong><?= count($currentImages) ?> / 5</strong></span>
        </div>
        <p class="card-section-desc">Upload up to 5 visual angles. The first photo serves as the storefront cover image.</p>

        <!-- Current Images Grid -->
        <?php if (!empty($currentImages)): ?>
          <div class="gallery-thumbs-grid">
            <?php foreach ($currentImages as $idx => $img): ?>
              <div class="gallery-thumb-item">
                <img src="<?= e($img) ?>" alt="Product image <?= $idx + 1 ?>">
                <span class="thumb-badge"><?= $idx === 0 ? 'Primary Cover' : '#' . ($idx + 1) ?></span>
                <button
                  type="submit"
                  name="remove_image"
                  value="<?= ea($img) ?>"
                  class="thumb-del-btn"
                  title="Remove this image"
                  aria-label="Remove image"
                >
                  <?= icon('close') ?>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Direct Image URL Inputs -->
        <details class="admin-details" <?= empty($currentImages) ? 'open' : '' ?>>
          <summary class="admin-details-summary">
            <span>Provide Direct Image URLs (Up to 5 Links)</span>
          </summary>
          <div class="admin-details-body">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <div class="form-group" style="margin-bottom: var(--s-3);">
                <label class="admin-label" style="font-size: 0.78rem;">
                  Image Slot <?= $i + 1 ?> <?= $i === 0 ? '(Primary Cover)' : '' ?>
                </label>
                <input
                  type="url"
                  name="image_urls[]"
                  class="admin-input"
                  style="font-size: 0.85rem;"
                  placeholder="https://images.unsplash.com/... or CDN link"
                  value="<?= ea($currentImages[$i] ?? '') ?>"
                >
              </div>
            <?php endfor; ?>
          </div>
        </details>

        <!-- File Upload Area -->
        <?php if (count($currentImages) < 5): ?>
          <div class="form-group" style="margin-top: var(--s-4); margin-bottom: 0;">
            <label for="image-uploads" class="admin-label">Or Upload Image Files (JPEG, PNG, WebP ≤ 2MB)</label>
            <input type="file" id="image-uploads" name="image_uploads[]" multiple accept="image/jpeg,image/png,image/webp" class="admin-input">
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar Column (Right: 32%) -->
    <div class="admin-editor-side">
      <!-- Card 1: Organization & Status -->
      <div class="admin-card">
        <h2 class="card-section-title">Status &amp; Category</h2>
        <p class="card-section-desc">Control visibility across categories and customer navigation.</p>

        <!-- Status Toggle -->
        <div class="toggle-control-group">
          <div class="toggle-text">
            <span class="title">Active in Store</span>
            <span class="sub">Visible in search and catalog</span>
          </div>
          <label class="switch">
            <input type="checkbox" name="is_active" value="1" <?= old('is_active', $product['is_active'] ?? true) ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
        </div>

        <div class="admin-divider"></div>

        <!-- Featured Toggle -->
        <div class="toggle-control-group">
          <div class="toggle-text">
            <span class="title">Featured Product</span>
            <span class="sub">Highlight in home showcase</span>
          </div>
          <label class="switch">
            <input type="checkbox" name="is_featured" value="1" <?= old('is_featured', $product['is_featured'] ?? false) ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
        </div>

        <div class="admin-divider"></div>

        <!-- Category Dropdown -->
        <div class="form-group" style="margin-bottom: 0;">
          <label for="prod-cat" class="admin-label">Assigned Category</label>
          <select id="prod-cat" name="category_id" class="admin-select">
            <option value="">None / Unassigned</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= ea($cat['id']) ?>" <?= (old('category_id', $product['category_id'] ?? '') === $cat['id']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Card 2: Sticky Action Buttons -->
      <div class="admin-card sticky-action-card">
        <h3 class="card-section-title" style="font-size: 0.95rem;">Publishing Actions</h3>
        <button type="submit" class="btn-admin-primary btn-block">
          <?= icon('check') ?>
          <span><?= $isEdit ? 'Save Changes' : 'Publish Product' ?></span>
        </button>
        <a href="<?= url('/admin/products') ?>" class="btn-admin-ghost btn-block" style="text-align: center; margin-top: var(--s-2);">
          Discard Changes
        </a>
      </div>
    </div>
  </form>
</div>
