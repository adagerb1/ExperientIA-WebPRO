<?php
/**
 * Migraciones idempotentes de ExperientIA. Añade columnas nuevas a bases de datos
 * ya instaladas, sin tocar los datos existentes.
 *
 *   • Navegador:  https://tu-dominio/migrate.php
 *   • Consola:    php migrate.php
 *
 * Seguro de ejecutar varias veces. BORRAR del servidor cuando termines.
 */
$cli = PHP_SAPI === 'cli';
if (! $cli) { header('Content-Type: text/plain; charset=utf-8'); }

require __DIR__ . '/api/bootstrap.php';
require __DIR__ . '/api/helpers.php';

use Core\Database;

$pdo = Database::pdo();
$sqlite = Database::isSqlite();

/** Columnas deseadas por tabla: nombre => tipo MySQL. */
$deseadas = [
    'leads' => [
        'utm_source' => 'VARCHAR(120) NULL',
        'utm_medium' => 'VARCHAR(120) NULL',
        'utm_campaign' => 'VARCHAR(160) NULL',
        'utm_content' => 'VARCHAR(160) NULL',
        'utm_term' => 'VARCHAR(160) NULL',
        'referrer' => 'VARCHAR(255) NULL',
        'landing_page' => 'VARCHAR(255) NULL',
    ],
    'resources' => [
        'categories' => 'JSON NULL',
        'author' => 'VARCHAR(120) NULL',
        'read_minutes' => 'INT NOT NULL DEFAULT 5',
        'cover_image' => 'VARCHAR(255) NULL',
        'audio_path' => 'VARCHAR(255) NULL',
        'video_url' => 'VARCHAR(500) NULL',
        'gated' => 'TINYINT NOT NULL DEFAULT 0',
        'featured' => 'TINYINT NOT NULL DEFAULT 0',
        'seo_title' => 'JSON NULL',
        'seo_desc' => 'JSON NULL',
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'draft'",
    ],
];

/** Adapta un tipo MySQL a SQLite. */
function tipoSqlite(string $t): string
{
    $t = preg_replace('/\bJSON\b/', 'TEXT', $t);
    $t = preg_replace('/\bTINYINT\b/', 'INTEGER', $t);
    $t = preg_replace('/\bINT\b/', 'INTEGER', $t);
    return $t;
}

/** Columnas existentes de una tabla (portable). */
function columnas(PDO $pdo, bool $sqlite, string $tabla): array
{
    if ($sqlite) {
        $rows = $pdo->query("PRAGMA table_info({$tabla})")->fetchAll(PDO::FETCH_ASSOC);
        return array_column($rows, 'name');
    }
    $rows = $pdo->query("SHOW COLUMNS FROM {$tabla}")->fetchAll(PDO::FETCH_ASSOC);
    return array_column($rows, 'Field');
}

$total = 0;
foreach ($deseadas as $tabla => $cols) {
    try {
        $existentes = columnas($pdo, $sqlite, $tabla);
    } catch (\Throwable $e) {
        echo "! Tabla {$tabla} no existe (ejecuta install.php primero).\n";
        continue;
    }
    foreach ($cols as $col => $tipo) {
        if (in_array($col, $existentes, true)) { continue; }
        $ddl = $sqlite ? tipoSqlite($tipo) : $tipo;
        $pdo->exec("ALTER TABLE {$tabla} ADD COLUMN {$col} {$ddl}");
        echo "+ {$tabla}.{$col} añadida\n";
        $total++;
    }
}

