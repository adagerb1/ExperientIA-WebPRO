<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;

/**
 * Propuesta comercial confidencial (modelo de los proposals de ExperientIA):
 *   GET  /propuesta/{code}          → solo metadatos (cliente, estado) para pintar la compuerta
 *   POST /propuesta/{code}/acceso   → valida correo autorizado + NIT y entrega el contenido
 *   POST /propuesta/{code}/aceptar  → el cliente acepta la propuesta (queda trazado)
 * Cada apertura suma a la trazabilidad (vistas, última vista, timeline del lead y Telegram).
 */
final class ProposalController extends Controller
{
    public function meta(string $code): void
    {
        RateLimiter::public($this->req);
        $p = $this->porCodigo($code);
        Response::ok([
            'cliente' => $p['cliente'],
            'locale' => $p['locale'],
            'estado' => $p['estado'],
        ]);
    }

    public function acceso(string $code): void
    {
        RateLimiter::public($this->req);
        $p = $this->porCodigo($code);
        if ($p['estado'] === 'borrador') { Response::error('Esta propuesta aún no está disponible.', 403); }

        $email = strtolower(trim((string) $this->req->input('email', '')));
        $nit = preg_replace('/\D+/', '', (string) $this->req->input('nit', ''));
        $autorizados = array_map('strtolower', json_decode($p['auth_emails'] ?: '[]', true) ?: []);

        $emailOk = $email !== '' && (in_array($email, $autorizados, true) || $email === strtolower((string) $p['contact_email']));
        $nitOk = $p['nit'] === null || $p['nit'] === '' || $nit === $p['nit'];
        if (! $emailOk || ! $nitOk) {
            Response::error('Los datos no coinciden con los autorizados para esta propuesta. Verifica el correo y el NIT (solo números).', 403);
        }

        // Trazabilidad: apertura registrada + notificación interna.
        Database::run('UPDATE proposals SET views = views + 1, last_view = ? WHERE id = ?', [now_utc(), $p['id']]);
        $this->trazar($p, 'propuesta_vista', 'Propuesta abierta por ' . $email);

        Response::ok([
            'cliente' => $p['cliente'],
            'nit' => $p['nit'],
            'contact' => $p['contact'],
            'title' => $p['title'],
            'version' => $p['version'],
            'doc_date' => $p['doc_date'],
            'locale' => $p['locale'],
            'estado' => $p['estado'],
            'contenido' => json_decode($p['contenido'] ?: '{}', true) ?: new \stdClass(),
        ]);
    }

    public function aceptar(string $code): void
    {
        RateLimiter::public($this->req);
        $p = $this->porCodigo($code);
        if ($p['estado'] === 'aceptada') { Response::ok(['message' => 'ok', 'estado' => 'aceptada']); }
        $email = strtolower(trim((string) $this->req->input('email', '')));
        $autorizados = array_map('strtolower', json_decode($p['auth_emails'] ?: '[]', true) ?: []);
        if ($email === '' || (! in_array($email, $autorizados, true) && $email !== strtolower((string) $p['contact_email']))) {
            Response::error('Correo no autorizado.', 403);
        }
        Database::run("UPDATE proposals SET estado = 'aceptada', decided_at = ? WHERE id = ?", [now_utc(), $p['id']]);
        $this->trazar($p, 'propuesta_aceptada', '🎉 Propuesta ACEPTADA por ' . $email);
        // El lead pasa a cliente: cierre del ciclo comercial.
        if ($p['lead_id']) {
            try { Database::run("UPDATE leads SET status = 'cliente' WHERE id = ?", [$p['lead_id']]); } catch (\Throwable $e) { /* opcional */ }
        }
        Response::ok(['message' => 'ok', 'estado' => 'aceptada']);
    }

    private function porCodigo(string $code): array
    {
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $code);
        $p = Database::run('SELECT * FROM proposals WHERE code = ?', [$code])->fetch();
        if (! $p) { Response::error('Propuesta no encontrada.', 404); }
        return $p;
    }

    private function trazar(array $p, string $tipo, string $titulo): void
    {
        // Aviso interno inmediato (Telegram a los admins con la bandera activa).
        try {
            \Services\Notifier::telegram('📄 <b>' . htmlspecialchars($p['cliente'] ?: 'Propuesta') . '</b> · ' . htmlspecialchars($titulo));
        } catch (\Throwable $e) { /* opcional */ }
        // Trazabilidad en el timeline del lead (portal admin).
        if (! $p['lead_id']) { return; }
        try {
            Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                [$p['lead_id'], $tipo, $titulo, json_encode(['proposal_code' => $p['code']], JSON_UNESCAPED_UNICODE), now_utc()]);
        } catch (\Throwable $e) { /* trazabilidad opcional */ }
    }
}
