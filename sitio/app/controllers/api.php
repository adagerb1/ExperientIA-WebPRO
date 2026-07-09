<?php
/**
 * API JSON (PHP puro). El frontend interactúa con estos endpoints vía fetch().
 * Públicos:  POST contacto | newsletter | descarga | diagnostico | reserva · GET slots · GET descarga-archivo
 * Admin:     bajo /api/admin/* (sesión requerida) — ver api_admin.php
 */

function api_despachar(string $ruta, string $metodo): never
{
    // Idioma de la petición (para textos de respuesta y captura)
    $loc = input()['locale'] ?? ($_GET['locale'] ?? 'es');
    set_locale(in_array($loc, config('app.locales'), true) ? $loc : 'es');

    if (str_starts_with($ruta, 'admin/') || $ruta === 'admin') {
        require __DIR__ . '/api_admin.php';
        api_admin_despachar(substr($ruta, 6) ?: '', $metodo);
    }

    match (true) {
        $ruta === 'contacto' && $metodo === 'POST' => api_contacto(),
        $ruta === 'newsletter' && $metodo === 'POST' => api_newsletter(),
        $ruta === 'descarga' && $metodo === 'POST' => api_descarga(),
        $ruta === 'descarga-archivo' && $metodo === 'GET' => api_descarga_archivo(),
        $ruta === 'diagnostico' && $metodo === 'POST' => api_diagnostico(),
        $ruta === 'slots' && $metodo === 'GET' => json_out(['ok' => true, 'slots' => slots_disponibles()]),
        $ruta === 'reserva' && $metodo === 'POST' => api_reserva(),
        default => json_error('Ruta no encontrada', 404),
    };
}

