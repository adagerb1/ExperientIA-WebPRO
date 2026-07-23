<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Env;
use Core\Middleware\AuthMiddleware;
use Services\Mailer;

/**
 * Propuestas comerciales desde el panel: crear (opcionalmente ligada a un lead),
 * editar secciones, generar con AlexIA, enviar el mailing de marca con el enlace
 * confidencial y seguir la trazabilidad (envíos, aperturas, aceptación).
 */
final class ProposalsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = Database::run(
            'SELECT p.*, l.name lead_name, l.company lead_company FROM proposals p
             LEFT JOIN leads l ON l.id = p.lead_id ORDER BY p.created_at DESC LIMIT 200'
        )->fetchAll();
        $base = rtrim(Env::get('APP_URL', ''), '/');
        foreach ($rows as &$r) {
            $r['contenido'] = json_decode($r['contenido'] ?: '{}', true) ?: new \stdClass();
            $r['auth_emails'] = json_decode($r['auth_emails'] ?: '[]', true) ?: [];
            $r['url'] = $base . '/' . ($r['locale'] ?: 'es') . '/' . self::slugPropuesta($r['locale']) . '/' . $r['code'];
        }
        unset($r);
        $res = ['total' => count($rows)];
        foreach (['borrador', 'enviada', 'aceptada'] as $e) { $res[$e] = count(array_filter($rows, fn ($x) => $x['estado'] === $e)); }
        Response::ok(['items' => $rows, 'resumen' => $res]);
    }

    public function store(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $cliente = trim((string) $this->req->input('cliente', ''));
        if ($cliente === '') { Response::error('Escribe el nombre del cliente o empresa.', 422); }
        $leadId = (int) $this->req->input('lead_id', 0) ?: null;

        $abc = 'abcdefghjkmnpqrstuvwxyz23456789';
        do {
            $code = 'p';
            for ($i = 0; $i < 9; $i++) { $code .= $abc[random_int(0, strlen($abc) - 1)]; }
            $dup = Database::run('SELECT COUNT(*) FROM proposals WHERE code = ?', [$code])->fetchColumn();
        } while ((int) $dup > 0);

        $emails = array_values(array_filter(array_map(
            fn ($e) => strtolower(trim((string) $e)),
            (array) $this->req->input('auth_emails', [])
        ), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        Database::run(
            'INSERT INTO proposals (code, lead_id, cliente, nit, contact, contact_email, auth_emails, locale, version, doc_date, contenido, estado, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$code, $leadId, $cliente,
             preg_replace('/\D+/', '', (string) $this->req->input('nit', '')) ?: null,
             trim((string) $this->req->input('contact', '')) ?: null,
             strtolower(trim((string) $this->req->input('contact_email', ''))) ?: null,
             json_encode($emails, JSON_UNESCAPED_UNICODE),
             in_array($this->req->input('locale'), ['es', 'en', 'pt'], true) ? $this->req->input('locale') : 'es',
             'Versión 1.0', trim((string) $this->req->input('doc_date', '')) ?: self::fechaDoc((string) ($this->req->input('locale') ?: 'es')),
             json_encode(new \stdClass()), 'borrador', now_utc()]
        );
        Response::ok(['message' => 'ok', 'code' => $code]);
    }

    public function update(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $p = $this->porId($id);
        $sets = []; $vals = [];
        foreach (['title', 'cliente', 'contact', 'contact_email', 'version', 'doc_date'] as $c) {
            $v = $this->req->input($c, null);
            if ($v !== null) { $sets[] = "$c = ?"; $vals[] = trim((string) $v); }
        }
        $nit = $this->req->input('nit', null);
        if ($nit !== null) { $sets[] = 'nit = ?'; $vals[] = preg_replace('/\D+/', '', (string) $nit) ?: null; }
        $emails = $this->req->input('auth_emails', null);
        if ($emails !== null) {
            $emails = array_values(array_filter(array_map(fn ($e) => strtolower(trim((string) $e)), (array) $emails), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
            $sets[] = 'auth_emails = ?'; $vals[] = json_encode($emails, JSON_UNESCAPED_UNICODE);
        }
        $cont = $this->req->input('contenido', null);
        if ($cont !== null) { $sets[] = 'contenido = ?'; $vals[] = json_encode($cont, JSON_UNESCAPED_UNICODE); }
        $estado = $this->req->input('estado', null);
        if ($estado !== null && in_array($estado, ['borrador', 'enviada', 'aceptada', 'rechazada'], true)) {
            $sets[] = 'estado = ?'; $vals[] = $estado;
            if (in_array($estado, ['aceptada', 'rechazada'], true)) { $sets[] = 'decided_at = ?'; $vals[] = now_utc(); }
        }
        if (! $sets) { Response::ok(['message' => 'sin cambios']); }
        $sets[] = 'updated_at = ?'; $vals[] = now_utc();
        $vals[] = $p['id'];
        Database::run('UPDATE proposals SET ' . implode(', ', $sets) . ' WHERE id = ?', $vals);
        Response::ok(['message' => 'ok']);
    }

    /** AlexIA genera el borrador de la propuesta (ficha del lead + brief). */
    public function generar(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $p = $this->porId($id);
        $lead = $p['lead_id'] ? (Database::run('SELECT * FROM leads WHERE id = ?', [$p['lead_id']])->fetch() ?: null) : null;
        $brief = trim((string) $this->req->input('brief', ''));
        if ($lead === null && $brief === '') { Response::error('Sin lead asociado necesitas un brief: cuéntale a AlexIA qué vas a proponer.', 422); }
        try {
            $doc = \Services\ProposalGenerator::generar($lead, $brief, $p['locale'] ?: 'es');
        } catch (\Throwable $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? (int) $e->getCode() : 503;
            Response::error('AlexIA no pudo generar la propuesta: ' . $e->getMessage(), $code);
        }
        Response::ok(['contenido' => $doc]);
    }

    /** Envía el mailing de marca con el enlace confidencial y marca 'enviada'. */
    public function enviar(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $p = $this->porId($id);
        $cont = json_decode($p['contenido'] ?: '{}', true) ?: [];
        if (empty($cont['title']) && empty($p['title'])) { Response::error('La propuesta aún no tiene contenido. Génerala o edítala antes de enviar.', 422); }
        $to = strtolower(trim((string) ($this->req->input('to', '') ?: $p['contact_email'] ?: '')));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) { Response::error('Define el correo del destinatario (contacto de la propuesta).', 422); }

        $base = rtrim(Env::get('APP_URL', ''), '/');
        $loc = $p['locale'] ?: 'es';
        $url = $base . '/' . $loc . '/' . self::slugPropuesta($loc) . '/' . $p['code'];
        $titulo = $cont['title'] ?? $p['title'] ?? 'Propuesta comercial';
        $nombre = $p['contact'] ?: $p['cliente'];

        $subject = ['es' => 'Propuesta para ' . $p['cliente'] . ' · ExperientIA', 'en' => 'Proposal for ' . $p['cliente'] . ' · ExperientIA', 'pt' => 'Proposta para ' . $p['cliente'] . ' · ExperientIA'][$loc];
        $intro = ['es' => '<p>Hola, ' . htmlspecialchars($nombre) . '.</p><p>Preparamos una propuesta pensada específicamente para ' . htmlspecialchars($p['cliente']) . ': <b>' . htmlspecialchars($titulo) . '</b>.</p><p>Es un documento confidencial en línea. Para abrirlo, usa tu correo corporativo' . ($p['nit'] ? ' y el NIT de la empresa (solo números)' : '') . '.</p>',
            'en' => '<p>Hi, ' . htmlspecialchars($nombre) . '.</p><p>We prepared a proposal specifically for ' . htmlspecialchars($p['cliente']) . ': <b>' . htmlspecialchars($titulo) . '</b>.</p><p>It is a confidential online document. To open it, use your corporate email' . ($p['nit'] ? ' and the company tax ID (numbers only)' : '') . '.</p>',
            'pt' => '<p>Olá, ' . htmlspecialchars($nombre) . '.</p><p>Preparamos uma proposta pensada especificamente para ' . htmlspecialchars($p['cliente']) . ': <b>' . htmlspecialchars($titulo) . '</b>.</p><p>É um documento confidencial on-line. Para abri-lo, use seu e-mail corporativo' . ($p['nit'] ? ' e o CNPJ/NIT da empresa (somente números)' : '') . '.</p>'][$loc];
        $btn = ['es' => 'Ver la propuesta', 'en' => 'View the proposal', 'pt' => 'Ver a proposta'][$loc];

        $ok = Mailer::send($to, $subject, $intro . Mailer::boton($url, $btn)
            . '<p style="font-size:12px;color:#8ea0bd">' . ['es' => 'Documento confidencial · uso exclusivo de ' . htmlspecialchars($p['cliente']), 'en' => 'Confidential document · exclusive use of ' . htmlspecialchars($p['cliente']), 'pt' => 'Documento confidencial · uso exclusivo de ' . htmlspecialchars($p['cliente'])][$loc] . '</p>');
        if (! $ok) { Response::error('No se pudo enviar el correo. Revisa el conector SendGrid.', 502); }

        Database::run("UPDATE proposals SET estado = CASE WHEN estado = 'borrador' THEN 'enviada' ELSE estado END, sent_at = ? WHERE id = ?", [now_utc(), $p['id']]);
        if ($p['lead_id']) {
            try {
                Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                    [$p['lead_id'], 'propuesta_enviada', 'Propuesta enviada a ' . $to, json_encode(['proposal_code' => $p['code']], JSON_UNESCAPED_UNICODE), now_utc()]);
                Database::run("UPDATE leads SET status = CASE WHEN status IN ('nuevo','contactado','calificado') THEN 'propuesta' ELSE status END WHERE id = ?", [$p['lead_id']]);
            } catch (\Throwable $e) { /* opcional */ }
        }
        Response::ok(['message' => 'Propuesta enviada a ' . $to]);
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->porId($id);
        Database::run('DELETE FROM proposals WHERE id = ?', [$id]);
        Response::ok(['message' => 'ok']);
    }

    private function porId(string $id): array
    {
        $p = Database::run('SELECT * FROM proposals WHERE id = ?', [$id])->fetch();
        if (! $p) { Response::error('Propuesta no encontrada.', 404); }
        return $p;
    }

    public static function slugPropuesta(?string $loc): string
    {
        return ['es' => 'propuesta', 'en' => 'proposal', 'pt' => 'proposta'][$loc ?? 'es'] ?? 'propuesta';
    }

    /** Fecha del documento en el idioma de la propuesta (p. ej. "Julio 2026"). */
    private static function fechaDoc(string $loc): string
    {
        $meses = [
            'es' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'pt' => ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        ];
        $m = $meses[$loc] ?? $meses['es'];
        return $m[(int) date('n') - 1] . ' ' . date('Y');
    }
}
