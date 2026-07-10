<?php
namespace Services\Connectors;

use Services\Http;

/**
 * Conector Anthropic (Claude) — Messages API (/v1/messages).
 * Cerebro alternativo de AlexIA para redacción y análisis estratégico.
 */
final class AnthropicConnector
{
    public static function isReady(): bool
    {
        $cfg = ConnectorRegistry::config('anthropic');
        return ! empty($cfg['api_key']);
    }

    /**
     * Genera una respuesta de texto. $historial es una lista de
     * ['role'=>'user'|'assistant','content'=>string]. Devuelve el texto.
     */
    public static function respond(string $instructions, array $historial): string
    {
        $cfg = ConnectorRegistry::config('anthropic');
        $r = Http::json('POST', 'https://api.anthropic.com/v1/messages', [
            'model' => $cfg['model'] ?: 'claude-sonnet-4-5',
            'max_tokens' => 1200,
            'system' => $instructions,
            'messages' => $historial,
        ], [
            'x-api-key: ' . $cfg['api_key'],
            'anthropic-version: 2023-06-01',
        ]);
        if ($r['status'] < 200 || $r['status'] >= 300) {
            $msg = is_array($r['body']) ? ($r['body']['error']['message'] ?? 'Error de Anthropic') : 'Error de Anthropic';
            throw new \RuntimeException($msg, 502);
        }
        $out = '';
        foreach ($r['body']['content'] ?? [] as $c) {
            if (($c['type'] ?? '') === 'text') { $out .= $c['text']; }
        }
        return $out ?: 'No fue posible generar una respuesta.';
    }

    public static function test(): array
    {
        if (! self::isReady()) {
            return ['ok' => false, 'error' => 'Falta la API key de Anthropic.'];
        }
        try {
            $txt = self::respond('Responde solo: OK', [['role' => 'user', 'content' => 'ping']]);
            return ['ok' => true, 'message' => 'Conexión válida. Respuesta: ' . mb_substr($txt, 0, 40)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
