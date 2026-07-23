<?php
namespace Services\Connectors;

use Services\Http;

/**
 * Conector OpenAI · AlexIA — usa la Responses API (/v1/responses).
 * Encadena turnos con previous_response_id. Soporta texto, imagen y audio.
 */
final class OpenAIConnector
{
    public static function isReady(): bool
    {
        $cfg = ConnectorRegistry::config('openai');
        return ! empty($cfg['api_key']);
    }

    /** Genera respuesta de texto. Devuelve [texto, response_id]. */
    public static function respond(string $instructions, string $userInput, ?string $previousResponseId = null, ?int $maxTokens = null): array
    {
        $cfg = ConnectorRegistry::config('openai');
        $body = [
            'model' => $cfg['model'] ?: 'gpt-4o-mini',
            'instructions' => $instructions,
            'input' => $userInput,
            'store' => true,
        ];
        if ($maxTokens) {
            $body['max_output_tokens'] = $maxTokens;
        }
        if ($previousResponseId) {
            $body['previous_response_id'] = $previousResponseId;
        }
        $r = Http::json('POST', 'https://api.openai.com/v1/responses', $body, [
            'Authorization: Bearer ' . $cfg['api_key'],
        ]);
        if ($r['status'] < 200 || $r['status'] >= 300) {
            $msg = is_array($r['body']) ? ($r['body']['error']['message'] ?? 'Error de OpenAI') : 'Error de OpenAI';
            throw new \RuntimeException($msg, 502);
        }
        return [self::extractText($r['body']), $r['body']['id'] ?? null];
    }

    /** Genera una imagen (gpt-image-1). Devuelve URL/base64. */
    public static function image(string $prompt, string $size = '1024x1024'): array
    {
        $cfg = ConnectorRegistry::config('openai');
        $r = Http::json('POST', 'https://api.openai.com/v1/images/generations', [
            'model' => $cfg['image_model'] ?: 'gpt-image-1', 'prompt' => $prompt, 'size' => $size, 'n' => 1,
        ], ['Authorization: Bearer ' . $cfg['api_key']]);
        return $r['body']['data'][0] ?? [];
    }

    /** Sintetiza voz (TTS) con el modelo y voz configurados. Devuelve bytes MP3. */
    public static function speak(string $text): string
    {
        $cfg = ConnectorRegistry::config('openai');
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cfg['api_key'], 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $cfg['audio_model'] ?: 'gpt-4o-mini-tts',
                'voice' => $cfg['voice'] ?: 'shimmer',
                'input' => $text,
            ]),
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return $raw ?: '';
    }

    /** Transcribe audio (whisper/gpt-4o-transcribe). */
    public static function transcribe(string $audioPath): string
    {
        $cfg = ConnectorRegistry::config('openai');
        $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $cfg['api_key']],
            CURLOPT_POSTFIELDS => ['model' => 'gpt-4o-transcribe', 'file' => new \CURLFile($audioPath)],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode($raw, true)['text'] ?? '';
    }

    private static function extractText(array $resp): string
    {
        if (! empty($resp['output_text'])) {
            return $resp['output_text'];
        }
        $out = '';
        foreach ($resp['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $c) {
                if (($c['type'] ?? '') === 'output_text') { $out .= $c['text']; }
            }
        }
        return $out ?: 'No fue posible generar una respuesta.';
    }

    public static function test(): array
    {
        if (! self::isReady()) {
            return ['ok' => false, 'error' => 'Falta la API key de OpenAI.'];
        }
        try {
            [$txt] = self::respond('Responde solo: OK', 'ping');
            return ['ok' => true, 'message' => 'Conexión válida. Respuesta: ' . mb_substr($txt, 0, 40)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
