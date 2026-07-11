<?php
namespace Core;

/**
 * Taxonomías del negocio (industrias, países) leídas de la base de datos, con
 * fallback a la configuración/semilla si aún no están migradas. Cacheado por
 * petición. Sirve a los formularios públicos, la segmentación y la validación.
 */
final class Taxonomy
{
    private static array $cache = [];

    /** [{key, nombre:{es,en,pt}}] */
    public static function industries(): array
    {
        return self::$cache['ind'] ??= self::loadIndustries();
    }

    /** Claves válidas de industria (BD ∪ config) para validación. */
    public static function industryKeys(): array
    {
        $keys = array_column(self::industries(), 'key');
        return array_values(array_unique(array_merge($keys, array_keys(biz('industries')))));
    }

    /** [{iso, nombre:{es,en,pt}}] */
    public static function countries(): array
    {
        return self::$cache['cty'] ??= self::loadCountries();
    }

    private static function loadIndustries(): array
    {
        try {
            $rows = Database::run('SELECT ikey, nombre FROM industries WHERE active = 1 ORDER BY sort, ikey')->fetchAll();
            if ($rows) {
                return array_map(fn ($r) => ['key' => $r['ikey'], 'nombre' => self::json($r['nombre'], $r['ikey'])], $rows);
            }
        } catch (\Throwable $e) { /* sin migrar → fallback */ }
        $out = [];
        foreach (biz('industries') as $k => $es) { $out[] = ['key' => $k, 'nombre' => ['es' => $es]]; }
        return $out;
    }

    private static function loadCountries(): array
    {
        try {
            $rows = Database::run('SELECT iso, nombre FROM countries WHERE active = 1 ORDER BY sort, iso')->fetchAll();
            if ($rows) {
                return array_map(fn ($r) => ['iso' => $r['iso'], 'nombre' => self::json($r['nombre'], $r['iso'])], $rows);
            }
        } catch (\Throwable $e) { /* sin migrar → fallback */ }
        $p = require BASE_PATH . '/api/config/paises.php';
        $out = [];
        foreach (array_keys($p['es']) as $iso) {
            $out[] = ['iso' => $iso, 'nombre' => ['es' => $p['es'][$iso] ?? $iso, 'en' => $p['en'][$iso] ?? null, 'pt' => $p['pt'][$iso] ?? null]];
        }
        return $out;
    }

    private static function json($v, string $fb): array
    {
        if (is_array($v)) { return $v; }
        $d = is_string($v) ? json_decode($v, true) : null;
        return is_array($d) ? $d : ['es' => $fb];
    }
}
