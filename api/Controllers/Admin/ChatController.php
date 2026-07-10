<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Validator;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;

/** AlexIA interno: solo usuarios autenticados del portal admin. */
final class ChatController extends Controller
{
    public function mensaje(): void
    {
        $admin = AuthMiddleware::require($this->req);
        $d = Validator::make($this->req->body)->textarea('mensaje', true, 2000)->int('conversation_id')->validated();
        $out = AlexIA::chat('interno', 'web', $d['mensaje'], [
            'admin_id' => (int) $admin['id'],
            'conversation_id' => $d['conversation_id'] ?? null,
            'locale' => 'es',
        ]);
        Response::ok($out);
    }

    /** AlexIA estratega: analiza la data y devuelve recomendaciones priorizadas. */
    public function estrategia(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);

        $instr = 'Eres AlexIA, estratega de crecimiento de ExperientIA SAS (automatización, growth e IA). '
            . 'Con base EXCLUSIVAMENTE en los datos entregados, produce recomendaciones accionables y priorizadas para hacer crecer el negocio. '
            . 'Responde ÚNICAMENTE con JSON válido (sin texto adicional ni markdown) con esta forma exacta: '
            . '{"resumen":"1-2 frases del estado actual","recomendaciones":[{"titulo":"...","detalle":"...","prioridad":"alta|media|baja","accion":"paso o módulo concreto"}]}. '
            . 'Entre 3 y 5 recomendaciones, en español, tono ejecutivo y concreto.';

        try {
            $raw = AlexIA::ask($instr, AlexIA::snapshot());
            $data = json_decode(self::extractJson($raw), true);
            if (! is_array($data)) {
                Response::ok(['resumen' => trim($raw), 'recomendaciones' => []]);
            }
            Response::ok([
                'resumen' => $data['resumen'] ?? '',
                'recomendaciones' => array_slice($data['recomendaciones'] ?? [], 0, 5),
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    private static function extractJson(string $s): string
    {
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        return ($a !== false && $b !== false && $b > $a) ? substr($s, $a, $b - $a + 1) : $s;
    }
}
