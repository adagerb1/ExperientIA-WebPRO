<?php
/**
 * Sitemap XML dinámico con alternantes hreflang (es/en/pt).
 * Servido en /sitemap.xml vía RewriteRule. Solo lectura, sin autenticación.
 * Combina rutas estáticas (desde los diccionarios de idioma) + recursos publicados.
 */
header('Content-Type: application/xml; charset=UTF-8');

$locales = ['es', 'en', 'pt'];
$appUrl = rtrim(getenv('APP_URL') ?: 'https://experientia.pro', '/');
$dir = __DIR__ . '/assets/js/lib';

// Slugs por idioma (misma fuente que consume el SPA y el shell SEO)
$slugsByLoc = [];
foreach ($locales as $l) {
    $lang = json_decode(@file_get_contents("{$dir}/lang.{$l}.json") ?: '{}', true) ?: [];
    $slugsByLoc[$l] = $lang['slugs'] ?? [];
}

// Claves de sección comunes a los 3 idiomas (home implícito)
$keys = ['soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq', 'contacto', 'diagnostico', 'agenda'];

function xmlEsc($s) { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1, 'UTF-8'); }

/** Emite una <url> con sus alternantes hreflang para las 3 versiones de idioma. */
function urlBlock(array $paths, string $appUrl, string $priority = '0.8', string $freq = 'weekly'): string {
    $loc = $appUrl . $paths['es'];
    $out = "  <url>\n    <loc>" . xmlEsc($loc) . "</loc>\n";
    foreach (['es', 'en', 'pt'] as $l) {
        $out .= '    <xhtml:link rel="alternate" hreflang="' . $l . '" href="' . xmlEsc($appUrl . $paths[$l]) . "\"/>\n";
    }
    $out .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . xmlEsc($appUrl . $paths['es']) . "\"/>\n";
    $out .= "    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
    return $out;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// Home
echo urlBlock(['es' => '/es/', 'en' => '/en/', 'pt' => '/pt/'], $appUrl, '1.0', 'daily');

// Secciones estáticas
foreach ($keys as $key) {
    $paths = [];
    foreach ($locales as $l) {
        $slug = $slugsByLoc[$l][$key] ?? $slugsByLoc['es'][$key] ?? $key;
        $paths[$l] = "/{$l}/{$slug}";
    }
    $prio = in_array($key, ['soluciones', 'contacto', 'diagnostico'], true) ? '0.9' : '0.7';
    echo urlBlock($paths, $appUrl, $prio);
}

// Recursos publicados (detalle) — best-effort, sin romper el sitemap si falta la BD
try {
    require_once __DIR__ . '/../app/bootstrap.php';
    $rows = \Core\Database::run('SELECT slug FROM resources WHERE active = 1')->fetchAll();
    foreach ($rows as $r) {
        $paths = [];
        foreach ($locales as $l) {
            $rslug = $slugsByLoc[$l]['recursos'] ?? 'recursos';
            $paths[$l] = "/{$l}/{$rslug}/" . rawurlencode($r['slug']);
        }
        echo urlBlock($paths, $appUrl, '0.6', 'monthly');
    }
} catch (\Throwable $e) {
    // Silencioso: el sitemap con rutas estáticas sigue siendo válido.
}

echo '</urlset>' . "\n";
