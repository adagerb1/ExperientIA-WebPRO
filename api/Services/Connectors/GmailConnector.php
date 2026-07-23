<?php
namespace Services\Connectors;

use Services\Http;

/**
 * Correo corporativo en Google Workspace vía Gmail API (OAuth con refresh token,
 * mismo patrón que Calendar/Business). Con este conector la plataforma puede:
 *   • ENVIAR con la identidad corporativa (queda en "Enviados" del buzón real).
 *   • LEER la bandeja de entrada para que AlexIA clasifique y dé trámite.
 *   • RESPONDER dentro del mismo hilo (threading con In-Reply-To/References).
 * Scopes requeridos: gmail.send + gmail.readonly (o gmail.modify).
 */
final class GmailConnector
{
    private const API = 'https://gmail.googleapis.com/gmail/v1/users/me';

    private static function accessToken(?array $cfg = null): ?string
    {
        $cfg = $cfg ?? ConnectorRegistry::config('google_mail');
        if (empty($cfg['refresh_token'])) { return null; }
        $r = Http::form('POST', 'https://oauth2.googleapis.com/token', [
            'client_id' => $cfg['client_id'] ?? '', 'client_secret' => $cfg['client_secret'] ?? '',
            'refresh_token' => $cfg['refresh_token'], 'grant_type' => 'refresh_token',
        ]);
        return $r['body']['access_token'] ?? null;
    }

    public static function isReady(): bool
    {
        $cfg = ConnectorRegistry::config('google_mail');
        return ! empty($cfg['refresh_token']) && ! empty($cfg['client_id']);
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('google_mail');
        if (empty($cfg['refresh_token'])) { return ['ok' => false, 'error' => 'Configura el refresh token de Google (scopes gmail.send y gmail.readonly).']; }
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'No se pudo renovar el token. Revisa Client ID/Secret y el refresh token.']; }
        $r = Http::json('GET', self::API . '/profile', null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] === 200) {
            return ['ok' => true, 'message' => 'Gmail conectado como ' . ($r['body']['emailAddress'] ?? 'buzón') . ' · ' . (int) ($r['body']['messagesTotal'] ?? 0) . ' mensajes en el buzón.'];
        }
        return ['ok' => false, 'error' => 'Google respondió ' . $r['status'] . '. Verifica que la Gmail API esté habilitada y los scopes autorizados.'];
    }

    /**
     * Envía un correo HTML con la identidad del buzón conectado.
     * $opts: from_name, reply_to, in_reply_to (Message-ID), references, thread_id.
     * Devuelve ['ok'=>bool, 'id'=>gmailId|null, 'error'=>...].
     */
    public static function send(string $to, string $subject, string $html, array $opts = []): array
    {
        $cfg = ConnectorRegistry::config('google_mail');
        $token = self::accessToken($cfg);
        if (! $token) { return ['ok' => false, 'error' => 'Gmail no configurado.']; }

        $from = $cfg['mailbox'] ?? '';
        $fromName = $opts['from_name'] ?? ($cfg['from_name'] ?? 'ExperientIA');
        $b64h = fn ($s) => '=?UTF-8?B?' . base64_encode($s) . '?=';

        $headers = [];
        $headers[] = 'From: ' . $b64h($fromName) . ' <' . $from . '>';
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $b64h($subject);
        if (! empty($opts['reply_to'])) { $headers[] = 'Reply-To: ' . $opts['reply_to']; }
        if (! empty($opts['in_reply_to'])) {
            $headers[] = 'In-Reply-To: ' . $opts['in_reply_to'];
            $headers[] = 'References: ' . ($opts['references'] ?? $opts['in_reply_to']);
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        $raw = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html));
        $payload = ['raw' => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=')];
        if (! empty($opts['thread_id'])) { $payload['threadId'] = $opts['thread_id']; }

        $r = Http::json('POST', self::API . '/messages/send', $payload, ['Authorization: Bearer ' . $token]);
        if ($r['status'] >= 200 && $r['status'] < 300) {
            return ['ok' => true, 'id' => $r['body']['id'] ?? null, 'thread_id' => $r['body']['threadId'] ?? null];
        }
        return ['ok' => false, 'error' => 'Gmail no pudo enviar (' . $r['status'] . ').'];
    }

    /** IDs de mensajes recientes de la bandeja (excluye lo enviado por nosotros). */
    public static function listInbox(int $max = 20, string $newerThan = '7d'): array
    {
        $token = self::accessToken();
        if (! $token) { return ['ok' => false, 'error' => 'Gmail no configurado.']; }
        $q = urlencode('in:inbox -from:me newer_than:' . $newerThan);
        $r = Http::json('GET', self::API . '/messages?maxResults=' . $max . '&q=' . $q, null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] !== 200) { return ['ok' => false, 'error' => 'Gmail respondió ' . $r['status'] . ' al listar.']; }
        return ['ok' => true, 'ids' => array_map(fn ($m) => $m['id'], $r['body']['messages'] ?? [])];
    }

    /** Mensaje completo normalizado: remitente, asunto, texto, hilo y Message-ID. */
    public static function getMessage(string $id): ?array
    {
        $token = self::accessToken();
        if (! $token) { return null; }
        $r = Http::json('GET', self::API . '/messages/' . $id . '?format=full', null, ['Authorization: Bearer ' . $token]);
        if ($r['status'] !== 200) { return null; }
        $m = $r['body'];
        $headers = [];
        foreach (($m['payload']['headers'] ?? []) as $h) { $headers[strtolower($h['name'])] = $h['value']; }
        $fromRaw = $headers['from'] ?? '';
        $fromEmail = strtolower(preg_match('/<([^>]+)>/', $fromRaw, $mm) ? $mm[1] : trim($fromRaw));
        $fromName = trim(preg_replace('/<[^>]+>/', '', $fromRaw), ' "');

        return [
            'gmail_id' => $m['id'],
            'thread_id' => $m['threadId'] ?? null,
            'message_id' => $headers['message-id'] ?? null,
            'from_email' => $fromEmail,
            'from_name' => $fromName ?: $fromEmail,
            'subject' => $headers['subject'] ?? '(sin asunto)',
            'snippet' => $m['snippet'] ?? '',
            'body' => self::extraerTexto($m['payload'] ?? []),
            'received_at' => isset($m['internalDate']) ? gmdate('Y-m-d H:i:s', (int) ($m['internalDate'] / 1000)) : now_utc(),
        ];
    }

    /** Texto plano del cuerpo (prefiere text/plain; si no, limpia el HTML). */
    private static function extraerTexto(array $payload): string
    {
        $decode = fn ($d) => base64_decode(strtr($d, '-_', '+/')) ?: '';
        $buscar = function (array $part, string $mime) use (&$buscar, $decode): string {
            if (($part['mimeType'] ?? '') === $mime && ! empty($part['body']['data'])) { return $decode($part['body']['data']); }
            foreach (($part['parts'] ?? []) as $p) { $t = $buscar($p, $mime); if ($t !== '') { return $t; } }
            return '';
        };
        $txt = $buscar($payload, 'text/plain');
        if ($txt === '') { $txt = strip_tags($buscar($payload, 'text/html')); }
        $txt = preg_replace('/\r\n|\r/', "\n", trim($txt));
        return mb_substr($txt, 0, 6000);
    }
}
