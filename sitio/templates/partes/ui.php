<?php
/** Componentes de UI del sitio público (funciones que devuelven HTML). */

function icono(string $nombre, int $size = 24): string
{
    static $iconos = null;
    $iconos ??= [
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>',
        'growth' => '<path d="M3 21h18"/><path d="M4 16.5 9.5 11l3.5 3.5L19.5 8"/><path d="M15.5 8h4v4"/>',
        'gear' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.6M12 18.6v2.6M21.2 12h-2.6M5.4 12H2.8M18.5 5.5l-1.9 1.9M7.4 16.6l-1.9 1.9M18.5 18.5l-1.9-1.9M7.4 7.4 5.5 5.5"/>',
        'ia' => '<circle cx="6" cy="6" r="2.2"/><circle cx="17.5" cy="4.5" r="1.8"/><circle cx="12" cy="12" r="2.6"/><circle cx="5.5" cy="18" r="1.8"/><circle cx="18" cy="18.5" r="2.2"/><path d="M7.8 7.4l2.4 2.7M14.4 10.6l1.8-4.3M10.2 13.9l-3.2 2.7M14.3 13.7l2.3 3.1"/>',
        'shield' => '<path d="M12 3l7 2.8v5.3c0 4.4-2.9 7.4-7 9.1-4.1-1.7-7-4.7-7-9.1V5.8z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
        'bulb' => '<path d="M12 3a6 6 0 0 0-3.4 10.9c.7.5 1.1 1.3 1.1 2.1h4.6c0-.8.4-1.6 1.1-2.1A6 6 0 0 0 12 3z"/><path d="M9.7 19.5h4.6M10.6 22h2.8"/>',
        'cube' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12 4 7.5M12 12v9"/>',
        'analitica' => '<rect x="3" y="4" width="18" height="15" rx="2"/><path d="M3 8.5h18"/><path d="M7 15.5v-2.2M10.3 15.5v-3.8M13.6 15.5v-2.8M16.9 15.5v-4.6"/>',
        'people' => '<circle cx="9" cy="8.2" r="3"/><path d="M3.8 19.5c0-2.9 2.3-5.2 5.2-5.2s5.2 2.3 5.2 5.2"/><circle cx="16.8" cy="9" r="2.4"/><path d="M16 14.6c2.4.3 4.2 2.2 4.2 4.9"/>',
        'impulso' => '<path d="M12 2.5c3 2 4.9 5.8 4.9 9.6l-2.3 2.4H9.4L7.1 12c0-3.8 1.9-7.5 4.9-9.5z"/><circle cx="12" cy="8.8" r="1.7"/><path d="M7.2 12.4 4.4 15l2.8.6M16.8 12.4l2.8 2.6-2.8.6M10.4 17l1.6 4 1.6-4"/>',
        'check' => '<path d="m4.5 12.5 5 5L19.5 6.5"/>',
        'arrow' => '<path d="M4 12h15"/><path d="m13.5 6 6 6-6 6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.4 2"/>',
        'alert' => '<path d="M6.2 9.3a5.8 5.8 0 0 1 11.6 0c0 4.6 1.9 5.8 1.9 5.8H4.3s1.9-1.2 1.9-5.8z"/><path d="M10 19.2a2.1 2.1 0 0 0 4 0"/>',
        'eye' => '<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.6"/>',
        'doc' => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M10 12h5M10 15.5h5"/>',
        'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><circle cx="12" cy="15" r="1.4" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>',
        'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path d="M7.2 10.2v6.6M7.2 7.4v.02"/><path d="M11 16.8v-3.6a2.2 2.2 0 0 1 4.4 0v3.6M11 16.8v-6.6"/>',
        'facebook' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M14.8 8.2h-1.3c-.9 0-1.6.7-1.6 1.6v1.4m-1.6 0h4m-2.4 0v6.1"/>',
    ];

    $cuerpo = $iconos[$nombre] ?? $iconos['target'];
    return "<svg width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.6\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">{$cuerpo}</svg>";
}

