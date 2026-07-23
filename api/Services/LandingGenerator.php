<?php
namespace Services;

/**
 * Generador de landings con IA: AlexIA orquesta agentes especializados.
 *   1) Estratega   → ángulo, promesa y contraste (a partir de la ficha + brief).
 *   2) Copywriter  → los bloques de conversión en ES (héroe, beneficios, pasos,
 *                    entregables, métricas, FAQ; para casos: historia y línea de tiempo).
 *   3) Traductor   → traduce cada texto a EN y PT preservando la estructura.
 * Devuelve el JSON de landing (mismo esquema que edita el panel). NO publica:
 * el borrador cae en el editor para que el usuario lo revise, ajuste y guarde.
 */
final class LandingGenerator
{
    private const ICONS = ['target', 'shield', 'growth', 'gear', 'analitica', 'ia', 'people', 'bulb', 'cube', 'check', 'clock', 'plug'];

    /** @param array $item Ficha (fila BD ya decodificada a arrays i18n). */
    public static function generar(string $tabla, array $item, string $brief): array
    {
        $ficha = self::ficha($tabla, $item);
        $esCaso = ($tabla === 'case_studies');

        // ── Agente 1 · Estratega ────────────────────────────────────────────
        $estr = AlexIA::askJson(
            "Eres la estratega de conversión de ExperientIA SAS (automatización, growth e IA aplicada a negocios; "
            . "el consultor experto es Tonny Dager y la herramienta es GrowthBoard). A partir de la ficha y el brief, "
            . "define el ángulo de una landing de venta premium, honesta y sin humo. Respeta la división de roles: la "
            . "herramienta estructura y mide; la interpretación estratégica es del consultor. No inventes cifras.\n"
            . "Responde JSON: {\"promesa\":\"...\",\"antes\":\"...\",\"despues\":\"...\",\"angulo\":\"...\",\"publico\":\"...\"} en español.",
            "FICHA:\n{$ficha}\n\nBRIEF DEL USUARIO:\n" . ($brief ?: '(sin brief; usa la ficha)')
        );

        // ── Agente 2 · Copywriter ───────────────────────────────────────────
        if ($esCaso) {
            $copy = AlexIA::askJson(
                "Eres copywriter de conversión de ExperientIA. Redacta en ESPAÑOL los bloques de una landing de CASO de éxito "
                . "como historia (reto → jugada → resultado), premium y sin humo, respetando la confidencialidad del cliente "
                . "(no reveles el nombre del cliente). No inventes métricas: usa solo las de la ficha.\n"
                . "Responde JSON EXACTO con esta forma (strings en español):\n"
                . "{\"cta\":\"...\",\"confidencial\":true,"
                . "\"historia\":[{\"tag\":\"El reto\",\"t\":\"...\",\"x\":\"...\",\"viz\":\"radar\"},{\"tag\":\"La jugada\",\"t\":\"...\",\"x\":\"...\",\"viz\":\"flow\"},{\"tag\":\"El resultado\",\"t\":\"...\",\"x\":\"...\",\"viz\":\"bars\"}],"
                . "\"timeline\":[{\"fase\":\"Semanas 1-2\",\"t\":\"...\",\"x\":\"...\"}],"
                . "\"beneficios\":[{\"icon\":\"target\",\"t\":\"...\",\"x\":\"...\"}],"
                . "\"faqs\":[{\"q\":\"...\",\"a\":\"...\"}],"
                . "\"testimonio\":{\"quote\":\"...\",\"cargo\":\"Dirección · cliente en confidencialidad\"}}\n"
                . "Reglas: historia con 3 capítulos y viz en {radar,flow,bars}; timeline 3-4 hitos; beneficios 3 (icon en "
                . implode('/', self::ICONS) . "); faqs 2; testimonio sin nombre propio.",
                "FICHA:\n{$ficha}\n\nESTRATEGIA:\n" . json_encode($estr, JSON_UNESCAPED_UNICODE) . "\n\nBRIEF:\n" . ($brief ?: '(sin brief)')
            );
            $es = [
                'confidencial' => (bool) ($copy['confidencial'] ?? true),
                'proof_casos' => true,
                'cta' => (string) ($copy['cta'] ?? 'Quiero resultados así'),
                'historia' => self::limpiaHistoria($copy['historia'] ?? []),
                'timeline' => self::limpiaTimeline($copy['timeline'] ?? []),
                'beneficios' => self::limpiaBeneficios($copy['beneficios'] ?? []),
                'faqs' => self::limpiaFaqs($copy['faqs'] ?? []),
                'testimonio' => self::limpiaTestimonio($copy['testimonio'] ?? null),
            ];
        } else {
            $copy = AlexIA::askJson(
                "Eres copywriter de conversión de ExperientIA. Redacta en ESPAÑOL los bloques de una landing de venta para esta "
                . ($tabla === 'products' ? 'oferta/producto' : 'solución/servicio') . ", premium y sin humo. No inventes cifras.\n"
                . "Responde JSON EXACTO (strings en español):\n"
                . "{\"cta\":\"...\","
                . "\"beneficios\":[{\"icon\":\"target\",\"t\":\"...\",\"x\":\"...\"}],"
                . "\"pasos\":[{\"t\":\"...\",\"x\":\"...\"}],"
                . "\"entregables\":[\"...\"],"
                . "\"metricas\":[{\"valor\":\"24/7\",\"suf\":\"\",\"label\":\"...\"}],"
                . "\"faqs\":[{\"q\":\"...\",\"a\":\"...\"}]}\n"
                . "Reglas: beneficios 3 (icon en " . implode('/', self::ICONS) . "); pasos 3; entregables 4-5; métricas 3 "
                . "(valor corto tipo 100%, 24/7, 3x; label describe la métrica); faqs 2-3.",
                "FICHA:\n{$ficha}\n\nESTRATEGIA:\n" . json_encode($estr, JSON_UNESCAPED_UNICODE) . "\n\nBRIEF:\n" . ($brief ?: '(sin brief)')
            );
            $es = [
                'proof_casos' => true,
                'promesa' => (string) ($estr['promesa'] ?? ''),
                'antes' => (string) ($estr['antes'] ?? ''),
                'despues' => (string) ($estr['despues'] ?? ''),
                'cta' => (string) ($copy['cta'] ?? 'Quiero más información'),
                'beneficios' => self::limpiaBeneficios($copy['beneficios'] ?? []),
                'pasos' => self::limpiaPasos($copy['pasos'] ?? []),
                'entregables' => array_values(array_filter(array_map('strval', $copy['entregables'] ?? []))),
                'metricas' => self::limpiaMetricas($copy['metricas'] ?? []),
                'faqs' => self::limpiaFaqs($copy['faqs'] ?? []),
            ];
        }

        // ── Construir esqueleto trilingüe (en/pt = es de momento) ───────────
        $land = self::aTrilingue($es);

        // ── Agente 3 · Traductor (ES → EN, ES → PT) ─────────────────────────
        $bag = [];
        self::recolectar($land, $bag);            // referencias a hojas {es,en,pt}
        $textos = array_map(fn ($ref) => $ref['es'], $bag);
        // entregables se traduce aparte (lista por idioma).
        $entregEs = $es['entregables'] ?? null;

        foreach (['en' => 'inglés', 'pt' => 'portugués'] as $code => $nombre) {
            if ($textos) {
                $tr = self::traducir($textos, $nombre);
                foreach ($bag as $i => &$ref) { if (isset($tr[$i])) { $ref[$code] = (string) $tr[$i]; } }
                unset($ref);
            }
            if (is_array($entregEs) && $entregEs) {
                $tr = self::traducir($entregEs, $nombre);
                $land['entregables'][$code] = array_values(array_map('strval', $tr));
            }
        }

        return $land;
    }