// Tabla de diagnósticos dinámicos (crear si falta + sembrar si está vacía).
try {
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS diagnostics (
            id INTEGER PRIMARY KEY AUTOINCREMENT, dkey TEXT NOT NULL UNIQUE, icon TEXT NOT NULL DEFAULT \'target\',
            nombre TEXT NOT NULL, intro TEXT NULL, preguntas TEXT NOT NULL, resultados TEXT NOT NULL,
            default_result TEXT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS diagnostics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dkey VARCHAR(40) NOT NULL UNIQUE, icon VARCHAR(30) NOT NULL DEFAULT \'target\',
            nombre JSON NOT NULL, intro JSON NULL, preguntas JSON NOT NULL, resultados JSON NOT NULL,
            default_result VARCHAR(40) NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1)
            ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    foreach (require __DIR__ . '/api/db/seed_diagnostics.php' as $dg) {
        $ex = $pdo->prepare('SELECT COUNT(*) FROM diagnostics WHERE dkey = ?');
        $ex->execute([$dg['dkey']]);
        if ((int) $ex->fetchColumn() > 0) { continue; }
        $row = ['dkey' => $dg['dkey'], 'icon' => $dg['icon'],
            'nombre' => json_encode($dg['nombre'], JSON_UNESCAPED_UNICODE),
            'intro' => json_encode($dg['intro'] ?? null, JSON_UNESCAPED_UNICODE),
            'preguntas' => json_encode($dg['preguntas'], JSON_UNESCAPED_UNICODE),
            'resultados' => json_encode($dg['resultados'], JSON_UNESCAPED_UNICODE),
            'default_result' => $dg['default_result'] ?? null, 'sort' => $dg['sort'] ?? 0, 'active' => $dg['active'] ?? 1];
        $cols = implode(',', array_keys($row));
        $ph = implode(',', array_fill(0, count($row), '?'));
        $pdo->prepare("INSERT INTO diagnostics ({$cols}) VALUES ({$ph})")->execute(array_values($row));
        echo "+ diagnóstico '{$dg['dkey']}' sembrado\n";
    }
} catch (\Throwable $e) { echo "! diagnostics: " . $e->getMessage() . "\n"; }

// Taxonomías: industrias y países (crear tablas si faltan + sembrar si vacías).
try {
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS industries (id INTEGER PRIMARY KEY AUTOINCREMENT, ikey TEXT NOT NULL UNIQUE, nombre TEXT NOT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS countries (id INTEGER PRIMARY KEY AUTOINCREMENT, iso TEXT NOT NULL UNIQUE, nombre TEXT NOT NULL, dial TEXT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS industries (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ikey VARCHAR(40) NOT NULL UNIQUE, nombre JSON NOT NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS countries (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, iso CHAR(2) NOT NULL UNIQUE, nombre JSON NOT NULL, dial VARCHAR(8) NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    $taxo = require __DIR__ . '/api/db/seed_taxonomies.php';
    if ((int) $pdo->query('SELECT COUNT(*) FROM industries')->fetchColumn() === 0) {
        foreach ($taxo['industries'] as $it) {
            $pdo->prepare('INSERT INTO industries (ikey, nombre, sort, active) VALUES (?,?,?,?)')
                ->execute([$it['ikey'], json_encode($it['nombre'], JSON_UNESCAPED_UNICODE), $it['sort'], $it['active']]);
        }
        echo "+ industrias sembradas\n";
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM countries')->fetchColumn() === 0) {
        foreach ($taxo['countries'] as $c) {
            $pdo->prepare('INSERT INTO countries (iso, nombre, dial, sort, active) VALUES (?,?,?,?,?)')
                ->execute([$c['iso'], json_encode($c['nombre'], JSON_UNESCAPED_UNICODE), $c['dial'], $c['sort'], $c['active']]);
        }
        echo "+ países sembrados\n";
    }
} catch (\Throwable $e) { echo '! taxonomías: ' . $e->getMessage() . "\n"; }

// Los artículos ya publicados (active=1 + published_at) pasan a status='published'.
try {
    $pdo->exec("UPDATE resources SET status = 'published' WHERE (status IS NULL OR status = '' OR status = 'draft') AND active = 1 AND published_at IS NOT NULL");
} catch (\Throwable $e) { /* columna status recién creada podría no requerirlo */ }

echo $total === 0 ? "Sin cambios: la base de datos ya está al día.\n" : "Listo: {$total} columna(s) añadida(s).\n";
echo "BORRE migrate.php del servidor.\n";
