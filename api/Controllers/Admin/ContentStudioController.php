<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\RateLimiter;
use Core\Response;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;

/**
 * GrowthBoard AI Content Studio (MVP · Fase 1 del blueprint):
 * documento estratégico → brief estructurado → calendario editorial → piezas
 * completas con revisión humana obligatoria. La IA convierte la estrategia en
 * ejecución; qué representa la marca lo decide la estrategia (regla final §44).
 */
final class ContentStudioController extends Controller
{
    private const CANALES = ['linkedin', 'instagram', 'tiktok', 'youtube', 'email'];
    private const PILARES = ['diagnostico', 'framework', 'prueba', 'vision', 'oferta'];
    private const ESTADOS = ['idea', 'redaccion', 'aprobada', 'programada', 'publicada'];
    private const MECANISMOS = ['afirmacion_divisiva', 'error_visible', 'pregunta_compleja', 'numero_especifico', 'reto_verificacion', 'contraste'];

    // ── Estrategias ──────────────────────────────────────────────────────────
    public function index(): void
    {
        AuthMiddleware::require($this->req);
        $rows = Database::run(
            'SELECT s.id, s.nombre, s.periodo, s.status, s.created_at,
                (SELECT COUNT(*) FROM gbc_items i WHERE i.strategy_id = s.id) AS piezas,
                (SELECT COUNT(*) FROM gbc_items i WHERE i.strategy_id = s.id AND i.estado IN (\'aprobada\',\'programada\',\'publicada\')) AS aprobadas
             FROM gbc_strategies s ORDER BY s.id DESC')->fetchAll();
        Response::ok($rows);
    }

    public function store(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $nombre = mb_substr(trim(strip_tags((string) $this->req->input('nombre', ''))), 0, 190);
        if ($nombre === '') { Response::error('La estrategia necesita un nombre.', 422); }
        $periodo = in_array($p = (string) $this->req->input('periodo', 'mensual'), ['mensual', 'trimestral', 'campana'], true) ? $p : 'mensual';
        $doc = mb_substr((string) $this->req->input('source_doc', ''), 0, 60000);
        Database::run('INSERT INTO gbc_strategies (nombre, periodo, source_doc, status, created_at) VALUES (?,?,?,?,?)',
            [$nombre, $periodo, $doc, 'borrador', now_utc()]);
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function show(string $id): void
    {
        AuthMiddleware::require($this->req);
        $s = $this->estrategia($id);
        $items = Database::run('SELECT * FROM gbc_items WHERE strategy_id = ? ORDER BY semana, sort, id', [$id])->fetchAll();
        foreach ($items as &$i) { $i['copy'] = $i['copy'] ? json_decode($i['copy'], true) : null; }
        unset($i);
        Response::ok(['estrategia' => $s, 'items' => $items]);
    }

    public function update(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $s = $this->estrategia($id);
        $nombre = mb_substr(trim(strip_tags((string) $this->req->input('nombre', $s['nombre']))), 0, 190) ?: $s['nombre'];
        $doc = $this->req->body['source_doc'] ?? null;
        $brief = $this->req->body['brief'] ?? null;
        $status = in_array($st = (string) $this->req->input('status', $s['status']), ['borrador', 'brief', 'aprobada'], true) ? $st : $s['status'];
        Database::run('UPDATE gbc_strategies SET nombre = ?, source_doc = ?, brief = ?, status = ?, updated_at = ? WHERE id = ?', [
            $nombre,
            $doc !== null ? mb_substr((string) $doc, 0, 60000) : $s['source_doc'],
            $brief !== null ? json_encode($brief, JSON_UNESCAPED_UNICODE) : ($s['brief'] ? json_encode($s['brief'], JSON_UNESCAPED_UNICODE) : null),
            $status, now_utc(), $id,
        ]);
        Response::ok(['message' => 'ok']);
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Database::run('DELETE FROM gbc_items WHERE strategy_id = ?', [$id]);
        Database::run('DELETE FROM gbc_strategies WHERE id = ?', [$id]);
        Response::ok(['message' => 'ok']);
    }

    /** Paso 1-3 del procesamiento (§8): extraer + validar + brief estructurado. */
    public function brief(string $id): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        $s = $this->estrategia($id);
        if (trim((string) $s['source_doc']) === '') { Response::error('Pega primero el documento estratégico.', 422); }

        $instr = 'Eres el Growth Director Agent del GrowthBoard AI Content Studio (Tonny Dager x ExperientIA). '
            . 'Lee el documento estratégico y devuelve SOLO JSON válido con la ficha estratégica: '
            . '{"campana":"","periodo":"","objetivo_general":"","objetivos":[],"audiencia":"","problema":"","promesa":"","mecanismo_unico":"","enemigo":"","oferta":"","cta":"","pilares":[],"canales":[],"frecuencia":"","tono":"","metricas":[],"faltantes":[],"contradicciones":[]}. '
            . 'En "faltantes" lista información que el documento NO trae (no la inventes); en "contradicciones", promesas sin soporte o CTA desconectados. '
            . 'No inventes ofertas ni cifras: si no están en el documento, van en faltantes. Responde en español.';
        try {
            $raw = AlexIA::ask($instr, mb_substr($s['source_doc'], 0, 24000));
            $data = json_decode($this->extractJson($raw), true);
            if (! is_array($data)) { Response::error('La IA no devolvió un brief válido. Intenta de nuevo.', 502); }
            Database::run('UPDATE gbc_strategies SET brief = ?, status = ?, updated_at = ? WHERE id = ?',
                [json_encode($data, JSON_UNESCAPED_UNICODE), 'brief', now_utc(), $id]);
            Response::ok(['brief' => $data]);
        } catch (\Throwable $e) { Response::error($e->getMessage(), 503); }
    }

    /** Generador de calendario inteligente (§10, §15, §16): crea las piezas. */
    public function calendario(string $id): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        $s = $this->estrategia($id);
        if (! $s['brief']) { Response::error('Extrae y aprueba el brief antes de generar el calendario.', 422); }

        $semanas = max(1, min(13, (int) $this->req->input('semanas', 4)));
        $porSemana = max(1, min(7, (int) $this->req->input('por_semana', 5)));
        $canales = array_values(array_intersect((array) $this->req->input('canales', ['linkedin']), self::CANALES)) ?: ['linkedin'];

        $instr = 'Eres el Arquitecto Editorial del GrowthBoard AI Content Studio. Con la ficha estratégica dada, genera el calendario. '
            . 'Devuelve SOLO JSON: {"items":[{"semana":1,"canal":"","formato":"","pilar":"","tema":"","objetivo":"","mecanismo":""}]}. '
            . "Genera exactamente {$semanas} semanas con {$porSemana} piezas por semana, solo en estos canales: " . implode(', ', $canales) . '. '
            . 'Formatos válidos: post, carrusel, video, documento, newsletter. '
            . 'Distribución de pilares (matriz editorial): 40% diagnostico, 25% framework, 20% prueba, 10% vision, 5% oferta. '
            . 'Mecanismos válidos: ' . implode(', ', self::MECANISMOS) . ' (mínimo uno por pieza). '
            . 'Reglas: no repetir hooks ni temas, no saturar un pilar, no usar el mismo CTA seguido, alternar profundidad y alcance, mantener narrativa mensual coherente con los OKR. Temas concretos y específicos, no genéricos. Responde en español.';
        try {
            $raw = AlexIA::ask($instr, 'Ficha estratégica: ' . json_encode($s['brief'], JSON_UNESCAPED_UNICODE));
            $data = json_decode($this->extractJson($raw), true);
            $items = is_array($data['items'] ?? null) ? $data['items'] : null;
            if (! $items) { Response::error('La IA no devolvió un calendario válido. Intenta de nuevo.', 502); }
            $n = 0;
            foreach ($items as $it) {
                if (empty($it['tema'])) { continue; }
                Database::run('INSERT INTO gbc_items (strategy_id, semana, canal, formato, pilar, tema, objetivo, mecanismo, estado, sort, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
                    $id, max(1, min($semanas, (int) ($it['semana'] ?? 1))),
                    in_array($it['canal'] ?? '', self::CANALES, true) ? $it['canal'] : $canales[0],
                    mb_substr((string) ($it['formato'] ?? 'post'), 0, 30),
                    in_array($it['pilar'] ?? '', self::PILARES, true) ? $it['pilar'] : 'diagnostico',
                    mb_substr(trim((string) $it['tema']), 0, 255),
                    mb_substr((string) ($it['objetivo'] ?? ''), 0, 255),
                    in_array($it['mecanismo'] ?? '', self::MECANISMOS, true) ? $it['mecanismo'] : null,
                    'idea', $n++, now_utc(),
                ]);
            }
            Response::ok(['creadas' => $n]);
        } catch (\Throwable $e) { Response::error($e->getMessage(), 503); }
    }

    /** Copywriter estratégico + guionista (§Agentes 5-6): redacta la pieza completa. */
    public function generarPieza(string $pid): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        $it = Database::run('SELECT * FROM gbc_items WHERE id = ?', [$pid])->fetch();
        if (! $it) { Response::error('Pieza no encontrada.', 404); }
        $s = $this->estrategia((string) $it['strategy_id']);

        $esVideo = in_array($it['formato'], ['video'], true) || in_array($it['canal'], ['tiktok', 'youtube'], true);
        $instr = 'Eres el Copywriter Estratégico del GrowthBoard AI Content Studio (voz: Tonny Dager, consultor de crecimiento; marca: ExperientIA). '
            . 'Redacta la pieza y devuelve SOLO JSON: {"hook":"","texto":"","corta":"","cta":"","hashtags":"","comentario":""' . ($esVideo ? ',"guion":""' : '') . ',"score":0}. '
            . 'Reglas del copy: no pedir interacción vacía; provocar procesamiento; nombrar problemas específicos; defender una postura real; conectar con la oferta del brief. '
            . 'El hook es la primera línea (detiene el scroll sin clickbait vacío). "texto" es el copy completo para el canal ' . $it['canal'] . ' (formato ' . ($it['formato'] ?: 'post') . '). '
            . '"corta" es una versión breve alternativa. Aplica el mecanismo de interacción "' . ($it['mecanismo'] ?: 'pregunta_compleja') . '". '
            . ($esVideo ? '"guion" incluye hook oral, desarrollo, indicaciones de cámara/b-roll, texto en pantalla y CTA. ' : '')
            . '"score" (0-100) autoevalúa alineación, claridad, hook, especificidad, originalidad y conversión; sé exigente. '
            . 'No inventes cifras, casos ni promesas que no estén en el brief. Responde en español.';
        $input = 'Ficha estratégica: ' . json_encode($s['brief'] ?: ['nota' => 'sin brief'], JSON_UNESCAPED_UNICODE)
            . "\nPieza: tema=\"{$it['tema']}\" · objetivo=\"{$it['objetivo']}\" · pilar={$it['pilar']} · canal={$it['canal']} · formato={$it['formato']}";
        try {
            $raw = AlexIA::ask($instr, $input);
            $data = json_decode($this->extractJson($raw), true);
            if (! is_array($data) || empty($data['texto'])) { Response::error('La IA no devolvió la pieza. Intenta de nuevo.', 502); }
            $copy = ['hook' => (string) ($data['hook'] ?? ''), 'texto' => (string) $data['texto'], 'corta' => (string) ($data['corta'] ?? ''),
                'cta' => (string) ($data['cta'] ?? ''), 'hashtags' => (string) ($data['hashtags'] ?? ''), 'comentario' => (string) ($data['comentario'] ?? '')];
            Database::run('UPDATE gbc_items SET copy = ?, guion = ?, score = ?, estado = ?, updated_at = ? WHERE id = ?', [
                json_encode($copy, JSON_UNESCAPED_UNICODE), (string) ($data['guion'] ?? ($it['guion'] ?? '')),
                max(0, min(100, (int) ($data['score'] ?? 0))), 'redaccion', now_utc(), $pid,
            ]);
            Response::ok(['copy' => $copy, 'guion' => (string) ($data['guion'] ?? ''), 'score' => (int) ($data['score'] ?? 0)]);
        } catch (\Throwable $e) { Response::error($e->getMessage(), 503); }
    }

