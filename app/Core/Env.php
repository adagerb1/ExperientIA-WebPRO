<?php
namespace Core;

/** Cargador de variables de entorno desde .env (sin dependencias). */
final class Env
{
    private static array $data = [];

    public static function load(string $path): void
    {
        if (! is_file($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            // Quitar comentarios en línea y comillas
            if (($pos = strpos($value, ' #')) !== false) {
                $value = substr($value, 0, $pos);
            }
            $value = trim($value, "\"' \t");
            self::$data[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $v = self::$data[$key] ?? getenv($key);
        return ($v === false || $v === null || $v === '') ? $default : $v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return $v === null ? $default : (int) $v;
    }
}
