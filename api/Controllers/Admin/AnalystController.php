<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;

/**
 * AlexIA analista: conoce el esquema completo (autodescubierto, se actualiza al
 * agregar columnas), consulta la base de datos en modo SOLO LECTURA y responde con
 * texto, tablas y gráficos (descriptiva, inferencial, correlación/regresión).
 * Pipeline seguro: 1) planifica consultas SQL → 2) las ejecuta validadas →
 * 3) responde sobre los datos reales. Nunca inventa cifras.
 */
final class AnalystController extends Controller
{
    /** Tablas que el analista puede ver y consultar (excluye credenciales/secretos). */
    private const TABLAS = [
        'leads', 'touchpoints', 'bookings', 'solutions', 'products', 'case_studies', 'faqs',
        'resources', 'availability_rules', 'diagnostics', 'industries', 'countries',
        'campaign_templates', 'sequences', 'sequence_steps', 'sequence_enrollments',
        'ai_conversations', 'ai_messages', 'payments', 'email_templates',
    ];
    private const MAX_CONSULTAS = 6;
    private const MAX_FILAS = 500;
    private const FILAS_AL_MODELO = 80;

    public function consultar(): void
    {
        $admin = AuthMiddleware::require($this->req);
        RateLimiter::user((int) $admin['id'], $this->req);
        $pregunta = trim((string) $this->req->input('mensaje', ''));
        if ($pregunta === '') { Response::error('Escribe tu consulta.', 422); }

        try {
            $esquema = $this->esquemaTexto();

            // 1) PLAN: qué consultas SQL se necesitan.
            $planInstr = 'Eres AlexIA, analista de datos senior de ExperientIA (experta en analítica aplicada a marketing, '
                . 'growth, IA y consultoría; estadística descriptiva, inferencial, correlación y regresión). Tienes acceso de '
                . "SOLO LECTURA a esta base de datos (MySQL/SQLite):\n{$esquema}\n"
                . 'Planifica las consultas SQL SELECT necesarias para responder la pregunta con DATOS REALES. Prefiere agregados '
                . '(COUNT, SUM, AVG, GROUP BY) y usa funciones portables (evita específicas del motor). Para una regresión o '
                . 'correlación entre dos columnas numéricas, define una consulta con esas dos columnas y márcala. '
                . 'Devuelve ÚNICAMENTE JSON válido: {"consultas":[{"titulo":"","sql":""}],"regresiones":[{"titulo":"","sql":"","x":"","y":""}]}. '
                . 'Máximo ' . self::MAX_CONSULTAS . ' consultas. Si la pregunta no requiere datos, devuelve listas vacías.';
            $plan = json_decode($this->extractJson(AlexIA::ask($planInstr, 'Pregunta: ' . $pregunta)), true) ?: [];

            // 2) EJECUTA (validado, solo lectura).
            $resultados = [];
            foreach (array_slice((array) ($plan['consultas'] ?? []), 0, self::MAX_CONSULTAS) as $c) {
                $resultados[] = $this->correr((string) ($c['titulo'] ?? 'Consulta'), (string) ($c['sql'] ?? ''));
            }
            $regres = [];
            foreach (array_slice((array) ($plan['regresiones'] ?? []), 0, 3) as $rg) {
                $res = $this->correr((string) ($rg['titulo'] ?? 'Regresión'), (string) ($rg['sql'] ?? ''), true);
                if (empty($res['error'])) {
                    $res['regresion'] = $this->ols($res['filas_full'] ?? [], (string) ($rg['x'] ?? ''), (string) ($rg['y'] ?? ''));
                }
                unset($res['filas_full']);
                $regres[] = $res;
            }

            // 3) RESPONDE sobre los datos reales.
            $datos = json_encode(['consultas' => $resultados, 'regresiones' => $regres], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $ansInstr = 'Eres AlexIA, analista de datos de ExperientIA y miembro del consejo consultivo del CEO. Responde a la '
                . 'pregunta USANDO ÚNICAMENTE los resultados de consulta provistos (datos reales; NUNCA inventes cifras). '
                . "Aplica estadística cuando aporte (descriptiva: totales, promedios, %, tendencias; correlación/regresión con los "
                . "valores calculados). Responde en HTML válido y ejecutivo (usa <p>, <h3>, <strong>, <ul><li>, y <table><thead><tbody> "
                . "para tablas). Incluye gráficos relevantes como bloque independiente con esta sintaxis EXACTA:\n"
                . "<div class=\"ai-chart\" data-chart='{\"type\":\"bar\",\"title\":\"Título\",\"series\":[{\"label\":\"A\",\"value\":10}]}'></div>\n"
                . 'type: "bar", "line" o "pie". Cierra con una recomendación accionable. Sé claro y conciso.';
            $reply = AlexIA::ask($ansInstr, "Pregunta: {$pregunta}\n\nResultados (JSON):\n{$datos}");

            Response::ok(['reply' => trim($reply), 'consultas' => array_map(fn ($r) => ['titulo' => $r['titulo'], 'sql' => $r['sql'], 'error' => $r['error'] ?? null, 'filas' => $r['n'] ?? 0], array_merge($resultados, $regres))]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    /** Ejecuta una consulta validada (solo lectura). */
    private function correr(string $titulo, string $sql, bool $conservarFull = false): array
    {
        try {
            $safe = $this->safeSelect($sql);
            $rows = Database::pdo()->query($safe)->fetchAll(\PDO::FETCH_ASSOC);
            $out = ['titulo' => $titulo, 'sql' => $safe, 'n' => count($rows), 'filas' => array_slice($rows, 0, self::FILAS_AL_MODELO)];
            if (count($rows) > self::FILAS_AL_MODELO) { $out['nota'] = 'Se muestran las primeras ' . self::FILAS_AL_MODELO . ' de ' . count($rows) . ' filas.'; }
            if ($conservarFull) { $out['filas_full'] = $rows; }
            return $out;
        } catch (\Throwable $e) {
            return ['titulo' => $titulo, 'sql' => $sql, 'error' => $e->getMessage(), 'n' => 0];
        }
    }

    /** Valida y normaliza una consulta a SELECT de solo lectura. Lanza si no es segura. */
    private function safeSelect(string $sql): string
    {
        $s = trim(rtrim($sql, "; \t\n\r"));
        if ($s === '') { throw new \RuntimeException('Consulta vacía.'); }
        if (strpos($s, ';') !== false) { throw new \RuntimeException('Solo se permite una consulta.'); }
        if (! preg_match('/^\s*(select|with)\b/i', $s)) { throw new \RuntimeException('Solo se permiten consultas SELECT.'); }
        $low = strtolower($s);
        foreach (['insert', 'update', 'delete', 'drop', 'alter', 'create', 'truncate', 'replace', 'grant', 'revoke', 'attach', 'detach', 'vacuum', 'reindex', 'pragma', 'merge', ' into '] as $bad) {
            if (str_contains($low, $bad)) { throw new \RuntimeException('Operación no permitida en el analista (solo lectura).'); }
        }
        // Bloquea tablas/columnas sensibles (credenciales, secretos).
        foreach (['admins', 'connectors', 'rate_limits', 'password', 'password_hash', 'api_key', 'secret', 'private_key', 'client_secret', 'refresh_token', 'integrity_secret', 'events_secret', 'webhook_secret'] as $bad) {
            if (preg_match('/\b' . preg_quote($bad, '/') . '\b/i', $s)) { throw new \RuntimeException('Acceso restringido a datos sensibles.'); }
        }
        if (! preg_match('/\blimit\b/i', $s)) { $s .= ' LIMIT ' . self::MAX_FILAS; }
        return $s;
    }

    /** Regresión lineal simple (MCO) + correlación de Pearson sobre las filas. */
    private function ols(array $rows, string $x, string $y): array
    {
        $n = 0; $sx = 0.0; $sy = 0.0; $sxx = 0.0; $sxy = 0.0; $syy = 0.0;
        foreach ($rows as $r) {
            if (! isset($r[$x], $r[$y]) || ! is_numeric($r[$x]) || ! is_numeric($r[$y])) { continue; }
            $xi = (float) $r[$x]; $yi = (float) $r[$y];
            $n++; $sx += $xi; $sy += $yi; $sxx += $xi * $xi; $sxy += $xi * $yi; $syy += $yi * $yi;
        }
        if ($n < 3) { return ['n' => $n, 'error' => 'Datos numéricos insuficientes para regresión.']; }
        $den = $n * $sxx - $sx * $sx;
        if ($den == 0.0) { return ['n' => $n, 'error' => 'La variable X no tiene varianza.']; }
        $slope = ($n * $sxy - $sx * $sy) / $den;
        $intercept = ($sy - $slope * $sx) / $n;
        $rden = sqrt(($n * $sxx - $sx * $sx) * ($n * $syy - $sy * $sy));
        $r = $rden != 0.0 ? ($n * $sxy - $sx * $sy) / $rden : 0.0;
        return ['n' => $n, 'x' => $x, 'y' => $y, 'pendiente' => round($slope, 4), 'intercepto' => round($intercept, 4),
            'r' => round($r, 4), 'r2' => round($r * $r, 4)];
    }

    /** Esquema autodescubierto (tablas permitidas → columnas), portable. */
    private function esquemaTexto(): string
    {
        $pdo = Database::pdo();
        $sqlite = Database::isSqlite();
        $ocultas = ['password', 'password_hash', 'api_key', 'secret', 'token'];
        $out = '';
        foreach (self::TABLAS as $t) {
            try {
                $cols = [];
                if ($sqlite) {
                    foreach ($pdo->query("PRAGMA table_info({$t})")->fetchAll() as $c) {
                        if (! $this->sensible($c['name'], $ocultas)) { $cols[] = $c['name'] . ' ' . ($c['type'] ?: 'TEXT'); }
                    }
                } else {
                    foreach ($pdo->query("SHOW COLUMNS FROM {$t}")->fetchAll() as $c) {
                        if (! $this->sensible($c['Field'], $ocultas)) { $cols[] = $c['Field'] . ' ' . $c['Type']; }
                    }
                }
                if ($cols) { $out .= "- {$t}(" . implode(', ', $cols) . ")\n"; }
            } catch (\Throwable $e) { /* tabla ausente: se ignora */ }
        }
        return $out;
    }

    private function sensible(string $col, array $ocultas): bool
    {
        $c = strtolower($col);
        foreach ($ocultas as $o) { if (str_contains($c, $o)) { return true; } }
        return false;
    }

    private function extractJson(string $raw): string
    {
        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));
        $i = strpos($raw, '{'); $j = strrpos($raw, '}');
        return ($i !== false && $j !== false && $j > $i) ? substr($raw, $i, $j - $i + 1) : $raw;
    }
}
