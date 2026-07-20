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
    'solutions' => ['landing' => 'JSON NULL'],
    'products' => ['landing' => 'JSON NULL'],
    'case_studies' => ['landing' => 'JSON NULL'],
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
        'cover_image' => 'TEXT NULL',
        'audio_path' => 'TEXT NULL',
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

// Automatización: plantillas de campaña y secuencias de nurturing.
try {
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS campaign_templates (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, canal TEXT NOT NULL DEFAULT \'email\', asunto TEXT NULL, cuerpo TEXT NOT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1, updated_at TEXT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequences (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, trigger_status TEXT NOT NULL, active INTEGER NOT NULL DEFAULT 1, created_at TEXT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequence_steps (id INTEGER PRIMARY KEY AUTOINCREMENT, sequence_id INTEGER NOT NULL, orden INTEGER NOT NULL DEFAULT 0, delay_hours INTEGER NOT NULL DEFAULT 0, template_id INTEGER NULL, active INTEGER NOT NULL DEFAULT 1)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequence_enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, sequence_id INTEGER NOT NULL, lead_id INTEGER NOT NULL, step_index INTEGER NOT NULL DEFAULT 0, next_run_at TEXT NULL, status TEXT NOT NULL DEFAULT \'activa\', created_at TEXT NULL, updated_at TEXT NULL)');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS campaign_templates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(160) NOT NULL, canal VARCHAR(20) NOT NULL DEFAULT \'email\', asunto JSON NULL, cuerpo JSON NOT NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequences (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(160) NOT NULL, trigger_status VARCHAR(20) NOT NULL, active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequence_steps (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sequence_id INT UNSIGNED NOT NULL, orden INT NOT NULL DEFAULT 0, delay_hours INT NOT NULL DEFAULT 0, template_id INT UNSIGNED NULL, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS sequence_enrollments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sequence_id INT UNSIGNED NOT NULL, lead_id INT UNSIGNED NOT NULL, step_index INT NOT NULL DEFAULT 0, next_run_at DATETIME NULL, status VARCHAR(20) NOT NULL DEFAULT \'activa\', created_at DATETIME NULL, updated_at DATETIME NULL, INDEX idx_due (status, next_run_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    echo "· tablas de automatización listas\n";
    if ((int) $pdo->query('SELECT COUNT(*) FROM campaign_templates')->fetchColumn() === 0) {
        $seedA = require __DIR__ . '/api/db/seed_automation.php';
        $tids = [];
        foreach ($seedA['templates'] as $t) {
            $pdo->prepare('INSERT INTO campaign_templates (nombre, canal, asunto, cuerpo, sort, active, updated_at) VALUES (?,?,?,?,?,1,?)')
                ->execute([$t['nombre'], $t['canal'], json_encode($t['asunto'], JSON_UNESCAPED_UNICODE), json_encode($t['cuerpo'], JSON_UNESCAPED_UNICODE), count($tids), now_utc()]);
            $tids[] = (int) $pdo->lastInsertId();
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM sequences')->fetchColumn() === 0) {
            $s = $seedA['sequence'];
            $pdo->prepare('INSERT INTO sequences (nombre, trigger_status, active, created_at) VALUES (?,?,?,?)')->execute([$s['nombre'], $s['trigger_status'], $s['active'], now_utc()]);
            $sid = (int) $pdo->lastInsertId();
            $o = 0;
            foreach ($s['steps'] as $st) {
                $pdo->prepare('INSERT INTO sequence_steps (sequence_id, orden, delay_hours, template_id, active) VALUES (?,?,?,?,1)')
                    ->execute([$sid, $o++, $st['delay_hours'], $tids[$st['template_idx']] ?? null]);
            }
        }
        echo "+ automatización sembrada (plantillas + secuencia pausada)\n";
    }
} catch (\Throwable $e) { echo '! automatización: ' . $e->getMessage() . "\n"; }

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
    // Segmentos configurables (categorías de recursos, tamaños, orígenes, canales).
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS segments (id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, skey TEXT NOT NULL, nombre TEXT NOT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1, UNIQUE(kind, skey))');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS segments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(40) NOT NULL, skey VARCHAR(60) NOT NULL, nombre JSON NOT NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1, UNIQUE KEY uniq_segment (kind, skey)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM segments')->fetchColumn() === 0) {
        foreach ($taxo['segments'] ?? [] as $s) {
            $pdo->prepare('INSERT INTO segments (kind, skey, nombre, sort, active) VALUES (?,?,?,?,?)')
                ->execute([$s['kind'], $s['skey'], json_encode($s['nombre'], JSON_UNESCAPED_UNICODE), $s['sort'], $s['active']]);
        }
        echo "+ segmentos sembrados (categorías, tamaños, orígenes, canales)\n";
    }
} catch (\Throwable $e) { echo '! taxonomías: ' . $e->getMessage() . "\n"; }

