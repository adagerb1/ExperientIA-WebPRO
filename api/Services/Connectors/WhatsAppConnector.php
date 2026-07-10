<?php
namespace Services\Connectors;

use Services\Http;

/** WhatsApp Business Cloud API — canal comercial de AlexIA. */
final class WhatsAppConnector
{
    public static function sendText(string $to, string $text): bool
    {
        $cfg = ConnectorRegistry::config('whatsapp');
        if (empty($cfg['token']) || empty($cfg['phone_id'])) { return false; }
        $r = Http::json('POST', "https://graph.facebook.com/v21.0/{$cfg['phone_id']}/messages", [
            'messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text',
            'text' => ['body' => $text],
        ], ['Authorization: Bearer ' . $cfg['token']]);
        return $r['status'] >= 200 && $r['status'] < 300;
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('whatsapp');
        if (empty($cfg['token']) || empty($cfg['phone_id'])) {
            return ['ok' => false, 'error' => 'Configure token y phone_id de WhatsApp Business.'];
        }
        $r = Http::json('GET', "https://graph.facebook.com/v21.0/{$cfg['phone_id']}", [], ['Authorization: Bearer ' . $cfg['token']]);
        return $r['status'] === 200
            ? ['ok' => true, 'message' => 'Número verificado: ' . ($r['body']['display_phone_number'] ?? $cfg['phone_id'])]
            : ['ok' => false, 'error' => 'No se pudo validar el número/token.'];
    }
}
