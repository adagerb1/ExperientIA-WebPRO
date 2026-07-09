<?php
/**
 * Instalador (una sola vez): crea tablas, siembra contenido inicial y crea
 * el usuario del portal admin. Ejecutar por consola:
 *
 *   php instalar.php "Nombre Admin" correo@experientia.pro "contraseña"
 *
 * IMPORTANTE: borrar este archivo del servidor después de usarlo.
 */

if (PHP_SAPI !== 'cli') {
    exit("Ejecutar por consola.\n");
}

require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/db.php';

db_migrate();
echo "Tablas creadas/verificadas.\n";

// Semillas de contenido (solo si las tablas están vacías)
$semillas = require __DIR__ . '/datos/semillas.php';
foreach ($semillas as $tabla => $filas) {
    $count = (int) db()->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
    if ($count > 0 || ! $filas) {
        echo "- {$tabla}: ya tiene datos ({$count}), se omite.\n";
        continue;
    }
    foreach ($filas as $fila) {
        $cols = implode(', ', array_keys($fila));
        $marks = implode(', ', array_fill(0, count($fila), '?'));
        db()->prepare("INSERT INTO {$tabla} ({$cols}) VALUES ({$marks})")->execute(array_values($fila));
    }
    echo "- {$tabla}: " . count($filas) . " registros sembrados.\n";
}

// Usuario administrador
[$_, $nombre, $correo, $clave] = array_pad($argv, 4, null);
if ($nombre && $correo && $clave) {
    $existe = db()->prepare('SELECT COUNT(*) FROM admins WHERE email = ?');
    $existe->execute([mb_strtolower($correo)]);
    if ((int) $existe->fetchColumn() === 0) {
        db()->prepare('INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)')
            ->execute([$nombre, mb_strtolower($correo), password_hash($clave, PASSWORD_DEFAULT), gmdate('Y-m-d H:i:s')]);
        echo "Admin creado: {$correo}\n";
    } else {
        echo "El admin {$correo} ya existe.\n";
    }
} else {
    echo "Sin datos de admin (pasar: nombre correo contraseña). Puede ejecutarse de nuevo.\n";
}

echo "Listo. Recuerde BORRAR instalar.php del servidor.\n";