// GrowthBoard: zonas del método (editables) + resultados de diagnóstico.
try {
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_zones (id INTEGER PRIMARY KEY AUTOINCREMENT, zkey TEXT NOT NULL UNIQUE, linea TEXT NOT NULL, icon TEXT NOT NULL DEFAULT \'target\', nombre TEXT NOT NULL, pregunta TEXT NULL, afirmaciones TEXT NOT NULL, senales TEXT NULL, jugada TEXT NULL, sort INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 1)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_results (id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER NULL, locale TEXT NOT NULL DEFAULT \'es\', total REAL NOT NULL, banda TEXT NOT NULL, linea_debil TEXT NOT NULL, zona_critica TEXT NOT NULL, scores TEXT NOT NULL, contexto TEXT NULL, extras TEXT NULL, created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_plays (id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER NOT NULL, zona TEXT NULL, titulo TEXT NOT NULL, porque TEXT NULL, responsable TEXT NULL, fecha_limite TEXT NULL, indicador TEXT NULL, estado TEXT NOT NULL DEFAULT \'pendiente\', resultado TEXT NULL, notas TEXT NULL, sort INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_checkins (id INTEGER PRIMARY KEY AUTOINCREMENT, lead_id INTEGER NOT NULL, avanzo TEXT NULL, trabo TEXT NULL, dato TEXT NULL, decision TEXT NULL, proxima TEXT NULL, created_at TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gbc_strategies (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, periodo TEXT NOT NULL DEFAULT \'mensual\', source_doc TEXT NULL, brief TEXT NULL, status TEXT NOT NULL DEFAULT \'borrador\', created_at TEXT NOT NULL, updated_at TEXT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gbc_items (id INTEGER PRIMARY KEY AUTOINCREMENT, strategy_id INTEGER NOT NULL, semana INTEGER NOT NULL DEFAULT 1, fecha TEXT NULL, canal TEXT NOT NULL DEFAULT \'linkedin\', formato TEXT NULL, pilar TEXT NULL, tema TEXT NOT NULL, objetivo TEXT NULL, mecanismo TEXT NULL, estado TEXT NOT NULL DEFAULT \'idea\', score INTEGER NULL, copy TEXT NULL, guion TEXT NULL, notas TEXT NULL, sort INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NULL)');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_zones (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, zkey VARCHAR(40) NOT NULL UNIQUE, linea VARCHAR(20) NOT NULL, icon VARCHAR(30) NOT NULL DEFAULT \'target\', nombre JSON NOT NULL, pregunta JSON NULL, afirmaciones JSON NOT NULL, senales JSON NULL, jugada JSON NULL, sort INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_results (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lead_id INT UNSIGNED NULL, locale CHAR(2) NOT NULL DEFAULT \'es\', total DECIMAL(4,1) NOT NULL, banda VARCHAR(20) NOT NULL, linea_debil VARCHAR(20) NOT NULL, zona_critica VARCHAR(40) NOT NULL, scores JSON NOT NULL, contexto JSON NULL, extras JSON NULL, created_at DATETIME NOT NULL, INDEX idx_gb_lead (lead_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_plays (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lead_id INT UNSIGNED NOT NULL, zona VARCHAR(40) NULL, titulo VARCHAR(255) NOT NULL, porque TEXT NULL, responsable VARCHAR(120) NULL, fecha_limite VARCHAR(20) NULL, indicador VARCHAR(255) NULL, estado VARCHAR(20) NOT NULL DEFAULT \'pendiente\', resultado VARCHAR(20) NULL, notas TEXT NULL, sort INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NULL, INDEX idx_gbp_lead (lead_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gb_checkins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lead_id INT UNSIGNED NOT NULL, avanzo TEXT NULL, trabo TEXT NULL, dato TEXT NULL, decision TEXT NULL, proxima TEXT NULL, created_at DATETIME NOT NULL, INDEX idx_gbc_lead (lead_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gbc_strategies (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(190) NOT NULL, periodo VARCHAR(20) NOT NULL DEFAULT \'mensual\', source_doc MEDIUMTEXT NULL, brief JSON NULL, status VARCHAR(20) NOT NULL DEFAULT \'borrador\', created_at DATETIME NOT NULL, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE IF NOT EXISTS gbc_items (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, strategy_id INT UNSIGNED NOT NULL, semana INT NOT NULL DEFAULT 1, fecha VARCHAR(20) NULL, canal VARCHAR(20) NOT NULL DEFAULT \'linkedin\', formato VARCHAR(30) NULL, pilar VARCHAR(30) NULL, tema VARCHAR(255) NOT NULL, objetivo VARCHAR(255) NULL, mecanismo VARCHAR(40) NULL, estado VARCHAR(20) NOT NULL DEFAULT \'idea\', score INT NULL, copy JSON NULL, guion TEXT NULL, notas TEXT NULL, sort INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NULL, INDEX idx_gbc_str (strategy_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM gb_zones')->fetchColumn() === 0) {
        $gb = require __DIR__ . '/api/config/growthboard.php';
        $s = 1;
        foreach ($gb['zonas'] as $zkey => $z) {
            $pdo->prepare('INSERT INTO gb_zones (zkey, linea, icon, nombre, pregunta, afirmaciones, senales, jugada, sort, active) VALUES (?,?,?,?,?,?,?,?,?,1)')
                ->execute([$zkey, $z['linea'], $z['icon'],
                    json_encode($z['nombre'], JSON_UNESCAPED_UNICODE), json_encode($z['pregunta'], JSON_UNESCAPED_UNICODE),
                    json_encode($z['afirmaciones'], JSON_UNESCAPED_UNICODE), json_encode($z['senales'], JSON_UNESCAPED_UNICODE),
                    json_encode($z['jugada'], JSON_UNESCAPED_UNICODE), $s++]);
        }
        echo "+ GrowthBoard: 11 zonas sembradas\n";
    }
} catch (\Throwable $e) { echo '! growthboard: ' . $e->getMessage() . "\n"; }

