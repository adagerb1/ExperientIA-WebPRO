<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Core\Database;
use Services\LeadService;

final class DiagnosticController extends Controller
{
    public function evaluar(): void
    {
        RateLimiter::public($this->req);
        $preguntas = (require BASE_PATH . '/app/config/diagnostico.php')['preguntas'];

        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5)
            ->country('country', true)->text('company', false, 160)
            ->in('industry', array_keys(biz('industries')))->in('company_size', array_keys(biz('company_sizes')));
        $d = $v->failOrValidated();

        $respuestas = $this->req->input('respuestas', []);
        $scores = ['estrategia' => 0, 'automatizacion' => 0, 'datos' => 0, 'growth' => 0];
        $legibles = [];
        foreach ($preguntas as $p) {
            $idx = $respuestas[$p['id']] ?? null;
            if (! is_numeric($idx) || ! isset($p['opciones'][(int) $idx])) {
                Response::error('Revise los campos marcados.', 422, ['campos' => ['respuestas.' . $p['id']]]);
            }
            $op = $p['opciones'][(int) $idx];
            foreach ($op['scores'] as $k => $pts) { $scores[$k] += $pts; }
            $legibles[tr($p['texto'], 'es')] = tr($op['texto'], 'es');
        }
        arsort($scores);
        $resultado = array_key_first($scores);
        if ($scores[$resultado] === 0) { $resultado = 'estrategia'; }

        $sol = Database::run('SELECT * FROM solutions WHERE skey = ?', [$resultado])->fetch();
        $d['locale'] = in_array($this->req->input('locale', 'es'), biz('locales'), true) ? $this->req->input('locale') : 'es';
        LeadService::capture($d, 'diagnostico',
            'Completó el diagnóstico → ' . ($sol ? tr($sol['titulo'], 'es') : $resultado),
            ['resultado' => $resultado, 'puntajes' => json_encode($scores), 'respuestas' => json_encode($legibles, JSON_UNESCAPED_UNICODE)]);

        Response::ok([
            'resultado' => $resultado,
            'solucion' => $sol ? [
                'titulo' => tr($sol['titulo'], $d['locale']), 'pilar' => tr($sol['pilar'], $d['locale']),
                'cambia' => tr($sol['cambia'], $d['locale']), 'icon' => $sol['icon'],
            ] : null,
        ]);
    }
}
