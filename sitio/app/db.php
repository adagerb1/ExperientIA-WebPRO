<?php
/** Conexión PDO única + creación de tablas (MySQL en producción, SQLite en desarrollo). */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = config('db');

    if ($cfg['driver'] === 'sqlite') {
        $pdo = new PDO('sqlite:' . $cfg['sqlite_path']);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4",
            $cfg['user'],
            $cfg['pass'],
            [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"]
        );
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

/** Crea todas las tablas si no existen. Idempotente. */
function db_migrate(): void
{
    $pdo = db();
    $sqlite = config('db')['driver'] === 'sqlite';

    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED PRIMARY KEY AUTO_INCREMENT';
    $now = 'DATETIME';
    $suffix = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $tables = [
        "CREATE TABLE IF NOT EXISTS leads (
            id {$id},
            name VARCHAR(160) NOT NULL,
            email VARCHAR(190) NULL,
            phone_wa VARCHAR(20) NULL,
            phone_dial VARCHAR(5) NULL,
            country CHAR(2) NULL,
            company VARCHAR(160) NULL,
            role VARCHAR(120) NULL,
            industry VARCHAR(60) NULL,
            company_size VARCHAR(20) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'nuevo',
            source VARCHAR(30) NULL,
            locale CHAR(2) NOT NULL DEFAULT 'es',
            notes TEXT NULL,
            created_at {$now} NOT NULL,
            updated_at {$now} NOT NULL
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS touchpoints (
            id {$id},
            lead_id INT" . ($sqlite ? '' : ' UNSIGNED') . " NOT NULL,
            type VARCHAR(30) NOT NULL,
            title VARCHAR(255) NOT NULL,
            payload TEXT NULL,
            created_at {$now} NOT NULL
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS solutions (
            id {$id},
            skey VARCHAR(40) NOT NULL UNIQUE,
            icon VARCHAR(30) NOT NULL DEFAULT 'target',
            titulo TEXT NOT NULL,
            pilar TEXT NOT NULL,
            problema TEXT NOT NULL,
            como TEXT NOT NULL,
            cambia TEXT NOT NULL,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS products (
            id {$id},
            icon VARCHAR(30) NOT NULL DEFAULT 'cube',
            nombre TEXT NOT NULL,
            rol TEXT NOT NULL,
            texto TEXT NOT NULL,
            destacado TINYINT NOT NULL DEFAULT 0,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS case_studies (
            id {$id},
            sector TEXT NOT NULL,
            titulo TEXT NOT NULL,
            contexto TEXT NOT NULL,
            intervencion TEXT NOT NULL,
            resultados TEXT NOT NULL,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS faqs (
            id {$id},
            pregunta TEXT NOT NULL,
            respuesta TEXT NOT NULL,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS resources (
            id {$id},
            slug VARCHAR(190) NOT NULL UNIQUE,
            type VARCHAR(20) NOT NULL DEFAULT 'article',
            tipo_label TEXT NOT NULL,
            titulo TEXT NOT NULL,
            extracto TEXT NOT NULL,
            cuerpo TEXT NULL,
            file_path VARCHAR(255) NULL,
            downloads INT NOT NULL DEFAULT 0,
            sort INT NOT NULL DEFAULT 0,
            active TINYINT NOT NULL DEFAULT 1,
            published_at {$now} NULL
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS availability_rules (
            id {$id},
            weekday TINYINT NOT NULL,
            start_time VARCHAR(5) NOT NULL,
            end_time VARCHAR(5) NOT NULL,
            active TINYINT NOT NULL DEFAULT 1
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS bookings (
            id {$id},
            lead_id INT" . ($sqlite ? '' : ' UNSIGNED') . " NOT NULL,
            starts_at {$now} NOT NULL,
            ends_at {$now} NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'confirmada',
            visitor_timezone VARCHAR(60) NULL,
            tema TEXT NULL,
            created_at {$now} NOT NULL
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS admins (
            id {$id},
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at {$now} NOT NULL
        ){$suffix}",
        "CREATE TABLE IF NOT EXISTS throttle (
            k VARCHAR(120) NOT NULL PRIMARY KEY,
            hits INT NOT NULL DEFAULT 0,
            reset_at INT NOT NULL
        ){$suffix}",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}
