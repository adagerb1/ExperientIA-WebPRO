<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;
use Services\Connectors\GmailConnector;

/**
 * Buzón comercial en el panel: los correos leídos del Google Workspace con el
 * triage de AlexIA (clase, resumen, acción, borrador). Desde aquí se da trámite:
 * regenerar/editar la respuesta, enviarla en el mismo hilo con la identidad
 * corporativa y marcar como tramitado. Todo queda trazado (email + timeline).
 */
final class MailboxController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = Database::run(
            'SELECT e.*, l.name lead_name, l.status lead_status FROM emails e
             LEFT JOIN leads l ON l.id = e.lead_id ORDER BY e.received_at DESC LIMIT 120'
        )->fetchAll();
        $res = ['total' => count($rows),
            'nuevos' => count(array_filter($rows, fn ($x) => $x['estado'] === 'nuevo')),
            'clientes' => count(array_filter($rows, fn ($x) => in_array($x['ai_clase'], ['cliente', 'prospecto'], true)))];
        Response::ok(['items' => $rows, 'resumen' => $res, 'conectado' => GmailConnector::isReady()]);
    }

    public function sync(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Response::ok(\Services\MailboxService::sync());
    }

    /** (Re)genera el triage/borrador de AlexIA para un correo puntual. */
    public function sugerir(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $e = $this->porId($id);
        if (! \Services\MailboxService::triage($e)) { Response::error('AlexIA no está disponible en este momento.', 503); }
        $e = $this->porId($id);
        Response::ok(['ai_clase' => $e['ai_clase'], 'ai_resumen' => $e['ai_resumen'], 'ai_accion' => $e['ai_accion'], 'ai_respuesta' => $e['ai_respuesta']]);
    }

    /** Envía la respuesta (editada o sugerida) en el mismo hilo, con identidad corporativa. */
    public function responder(string $id): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        $e = $this->porId($id);
        $texto = trim((string) $this->req->input('respuesta', $e['ai_respuesta'] ?? ''));
        if ($texto === '') { Response::error('Escribe o genera la respuesta antes de enviar.', 422); }

        $html = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
        $subject = str_starts_with(strtolower((string) $e['subject']), 're:') ? $e['subject'] : 'Re: ' . $e['subject'];
        $r = GmailConnector::send($e['from_email'], $subject, '<p style="font-size:15px;line-height:1.6;color:#22314f">' . $html . '</p>',
            ['in_reply_to' => $e['message_id'], 'thread_id' => $e['thread_id']]);
        if (empty($r['ok'])) { Response::error($r['error'] ?? 'No se pudo enviar por Gmail.', 502); }

        Database::run("UPDATE emails SET estado = 'tramitado', ai_respuesta = ?, replied_at = ? WHERE id = ?", [$texto, now_utc(), $e['id']]);
        if ($e['lead_id']) {
            try {
                Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                    [$e['lead_id'], 'email_respondido', 'Respuesta enviada: ' . mb_substr($subject, 0, 170),
                     json_encode(['por' => $admin['email'] ?? 'admin'], JSON_UNESCAPED_UNICODE), now_utc()]);
            } catch (\Throwable $ex) { /* opcional */ }
        }
        Response::ok(['message' => 'Respuesta enviada en el hilo con tu identidad corporativa.']);
    }

    /** Marca tramitado sin responder (p. ej. se gestionó por otro canal). */
    public function tramitar(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $e = $this->porId($id);
        Database::run("UPDATE emails SET estado = 'tramitado' WHERE id = ?", [$e['id']]);
        Response::ok(['message' => 'ok']);
    }

    private function porId(string $id): array
    {
        $e = Database::run('SELECT * FROM emails WHERE id = ?', [$id])->fetch();
        if (! $e) { Response::error('Correo no encontrado.', 404); }
        return $e;
    }
}
