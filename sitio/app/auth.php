<?php
/** Autenticación del portal administrativo (sesiones PHP). */

function admin_actual(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function admin_login(string $email, string $password): bool
{
    $st = db()->prepare('SELECT * FROM admins WHERE email = ?');
    $st->execute([mb_strtolower(trim($email))]);
    $admin = $st->fetch();

    if (! $admin || ! password_verify($password, $admin['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin'] = ['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email']];
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}

function admin_requerido_api(): void
{
    if (! admin_actual()) {
        json_error('No autorizado', 401);
    }
}
