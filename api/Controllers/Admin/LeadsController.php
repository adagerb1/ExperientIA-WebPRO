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
        $sql = "SELECT l.*, (SELECT COUNT(*) FROM touchpoints t WHERE t.lead_id = l.id) touchpoints
                FROM leads l {$where} ORDER BY l.updated_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per);
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
        $campos = [];
        $params = [];
        if (isset($this->req->body['status']) && isset(biz('lead_statuses')[$this->req->body['status']])) {
            $campos[] = 'status = ?'; $params[] = $this->req->body['status'];
        }
        if (array_key_exists('notes', $this->req->body)) {
            $campos[] = 'notes = ?'; $params[] = mb_substr(strip_tags((string) $this->req->body['notes']), 0, 5000);
        }
        if (! $campos) { Response::error('Nada que actualizar.'); }
        $params[] = now_utc(); $params[] = $id;
        Database::run('UPDATE leads SET ' . implode(',', $campos) . ', updated_at = ? WHERE id = ?', $params);
        Response::ok(['message' => 'ok']);
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
