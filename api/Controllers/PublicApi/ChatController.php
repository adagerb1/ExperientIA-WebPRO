<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Services\AlexIA;

/** AlexIA comercial en la web (widget del sitio público). */
final class ChatController extends Controller
{
    public function mensaje(): void
    {
        RateLimiter::hit('alexia:' . $this->req->ip(), 20, 60);
        $v = Validator::make($this->req->body)->honeypot()->textarea('mensaje', true, 2000)->int('conversation_id');
        $d = $v->failOrValidated();
        $locale = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';

        $out = AlexIA::chat('comercial', 'web', $d['mensaje'], [
            'locale' => $locale,
            'conversation_id' => $d['conversation_id'] ?? null,
        ]);
        Response::ok($out);
    }
}
