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

<div class="admin-header">
  <div>
    <div class="breadcrumbs" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-2);">
      <a href="<?= url('/admin/products') ?>">Products</a>
      <span>/</span>
      <span style="color: var(--text-1);"><?= $isEdit ? 'Edit Product' : 'New Product' ?></span>
    </div>
    <h1><?= $isEdit ? 'Edit ' . e($product['name']) : 'Create Product' ?></h1>
    <p style="font-size: 0.9rem; color: var(--text-3); margin-top: 4px;">Manage catalog details, multi-image gallery, and promotional pricing.</p>
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert--error glass--strong">
    <span><?= e($errors['general']) ?></span>
  </div>
<?php endif; ?>

<form action="<?= $isEdit ? url('/admin/products/' . ea($product['id'])) : url('/admin/products') ?>" method="POST" enctype="multipart/form-data" class="glass--strong" style="padding: var(--s-6); max-width: 960px;">
  <?= csrf_field() ?>

  <!-- Basic Info -->
  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--s-4);">
    <div class="form-group">
      <label for="prod-name" class="form-label">Product Title</label>
      <input type="text" id="prod-name" name="name" class="form-input" value="<?= ea(old('name', $product['name'] ?? '')) ?>" placeholder="e.g. Prism ANC Headphones" required autofocus>
      <?php if (!empty($errors['name'])): ?>
        <span class="form-error"><?= e($errors['name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="prod-sku" class="form-label">SKU (Stock Keeping Unit)</label>
      <input type="text" id="prod-sku" name="sku" class="form-input" value="<?= ea(old('sku', $product['sku'] ?? '')) ?>" placeholder="e.g. GC-PRISM-01">
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4);">
    <div class="form-group">
      <label for="prod-slug" class="form-label">URL Slug</label>
      <input type="text" id="prod-slug" name="slug" class="form-input" value="<?= ea(old('slug', $product['slug'] ?? '')) ?>" placeholder="Auto-generated if blank">
      <?php if (!empty($errors['slug'])): ?>
        <span class="form-error"><?= e($errors['slug']) ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="prod-cat" class="form-label">Category</label>
      <select id="prod-cat" name="category_id" class="form-select">
        <option value="">None / Unassigned</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= ea($cat['id']) ?>" <?= (old('category_id', $product['category_id'] ?? '') === $cat['id']) ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Pricing & Discount Offer Section (Shopify Style) -->
  <div style="border: 1px solid var(--line); border-radius: var(--r-lg); padding: var(--s-5); background: var(--glass); margin-block: var(--s-5);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--s-3);">
      <h3 style="font-size: 1.1rem; margin: 0; display: flex; align-items: center; gap: 8px;">
        <?= icon('check') ?>
        <span>Pricing &amp; Promotional Discount Offer</span>
      </h3>
      <span class="badge badge--brand" id="discount-calc-badge" style="display: none;">Discount Active</span>
    </div>
    <p style="font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-4);">
      Set standard pricing and optional compare-at price to show discount badges, sale strike-throughs, and customer savings.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--s-4);">
      <div class="form-group">
        <label for="prod-price" class="form-label">
          <span>Sale / Selling Price ($ USD)</span>
          <strong style="color: var(--brand-600); font-size: 0.8rem;">(Customer pays this)</strong>
        </label>
        <input
          type="number"
          id="prod-price"
          name="price"
          class="form-input"
          value="<?= ea(old('price', $priceMajor)) ?>"
          step="0.01"
          min="0"
          placeholder="0.00"
          required
        >
        <?php if (!empty($errors['price_cents'])): ?>
          <span class="form-error"><?= e($errors['price_cents']) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="prod-compare" class="form-label">
          <span>Compare-At Price ($ USD)</span>
          <span style="color: var(--text-3); font-size: 0.8rem;">(Original crossed-out price)</span>
        </label>
        <input
          type="number"
          id="prod-compare"
          name="compare_at_price"
          class="form-input"
          value="<?= ea(old('compare_at_price', $compareMajor)) ?>"
          step="0.01"
          min="0"
          placeholder="e.g. 199.00"
        >
      </div>

      <div class="form-group">
        <label for="prod-stock" class="form-label">Inventory Quantity</label>
        <input
          type="number"
          id="prod-stock"
          name="stock"
          class="form-input"
          value="<?= ea(old('stock', (string)($product['stock'] ?? 10))) ?>"
          min="0"
          required
        >
        <?php if (!empty($errors['stock'])): ?>
          <span class="form-error"><?= e($errors['stock']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Discount Preset Buttons -->
    <div style="display: flex; align-items: center; gap: var(--s-2); margin-top: var(--s-3); flex-wrap: wrap;">
      <span style="font-size: 0.8rem; color: var(--text-3); font-weight: 500;">Quick Discount Presets:</span>
      <button type="button" class="btn btn--ghost btn--sm" data-action="apply-discount" data-pct="10">10% Off</button>
      <button type="button" class="btn btn--ghost btn--sm" data-action="apply-discount" data-pct="20">20% Off</button>
      <button type="button" class="btn btn--ghost btn--sm" data-action="apply-discount" data-pct="30">30% Off</button>
      <button type="button" class="btn btn--ghost btn--sm" data-action="apply-discount" data-pct="50">50% Off</button>
    </div>
  </div>

  <div class="form-group">
    <label for="prod-short" class="form-label">Short Summary (1–2 sentences for cards &amp; SEO)</label>
    <input type="text" id="prod-short" name="short_description" class="form-input" value="<?= ea(old('short_description', $product['short_description'] ?? '')) ?>" maxlength="255" placeholder="e.g. Precision tuned transducers enclosed in frosted acoustic chamber.">
  </div>

  <div class="form-group">
    <label for="prod-desc" class="form-label">Full Product Description</label>
    <textarea id="prod-desc" name="description" class="form-textarea" rows="4" placeholder="Detailed product specifications, materials, and features..."><?= e(old('description', $product['description'] ?? '')) ?></textarea>
  </div>

  <!-- Multi-Image Gallery Management (Up to 5 images) -->
  <div style="border-top: 1px solid var(--line); padding-top: var(--s-5); margin-top: var(--s-5);">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--s-3);">
      <h3 style="font-size: 1.1rem; margin: 0;">Product Gallery (Up to 5 Images)</h3>
      <span style="font-size: 0.85rem; color: var(--text-3);">
        Current images: <strong><?= count($currentImages) ?> / 5</strong>
      </span>
    </div>

    <!-- Existing Images Thumbnails -->
    <?php if (!empty($currentImages)): ?>
      <div style="display: flex; gap: var(--s-3); flex-wrap: wrap; margin-bottom: var(--s-5);">
        <?php foreach ($currentImages as $idx => $img): ?>
          <div style="position: relative; width: 100px; height: 100px; border-radius: var(--r-md); overflow: hidden; border: 1px solid var(--glass-border); box-shadow: var(--shadow-sm); background: #000;">
            <img src="<?= e($img) ?>" alt="" width="100" height="100" style="width: 100%; height: 100%; object-fit: cover;">
            <span style="position: absolute; bottom: 4px; left: 4px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: var(--r-sm);">
              <?= $idx === 0 ? 'Primary' : 'Photo ' . ($idx + 1) ?>
            </span>
            <button
              type="submit"
              name="remove_image"
              value="<?= ea($img) ?>"
              class="btn btn--danger btn--icon"
              style="position: absolute; top: 4px; right: 4px; padding: 4px; border-radius: var(--r-pill); background: rgba(239, 68, 68, 0.9);"
              title="Delete this image"
              aria-label="Delete Image"
            >
              <?= icon('close') ?>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- 5 Direct Image URL Slots -->
    <details style="margin-bottom: var(--s-4); background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--r-md); padding: var(--s-4);" <?= empty($currentImages) ? 'open' : '' ?>>
      <summary style="font-weight: 600; cursor: pointer; color: var(--text-1);">
        Provide Direct Image URLs (Up to 5 Links)
      </summary>
      <div style="margin-top: var(--s-3); display: flex; flex-direction: column; gap: var(--s-3);">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <div>
            <label class="form-label" style="font-size: 0.8rem; margin-bottom: 2px;">
              Image Slot <?= $i + 1 ?> <?= $i === 0 ? '(Primary Cover Image)' : '' ?>
            </label>
            <input
              type="url"
              name="image_urls[]"
              class="form-input"
              style="font-size: 0.85rem;"
              placeholder="https://images.unsplash.com/... or CDN link"
              value="<?= ea($currentImages[$i] ?? '') ?>"
            >
          </div>
        <?php endfor; ?>
      </div>
    </details>

    <!-- File Upload Section -->
    <?php if (count($currentImages) < 5): ?>
      <div class="form-group">
        <label for="image-uploads" class="form-label">Or Upload Image Files (JPEG, PNG, WebP ≤ 2MB each)</label>
        <input type="file" id="image-uploads" name="image_uploads[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input">
      </div>
    <?php endif; ?>
  </div>

  <!-- Visibility & Featured Flags -->
  <div style="display: flex; gap: var(--s-6); border-top: 1px solid var(--line); padding-top: var(--s-5); margin-top: var(--s-5);">
    <label style="display: flex; align-items: center; gap: var(--s-2); cursor: pointer;">
      <input type="checkbox" name="is_active" value="1" <?= old('is_active', $product['is_active'] ?? true) ? 'checked' : '' ?>>
      <span style="font-weight: 600;">Active in Online Store</span>
    </label>

    <label style="display: flex; align-items: center; gap: var(--s-2); cursor: pointer;">
      <input type="checkbox" name="is_featured" value="1" <?= old('is_featured', $product['is_featured'] ?? false) ? 'checked' : '' ?>>
      <span style="font-weight: 600;">Feature on Homepage Carousel</span>
    </label>
  </div>

  <div style="margin-top: var(--s-6); display: flex; gap: var(--s-3);">
    <button type="submit" class="btn btn--primary btn--lg">
      <span><?= $isEdit ? 'Save Changes' : 'Publish Product' ?></span>
    </button>
    <a href="<?= url('/admin/products') ?>" class="btn btn--ghost btn--lg">Cancel</a>
  </div>
</form>
