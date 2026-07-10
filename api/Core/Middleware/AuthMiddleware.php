<?php
namespace Core\Middleware;

use Core\Request;
use Core\Response;
use Core\Token;
use Core\Database;

/** Autenticación por Bearer Token. Protege cada endpoint del panel. */
final class AuthMiddleware
{
    /** Devuelve el admin autenticado o corta con 401. Opcionalmente exige rol. */
    public static function require(Request $req, ?string $rol = null): array
    {
        $payload = Token::verify($req->bearerToken());
        if (! $payload || ($payload['sub'] ?? null) === null) {
            Response::error('Sesión no válida o expirada. Inicie sesión de nuevo.', 401);
        }

        $st = Database::run('SELECT id, name, email, role, active FROM admins WHERE id = ?', [$payload['sub']]);
        $admin = $st->fetch();
        if (! $admin || ! (int) $admin['active']) {
            Response::error('Cuenta no disponible.', 401);
        }

        if ($rol !== null && $admin['role'] !== 'owner' && $admin['role'] !== $rol) {
            Response::error('Tu rol no permite esta acción. Solicita acceso a un administrador.', 403);
        }

        return $admin;
    }

    /** Igual pero para el bot interno de Telegram (usuario ya vinculado). */
    public static function optional(Request $req): ?array
    {
        $payload = Token::verify($req->bearerToken());
        if (! $payload) {
            return null;
        }
        $st = Database::run('SELECT id, name, email, role FROM admins WHERE id = ? AND active = 1', [$payload['sub'] ?? 0]);
        return $st->fetch() ?: null;
    }
}
