<?php
/** Envío de correo con mail() de PHP (exim en cPanel). Sin dependencias. */

function enviar_correo(string $para, string $asunto, string $htmlCuerpo, ?string $replyTo = null): bool
{
    $from = config('mail.from');
    $fromName = config('mail.from_name');

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>",
    ];
    if ($replyTo) {
        $headers[] = "Reply-To: {$replyTo}";
    }

    $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

    try {
        return mail($para, $asuntoCodificado, correo_plantilla($htmlCuerpo), implode("\r\n", $headers));
    } catch (Throwable $e) {
        error_log('Fallo de correo: ' . $e->getMessage());
        return false;
    }
}

/** Envoltura visual simple y sobria para los correos. */
function correo_plantilla(string $contenido): string
{
    return '<!doctype html><html><body style="margin:0;background:#f4f6fa;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#1a2333;">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e3e9f2;">'
        . $contenido
        . '<hr style="border:none;border-top:1px solid #e3e9f2;margin:28px 0 16px;">'
        . '<p style="font-size:12px;color:#7a869c;margin:0;">ExperientIA · Automatización · Growth · IA · <a href="' . e(config('app.url')) . '" style="color:#0aa6c9;">experientia.pro</a></p>'
        . '</div></body></html>';
}

/** Aviso interno de nueva interacción de un lead. */
function notificar_lead(array $lead, string $tipo, string $titulo, array $payload = []): void
{
    $industria = config('industries')[$lead['industry'] ?? ''] ?? ($lead['industry'] ?? '—');
    $tamano = config('company_sizes')[$lead['company_size'] ?? ''] ?? '—';
    $wa = $lead['phone_wa'] ? "<a href=\"https://wa.me/{$lead['phone_wa']}\">+{$lead['phone_wa']}</a>" : '—';

    $detalle = '';
    foreach ($payload as $k => $v) {
        if (is_scalar($v) && $v !== '') {
            $detalle .= '<li><b>' . e(ucfirst((string) $k)) . ':</b> ' . e((string) $v) . '</li>';
        }
    }

    $html = '<h2 style="margin:0 0 12px;">' . e($titulo) . '</h2>'
        . '<p style="margin:0 0 16px;"><b>' . e($lead['name']) . '</b>' . ($lead['company'] ? ' · ' . e($lead['company']) : '') . '</p>'
        . '<ul style="padding-left:18px;line-height:1.7;margin:0 0 16px;">'
        . '<li><b>Correo:</b> ' . e($lead['email'] ?? '—') . '</li>'
        . '<li><b>WhatsApp:</b> ' . $wa . '</li>'
        . '<li><b>País:</b> ' . e($lead['country'] ?? '—') . ' · <b>Industria:</b> ' . e($industria) . '</li>'
        . '<li><b>Tamaño:</b> ' . e($tamano) . ' · <b>Idioma:</b> ' . strtoupper($lead['locale'] ?? 'es') . '</li>'
        . '</ul>'
        . ($detalle ? '<ul style="padding-left:18px;line-height:1.7;">' . $detalle . '</ul>' : '')
        . '<p><a href="' . e(config('app.url')) . '/admin" style="display:inline-block;background:#18d6f1;color:#041022;padding:10px 22px;border-radius:999px;text-decoration:none;font-weight:bold;">Ver en el CRM</a></p>';

    enviar_correo(config('mail.notify_to'), "[ExperientIA] {$titulo}", $html, $lead['email'] ?? null);
}
