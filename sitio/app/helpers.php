<?php
/** Utilidades de la plataforma (sin frameworks). */

function config(?string $key = null): mixed
{
    static $config = null;
    $config ??= require __DIR__ . '/config.php';

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $part) {
        $value = $value[$part] ?? null;
        if ($value === null) {
            break;
        }
    }

    return $value;
}

/** Escapa HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function locale(): string
{
    return $GLOBALS['__locale'] ?? config('app.default_locale');
}

function set_locale(string $locale): void
{
    $GLOBALS['__locale'] = $locale;
}

/** Diccionario del idioma actual (lang/{locale}.php), con acceso por puntos. */
function t(string $key, ?string $locale = null): mixed
{
    static $dicts = [];
    $locale ??= locale();
    $dicts[$locale] ??= require dirname(__DIR__) . "/lang/{$locale}.php";

    $value = $dicts[$locale];
    foreach (explode('.', $key) as $part) {
        $value = $value[$part] ?? null;
        if ($value === null) {
            return $key;
        }
    }

    return $value;
}

/** Traducción de un campo JSON {es,en,pt} con respaldo al español. */
function tr(mixed $field, ?string $locale = null): mixed
{
    if (is_string($field)) {
        $decoded = json_decode($field, true);
        if (is_array($decoded)) {
            $field = $decoded;
        }
    }
    if (! is_array($field)) {
        return $field ?? '';
    }

    $locale ??= locale();
    $value = $field[$locale] ?? null;

    if ($value === null || $value === '' || $value === []) {
        $value = $field['es'] ?? (reset($field) ?: '');
    }

    return $value;
}

function tr_lines(mixed $field, ?string $locale = null): array
{
    $value = tr($field, $locale);
    if (is_array($value)) {
        return array_values(array_filter($value));
    }
    return array_values(array_filter(array_map('trim', explode("\n", (string) $value))));
}

/** URL de una página en el idioma actual (slugs localizados). */
function url_pagina(string $key, array $params = [], ?string $locale = null): string
{
    $locale ??= locale();
    if ($key === 'home') {
        return "/{$locale}/";
    }
    $slug = t("slugs.{$key}", $locale);
    $extra = isset($params['slug']) ? '/' . rawurlencode($params['slug']) : '';
    return "/{$locale}/{$slug}{$extra}";
}

/** URL de la página actual en otro idioma (selector ES/EN/PT). */
function url_cambio_idioma(string $locale): string
{
    $actual = $GLOBALS['__pagina_actual'] ?? 'home';
    $params = $GLOBALS['__pagina_params'] ?? [];
    return url_pagina($actual, $params, $locale);
}

/** Marca la página actual (para nav activa, hreflang y selector de idioma). */
function set_pagina(string $key, array $params = []): void
{
    $GLOBALS['__pagina_actual'] = $key;
    $GLOBALS['__pagina_params'] = $params;
}

function pagina_actual(): string
{
    return $GLOBALS['__pagina_actual'] ?? 'home';
}

/** Renderiza una plantilla con variables. */
function view(string $template, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . "/templates/{$template}.php";
    return ob_get_clean();
}

function render_pagina(string $template, array $vars = []): void
{
    // El contenido se renderiza primero para que set_meta() surta efecto en el <head>.
    require_once dirname(__DIR__) . '/templates/partes/ui.php';
    $contenido = view("paginas/{$template}", $vars);
    echo view('layout', ['contenido' => $contenido]);
}

/** Define título/descripción/schema de la página (llamar al inicio de la plantilla). */
function set_meta(string $titulo, string $desc = '', string $schemaExtra = ''): void
{
    $GLOBALS['__meta'] = ['titulo' => $titulo, 'desc' => $desc, 'schema' => $schemaExtra];
}

function meta(string $campo, string $porDefecto = ''): string
{
    return $GLOBALS['__meta'][$campo] ?? $porDefecto ?: $porDefecto;
}

/** Respuesta JSON de la API. */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 422, array $extra = []): never
{
    json_out(['ok' => false, 'error' => $message] + $extra, $status);
}

/** Cuerpo de la petición (JSON o formulario). */
function input(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $raw = file_get_contents('php://input');
    if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $data = json_decode($raw, true) ?: [];
    } else {
        $data = $_POST;
    }
    return $data;
}

/** CSRF por sesión: token en <meta>, la API exige cabecera X-CSRF. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}

function csrf_verificar(): void
{
    $token = $_SERVER['HTTP_X_CSRF'] ?? input()['_csrf'] ?? '';
    if (! hash_equals($_SESSION['csrf'] ?? '', $token)) {
        json_error('CSRF inválido', 419);
    }
}

/** Rate limit sencillo por IP+acción usando la tabla throttle. */
function throttle(string $accion, int $max = 10, int $ventanaSeg = 60): void
{
    $k = $accion . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');
    $pdo = db();
    $row = $pdo->prepare('SELECT hits, reset_at FROM throttle WHERE k = ?');
    $row->execute([$k]);
    $r = $row->fetch();
    $ahora = time();

    if (! $r || $r['reset_at'] < $ahora) {
        $pdo->prepare('REPLACE INTO throttle (k, hits, reset_at) VALUES (?, 1, ?)')->execute([$k, $ahora + $ventanaSeg]);
        return;
    }
    if ($r['hits'] >= $max) {
        json_error('Demasiadas solicitudes. Intente de nuevo en un momento.', 429);
    }
    $pdo->prepare('UPDATE throttle SET hits = hits + 1 WHERE k = ?')->execute([$k]);
}

/** Firma HMAC para enlaces de descarga con expiración. */
function firmar(string $dato, int $expira): string
{
    return hash_hmac('sha256', "{$dato}|{$expira}", config('secret'));
}

function firma_valida(string $dato, int $expira, string $firma): bool
{
    return $expira > time() && hash_equals(firmar($dato, $expira), $firma);
}

function ahora(): string
{
    return gmdate('Y-m-d H:i:s');
}
