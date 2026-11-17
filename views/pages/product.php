<?php
declare(strict_types=1);

$product = $product ?? [];
$related = $related ?? [];

$images = $product['images'] ?? [];
$mainImg = asset('assets/img/placeholder.svg');
if (is_array($images) && count($images) > 0) {
    $mainImg = $images[0];
} elseif (!empty($product['slug'])) {
    $localRel = 'assets/img/products/' . $product['slug'] . '.jpg';
    if (is_file(dirname(__DIR__, 2) . '/public/' . $localRel)) {
        $mainImg = asset($localRel);
        $images = [$mainImg];
    }
}
$priceCents = (int)($product['price_cents'] ?? 0);
$compareCents = !empty($product['compare_at_cents']) ? (int)$product['compare_at_cents'] : null;
$stock = (int)($product['stock'] ?? 0);
$isOutOfStock = ($stock <= 0);
$isLowStock = ($stock > 0 && $stock <= 5);
$category = $product['category'] ?? null;

$discountPercent = 0;
if ($compareCents && $compareCents > $priceCents) {
    $discountPercent = (int)round((($compareCents - $priceCents) / $compareCents) * 100);
}

// JSON-LD structured data
$jsonLd = [
    '@context'    => 'https://schema.org/',
    '@type'       => 'Product',
    'name'        => $product['name'],
    'image'       => is_array($images) && count($images) > 0 ? $images : [$mainImg],
    'description' => $product['short_description'] ?? $product['description'] ?? '',
    'sku'         => $product['sku'] ?? $product['slug'],
    'offers'      => [
        '@type'         => 'Offer',
        'priceCurrency' => settings('currency_code', 'USD'),
        'price'         => number_format($priceCents / 100, 2, '.', ''),
        'availability'  => $isOutOfStock ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
    ],
];
?>
<script type="application/ld+json">
<?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>

