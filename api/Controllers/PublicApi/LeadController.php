<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Services\LeadService;

final class LeadController extends Controller
{
    public function contacto(): void
    {
        RateLimiter::public($this->req);
        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5)
            ->country('country', true)->text('company', true, 160)->text('role', false, 120)
            ->in('industry', array_keys(biz('industries')), true)
            ->in('company_size', array_keys(biz('company_sizes')), true)
            ->in('desafio', ['crecimiento', 'automatizacion', 'datos', 'estrategia', 'otro'], true)
            ->textarea('mensaje', false, 3000);
        $d = $v->failOrValidated();
        $d['locale'] = $this->locale();
        LeadService::capture($d, 'contacto', 'Solicitó diagnóstico ejecutivo',
            ['desafio' => $d['desafio'], 'mensaje' => $d['mensaje']]);
        Response::ok(['message' => 'ok']);
    }

    public function newsletter(): void
    {
        RateLimiter::public($this->req);
        $v = Validator::make($this->req->body)->honeypot()->email('email', true);
        $d = $v->failOrValidated();
        $d['name'] = $d['email'];
        $d['locale'] = $this->locale();
        LeadService::capture($d, 'newsletter', 'Se suscribió al newsletter');
        Response::ok(['message' => 'ok']);
    }

    private function locale(): string
    {
        $l = $this->req->input('locale', 'es');
        return in_array($l, biz('locales'), true) ? $l : 'es';
    }
}
