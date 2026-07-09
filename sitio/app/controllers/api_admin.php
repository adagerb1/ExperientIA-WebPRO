<?php
/**
 * API del portal administrativo (/api/admin/*). Requiere sesión, salvo login.
 * CRUD genérico de contenido + CRM de leads + reservas + disponibilidad.
 */

/** Tablas gestionables por el CRUD genérico y sus campos permitidos. */
function admin_tablas(): array
{
    return [
        'solutions' => ['skey', 'icon', 'titulo', 'pilar', 'problema', 'como', 'cambia', 'sort', 'active'],
        'products' => ['icon', 'nombre', 'rol', 'texto', 'destacado', 'sort', 'active'],
        'case_studies' => ['sector', 'titulo', 'contexto', 'intervencion', 'resultados', 'sort', 'active'],
        'faqs' => ['pregunta', 'respuesta', 'sort', 'active'],
        'resources' => ['slug', 'type', 'tipo_label', 'titulo', 'extracto', 'cuerpo', 'file_path', 'sort', 'active', 'published_at'],
        'availability_rules' => ['weekday', 'start_time', 'end_time', 'active'],
    ];
}

function api_admin_despachar(string $ruta, string $metodo): never
{
    // --- Autenticación ---
    if ($ruta === 'login' && $metodo === 'POST') {
        throttle('admin-login', 6, 300);
        csrf_verificar();
        $in = input();
        if (admin_login((string) ($in['email'] ?? ''), (string) ($in['password'] ?? ''))) {
            json_out(['ok' => true, 'admin' => admin_actual()]);
        }
        json_error('Credenciales incorrectas', 401);
    }

    admin_requerido_api();
    csrf_verificar();

    if ($ruta === 'logout' && $metodo === 'POST') {
        admin_logout();
        json_out(['ok' => true]);
    }

    if ($ruta === 'resumen' && $metodo === 'GET') {
        api_admin_resumen();
    }

    // --- Leads (CRM) ---
    if ($ruta === 'leads' && $metodo === 'GET') {
        api_admin_leads_lista();
    }
    if (preg_match('#^leads/(\d+)$#', $ruta, $m)) {
        match ($metodo) {
            'GET' => api_admin_lead_detalle((int) $m[1]),
            'PATCH', 'PUT' => api_admin_lead_actualizar((int) $m[1]),
            'DELETE' => api_admin_borrar('leads', (int) $m[1]),
            default => json_error('Método no permitido', 405),
        };
    }

    // --- Reservas ---
    if ($ruta === 'reservas' && $metodo === 'GET') {
        $rows = db()->query(
            'SELECT b.*, l.name AS lead_name, l.company AS lead_company, l.email AS lead_email
             FROM bookings b JOIN leads l ON l.id = b.lead_id ORDER BY b.starts_at DESC LIMIT 200'
        )->fetchAll();
        json_out(['ok' => true, 'items' => $rows]);
    }
    if (preg_match('#^reservas/(\d+)$#', $ruta, $m) && in_array($metodo, ['PATCH', 'PUT'])) {
        $estado = input()['status'] ?? '';
        if (! in_array($estado, ['confirmada', 'realizada', 'cancelada'], true)) {
            json_error('Estado inválido');
        }
        db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$estado, (int) $m[1]]);
        json_out(['ok' => true]);
    }

    // --- Subida de PDF para recursos descargables ---
    if ($ruta === 'archivo' && $metodo === 'POST') {
        api_admin_subir_archivo();
    }

    // --- CRUD genérico de contenido ---
    $tablas = admin_tablas();
    if (preg_match('#^([a-z_]+)(?:/(\d+))?$#', $ruta, $m) && isset($tablas[$m[1]])) {
        $tabla = $m[1];
        $id = isset($m[2]) ? (int) $m[2] : null;
        match (true) {
            $metodo === 'GET' && $id === null => api_admin_lista($tabla),
            $metodo === 'POST' && $id === null => api_admin_crear($tabla),
            in_array($metodo, ['PUT', 'PATCH']) && $id !== null => api_admin_actualizar($tabla, $id),
            $metodo === 'DELETE' && $id !== null => api_admin_borrar($tabla, $id),
            default => json_error('Método no permitido', 405),
        };
    }

    json_error('Ruta no encontrada', 404);
}

function api_admin_resumen(): never
{
    $q = fn (string $sql) => (int) db()->query($sql)->fetchColumn();
    json_out(['ok' => true, 'resumen' => [
        'leads' => $q('SELECT COUNT(*) FROM leads'),
        'leads_nuevos' => $q("SELECT COUNT(*) FROM leads WHERE status = 'nuevo'"),
        'interacciones' => $q('SELECT COUNT(*) FROM touchpoints'),
        'reservas_proximas' => $q("SELECT COUNT(*) FROM bookings WHERE status = 'confirmada' AND starts_at >= '" . ahora() . "'"),
        'descargas' => $q('SELECT COALESCE(SUM(downloads),0) FROM resources'),
    ]]);
}

