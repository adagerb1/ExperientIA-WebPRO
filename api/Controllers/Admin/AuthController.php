<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Validator;
use Core\Database;
use Core\Token;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;

final class AuthController extends Controller
{
    public function login(): void
    {
        RateLimiter::hit('login:' . $this->req->ip(), 8, 300);
        $d = Validator::make($this->req->body)->email('email', true)->text('password', true, 200)->validated();

        $admin = Database::run('SELECT * FROM admins WHERE email = ? AND active = 1', [$d['email']])->fetch();
        if (! $admin || ! password_verify($this->req->input('password', ''), $admin['password_hash'])) {
            Response::error('Credenciales incorrectas.', 401);
        }
        $token = Token::issue(['sub' => (int) $admin['id'], 'role' => $admin['role']]);
        Response::ok([
            'token' => $token,
            'admin' => ['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => $admin['role']],
        ]);
    }

    public function me(): void
    {
        $admin = AuthMiddleware::require($this->req);
        Response::ok(['admin' => $admin]);
    }
}
