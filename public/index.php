<?php
/**
 * Shell del SPA con inyección de meta SEO por ruta (para bots/crawlers).
 * El único rol de este PHP es servir el HTML base con las etiquetas <meta>
 * correctas según la URL. La aplicación es Vue 3 (importmap) y toma el control
 * en el navegador. El backend (API) sigue siendo JSON puro.
 */

$locales = ['es', 'en', 'pt'];
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$parts = array_values(array_filter(explode('/', trim($uri, '/'))));
$loc = in_array($parts[0] ?? '', $locales, true) ? $parts[0] : 'es';
$appUrl = getenv('APP_URL') ?: 'https://experientia.pro';

// Diccionario de meta por sección (server-side, sin depender de JS)
$dir = __DIR__ . '/assets/js/lib';
$lang = json_decode(@file_get_contents("{$dir}/lang.{$loc}.json") ?: '{}', true) ?: [];
$seg = $parts[1] ?? '';
$slugs = $lang['slugs'] ?? [];
$key = 'home';
foreach ($slugs as $k => $slug) { if ($slug === $seg) { $key = $k; break; } }

$titleBase = $lang['meta']['title_base'] ?? 'ExperientIA — Automatización · Growth · IA';
$descBase = $lang['meta']['description'] ?? '';
$title = $key === 'home' ? $titleBase : (($lang[$key]['meta_title'] ?? ucfirst($key)) . ' · ExperientIA');
$desc = $key === 'home' ? $descBase : ($lang[$key]['meta_desc'] ?? $descBase);
$canonical = $appUrl . $uri;

$isAdmin = ($parts[0] ?? '') === 'admin';

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?= h($loc) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= h($isAdmin ? 'Portal · ExperientIA' : $title) ?></title>
  <meta name="description" content="<?= h($desc) ?>">
  <meta name="theme-color" content="#051126">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <?php if ($isAdmin): ?>
  <meta name="robots" content="noindex, nofollow">
  <?php else: ?>
  <link rel="canonical" href="<?= h($canonical) ?>">
  <?php foreach ($locales as $l): $altUri = preg_replace('#^/(es|en|pt)#', '/' . $l, $uri) ?: "/{$l}/"; ?>
  <link rel="alternate" hreflang="<?= $l ?>" href="<?= h($appUrl . $altUri) ?>">
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= h($appUrl . (preg_replace('#^/(es|en|pt)#', '/es', $uri) ?: '/es/')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="ExperientIA">
  <meta property="og:title" content="<?= h($title) ?>">
  <meta property="og:description" content="<?= h($desc) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:locale" content="<?= ['es' => 'es_ES', 'en' => 'en_US', 'pt' => 'pt_BR'][$loc] ?>">
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'ExperientIA',
      'slogan' => 'Automatización · Growth · IA', 'description' => $descBase, 'url' => $appUrl,
      'logo' => $appUrl . '/favicon.svg',
      'sameAs' => ['https://www.instagram.com/experientia.sas/', 'https://www.linkedin.com/in/tonny-dager/', 'https://www.facebook.com/Experientia.SAS'],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
  <?php endif; ?>

  <link rel="stylesheet" href="/assets/css/<?= $isAdmin ? 'admin' : 'app' ?>.css">
  <link rel="preload" as="font" type="font/woff2" href="/assets/fonts/inter-latin-wght-normal.woff2" crossorigin>

  <script type="importmap">
  {
    "imports": {
      "vue": "/assets/vendor/vue.esm-browser.prod.js",
      "vue-router": "/assets/vendor/vue-router.esm-browser.prod.js"
    }
  }
  </script>
</head>
<body>
  <div id="app"></div>
  <noscript><div style="padding:2rem;color:#f6f8fb;font-family:sans-serif;">ExperientIA requiere JavaScript. Active JavaScript para continuar.</div></noscript>
  <script type="module" src="/assets/js/<?= $isAdmin ? 'admin/main.js' : 'main.js' ?>"></script>
</body>
</html>
