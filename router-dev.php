<?php
/**
 * Router para el servidor embebido de PHP (php -S) en desarrollo.
 * Emula el .htaccess de producción. Uso:
 *   php -S localhost:8092 router-dev.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API (JSON)
if ($uri === '/api' || str_starts_with($uri, '/api/')) { require __DIR__ . '/api/index.php'; return true; }
// Sitemap dinámico
if ($uri === '/sitemap.xml') { require __DIR__ . '/api/sitemap.php'; return true; }
// Archivos estáticos reales (assets, favicon, robots, html…)
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) { return false; }
// Portal admin → su shell estático
if ($uri === '/admin' || str_starts_with($uri, '/admin/')) { require __DIR__ . '/admin/index.html'; return true; }
// Resto → shell público
require __DIR__ . '/index.html';
return true;
