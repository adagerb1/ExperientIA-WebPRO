<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Validator;
use Core\Middleware\AuthMiddleware;

final class LeadsController extends Controller
{
    public function index(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        [$where, $params] = $this->filtros();
        $page = $this->req->page();
        $per = $this->req->perPage();
        $total = (int) Database::run("SELECT COUNT(*) FROM leads l {$where}", $params)->fetchColumn();
        // Ordenamiento seguro (whitelist de columnas)
        $cols = ['name', 'company', 'email', 'industry', 'status', 'touchpoints', 'created_at', 'updated_at'];
        $sort = in_array($this->req->input('sort', ''), $cols, true) ? $this->req->input('sort') : 'updated_at';
        $dir = strtolower((string) $this->req->input('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderCol = $sort === 'touchpoints' ? 'touchpoints' : "l.{$sort}";
        $sql = "SELECT l.*, (SELECT COUNT(*) FROM touchpoints t WHERE t.lead_id = l.id) touchpoints
                FROM leads l {$where} ORDER BY {$orderCol} {$dir} LIMIT {$per} OFFSET " . (($page - 1) * $per);
        $items = Database::run($sql, $params)->fetchAll();
        Response::paginated($items, $total, $page, $per);
    }

    public function show(string $id): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);
        $lead = Database::run('SELECT * FROM leads WHERE id = ?', [$id])->fetch();
        if (! $lead) { Response::error('Lead no encontrado.', 404); }
        Response::ok([
            'lead' => $lead,
            'touchpoints' => Database::run('SELECT * FROM touchpoints WHERE lead_id = ? ORDER BY created_at DESC', [$id])->fetchAll(),
            'reservas' => Database::run('SELECT * FROM bookings WHERE lead_id = ? ORDER BY starts_at DESC', [$id])->fetchAll(),
        ]);
    }

