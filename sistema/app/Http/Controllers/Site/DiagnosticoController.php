<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use App\Services\LeadCapture;
use Illuminate\Http\Request;

class DiagnosticoController extends Controller
{
    public function show()
    {
        return view('site.diagnostico', [
            'preguntas' => config('diagnostico.preguntas'),
        ]);
    }

    public function store(Request $request, LeadCapture $capture)
    {
        $preguntas = config('diagnostico.preguntas');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone_wa' => ['nullable', 'string', 'max:20'],
            'phone_dial' => ['nullable', 'string', 'max:5'],
            'country' => ['required', 'string', 'size:2'],
            'company' => ['nullable', 'string', 'max:160'],
            'industry' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('experientia.industries')))],
            'company_size' => ['nullable', 'string', 'in:micro,pequena,mediana,grande,corporativa'],
            'website' => ['prohibited'],
        ];
        foreach ($preguntas as $i => $p) {
            $rules["respuestas.{$p['id']}"] = ['required', 'integer', 'min:0', 'max:' . (count($p['opciones']) - 1)];
        }

        $data = $request->validate($rules);

        // Puntaje por solución
        $scores = ['estrategia' => 0, 'automatizacion' => 0, 'datos' => 0, 'growth' => 0];
        $respuestasLegibles = [];
        foreach ($preguntas as $p) {
            $idx = (int) $data['respuestas'][$p['id']];
            $opcion = $p['opciones'][$idx];
            foreach ($opcion['scores'] as $key => $pts) {
                $scores[$key] = ($scores[$key] ?? 0) + $pts;
            }
            $respuestasLegibles[tr($p['texto'], 'es')] = tr($opcion['texto'], 'es');
        }

        arsort($scores);
        $resultKey = array_key_first($scores);

        // Empate o todo en cero: recomendar estrategia (punto de partida natural)
        if ($scores[$resultKey] === 0) {
            $resultKey = 'estrategia';
        }

        $solution = Solution::where('key', $resultKey)->first();

        $lead = $capture->capture(
            $data + ['locale' => app()->getLocale()],
            'diagnostico',
            'Completó el diagnóstico → ' . ($solution ? tr($solution->titulo, 'es') : $resultKey),
            [
                'resultado' => $resultKey,
                'puntajes' => $scores,
                'respuestas' => $respuestasLegibles,
            ],
        );

        ContactController::notifyAdmin($lead);

        session()->flash('diagnostico_resultado', $resultKey);

        return redirect()->to(lroute('diagnostico') . '?resultado=1');
    }
}
