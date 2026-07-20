<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Database;
use Core\Env;
use Core\RateLimiter;
use Core\Response;
use Core\Token;
use Services\Mailer;

/**
 * Mi GrowthBoard · el tablero vivo del cliente durante el acompañamiento.
 * Acceso sin contraseña: enlace firmado enviado al correo del lead (o compartido
 * por el consultor desde el CRM). El token usa la claim 'gbl' (nunca 'sub'),
 * por lo que jamás puede autenticar contra el panel de administración.
 */
final class ClientBoardController extends Controller
{
    private const TTL = 30 * 86400; // 30 días

    /** Solicita el enlace de acceso por correo. Respuesta genérica (sin enumerar). */
    public function acceso(): void
    {
        RateLimiter::hit('gbacceso:' . $this->req->ip(), 5, 300);
        $email = filter_var((string) $this->req->input('email', ''), FILTER_VALIDATE_EMAIL);
        $locale = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';

        if ($email) {
            try {
                $lead = Database::run('SELECT l.id, l.name FROM leads l WHERE l.email = ? AND EXISTS (SELECT 1 FROM gb_results r WHERE r.lead_id = l.id) LIMIT 1', [mb_strtolower($email)])->fetch();
                if ($lead) { self::enviarAcceso((int) $lead['id'], $lead['name'], $email, $locale); }
            } catch (\Throwable $e) { /* nunca revelar si el correo existe */ }
        }
        Response::ok(['message' => 'sent']);
    }

    /** El tablero del cliente: último resultado, evolución, jugadas y check-ins. */
    public function board(): void
    {
        RateLimiter::public($this->req);
        $leadId = self::leadId($this->req->input('t', ''));
        $lead = Database::run('SELECT id, name, company FROM leads WHERE id = ?', [$leadId])->fetch();
        if (! $lead) { Response::error('Acceso no válido.', 401); }

        $results = Database::run('SELECT total, banda, linea_debil, zona_critica, scores, created_at FROM gb_results WHERE lead_id = ? ORDER BY id DESC LIMIT 5', [$leadId])->fetchAll();
        foreach ($results as &$r) { $r['scores'] = json_decode($r['scores'], true); $r['total'] = (float) $r['total']; }
        unset($r);
        if (! $results) { Response::error('Aún no hay un diagnóstico asociado a este acceso.', 404); }

        $plays = Database::run('SELECT id, zona, titulo, porque, responsable, fecha_limite, indicador, estado, resultado, notas FROM gb_plays WHERE lead_id = ? ORDER BY sort, id', [$leadId])->fetchAll();
        $checkins = Database::run('SELECT id, avanzo, trabo, dato, decision, proxima, created_at FROM gb_checkins WHERE lead_id = ? ORDER BY id DESC LIMIT 12', [$leadId])->fetchAll();

        Response::ok([
            'lead' => ['name' => $lead['name'], 'company' => $lead['company']],
            'results' => $results, 'plays' => $plays, 'checkins' => $checkins,
        ]);
    }

    /** El cliente reporta el avance de una jugada (solo estado/resultado/notas). */
    public function estado(string $id): void
    {
        RateLimiter::public($this->req);
        $leadId = self::leadId($this->req->input('t', ''));
        $estado = (string) $this->req->input('estado', '');
        if (! in_array($estado, ['pendiente', 'ejecucion', 'ejecutada', 'descartada'], true)) { Response::error('Estado no válido.', 422); }
        $resultado = (string) $this->req->input('resultado', '');
        $resultado = in_array($resultado, ['movio', 'no_movio'], true) ? $resultado : null;
        $notas = mb_substr(trim(strip_tags((string) $this->req->input('notas', ''))), 0, 1000);

        Database::run('UPDATE gb_plays SET estado = ?, resultado = ?, notas = ?, updated_at = ? WHERE id = ? AND lead_id = ?',
            [$estado, $resultado, $notas ?: null, now_utc(), $id, $leadId]);
        Response::ok(['message' => 'ok']);
    }

