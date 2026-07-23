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
        $todosOk = true;
        foreach (['comercial' => $cfg['commercial_token'] ?? null, 'interno' => $cfg['internal_token'] ?? null] as $name => $tok) {
            if (! $tok) { continue; }
            $r = Http::json('GET', "https://api.telegram.org/bot{$tok}/getMe");
            $ok = ($r['body']['ok'] ?? false) === true;
            if (! $ok) { $todosOk = false; }
            $results[] = $name . ': ' . ($ok ? '@' . $r['body']['result']['username'] : '⚠ token inválido');
        }
        // Cuántos usuarios del equipo recibirán notificaciones internas.
        try {
            $subs = (int) \Core\Database::run("SELECT COUNT(*) FROM admins WHERE active = 1 AND notify_telegram = 1 AND telegram_user_id IS NOT NULL AND telegram_user_id != ''")->fetchColumn();
            $results[] = $subs . ' usuario(s) reciben notificaciones';
        } catch (\Throwable $e) {}
        return ['ok' => $todosOk, 'message' => implode(' · ', $results), 'error' => $todosOk ? null : 'Uno o más tokens son inválidos.'];
    }
}