function simbolo_marca(string $variante = 'gradient', int $size = 40, string $clase = ''): string
{
    $uid = 'xg' . substr(md5((string) mt_rand()), 0, 6);
    $solido = match ($variante) { 'white' => '#ffffff', 'black' => '#0a1224', default => "url(#{$uid})" };
    $trazo = match ($variante) { 'white' => '#ffffff', 'black' => '#0a1224', default => '#00e5ff' };
    $w = $size * 1.28;

    $defs = $variante === 'gradient'
        ? "<defs><linearGradient id=\"{$uid}\" x1=\"18\" y1=\"0\" x2=\"50\" y2=\"100\" gradientUnits=\"userSpaceOnUse\"><stop offset=\"0\" stop-color=\"#00e5ff\"/><stop offset=\"0.52\" stop-color=\"#7b61ff\"/><stop offset=\"1\" stop-color=\"#00e5ff\"/></linearGradient></defs>"
        : '';

    return "<svg class=\"{$clase}\" width=\"{$w}\" height=\"{$size}\" viewBox=\"0 0 128 100\" fill=\"none\" role=\"img\" aria-hidden=\"true\">{$defs}"
        . "<path fill=\"{$solido}\" d=\"M 6 2 L 38 2 L 64 41.5 L 64 58.5 L 38 98 L 6 98 L 6 55.5 L 12.5 52 L 12.5 48 L 6 44.5 Z\"/>"
        . "<g stroke=\"{$trazo}\" fill=\"none\" stroke-width=\"10.5\">"
        . "<polyline points=\"88,26.5 72,50 88,73.5\"/><polyline points=\"61.5,32 78.5,7.25 96,7.25\"/>"
        . "<line x1=\"102.5\" y1=\"6\" x2=\"124\" y2=\"31.5\" stroke-width=\"11\"/>"
        . "<polyline points=\"61.5,68 78.5,92.75 96,92.75\"/><line x1=\"102.5\" y1=\"94\" x2=\"124\" y2=\"68.5\" stroke-width=\"11\"/></g></svg>";
}

function logo_marca(bool $descriptor = true, int $size = 40): string
{
    $desc = $descriptor ? '<span class="brand-logo__descriptor">Automatización · Growth · IA</span>' : '';
    return "<span class=\"brand-logo\" style=\"--logo-h:{$size}px\" translate=\"no\">"
        . simbolo_marca('gradient', $size)
        . "<span class=\"brand-logo__text\"><span class=\"brand-logo__word\">Experient<i class=\"brand-logo__ia\">IA</i></span>{$desc}</span></span>";
}

function hero_pagina(string $eyebrow, string $titulo, string $sub = '', string $extra = ''): string
{
    $subHtml = $sub !== '' ? '<p class="lead reveal" style="--reveal-delay:.16s">' . e($sub) . '</p>' : '';
    return '<section class="page-hero"><div class="bg-atmos" aria-hidden="true"><div class="bg-grid"></div>'
        . '<div class="halo halo-cyan" style="width:520px;height:520px;top:-260px;right:-140px;"></div>'
        . '<div class="halo halo-violet" style="width:420px;height:420px;top:40px;left:-200px;opacity:.35;"></div></div>'
        . '<div class="container page-hero__inner"><p class="eyebrow reveal">' . e($eyebrow) . '</p>'
        . '<h1 class="display reveal" style="--reveal-delay:.08s">' . e($titulo) . '</h1>'
        . $subHtml . $extra . '</div></section>';
}

function seccion_cta(string $titulo, string $sub, string $urlPrimario, string $ctaPrimario, string $urlSecundario = '', string $ctaSecundario = ''): string
{
    $secundario = $urlSecundario !== ''
        ? '<a href="' . e($urlSecundario) . '" class="btn btn-ghost">' . e($ctaSecundario) . '</a>'
        : '';
    return '<section class="section cta-final"><div class="container"><div class="cta-final__panel card-lum reveal">'
        . '<div class="bg-atmos" aria-hidden="true"><div class="halo halo-cyan anim-pulse" style="width:420px;height:420px;bottom:-260px;left:-120px;"></div>'
        . '<div class="halo halo-violet anim-pulse" style="width:460px;height:460px;top:-280px;right:-140px;animation-delay:2.4s;"></div><div class="bg-skyline"></div></div>'
        . '<div class="cta-final__content"><h2 class="h2">' . e($titulo) . '</h2>'
        . ($sub !== '' ? '<p class="lead">' . e($sub) . '</p>' : '')
        . '<div class="cta-final__actions"><a href="' . e($urlPrimario) . '" class="btn btn-primary">' . e($ctaPrimario) . '</a>' . $secundario . '</div>'
        . '</div></div></div></section>';
}

