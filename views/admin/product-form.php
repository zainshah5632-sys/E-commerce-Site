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
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert--error glass--strong">
    <span><?= e($errors['general']) ?></span>
  </div>
<?php endif; ?>

<form action="<?= $isEdit ? url('/admin/products/' . ea($product['id'])) : url('/admin/products') ?>" method="POST" enctype="multipart/form-data" class="glass--strong" style="padding: var(--s-6); max-width: 900px;">
  <?= csrf_field() ?>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--s-4);">
    <div class="form-group">
      <label for="prod-name" class="form-label">Product Name</label>
      <input type="text" id="prod-name" name="name" class="form-input" value="<?= ea(old('name', $product['name'] ?? '')) ?>" required>
      <?php if (!empty($errors['name'])): ?>
        <span class="form-error"><?= e($errors['name']) ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="prod-sku" class="form-label">SKU</label>
      <input type="text" id="prod-sku" name="sku" class="form-input" value="<?= ea(old('sku', $product['sku'] ?? '')) ?>" placeholder="e.g. AUD-001">
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--s-4);">
    <div class="form-group">
      <label for="prod-slug" class="form-label">URL Slug</label>
      <input type="text" id="prod-slug" name="slug" class="form-input" value="<?= ea(old('slug', $product['slug'] ?? '')) ?>" placeholder="Leave blank to auto-generate">
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

  <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--s-4);">
    <div class="form-group">
      <label for="prod-price" class="form-label">Price ($ USD)</label>
      <input type="number" id="prod-price" name="price" class="form-input" value="<?= ea(old('price', $priceMajor)) ?>" step="0.01" min="0" required>
      <?php if (!empty($errors['price_cents'])): ?>
        <span class="form-error"><?= e($errors['price_cents']) ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="prod-compare" class="form-label">Compare-At Price ($ USD)</label>
      <input type="number" id="prod-compare" name="compare_at_price" class="form-input" value="<?= ea(old('compare_at_price', $compareMajor)) ?>" step="0.01" min="0" placeholder="Optional">
    </div>

    <div class="form-group">
      <label for="prod-stock" class="form-label">Inventory Stock</label>
      <input type="number" id="prod-stock" name="stock" class="form-input" value="<?= ea(old('stock', (string)($product['stock'] ?? 0))) ?>" min="0" required>
      <?php if (!empty($errors['stock'])): ?>
        <span class="form-error"><?= e($errors['stock']) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="form-group">
    <label for="prod-short" class="form-label">Short Summary (1–2 sentences)</label>
    <input type="text" id="prod-short" name="short_description" class="form-input" value="<?= ea(old('short_description', $product['short_description'] ?? '')) ?>" maxlength="255">
  </div>

  <div class="form-group">
    <label for="prod-desc" class="form-label">Full Description</label>
    <textarea id="prod-desc" name="description" class="form-textarea" rows="4"><?= e(old('description', $product['description'] ?? '')) ?></textarea>
  </div>

  <!-- Image Uploads & Management -->
  <div style="border-top: 1px solid var(--line); padding-top: var(--s-4); margin-top: var(--s-5);">
    <h3 style="font-size: 1.1rem; margin-bottom: var(--s-2);">Product Images</h3>

    <?php if (!empty($currentImages)): ?>
      <div style="display: flex; gap: var(--s-3); flex-wrap: wrap; margin-bottom: var(--s-4);">
        <?php foreach ($currentImages as $img): ?>
          <div style="position: relative; width: 84px; height: 84px; border-radius: var(--r-md); overflow: hidden; border: 1px solid var(--glass-border);">
            <img src="<?= e($img) ?>" alt="" width="84" height="84" style="width: 100%; height: 100%; object-fit: cover;">
            <button
              type="submit"
              name="remove_image"
              value="<?= ea($img) ?>"
              class="btn btn--danger btn--icon"
              style="position: absolute; top: 4px; right: 4px; padding: 4px; border-radius: var(--r-pill);"
              title="Delete this image"
              aria-label="Delete Image"
            >
              <?= icon('close') ?>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (count($currentImages) < 5): ?>
      <div class="form-group">
        <label for="image-uploads" class="form-label">Upload New Photos (Max 5 total, ≤ 2MB each, JPEG/PNG/WebP)</label>
        <input type="file" id="image-uploads" name="image_uploads[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input">
        <div id="image-previews" style="display: flex; gap: var(--s-2); margin-top: var(--s-3);"></div>
      </div>
    <?php else: ?>
      <p style="font-size: 0.85rem; color: var(--text-3);">Maximum of 5 images reached. Remove an existing image to upload more.</p>
    <?php endif; ?>
  </div>

  <!-- Flags -->
  <div style="display: flex; gap: var(--s-5); border-top: 1px solid var(--line); padding-top: var(--s-4); margin-top: var(--s-5);">
    <label style="display: flex; align-items: center; gap: var(--s-2); cursor: pointer;">
      <input type="checkbox" name="is_active" value="1" <?= old('is_active', $product['is_active'] ?? true) ? 'checked' : '' ?>>
      <span style="font-weight: 600;">Active in Catalog</span>
    </label>

    <label style="display: flex; align-items: center; gap: var(--s-2); cursor: pointer;">
      <input type="checkbox" name="is_featured" value="1" <?= old('is_featured', $product['is_featured'] ?? false) ? 'checked' : '' ?>>
      <span style="font-weight: 600;">Featured on Home</span>
    </label>
  </div>

  <div style="margin-top: var(--s-6); display: flex; gap: var(--s-3);">
    <button type="submit" class="btn btn--primary btn--lg">
      <span><?= $isEdit ? 'Update Product' : 'Save & Publish Product' ?></span>
    </button>
    <a href="<?= url('/admin/products') ?>" class="btn btn--ghost btn--lg">Cancel</a>
  </div>
</form>
