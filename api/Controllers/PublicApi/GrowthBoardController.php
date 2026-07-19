<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Database;
use Core\Env;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Services\Captcha;
use Services\LeadService;
use Services\Mailer;

/**
 * GrowthBoard · el instrumento del Tablero de Crecimiento.
 * La plataforma mide, ordena y visualiza; la interpretación y el
 * acompañamiento son del consultor (lectura estratégica).
 */
final class GrowthBoardController extends Controller
{
    /** Configuración pública: zonas (BD con respaldo en config), líneas, bandas, contexto. */
    public function config(): void
    {
        RateLimiter::public($this->req);
        $cfg = self::cfg();
        Response::ok([
            'zonas' => self::zonas(),
            'lineas' => $cfg['lineas'],
            'bandas' => array_map(fn ($b) => ['min' => $b['min'], 'max' => $b['max'], 'key' => $b['key'], 'titulo' => $b['titulo'], 'lectura' => $b['lectura'], 'prioridad' => $b['prioridad'], 'oferta' => $b['oferta']], $cfg['bandas']),
            'contexto' => $cfg['contexto'],
            'facturacion' => $cfg['facturacion'],
        ]);
    }

    /**
     * Recibe el diagnóstico completo: datos del empresario (con captcha),
     * contexto y calificaciones 1–5 de las 5 afirmaciones de cada zona.
     * Calcula el tablero, guarda el resultado, captura el lead y responde
     * con la lectura estructural (la interpretación la da el consultor).
     */
    public function diagnostico(): void
    {
        RateLimiter::hit('gbdiag:' . $this->req->ip(), 6, 300);
        if (! Captcha::verify((string) $this->req->input('captcha_code', ''), (string) $this->req->input('captcha_token', ''))) {
            Response::error('El código de verificación no es válido o expiró. Intenta con el nuevo código.', 422, ['captcha' => 'invalid']);
        }

        // Nota: el honeypot ocupa el campo 'website'; el sitio real viaja como 'web_url'.
        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa', true)
            ->text('phone_dial', false, 5)->text('company', true, 160)->text('role', false, 120)
            ->text('country', false, 2)->text('city', false, 120)->text('web_url', false, 190)
            ->text('company_size', false, 20)->text('industry', false, 60)->text('revenue', false, 20);
        $d = $v->failOrValidated();
        $d['website'] = $d['web_url'] ?? '';
        $locale = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';

        // Calificaciones: {zkey: [5 enteros 1..5]}
        $zonas = self::zonas();
        $raw = (array) $this->req->input('scores', []);
        $scores = [];
        foreach ($zonas as $z) {
            $vals = array_values(array_filter(array_map('intval', (array) ($raw[$z['zkey']] ?? [])), fn ($n) => $n >= 1 && $n <= 5));
            if (count($vals) < count($z['afirmaciones'])) {
                Response::error('Faltan calificaciones en la zona "' . ($z['nombre']['es'] ?? $z['zkey']) . '".', 422);
            }
            $scores[$z['zkey']] = round(array_sum($vals) / count($vals), 1);
        }

        $res = self::leer($scores);
        $contexto = array_intersect_key((array) $this->req->input('contexto', []), array_flip(['reto', 'frase', 'urgencia']));

        // Lead al CRM con perfil completo (la demo del funnel de Tonny).
        $lead = LeadService::capture(array_merge($d, ['locale' => $locale], \Core\Attribution::fromRequest($this->req)), 'growthboard',
            'Completó el Diagnóstico GrowthBoard · ' . $res['total'] . '/55 (' . $res['banda']['key'] . ')',
            ['total' => $res['total'], 'banda' => $res['banda']['key'], 'linea_debil' => $res['linea_debil'],
                'zona_critica' => $res['zona_critica'], 'ciudad' => $d['city'] ?? '', 'facturacion' => $d['revenue'] ?? '', 'contexto' => $contexto]);

        try {
            Database::run('INSERT INTO gb_results (lead_id, locale, total, banda, linea_debil, zona_critica, scores, contexto, extras, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)', [
                (int) $lead['id'], $locale, $res['total'], $res['banda']['key'], $res['linea_debil'], $res['zona_critica'],
                json_encode($scores, JSON_UNESCAPED_UNICODE), json_encode($contexto, JSON_UNESCAPED_UNICODE),
                json_encode(['city' => $d['city'] ?? '', 'revenue' => $d['revenue'] ?? '', 'website' => $d['web_url'] ?? ''], JSON_UNESCAPED_UNICODE), now_utc(),
            ]);
        } catch (\Throwable $e) { /* tabla sin migrar: el touchpoint ya conserva el resumen */ }

        // Correo postdiagnóstico con el copy del método (best-effort).
        try {
            $mail = self::cfg()['email'][$locale] ?? self::cfg()['email']['es'];
            $agenda = rtrim(Env::get('APP_URL', 'https://experientia.pro'), '/') . '/' . $locale . '/' . self::slugAgenda($locale);
            Mailer::send($d['email'], $mail['asunto'], str_replace(['{nombre}', '{enlace}'], [explode(' ', trim($d['name']))[0], $agenda], $mail['cuerpo']));
        } catch (\Throwable $e) { /* sin conector de correo: no rompe el diagnóstico */ }

        Response::ok($res + ['scores' => $scores, 'lead_id' => (int) $lead['id']]);
    }

