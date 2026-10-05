// cart.js: Progressive enhancement for shopping cart
document.addEventListener('DOMContentLoaded', () => {
  const getCsrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  };

  const updateCartUI = (data) => {
    // Update badge counters
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(badge => {
      badge.textContent = data.count;
      badge.style.display = data.count > 0 ? 'flex' : 'none';
    });

    // Update mini-cart container
    const miniCartContainer = document.getElementById('mini-cart-container');
    if (miniCartContainer && data.html) {
      miniCartContainer.innerHTML = data.html;
    }
  };

  // Intercept Add to Cart forms
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.js-add-to-cart');
    if (!form) return;

    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;

    const formData = new FormData(form);

    try {
      const res = await fetch('/cart/add', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-Token': getCsrf(),
        },
        body: formData,
      });

      const data = await res.json();
      if (data.ok) {
        updateCartUI(data);
        if (window.showToast) window.showToast(data.message, 'success');

        // Open cart drawer
        const openBtn = document.querySelector('[data-action="open-cart"]');
        if (openBtn) openBtn.click();
      } else {
        if (window.showToast) window.showToast(data.message || 'Could not add item', 'error');
      }
    } catch (err) {
      form.submit(); // Fallback to normal POST
    } finally {
      if (btn) btn.disabled = false;
    }
  });

  // Delegate updates and removals in mini-cart and cart page
  document.addEventListener('click', async (e) => {
    const removeBtn = e.target.closest('.js-cart-remove');
    if (!removeBtn) return;

    e.preventDefault();
    const pid = removeBtn.getAttribute('data-product-id');
    if (!pid) return;

    const formData = new FormData();
    formData.append('product_id', pid);

    try {
      const res = await fetch('/cart/remove', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-Token': getCsrf(),
        },
        body: formData,
      });

      const data = await res.json();
      if (data.ok) {
        updateCartUI(data);
        // If on /cart page, reload to sync table
        if (window.location.pathname === '/cart') {
          window.location.reload();
        }
      }
    } catch (err) {
      window.location.reload();
    }
  });

  // Quantity input change
  document.addEventListener('change', async (e) => {
    const qtyInput = e.target.closest('.js-cart-qty');
    if (!qtyInput) return;

    const pid = qtyInput.getAttribute('data-product-id');
    const qty = parseInt(qtyInput.value, 10);
    if (!pid) return;

    const formData = new FormData();
    formData.append('product_id', pid);
    formData.append('quantity', qty);

    try {
      const res = await fetch('/cart/update', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-Token': getCsrf(),
        },
        body: formData,
      });

      const data = await res.json();
      if (data.ok) {
        updateCartUI(data);
        if (window.location.pathname === '/cart') {
          window.location.reload();
        }
      }
    } catch (err) {
      window.location.reload();
    }
  });
});
