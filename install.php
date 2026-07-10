<?php
/**
 * Instalador de un solo uso (CLI): crea tablas, siembra contenido y
 * crea el usuario propietario del portal. Borrar tras ejecutar.
 *
 *   php install.php "Nombre" correo@dominio "contraseña"
 */
if (PHP_SAPI !== 'cli') { exit("Solo por consola.\n"); }

require __DIR__ . '/api/bootstrap.php';
require __DIR__ . '/api/helpers.php';

use Core\Database;

$pdo = Database::pdo();
$sqlite = Database::isSqlite();

// 1) Esquema (adaptado a SQLite en desarrollo)
$sql = file_get_contents(__DIR__ . '/api/db/schema.sql');
if ($sqlite) {
    $sql = preg_replace('/ENGINE=InnoDB[^;]*/', '', $sql);
    $sql = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/\bINT UNSIGNED\b/', 'INTEGER', $sql);
    $sql = preg_replace('/\bBIGINT\b/', 'INTEGER', $sql);
    $sql = preg_replace('/\bTINYINT\b/', 'INTEGER', $sql);
    $sql = preg_replace('/\bJSON\b/', 'TEXT', $sql);
    $sql = preg_replace('/\bMEDIUMTEXT\b/', 'TEXT', $sql);
    $sql = preg_replace('/,\s*INDEX\s+\w+\s*\([^)]*\)/', '', $sql);
}
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt !== '') { $pdo->exec($stmt); }
}
echo "Tablas listas.\n";

// 2) Contenido (soluciones, productos, casos, faqs, recursos, disponibilidad)
$semillas = require __DIR__ . '/api/db/semillas.php';
$map = ['solutions' => 'solutions', 'products' => 'products', 'case_studies' => 'case_studies', 'faqs' => 'faqs', 'resources' => 'resources', 'availability_rules' => 'availability_rules'];
foreach ($map as $tabla => $_) {
    if ((int) $pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn() > 0) { echo "- {$tabla}: ya tiene datos.\n"; continue; }
    foreach ($semillas[$tabla] ?? [] as $fila) {
        $cols = implode(',', array_keys($fila));
        $ph = implode(',', array_fill(0, count($fila), '?'));
        $pdo->prepare("INSERT INTO {$tabla} ({$cols}) VALUES ({$ph})")->execute(array_values($fila));
    }
    echo "- {$tabla}: " . count($semillas[$tabla] ?? []) . " sembrados.\n";
}

// 3) Conectores + plantillas de email
$extra = require __DIR__ . '/api/db/seed_extra.php';
foreach ($extra['connectors'] as $prov) {
    $pdo->prepare('INSERT INTO connectors (provider, enabled, status, updated_at) VALUES (?, 0, ?, ?)'
        . ($sqlite ? ' ON CONFLICT(provider) DO NOTHING' : ' ON DUPLICATE KEY UPDATE provider = provider'))
        ->execute([$prov, 'sin_configurar', now_utc()]);
}
foreach ($extra['email_templates'] as $tpl) {
    $exists = $pdo->prepare('SELECT COUNT(*) FROM email_templates WHERE tkey = ?');
    $exists->execute([$tpl['tkey']]);
    if ((int) $exists->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO email_templates (tkey, subject, body, active, updated_at) VALUES (?, ?, ?, 1, ?)')
            ->execute([$tpl['tkey'], json_encode($tpl['subject'], JSON_UNESCAPED_UNICODE), json_encode($tpl['body'], JSON_UNESCAPED_UNICODE), now_utc()]);
    }
}
echo "Conectores y plantillas listos.\n";

// 4) Admin propietario
[$_, $nombre, $correo, $clave] = array_pad($argv, 4, null);
if ($nombre && $correo && $clave) {
    $e = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE email = ?');
    $e->execute([mb_strtolower($correo)]);
    if ((int) $e->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, active, created_at) VALUES (?, ?, ?, ?, 1, ?)')
            ->execute([$nombre, mb_strtolower($correo), password_hash($clave, PASSWORD_DEFAULT), 'owner', now_utc()]);
        echo "Propietario creado: {$correo}\n";
    } else { echo "El admin {$correo} ya existe.\n"; }
}
echo "Listo. BORRE install.php del servidor.\n";
