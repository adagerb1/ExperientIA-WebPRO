<?php
/** Router para el servidor embebido de PHP (php -S). Emula el .htaccess. */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;
// Archivos estáticos reales
if ($uri !== '/' && is_file($file)) { return false; }
// API
if (str_starts_with($uri, '/api/')) { require __DIR__ . '/api.php'; return true; }
// Sitemap dinámico
if ($uri === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; return true; }
// Todo lo demás → shell SPA
require __DIR__ . '/index.php';
return true;
