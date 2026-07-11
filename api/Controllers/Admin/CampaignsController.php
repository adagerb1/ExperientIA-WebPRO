<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;

/**
 * Panel de campañas: atribución de leads por UTM (campaña, fuente, medio) y
 * landing de origen, con conversión a cliente. Base para medir la pauta y
 * decidir dónde invertir. SQL portable MySQL/SQLite.
 */
final class CampaignsController extends Controller
{
    public function panel(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        $pdo = Database::pdo();
        $q = fn (string $sql, array $p = []) => (int) Database::run($sql, $p)->fetchColumn();

        $total = $q('SELECT COUNT(*) FROM leads');
        $atribuidos = $q("SELECT COUNT(*) FROM leads WHERE utm_source IS NOT NULL AND utm_source <> ''");
        $clientes = $q("SELECT COUNT(*) FROM leads WHERE status = 'cliente'");
        $campanas = $q("SELECT COUNT(DISTINCT utm_campaign) FROM leads WHERE utm_campaign IS NOT NULL AND utm_campaign <> ''");
        $resumen = [
            'total' => $total,
            'atribuidos' => $atribuidos,
            'directos' => $total - $atribuidos,
            'clientes' => $clientes,
            'campanas' => $campanas,
            'tasa_atribucion' => $total > 0 ? round($atribuidos / $total * 100, 1) : 0.0,
        ];

        Response::ok([
            'resumen' => $resumen,
            'campanas' => $this->porCampana(),
            'fuentes' => $this->grupo('utm_source', '(directo)'),
            'medios' => $this->grupo('utm_medium', '(sin medio)'),
            'landings' => $this->grupo('landing_page', '(desconocida)', 10),
            'generado' => now_utc(),
        ]);
    }

    /** Filas de campaña: combinación campaña × fuente × medio con leads y conversión. */
    private function porCampana(): array
    {
        $campaign = "COALESCE(NULLIF(utm_campaign,''),'(directo)')";
        $source = "COALESCE(NULLIF(utm_source,''),'(directo)')";
        $medium = "COALESCE(NULLIF(utm_medium,''),'—')";
        $sql = "SELECT {$campaign} campaign, {$source} source, {$medium} medium,
                       COUNT(*) leads,
                       SUM(CASE WHEN status='cliente' THEN 1 ELSE 0 END) clientes,
                       SUM(CASE WHEN status IN ('calificado','propuesta','cliente') THEN 1 ELSE 0 END) calificados
                FROM leads GROUP BY {$campaign}, {$source}, {$medium} ORDER BY leads DESC";
        $out = [];
        $i = 0;
        foreach (Database::pdo()->query($sql)->fetchAll() as $r) {
            $leads = (int) $r['leads'];
            $cli = (int) $r['clientes'];
            $out[] = [
                'id' => ++$i,
                'campaign' => $r['campaign'],
                'source' => $r['source'],
                'medium' => $r['medium'],
                'leads' => $leads,
                'calificados' => (int) $r['calificados'],
                'clientes' => $cli,
                'tasa' => $leads > 0 ? round($cli / $leads * 100, 1) : 0.0,
            ];
        }
        return $out;
    }

    /** Agrupa leads por una columna UTM/landing, normalizando vacíos. */
    private function grupo(string $col, string $vacio, int $limit = 12): array
    {
        $expr = "COALESCE(NULLIF({$col},''), " . Database::pdo()->quote($vacio) . ")";
        $rows = Database::pdo()->query("SELECT {$expr} k, COUNT(*) c,
            SUM(CASE WHEN status='cliente' THEN 1 ELSE 0 END) cli
            FROM leads GROUP BY {$expr} ORDER BY c DESC")->fetchAll();
        $out = [];
        foreach (array_slice($rows, 0, $limit) as $r) {
            $out[] = ['clave' => $r['k'], 'total' => (int) $r['c'], 'clientes' => (int) $r['cli']];
        }
        return $out;
    }
}
