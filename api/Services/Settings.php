<?php
namespace Services;

use Core\Database;

/**
 * Ajustes de aplicación clave/valor (tabla app_settings). Ligero, con caché en
 * memoria por request. Usado, p. ej., para el interruptor de auto-respuesta de
 * AlexIA a las reseñas de Google.
 */
final class Settings
{
    private static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) { return self::$cache[$key]; }
        try {
            $row = Database::run('SELECT sval FROM app_settings WHERE skey = ?', [$key])->fetch();
            $val = $row ? $row['sval'] : $default;
        } catch (\Throwable $e) { $val = $default; }
        return self::$cache[$key] = $val;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key, $default ? '1' : '0');
        return $v === '1' || $v === 'true';
    }

    public static function set(string $key, string $value): void
    {
        $sql = Database::isSqlite()
            ? 'INSERT INTO app_settings (skey, sval, updated_at) VALUES (?,?,?) ON CONFLICT(skey) DO UPDATE SET sval=excluded.sval, updated_at=excluded.updated_at'
            : 'INSERT INTO app_settings (skey, sval, updated_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval), updated_at=VALUES(updated_at)';
        Database::run($sql, [$key, $value, now_utc()]);
        self::$cache[$key] = $value;
    }
}
