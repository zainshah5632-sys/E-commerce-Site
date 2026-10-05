<?php
declare(strict_types=1);

$categories = $categories ?? [];
$featured = $featured ?? [];
$newArrivals = $newArrivals ?? [];
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container hero__content">
    <span class="badge badge--brand">New Collection 2026</span>
    <h1 class="hero__title">
      Crafted with <span class="hero__gradient-text">Translucent Precision</span> &amp; Acoustic Purity
    </h1>
    <p class="hero__desc">
      Architectural audio monitors, ergonomic acrylic risers, and biometric timepieces designed for modern creative spaces.
    </p>
    <div class="hero__cta-group">
      <a href="<?= url('/shop') ?>" class="btn btn--primary btn--lg">
        <span>Explore Collection</span>
        <?= icon('arrow-right') ?>
      </a>
      <a href="<?= url('/category/audio') ?>" class="btn btn--ghost btn--lg">
        <span>Studio Audio</span>
      </a>
    </div>
  </div>
</section>

<!-- Value Propositions -->
<section style="padding-block: var(--s-4);">
  <div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--s-4);">
      <div class="glass" style="padding: var(--s-4); display: flex; align-items: center; gap: var(--s-3);">
        <div style="color: var(--brand-500);"><?= icon('truck') ?></div>
        <div>
          <div style="font-weight: 700; font-size: 0.95rem;">Complimentary Shipping</div>
          <div style="font-size: 0.82rem; color: var(--text-3);">Free on all orders over $75</div>
        </div>
      </div>

      <div class="glass" style="padding: var(--s-4); display: flex; align-items: center; gap: var(--s-3);">
        <div style="color: var(--brand-500);"><?= icon('shield') ?></div>
        <div>
          <div style="font-weight: 700; font-size: 0.95rem;">30-Day Studio Trial</div>
          <div style="font-size: 0.82rem; color: var(--text-3);">Hassle-free return policy</div>
        </div>
      </div>

      <div class="glass" style="padding: var(--s-4); display: flex; align-items: center; gap: var(--s-3);">
        <div style="color: var(--brand-500);"><?= icon('check') ?></div>
        <div>
          <div style="font-weight: 700; font-size: 0.95rem;">Precision Guaranteed</div>
          <div style="font-size: 0.82rem; color: var(--text-3);">2-year manufacturer warranty</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Category Tiles -->
<?php if (!empty($categories)): ?>
  <section class="section">
    <div class="container">
      <div class="section__header">
        <div>
          <span class="badge badge--neutral">Browse by Focus</span>
          <h2 class="section__title" style="margin-top: var(--s-2);">Curated Disciplines</h2>
        </div>
      </div>

      <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
          <a href="<?= url('/category/' . rawurlencode($cat['slug'])) ?>" class="category-card glass">
            <div>
              <div class="category-card__name"><?= e($cat['name']) ?></div>
              <div class="category-card__desc"><?= e($cat['description'] ?? '') ?></div>
            </div>
            <div style="display: flex; align-items: center; gap: var(--s-1); color: var(--brand-500); font-weight: 600; font-size: 0.88rem; margin-top: var(--s-4);">
              <span>Explore</span>
              <?= icon('arrow-right') ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- Featured Products -->
<?php if (!empty($featured)): ?>
  <section class="section">
    <div class="container">
      <div class="section__header">
        <div>
          <span class="badge badge--brand">Spotlight</span>
          <h2 class="section__title" style="margin-top: var(--s-2);">Featured Hardware</h2>
        </div>
        <a href="<?= url('/shop') ?>" class="btn btn--ghost btn--sm">
          <span>View All</span>
          <?= icon('arrow-right') ?>
        </a>
      </div>

      <div class="product-grid">
        <?php foreach ($featured as $product): ?>
          <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- New Arrivals -->
<?php if (!empty($newArrivals)): ?>
  <section class="section" style="padding-bottom: var(--s-9);">
    <div class="container">
      <div class="section__header">
        <div>
          <span class="badge badge--neutral">Fresh Releases</span>
          <h2 class="section__title" style="margin-top: var(--s-2);">New Arrivals</h2>
        </div>
      </div>

      <div class="product-grid">
        <?php foreach ($newArrivals as $product): ?>
          <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