    public function update(string $id): void
    {
        $admin = AuthMiddleware::require($this->req);
        $lead = Database::run('SELECT * FROM leads WHERE id = ?', [$id])->fetch();
        if (! $lead) { Response::error('Lead no encontrado.', 404); }

        $campos = [];
        $params = [];
        $nuevoEstado = null;
        if (isset($this->req->body['status']) && isset(biz('lead_statuses')[$this->req->body['status']])) {
            $nuevoEstado = $this->req->body['status'];
            $campos[] = 'status = ?'; $params[] = $nuevoEstado;
        }
        if (array_key_exists('notes', $this->req->body)) {
            $campos[] = 'notes = ?'; $params[] = mb_substr(strip_tags((string) $this->req->body['notes']), 0, 5000);
        }
        if (! $campos) { Response::error('Nada que actualizar.'); }
        $params[] = now_utc(); $params[] = $id;
        Database::run('UPDATE leads SET ' . implode(',', $campos) . ', updated_at = ? WHERE id = ?', $params);

        // Registra el cambio de estado como touchpoint (auditoría del pipeline).
        if ($nuevoEstado !== null && $nuevoEstado !== $lead['status']) {
            $de = biz('lead_statuses')[$lead['status']] ?? $lead['status'];
            $a = biz('lead_statuses')[$nuevoEstado] ?? $nuevoEstado;
            Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                [$id, 'estado', "Estado: {$de} → {$a}", json_encode(['por' => $admin['name'] ?? 'admin'], JSON_UNESCAPED_UNICODE), now_utc()]);
            // Inscribe en las secuencias de nurturing de la nueva etapa.
            \Services\SequenceService::enroll((int) $id, $nuevoEstado);
        }
        Response::ok(['message' => 'ok']);
    }

    /** Pipeline: leads agrupados por estado (para el tablero Kanban). */
    public function pipeline(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);
        $cols = [];
        foreach (array_keys(biz('lead_statuses')) as $st) {
            $total = (int) Database::run('SELECT COUNT(*) FROM leads WHERE status = ?', [$st])->fetchColumn();
            $items = Database::run('SELECT id, name, company, email, phone_wa, country, industry, source, channel, utm_campaign, updated_at FROM leads WHERE status = ? ORDER BY updated_at DESC LIMIT 50', [$st])->fetchAll();
            $cols[] = ['estado' => $st, 'label' => biz('lead_statuses')[$st], 'total' => $total, 'items' => $items];
        }
        Response::ok(['columnas' => $cols]);
    }

    /** AlexIA sugiere el siguiente paso para avanzar el lead. */
    public function sugerencia(string $id): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);
        $lead = Database::run('SELECT * FROM leads WHERE id = ?', [$id])->fetch();
        if (! $lead) { Response::error('Lead no encontrado.', 404); }
        $tps = Database::run('SELECT type, title, created_at FROM touchpoints WHERE lead_id = ? ORDER BY created_at DESC LIMIT 4', [$id])->fetchAll();

        $estado = biz('lead_statuses')[$lead['status']] ?? $lead['status'];
        $ind = biz('industries')[$lead['industry'] ?? ''] ?? ($lead['industry'] ?? '—');
        $resumen = "Lead: {$lead['name']} · Empresa: " . ($lead['company'] ?: '—') . " · Industria: {$ind} · País: " . ($lead['country'] ?: '—')
            . " · Estado actual: {$estado} · Origen: " . ($lead['source'] ?: '—') . ($lead['utm_campaign'] ? " · Campaña: {$lead['utm_campaign']}" : '')
            . ' · Contacto: ' . ($lead['email'] ? 'correo' : '') . ($lead['phone_wa'] ? ' WhatsApp' : '');
        $hist = '';
        foreach ($tps as $t) { $hist .= "\n- {$t['title']}"; }

        try {
            $instr = 'Eres AlexIA, asesora comercial de ExperientIA (firma C-Level de automatización, datos, IA y growth). '
                . 'Sugiere el SIGUIENTE PASO concreto para avanzar este lead en el pipeline comercial. '
                . 'Responde en español, 1 a 2 frases accionables, sin rodeos ni saludos, orientado a agendar o cerrar. No inventes datos del lead.';
            $sug = \Services\AlexIA::ask($instr, $resumen . ($hist ? "\nHistorial:{$hist}" : ''));
            Response::ok(['sugerencia' => trim($sug), 'fuente' => 'alexia']);
        } catch (\Throwable $e) {
            $fb = [
                'nuevo' => 'Contáctalo en menos de 24 h por WhatsApp o correo y propón un diagnóstico ejecutivo de 30 min.',
                'contactado' => 'Confirma el dolor principal y agenda una sesión 1:1 para calificar presupuesto y decisión.',
                'calificado' => 'Envía una propuesta concreta con alcance, entregables y retorno esperado; fija fecha de revisión.',
                'propuesta' => 'Haz seguimiento a la propuesta, resuelve objeciones y propón fecha de inicio para cerrar.',
                'cliente' => 'Activa el onboarding, define métricas de éxito y pide un referido o caso de éxito.',
                'descartado' => 'Reactiva con contenido de valor (recurso o caso) y revisa en 60-90 días.',
            ];
            Response::ok(['sugerencia' => $fb[$lead['status']] ?? 'Da el siguiente paso comercial según el contexto del lead.', 'fuente' => 'heuristica']);
        }
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Database::run('DELETE FROM leads WHERE id = ?', [$id]);  // CASCADE limpia touchpoints/bookings
        Response::ok(['message' => 'ok']);
    }

    /** Exportación CSV nativa (; + BOM UTF-8 para Excel español). */
    public function export(): void
    {
        AuthMiddleware::require($this->req);
        [$where, $params] = $this->filtros();
        $rows = Database::run("SELECT name,email,phone_wa,country,company,role,industry,company_size,status,source,channel,created_at FROM leads l {$where} ORDER BY created_at DESC", $params)->fetchAll();
        Response::ok(['rows' => $rows]);   // el frontend arma el CSV con ; + BOM
    }

    private function filtros(): array
    {
        $where = [];
        $params = [];
        if (! empty($this->req->query['status'])) { $where[] = 'l.status = ?'; $params[] = $this->req->query['status']; }
        if (! empty($this->req->query['channel'])) { $where[] = 'l.channel = ?'; $params[] = $this->req->query['channel']; }
        if (! empty($this->req->query['q'])) {
            $where[] = '(l.name LIKE ? OR l.email LIKE ? OR l.company LIKE ?)';
            $t = '%' . $this->req->query['q'] . '%';
            array_push($params, $t, $t, $t);
        }
        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
