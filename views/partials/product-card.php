<?php
declare(strict_types=1);

$images = $product['images'] ?? [];
$imgUrl = asset('assets/img/placeholder.svg');
if (is_array($images) && count($images) > 0) {
    $imgUrl = $images[0];
} elseif (!empty($product['slug'])) {
    $localRel = 'assets/img/products/' . $product['slug'] . '.jpg';
    if (is_file(dirname(__DIR__, 2) . '/public/' . $localRel)) {
        $imgUrl = asset($localRel);
    }
}
$priceCents = (int)($product['price_cents'] ?? 0);
$compareCents = !empty($product['compare_at_cents']) ? (int)$product['compare_at_cents'] : null;
$stock = (int)($product['stock'] ?? 0);
$isOutOfStock = ($stock <= 0);
$isLowStock = ($stock > 0 && $stock <= 5);
$categoryName = $product['category']['name'] ?? 'Hardware';

$discountPercent = 0;
if ($compareCents && $compareCents > $priceCents) {
    $discountPercent = (int)round((($compareCents - $priceCents) / $compareCents) * 100);
}
?>
<article class="product-card group" data-product-id="<?= ea($product['id']) ?>">
  <!-- Square Image Box with Zoom-on-Hover -->
  <div class="product-card__image-box">
    <!-- Tag Badge: Top-Left Rounded Pill -->
    <?php if ($isOutOfStock): ?>
      <span class="product-card__pill-badge product-card__pill-badge--danger">Sold Out</span>
    <?php elseif ($discountPercent > 0): ?>
      <span class="product-card__pill-badge product-card__pill-badge--brand">-<?= $discountPercent ?>%</span>
    <?php elseif ($isLowStock): ?>
      <span class="product-card__pill-badge product-card__pill-badge--warning">Only <?= $stock ?> Left</span>
    <?php endif; ?>

    <a href="<?= url('/product/' . rawurlencode($product['slug'])) ?>" aria-label="<?= ea($product['name']) ?>" class="product-card__img-link">
      <img src="<?= e($imgUrl) ?>" alt="<?= ea($product['name']) ?>" width="400" height="400" loading="lazy" class="product-card__img">
    </a>
  </div>

  <!-- Content & Info Hierarchy -->
  <div class="product-card__body">
    <!-- Category Eyebrow -->
    <div class="product-card__eyebrow"><?= e($categoryName) ?></div>

    <!-- Product Title -->
    <h3 class="product-card__heading">
      <a href="<?= url('/product/' . rawurlencode($product['slug'])) ?>">
        <?= e($product['name']) ?>
      </a>
    </h3>

    <!-- Short Description -->
    <?php if (!empty($product['short_description'])): ?>
      <p class="product-card__snippet">
        <?= e($product['short_description']) ?>
      </p>
    <?php endif; ?>

    <!-- Bottom Action Row: Price & Quick Add to Cart Button -->
    <div class="product-card__footer">
      <div class="product-card__price-wrap">
        <span class="product-card__current-price"><?= money($priceCents) ?></span>
        <?php if ($compareCents && $compareCents > $priceCents): ?>
          <span class="product-card__strikethrough-price"><?= money($compareCents) ?></span>
        <?php endif; ?>
      </div>

      <form action="<?= url('/cart/add') ?>" method="POST" class="js-add-to-cart">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= ea($product['id']) ?>">
        <input type="hidden" name="quantity" value="1">
        <button
          type="submit"
          class="product-card__cart-btn"
          <?= $isOutOfStock ? 'disabled' : '' ?>
          title="<?= $isOutOfStock ? 'Sold Out' : 'Add to Cart' ?>"
        >
          <?= icon('cart') ?>
          <span><?= $isOutOfStock ? 'Sold Out' : 'Add to Cart' ?></span>
        </button>
      </form>
    </div>
  </div>
</article>
