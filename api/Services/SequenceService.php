<?php
namespace Services;

use Core\Database;
use Services\Connectors\ConnectorRegistry;
use Services\Connectors\WhatsAppConnector;

/**
 * Secuencias de nurturing: al entrar un lead a una etapa del pipeline se inscribe
 * en las secuencias activas de esa etapa, y un runner (cron o "procesar ahora")
 * envía cada paso cuando vence su retraso. Reutiliza plantillas de campaña.
 */
final class SequenceService
{
    /** Inscribe un lead en las secuencias activas cuyo disparador es este estado. */
    public static function enroll(int $leadId, string $status): void
    {
        try {
            $seqs = Database::run('SELECT * FROM sequences WHERE active = 1 AND trigger_status = ?', [$status])->fetchAll();
            foreach ($seqs as $seq) {
                // Evita doble inscripción activa en la misma secuencia.
                $ya = (int) Database::run("SELECT COUNT(*) FROM sequence_enrollments WHERE sequence_id = ? AND lead_id = ? AND status = 'activa'", [$seq['id'], $leadId])->fetchColumn();
                if ($ya > 0) { continue; }
                $steps = self::steps((int) $seq['id']);
                if (! $steps) { continue; }
                $next = self::future((int) $steps[0]['delay_hours']);
                Database::run('INSERT INTO sequence_enrollments (sequence_id, lead_id, step_index, next_run_at, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
                    [$seq['id'], $leadId, 0, $next, 'activa', now_utc(), now_utc()]);
            }
        } catch (\Throwable $e) { /* automatización sin migrar: no interrumpir la captura */ }
    }

    /** Procesa las inscripciones vencidas. Devuelve un resumen de la corrida. */
    public static function processDue(int $limit = 100): array
    {
        $out = ['procesados' => 0, 'enviados' => 0, 'fallidos' => 0, 'completados' => 0, 'omitidos' => 0];
        try {
            $due = Database::run("SELECT * FROM sequence_enrollments WHERE status = 'activa' AND next_run_at IS NOT NULL AND next_run_at <= ? ORDER BY next_run_at LIMIT {$limit}", [now_utc()])->fetchAll();
        } catch (\Throwable $e) { return $out + ['error' => 'sin tablas de automatización']; }

        foreach ($due as $en) {
            $out['procesados']++;
            $steps = self::steps((int) $en['sequence_id']);
            $idx = (int) $en['step_index'];
            if (! isset($steps[$idx])) { self::complete((int) $en['id']); $out['completados']++; continue; }
            $step = $steps[$idx];

            $lead = Database::run('SELECT * FROM leads WHERE id = ?', [$en['lead_id']])->fetch();
            $tpl = $step['template_id'] ? Database::run('SELECT * FROM campaign_templates WHERE id = ?', [$step['template_id']])->fetch() : null;
            if ($lead && $tpl && ($lead['status'] ?? '') !== 'descartado') {
                $r = self::sendStep($lead, $tpl);
                if ($r === true) { $out['enviados']++; } elseif ($r === false) { $out['fallidos']++; } else { $out['omitidos']++; }
            } else { $out['omitidos']++; }

            // Avanza al siguiente paso o completa.
            $nextIdx = $idx + 1;
            if (isset($steps[$nextIdx])) {
                Database::run('UPDATE sequence_enrollments SET step_index = ?, next_run_at = ?, updated_at = ? WHERE id = ?',
                    [$nextIdx, self::future((int) $steps[$nextIdx]['delay_hours']), now_utc(), $en['id']]);
            } else {
                self::complete((int) $en['id']);
                $out['completados']++;
            }
        }
        return $out;
    }

    /** Envía un paso. true=enviado, false=falló, null=omitido (canal no disponible o sin contacto). */
    private static function sendStep(array $lead, array $tpl): ?bool
    {
        $canal = $tpl['canal'] === 'whatsapp' ? 'whatsapp' : 'email';
        $loc = $lead['locale'] ?: 'es';
        $cuerpo = self::personalizar(tr(self::json($tpl['cuerpo']), $loc), $lead);

        if ($canal === 'whatsapp') {
            if (! ConnectorRegistry::enabled('whatsapp') || empty($lead['phone_wa'])) { return null; }
            $ok = WhatsAppConnector::sendText($lead['phone_wa'], $cuerpo);
        } else {
            if (empty($lead['email'])) { return null; }
            $asunto = self::personalizar(tr(self::json($tpl['asunto']), $loc) ?: 'ExperientIA', $lead);
            $ok = Mailer::send($lead['email'], $asunto, nl2br(htmlspecialchars($cuerpo)));
        }
        if ($ok) {
            Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                [$lead['id'], 'secuencia', 'Secuencia: ' . ($canal === 'whatsapp' ? 'WhatsApp' : 'correo') . ' · ' . $tpl['nombre'], json_encode(['template' => $tpl['nombre']], JSON_UNESCAPED_UNICODE), now_utc()]);
        }
        return (bool) $ok;
    }

    private static function steps(int $sequenceId): array
    {
        return Database::run('SELECT * FROM sequence_steps WHERE sequence_id = ? AND active = 1 ORDER BY orden, id', [$sequenceId])->fetchAll();
    }

    private static function complete(int $enrollmentId): void
    {
        Database::run("UPDATE sequence_enrollments SET status = 'completada', updated_at = ? WHERE id = ?", [now_utc(), $enrollmentId]);
    }

    private static function future(int $hours): string
    {
        return gmdate('Y-m-d H:i:s', time() + max(0, $hours) * 3600);
    }

    private static function personalizar(string $txt, array $lead): string
    {
        $nombre = trim((string) ($lead['name'] ?? ''));
        $first = $nombre !== '' ? explode(' ', $nombre)[0] : '';
        return str_replace(['{nombre}', '{empresa}'], [$first, (string) ($lead['company'] ?? '')], $txt);
    }

    private static function json($v): array
    {
        if (is_array($v)) { return $v; }
        $d = is_string($v) ? json_decode($v, true) : null;
        return is_array($d) ? $d : [];
    }
}