/** Tablero de Crecimiento (mock visual del centro de comando). */
function dash_mock(string $clase = ''): string
{
    $L = [
        'es' => ['titulo' => 'Tablero de Crecimiento', 'vivo' => 'En vivo', 'crec' => 'Crecimiento sostenible', 'vs' => 'vs. trimestre anterior', 'proc' => 'Procesos optimizados', 'auto' => 'Automatización', 'roi' => 'Retorno del programa', 'flujo' => 'Flujo de automatización', 'ing' => 'Ingresos por canal'],
        'en' => ['titulo' => 'Growth Board', 'vivo' => 'Live', 'crec' => 'Sustainable growth', 'vs' => 'vs. previous quarter', 'proc' => 'Processes optimized', 'auto' => 'Automation', 'roi' => 'Program return', 'flujo' => 'Automation flow', 'ing' => 'Revenue by channel'],
        'pt' => ['titulo' => 'Painel de Crescimento', 'vivo' => 'Ao vivo', 'crec' => 'Crescimento sustentável', 'vs' => 'vs. trimestre anterior', 'proc' => 'Processos otimizados', 'auto' => 'Automação', 'roi' => 'Retorno do programa', 'flujo' => 'Fluxo de automação', 'ing' => 'Receita por canal'],
    ][locale()];
    $g = 'dg' . substr(md5((string) mt_rand()), 0, 5);

    $barras = '';
    foreach ([34, 52, 40, 66, 58, 80, 72, 92] as $i => $h) {
        $hot = $i >= 6 ? ' class="is-hot"' : '';
        $d = $i * 0.08;
        $barras .= "<span style=\"--h:{$h}%; --d:{$d}s\"{$hot}></span>";
    }

    return '<div class="dash ' . e($clase) . '" role="img" aria-label="' . e($L['titulo']) . '">'
        . '<div class="dash__chrome"><span class="dash__dot"></span><span class="dash__dot"></span><span class="dash__dot"></span>'
        . '<span class="dash__title">ExperientIA · ' . e($L['titulo']) . '</span>'
        . '<span class="dash__live"><i class="anim-pulse"></i>' . e($L['vivo']) . '</span></div>'
        . '<div class="dash__grid">'
        . '<div class="dash__card dash__card--chart"><p class="dash__label">' . e($L['crec']) . '</p><p class="dash__big grad-text">+42%</p><p class="dash__hint">' . e($L['vs']) . '</p>'
        . '<svg viewBox="0 0 220 74" preserveAspectRatio="none" aria-hidden="true"><defs>'
        . "<linearGradient id=\"{$g}l\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"0\"><stop offset=\"0\" stop-color=\"#18d6f1\"/><stop offset=\"1\" stop-color=\"#7a63ff\"/></linearGradient>"
        . "<linearGradient id=\"{$g}a\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"rgba(24,214,241,0.25)\"/><stop offset=\"1\" stop-color=\"rgba(24,214,241,0)\"/></linearGradient></defs>"
        . "<path d=\"M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12 L220 74 L0 74 Z\" fill=\"url(#{$g}a)\"/>"
        . "<path class=\"dash__line\" d=\"M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12\" fill=\"none\" stroke=\"url(#{$g}l)\" stroke-width=\"2.4\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>"
        . '<circle cx="204" cy="10" r="3.4" fill="#18d6f1" class="anim-pulse"/></svg></div>'
        . '<div class="dash__card dash__card--ring"><p class="dash__label">' . e($L['auto']) . '</p><div class="dash__ring">'
        . '<svg viewBox="0 0 96 96" aria-hidden="true"><circle cx="48" cy="48" r="40" fill="none" stroke="rgba(217,226,240,0.12)" stroke-width="7"/>'
        . "<circle cx=\"48\" cy=\"48\" r=\"40\" fill=\"none\" stroke=\"url(#{$g}r)\" stroke-width=\"7\" stroke-linecap=\"round\" stroke-dasharray=\"196 251\" transform=\"rotate(-90 48 48)\"/>"
        . "<defs><linearGradient id=\"{$g}r\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"#18d6f1\"/><stop offset=\"1\" stop-color=\"#7a63ff\"/></linearGradient></defs></svg>"
        . '<span class="dash__ring-val">78%</span></div><p class="dash__hint">' . e($L['proc']) . '</p></div>'
        . '<div class="dash__card dash__card--bars"><p class="dash__label">' . e($L['ing']) . '</p><div class="dash__bars" aria-hidden="true">' . $barras . '</div></div>'
        . '<div class="dash__card dash__card--roi"><p class="dash__label">' . e($L['roi']) . '</p><p class="dash__big">3.2x</p><div class="dash__spark" aria-hidden="true"><span></span><span></span><span></span></div></div>'
        . '<div class="dash__card dash__card--flow"><p class="dash__label">' . e($L['flujo']) . '</p><div class="dash__flow" aria-hidden="true">'
        . '<span class="dash__node">' . icono('play', 16) . '</span><i class="dash__wire"></i>'
        . '<span class="dash__node">' . icono('gear', 16) . '</span><i class="dash__wire"></i>'
        . '<span class="dash__node dash__node--ok">' . icono('check', 16) . '</span></div></div>'
        . '</div></div>';
}

