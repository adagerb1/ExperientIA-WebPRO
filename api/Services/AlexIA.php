<?php
namespace Services;

use Core\Database;
use Services\Connectors\OpenAIConnector;
use Services\Connectors\AnthropicConnector;
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
            return "Eres AlexIA, el estratega interno de ExperientIA SAS para el equipo del portal administrativo. "
                . "Ayudas a interpretar leads, métricas del CRM, agenda y contenido, y a tomar decisiones de crecimiento. "
                . "Responde en {$locale}, de forma ejecutiva, breve y accionable. Basa tus respuestas en los datos; "
                . "si no tienes un dato, dilo.\nContexto de negocio:\n{$ctx}\n\nDatos en vivo del CRM:\n" . self::snapshot();
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
        $brain = self::brain();
        if (! $brain) {
            throw new \RuntimeException('AlexIA no está disponible: configure y active OpenAI o Anthropic (Claude) en el panel.', 503);
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

        if ($brain === 'anthropic') {
            $respuesta = AnthropicConnector::respond(self::instructions($scope, $locale), self::history($pdo, $convId));
            $responseId = null;
        } else {
            [$respuesta, $responseId] = OpenAIConnector::respond(self::instructions($scope, $locale), $mensaje, $prev);
        }

        $pdo->prepare('INSERT INTO ai_messages (conversation_id, role, content, created_at) VALUES (?,?,?,?)')
            ->execute([$convId, 'assistant', $respuesta, now_utc()]);
        $pdo->prepare('UPDATE ai_conversations SET previous_response_id = ?, updated_at = ? WHERE id = ?')
            ->execute([$responseId, now_utc(), $convId]);

        return ['reply' => $respuesta, 'conversation_id' => $convId];
    }

    /** Llamada one-shot al cerebro activo (sin persistir conversación). */
    public static function ask(string $instructions, string $userInput): string
    {
        $brain = self::brain();
        if (! $brain) {
            throw new \RuntimeException('AlexIA no está disponible: configure y active OpenAI o Anthropic (Claude).', 503);
        }
        if ($brain === 'anthropic') {
            return AnthropicConnector::respond($instructions, [['role' => 'user', 'content' => $userInput]]);
        }
        [$txt] = OpenAIConnector::respond($instructions, $userInput);
        return $txt;
    }

    /** Resumen conciso de la data del negocio para alimentar prompts. */
    public static function snapshot(): string
    {
        $pdo = Database::pdo();
        $q = fn (string $sql) => (int) $pdo->query($sql)->fetchColumn();

        $total = $q('SELECT COUNT(*) FROM leads');
        $nuevos = $q("SELECT COUNT(*) FROM leads WHERE status = 'nuevo'");
        $avanzados = $q("SELECT COUNT(*) FROM leads WHERE status IN ('contactado','calificado','propuesta','cliente')");
        $clientes = $q("SELECT COUNT(*) FROM leads WHERE status = 'cliente'");
        $reservas = $q('SELECT COUNT(DISTINCT lead_id) FROM bookings');
        $prox = $q("SELECT COUNT(*) FROM bookings WHERE status = 'confirmada' AND starts_at >= '" . now_utc() . "'");

        $out = "- Leads totales: {$total} (sin gestionar: {$nuevos})\n";
        $out .= "- Avanzados (contactado+): {$avanzados} · Con reserva: {$reservas} · Clientes: {$clientes} · Sesiones próximas: {$prox}\n";

        $out .= 'Fuentes: ';
        $frag = [];
        foreach ($pdo->query("SELECT COALESCE(NULLIF(source,''),'(sin origen)') s, COUNT(*) c FROM leads GROUP BY s ORDER BY c DESC")->fetchAll() as $r) { $frag[] = "{$r['s']}:{$r['c']}"; }
        $out .= implode(', ', $frag) . "\n";

        $out .= 'Industrias: ';
        $frag = [];
        foreach ($pdo->query("SELECT COALESCE(NULLIF(industry,''),'(sin dato)') i, COUNT(*) c FROM leads GROUP BY i ORDER BY c DESC LIMIT 6")->fetchAll() as $r) { $frag[] = "{$r['i']}:{$r['c']}"; }
        $out .= implode(', ', $frag) . "\n";

        $des = [];
        $sol = [];
        foreach ($pdo->query("SELECT type, payload FROM touchpoints WHERE type IN ('contacto','diagnostico')")->fetchAll() as $tp) {
            $p = json_decode($tp['payload'] ?? '', true) ?: [];
            if ($tp['type'] === 'contacto' && ! empty($p['desafio'])) { $des[$p['desafio']] = ($des[$p['desafio']] ?? 0) + 1; }
            if ($tp['type'] === 'diagnostico' && ! empty($p['resultado'])) { $sol[$p['resultado']] = ($sol[$p['resultado']] ?? 0) + 1; }
        }
        arsort($des);
        arsort($sol);
        if ($des) { $out .= 'Retos declarados: ' . implode(', ', array_map(fn ($k, $v) => "{$k}({$v})", array_keys($des), $des)) . "\n"; }
        if ($sol) { $out .= 'Soluciones más pedidas: ' . implode(', ', array_map(fn ($k, $v) => "{$k}({$v})", array_keys($sol), $sol)) . "\n"; }

        return $out;
    }

    /** Cerebro activo: prioriza el habilitado; si no, cualquiera con credencial. */
    public static function brain(): ?string
    {
        if (ConnectorRegistry::enabled('anthropic') && AnthropicConnector::isReady()) { return 'anthropic'; }
        if (ConnectorRegistry::enabled('openai') && OpenAIConnector::isReady()) { return 'openai'; }
        if (OpenAIConnector::isReady()) { return 'openai'; }
        if (AnthropicConnector::isReady()) { return 'anthropic'; }
        return null;
    }

    /** Historial de la conversación para cerebros sin estado (Anthropic). */
    private static function history(\PDO $pdo, int $convId): array
    {
        $st = $pdo->prepare('SELECT role, content FROM ai_messages WHERE conversation_id = ? ORDER BY id ASC');
        $st->execute([$convId]);
        $out = [];
        foreach ($st->fetchAll() as $m) {
            $out[] = ['role' => $m['role'] === 'assistant' ? 'assistant' : 'user', 'content' => $m['content']];
        }
        return $out;
    }
}
