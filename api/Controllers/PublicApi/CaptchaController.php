<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Services\Captcha;

/** Entrega retos captcha para los formularios sensibles (gate del chat). */
final class CaptchaController extends Controller
{
    public function nuevo(): void
    {
        // Límite propio, más estricto que el público general: frena la generación masiva.
        RateLimiter::hit('captcha:' . $this->req->ip(), 15, 60);
        Response::ok(Captcha::issue());
    }
}
