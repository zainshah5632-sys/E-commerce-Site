// theme-init.js: Immediately sets data-theme and data-font-scale to prevent FOUC
(function () {
  try {
    var stored = localStorage.getItem('theme');
    var theme = stored ? stored : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', theme);

    var storedScale = localStorage.getItem('font_scale');
    if (storedScale) {
      document.documentElement.setAttribute('data-font-scale', storedScale);
    }
  } catch (e) {}
})();
