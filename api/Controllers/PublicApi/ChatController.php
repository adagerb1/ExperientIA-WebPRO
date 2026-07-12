<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Core\Database;
use Services\AlexIA;
use Services\LeadService;
use Services\Mailer;

/** AlexIA comercial en la web (widget del sitio público). */
final class ChatController extends Controller
{
    /** Captura del lead al iniciar el chat (lead magnet del asesor). */
    public function lead(): void
    {
        RateLimiter::public($this->req);
        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5);
        $d = $v->failOrValidated();
        $d['locale'] = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        $d = array_merge($d, \Core\Attribution::fromRequest($this->req));
        $lead = LeadService::capture($d, 'chat', 'Inició conversación con AlexIA', ['origen' => 'chat_web']);
        Response::ok(['lead_id' => (int) $lead['id']]);
    }

    public function mensaje(): void
    {
        RateLimiter::hit('alexia:' . $this->req->ip(), 20, 60);
        $v = Validator::make($this->req->body)->honeypot()->textarea('mensaje', true, 2000)->int('conversation_id');
        $d = $v->failOrValidated();
        $locale = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        $nombre = mb_substr(trim(strip_tags((string) $this->req->input('nombre', ''))), 0, 80);

        $out = AlexIA::chat('comercial', 'web', $d['mensaje'], [
            'locale' => $locale,
            'conversation_id' => $d['conversation_id'] ?? null,
            'lead_id' => (int) $this->req->input('lead_id', 0) ?: null,
            'nombre' => $nombre,
        ]);
        Response::ok($out);
    }

    /** Envía por correo un resumen de la conversación (al cerrar el chat). */
    public function resumen(): void
    {
        RateLimiter::public($this->req);
        $convId = (int) $this->req->input('conversation_id', 0);
        $email = filter_var((string) $this->req->input('email', ''), FILTER_VALIDATE_EMAIL);
        $locale = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        if (! $convId || ! $email) { Response::ok(['message' => 'skip']); return; }

        try {
            $msgs = Database::run('SELECT role, content FROM ai_messages WHERE conversation_id = ? ORDER BY id', [$convId])->fetchAll();
            if (count($msgs) < 2) { Response::ok(['message' => 'skip']); return; }
            $transcript = '';
            foreach ($msgs as $m) { $transcript .= ($m['role'] === 'user' ? 'Cliente' : 'AlexIA') . ': ' . $m['content'] . "\n"; }

            $instr = "Eres AlexIA, asesora comercial de ExperientIA. Redacta en {$locale} un correo BREVE y cálido que resuma la "
                . "conversación con el cliente: 2-3 puntos clave tratados y el siguiente paso recomendado (diagnóstico gratuito o "
                . "agendar 1:1). En HTML simple (<p>, <ul><li>, <strong>). No inventes datos ni precios. Cierra invitando a responder este correo.";
            $html = AlexIA::ask($instr, "Transcripción:\n{$transcript}");
            Mailer::send($email, 'Tu conversación con AlexIA · ExperientIA', $html);
            Response::ok(['message' => 'ok']);
        } catch (\Throwable $e) {
            Response::ok(['message' => 'skip']); // nunca romper la experiencia del chat
        }
    }
}
