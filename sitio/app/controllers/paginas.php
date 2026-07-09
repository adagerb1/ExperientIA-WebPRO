<?php
/** Controlador de páginas públicas: consulta la BD y renderiza plantillas PHP. */

function contenido_activo(string $tabla, string $orden = 'sort'): array
{
    return db()->query("SELECT * FROM {$tabla} WHERE active = 1 ORDER BY {$orden}")->fetchAll();
}

function pagina_home(): void
{
    set_pagina('home');
    render_pagina('home', [
        'soluciones' => contenido_activo('solutions'),
        'casos' => contenido_activo('case_studies'),
    ]);
    exit;
}

function pagina_generica(string $clave): void
{
    set_pagina($clave);

    $vars = match ($clave) {
        'soluciones' => ['soluciones' => contenido_activo('solutions')],
        'productos' => ['productos' => contenido_activo('products')],
        'casos' => ['casos' => contenido_activo('case_studies')],
        'faq' => ['faqs' => contenido_activo('faqs')],
        'recursos' => ['recursos' => db()->query(
            "SELECT * FROM resources WHERE active = 1 AND published_at IS NOT NULL AND published_at <= '" . ahora() . "' ORDER BY sort"
        )->fetchAll()],
        'diagnostico' => [
            'preguntas' => (require dirname(__DIR__, 2) . '/config/diagnostico.php')['preguntas'],
            'soluciones' => contenido_activo('solutions'),
        ],
        'agenda' => ['slots' => slots_disponibles()],
        default => [],
    };

    render_pagina($clave, $vars);
    exit;
}

function pagina_recurso(string $slug): void
{
    $st = db()->prepare("SELECT * FROM resources WHERE slug = ? AND active = 1 AND published_at IS NOT NULL AND published_at <= ?");
    $st->execute([$slug, ahora()]);
    $recurso = $st->fetch();

    if (! $recurso) {
        pagina_404();
    }

    set_pagina('recurso', ['slug' => $slug]);
    render_pagina($recurso['type'] === 'download' ? 'recurso-descarga' : 'recurso-articulo', ['recurso' => $recurso]);
    exit;
}

function pagina_404(): void
{
    http_response_code(404);
    if (empty($GLOBALS['__locale'])) {
        set_locale('es');
    }
    set_pagina('home');
    render_pagina('404');
    exit;
}

function sitemap_xml(): void
{
    $base = config('app.url');
    $locales = config('app.locales');
    $paginas = ['home', 'soluciones', 'tablero', 'productos', 'casos', 'recursos', 'nosotros', 'faq', 'contacto', 'diagnostico', 'agenda'];

    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $bloque = function (array $porIdioma) use ($base) {
        foreach ($porIdioma as $url) {
            echo "  <url>\n    <loc>{$base}{$url}</loc>\n";
            foreach ($porIdioma as $l => $alt) {
                echo "    <xhtml:link rel=\"alternate\" hreflang=\"{$l}\" href=\"{$base}{$alt}\" />\n";
            }
            echo "  </url>\n";
        }
    };

    foreach ($paginas as $p) {
        $porIdioma = [];
        foreach ($locales as $l) {
            $porIdioma[$l] = url_pagina($p, [], $l);
        }
        $bloque($porIdioma);
    }

    $recursos = db()->query("SELECT slug FROM resources WHERE active = 1 AND published_at IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($recursos as $slug) {
        $porIdioma = [];
        foreach ($locales as $l) {
            $porIdioma[$l] = url_pagina('recurso', ['slug' => $slug], $l);
        }
        $bloque($porIdioma);
    }

    echo '</urlset>';
    exit;
}