/** Validación y normalización de los campos de lead compartidos. */
function validar_lead(array $in, array $obligatorios = ['name', 'email', 'country']): array
{
    if (! empty($in['website'])) {
        json_error('Solicitud rechazada'); // honeypot
    }

    $errores = [];
    $out = [];

    $out['name'] = trim((string) ($in['name'] ?? ''));
    if (in_array('name', $obligatorios) && ($out['name'] === '' || mb_strlen($out['name']) > 160)) {
        $errores['name'] = true;
    }

    $out['email'] = mb_strtolower(trim((string) ($in['email'] ?? '')));
    if ($out['email'] !== '' && ! filter_var($out['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = true;
    }
    if (in_array('email', $obligatorios) && $out['email'] === '') {
        $errores['email'] = true;
    }

    $out['phone_wa'] = preg_replace('/\D+/', '', (string) ($in['phone_wa'] ?? ''));
    if ($out['phone_wa'] !== '' && (strlen($out['phone_wa']) < 8 || strlen($out['phone_wa']) > 15)) {
        $errores['phone_wa'] = true;
    }
    $out['phone_dial'] = substr(preg_replace('/\D+/', '', (string) ($in['phone_dial'] ?? '')), 0, 5);

    $out['country'] = strtoupper(substr(trim((string) ($in['country'] ?? '')), 0, 2));
    if (in_array('country', $obligatorios) && strlen($out['country']) !== 2) {
        $errores['country'] = true;
    }

    $out['company'] = mb_substr(trim((string) ($in['company'] ?? '')), 0, 160);
    if (in_array('company', $obligatorios) && $out['company'] === '') {
        $errores['company'] = true;
    }
    $out['role'] = mb_substr(trim((string) ($in['role'] ?? '')), 0, 120);

    $out['industry'] = $in['industry'] ?? '';
    if ($out['industry'] !== '' && ! isset(config('industries')[$out['industry']])) {
        $errores['industry'] = true;
    }
    if (in_array('industry', $obligatorios) && $out['industry'] === '') {
        $errores['industry'] = true;
    }

    $out['company_size'] = $in['company_size'] ?? '';
    if ($out['company_size'] !== '' && ! isset(config('company_sizes')[$out['company_size']])) {
        $errores['company_size'] = true;
    }
    if (in_array('company_size', $obligatorios) && $out['company_size'] === '') {
        $errores['company_size'] = true;
    }

    if ($errores) {
        json_error(t('form.error_validacion'), 422, ['campos' => array_keys($errores)]);
    }

    $out['locale'] = locale();

    return $out;
}

function api_contacto(): never
{
    throttle('contacto', 8, 60);
    csrf_verificar();
    $in = input();
    $lead = validar_lead($in, ['name', 'email', 'country', 'company', 'industry', 'company_size']);

    $desafios = t('form.desafios');
    $desafio = $in['desafio'] ?? '';
    if (! isset($desafios[$desafio])) {
        json_error(t('form.error_validacion'), 422, ['campos' => ['desafio']]);
    }

    capturar_lead($lead, 'contacto', 'Solicitó diagnóstico ejecutivo', [
        'desafio' => $desafios[$desafio],
        'mensaje' => mb_substr(trim((string) ($in['mensaje'] ?? '')), 0, 3000),
    ]);

    json_out(['ok' => true]);
}

function api_newsletter(): never
{
    throttle('newsletter', 6, 60);
    csrf_verificar();
    $email = mb_strtolower(trim((string) (input()['email'] ?? '')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! empty(input()['website'])) {
        json_error(t('form.error_validacion'), 422, ['campos' => ['email']]);
    }

    capturar_lead(['name' => $email, 'email' => $email], 'newsletter', 'Se suscribió al newsletter');

    json_out(['ok' => true]);
}

function api_descarga(): never
{
    throttle('descarga', 8, 60);
    csrf_verificar();
    $in = input();

    $st = db()->prepare("SELECT * FROM resources WHERE slug = ? AND type = 'download' AND active = 1");
    $st->execute([(string) ($in['slug'] ?? '')]);
    $recurso = $st->fetch();
    if (! $recurso) {
        json_error('Recurso no encontrado', 404);
    }

    $lead = validar_lead($in, ['name', 'email', 'country']);
    capturar_lead($lead, 'descarga', 'Descargó: ' . tr($recurso['titulo'], 'es'), ['recurso' => $recurso['slug']]);

    db()->prepare('UPDATE resources SET downloads = downloads + 1 WHERE id = ?')->execute([$recurso['id']]);

    $expira = time() + 7 * 86400;
    $url = config('app.url') . '/api/descarga-archivo?slug=' . rawurlencode($recurso['slug'])
        . '&exp=' . $expira . '&sig=' . firmar($recurso['slug'], $expira);

    // Enviar también por correo
    $titulos = ['es' => 'Su recurso está listo', 'en' => 'Your resource is ready', 'pt' => 'Seu recurso está pronto'];
    $botones = ['es' => 'Descargar', 'en' => 'Download', 'pt' => 'Baixar'];
    $l = locale();
    enviar_correo(
        $lead['email'],
        '[ExperientIA] ' . tr($recurso['titulo']),
        '<h2>' . e($titulos[$l] ?? $titulos['es']) . '</h2><p><b>' . e(tr($recurso['titulo'])) . '</b></p>'
        . '<p><a href="' . e($url) . '" style="display:inline-block;background:#18d6f1;color:#041022;padding:10px 22px;border-radius:999px;text-decoration:none;font-weight:bold;">' . e($botones[$l] ?? 'Descargar') . '</a></p>'
    );

    json_out(['ok' => true, 'url' => $url]);
}

function api_descarga_archivo(): never
{
    $slug = (string) ($_GET['slug'] ?? '');
    $exp = (int) ($_GET['exp'] ?? 0);
    $sig = (string) ($_GET['sig'] ?? '');

    if (! firma_valida($slug, $exp, $sig)) {
        json_error('Enlace vencido o inválido', 403);
    }

    $st = db()->prepare("SELECT * FROM resources WHERE slug = ? AND type = 'download'");
    $st->execute([$slug]);
    $recurso = $st->fetch();

    $archivo = dirname(__DIR__, 2) . '/datos/recursos/' . basename($recurso['file_path'] ?? '');
    if (! $recurso || ! is_file($archivo)) {
        json_error('Archivo no encontrado', 404);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9\-]+/', '-', mb_strtolower(tr($recurso['titulo'], 'es'))) . '.pdf"');
    header('Content-Length: ' . filesize($archivo));
    readfile($archivo);
    exit;
}

function api_diagnostico(): never
{
    throttle('diagnostico', 8, 60);
    csrf_verificar();
    $in = input();
    $preguntas = (require dirname(__DIR__, 2) . '/config/diagnostico.php')['preguntas'];

    $lead = validar_lead($in, ['name', 'email', 'country']);

    $scores = ['estrategia' => 0, 'automatizacion' => 0, 'datos' => 0, 'growth' => 0];
    $legibles = [];
    foreach ($preguntas as $p) {
        $idx = $in['respuestas'][$p['id']] ?? null;
        if (! is_numeric($idx) || ! isset($p['opciones'][(int) $idx])) {
            json_error(t('form.error_validacion'), 422, ['campos' => ['respuestas.' . $p['id']]]);
        }
        $opcion = $p['opciones'][(int) $idx];
        foreach ($opcion['scores'] as $k => $pts) {
            $scores[$k] = ($scores[$k] ?? 0) + $pts;
        }
        $legibles[tr($p['texto'], 'es')] = tr($opcion['texto'], 'es');
    }

    arsort($scores);
    $resultado = array_key_first($scores);
    if ($scores[$resultado] === 0) {
        $resultado = 'estrategia';
    }

    $st = db()->prepare('SELECT * FROM solutions WHERE skey = ?');
    $st->execute([$resultado]);
    $solucion = $st->fetch();

    capturar_lead(
        $lead,
        'diagnostico',
        'Completó el diagnóstico → ' . ($solucion ? tr($solucion['titulo'], 'es') : $resultado),
        ['resultado' => $resultado, 'puntajes' => json_encode($scores), 'respuestas' => json_encode($legibles, JSON_UNESCAPED_UNICODE)],
    );

    json_out([
        'ok' => true,
        'resultado' => $resultado,
        'solucion' => $solucion ? [
            'titulo' => tr($solucion['titulo']),
            'pilar' => tr($solucion['pilar']),
            'cambia' => tr($solucion['cambia']),
            'icon' => $solucion['icon'],
        ] : null,
    ]);
}

function api_reserva(): never
{
    throttle('reserva', 6, 60);
    csrf_verificar();
    $in = input();

    $slot = (string) ($in['slot'] ?? '');
    try {
        $inicio = new DateTimeImmutable($slot, new DateTimeZone('UTC'));
    } catch (Throwable) {
        json_error(t('form.error_validacion'), 422, ['campos' => ['slot']]);
    }
    $iso = $inicio->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

    if (! slot_disponible($iso)) {
        json_error(t('agenda.reservado'), 409, ['campos' => ['slot']]);
    }

    $lead = validar_lead($in, ['name', 'email', 'country']);
    $tema = mb_substr(trim((string) ($in['tema'] ?? '')), 0, 2000);

    $cfgTz = new DateTimeZone(config('booking.timezone'));
    $leadRow = capturar_lead(
        $lead,
        'reserva',
        'Agendó sesión 1:1 · ' . $inicio->setTimezone($cfgTz)->format('d/m/Y H:i'),
        ['tema' => $tema],
    );

    db()->prepare('INSERT INTO bookings (lead_id, starts_at, ends_at, status, visitor_timezone, tema, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $leadRow['id'],
            $inicio->format('Y-m-d H:i:s'),
            $inicio->modify('+' . config('booking.slot_minutes') . ' minutes')->format('Y-m-d H:i:s'),
            'confirmada',
            mb_substr((string) ($in['timezone'] ?? ''), 0, 60),
            $tema,
            ahora(),
        ]);

    // Confirmación al visitante
    $tzVisitante = $in['timezone'] ?? config('booking.timezone');
    try {
        $local = $inicio->setTimezone(new DateTimeZone($tzVisitante));
    } catch (Throwable) {
        $local = $inicio->setTimezone($cfgTz);
        $tzVisitante = config('booking.timezone');
    }
    $textos = [
        'es' => ['t' => 'Su sesión 1:1 está confirmada', 'd' => 'Fecha y hora', 'm' => 'Le contactaremos a este correo con el enlace de la reunión.'],
        'en' => ['t' => 'Your 1:1 session is confirmed', 'd' => 'Date and time', 'm' => 'We will contact you at this email with the meeting link.'],
        'pt' => ['t' => 'Sua sessão 1:1 está confirmada', 'd' => 'Data e hora', 'm' => 'Entraremos em contato por este e-mail com o link da reunião.'],
    ][locale()];

    enviar_correo(
        $lead['email'],
        '[ExperientIA] ' . $textos['t'],
        '<h2>' . e($textos['t']) . '</h2><p><b>' . e($textos['d']) . ':</b> ' . $local->format('d/m/Y H:i') . ' (' . e($tzVisitante) . ')</p><p>' . e($textos['m']) . '</p>'
    );

    json_out(['ok' => true]);
}
