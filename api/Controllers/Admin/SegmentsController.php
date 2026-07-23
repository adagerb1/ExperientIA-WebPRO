<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\Mailer;
use Services\Connectors\ConnectorRegistry;
use Services\Connectors\WhatsAppConnector;

/**
 * Segmentos de audiencia: filtra leads por industria, tamaño, país, origen,
 * campaña, canal, idioma y disponibilidad de contacto (email/WhatsApp) para
 * armar audiencias exportables (CSV) que alimenten pautas y campañas.
 */
final class SegmentsController extends Controller
{
    /** Vista previa: conteo + muestra + desglose para afinar el segmento. */
    public function preview(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        [$where, $params] = $this->build();
        $total = (int) Database::run("SELECT COUNT(*) FROM leads {$where}", $params)->fetchColumn();
        $conEmail = (int) Database::run("SELECT COUNT(*) FROM leads {$where}" . ($where ? ' AND' : ' WHERE') . " email IS NOT NULL AND email <> ''", $params)->fetchColumn();
        $conWa = (int) Database::run("SELECT COUNT(*) FROM leads {$where}" . ($where ? ' AND' : ' WHERE') . " phone_wa IS NOT NULL AND phone_wa <> ''", $params)->fetchColumn();
        $muestra = Database::run("SELECT name,email,phone_wa,country,company,industry,company_size,status,source,utm_campaign FROM leads {$where} ORDER BY updated_at DESC LIMIT 8", $params)->fetchAll();

        Response::ok([
            'total' => $total,
            'con_email' => $conEmail,
            'con_whatsapp' => $conWa,
            'muestra' => $muestra,
            'por_industria' => $this->desglose('industry', $where, $params),
            'por_origen' => $this->desglose('source', $where, $params),
            'canales' => [
                'email' => ! empty(ConnectorRegistry::config('sendgrid')['api_key']) || (bool) ini_get('sendmail_path'),
                'whatsapp' => ConnectorRegistry::enabled('whatsapp'),
            ],
        ]);
    }

    /** Límite de seguridad por envío. */
    private const MAX_ENVIO = 500;

    /** Envía un mensaje al segmento por email o WhatsApp (acción del admin). */
    public function enviar(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);

        $canal = $this->req->input('canal') === 'whatsapp' ? 'whatsapp' : 'email';
        $mensaje = trim((string) $this->req->input('mensaje', ''));
        $asunto = trim((string) $this->req->input('asunto', ''));
        if (mb_strlen($mensaje) < 3) { Response::error('Escribe un mensaje.', 422, ['campos' => ['mensaje']]); }
        if ($canal === 'email' && $asunto === '') { Response::error('El correo necesita asunto.', 422, ['campos' => ['asunto']]); }

        if ($canal === 'whatsapp' && ! ConnectorRegistry::enabled('whatsapp')) {
            Response::error('El conector de WhatsApp no está configurado o activo.', 409);
        }
        if ($canal === 'email' && empty(ConnectorRegistry::config('sendgrid')['api_key']) && ! ini_get('sendmail_path')) {
            Response::error('No hay un método de envío de correo configurado (SendGrid).', 409);
        }

        [$where, $params] = $this->build();
        $campo = $canal === 'whatsapp' ? 'phone_wa' : 'email';
        $rows = Database::run("SELECT id, name, company, email, phone_wa FROM leads {$where}"
            . ($where ? ' AND' : ' WHERE') . " {$campo} IS NOT NULL AND {$campo} <> '' ORDER BY updated_at DESC LIMIT " . self::MAX_ENVIO, $params)->fetchAll();

        $enviados = 0; $fallidos = 0;
        foreach ($rows as $l) {
            $msg = $this->personalizar($mensaje, $l);
            $ok = $canal === 'whatsapp'
                ? WhatsAppConnector::sendText($l['phone_wa'], $msg)
                : Mailer::send($l['email'], $this->personalizar($asunto, $l), nl2br(htmlspecialchars($msg)) . $this->footer());
            if ($ok) {
                $enviados++;
                Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                    [$l['id'], 'envio', ($canal === 'whatsapp' ? 'WhatsApp' : 'Correo') . ' de campaña enviado', json_encode(['asunto' => $asunto ?: null, 'por' => $admin['name'] ?? 'admin'], JSON_UNESCAPED_UNICODE), now_utc()]);
            } else { $fallidos++; }
        }

        Response::ok(['canal' => $canal, 'total' => count($rows), 'enviados' => $enviados, 'fallidos' => $fallidos,
            'tope' => count($rows) >= self::MAX_ENVIO]);
    }

    private function personalizar(string $txt, array $lead): string
    {
        $nombre = trim((string) ($lead['name'] ?? ''));
        $first = $nombre !== '' ? explode(' ', $nombre)[0] : '';
        return str_replace(['{nombre}', '{empresa}'], [$first, (string) ($lead['company'] ?? '')], $txt);
    }

    private function footer(): string
    {
        return '<hr style="border:none;border-top:1px solid #eee;margin:24px 0"><p style="font-size:12px;color:#8792a8">ExperientIA · experientia.pro · Recibes esto porque dejaste tus datos con nosotros.</p>';
    }

    /** Exportación de la audiencia (el frontend arma el CSV con ; + BOM). */
    public function export(): void
    {
        AuthMiddleware::require($this->req);
        [$where, $params] = $this->build();
        $rows = Database::run("SELECT name,email,phone_wa,phone_dial,country,company,role,industry,company_size,status,source,channel,locale,utm_source,utm_medium,utm_campaign,landing_page,created_at FROM leads {$where} ORDER BY created_at DESC", $params)->fetchAll();
        Response::ok(['rows' => $rows, 'total' => count($rows)]);
    }

    /** Construye el WHERE portable a partir de los filtros del segmento. */
    private function build(): array
    {
        $where = [];
        $params = [];
        $eq = ['status', 'channel', 'industry', 'company_size', 'country', 'source', 'locale', 'utm_source', 'utm_medium', 'utm_campaign'];
        foreach ($eq as $f) {
            $v = $this->req->input($f);
            if ($v !== null && $v !== '') { $where[] = "{$f} = ?"; $params[] = $v; }
        }
        if ($this->req->input('has_email')) { $where[] = "email IS NOT NULL AND email <> ''"; }
        if ($this->req->input('has_phone')) { $where[] = "phone_wa IS NOT NULL AND phone_wa <> ''"; }
        if (($q = $this->req->input('q')) !== null && $q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR company LIKE ?)';
            $t = '%' . $q . '%';
            array_push($params, $t, $t, $t);
        }
        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    /** Desglose por una columna dentro del segmento (top 6). */
    private function desglose(string $col, string $where, array $params): array
    {
        $expr = "COALESCE(NULLIF({$col},''),'(sin dato)')";
        $rows = Database::run("SELECT {$expr} k, COUNT(*) c FROM leads {$where} GROUP BY {$expr} ORDER BY c DESC", $params)->fetchAll();
        $out = [];
        foreach (array_slice($rows, 0, 6) as $r) { $out[] = ['clave' => $r['k'], 'total' => (int) $r['c']]; }
        return $out;
    }
}
