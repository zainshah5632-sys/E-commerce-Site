// admin.js: Admin interactions
document.addEventListener('DOMContentLoaded', () => {
  // Confirm actions (e.g. Delete, Cancel order)
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Multiple Image Upload Preview
  const imageInput = document.getElementById('image-uploads');
  const previewContainer = document.getElementById('image-previews');
  if (imageInput && previewContainer) {
    imageInput.addEventListener('change', () => {
      previewContainer.innerHTML = '';
      const files = imageInput.files;
      if (files) {
        Array.from(files).forEach(file => {
          if (!file.type.startsWith('image/')) return;
          const reader = new FileReader();
          reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            img.style.border = '1px solid var(--glass-border)';
            previewContainer.appendChild(img);
          };
          reader.readAsDataURL(file);
        });
      }
    });
  }

  // Admin Settings Tab Switcher (Supports Vertical Sidebar Tabs & Legacy Horizontal Tabs)
  const settingsNavItems = document.querySelectorAll('.settings-nav-item, .settings-tab');
  const settingsPanes = document.querySelectorAll('.settings-pane');

  function switchSettingsTab(targetId) {
    if (!targetId || !settingsPanes.length) return;

    // Normalize targetId (strip leading # if provided)
    targetId = targetId.replace(/^#/, '');

    let found = false;
    settingsPanes.forEach(pane => {
      if (pane.getAttribute('data-tab-pane') === targetId) {
        pane.classList.remove('hidden');
        pane.style.display = 'block';
        found = true;
      } else {
        pane.classList.add('hidden');
        pane.style.display = 'none';
      }
    });

    if (found) {
      settingsNavItems.forEach(item => {
        const itemTarget = item.getAttribute('data-tab-target');
        const isActive = itemTarget === targetId;

        // Modern vertical sidebar item
        if (item.classList.contains('settings-nav-item')) {
          item.classList.toggle('settings-nav-item--active', isActive);
          item.setAttribute('aria-selected', isActive ? 'true' : 'false');
          if (isActive && window.innerWidth <= 992) {
            item.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
          }
        }

        // Legacy horizontal tab support
        if (item.classList.contains('settings-tab')) {
          if (isActive) {
            item.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
            item.classList.remove('text-slate-600', 'font-medium');
          } else {
            item.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
            item.classList.add('text-slate-600', 'hover:text-slate-900', 'font-medium');
          }
        }
      });

      // Synchronize hidden active_tab input for persistent server roundtrips
      const activeTabInput = document.getElementById('settings-active-tab');
      if (activeTabInput) {
        activeTabInput.value = targetId;
      }

      // Update URL hash without scroll jump
      if (history.replaceState) {
        history.replaceState(null, '', '#' + targetId);
      }
    }
  }

  if (settingsNavItems.length > 0 && settingsPanes.length > 0) {
    settingsNavItems.forEach(tab => {
      tab.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('data-tab-target');
        switchSettingsTab(target);
      });
    });

    // Check URL hash on initial load (e.g. #shipping or #payments)
    const initialHash = window.location.hash.replace('#', '');
    if (initialHash) {
      switchSettingsTab(initialHash);
    }

    // Listen for browser forward / back button navigation
    window.addEventListener('hashchange', () => {
      const currentHash = window.location.hash.replace('#', '');
      if (currentHash) {
        switchSettingsTab(currentHash);
      }
    });
  }

  // Interactive Live Announcement Preview in Settings
  const announcementInput = document.getElementById('set-announcement');
  const announcementPreviewText = document.getElementById('announcement-preview-text');
  if (announcementInput && announcementPreviewText) {
    announcementInput.addEventListener('input', () => {
      const val = announcementInput.value.trim();
      announcementPreviewText.textContent = val || 'Free delivery across Pakistan on orders over Rs 5,000';
    });
  }

  // Interactive Price & Threshold Live Preview in Settings
  const currencySymbolInput = document.getElementById('set-currency-symbol');
  const currencyCodeInput = document.getElementById('set-currency-code');
  const pricePreviewDemo = document.getElementById('price-preview-demo');
  const thresholdInput = document.getElementById('set-free-threshold');
  const thresholdDemo = document.getElementById('threshold-demo');

  function updateCurrencyPreview() {
    if (pricePreviewDemo && currencySymbolInput && currencyCodeInput) {
      const sym = currencySymbolInput.value.trim() || 'Rs';
      const code = currencyCodeInput.value.trim().toUpperCase() || 'PKR';
      const sep = ['$', '€', '£', '¥'].includes(sym) ? '' : ' ';
      pricePreviewDemo.textContent = `${sym}${sep}2,500.00 ${code}`;
    }
    if (thresholdDemo && thresholdInput && currencySymbolInput) {
      const sym = currencySymbolInput.value.trim() || 'Rs';
      const thresh = parseFloat(thresholdInput.value) || 5000;
      const sep = ['$', '€', '£', '¥'].includes(sym) ? '' : ' ';
      thresholdDemo.textContent = `${sym}${sep}${thresh.toFixed(2)}`;
    }
  }

  if (currencySymbolInput) currencySymbolInput.addEventListener('input', updateCurrencyPreview);
  if (currencyCodeInput) currencyCodeInput.addEventListener('input', updateCurrencyPreview);
  if (thresholdInput) thresholdInput.addEventListener('input', updateCurrencyPreview);

  // Copy to Clipboard Helpers
  document.querySelectorAll('[data-copy-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-copy-target');
      const targetEl = document.getElementById(targetId);
      if (targetEl) {
        const textToCopy = targetEl.value || targetEl.textContent || '';
        navigator.clipboard.writeText(textToCopy).then(() => {
          const originalHtml = btn.innerHTML;
          btn.innerHTML = '<span>✓ Copied!</span>';
          btn.style.color = '#10b981';
          btn.style.borderColor = '#10b981';
          setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.style.color = '';
            btn.style.borderColor = '';
          }, 2000);
        }).catch(err => {
          console.error('Copy failed:', err);
        });
      }
    });
  });

  // Dynamic discount calculation and preset buttons in product form
  const priceInput = document.getElementById('prod-price');
  const compareInput = document.getElementById('prod-compare');
  const discountBadge = document.getElementById('discount-calc-badge');

  function updateDiscountDisplay() {
    if (!priceInput || !compareInput || !discountBadge) return;
    const price = parseFloat(priceInput.value) || 0;
    const compare = parseFloat(compareInput.value) || 0;

    if (compare > price && price > 0) {
      const diff = compare - price;
      const pct = Math.round((diff / compare) * 100);
      discountBadge.textContent = 'Discount: ' + pct + '% OFF (Save $' + diff.toFixed(2) + ')';
      discountBadge.style.display = 'inline-flex';
    } else {
      discountBadge.style.display = 'none';
    }
  }

  if (priceInput && compareInput) {
    priceInput.addEventListener('input', updateDiscountDisplay);
    compareInput.addEventListener('input', updateDiscountDisplay);
    updateDiscountDisplay();
  }

  document.querySelectorAll('[data-action="apply-discount"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const pct = parseInt(btn.getAttribute('data-pct'), 10);
      let compare = parseFloat(compareInput.value);
      if (!compare || compare <= 0) {
        compare = parseFloat(priceInput.value) || 100;
        compareInput.value = compare.toFixed(2);
      }
      const discounted = compare * (1 - (pct / 100));
      priceInput.value = discounted.toFixed(2);
      updateDiscountDisplay();
    });
  });

  // =====================================================================
  // Categories & Navigation Page: Slide-over Drawer & Live Filters
  // =====================================================================
  const categoryDrawer = document.getElementById('category-drawer');
  const drawerBackdrop = document.getElementById('category-drawer-backdrop');
  const drawerCloseBtn = document.getElementById('category-drawer-close');
  const drawerCancelBtn = document.getElementById('category-drawer-cancel');
  const drawerForm = document.getElementById('category-drawer-form');
  const drawerTitle = document.getElementById('category-drawer-title');
  const drawerDesc = document.getElementById('category-drawer-desc');
  const drawerSubmitLabel = document.getElementById('category-drawer-submit-label');
  const inputCatId = document.getElementById('category-id');
  const inputCatName = document.getElementById('cat-name');
  const inputCatSlug = document.getElementById('cat-slug');
  const inputCatDesc = document.getElementById('cat-desc');
  const inputCatSort = document.getElementById('cat-sort');
  const inputCatActive = document.getElementById('cat-active');
  const btnSlugRegenerate = document.getElementById('cat-slug-regenerate');

  let isManualSlug = false;
  let currentMode = 'create'; // 'create' or 'edit'

  function slugify(text) {
    return text
      .toString()
      .toLowerCase()
      .trim()
      .replace(/\s+/g, '-')           // Replace spaces with -
      .replace(/[^\w\-]+/g, '')       // Remove non-word chars
      .replace(/\-\-+/g, '-')         // Replace multiple - with single -
      .replace(/^-+/, '')             // Trim - from start
      .replace(/-+$/, '');            // Trim - from end
  }

  function openCategoryDrawer(mode = 'create', data = {}) {
    if (!categoryDrawer || !drawerBackdrop) return;
    currentMode = mode;

    if (mode === 'create') {
      if (drawerForm) drawerForm.action = '/admin/categories';
      if (drawerTitle) drawerTitle.textContent = 'Add Category';
      if (drawerDesc) drawerDesc.textContent = 'Define a new product grouping and storefront navigation placement.';
      if (drawerSubmitLabel) drawerSubmitLabel.textContent = 'Save Category';
      if (inputCatId) inputCatId.value = '';
      if (inputCatName) inputCatName.value = '';
      if (inputCatSlug) inputCatSlug.value = '';
      if (inputCatDesc) inputCatDesc.value = '';
      if (inputCatSort) inputCatSort.value = '0';
      if (inputCatActive) inputCatActive.checked = true;
      isManualSlug = false;
    } else {
      const id = data.id || '';
      if (drawerForm) drawerForm.action = '/admin/categories/' + encodeURIComponent(id);
      if (drawerTitle) drawerTitle.textContent = 'Edit Category: ' + (data.name || '');
      if (drawerDesc) drawerDesc.textContent = 'Update taxonomy identity, URL slug, and visibility settings.';
      if (drawerSubmitLabel) drawerSubmitLabel.textContent = 'Save Changes';
      if (inputCatId) inputCatId.value = id;
      if (inputCatName) inputCatName.value = data.name || '';
      if (inputCatSlug) inputCatSlug.value = data.slug || '';
      if (inputCatDesc) inputCatDesc.value = data.desc || '';
      if (inputCatSort) inputCatSort.value = data.sort !== undefined ? data.sort : '0';
      if (inputCatActive) inputCatActive.checked = data.status === 'active';
      isManualSlug = true;
    }

    categoryDrawer.classList.add('category-drawer--open');
    drawerBackdrop.classList.add('category-drawer-backdrop--open');
    document.body.style.overflow = 'hidden';

    // Focus first input
    setTimeout(() => {
      if (inputCatName) inputCatName.focus();
    }, 100);
  }

  function closeCategoryDrawer() {
    if (!categoryDrawer || !drawerBackdrop) return;
    categoryDrawer.classList.remove('category-drawer--open');
    drawerBackdrop.classList.remove('category-drawer-backdrop--open');
    document.body.style.overflow = '';
  }

  // Open on Add button
  document.querySelectorAll('[data-action="add-category"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openCategoryDrawer('create');
    });
  });

  // Open on Edit button (event delegation on table)
  const categoryTable = document.getElementById('category-table');
  if (categoryTable) {
    categoryTable.addEventListener('click', (e) => {
      const editBtn = e.target.closest('[data-action="edit-category"]');
      if (editBtn) {
        e.preventDefault();
        const row = editBtn.closest('[data-category-row]');
        if (row) {
          const data = {
            id: row.getAttribute('data-id'),
            name: row.getAttribute('data-name'),
            slug: row.getAttribute('data-slug'),
            desc: row.getAttribute('data-desc'),
            sort: row.getAttribute('data-sort'),
            status: row.getAttribute('data-status')
          };
          openCategoryDrawer('edit', data);
        }
      }
    });
  }

  // Close triggers
  if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeCategoryDrawer);
  if (drawerCancelBtn) drawerCancelBtn.addEventListener('click', closeCategoryDrawer);
  if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeCategoryDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && categoryDrawer && categoryDrawer.classList.contains('category-drawer--open')) {
      closeCategoryDrawer();
    }
  });

  // Auto slug generation
  if (inputCatName && inputCatSlug) {
    inputCatName.addEventListener('input', () => {
      if (currentMode === 'create' && !isManualSlug) {
        inputCatSlug.value = slugify(inputCatName.value);
      }
    });

    inputCatSlug.addEventListener('input', () => {
      isManualSlug = true;
    });
  }

  if (btnSlugRegenerate && inputCatName && inputCatSlug) {
    btnSlugRegenerate.addEventListener('click', (e) => {
      e.preventDefault();
      inputCatSlug.value = slugify(inputCatName.value);
      isManualSlug = false;
    });
  }

  // Sort stepper buttons
  const sortIncBtn = document.getElementById('cat-sort-inc');
  const sortDecBtn = document.getElementById('cat-sort-dec');
  if (sortIncBtn && inputCatSort) {
    sortIncBtn.addEventListener('click', () => {
      const val = parseInt(inputCatSort.value, 10) || 0;
      inputCatSort.value = val + 1;
    });
  }
  if (sortDecBtn && inputCatSort) {
    sortDecBtn.addEventListener('click', () => {
      const val = parseInt(inputCatSort.value, 10) || 0;
      inputCatSort.value = Math.max(0, val - 1);
    });
  }

  // Live Table Search & Status Filtering
  const searchInput = document.getElementById('category-search');
  const searchClearBtn = document.getElementById('category-search-clear');
  const statusFilter = document.getElementById('category-status-filter');
  const visibleCountEl = document.getElementById('category-visible-count');
  const emptyFilterState = document.getElementById('category-empty-filter');
  const resetFiltersBtn = document.getElementById('category-reset-filters');
  const categoryRows = document.querySelectorAll('[data-category-row]');

  function filterCategories() {
    if (!categoryRows.length) return;
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const status = statusFilter ? statusFilter.value : 'all';

    if (searchClearBtn) {
      searchClearBtn.style.display = query ? 'flex' : 'none';
    }

    let visibleCount = 0;

    categoryRows.forEach(row => {
      const name = (row.getAttribute('data-name') || '').toLowerCase();
      const slug = (row.getAttribute('data-slug') || '').toLowerCase();
      const desc = (row.getAttribute('data-desc') || '').toLowerCase();
      const rowStatus = row.getAttribute('data-status') || 'active';

      const matchesQuery = !query || name.includes(query) || slug.includes(query) || desc.includes(query);
      const matchesStatus = status === 'all' || rowStatus === status;

      if (matchesQuery && matchesStatus) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    if (visibleCountEl) {
      visibleCountEl.textContent = visibleCount;
    }

    if (emptyFilterState) {
      emptyFilterState.style.display = visibleCount === 0 ? '' : 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterCategories);
  }

  if (searchClearBtn) {
    searchClearBtn.addEventListener('click', () => {
      if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        filterCategories();
      }
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', filterCategories);
  }

  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', () => {
      if (searchInput) searchInput.value = '';
      if (statusFilter) statusFilter.value = 'all';
      filterCategories();
    });
  }

  // Dashboard Date Filter Quick Toggle
  const datePickerBtn = document.getElementById('dashboard-date-picker');
  if (datePickerBtn) {
    const ranges = ['Last 30 Days', 'Last 7 Days', 'This Month', 'This Quarter', 'All Time'];
    let currentIdx = 0;
    datePickerBtn.addEventListener('click', () => {
      currentIdx = (currentIdx + 1) % ranges.length;
      const label = datePickerBtn.querySelector('.dashboard-date-filter__label');
      if (label) {
        label.textContent = ranges[currentIdx];
      }
    });
  }

  // Product Title to Slug Auto-generator
  const prodNameInput = document.getElementById('prod-name');
  const prodSlugInput = document.getElementById('prod-slug');
  if (prodNameInput && prodSlugInput) {
    let slugManual = Boolean(prodSlugInput.value.trim());
    prodSlugInput.addEventListener('input', () => {
      slugManual = Boolean(prodSlugInput.value.trim());
    });
    prodNameInput.addEventListener('input', () => {
      if (!slugManual) {
        prodSlugInput.value = prodNameInput.value
          .toLowerCase()
          .trim()
          .replace(/[^\w\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
    });
  }
});

