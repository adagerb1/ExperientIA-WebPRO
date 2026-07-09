<?php
/**
 * Captura de leads con deduplicación (PDO puro).
 * Un lead se identifica por correo o WhatsApp: si existe, se enriquece la
 * ficha (solo campos vacíos) y se registra el touchpoint en su historial.
 */

function capturar_lead(array $data, string $tipo, string $titulo, array $payload = []): array
{
    $pdo = db();

    $email = isset($data['email']) ? mb_strtolower(trim($data['email'])) : null;
    $phone = isset($data['phone_wa']) ? preg_replace('/\D+/', '', $data['phone_wa']) : null;
    $email = $email ?: null;
    $phone = $phone ?: null;

    $lead = null;
    if ($email || $phone) {
        $st = $pdo->prepare('SELECT * FROM leads WHERE (email IS NOT NULL AND email = ?) OR (phone_wa IS NOT NULL AND phone_wa = ?) LIMIT 1');
        $st->execute([$email ?? '', $phone ?? '']);
        $lead = $st->fetch() ?: null;
    }

    $attrs = array_filter([
        'name' => trim($data['name'] ?? '') ?: null,
        'email' => $email,
        'phone_wa' => $phone,
        'phone_dial' => $data['phone_dial'] ?? null,
        'country' => isset($data['country']) ? strtoupper(substr($data['country'], 0, 2)) : null,
        'company' => trim($data['company'] ?? '') ?: null,
        'role' => trim($data['role'] ?? '') ?: null,
        'industry' => $data['industry'] ?? null,
        'company_size' => $data['company_size'] ?? null,
        'locale' => $data['locale'] ?? locale(),
    ], fn ($v) => $v !== null && $v !== '');

    if ($lead) {
        // Enriquecer solo campos actualmente vacíos.
        $updates = [];
        foreach ($attrs as $campo => $valor) {
            if (empty($lead[$campo])) {
                $updates[$campo] = $valor;
            }
        }
        if ($updates) {
            $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($updates)));
            $pdo->prepare("UPDATE leads SET {$sets}, updated_at = ? WHERE id = ?")
                ->execute([...array_values($updates), ahora(), $lead['id']]);
        }
        $st = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
        $st->execute([$lead['id']]);
        $lead = $st->fetch();
    } else {
        $attrs['source'] = $tipo;
        $attrs['status'] = 'nuevo';
        $attrs['created_at'] = ahora();
        $attrs['updated_at'] = ahora();
        $cols = implode(', ', array_keys($attrs));
        $marks = implode(', ', array_fill(0, count($attrs), '?'));
        $pdo->prepare("INSERT INTO leads ({$cols}) VALUES ({$marks})")->execute(array_values($attrs));
        $st = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
        $st->execute([$pdo->lastInsertId()]);
        $lead = $st->fetch();
    }

    $pdo->prepare('INSERT INTO touchpoints (lead_id, type, title, payload, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$lead['id'], $tipo, $titulo, json_encode($payload, JSON_UNESCAPED_UNICODE), ahora()]);

    notificar_lead($lead, $tipo, $titulo, $payload);

    return $lead;
}
