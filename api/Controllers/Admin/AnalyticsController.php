<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;

/**
 * Tablero de Crecimiento (BI de ExperientIA): embudo de conversión, tendencias,
 * fuentes, segmentación y señales de demanda. SQL portable MySQL/SQLite; el
 * bucketing temporal y el parseo de JSON se hacen en PHP para no depender de
 * funciones específicas del motor.
 */
final class AnalyticsController extends Controller
{
    public function panel(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        $pdo = Database::pdo();
        $q = fn (string $sql, array $p = []) => (int) Database::run($sql, $p)->fetchColumn();

        // ── Embudo de conversión ──────────────────────────────────────────
        $leads = $q('SELECT COUNT(*) FROM leads');
        $contactados = $q("SELECT COUNT(*) FROM leads WHERE status IN ('contactado','calificado','propuesta','cliente')");
        $calificados = $q("SELECT COUNT(*) FROM leads WHERE status IN ('calificado','propuesta','cliente')");
        $reservas = $q('SELECT COUNT(DISTINCT lead_id) FROM bookings');
        $ganados = $q("SELECT COUNT(*) FROM leads WHERE status = 'cliente'");
        $rate = fn (int $a, int $b) => $b > 0 ? round($a / $b * 100, 1) : 0.0;
        $embudo = [
            'etapas' => [
                ['clave' => '', 'label' => 'Leads', 'valor' => $leads],
                ['clave' => 'contactado', 'label' => 'Contactados', 'valor' => $contactados],
                ['clave' => 'calificado', 'label' => 'Calificados', 'valor' => $calificados],
                ['clave' => '', 'label' => 'Con reserva', 'valor' => $reservas],
                ['clave' => 'cliente', 'label' => 'Clientes', 'valor' => $ganados],
            ],
            'tasas' => [
                'contacto' => $rate($contactados, $leads),
                'calificacion' => $rate($calificados, $leads),
                'reserva' => $rate($reservas, $leads),
                'cierre' => $rate($ganados, $leads),
            ],
        ];

        // ── Tendencia semanal (12 semanas) ────────────────────────────────
        $desde = gmdate('Y-m-d', time() - 83 * 86400);
        $tendencia = $this->serieSemanal($desde);

        // ── Segmentaciones (agrupaciones portables) ───────────────────────
        $fuentes = $this->grupo('source', '(sin origen)');
        $canales = $this->grupo('channel', 'web');
        $industrias = $this->grupo('industry', '(sin dato)', 8);
        $tamanos = $this->grupo('company_size', '(sin dato)');
        $paises = $this->grupo('country', '(sin dato)', 8);

        // Pipeline por estado (orden fijo del negocio)
        $orden = ['nuevo', 'contactado', 'calificado', 'propuesta', 'cliente', 'descartado'];
        $crudo = [];
        foreach ($pdo->query("SELECT status, COUNT(*) c FROM leads GROUP BY status")->fetchAll() as $r) {
            $crudo[$r['status']] = (int) $r['c'];
        }
        $pipeline = [];
        foreach ($orden as $st) { $pipeline[] = ['clave' => $st, 'total' => $crudo[$st] ?? 0]; }

        // ── Señales de demanda (desde touchpoints, parseo en PHP) ──────────
        $desafios = [];
        $soluciones = [];
        foreach ($pdo->query("SELECT type, payload FROM touchpoints WHERE type IN ('contacto','diagnostico')")->fetchAll() as $tp) {
            $p = json_decode($tp['payload'] ?? '', true) ?: [];
            if ($tp['type'] === 'contacto' && ! empty($p['desafio'])) {
                $desafios[$p['desafio']] = ($desafios[$p['desafio']] ?? 0) + 1;
            }
            if ($tp['type'] === 'diagnostico' && ! empty($p['resultado'])) {
                $soluciones[$p['resultado']] = ($soluciones[$p['resultado']] ?? 0) + 1;
            }
        }
        arsort($desafios);
        arsort($soluciones);

        // ── Rendimiento de contenido (descargas) ──────────────────────────
        $contenido = [];
        foreach (Database::run("SELECT titulo, downloads FROM resources ORDER BY downloads DESC LIMIT 8")->fetchAll() as $r) {
            $contenido[] = ['titulo' => tr($r['titulo'], 'es'), 'downloads' => (int) $r['downloads']];
        }

        Response::ok([
            'embudo' => $embudo,
            'tendencia' => $tendencia,
            'fuentes' => $fuentes,
            'canales' => $canales,
            'industrias' => $industrias,
            'tamanos' => $tamanos,
            'paises' => $paises,
            'pipeline' => $pipeline,
            'desafios' => $this->top($desafios, 6),
            'soluciones' => $this->top($soluciones, 6),
            'contenido' => $contenido,
            'generado' => now_utc(),
        ]);
    }

    /** Serie de 12 semanas (leads e interacciones) bucketizada en PHP. */
    private function serieSemanal(string $desde): array
    {
        $today = strtotime(gmdate('Y-m-d'));
        $bkLeads = array_fill(0, 12, 0);
        $bkInter = array_fill(0, 12, 0);

        foreach (Database::run("SELECT substr(created_at,1,10) d, COUNT(*) c FROM leads WHERE created_at >= ? GROUP BY d", [$desde])->fetchAll() as $r) {
            $w = intdiv((int) (($today - strtotime($r['d'])) / 86400), 7);
            if ($w >= 0 && $w < 12) { $bkLeads[11 - $w] += (int) $r['c']; }
        }
        foreach (Database::run("SELECT substr(created_at,1,10) d, COUNT(*) c FROM touchpoints WHERE created_at >= ? GROUP BY d", [$desde])->fetchAll() as $r) {
            $w = intdiv((int) (($today - strtotime($r['d'])) / 86400), 7);
            if ($w >= 0 && $w < 12) { $bkInter[11 - $w] += (int) $r['c']; }
        }

        $out = [];
        for ($b = 0; $b < 12; $b++) {
            $out[] = [
                'label' => gmdate('d/m', $today - (11 - $b) * 7 * 86400),
                'leads' => $bkLeads[$b],
                'interacciones' => $bkInter[$b],
            ];
        }
        return $out;
    }

    /** Agrupa leads por una columna, normalizando NULL/'' a una etiqueta. */
    private function grupo(string $col, string $vacio, int $limit = 12): array
    {
        $expr = "COALESCE(NULLIF({$col},''), " . Database::pdo()->quote($vacio) . ")";
        $rows = Database::pdo()->query("SELECT {$expr} k, COUNT(*) c FROM leads GROUP BY {$expr} ORDER BY c DESC")->fetchAll();
        $out = [];
        foreach (array_slice($rows, 0, $limit) as $r) {
            $out[] = ['clave' => $r['k'], 'total' => (int) $r['c']];
        }
        return $out;
    }

    /** Convierte un mapa clave=>conteo (ya ordenado) en lista top N. */
    private function top(array $map, int $n): array
    {
        $out = [];
        foreach (array_slice($map, 0, $n, true) as $k => $v) {
            $out[] = ['clave' => $k, 'total' => (int) $v];
        }
        return $out;
    }
}
