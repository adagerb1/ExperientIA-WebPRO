<?php
require_once __DIR__ . '/partes/ui.php';

$l = locale();
$titulo = meta('titulo') ?: t('meta.title_base');
$descripcion = meta('desc') ?: t('meta.description');
$base = config('app.url');
$urlActual = $base . url_pagina(pagina_actual(), $GLOBALS['__pagina_params'] ?? []);

$schemaOrg = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'ExperientIA',
    'slogan' => 'Automatización · Growth · IA',
    'description' => t('meta.description'),
    'url' => $base,
    'email' => config('mail.from'),
    'logo' => $base . '/favicon.svg',
    'sameAs' => array_column(config('social'), 'url'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="<?= e($l) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?></title>
  <meta name="description" content="<?= e($descripcion) ?>">
  <meta name="theme-color" content="#051126">
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="canonical" href="<?= e($urlActual) ?>">
<?php foreach (config('app.locales') as $alt): ?>
  <link rel="alternate" hreflang="<?= e($alt) ?>" href="<?= e($base . url_cambio_idioma($alt)) ?>">
<?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= e($base . url_cambio_idioma('es')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="ExperientIA">
  <meta property="og:title" content="<?= e($titulo) ?>">
  <meta property="og:description" content="<?= e($descripcion) ?>">
  <meta property="og:url" content="<?= e($urlActual) ?>">
  <meta property="og:locale" content="<?= ['es' => 'es_ES', 'en' => 'en_US', 'pt' => 'pt_BR'][$l] ?>">
  <script type="application/ld+json"><?= $schemaOrg ?></script>
<?= meta('schema') ?>
  <link rel="stylesheet" href="/assets/vendor/tom-select.css">
  <link rel="stylesheet" href="/assets/vendor/intl-tel-input.css">
  <link rel="stylesheet" href="/assets/css/estilos.css">
  <script>document.documentElement.classList.add('js');</script>
</head>
<body>
  <?php require __DIR__ . '/partes/header.php'; ?>
  <main id="main">
    <?= $contenido ?>
  </main>
  <?php require __DIR__ . '/partes/footer.php'; ?>
  <script src="/assets/vendor/tom-select.js" defer></script>
  <script src="/assets/vendor/intl-tel-input.js" defer></script>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