    /** Resumen textual de la ficha para alimentar a los agentes. */
    private static function ficha(string $tabla, array $item): string
    {
        $g = fn ($v) => is_array($v) ? (string) ($v['es'] ?? reset($v) ?? '') : (string) $v;
        $out = '';
        if ($tabla === 'case_studies') {
            $out .= 'Título: ' . $g($item['titulo'] ?? '') . "\n";
            $out .= 'Sector: ' . $g($item['sector'] ?? '') . "\n";
            $out .= 'Contexto: ' . $g($item['contexto'] ?? '') . "\n";
            $out .= 'Intervención: ' . $g($item['intervencion'] ?? '') . "\n";
            $res = $item['resultados'] ?? [];
            if (is_string($res)) { $res = json_decode($res, true) ?: []; }
            if ($res) { $out .= "Resultados (usar SOLO estas cifras):\n"; foreach ($res as $r) { $out .= '  - ' . ($r['valor'] ?? '') . ' ' . $g($r['label'] ?? '') . "\n"; } }
        } elseif ($tabla === 'products') {
            $out .= 'Producto: ' . $g($item['nombre'] ?? '') . "\n";
            $out .= 'Rol: ' . $g($item['rol'] ?? '') . "\n";
            $out .= 'Descripción: ' . $g($item['texto'] ?? '') . "\n";
        } else {
            $out .= 'Solución: ' . $g($item['titulo'] ?? '') . "\n";
            $out .= 'Pilar: ' . $g($item['pilar'] ?? '') . "\n";
            $out .= 'Problema: ' . $g($item['problema'] ?? '') . "\n";
            $out .= 'Qué cambia: ' . $g($item['cambia'] ?? '') . "\n";
        }
        return $out;
    }

