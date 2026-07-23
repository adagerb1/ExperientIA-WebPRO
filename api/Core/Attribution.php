<?php
namespace Core;

/**
 * Atribución de marketing (first-touch). Extrae y sanea los parámetros UTM,
 * el referrer y la landing de origen desde el cuerpo de la petición, para
 * adjuntarlos al lead. El frontend los captura del primer clic y los persiste,
 * de modo que cada lead sepa qué pauta/campaña lo trajo.
 */
final class Attribution
{
    private const CAMPOS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer', 'landing_page'];

    /** Devuelve un mapa columna=>valor saneado (solo los presentes y no vacíos). */
    public static function fromRequest(Request $req): array
    {
        $out = [];
        foreach (self::CAMPOS as $c) {
            $v = $req->input($c);
            if ($v === null) { continue; }
            $v = trim(strip_tags((string) $v));
            if ($v === '') { continue; }
            $max = ($c === 'utm_source' || $c === 'utm_medium') ? 120 : ($c === 'referrer' || $c === 'landing_page' ? 255 : 160);
            $out[$c] = mb_substr($v, 0, $max);
        }
        return $out;
    }
}
