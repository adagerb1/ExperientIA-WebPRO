<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;

final class BookingsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req);
        $page = $this->req->page();
        $per = $this->req->perPage();
        $total = (int) Database::run('SELECT COUNT(*) FROM bookings')->fetchColumn();
        $items = Database::run(
            "SELECT b.*, l.name lead_name, l.company lead_company, l.email lead_email
             FROM bookings b JOIN leads l ON l.id = b.lead_id ORDER BY b.starts_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per)
        )->fetchAll();
        Response::paginated($items, $total, $page, $per);
    }

    public function update(string $id): void
    {
        AuthMiddleware::require($this->req);
        $estado = $this->req->input('status', '');
        if (! in_array($estado, ['confirmada', 'realizada', 'cancelada'], true)) { Response::error('Estado inválido.'); }
        Database::run('UPDATE bookings SET status = ? WHERE id = ?', [$estado, $id]);
        Response::ok(['message' => 'ok']);
    }
}
