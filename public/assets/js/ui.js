// ui.js: UI interactions and event delegation
document.addEventListener('DOMContentLoaded', () => {
  // Theme Toggle
  const themeToggle = document.querySelector('[data-action="toggle-theme"]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try {
        localStorage.setItem('theme', next);
      } catch (e) {}
    });
  }

  // Cart Drawer
  const drawer = document.getElementById('cart-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  const openButtons = document.querySelectorAll('[data-action="open-cart"]');
  const closeButtons = document.querySelectorAll('[data-action="close-cart"]');

  function openDrawer() {
    if (drawer && backdrop) {
      drawer.classList.add('is-open');
      backdrop.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      const firstFocusable = drawer.querySelector('button, [href], input');
      if (firstFocusable) firstFocusable.focus();
    }
  }

  function closeDrawer() {
    if (drawer && backdrop) {
      drawer.classList.remove('is-open');
      backdrop.classList.remove('is-open');
      document.body.style.overflow = '';
    }
  }

  openButtons.forEach(btn => btn.addEventListener('click', (e) => {
    e.preventDefault();
    openDrawer();
  }));

  closeButtons.forEach(btn => btn.addEventListener('click', (e) => {
    e.preventDefault();
    closeDrawer();
  }));

  if (backdrop) {
    backdrop.addEventListener('click', closeDrawer);
  }

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });

  // PDP Gallery Thumbnail Switcher
  const mainImage = document.getElementById('pdp-main-image');
  const thumbs = document.querySelectorAll('.pdp-gallery__thumb');
  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      const targetSrc = thumb.getAttribute('data-src');
      if (mainImage && targetSrc) {
        mainImage.src = targetSrc;
        thumbs.forEach(t => t.classList.remove('is-active'));
        thumb.classList.add('is-active');
      }
    });
  });

  // Quantity Stepper
  document.addEventListener('click', (e) => {
    const stepper = e.target.closest('[data-qty-step]');
    if (!stepper) return;

    const delta = parseInt(stepper.getAttribute('data-qty-step'), 10);
    const container = stepper.closest('.qty-control');
    if (!container) return;

    const input = container.querySelector('input[type="number"]');
    if (input) {
      const min = parseInt(input.min || '1', 10);
      const max = parseInt(input.max || '10', 10);
      const current = parseInt(input.value || '1', 10);
      const next = Math.max(min, Math.min(max, current + delta));
      input.value = next;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });

  // Auto-dismiss Toasts
  const toasts = document.querySelectorAll('.toast');
  toasts.forEach(toast => {
    setTimeout(() => {
      toast.classList.add('is-visible');
    }, 50);

    setTimeout(() => {
      toast.classList.remove('is-visible');
      setTimeout(() => toast.remove(), 300);
    }, 4500);
  });

  // Auto-submit elements with data-auto-submit
  document.querySelectorAll('[data-auto-submit]').forEach(el => {
    el.addEventListener('change', () => {
      if (el.form) el.form.submit();
    });
  });

  // Password Visibility Toggle
  document.addEventListener('click', (e) => {
    const toggleBtn = e.target.closest('[data-action="toggle-password"]');
    if (!toggleBtn) return;

    const wrap = toggleBtn.closest('.password-input-wrap');
    if (!wrap) return;

    const input = wrap.querySelector('input');
    if (!input) return;

    const isPassword = (input.type === 'password');
    input.type = isPassword ? 'text' : 'password';

    toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    const eyeIcon = toggleBtn.querySelector('.icon-eye');
    const eyeOffIcon = toggleBtn.querySelector('.icon-eye-off');
    if (eyeIcon && eyeOffIcon) {
      eyeIcon.style.display = isPassword ? 'none' : 'inline-block';
      eyeOffIcon.style.display = isPassword ? 'inline-block' : 'none';
    }
  });

  // Alphabet-only input filter (no numbers allowed)
  document.addEventListener('input', (e) => {
    if (e.target && e.target.hasAttribute('data-alpha-only')) {
      const sanitized = e.target.value.replace(/[^a-zA-Z\s\-\'\.]/g, '');
      if (e.target.value !== sanitized) {
        e.target.value = sanitized;
      }
    }
  });

  // 11-digits-only phone filter
  document.addEventListener('input', (e) => {
    if (e.target && e.target.hasAttribute('data-phone-11')) {
      const sanitized = e.target.value.replace(/[^0-9]/g, '').slice(0, 11);
      if (e.target.value !== sanitized) {
        e.target.value = sanitized;
      }
    }
  });
});

// Toast notification trigger
window.showToast = function (message, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast--${type} glass--strong`;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => toast.classList.add('is-visible'), 20);
  setTimeout(() => {
    toast.classList.remove('is-visible');
    setTimeout(() => toast.remove(), 300);
  }, 4000);
};
