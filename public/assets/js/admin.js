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

  // Admin Settings Tab Switcher
  const settingsTabs = document.querySelectorAll('.settings-tab');
  const settingsPanes = document.querySelectorAll('.settings-pane');
  if (settingsTabs.length > 0 && settingsPanes.length > 0) {
    settingsTabs.forEach(tab => {
      tab.addEventListener('click', function () {
        const target = this.getAttribute('data-tab-target');

        settingsTabs.forEach(t => {
          t.classList.remove('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
          t.classList.add('text-slate-600', 'hover:text-slate-900', 'font-medium');
        });

        this.classList.add('bg-white', 'text-slate-900', 'font-semibold', 'shadow-sm');
        this.classList.remove('text-slate-600', 'font-medium');

        settingsPanes.forEach(pane => {
          if (pane.getAttribute('data-tab-pane') === target) {
            pane.classList.remove('hidden');
            pane.style.display = 'block';
          } else {
            pane.classList.add('hidden');
            pane.style.display = 'none';
          }
        });
      });
    });
  }

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
});

