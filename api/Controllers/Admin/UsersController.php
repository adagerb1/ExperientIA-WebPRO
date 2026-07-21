<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\Response;
use Core\Validator;
use Core\Middleware\AuthMiddleware;

/**
 * Usuarios del portal admin: nombre, rol (owner|admin|staff), estado, y su
 * vinculación de Telegram (ID + si recibe notificaciones del negocio).
 * Ver: cualquier admin. Crear/editar/eliminar: solo el propietario (owner).
 */
final class UsersController extends Controller
{
    private const ROLES = ['owner', 'admin', 'staff'];

    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = Database::run('SELECT id, name, email, role, active, telegram_user_id, notify_telegram, created_at FROM admins ORDER BY id')->fetchAll();
        Response::ok($rows);
    }

    public function store(): void
    {
        $yo = $this->owner();
        $d = Validator::make($this->req->body)->text('name', true, 120)->email('email', true)->text('password', true, 200)->failOrValidated();
        if (mb_strlen($this->req->input('password', '')) < 8) { Response::error('La contraseña debe tener al menos 8 caracteres.', 422); }
        if (Database::run('SELECT COUNT(*) FROM admins WHERE email = ?', [$d['email']])->fetchColumn() > 0) { Response::error('Ya existe un usuario con ese correo.', 422); }
        Database::run('INSERT INTO admins (name, email, password_hash, role, telegram_user_id, notify_telegram, active, created_at) VALUES (?,?,?,?,?,?,1,?)', [
            $d['name'], $d['email'], password_hash((string) $this->req->input('password'), PASSWORD_DEFAULT),
            $this->rol(), $this->tg(), $this->notif(), now_utc(),
        ]);
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function update(string $id): void
    {
        $yo = $this->owner();
        $u = Database::run('SELECT * FROM admins WHERE id = ?', [$id])->fetch();
        if (! $u) { Response::error('Usuario no encontrado.', 404); }
        $d = Validator::make($this->req->body)->text('name', true, 120)->email('email', true)->failOrValidated();

        $rol = $this->rol();
        $activo = (int) ! empty($this->req->input('active', 1));
        // Blindaje: el propietario no puede degradarse ni desactivarse a sí mismo,
        // y siempre debe quedar al menos un owner activo.
        if ((int) $u['id'] === (int) $yo['id'] && ($rol !== 'owner' || ! $activo)) {
            Response::error('No puedes quitarte el rol de propietario ni desactivarte a ti mismo.', 422);
        }
        if ($u['role'] === 'owner' && ($rol !== 'owner' || ! $activo)) {
            $otros = (int) Database::run("SELECT COUNT(*) FROM admins WHERE role = 'owner' AND active = 1 AND id != ?", [$id])->fetchColumn();
            if ($otros === 0) { Response::error('Debe quedar al menos un propietario activo.', 422); }
        }

        $sets = 'name = ?, email = ?, role = ?, telegram_user_id = ?, notify_telegram = ?, active = ?';
        $vals = [$d['name'], $d['email'], $rol, $this->tg(), $this->notif(), $activo];
        $pass = (string) $this->req->input('password', '');
        if ($pass !== '') {
            if (mb_strlen($pass) < 8) { Response::error('La contraseña debe tener al menos 8 caracteres.', 422); }
            $sets .= ', password_hash = ?';
            $vals[] = password_hash($pass, PASSWORD_DEFAULT);
        }
        $vals[] = $id;
        Database::run("UPDATE admins SET {$sets} WHERE id = ?", $vals);
        Response::ok(['message' => 'ok']);
    }

    public function destroy(string $id): void
    {
        $yo = $this->owner();
        if ((int) $id === (int) $yo['id']) { Response::error('No puedes eliminar tu propia cuenta.', 422); }
        $u = Database::run('SELECT role FROM admins WHERE id = ?', [$id])->fetch();
        if (! $u) { Response::error('Usuario no encontrado.', 404); }
        if ($u['role'] === 'owner') {
            $otros = (int) Database::run("SELECT COUNT(*) FROM admins WHERE role = 'owner' AND active = 1 AND id != ?", [$id])->fetchColumn();
            if ($otros === 0) { Response::error('Debe quedar al menos un propietario activo.', 422); }
        }
        Database::run('DELETE FROM admins WHERE id = ?', [$id]);
        Response::ok(['message' => 'ok']);
    }

    private function owner(): array
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        if ($admin['role'] !== 'owner') { Response::error('Solo el propietario puede gestionar usuarios.', 403); }
        return $admin;
    }

    private function rol(): string
    {
        return in_array($r = (string) $this->req->input('role', 'staff'), self::ROLES, true) ? $r : 'staff';
    }

    private function tg(): ?string
    {
        return mb_substr(preg_replace('/\D+/', '', (string) $this->req->input('telegram_user_id', '')), 0, 40) ?: null;
    }

    private function notif(): int
    {
        return (int) ! empty($this->req->input('notify_telegram', 0));
    }
}
