<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Validator;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;

/** AlexIA interno: solo usuarios autenticados del portal admin. */
final class ChatController extends Controller
{
    public function mensaje(): void
    {
        $admin = AuthMiddleware::require($this->req);
        $d = Validator::make($this->req->body)->textarea('mensaje', true, 2000)->int('conversation_id')->validated();
        $out = AlexIA::chat('interno', 'web', $d['mensaje'], [
            'admin_id' => (int) $admin['id'],
            'conversation_id' => $d['conversation_id'] ?? null,
            'locale' => 'es',
        ]);
        Response::ok($out);
    }
}
