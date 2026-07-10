<?php
namespace Services\Connectors;

use Services\Http;

/** Telegram: dos bots (comercial público / interno admin). */
final class TelegramConnector
{
    private static function token(string $bot): ?string
    {
        $cfg = ConnectorRegistry::config('telegram');
        return $bot === 'internal' ? ($cfg['internal_token'] ?? null) : ($cfg['commercial_token'] ?? null);
    }

    public static function sendMessage(string $bot, string $chatId, string $text): bool
    {
        $token = self::token($bot);
        if (! $token) { return false; }
        $r = Http::json('POST', "https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML',
        ]);
        return ($r['body']['ok'] ?? false) === true;
    }

    /** Registra el webhook de ambos bots hacia /api/webhook/telegram/{bot}. */
    public static function registerWebhooks(string $baseUrl): array
    {
        $cfg = ConnectorRegistry::config('telegram');
        $secret = $cfg['webhook_secret'] ?? '';
        $out = [];
        foreach (['commercial', 'internal'] as $bot) {
            $tok = $bot === 'internal' ? ($cfg['internal_token'] ?? null) : ($cfg['commercial_token'] ?? null);
            if (! $tok) { continue; }
            $url = rtrim($baseUrl, '/') . "/api/webhook/telegram/{$bot}";
            $params = ['url' => $url];
            if ($secret) { $params['secret_token'] = $secret; }
            $r = Http::json('POST', "https://api.telegram.org/bot{$tok}/setWebhook", $params);
            $out[] = $bot . ': ' . (($r['body']['ok'] ?? false) ? 'registrado' : ($r['body']['description'] ?? 'error'));
        }
        if (! $out) { return ['ok' => false, 'error' => 'Configure al menos un token de bot.']; }
        return ['ok' => true, 'message' => implode(' · ', $out)];
    }

    public static function test(): array
    {
        $cfg = ConnectorRegistry::config('telegram');
        if (empty($cfg['commercial_token']) && empty($cfg['internal_token'])) {
            return ['ok' => false, 'error' => 'Configure al menos un token de bot.'];
        }
        $results = [];
        foreach (['commercial' => $cfg['commercial_token'] ?? null, 'internal' => $cfg['internal_token'] ?? null] as $name => $tok) {
            if (! $tok) { continue; }
            $r = Http::json('GET', "https://api.telegram.org/bot{$tok}/getMe");
            $results[] = $name . ': ' . (($r['body']['ok'] ?? false) ? '@' . $r['body']['result']['username'] : 'token inválido');
        }
        return ['ok' => true, 'message' => implode(' · ', $results)];
    }
}
