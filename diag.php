<?php
/**
 * Diagnóstico temporal de ExperientIA. Subir a la raíz (public_html), abrir en el
 * navegador (https://tu-dominio/diag.php), copiar la salida y BORRAR el archivo.
 * No expone contraseñas (las enmascara).
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

function line($k, $v) { echo str_pad($k, 26) . ': ' . $v . "\n"; }
function mask($v) { $v = (string) $v; return $v === '' ? '(vacío)' : substr($v, 0, 2) . str_repeat('*', max(1, strlen($v) - 3)) . substr($v, -1); }

echo "==== ExperientIA · Diagnóstico ====\n\n";
line('PHP', PHP_VERSION . ' (' . PHP_SAPI . ')');
line('pdo_mysql', extension_loaded('pdo_mysql') ? 'sí' : 'NO');
line('mbstring', extension_loaded('mbstring') ? 'sí' : 'NO');
line('curl', extension_loaded('curl') ? 'sí' : 'NO');

$root = __DIR__;
$envPath = $root . '/.env';
echo "\n-- .env --\n";
line('Existe .env', is_file($envPath) ? 'sí (' . $envPath . ')' : 'NO EXISTE en ' . $envPath);
line('api/ existe', is_dir($root . '/api') ? 'sí' : 'NO');
line('storage/logs escribible', is_writable($root . '/storage/logs') ? 'sí' : (is_dir($root . '/storage/logs') ? 'no' : 'no existe'));

if (!is_file($envPath)) {
    echo "\n>>> No hay .env. El sitio usa valores por defecto y NO conectará. Crea el .env.\n";
    exit;
}

require_once $root . '/api/Core/Env.php';
Core\Env::load($envPath);
echo "\n-- Variables leídas del .env --\n";
line('APP_ENV', Core\Env::get('APP_ENV'));
line('DB_DRIVER', Core\Env::get('DB_DRIVER'));
line('DB_HOST', Core\Env::get('DB_HOST'));
line('DB_PORT', Core\Env::get('DB_PORT'));
line('DB_NAME', Core\Env::get('DB_NAME'));
line('DB_USER', Core\Env::get('DB_USER'));
line('DB_PASS (enmascarada)', mask(Core\Env::get('DB_PASS')));
line('APP_SECRET presente', Core\Env::get('APP_SECRET') ? 'sí' : 'NO');

echo "\n-- Conexión a la base de datos --\n";
try {
    $driver = Core\Env::get('DB_DRIVER', 'mysql');
    if ($driver === 'sqlite') {
        $pdo = new PDO('sqlite:' . Core\Env::get('DB_SQLITE', $root . '/api/db/dev.sqlite'));
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Core\Env::get('DB_HOST', '127.0.0.1'), Core\Env::get('DB_PORT', '3306'), Core\Env::get('DB_NAME', ''));
        $pdo = new PDO($dsn, Core\Env::get('DB_USER'), Core\Env::get('DB_PASS'));
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "CONEXIÓN OK\n";

    echo "\n-- Tablas y conteos --\n";
    foreach (['admins', 'solutions', 'products', 'case_studies', 'faqs', 'resources', 'rate_limits', 'availability_rules'] as $t) {
        try {
            $n = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            line($t, $n . ' filas');
        } catch (\Throwable $e) {
            line($t, 'ERROR: ' . $e->getMessage());
        }
    }

    echo "\n-- Prueba de consulta real (case_studies) --\n";
    try {
        $r = $pdo->query("SELECT * FROM case_studies WHERE active = 1 ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
        echo 'OK, ' . count($r) . " filas activas\n";
    } catch (\Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
} catch (\Throwable $e) {
    echo "FALLO DE CONEXIÓN:\n";
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "\n-- Último error registrado --\n";
$logs = glob($root . '/storage/logs/error-*.log');
if ($logs) {
    $last = end($logs);
    $lines = file($last, FILE_IGNORE_NEW_LINES);
    foreach (array_slice($lines, -5) as $l) { echo $l . "\n"; }
} else {
    echo "(sin archivos de log)\n";
}

echo "\n==== Fin. BORRA diag.php del servidor. ====\n";
