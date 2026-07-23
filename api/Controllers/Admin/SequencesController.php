<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\SequenceService;

/** Automatizaciones: secuencias de nurturing por etapa del pipeline y su runner. */
final class SequencesController extends Controller
{
    public function index(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        $seqs = Database::run('SELECT * FROM sequences ORDER BY id DESC')->fetchAll();
        foreach ($seqs as &$s) {
            $s['steps'] = Database::run('SELECT id, orden, delay_hours, template_id FROM sequence_steps WHERE sequence_id = ? ORDER BY orden, id', [$s['id']])->fetchAll();
            $s['activas'] = (int) Database::run("SELECT COUNT(*) FROM sequence_enrollments WHERE sequence_id = ? AND status = 'activa'", [$s['id']])->fetchColumn();
            $s['total'] = (int) Database::run('SELECT COUNT(*) FROM sequence_enrollments WHERE sequence_id = ?', [$s['id']])->fetchColumn();
        }
        unset($s);
        $templates = Database::run('SELECT id, nombre, canal FROM campaign_templates WHERE active = 1 ORDER BY nombre')->fetchAll();
        Response::ok(['sequences' => $seqs, 'templates' => $templates, 'estados' => biz('lead_statuses')]);
    }

    public function save(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $b = $this->req->body;
        $nombre = trim((string) ($b['nombre'] ?? ''));
        $trigger = (string) ($b['trigger_status'] ?? '');
        if ($nombre === '') { Response::error('El nombre es obligatorio.', 422, ['campos' => ['nombre']]); }
        if (! isset(biz('lead_statuses')[$trigger])) { Response::error('Etapa disparadora inválida.', 422, ['campos' => ['trigger_status']]); }
        $active = ! empty($b['active']) ? 1 : 0;
        $steps = is_array($b['steps'] ?? null) ? $b['steps'] : [];

        $id = (int) ($b['id'] ?? 0);
        if ($id > 0) {
            Database::run('UPDATE sequences SET nombre = ?, trigger_status = ?, active = ? WHERE id = ?', [$nombre, $trigger, $active, $id]);
            Database::run('DELETE FROM sequence_steps WHERE sequence_id = ?', [$id]);
        } else {
            Database::run('INSERT INTO sequences (nombre, trigger_status, active, created_at) VALUES (?,?,?,?)', [$nombre, $trigger, $active, now_utc()]);
            $id = (int) Database::pdo()->lastInsertId();
        }
        $orden = 0;
        foreach ($steps as $st) {
            $tid = (int) ($st['template_id'] ?? 0);
            if ($tid <= 0) { continue; }
            Database::run('INSERT INTO sequence_steps (sequence_id, orden, delay_hours, template_id, active) VALUES (?,?,?,?,1)',
                [$id, $orden++, max(0, (int) ($st['delay_hours'] ?? 0)), $tid]);
        }
        Response::ok(['id' => $id, 'message' => 'ok']);
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Database::run('DELETE FROM sequences WHERE id = ?', [$id]); // CASCADE limpia pasos e inscripciones
        Response::ok(['message' => 'ok']);
    }

    /** Corre el runner manualmente ("procesar ahora"). */
    public function procesar(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        Response::ok(SequenceService::processDue(200));
    }
}
