<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Core\Database;
use Services\LeadService;

/**
 * Motor de diagnósticos dinámico. Cada diagnóstico (tabla `diagnostics`) define
 * sus preguntas y sus resultados; el resultado puede enlazar una solución para
 * heredar su copy. Se pueden habilitar varios tipos. Si la tabla no existe aún
 * (BD sin migrar), cae a la semilla de configuración para no romper el sitio.
 */
final class DiagnosticController extends Controller
{
    /** Lista de diagnósticos activos (para el selector público). */
    public function index(): void
    {
        $out = [];
        foreach ($this->all() as $d) {
            $out[] = ['dkey' => $d['dkey'], 'icon' => $d['icon'], 'nombre' => $d['nombre'], 'intro' => $d['intro'] ?? null];
        }
        Response::ok($out);
    }

    /** Configuración pública de un diagnóstico (preguntas sin pesos). */
    public function show(string $dkey): void
    {
        $d = $this->load($dkey);
        if (! $d) { Response::error('Diagnóstico no encontrado.', 404); }
        $preguntas = array_map(function ($p) {
            return ['id' => $p['id'], 'texto' => $p['texto'],
                'opciones' => array_map(fn ($o) => ['texto' => $o['texto']], $p['opciones'])];
        }, $d['preguntas']);
        Response::ok([
            'dkey' => $d['dkey'], 'icon' => $d['icon'], 'nombre' => $d['nombre'],
            'intro' => $d['intro'] ?? null, 'preguntas' => $preguntas,
        ]);
    }

    /** Evalúa las respuestas contra el diagnóstico y captura el lead. */
    public function evaluar(): void
    {
        RateLimiter::public($this->req);
        $dkey = (string) $this->req->input('dkey', 'madurez-ia');
        $diag = $this->load($dkey) ?? $this->load('madurez-ia');
        if (! $diag) { Response::error('Diagnóstico no disponible.', 404); }

        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5)
            ->country('country', true)->text('company', false, 160)
            ->in('industry', array_keys(biz('industries')))->in('company_size', array_keys(biz('company_sizes')));
        $d = $v->failOrValidated();

        $respuestas = $this->req->input('respuestas', []);
        $scores = [];
        foreach ($diag['resultados'] as $r) { $scores[$r['clave']] = 0; }
        $legibles = [];
        foreach ($diag['preguntas'] as $p) {
            $idx = $respuestas[$p['id']] ?? null;
            if (! is_numeric($idx) || ! isset($p['opciones'][(int) $idx])) {
                Response::error('Revise los campos marcados.', 422, ['campos' => ['respuestas.' . $p['id']]]);
            }
            $op = $p['opciones'][(int) $idx];
            foreach (($op['scores'] ?? []) as $k => $pts) {
                if (isset($scores[$k])) { $scores[$k] += $pts; }
            }
            $legibles[tr($p['texto'], 'es')] = tr($op['texto'], 'es');
        }
        arsort($scores);
        $ganadora = array_key_first($scores);
        if (! $ganadora || $scores[$ganadora] === 0) {
            $ganadora = $diag['default_result'] ?? $diag['resultados'][0]['clave'];
        }

        $reqLocale = $this->req->input('locale', 'es');
        $loc = in_array($reqLocale, biz('locales'), true) ? $reqLocale : 'es';
        $resultado = $this->resolverResultado($diag, $ganadora, $loc);

        $d['locale'] = $loc;
        $d = array_merge($d, \Core\Attribution::fromRequest($this->req));
        LeadService::capture($d, 'diagnostico',
            'Completó "' . tr($diag['nombre'], 'es') . '" → ' . $resultado['titulo'],
            ['diagnostico' => $diag['dkey'], 'resultado' => $ganadora,
                'puntajes' => json_encode($scores), 'respuestas' => json_encode($legibles, JSON_UNESCAPED_UNICODE)]);

        Response::ok(['resultado' => $resultado]);
    }

    /** Combina el resultado (posible enlace a solución) en copy localizado. */
    private function resolverResultado(array $diag, string $clave, string $loc): array
    {
        $r = null;
        foreach ($diag['resultados'] as $x) { if ($x['clave'] === $clave) { $r = $x; break; } }
        $r = $r ?? $diag['resultados'][0];

        $base = ['icon' => $diag['icon'], 'pilar' => '', 'titulo' => $clave, 'cambia' => '', 'solucion_skey' => null];
        if (! empty($r['solucion_skey'])) {
            $sol = Database::run('SELECT * FROM solutions WHERE skey = ?', [$r['solucion_skey']])->fetch();
            if ($sol) {
                $base = [
                    'icon' => $sol['icon'], 'pilar' => tr($sol['pilar'], $loc),
                    'titulo' => tr($sol['titulo'], $loc), 'cambia' => tr($sol['cambia'], $loc),
                    'solucion_skey' => $sol['skey'],
                ];
            }
        }
        // Overrides propios del resultado (si el diagnóstico define su copy).
        if (! empty($r['icon'])) { $base['icon'] = $r['icon']; }
        if (! empty($r['pilar'])) { $base['pilar'] = tr($r['pilar'], $loc); }
        if (! empty($r['titulo'])) { $base['titulo'] = tr($r['titulo'], $loc); }
        if (! empty($r['descripcion'])) { $base['cambia'] = tr($r['descripcion'], $loc); }
        $base['clave'] = $clave;
        return $base;
    }

    /** Carga un diagnóstico (BD → fallback semilla). Devuelve arreglo decodificado o null. */
    private function load(string $dkey): ?array
    {
        try {
            $row = Database::run('SELECT * FROM diagnostics WHERE dkey = ? AND active = 1', [$dkey])->fetch();
            if ($row) { return $this->decode($row); }
        } catch (\Throwable $e) { /* tabla sin migrar → fallback */ }
        foreach ($this->seed() as $d) { if ($d['dkey'] === $dkey) { return $d; } }
        return null;
    }

    /** Todos los diagnósticos activos (BD → fallback semilla). */
    private function all(): array
    {
        try {
            $rows = Database::run('SELECT * FROM diagnostics WHERE active = 1 ORDER BY sort, id')->fetchAll();
            if ($rows) { return array_map([$this, 'decode'], $rows); }
        } catch (\Throwable $e) { /* fallback */ }
        return $this->seed();
    }

    private function decode(array $row): array
    {
        foreach (['nombre', 'intro', 'preguntas', 'resultados'] as $f) {
            if (isset($row[$f]) && is_string($row[$f])) { $row[$f] = json_decode($row[$f], true) ?: []; }
        }
        return $row;
    }

    private ?array $seedCache = null;
    private function seed(): array
    {
        if ($this->seedCache !== null) { return $this->seedCache; }
        $seed = require BASE_PATH . '/api/db/seed_diagnostics.php';
        return $this->seedCache = $seed;
    }
}
