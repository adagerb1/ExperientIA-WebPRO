<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;

final class DashboardController extends Controller
{
    public function resumen(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);
        $q = fn (string $sql, array $p = []) => (int) Database::run($sql, $p)->fetchColumn();

        // Distribución por estado (para gráfica)
        $porEstado = [];
        foreach (Database::pdo()->query("SELECT status, COUNT(*) c FROM leads GROUP BY status")->fetchAll() as $r) {
            $porEstado[$r['status']] = (int) $r['c'];
        }
        // Leads por día (14 días) para tendencia
        $tendencia = Database::pdo()->query(
            "SELECT substr(created_at,1,10) d, COUNT(*) c FROM leads WHERE created_at >= '" . gmdate('Y-m-d', time() - 13 * 86400) . "' GROUP BY d ORDER BY d"
        )->fetchAll();

        Response::ok([
            'kpis' => [
                'leads' => $q('SELECT COUNT(*) FROM leads'),
                'leads_nuevos' => $q("SELECT COUNT(*) FROM leads WHERE status = 'nuevo'"),
                'interacciones' => $q('SELECT COUNT(*) FROM touchpoints'),
                'reservas_proximas' => $q("SELECT COUNT(*) FROM bookings WHERE status = 'confirmada' AND starts_at >= ?", [now_utc()]),
                'descargas' => $q('SELECT COALESCE(SUM(downloads),0) FROM resources'),
                'conversaciones_ia' => $q('SELECT COUNT(*) FROM ai_conversations'),
            ],
            'por_estado' => $porEstado,
            'tendencia' => $tendencia,
            'ultimos' => Database::run('SELECT id, name, company, status, source, created_at FROM leads ORDER BY created_at DESC LIMIT 8')->fetchAll(),
        ]);
    }
}