/** Campos compartidos de captura de lead para formularios API. */
function campos_lead(bool $completo = false, bool $empresa = true): string
{
    $paises = paises_lista();
    $req = fn (bool $c) => $c ? ' *' : '';

    $opcPais = '<option value=""></option>';
    foreach ($paises as $code => $nombre) {
        $opcPais .= '<option value="' . e($code) . '">' . e($nombre) . '</option>';
    }

    $opcInd = '<option value=""></option>';
    foreach (config('industries') as $k => $v) {
        $opcInd .= '<option value="' . e($k) . '">' . e($v) . '</option>';
    }

    $opcTam = '<option value=""></option>';
    foreach (t('form.empleados_opciones') as $k => $v) {
        $opcTam .= '<option value="' . e($k) . '">' . e($v) . '</option>';
    }

    $html = '<div class="honeypot" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<div class="form-row">'
        . '<div class="field"><label>' . e(t('form.nombre')) . ' *<input name="name" type="text" autocomplete="name" required></label><p class="error" data-error="name" hidden>' . e(t('form.error_validacion')) . '</p></div>'
        . '<div class="field"><label>' . e(t('form.email')) . ' *<input name="email" type="email" autocomplete="email" required></label><p class="error" data-error="email" hidden>' . e(t('form.error_validacion')) . '</p></div>'
        . '</div>'
        . '<div class="form-row">'
        . '<div class="field"><label>' . e(t('form.telefono')) . '<input type="tel" data-phone autocomplete="tel"></label>'
        . '<input type="hidden" name="phone_wa"><input type="hidden" name="phone_dial">'
        . '<p class="hint">' . e(t('form.telefono_hint')) . '</p><p class="error" data-phone-error hidden>' . e(t('form.error_telefono')) . '</p></div>'
        . '<div class="field"><label>' . e(t('form.pais')) . ' *<select name="country" required data-combobox data-placeholder="' . e(t('form.pais_placeholder')) . '">' . $opcPais . '</select></label><p class="error" data-error="country" hidden>' . e(t('form.error_validacion')) . '</p></div>'
        . '</div>';

    if ($empresa) {
        $html .= '<div class="form-row">'
            . '<div class="field"><label>' . e(t('form.empresa')) . $req($completo) . '<input name="company" type="text" autocomplete="organization"' . ($completo ? ' required' : '') . '></label></div>'
            . ($completo ? '<div class="field"><label>' . e(t('form.rol')) . '<input name="role" type="text" autocomplete="organization-title"></label></div>' : '')
            . '</div>'
            . '<div class="form-row">'
            . '<div class="field"><label>' . e(t('form.industria')) . $req($completo) . '<select name="industry"' . ($completo ? ' required' : '') . ' data-combobox data-placeholder="' . e(t('form.industria_placeholder')) . '">' . $opcInd . '</select></label></div>'
            . '<div class="field"><label>' . e(t('form.empleados')) . $req($completo) . '<select name="company_size"' . ($completo ? ' required' : '') . ' data-combobox>' . $opcTam . '</select></label></div>'
            . '</div>';
    }

    return $html;
}

/** Lista de países en el idioma actual (ISO 3166-1). */
function paises_lista(): array
{
    static $cache = [];
    $l = locale();
    if (isset($cache[$l])) {
        return $cache[$l];
    }
    $datos = require dirname(__DIR__, 2) . '/config/paises.php';
    $lista = $datos[$l] ?? $datos['es'];
    asort($lista, SORT_LOCALE_STRING);
    return $cache[$l] = $lista;
}
