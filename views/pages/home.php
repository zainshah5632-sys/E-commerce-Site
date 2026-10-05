<?php
declare(strict_types=1);

$categories = $categories ?? [];
$featured = $featured ?? [];
$newArrivals = $newArrivals ?? [];
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container hero__content">
    <span class="hero__badge">
      <span class="hero__badge-dot"></span>
      <span>New Collection 2026</span>
    </span>
    <h1 class="hero__title">
      Crafted with <span class="hero__gradient-text">Translucent Precision</span> &amp; Acoustic Purity
    </h1>
    <p class="hero__desc">
      Architectural audio monitors, ergonomic acrylic risers, and biometric timepieces designed for modern creative spaces.
    </p>
    <div class="hero__cta-group">
      <a href="<?= url('/shop') ?>" class="hero__cta-primary btn btn--primary btn--lg">
        <span>Explore Collection</span>
        <?= icon('arrow-right') ?>
      </a>
      <a href="<?= url('/category/audio') ?>" class="hero__cta-secondary btn btn--ghost btn--lg">
        <span>Studio Audio</span>
      </a>
    </div>
  </div>
</section>

<!-- Trust / Value Propositions Bar -->
<section class="trust-bar-section">
  <div class="container max-w-6xl mx-auto">
    <div class="trust-bar bg-white/70 backdrop-blur-md rounded-2xl border border-slate-200/80 p-6 shadow-sm my-12">
      <div class="trust-grid grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Feature 1: Complimentary Shipping -->
        <div class="trust-item flex items-center gap-4">
          <div class="trust-item__badge w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-lg flex-shrink-0">
            <?= icon('truck', 22) ?>
          </div>
          <div>
            <div class="trust-item__title text-sm sm:text-base font-bold text-slate-900">Complimentary Shipping</div>
            <div class="trust-item__desc text-xs text-slate-500 mt-0.5 leading-snug">Free on all orders over $75</div>
          </div>
        </div>

        <!-- Feature 2: 30-Day Studio Trial -->
        <div class="trust-item flex items-center gap-4">
          <div class="trust-item__badge w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-lg flex-shrink-0">
            <?= icon('shield', 22) ?>
          </div>
          <div>
            <div class="trust-item__title text-sm sm:text-base font-bold text-slate-900">30-Day Studio Trial</div>
            <div class="trust-item__desc text-xs text-slate-500 mt-0.5 leading-snug">Hassle-free return policy</div>
          </div>
        </div>

        <!-- Feature 3: Precision Guaranteed -->
        <div class="trust-item flex items-center gap-4">
          <div class="trust-item__badge w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-lg flex-shrink-0">
            <?= icon('check', 22) ?>
          </div>
          <div>
            <div class="trust-item__title text-sm sm:text-base font-bold text-slate-900">Precision Guaranteed</div>
            <div class="trust-item__desc text-xs text-slate-500 mt-0.5 leading-snug">2-year manufacturer warranty</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Category Tiles / Curated Disciplines -->
<?php if (!empty($categories)): ?>
  <section class="disciplines-section mt-16 mb-8">
    <div class="container max-w-6xl mx-auto">
      <div class="disciplines-header mb-8">
        <span class="disciplines-badge text-[11px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full inline-block mb-3">
          Browse by Focus
        </span>
        <h2 class="disciplines-title text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
          Curated Disciplines
        </h2>
      </div>

      <div class="category-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($categories as $cat): ?>
          <a href="<?= url('/category/' . rawurlencode($cat['slug'])) ?>" class="category-card bg-white/80 backdrop-blur-sm rounded-2xl border border-slate-200/80 p-6 shadow-sm hover:shadow-md hover:border-indigo-200 hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between group">
            <div>
              <h3 class="category-card__name text-lg font-bold text-slate-900 mb-2"><?= e($cat['name']) ?></h3>
              <p class="category-card__desc text-xs sm:text-sm text-slate-600 leading-relaxed flex-1 mb-4"><?= e($cat['description'] ?? '') ?></p>
            </div>
            <div class="category-card__link inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 group-hover:text-indigo-700 transition">
              <span>Explore</span>
              <?= icon('arrow-right', 14) ?>
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