    private static function traducir(array $textos, string $idioma): array
    {
        $res = AlexIA::askJson(
            "Eres traductor profesional. Traduce al {$idioma} cada texto del arreglo, conservando el orden, el tono premium y "
            . "los marcadores. Devuelve SOLO un arreglo JSON de strings del MISMO tamaño, sin claves ni texto extra.",
            json_encode(array_values($textos), JSON_UNESCAPED_UNICODE)
        );
        // Puede venir como arreglo directo o envuelto en {"result":[...]}.
        if (isset($res[0])) { return $res; }
        foreach ($res as $v) { if (is_array($v)) { return $v; } }
        return $textos;
    }

    /** Convierte cada string de la landing ES en {es,en,pt} (en/pt=es temporal). */
    private static function aTrilingue(array $es): array
    {
        $i18n = fn ($s) => ['es' => (string) $s, 'en' => (string) $s, 'pt' => (string) $s];
        $out = ['proof_casos' => $es['proof_casos'] ?? true];
        if (isset($es['confidencial'])) { $out['confidencial'] = (bool) $es['confidencial']; }
        foreach (['promesa', 'antes', 'despues', 'cta'] as $k) { if (isset($es[$k])) { $out[$k] = $i18n($es[$k]); } }
        if (isset($es['beneficios'])) { $out['beneficios'] = array_map(fn ($b) => ['icon' => $b['icon'], 't' => $i18n($b['t']), 'x' => $i18n($b['x'])], $es['beneficios']); }
        if (isset($es['pasos'])) { $out['pasos'] = array_map(fn ($p) => ['t' => $i18n($p['t']), 'x' => $i18n($p['x'])], $es['pasos']); }
        if (isset($es['metricas'])) { $out['metricas'] = array_map(fn ($m) => ['valor' => $m['valor'], 'suf' => $m['suf'] ?? '', 'label' => $i18n($m['label'])], $es['metricas']); }
        if (isset($es['faqs'])) { $out['faqs'] = array_map(fn ($f) => ['q' => $i18n($f['q']), 'a' => $i18n($f['a'])], $es['faqs']); }
        if (isset($es['historia'])) { $out['historia'] = array_map(fn ($h) => ['tag' => $i18n($h['tag']), 't' => $i18n($h['t']), 'x' => $i18n($h['x']), 'viz' => $h['viz'], 'img' => ''], $es['historia']); }
        if (isset($es['timeline'])) { $out['timeline'] = array_map(fn ($s) => ['fase' => $i18n($s['fase']), 't' => $i18n($s['t']), 'x' => $i18n($s['x'])], $es['timeline']); }
        if (! empty($es['testimonio'])) { $t = $es['testimonio']; $out['testimonio'] = ['on' => true, 'quote' => $i18n($t['quote']), 'autor' => $t['autor'] ?? '', 'cargo' => $i18n($t['cargo'] ?? '')]; }
        if (isset($es['entregables'])) { $out['entregables'] = ['es' => $es['entregables'], 'en' => $es['entregables'], 'pt' => $es['entregables']]; }
        return $out;
    }