    /** Demo con datos de ejemplo por industria (declarados como ficticios). */
    public function demo(string $industry = 'default'): void
    {
        RateLimiter::public($this->req);
        $cfg = self::cfg();
        $set = $cfg['demo'][$industry] ?? $cfg['demo']['default'];
        $res = self::leer($set['scores']);
        Response::ok($res + ['scores' => $set['scores'], 'marcador' => $set['marcador'], 'industrias' => array_keys($cfg['demo'])]);
    }

    // ── Lectura estructural del tablero (el cálculo del framework) ───────────
    private static function leer(array $scores): array
    {
        $cfg = self::cfg();
        $zonas = self::zonas();
        $total = round(array_sum($scores), 1);

        $lineas = [];
        foreach ($cfg['lineas'] as $k => $ln) {
            $s = round(array_sum(array_map(fn ($z) => $scores[$z] ?? 0, $ln['zonas'])), 1);
            $lineas[$k] = ['score' => $s, 'max' => $ln['max'], 'nombre' => $ln['nombre'], 'sintoma' => $ln['sintoma'], 'jugada' => $ln['jugada'], 'zonas' => $ln['zonas']];
        }
        // Línea más débil: menor proporción score/max.
        $lineaDebil = array_keys($lineas)[0];
        foreach ($lineas as $k => $ln) {
            if ($ln['score'] / $ln['max'] < $lineas[$lineaDebil]['score'] / $lineas[$lineaDebil]['max']) { $lineaDebil = $k; }
        }
        // Zona crítica: score más bajo (primer empate gana por orden del tablero).
        $zonaCritica = $zonas[0]['zkey'];
        foreach ($zonas as $z) {
            if (($scores[$z['zkey']] ?? 5) < ($scores[$zonaCritica] ?? 5)) { $zonaCritica = $z['zkey']; }
        }
        $zc = null;
        foreach ($zonas as $z) { if ($z['zkey'] === $zonaCritica) { $zc = $z; break; } }

        $banda = $cfg['bandas'][0];
        foreach ($cfg['bandas'] as $b) { if ($total >= $b['min'] && $total <= $b['max']) { $banda = $b; break; } }
        if ($total > 55) { $banda = end($cfg['bandas']); }

        return [
            'total' => $total, 'max' => 55,
            'banda' => ['key' => $banda['key'], 'titulo' => $banda['titulo'], 'lectura' => $banda['lectura'], 'prioridad' => $banda['prioridad'], 'oferta' => $banda['oferta']],
            'lineas' => $lineas, 'linea_debil' => $lineaDebil,
            'zona_critica' => $zonaCritica,
            'jugada' => $zc['jugada'] ?? null,
            'senales' => array_slice($zc['senales'] ?? [], 0, 3),
        ];
    }

    /** Zonas desde BD (editables) con respaldo en la configuración del método. */
    private static function zonas(): array
    {
        try {
            $rows = Database::run('SELECT zkey, linea, icon, nombre, pregunta, afirmaciones, senales, jugada FROM gb_zones WHERE active = 1 ORDER BY sort, id')->fetchAll();
            if ($rows) {
                return array_map(function ($r) {
                    foreach (['nombre', 'pregunta', 'afirmaciones', 'senales', 'jugada'] as $c) {
                        $d = is_string($r[$c] ?? null) ? json_decode($r[$c], true) : ($r[$c] ?? null);
                        $r[$c] = $d;
                    }
                    return $r;
                }, $rows);
            }
        } catch (\Throwable $e) { /* sin migrar → config */ }
        $out = [];
        foreach (self::cfg()['zonas'] as $zkey => $z) { $out[] = ['zkey' => $zkey] + $z; }
        return $out;
    }

    private static function cfg(): array
    {
        static $cfg = null;
        return $cfg ??= require BASE_PATH . '/api/config/growthboard.php';
    }

    private static function slugAgenda(string $locale): string
    {
        return ['es' => 'agendar', 'en' => 'book', 'pt' => 'agendar'][$locale] ?? 'agendar';
    }
}