<div class="container" style="padding-top: var(--s-6);">
  <!-- Breadcrumbs -->
  <nav class="breadcrumbs" aria-label="Breadcrumb" style="display: flex; gap: var(--s-2); font-size: 0.85rem; color: var(--text-3); margin-bottom: var(--s-5);">
    <a href="<?= url('/') ?>">Home</a>
    <span>/</span>
    <a href="<?= url('/shop') ?>">Shop</a>
    <?php if ($category): ?>
      <span>/</span>
      <a href="<?= url('/category/' . rawurlencode($category['slug'])) ?>"><?= e($category['name']) ?></a>
    <?php endif; ?>
    <span>/</span>
    <span style="color: var(--text-1);"><?= e($product['name']) ?></span>
  </nav>

  <!-- PDP Grid -->
  <div class="pdp-grid">
    <!-- Gallery -->
    <div class="pdp-gallery">
      <div class="pdp-gallery__main glass">
        <img
          id="pdp-main-image"
          src="<?= e($mainImg) ?>"
          alt="<?= ea($product['name']) ?>"
          width="600"
          height="600"
          fetchpriority="high"
        >
      </div>

      <?php if (is_array($images) && count($images) > 1): ?>
        <div class="pdp-gallery__thumbs">
          <?php foreach ($images as $index => $img): ?>
            <button
              type="button"
              class="pdp-gallery__thumb <?= $index === 0 ? 'is-active' : '' ?>"
              data-src="<?= ea($img) ?>"
              aria-label="View Image <?= $index + 1 ?>"
            >
              <img src="<?= e($img) ?>" alt="" width="72" height="72" style="width: 100%; height: 100%; object-fit: cover;">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Product Details & Buy Box (Flush with Gallery, Rounded 2xl Card) -->
    <div class="pdp-details">
      <!-- Category Micro-Badge -->
      <?php if ($category): ?>
        <a href="<?= url('/category/' . rawurlencode($category['slug'])) ?>" class="pdp-category-badge">
          <?= e($category['name']) ?>
        </a>
      <?php endif; ?>

      <!-- Product Title -->
      <h1 class="pdp-title"><?= e($product['name']) ?></h1>

      <!-- Pricing Row -->
      <div class="pdp-price-row">
        <span class="pdp-price"><?= money($priceCents) ?></span>
        <?php if ($compareCents && $compareCents > $priceCents): ?>
          <span class="pdp-compare-price"><?= money($compareCents) ?></span>
          <span class="pdp-discount-badge">-<?= $discountPercent ?>% OFF</span>
        <?php endif; ?>
      </div>

      <!-- Stock Status Pill with Pulsing Indicator Dot -->
      <div>
        <?php if ($isOutOfStock): ?>
          <span class="pdp-stock-pill pdp-stock-pill--out-of-stock">
            <span class="pdp-pulse-solid pdp-pulse-solid--rose"></span>
            <span>Out of Stock</span>
          </span>
        <?php elseif ($isLowStock): ?>
          <span class="pdp-stock-pill pdp-stock-pill--low-stock">
            <span class="pdp-pulse-dot">
              <span class="pdp-pulse-ping pdp-pulse-ping--amber"></span>
              <span class="pdp-pulse-solid pdp-pulse-solid--amber"></span>
            </span>
            <span>Only <?= $stock ?> Left &amp; Ready to Ship</span>
          </span>
        <?php else: ?>
          <span class="pdp-stock-pill pdp-stock-pill--in-stock">
            <span class="pdp-pulse-dot">
              <span class="pdp-pulse-ping pdp-pulse-ping--green"></span>
              <span class="pdp-pulse-solid pdp-pulse-solid--green"></span>
            </span>
            <span>In Stock &amp; Ready to Ship</span>
          </span>
        <?php endif; ?>
      </div>

      <!-- Short Description -->
      <?php if (!empty($product['short_description'])): ?>
        <p class="pdp-short-desc">
          <?= e($product['short_description']) ?>
        </p>
      <?php endif; ?>

      <!-- Action Row Alignment (Quantity Stepper + Add to Cart Button Side-by-Side) -->
      <form action="<?= url('/cart/add') ?>" method="POST" class="js-add-to-cart pdp-buy-form">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= ea($product['id']) ?>">

        <div class="pdp-action-row">
          <!-- Quantity Stepper: joined sleek pill/box control -->
          <div class="pdp-qty-stepper qty-control">
            <button type="button" class="pdp-qty-btn" data-qty-step="-1" aria-label="Decrease quantity" <?= $isOutOfStock ? 'disabled' : '' ?>>
              <?= icon('minus') ?>
            </button>
            <input
              type="number"
              name="quantity"
              value="1"
              min="1"
              max="<?= min(10, max(1, $stock)) ?>"
              aria-label="Quantity"
              class="pdp-qty-input"
              <?= $isOutOfStock ? 'disabled' : '' ?>
            >
            <button type="button" class="pdp-qty-btn" data-qty-step="1" aria-label="Increase quantity" <?= $isOutOfStock ? 'disabled' : '' ?>>
              <?= icon('plus') ?>
            </button>
          </div>

          <!-- Add to Cart CTA Button -->
          <button
            type="submit"
            class="pdp-cta-btn"
            <?= $isOutOfStock ? 'disabled' : '' ?>
          >
            <?= icon('cart') ?>
            <span><?= $isOutOfStock ? 'Sold Out' : 'Add to Shopping Cart' ?></span>
          </button>
        </div>
      </form>

      <!-- Product Details Section with Subtle Divider -->
      <?php if (!empty($product['description'])): ?>
        <div class="pdp-details-section">
          <h3 class="pdp-details-heading">Product Details</h3>
          <p class="pdp-details-body"><?= nl2br(e($product['description'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related Products Section -->
  <?php if (!empty($related)): ?>
    <section class="section" style="margin-top: var(--s-8); margin-bottom: var(--s-8);">
      <div class="section__header">
        <h2 class="section__title">Complementary Pieces</h2>
      </div>
      <div class="product-grid">
        <?php foreach ($related as $product): ?>
          <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