    /** Marcador semanal: las 5 preguntas del ritual. */
    public function checkin(): void
    {
        RateLimiter::hit('gbcheckin:' . $this->req->ip(), 10, 3600);
        $leadId = self::leadId($this->req->input('t', ''));
        $campos = [];
        foreach (['avanzo', 'trabo', 'dato', 'decision', 'proxima'] as $c) {
            $campos[$c] = mb_substr(trim(strip_tags((string) $this->req->input($c, ''))), 0, 600) ?: null;
        }
        if (! array_filter($campos)) { Response::error('Escribe al menos una respuesta del marcador.', 422); }
        Database::run('INSERT INTO gb_checkins (lead_id, avanzo, trabo, dato, decision, proxima, created_at) VALUES (?,?,?,?,?,?,?)',
            [$leadId, $campos['avanzo'], $campos['trabo'], $campos['dato'], $campos['decision'], $campos['proxima'], now_utc()]);
        Response::ok(['message' => 'ok']);
    }

    // ── Helpers compartidos con el admin ─────────────────────────────────────
    /** Envía el correo de acceso (plantilla editable gb_acceso, con respaldo). */
    public static function enviarAcceso(int $leadId, string $nombre, string $email, string $locale = 'es'): bool
    {
        $url = self::urlAcceso($leadId, $locale);
        $tpl = Mailer::template('gb_acceso', $locale);
        if (! $tpl) {
            $tx = [
                'es' => ['Tu acceso a Mi GrowthBoard', '<p>Hola, {{nombre}}.</p><p>Este es tu acceso personal a tu tablero de crecimiento en vivo: tu cancha, tus jugadas y tu marcador semanal.</p><p>El enlace es personal y vence en 30 días.</p>'],
                'en' => ['Your access to My GrowthBoard', '<p>Hi, {{nombre}}.</p><p>This is your personal access to your live growth board: your field, your plays and your weekly scoreboard.</p><p>The link is personal and expires in 30 days.</p>'],
                'pt' => ['Seu acesso ao Meu GrowthBoard', '<p>Olá, {{nombre}}.</p><p>Este é seu acesso pessoal ao seu painel de crescimento ao vivo: seu campo, suas jogadas e seu placar semanal.</p><p>O link é pessoal e expira em 30 dias.</p>'],
            ][$locale] ?? null;
            $tpl = $tx ?: ['Tu acceso a Mi GrowthBoard', '<p>Hola, {{nombre}}.</p>'];
        }
        $n = explode(' ', trim($nombre))[0];
        $cuerpo = str_replace(['{{nombre}}', '{{enlace}}', '{nombre}', '{enlace}'], [$n, $url, $n, $url], $tpl[1]);
        if (! str_contains($cuerpo, 'display:inline-block')) {
            $cuerpo .= Mailer::boton($url, ['es' => 'Entrar a Mi GrowthBoard', 'en' => 'Open My GrowthBoard', 'pt' => 'Entrar no Meu GrowthBoard'][$locale] ?? 'Entrar a Mi GrowthBoard');
        }
        return Mailer::send($email, $tpl[0], $cuerpo);
    }

    public static function urlAcceso(int $leadId, string $locale = 'es'): string
    {
        $token = Token::issue(['gbl' => $leadId], self::TTL);
        $slug = ['es' => 'mi-tablero', 'en' => 'my-board', 'pt' => 'meu-painel'][$locale] ?? 'mi-tablero';
        return rtrim(Env::get('APP_URL', 'https://experientia.pro'), '/') . "/{$locale}/{$slug}?t=" . rawurlencode($token);
    }

    /** Valida el token del cliente. La claim es 'gbl': nunca autentica como admin. */
    private static function leadId(?string $t): int
    {
        $p = Token::verify((string) $t);
        $id = (int) ($p['gbl'] ?? 0);
        if (! $p || $id <= 0 || isset($p['sub'])) { Response::error('Tu acceso expiró o no es válido. Solicita un nuevo enlace.', 401); }
        return $id;
    }
}