    public function itemStore(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->estrategia($id);
        $d = $this->itemDatos();
        if ($d['tema'] === '') { Response::error('La pieza necesita un tema.', 422); }
        $copy = $this->req->body['copy'] ?? null;
        Database::run('INSERT INTO gbc_items (strategy_id, semana, fecha, canal, formato, pilar, tema, objetivo, mecanismo, estado, copy, guion, notas, sort, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$id, $d['semana'], $d['fecha'], $d['canal'], $d['formato'], $d['pilar'], $d['tema'], $d['objetivo'], $d['mecanismo'], $d['estado'],
                is_array($copy) ? json_encode($copy, JSON_UNESCAPED_UNICODE) : null,
                mb_substr((string) $this->req->input('guion', ''), 0, 8000) ?: null,
                mb_substr(trim(strip_tags((string) $this->req->input('notas', ''))), 0, 1000) ?: null,
                $d['sort'], now_utc()]);
        Response::ok(['id' => (int) Database::pdo()->lastInsertId()]);
    }

    public function itemUpdate(string $pid): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $it = Database::run('SELECT id FROM gbc_items WHERE id = ?', [$pid])->fetch();
        if (! $it) { Response::error('Pieza no encontrada.', 404); }
        $d = $this->itemDatos();
        $copy = $this->req->body['copy'] ?? null;
        Database::run('UPDATE gbc_items SET semana=?, fecha=?, canal=?, formato=?, pilar=?, tema=?, objetivo=?, mecanismo=?, estado=?, guion=?, notas=?, copy = COALESCE(?, copy), updated_at=? WHERE id=?', [
            $d['semana'], $d['fecha'], $d['canal'], $d['formato'], $d['pilar'], $d['tema'] ?: '(sin tema)', $d['objetivo'], $d['mecanismo'], $d['estado'],
            mb_substr((string) $this->req->input('guion', ''), 0, 8000) ?: null,
            mb_substr(trim(strip_tags((string) $this->req->input('notas', ''))), 0, 1000) ?: null,
            is_array($copy) ? json_encode($copy, JSON_UNESCAPED_UNICODE) : null,
            now_utc(), $pid,
        ]);
        Response::ok(['message' => 'ok']);
    }

    /** Publica la pieza en su canal (hoy LinkedIn). Requiere el conector activo. */
    public function publicar(string $pid): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $it = Database::run('SELECT * FROM gbc_items WHERE id = ?', [$pid])->fetch();
        if (! $it) { Response::error('Pieza no encontrada.', 404); }
        $copy = $it['copy'] ? json_decode($it['copy'], true) : [];
        $texto = trim((string) ($copy['texto'] ?? ''));
        if ($texto === '') { Response::error('Redacta el copy de la pieza antes de publicar.', 422); }
        $cta = $copy['cta'] ?? '';
        if ($cta && ! str_contains($texto, $cta)) { $texto .= "\n\n" . $cta; }
        if (! empty($copy['hashtags'])) { $texto .= "\n\n" . $copy['hashtags']; }

        $res = match ($it['canal']) {
            'linkedin' => \Services\Connectors\LinkedInConnector::publish($texto),
            default => ['ok' => false, 'error' => 'La publicación automática está disponible para LinkedIn. Para ' . $it['canal'] . ', exporta y publica manualmente.'],
        };
        if (! empty($res['ok'])) {
            Database::run('UPDATE gbc_items SET estado = ?, updated_at = ? WHERE id = ?', ['publicada', now_utc(), $pid]);
        }
        Response::ok($res);
    }

    public function itemDestroy(string $pid): void
    {
        AuthMiddleware::require($this->req, 'admin');
        Database::run('DELETE FROM gbc_items WHERE id = ?', [$pid]);
        Response::ok(['message' => 'ok']);
    }

    /** Exporta el calendario a CSV (; + BOM UTF-8). */
    public function export(string $id): void
    {
        AuthMiddleware::require($this->req);
        $s = $this->estrategia($id);
        $items = Database::run('SELECT * FROM gbc_items WHERE strategy_id = ? ORDER BY semana, sort, id', [$id])->fetchAll();
        $cols = ['semana', 'fecha', 'canal', 'formato', 'pilar', 'tema', 'objetivo', 'mecanismo', 'estado', 'score', 'hook', 'texto', 'cta', 'hashtags'];
        $esc = fn ($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"';
        $out = implode(';', array_map($esc, $cols)) . "\r\n";
        foreach ($items as $it) {
            $c = $it['copy'] ? json_decode($it['copy'], true) : [];
            $row = [$it['semana'], $it['fecha'], $it['canal'], $it['formato'], $it['pilar'], $it['tema'], $it['objetivo'], $it['mecanismo'], $it['estado'], $it['score'],
                $c['hook'] ?? '', $c['texto'] ?? '', $c['cta'] ?? '', $c['hashtags'] ?? ''];
            $out .= implode(';', array_map($esc, $row)) . "\r\n";
        }
        http_response_code(200);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="studio-' . preg_replace('/[^a-z0-9\-]+/i', '-', $s['nombre']) . '.csv"');
        echo "\u{FEFF}" . $out;
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private function estrategia(string $id): array
    {
        $s = Database::run('SELECT * FROM gbc_strategies WHERE id = ?', [$id])->fetch();
        if (! $s) { Response::error('Estrategia no encontrada.', 404); }
        $s['brief'] = $s['brief'] ? json_decode($s['brief'], true) : null;
        return $s;
    }

    private function itemDatos(): array
    {
        $b = $this->req->body;
        $txt = fn ($k, $m) => mb_substr(trim(strip_tags((string) ($b[$k] ?? ''))), 0, $m) ?: null;
        return [
            'semana' => max(1, min(13, (int) ($b['semana'] ?? 1))),
            'fecha' => $txt('fecha', 20),
            'canal' => in_array($b['canal'] ?? '', self::CANALES, true) ? $b['canal'] : 'linkedin',
            'formato' => $txt('formato', 30),
            'pilar' => in_array($b['pilar'] ?? '', self::PILARES, true) ? $b['pilar'] : null,
            'tema' => (string) $txt('tema', 255) ?: '',
            'objetivo' => $txt('objetivo', 255),
            'mecanismo' => in_array($b['mecanismo'] ?? '', self::MECANISMOS, true) ? $b['mecanismo'] : null,
            'estado' => in_array($b['estado'] ?? '', self::ESTADOS, true) ? $b['estado'] : 'idea',
            'sort' => (int) ($b['sort'] ?? 0),
        ];
    }

    private function extractJson(string $s): string
    {
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        return ($a !== false && $b !== false && $b > $a) ? substr($s, $a, $b - $a + 1) : $s;
    }
}
