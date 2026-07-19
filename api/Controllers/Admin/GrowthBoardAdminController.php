<?php
namespace Controllers\Admin;

use Controllers\PublicApi\ClientBoardController;
use Core\Controller;
use Core\Database;
use Core\Response;
use Core\Middleware\AuthMiddleware;

/**
 * GrowthBoard · seguimiento del acompañamiento (la herramienta del consultor).
 * Lista de clientes con diagnóstico, su tablero, las jugadas que define el
 * consultor y el enlace de acceso del cliente a Mi GrowthBoard.
 */
final class GrowthBoardAdminController extends Controller
{
    /** Clientes con diagnóstico: último resultado + conteos de jugadas/check-ins. */
    public function clientes(): void
    {
        AuthMiddleware::require($this->req);
        $rows = Database::run(
            'SELECT l.id, l.name, l.company, l.email, l.status, r.total, r.banda, r.zona_critica, r.created_at AS fecha,
                (SELECT COUNT(*) FROM gb_plays p WHERE p.lead_id = l.id) AS jugadas,
                (SELECT COUNT(*) FROM gb_plays p WHERE p.lead_id = l.id AND p.estado = \'ejecutada\') AS ejecutadas,
                (SELECT COUNT(*) FROM gb_checkins c WHERE c.lead_id = l.id) AS checkins
             FROM leads l
             JOIN gb_results r ON r.id = (SELECT MAX(r2.id) FROM gb_results r2 WHERE r2.lead_id = l.id)
             ORDER BY r.created_at DESC'
        )->fetchAll();
        Response::ok($rows);
    }

    /** Detalle: resultados (evolución), jugadas y check-ins de un cliente. */
    public function cliente(string $id): void
    {
        AuthMiddleware::require($this->req);
        $lead = Database::run('SELECT id, name, company, email, phone_wa, status, locale FROM leads WHERE id = ?', [$id])->fetch();
        if (! $lead) { Response::error('Cliente no encontrado.', 404); }
        $results = Database::run('SELECT id, total, banda, linea_debil, zona_critica, scores, contexto, created_at FROM gb_results WHERE lead_id = ? ORDER BY id DESC', [$id])->fetchAll();
        foreach ($results as &$r) { $r['scores'] = json_decode($r['scores'], true); $r['contexto'] = json_decode($r['contexto'] ?? 'null', true); $r['total'] = (float) $r['total']; }
        unset($r);
        Response::ok([
            'lead' => $lead,
            'results' => $results,
            'plays' => Database::run('SELECT * FROM gb_plays WHERE lead_id = ? ORDER BY sort, id', [$id])->fetchAll(),
            'checkins' => Database::run('SELECT * FROM gb_checkins WHERE lead_id = ? ORDER BY id DESC LIMIT 24', [$id])->fetchAll(),
        ]);
    }

    /** Enlace de acceso del cliente (para compartir en la lectura estratégica). */
    public function acceso(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $lead = Database::run('SELECT id, locale FROM leads WHERE id = ?', [$id])->fetch();
        if (! $lead) { Response::error('Cliente no encontrado.', 404); }
        Response::ok(['url' => ClientBoardController::urlAcceso((int) $lead['id'], $lead['locale'] ?: 'es')]);
    }

    /** Crea una jugada (la define el consultor con el cliente). */
    public function jugadaStore(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $d = $this->datos();
        if ($d['titulo'] === '') { Response::error('La jugada necesita un título.', 422); }
        Database::run('INSERT INTO gb_plays (lead_id, zona, titulo, porque, responsable, fecha_limite, indicador, estado, sort, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$id, $d['zona'], $d['titulo'], $d['porque'], $d['responsable'], $d['fecha_limite'], $d['indicador'], $d['estado'] ?: 'pendiente', $d['sort'], now_utc()]);
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function jugadaUpdate(string $id, string $pid): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $d = $this->datos();
        Database::run('UPDATE gb_plays SET zona = ?, titulo = ?, porque = ?, responsable = ?, fecha_limite = ?, indicador = ?, estado = ?, resultado = ?, sort = ?, updated_at = ? WHERE id = ? AND lead_id = ?',
            [$d['zona'], $d['titulo'], $d['porque'], $d['responsable'], $d['fecha_limite'], $d['indicador'], $d['estado'] ?: 'pendiente', $d['resultado'], $d['sort'], now_utc(), $pid, $id]);
        Response::ok(['message' => 'ok']);
    }

    public function jugadaDestroy(string $id, string $pid): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Database::run('DELETE FROM gb_plays WHERE id = ? AND lead_id = ?', [$pid, $id]);
        Response::ok(['message' => 'ok']);
    }

    private function datos(): array
    {
        $b = $this->req->body;
        $txt = fn ($k, $m) => mb_substr(trim(strip_tags((string) ($b[$k] ?? ''))), 0, $m) ?: null;
        $estado = (string) ($b['estado'] ?? 'pendiente');
        $resultado = (string) ($b['resultado'] ?? '');
        return [
            'zona' => $txt('zona', 40), 'titulo' => (string) $txt('titulo', 255) ?: '',
            'porque' => $txt('porque', 1000), 'responsable' => $txt('responsable', 120),
            'fecha_limite' => $txt('fecha_limite', 20), 'indicador' => $txt('indicador', 255),
            'estado' => in_array($estado, ['pendiente', 'ejecucion', 'ejecutada', 'descartada'], true) ? $estado : 'pendiente',
            'resultado' => in_array($resultado, ['movio', 'no_movio'], true) ? $resultado : null,
            'sort' => (int) ($b['sort'] ?? 0),
        ];
    }
}
