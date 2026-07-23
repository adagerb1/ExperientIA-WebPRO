<?php
namespace Core;

/** Rate limiting por ventana (IP para público, usuario para autenticado). */
final class RateLimiter
{
    /** @return array{remaining:int,limit:int,reset:int} */
    public static function hit(string $clave, int $max, int $ventanaSeg = 60): array
    {
        $pdo = Database::pdo();
        $ahora = time();
        $k = substr(hash('sha256', $clave), 0, 40);

        $st = $pdo->prepare('SELECT hits, reset_at FROM rate_limits WHERE k = ?');
        $st->execute([$k]);
        $row = $st->fetch();

        if (! $row || (int) $row['reset_at'] <= $ahora) {
            $reset = $ahora + $ventanaSeg;
            $pdo->prepare(
                Database::isSqlite()
                    ? 'INSERT INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?) ON CONFLICT(k) DO UPDATE SET hits = 1, reset_at = excluded.reset_at'
                    : 'INSERT INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits = 1, reset_at = VALUES(reset_at)'
            )->execute([$k, $reset]);
            return ['remaining' => $max - 1, 'limit' => $max, 'reset' => $reset];
        }

        $hits = (int) $row['hits'];
        if ($hits >= $max) {
            $retry = max(1, (int) $row['reset_at'] - $ahora);
            Response::error('Demasiadas solicitudes. Intente de nuevo en unos segundos.', 429, ['retry_after' => $retry]);
        }

        $pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE k = ?')->execute([$k]);
        return ['remaining' => $max - $hits - 1, 'limit' => $max, 'reset' => (int) $row['reset_at']];
    }

    public static function public(Request $req): void
    {
        $r = self::hit('pub:' . $req->ip() . ':' . $req->path, Env::int('RATELIMIT_PUBLIC', 60), 60);
        header('X-RateLimit-Limit: ' . $r['limit']);
        header('X-RateLimit-Remaining: ' . max(0, $r['remaining']));
    }

    public static function user(int $userId, Request $req): void
    {
        self::hit('usr:' . $userId, Env::int('RATELIMIT_AUTH', 180), 60);
    }
}
