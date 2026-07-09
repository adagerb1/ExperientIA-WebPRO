<?php
/**
 * ExperientIA · Front controller (PHP puro, sin frameworks).
 * Rutas: /{idioma}/{slug}, /api/*, /admin, /sitemap.xml
 */

declare(strict_types=1);

session_start();

require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/mailer.php';
require __DIR__ . '/../app/LeadCapture.php';
require __DIR__ . '/../app/BookingSlots.php';
require __DIR__ . '/../app/auth.php';

if (config('app.env') !== 'dev') {
    ini_set('display_errors', '0');
}

db_migrate();

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$uri = rtrim($uri, '/') ?: '/';
$metodo = $_SERVER['REQUEST_METHOD'];
$locales = config('app.locales');

// ---------- API ----------
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/../app/controllers/api.php';
    api_despachar(substr($uri, 5), $metodo);
}

// ---------- Portal admin ----------
if ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
    require __DIR__ . '/../app/controllers/admin.php';
    admin_despachar($uri, $metodo);
}

// ---------- Sitemap ----------
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/../app/controllers/paginas.php';
    sitemap_xml();
}

// ---------- Raíz: redirigir según idioma del navegador ----------
if ($uri === '/') {
    $pref = 'es';
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') as $parte) {
        $code = strtolower(substr(trim($parte), 0, 2));
        if (in_array($code, $locales, true)) {
            $pref = $code;
            break;
        }
    }
    header("Location: /{$pref}/", true, 302);
    exit;
}

// ---------- Páginas públicas /{idioma}/{slug} ----------
$partes = explode('/', trim($uri, '/'));
$loc = $partes[0] ?? '';

if (! in_array($loc, $locales, true)) {
    require __DIR__ . '/../app/controllers/paginas.php';
    pagina_404();
}

set_locale($loc);
require __DIR__ . '/../app/controllers/paginas.php';

$slug = $partes[1] ?? '';
$sub = $partes[2] ?? null;

// Mapa slug localizado → página
$paginas = ['soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq', 'contacto', 'diagnostico', 'agenda'];
$clave = null;
foreach ($paginas as $p) {
    if (t("slugs.{$p}") === $slug) {
        $clave = $p;
        break;
    }
}

match (true) {
    $slug === '' => pagina_home(),
    $clave === 'recursos' && $sub !== null => pagina_recurso($sub),
    $clave !== null && $sub === null => pagina_generica($clave),
    default => pagina_404(),
};
