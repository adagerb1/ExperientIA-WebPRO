<?php
namespace Services;

use Core\Database;

/** Captura de leads con deduplicación por correo/WhatsApp + touchpoints. */
final class LeadService
{
    public static function capture(array $data, string $tipo, string $titulo, array $payload = [], string $channel = 'web'): array
    {
        $pdo = Database::pdo();
        $email = ! empty($data['email']) ? mb_strtolower(trim($data['email'])) : null;
        $phone = ! empty($data['phone_wa']) ? preg_replace('/\D+/', '', $data['phone_wa']) : null;

        $lead = null;
        if ($email || $phone) {
            $st = $pdo->prepare('SELECT * FROM leads WHERE (email IS NOT NULL AND email = ?) OR (phone_wa IS NOT NULL AND phone_wa = ?) LIMIT 1');
            $st->execute([$email ?? '', $phone ?? '']);
            $lead = $st->fetch() ?: null;
        }

        $attrs = array_filter([
            'name' => $data['name'] ?? null, 'email' => $email, 'phone_wa' => $phone,
            'phone_dial' => $data['phone_dial'] ?? null,
            'country' => ! empty($data['country']) ? strtoupper($data['country']) : null,
            'company' => $data['company'] ?? null, 'role' => $data['role'] ?? null,
            'industry' => $data['industry'] ?? null, 'company_size' => $data['company_size'] ?? null,
            'locale' => $data['locale'] ?? 'es',
        ], fn ($v) => $v !== null && $v !== '');

        if ($lead) {
            $upd = [];
            foreach ($attrs as $c => $v) {
                if (empty($lead[$c])) { $upd[$c] = $v; }
            }
            if ($upd) {
                $sets = implode(',', array_map(fn ($c) => "{$c}=?", array_keys($upd)));
                $pdo->prepare("UPDATE leads SET {$sets}, updated_at=? WHERE id=?")
                    ->execute([...array_values($upd), now_utc(), $lead['id']]);
            }
        } else {
            $attrs['source'] = $tipo;
            $attrs['status'] = 'nuevo';
            $attrs['channel'] = $channel;
            $attrs['created_at'] = now_utc();
            $attrs['updated_at'] = now_utc();
            $cols = implode(',', array_keys($attrs));
            $ph = implode(',', array_fill(0, count($attrs), '?'));
            $pdo->prepare("INSERT INTO leads ({$cols}) VALUES ({$ph})")->execute(array_values($attrs));
            $id = $pdo->lastInsertId();
            $lead = ['id' => $id] + $attrs;
        }

        $pdo->prepare('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?,?,?,?,?)')
            ->execute([$lead['id'], $tipo, $titulo, json_encode($payload, JSON_UNESCAPED_UNICODE), now_utc()]);

        // Notificación (SendGrid si está configurado; si no, mail())
        \Services\Mailer::notifyLead($lead, $titulo, $payload);

        return $lead;
    }
}