function api_admin_leads_lista(): never
{
    $where = [];
    $params = [];
    if (! empty($_GET['status'])) {
        $where[] = 'status = ?';
        $params[] = $_GET['status'];
    }
    if (! empty($_GET['q'])) {
        $where[] = '(name LIKE ? OR email LIKE ? OR company LIKE ?)';
        $q = '%' . $_GET['q'] . '%';
        array_push($params, $q, $q, $q);
    }
    $sql = 'SELECT l.*, (SELECT COUNT(*) FROM touchpoints t WHERE t.lead_id = l.id) AS touchpoints
            FROM leads l' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
            ORDER BY l.updated_at DESC LIMIT 300';
    $st = db()->prepare($sql);
    $st->execute($params);
    json_out(['ok' => true, 'items' => $st->fetchAll()]);
}

function api_admin_lead_detalle(int $id): never
{
    $st = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $st->execute([$id]);
    $lead = $st->fetch();
    if (! $lead) {
        json_error('Lead no encontrado', 404);
    }

    $tp = db()->prepare('SELECT * FROM touchpoints WHERE lead_id = ? ORDER BY created_at DESC');
    $tp->execute([$id]);
    $rv = db()->prepare('SELECT * FROM bookings WHERE lead_id = ? ORDER BY starts_at DESC');
    $rv->execute([$id]);

    json_out(['ok' => true, 'lead' => $lead, 'touchpoints' => $tp->fetchAll(), 'reservas' => $rv->fetchAll()]);
}

function api_admin_lead_actualizar(int $id): never
{
    $in = input();
    $campos = [];
    $params = [];
    if (isset($in['status']) && isset(config('lead_statuses')[$in['status']])) {
        $campos[] = 'status = ?';
        $params[] = $in['status'];
    }
    if (array_key_exists('notes', $in)) {
        $campos[] = 'notes = ?';
        $params[] = mb_substr((string) $in['notes'], 0, 5000);
    }
    if (! $campos) {
        json_error('Nada que actualizar');
    }
    $params[] = ahora();
    $params[] = $id;
    db()->prepare('UPDATE leads SET ' . implode(', ', $campos) . ', updated_at = ? WHERE id = ?')->execute($params);
    json_out(['ok' => true]);
}

function api_admin_lista(string $tabla): never
{
    $orden = $tabla === 'availability_rules' ? 'weekday, start_time' : 'sort';
    json_out(['ok' => true, 'items' => db()->query("SELECT * FROM {$tabla} ORDER BY {$orden}")->fetchAll()]);
}

/** Toma del input solo los campos permitidos; serializa arrays (campos trilingües) a JSON. */
function admin_datos_tabla(string $tabla): array
{
    $in = input();
    $datos = [];
    foreach (admin_tablas()[$tabla] as $campo) {
        if (! array_key_exists($campo, $in)) {
            continue;
        }
        $v = $in[$campo];
        $datos[$campo] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
    }
    return $datos;
}

function api_admin_crear(string $tabla): never
{
    $datos = admin_datos_tabla($tabla);
    if (! $datos) {
        json_error('Sin datos');
    }
    $cols = implode(', ', array_keys($datos));
    $marks = implode(', ', array_fill(0, count($datos), '?'));
    db()->prepare("INSERT INTO {$tabla} ({$cols}) VALUES ({$marks})")->execute(array_values($datos));
    json_out(['ok' => true, 'id' => (int) db()->lastInsertId()]);
}

function api_admin_actualizar(string $tabla, int $id): never
{
    $datos = admin_datos_tabla($tabla);
    if (! $datos) {
        json_error('Sin datos');
    }
    $sets = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($datos)));
    db()->prepare("UPDATE {$tabla} SET {$sets} WHERE id = ?")->execute([...array_values($datos), $id]);
    json_out(['ok' => true]);
}

function api_admin_borrar(string $tabla, int $id): never
{
    if ($tabla === 'leads') {
        db()->prepare('DELETE FROM touchpoints WHERE lead_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM bookings WHERE lead_id = ?')->execute([$id]);
    }
    db()->prepare("DELETE FROM {$tabla} WHERE id = ?")->execute([$id]);
    json_out(['ok' => true]);
}

function api_admin_subir_archivo(): never
{
    $archivo = $_FILES['archivo'] ?? null;
    if (! $archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
        json_error('No se recibió el archivo');
    }
    if ($archivo['size'] > 25 * 1024 * 1024) {
        json_error('El archivo supera 25 MB');
    }
    $mime = mime_content_type($archivo['tmp_name']);
    if ($mime !== 'application/pdf') {
        json_error('Solo se permiten PDF');
    }

    $nombre = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9\-_]+/', '-', mb_strtolower(pathinfo($archivo['name'], PATHINFO_FILENAME))) . '.pdf';
    $destino = dirname(__DIR__, 2) . '/datos/recursos/' . $nombre;

    if (! move_uploaded_file($archivo['tmp_name'], $destino)) {
        json_error('No se pudo guardar el archivo', 500);
    }

    json_out(['ok' => true, 'file_path' => $nombre]);
}
