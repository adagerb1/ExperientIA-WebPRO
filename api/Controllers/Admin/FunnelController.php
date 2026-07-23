<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\RateLimiter;
use Core\Response;
use Core\Middleware\AuthMiddleware;

/**
 * Funnel comercial y costo por lead: la pauta se optimiza con números, no a ciegas.
 * Visitas (analítica propia, sin cookies) → leads → diagnósticos → reservas →
 * clientes, global y por campaña UTM, con gasto mensual editable y CPL/CPA.
 */
final class FunnelController extends Controller
{
    /** Beacon público de visita: cuenta agregada por día+ruta+idioma. Sin IP, sin cookies. */
    public function hit(): void
    {
        RateLimiter::hit('hit:' . $this->req->ip(), 120, 60);
        $path = substr(preg_replace('#[?\#].*$#', '', (string) $this->req->input('path', '/')), 0, 190) ?: '/';
        if (str_starts_with($path, '/admin')) { Response::ok(['message' => 'skip']); }
        $locale = in_array($l = (string) $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        $fecha = gmdate('Y-m-d');
        try {
            $pdo = Database::pdo();
            $sql = Database::isSqlite()
                ? 'INSERT INTO hits (fecha, path, locale, count) VALUES (?,?,?,1) ON CONFLICT(fecha, path, locale) DO UPDATE SET count = count + 1'
                : 'INSERT INTO hits (fecha, path, locale, count) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE count = count + 1';
            $pdo->prepare($sql)->execute([$fecha, $path, $locale]);
        } catch (\Throwable $e) { /* sin migrar: el beacon jamás rompe el sitio */ }
        Response::ok(['message' => 'ok']);
    }

    /** Resumen del funnel para el período (7/30/90 días) + desglose por campaña. */
    public function resumen(): void
    {
        AuthMiddleware::require($this->req);
        $dias = in_array($d = (int) $this->req->input('dias', 30), [7, 30, 90], true) ? $d : 30;
        $desde = gmdate('Y-m-d H:i:s', time() - $dias * 86400);
        $desdeFecha = substr($desde, 0, 10);
        $n = fn ($sql, $p = []) => (int) Database::run($sql, $p)->fetchColumn();

        $visitas = 0;
        try { $visitas = $n('SELECT COALESCE(SUM(count),0) FROM hits WHERE fecha >= ?', [$desdeFecha]); } catch (\Throwable $e) {}

        $funnel = [
            'visitas' => $visitas,
            'leads' => $n('SELECT COUNT(*) FROM leads WHERE created_at >= ?', [$desde]),
            'diagnosticos' => $n('SELECT COUNT(*) FROM gb_results WHERE created_at >= ?', [$desde]),
            'reservas' => $n('SELECT COUNT(*) FROM bookings WHERE created_at >= ?', [$desde]),
            'clientes' => $n("SELECT COUNT(*) FROM leads WHERE status = 'cliente' AND updated_at >= ?", [$desde]),
        ];

        // Leads por día (para la serie del gráfico)
        $porDia = Database::run(
            Database::isSqlite()
                ? "SELECT substr(created_at,1,10) d, COUNT(*) c FROM leads WHERE created_at >= ? GROUP BY substr(created_at,1,10) ORDER BY d"
                : 'SELECT DATE(created_at) d, COUNT(*) c FROM leads WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY d',
            [$desde]
        )->fetchAll();

        // Desglose por campaña UTM: leads, diagnósticos, reservas, clientes.
        $campanas = Database::run(
            "SELECT COALESCE(NULLIF(l.utm_campaign,''),'(directo)') campaign,
                COALESCE(NULLIF(l.utm_source,''),'—') source,
                COUNT(*) leads,
                SUM(CASE WHEN EXISTS (SELECT 1 FROM gb_results r WHERE r.lead_id = l.id) THEN 1 ELSE 0 END) diagnosticos,
                SUM(CASE WHEN EXISTS (SELECT 1 FROM bookings b WHERE b.lead_id = l.id) THEN 1 ELSE 0 END) reservas,
                SUM(CASE WHEN l.status = 'cliente' THEN 1 ELSE 0 END) clientes
             FROM leads l WHERE l.created_at >= ?
             GROUP BY COALESCE(NULLIF(l.utm_campaign,''),'(directo)'), COALESCE(NULLIF(l.utm_source,''),'—')
             ORDER BY leads DESC", [$desde]
        )->fetchAll();

        // Gasto del período (suma de los meses tocados por el rango) por campaña.
        $gasto = [];
        try {
            $mesDesde = substr($desdeFecha, 0, 7);
            foreach (Database::run('SELECT campaign, SUM(monto) monto FROM ad_spend WHERE mes >= ? GROUP BY campaign', [$mesDesde])->fetchAll() as $g) {
                $gasto[$g['campaign']] = (float) $g['monto'];
            }
        } catch (\Throwable $e) {}
        foreach ($campanas as &$c) {
            $c['gasto'] = $gasto[$c['campaign']] ?? 0;
            $c['cpl'] = $c['gasto'] > 0 && $c['leads'] > 0 ? round($c['gasto'] / $c['leads'], 2) : null;
            $c['cpa'] = $c['gasto'] > 0 && $c['clientes'] > 0 ? round($c['gasto'] / $c['clientes'], 2) : null;
        }
        unset($c);

        Response::ok(['dias' => $dias, 'funnel' => $funnel, 'por_dia' => $porDia, 'campanas' => $campanas, 'mes_actual' => gmdate('Y-m')]);
    }

    /** Registra/actualiza el gasto de una campaña en un mes (upsert). */
    public function gasto(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $mes = (string) $this->req->input('mes', gmdate('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $mes)) { Response::error('Mes inválido (AAAA-MM).', 422); }
        $campaign = mb_substr(trim((string) $this->req->input('campaign', '')), 0, 160);
        if ($campaign === '') { Response::error('Indica la campaña.', 422); }
        $source = mb_substr(trim((string) $this->req->input('source', '')), 0, 120) ?: null;
        $monto = round((float) $this->req->input('monto', 0), 2);

        $sql = Database::isSqlite()
            ? 'INSERT INTO ad_spend (mes, campaign, source, monto, updated_at) VALUES (?,?,?,?,?) ON CONFLICT(mes, campaign) DO UPDATE SET monto = excluded.monto, source = excluded.source, updated_at = excluded.updated_at'
            : 'INSERT INTO ad_spend (mes, campaign, source, monto, updated_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE monto = VALUES(monto), source = VALUES(source), updated_at = VALUES(updated_at)';
        Database::pdo()->prepare($sql)->execute([$mes, $campaign, $source, $monto, now_utc()]);
        Response::ok(['message' => 'ok']);
    }
}