// Plantillas de email nuevas (p. ej. GrowthBoard): sembrar solo las que falten.
try {
    $extra = require __DIR__ . '/api/db/seed_extra.php';
    foreach ($extra['email_templates'] as $tpl) {
        $ex = $pdo->prepare('SELECT COUNT(*) FROM email_templates WHERE tkey = ?');
        $ex->execute([$tpl['tkey']]);
        if ((int) $ex->fetchColumn() > 0) { continue; }
        $pdo->prepare('INSERT INTO email_templates (tkey, subject, body, active, updated_at) VALUES (?, ?, ?, 1, ?)')
            ->execute([$tpl['tkey'], json_encode($tpl['subject'], JSON_UNESCAPED_UNICODE), json_encode($tpl['body'], JSON_UNESCAPED_UNICODE), now_utc()]);
        echo "+ plantilla de email '{$tpl['tkey']}' sembrada\n";
    }
} catch (\Throwable $e) { echo '! plantillas email: ' . $e->getMessage() . "\n"; }

// Landings de conversión: sembrar copy curado en soluciones/productos existentes.
try {
    require __DIR__ . '/api/db/apply_landings.php';
    $nl = exp_apply_landings($pdo);
    if ($nl > 0) { echo "+ landings sembradas en {$nl} ítem(s)\n"; }
} catch (\Throwable $e) { echo '! landings: ' . $e->getMessage() . "\n"; }

// Portada y audio pasan a trilingües (JSON): ensanchar columnas en MySQL.
if (! $sqlite) {
    foreach (['cover_image', 'audio_path'] as $col) {
        try { $pdo->exec("ALTER TABLE resources MODIFY {$col} TEXT NULL"); }
        catch (\Throwable $e) { /* ya es TEXT o tabla ausente */ }
    }
}

// Los artículos ya publicados (active=1 + published_at) pasan a status='published'.
try {
    $pdo->exec("UPDATE resources SET status = 'published' WHERE (status IS NULL OR status = '' OR status = 'draft') AND active = 1 AND published_at IS NOT NULL");
} catch (\Throwable $e) { /* columna status recién creada podría no requerirlo */ }

echo $total === 0 ? "Sin cambios: la base de datos ya está al día.\n" : "Listo: {$total} columna(s) añadida(s).\n";
echo "BORRE migrate.php del servidor.\n";
