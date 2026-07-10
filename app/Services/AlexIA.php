<?php
namespace Services;

use Core\Database;
use Services\Connectors\OpenAIConnector;
use Services\Connectors\ConnectorRegistry;

/**
 * AlexIA — agente de ExperientIA. Dos personalidades según el ámbito:
 *  - comercial: atiende leads (web, WhatsApp, Telegram público). Informa de
 *    productos/servicios y captura datos hacia el CRM.
 *  - interno: asiste a usuarios del portal admin (Telegram interno / panel).
 */
final class AlexIA
{
    public static function instructions(string $scope, string $locale = 'es'): string
    {
        $ctx = self::businessContext($locale);
        if ($scope === 'interno') {
            return "Eres AlexIA, el asistente interno de ExperientIA SAS para el equipo del portal administrativo. "
                . "Ayudas a consultar y entender leads, métricas del CRM, agenda y contenido. Responde en {$locale}, "
                . "de forma ejecutiva, breve y accionable. No inventes datos; si no tienes un dato, dilo. "
                . "Contexto de negocio:\n{$ctx}";
        }
        return "Eres AlexIA, asesor comercial de ExperientIA SAS. Tu misión es ayudar a empresas a entender cómo "
            . "la automatización, el growth y la IA pueden hacerlas crecer, y guiar al interesado hacia un diagnóstico "
            . "o una sesión 1:1. Responde en {$locale}, con tono premium, cercano y claro. Sé conciso. "
            . "Cuando el interesado muestre intención, invítalo amablemente a dejar su nombre, correo o WhatsApp. "
            . "Contexto de negocio:\n{$ctx}";
    }

    private static function businessContext(string $locale): string
    {
        $sols = Database::pdo()->query('SELECT titulo, cambia FROM solutions WHERE active = 1 ORDER BY sort')->fetchAll();
        $prods = Database::pdo()->query('SELECT nombre, texto FROM products WHERE active = 1 ORDER BY sort')->fetchAll();
        $out = "Soluciones:\n";
        foreach ($sols as $s) { $out .= '- ' . tr($s['titulo'], $locale) . ': ' . tr($s['cambia'], $locale) . "\n"; }
        $out .= "Productos:\n";
        foreach ($prods as $p) { $out .= '- ' . tr($p['nombre'], $locale) . ': ' . tr($p['texto'], $locale) . "\n"; }
        return $out;
    }

    /** Procesa un mensaje en una conversación (crea/continúa). Devuelve la respuesta. */
    public static function chat(string $scope, string $channel, string $mensaje, array $ctx = []): array
    {
        if (! OpenAIConnector::isReady()) {
            throw new \RuntimeException('AlexIA no está disponible: configure el conector de OpenAI en el panel.', 503);
        }
        $pdo = Database::pdo();
        $locale = $ctx['locale'] ?? 'es';

        // Localizar/crear conversación
        $conv = null;
        if (! empty($ctx['external_id'])) {
            $st = $pdo->prepare('SELECT * FROM ai_conversations WHERE channel = ? AND external_id = ? ORDER BY id DESC LIMIT 1');
            $st->execute([$channel, $ctx['external_id']]);
            $conv = $st->fetch() ?: null;
        } elseif (! empty($ctx['conversation_id'])) {
            $st = $pdo->prepare('SELECT * FROM ai_conversations WHERE id = ?');
            $st->execute([$ctx['conversation_id']]);
            $conv = $st->fetch() ?: null;
        }

        if (! $conv) {
            $pdo->prepare('INSERT INTO ai_conversations (channel, scope, lead_id, admin_id, external_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?)')
                ->execute([$channel, $scope, $ctx['lead_id'] ?? null, $ctx['admin_id'] ?? null, $ctx['external_id'] ?? null, now_utc(), now_utc()]);
            $convId = (int) $pdo->lastInsertId();
            $prev = null;
        } else {
            $convId = (int) $conv['id'];
            $prev = $conv['previous_response_id'] ?: null;
        }

        $pdo->prepare('INSERT INTO ai_messages (conversation_id, role, content, created_at) VALUES (?,?,?,?)')
            ->execute([$convId, 'user', $mensaje, now_utc()]);

        [$respuesta, $responseId] = OpenAIConnector::respond(self::instructions($scope, $locale), $mensaje, $prev);

        $pdo->prepare('INSERT INTO ai_messages (conversation_id, role, content, created_at) VALUES (?,?,?,?)')
            ->execute([$convId, 'assistant', $respuesta, now_utc()]);
        $pdo->prepare('UPDATE ai_conversations SET previous_response_id = ?, updated_at = ? WHERE id = ?')
            ->execute([$responseId, now_utc(), $convId]);

        return ['reply' => $respuesta, 'conversation_id' => $convId];
    }
}
