<?php
declare(strict_types=1);

$images = $product['images'] ?? [];
$imgUrl = (is_array($images) && count($images) > 0) ? $images[0] : asset('assets/img/placeholder.svg');
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
<article class="product-card glass" data-product-id="<?= ea($product['id']) ?>">
  <div class="product-card__image-wrap">
    <?php if ($isOutOfStock): ?>
      <span class="badge badge--danger product-card__badge">Out of Stock</span>
    <?php elseif ($discountPercent > 0): ?>
      <span class="badge badge--brand product-card__badge">-<?= $discountPercent ?>%</span>
    <?php elseif ($isLowStock): ?>
      <span class="badge badge--warning product-card__badge">Only <?= $stock ?> left</span>
    <?php endif; ?>

    <a href="<?= url('/product/' . rawurlencode($product['slug'])) ?>" aria-label="<?= ea($product['name']) ?>" style="display:block; width:100%; height:100%;">
      <img src="<?= e($imgUrl) ?>" alt="<?= ea($product['name']) ?>" width="300" height="300" loading="lazy" class="product-card__image">
    </a>
  </div>

  <div class="product-card__content">
    <div class="product-card__category"><?= e($categoryName) ?></div>
    <h3 class="product-card__title">
      <a href="<?= url('/product/' . rawurlencode($product['slug'])) ?>"><?= e($product['name']) ?></a>
    </h3>

    <div class="product-card__price-row">
      <div class="product-card__price"><?= money($priceCents) ?></div>
      <?php if ($compareCents && $compareCents > $priceCents): ?>
        <div class="product-card__compare"><?= money($compareCents) ?></div>
      <?php endif; ?>
    </div>

    <!-- Quick Add to Cart Form -->
    <form action="<?= url('/cart/add') ?>" method="POST" class="js-add-to-cart" style="margin-top: var(--s-3);">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= ea($product['id']) ?>">
      <input type="hidden" name="quantity" value="1">
      <button type="submit" class="btn btn--ghost btn--block btn--sm" <?= $isOutOfStock ? 'disabled' : '' ?>>
        <?= icon('cart') ?>
        <span><?= $isOutOfStock ? 'Sold Out' : 'Add to Cart' ?></span>
      </button>
    </form>
  </div>
</article>
