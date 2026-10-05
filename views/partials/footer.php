<?php
declare(strict_types=1);

$storeName = settings('store_name', 'GlassCart');
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer__grid">
      <div class="footer__col">
        <div class="header__logo" style="margin-bottom: var(--s-3);">
          <img src="<?= asset('assets/img/logo.svg') ?>" alt="" width="24" height="24">
          <span><?= e($storeName) ?></span>
        </div>
        <p style="font-size: 0.9rem; max-width: 280px;">
          Precision transducers, wearable intelligence, and sculptural desk hardware encased in frosted clarity.
        </p>
      </div>

      <div class="footer__col">
        <h4>Collection</h4>
        <ul class="footer__links">
          <li><a href="<?= url('/shop') ?>">All Hardware</a></li>
          <li><a href="<?= url('/category/audio') ?>">Acoustic &amp; Studio</a></li>
          <li><a href="<?= url('/category/wearables') ?>">Biometric Wearables</a></li>
          <li><a href="<?= url('/category/desk-home') ?>">Desk &amp; Lighting</a></li>
          <li><a href="<?= url('/category/accessories') ?>">Cables &amp; Anchors</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Customer Care</h4>
        <ul class="footer__links">
          <li><a href="<?= url('/page/about') ?>">Our Philosophy</a></li>
          <li><a href="<?= url('/page/contact') ?>">Concierge Support</a></li>
          <li><a href="<?= url('/page/shipping') ?>">Shipping Rates</a></li>
          <li><a href="<?= url('/page/returns') ?>">30-Day Returns</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Security &amp; Legal</h4>
        <ul class="footer__links">
          <li><a href="<?= url('/page/privacy') ?>">Privacy Policy</a></li>
          <li><a href="<?= url('/page/terms') ?>">Terms of Service</a></li>
          <li><a href="<?= url('/sitemap.xml') ?>">XML Sitemap</a></li>
        </ul>
      </div>
    </div>

    <div class="footer__bottom">
      <div>&copy; <?= date('Y') ?> <?= e($storeName) ?> Inc. All rights reserved.</div>
      <div style="display: flex; gap: var(--s-3); align-items: center;">
        <span class="badge badge--neutral">Encrypted Libsodium</span>
        <span class="badge badge--neutral">Zero-Carbon Packaging</span>
      </div>
    </div>
  </div>
</footer>
