<?php
declare(strict_types=1);

$categories = $categories ?? [];
$errors = $errors ?? [];
$activeCount = count(array_filter($categories, fn($c) => !empty($c['is_active'])));
?>

<!-- Header -->
<div class="admin-header flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
  <div>
    <div class="text-xs font-medium text-slate-500 tracking-wide mb-1 flex items-center gap-1.5">
      <span>Admin</span>
      <span class="text-slate-400">/</span>
      <span class="text-indigo-600 font-semibold">Categories</span>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Categories &amp; Navigation</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Organize product groupings, disciplines, and navigation taxonomy.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
      <?= $activeCount ?> / <?= count($categories) ?> Active in Menu
    </span>
  </div>
</div>

<!-- Master-Detail 2-Column Layout (1/3 Form on Left, 2/3 List on Right) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
  
  <!-- Left Column: Add Category Form (1/3 width, sticky) -->
  <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm sticky top-6">
    <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
      <div>
        <h2 class="text-base font-bold text-slate-900">Add / Edit Category</h2>
        <p class="text-xs text-slate-500 mt-0.5">Create a new customer discipline.</p>
      </div>
      <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 text-xs">
        <?= icon('plus', 16) ?>
      </span>
    </div>

    <form action="<?= url('/admin/categories') ?>" method="POST" class="space-y-4" id="category-form">
      <?= csrf_field() ?>

      <!-- Category Name -->
      <div>
        <label for="cat-name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Category Name
        </label>
        <input
          type="text"
          id="cat-name"
          name="name"
          class="w-full h-10 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
          required
          placeholder="e.g. Studio Monitors"
        >
        <p class="text-[11px] text-slate-400 mt-1">Visible name displayed across storefront menus.</p>
        <?php if (!empty($errors['name'])): ?>
          <span class="text-xs text-rose-600 mt-1 block"><?= e($errors['name']) ?></span>
        <?php endif; ?>
      </div>

      <!-- URL Slug -->
      <div>
        <label for="cat-slug" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          URL Slug
        </label>
        <input
          type="text"
          id="cat-slug"
          name="slug"
          class="w-full h-10 bg-white border border-slate-300 rounded-xl px-3.5 text-sm font-mono text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
          placeholder="e.g. studio-monitors"
        >
        <p class="text-[11px] text-slate-400 mt-1">Unique URL path: <code>/category/<b>slug</b></code> (lowercase, hyphens only).</p>
        <?php if (!empty($errors['slug'])): ?>
          <span class="text-xs text-rose-600 mt-1 block"><?= e($errors['slug']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <div>
        <label for="cat-desc" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Description
        </label>
        <textarea
          id="cat-desc"
          name="description"
          rows="3"
          class="w-full bg-white border border-slate-300 rounded-xl p-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
          placeholder="Brief summary displayed on category banners and SEO cards..."
        ></textarea>
      </div>

      <!-- Sort Order -->
      <div>
        <label for="cat-sort" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
          Sort Priority
        </label>
        <input
          type="number"
          id="cat-sort"
          name="sort_order"
          value="0"
          min="0"
          class="w-full h-10 bg-white border border-slate-300 rounded-xl px-3.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition shadow-sm"
        >
        <p class="text-[11px] text-slate-400 mt-1">Lower integers display first in navigation.</p>
      </div>

      <!-- Active in Navigation -->
      <div class="pt-2">
        <label class="flex items-center gap-3 cursor-pointer select-none">
          <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
          <span class="text-sm font-semibold text-slate-700">Display in Storefront Navigation</span>
        </label>
      </div>

      <!-- Action Button -->
      <div class="pt-2">
        <button
          type="submit"
          class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition shadow-md shadow-indigo-600/20 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer"
        >
          <?= icon('plus', 14) ?>
          <span>Save Category</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Right Column: Active Categories List (2/3 width) -->
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">Active Categories Table</h2>
        <p class="text-xs text-slate-500 mt-0.5">Manage existing classifications and hierarchy.</p>
      </div>
      <span class="text-xs text-slate-400 font-mono"><?= count($categories) ?> total records</span>
    </div>

    <?php if (empty($categories)): ?>
      <div class="p-12 text-center text-slate-500 text-sm">
        No store categories found. Create one using the form on the left.
      </div>
    <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($categories as $cat): ?>
          <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/70 transition group">
            
            <!-- Left: Drag indicator, Title, Description, and Badges -->
            <div class="flex items-start gap-3.5 min-w-0">
              <span class="text-slate-300 group-hover:text-slate-500 transition cursor-grab text-lg select-none pt-0.5" title="Reorder">
                ⋮⋮
              </span>
              <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                  <h3 class="text-sm font-semibold text-slate-900">
                    <?= e($cat['name']) ?>
                  </h3>
                  <code class="text-[11px] font-mono bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                    /<?= e($cat['slug']) ?>
                  </code>
                  <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= !empty($cat['is_active']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-slate-100 text-slate-500' ?>">
                    <?= !empty($cat['is_active']) ? 'Active' : 'Hidden' ?>
                  </span>
                  <span class="text-[11px] text-slate-400 font-mono">
                    Order: <?= (int)$cat['sort_order'] ?>
                  </span>
                </div>
                <?php if (!empty($cat['description'])): ?>
                  <p class="text-xs text-slate-500 mt-1 max-w-xl leading-relaxed">
                    <?= e($cat['description']) ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-center">
              <a
                href="<?= url('/category/' . rawurlencode($cat['slug'])) ?>"
                target="_blank"
                class="p-2 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition"
                title="View on Storefront"
              >
                <?= icon('eye', 15) ?>
              </a>

              <!-- Delete Form -->
              <form action="<?= url('/admin/categories/' . ea($cat['id']) . '/delete') ?>" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button
                  type="submit"
                  class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
                  data-confirm="Are you sure you want to delete category '<?= ea($cat['name']) ?>'? Any associated products will remain in the catalog as unassigned."
                  title="Delete Category"
                >
                  <?= icon('trash', 15) ?>
                </button>
              </form>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>
