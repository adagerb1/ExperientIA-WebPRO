<?php
namespace Services;

use Core\Env;
use Core\Database;

/** Correo con SendGrid (si el conector está activo) o mail() como fallback. */
final class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $ok = false;
        $via = 'mail()';
        $sg = Connectors\ConnectorRegistry::config('sendgrid');
        if (! empty($sg['api_key'])) {
            $via = 'sendgrid';
            $ok = self::viaSendGrid($sg, $to, $subject, $html);
        } else {
            // Fallback mail()
            $from = Env::get('MAIL_FROM', 'hello@experientia.pro');
            $name = Env::get('MAIL_FROM_NAME', 'ExperientIA');
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
                . 'From: =?UTF-8?B?' . base64_encode($name) . "?= <{$from}>\r\n";
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', self::wrap($html), $headers);
        }
        // Los fallos de correo jamás deben ser silenciosos: quedan en el log del día.
        if (! $ok) {
            $dir = BASE_PATH . '/storage/logs';
            if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
            @file_put_contents($dir . '/mail-' . gmdate('Y-m-d') . '.log',
                '[' . gmdate('Y-m-d H:i:s') . "] FALLO via {$via} → {$to} · {$subject}\n", FILE_APPEND | LOCK_EX);
        }
        return $ok;
    }

    private static function viaSendGrid(array $cfg, string $to, string $subject, string $html): bool
    {
        $payload = [
            'personalizations' => [['to' => [['email' => $to]]]],
            'from' => ['email' => $cfg['from_email'] ?? Env::get('MAIL_FROM'), 'name' => $cfg['from_name'] ?? 'ExperientIA'],
            'subject' => $subject,
            'content' => [['type' => 'text/html', 'value' => self::wrap($html)]],
        ];
        $resp = Http::json('POST', 'https://api.sendgrid.com/v3/mail/send', $payload, [
            'Authorization: Bearer ' . $cfg['api_key'],
        ]);
        return $resp['status'] >= 200 && $resp['status'] < 300;
    }

    /** Notificación interna de lead: legible de un vistazo, sin claves crudas. */
    public static function notifyLead(array $lead, string $titulo, array $payload): void
    {
        $to = Env::get('MAIL_NOTIFY', 'hello@experientia.pro');
        $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $base = rtrim(Env::get('APP_URL', 'https://experientia.pro'), '/');

        // Traducciones de claves y valores conocidos a lenguaje del negocio.
        $labels = ['total' => 'Puntaje del tablero', 'banda' => 'Lectura', 'linea_debil' => 'Línea débil',
            'zona_critica' => 'Zona crítica', 'ciudad' => 'Ciudad', 'facturacion' => 'Facturación mensual',
            'recurso' => 'Recurso', 'origen' => 'Origen', 'tema' => 'Tema', 'fecha' => 'Fecha', 'solucion' => 'Solución'];
        $gb = null;
        $traducir = function (string $k, $v) use (&$gb): string {
            $gb ??= require BASE_PATH . '/api/config/growthboard.php';
            if ($k === 'total') { return $v . ' / 55'; }
            if ($k === 'banda') { foreach ($gb['bandas'] as $b) { if ($b['key'] === $v) { return $b['titulo']['es']; } } }
            if ($k === 'linea_debil') { return $gb['lineas'][$v]['nombre']['es'] ?? (string) $v; }
            if ($k === 'zona_critica') { return $gb['zonas'][$v]['nombre']['es'] ?? (string) $v; }
            if ($k === 'facturacion') { return $gb['facturacion'][$v]['es'] ?? (string) $v; }
            return (string) $v;
        };

        $fila = fn ($l, $v) => '<tr><td style="padding:7px 12px 7px 0;color:#7a869c;font-size:13px;white-space:nowrap;vertical-align:top;">' . $l . '</td>'
            . '<td style="padding:7px 0;color:#1a2333;font-size:14px;font-weight:600;">' . $v . '</td></tr>';

        $info = '';
        if (! empty($lead['email'])) { $info .= $fila('Correo', '<a href="mailto:' . $e($lead['email']) . '" style="color:#0aa9c4;text-decoration:none;">' . $e($lead['email']) . '</a>'); }
        $info .= $fila('WhatsApp', ! empty($lead['phone_wa']) ? '<a href="https://wa.me/' . $e($lead['phone_wa']) . '" style="color:#0aa9c4;text-decoration:none;">+' . $e($lead['phone_wa']) . '</a>' : '—');
        $lugar = array_filter([$lead['country'] ?? null, biz('industries')[$lead['industry'] ?? ''] ?? ($lead['industry'] ?? null), $lead['company_size'] ?? null]);
        if ($lugar) { $info .= $fila('Perfil', $e(implode(' · ', $lugar))); }
        if (! empty($lead['utm_campaign'])) { $info .= $fila('Campaña', $e($lead['utm_campaign']) . (! empty($lead['utm_source']) ? ' (' . $e($lead['utm_source']) . ')' : '')); }

        $det = '';
        foreach ($payload as $k => $v) {
            if (! is_scalar($v) || $v === '' || $v === null) { continue; }
            $det .= $fila($labels[$k] ?? $e(ucfirst(str_replace('_', ' ', $k))), $e($traducir($k, $v)));
        }

        $html = '<p style="margin:0 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#7a869c;">Nueva interacción</p>'
            . '<h2 style="margin:0 0 6px;font-size:20px;color:#0a1b3a;">' . $e($titulo) . '</h2>'
            . '<p style="margin:0 0 18px;font-size:16px;"><b>' . $e($lead['name'] ?? 'Lead') . '</b>'
            . (! empty($lead['company']) ? ' · ' . $e($lead['company']) : '') . '</p>'
            . '<table cellpadding="0" cellspacing="0" style="width:100%;border-top:1px solid #eef2f8;">' . $info . '</table>'
            . ($det ? '<p style="margin:18px 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#7a869c;">Detalle</p>'
                . '<table cellpadding="0" cellspacing="0" style="width:100%;border-top:1px solid #eef2f8;background:#f7fafd;border-radius:10px;">' . $det . '</table>' : '')
            . (! empty($lead['id']) ? self::boton($base . '/admin/leads/' . (int) $lead['id'], 'Abrir en el CRM') : '');
        self::send($to, "[ExperientIA] {$titulo}", $html);
    }

    /**
     * Plantilla editable desde el admin (Plataforma → Plantillas email), con
     * respaldo al contenido por defecto si no existe o está inactiva.
     * @return array{0:string,1:string}|null [asunto, cuerpo] en el idioma pedido
     */
    public static function template(string $tkey, string $locale = 'es'): ?array
    {
        try {
            $r = Database::run('SELECT subject, body, active FROM email_templates WHERE tkey = ?', [$tkey])->fetch();
            if (! $r || ! (int) $r['active']) { return null; }
            $s = json_decode($r['subject'], true) ?: [];
            $b = json_decode($r['body'], true) ?: [];
            $subject = $s[$locale] ?? $s['es'] ?? null;
            $body = $b[$locale] ?? $b['es'] ?? null;
            return ($subject && $body) ? [$subject, $body] : null;
        } catch (\Throwable $e) { return null; }
    }

    /** Botón de acción con estilos en línea (compatibles con Gmail/Outlook). */
    public static function boton(string $url, string $texto): string
    {
        return '<p style="text-align:center;margin:26px 0;"><a href="' . htmlspecialchars($url) . '" '
            . 'style="background-color:#18d6f1;background-image:linear-gradient(135deg,#18d6f1,#7a63ff);color:#041022;'
            . 'text-decoration:none;font-weight:700;font-size:15px;padding:14px 30px;border-radius:999px;display:inline-block;">'
            . $texto . '</a></p>';
    }

    private static function wrap(string $html): string
    {
        $base = rtrim(Env::get('APP_URL', 'https://experientia.pro'), '/');
        return '<div style="max-width:560px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;">'
            . '<div style="background-color:#0a1b3a;border-radius:14px 14px 0 0;padding:22px 28px;">'
            .   '<a href="' . $base . '" style="text-decoration:none;"><img src="' . $base . '/assets/img/brand/logo.png" alt="ExperientIA" height="30" style="height:30px;border:0;display:block;"></a>'
            . '</div>'
            . '<div style="background:#ffffff;border:1px solid #e3e9f2;border-top:0;border-radius:0 0 14px 14px;padding:30px 28px;color:#1a2333;font-size:15px;line-height:1.6;">'
            .   $html
            .   '<hr style="border:none;border-top:1px solid #e3e9f2;margin:26px 0 14px;">'
            .   '<p style="font-size:12px;color:#7a869c;margin:0;">ExperientIA · Automatización · Growth · IA · <a href="' . $base . '" style="color:#0aa9c4;text-decoration:none;">experientia.pro</a></p>'
            . '</div></div>';
    }
}
