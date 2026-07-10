<?php
namespace Services;

use Core\Env;
use Core\Database;

/** Correo con SendGrid (si el conector está activo) o mail() como fallback. */
final class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        $sg = Connectors\ConnectorRegistry::config('sendgrid');
        if (! empty($sg['api_key'])) {
            return self::viaSendGrid($sg, $to, $subject, $html);
        }
        // Fallback mail()
        $from = Env::get('MAIL_FROM', 'hello@experientia.pro');
        $name = Env::get('MAIL_FROM_NAME', 'ExperientIA');
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
            . 'From: =?UTF-8?B?' . base64_encode($name) . "?= <{$from}>\r\n";
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', self::wrap($html), $headers);
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

    public static function notifyLead(array $lead, string $titulo, array $payload): void
    {
        $to = Env::get('MAIL_NOTIFY', 'hello@experientia.pro');
        $wa = !empty($lead['phone_wa']) ? "+{$lead['phone_wa']}" : '—';
        $det = '';
        foreach ($payload as $k => $v) {
            if (is_scalar($v) && $v !== '') { $det .= '<li><b>' . htmlspecialchars((string) $k) . ':</b> ' . htmlspecialchars((string) $v) . '</li>'; }
        }
        $html = "<h2>{$titulo}</h2><p><b>" . htmlspecialchars($lead['name'] ?? '') . '</b> · ' . htmlspecialchars($lead['company'] ?? '') . '</p>'
            . '<ul><li>Correo: ' . htmlspecialchars($lead['email'] ?? '—') . "</li><li>WhatsApp: {$wa}</li>"
            . '<li>País: ' . htmlspecialchars($lead['country'] ?? '—') . ' · Industria: ' . htmlspecialchars(biz('industries')[$lead['industry'] ?? ''] ?? '—') . '</li></ul>'
            . ($det ? "<ul>{$det}</ul>" : '');
        self::send($to, "[ExperientIA] {$titulo}", $html);
    }

    private static function wrap(string $html): string
    {
        return '<div style="max-width:560px;margin:0 auto;font-family:Arial,sans-serif;background:#fff;border-radius:12px;padding:28px;border:1px solid #e3e9f2;color:#1a2333;">'
            . $html . '<hr style="border:none;border-top:1px solid #e3e9f2;margin:24px 0 12px;"><p style="font-size:12px;color:#7a869c;">ExperientIA · Automatización · Growth · IA</p></div>';
    }
}
