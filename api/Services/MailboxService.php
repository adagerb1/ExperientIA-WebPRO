<?php
namespace Services;

use Core\Database;
use Services\Connectors\GmailConnector;

/**
 * Buzón comercial: sincroniza la bandeja de Google Workspace, empareja cada
 * correo con su lead/cliente del CRM y deja que AlexIA haga el triage
 * (clase, resumen, acción sugerida y borrador de respuesta). Los correos de
 * clientes generan aviso por Telegram y touchpoint en el timeline del lead.
 * Corre desde el cron y bajo demanda desde el panel. Silencioso ante fallos
 * de IA: el correo queda 'pendiente' y se reintenta en la siguiente pasada.
 */
final class MailboxService
{
    public static function sync(int $max = 15): array
    {
        if (! GmailConnector::isReady()) { return ['ok' => false, 'error' => 'Configura el conector de Correo corporativo (Google Workspace).']; }
        $list = GmailConnector::listInbox($max);
        if (empty($list['ok'])) { return $list; }

        $nuevos = 0; $triage = 0;
        foreach ($list['ids'] as $gid) {
            $ya = Database::run('SELECT id FROM emails WHERE gmail_id = ?', [$gid])->fetch();
            if ($ya) { continue; }
            $m = GmailConnector::getMessage($gid);
            if (! $m) { continue; }

            // Emparejar con el CRM por el correo del remitente.
            $lead = null;
            try { $lead = Database::run('SELECT * FROM leads WHERE LOWER(email) = ?', [strtolower($m['from_email'])])->fetch() ?: null; } catch (\Throwable $e) { /* opcional */ }

            Database::run(
                'INSERT INTO emails (gmail_id, thread_id, message_id, from_email, from_name, subject, snippet, body, lead_id, ai_clase, estado, received_at, synced_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$m['gmail_id'], $m['thread_id'], $m['message_id'], $m['from_email'], $m['from_name'],
                 mb_substr($m['subject'], 0, 500), mb_substr($m['snippet'], 0, 500), $m['body'],
                 $lead['id'] ?? null, 'pendiente', 'nuevo', $m['received_at'], now_utc()]
            );
            $nuevos++;
        }

        // Triage de AlexIA sobre lo pendiente (también reintenta pasadas fallidas).
        $pendientes = Database::run("SELECT * FROM emails WHERE ai_clase = 'pendiente' ORDER BY received_at DESC LIMIT 10")->fetchAll();
        foreach ($pendientes as $e) {
            if (self::triage($e)) { $triage++; }
        }

        return ['ok' => true, 'message' => $nuevos . ' correo(s) nuevo(s) leído(s)' . ($triage ? ' · AlexIA clasificó ' . $triage : '') . '.'];
    }

    /** Clasifica un correo, sugiere acción y borrador de respuesta. */
    public static function triage(array $e): bool
    {
        $esLead = ! empty($e['lead_id']);
        $ctx = $esLead ? self::contextoLead((int) $e['lead_id']) : '(el remitente no está en el CRM)';
        try {
            $r = AlexIA::askJson(
                "Eres AlexIA, asistente comercial de ExperientIA SAS. Analiza este correo recibido en el buzón comercial y "
                . "responde JSON: {\"clase\":\"cliente|prospecto|proveedor|otro|spam\",\"resumen\":\"1-2 frases\","
                . "\"accion\":\"qué debería hacer el equipo (breve y concreta)\",\"respuesta\":\"borrador de respuesta en el idioma "
                . "del correo, tono profesional cercano de ExperientIA, listo para revisar; cadena vacía si no amerita respuesta\"}\n"
                . "Reglas: no inventes datos ni compromisos de precio/fechas; si pide algo que exige decisión humana, la acción "
                . "debe decirlo. 'cliente' = remitente ya en el CRM o que menciona un proyecto en curso; 'prospecto' = interés "
                . "comercial nuevo.\nContexto del remitente en el CRM:\n{$ctx}",
                'DE: ' . $e['from_name'] . ' <' . $e['from_email'] . ">\nASUNTO: " . $e['subject'] . "\n\n" . mb_substr((string) $e['body'], 0, 3500)
            );
        } catch (\Throwable $ex) { return false; }

        $clase = in_array($r['clase'] ?? '', ['cliente', 'prospecto', 'proveedor', 'otro', 'spam'], true) ? $r['clase'] : 'otro';
        Database::run('UPDATE emails SET ai_clase = ?, ai_resumen = ?, ai_accion = ?, ai_respuesta = ? WHERE id = ?',
            [$clase, mb_substr((string) ($r['resumen'] ?? ''), 0, 1000), mb_substr((string) ($r['accion'] ?? ''), 0, 1000),
             mb_substr((string) ($r['respuesta'] ?? ''), 0, 4000), $e['id']]);

        // Trazabilidad y aviso solo para lo que importa (cliente/prospecto).
        if (in_array($clase, ['cliente', 'prospecto'], true)) {
            try {
                Notifier::telegram('📥 <b>Correo de ' . ($clase === 'cliente' ? 'cliente' : 'prospecto') . '</b> · '
                    . htmlspecialchars($e['from_name'] ?: $e['from_email']) . "\n✉️ " . htmlspecialchars(mb_substr($e['subject'], 0, 120))
                    . "\n🧭 " . htmlspecialchars(mb_substr((string) ($r['resumen'] ?? ''), 0, 200)) . "\nRevisa el Buzón del panel para dar trámite.");
            } catch (\Throwable $ex) { /* opcional */ }
            if (! empty($e['lead_id'])) {
                try {
                    Database::run('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)',
                        [$e['lead_id'], 'email_recibido', 'Correo: ' . mb_substr($e['subject'], 0, 180),
                         json_encode(['resumen' => $r['resumen'] ?? ''], JSON_UNESCAPED_UNICODE), now_utc()]);
                } catch (\Throwable $ex) { /* opcional */ }
            }
        }
        return true;
    }

    private static function contextoLead(int $leadId): string
    {
        try {
            $l = Database::run('SELECT name, company, status, industry FROM leads WHERE id = ?', [$leadId])->fetch();
            if (! $l) { return '(no encontrado)'; }
            $out = 'Nombre: ' . $l['name'] . ($l['company'] ? ' · Empresa: ' . $l['company'] : '') . ' · Estado en CRM: ' . $l['status'] . "\n";
            $tps = Database::run('SELECT type, title FROM touchpoints WHERE lead_id = ? ORDER BY id DESC LIMIT 5', [$leadId])->fetchAll();
            foreach ($tps as $t) { $out .= '  - [' . $t['type'] . '] ' . $t['title'] . "\n"; }
            return $out;
        } catch (\Throwable $e) { return '(sin contexto)'; }
    }
}