    /** Recolecta referencias a cada hoja i18n {es,en,pt} (menos entregables, que es lista). */
    private static function recolectar(array &$node, array &$bag): void
    {
        foreach ($node as $k => &$v) {
            if (! is_array($v)) { continue; }
            if ($k === 'entregables') { continue; }
            if (array_key_exists('es', $v) && is_string($v['es']) && array_key_exists('en', $v)) {
                $bag[] = &$v;
            } else {
                self::recolectar($v, $bag);
            }
        }
        unset($v);
    }

    // ── Saneadores (garantizan forma y límites de cada bloque) ──────────────
    private static function limpiaBeneficios(array $a): array
    {
        $out = [];
        foreach (array_slice($a, 0, 4) as $b) {
            $icon = in_array($b['icon'] ?? '', self::ICONS, true) ? $b['icon'] : 'target';
            if (trim((string) ($b['t'] ?? '')) === '') { continue; }
            $out[] = ['icon' => $icon, 't' => (string) $b['t'], 'x' => (string) ($b['x'] ?? '')];
        }
        return $out;
    }
    private static function limpiaPasos(array $a): array
    {
        $out = [];
        foreach (array_slice($a, 0, 4) as $p) { if (trim((string) ($p['t'] ?? '')) === '') { continue; } $out[] = ['t' => (string) $p['t'], 'x' => (string) ($p['x'] ?? '')]; }
        return $out;
    }
    private static function limpiaMetricas(array $a): array
    {
        $out = [];
        foreach (array_slice($a, 0, 4) as $m) { if (trim((string) ($m['valor'] ?? '')) === '') { continue; } $out[] = ['valor' => (string) $m['valor'], 'suf' => (string) ($m['suf'] ?? ''), 'label' => (string) ($m['label'] ?? '')]; }
        return $out;
    }
    private static function limpiaFaqs(array $a): array
    {
        $out = [];
        foreach (array_slice($a, 0, 4) as $f) { if (trim((string) ($f['q'] ?? '')) === '') { continue; } $out[] = ['q' => (string) $f['q'], 'a' => (string) ($f['a'] ?? '')]; }
        return $out;
    }
    private static function limpiaHistoria(array $a): array
    {
        $viz = ['radar', 'flow', 'bars'];
        $out = [];
        foreach (array_slice($a, 0, 4) as $i => $h) {
            if (trim((string) ($h['t'] ?? '')) === '' && trim((string) ($h['x'] ?? '')) === '') { continue; }
            $v = in_array($h['viz'] ?? '', $viz, true) ? $h['viz'] : $viz[$i % 3];
            $out[] = ['tag' => (string) ($h['tag'] ?? ''), 't' => (string) ($h['t'] ?? ''), 'x' => (string) ($h['x'] ?? ''), 'viz' => $v];
        }
        return $out;
    }
    private static function limpiaTimeline(array $a): array
    {
        $out = [];
        foreach (array_slice($a, 0, 5) as $s) { if (trim((string) ($s['t'] ?? '')) === '') { continue; } $out[] = ['fase' => (string) ($s['fase'] ?? ''), 't' => (string) $s['t'], 'x' => (string) ($s['x'] ?? '')]; }
        return $out;
    }
    private static function limpiaTestimonio($t): ?array
    {
        if (! is_array($t) || trim((string) ($t['quote'] ?? '')) === '') { return null; }
        return ['quote' => (string) $t['quote'], 'autor' => (string) ($t['autor'] ?? ''), 'cargo' => (string) ($t['cargo'] ?? '')];
    }
}
