<?php
declare(strict_types=1);

$pageTitle = $title ?? 'GlassCart — Modern Minimalist Hardware & Audio';
$pageDesc = $description ?? 'GlassCart: Premium acoustic audio instruments, biometrics, and desk ergonomics with pure translucency.';
$ogImg = $ogImage ?? asset('assets/img/og-default.svg');
$canonical = $canonicalUrl ?? url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= ea($pageDesc) ?>">
<link rel="canonical" href="<?= ea($canonical) ?>">
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>

<!-- Open Graph / Social Meta -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= ea($pageTitle) ?>">
<meta property="og:description" content="<?= ea($pageDesc) ?>">
<meta property="og:image" content="<?= ea($ogImg) ?>">
<meta name="twitter:card" content="summary_large_image">

<!-- CSRF Meta -->
<meta name="csrf-token" content="<?= ea(csrf_token()) ?>">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">

<!-- Preconnect & Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

<!-- Core Stylesheets -->
<link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/pages.css') ?>">

<!-- Theme Initializer (blocking to prevent FOUC per D-012) -->
<script src="<?= asset('assets/js/theme-init.js') ?>"></script>
