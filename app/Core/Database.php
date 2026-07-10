<?php
namespace Core;

use PDO;
use PDOException;

/** Conexión PDO única con prepared statements (protección anti SQL injection). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $driver = Env::get('DB_DRIVER', 'mysql');

        try {
            if ($driver === 'sqlite') {
                $path = Env::get('DB_SQLITE', dirname(__DIR__) . '/db/dev.sqlite');
                self::$pdo = new PDO('sqlite:' . $path);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    Env::get('DB_HOST', '127.0.0.1'),
                    Env::get('DB_PORT', '3306'),
                    Env::get('DB_NAME', 'experientia')
                );
                self::$pdo = new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ]);
            }
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new \RuntimeException('No fue posible conectar con la base de datos.', 500, $e);
        }

        return self::$pdo;
    }

    /** Ejecuta una consulta preparada y devuelve el statement. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function isSqlite(): bool
    {
        return Env::get('DB_DRIVER', 'mysql') === 'sqlite';
    }
}
