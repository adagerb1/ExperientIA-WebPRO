<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Env;
use Core\Middleware\AuthMiddleware;
use Services\Mailer;

/**
 * Gestión de testimonios: crear invitación con código corto, enviarla por
 * email (directo) o compartirla por WhatsApp/Telegram (enlaces prellenados),
 * revisar lo recibido y publicar/destacar. Nada sale al sitio sin publicar.
 */
final class TestimonialsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = Database::run('SELECT * FROM testimonials ORDER BY featured DESC, created_at DESC LIMIT 300')->fetchAll();
        $base = rtrim(Env::get('APP_URL', ''), '/');
        foreach ($rows as &$r) {
            $r['items'] = $r['items'] ? (json_decode($r['items'], true) ?: []) : [];
            $r['url'] = $base . '/es/testimonio/' . $r['code'];
        }
        unset($r);
        $tot = count($rows);
        $pub = count(array_filter($rows, fn ($r) => $r['status'] === 'publicado'));
        $rec = count(array_filter($rows, fn ($r) => $r['status'] === 'recibido'));
        Response::ok(['items' => $rows, 'resumen' => ['total' => $tot, 'publicados' => $pub, 'por_revisar' => $rec]]);
    }

    /** Crea la invitación (código corto único) para un cliente. */
    public function store(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $nombre = trim((string) $this->req->input('client_name', ''));
        $email = trim((string) $this->req->input('client_email', ''));
        if ($nombre === '') { Response::error('Escribe el nombre del cliente que vas a invitar.', 422); }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) { Response::error('El correo del cliente no es válido.', 422); }

        // Código corto legible (sin caracteres ambiguos), único.
        $abc = 'abcdefghjkmnpqrstuvwxyz23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) { $code .= $abc[random_int(0, strlen($abc) - 1)]; }
            $dup = Database::run('SELECT COUNT(*) FROM testimonials WHERE code = ?', [$code])->fetchColumn();
        } while ((int) $dup > 0);

        Database::run(
            'INSERT INTO testimonials (code, status, client_name, client_email, locale, created_at) VALUES (?,?,?,?,?,?)',
            [$code, 'invitado', $nombre, $email ?: null, in_array($this->req->input('locale'), ['es', 'en', 'pt'], true) ? $this->req->input('locale') : 'es', now_utc()]
        );
        Response::ok(['message' => 'ok', 'code' => $code]);
    }

    /** Envía la invitación por email, o entrega los enlaces para WhatsApp/Telegram. */
    public function enviar(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $t = $this->porId($id);
        $via = (string) $this->req->input('via', 'email');
        $base = rtrim(Env::get('APP_URL', ''), '/');
        $loc = $t['locale'] ?: 'es';
        $slug = ['es' => 'testimonio', 'en' => 'testimonial', 'pt' => 'depoimento'][$loc] ?? 'testimonio';
        $url = $base . '/' . $loc . '/' . $slug . '/' . $t['code'];

        $textos = [
            'es' => "Hola {$t['client_name']}, para nosotros en ExperientIA tu opinión vale oro. ¿Nos regalas un testimonio? Es un formulario de 2 minutos: {$url}",
            'en' => "Hi {$t['client_name']}, your opinion is gold to us at ExperientIA. Would you share a testimonial? It's a 2-minute form: {$url}",
            'pt' => "Olá {$t['client_name']}, sua opinião vale ouro para nós na ExperientIA. Você nos daria um depoimento? É um formulário de 2 minutos: {$url}",
        ];
        $texto = $textos[$loc] ?? $textos['es'];

        if ($via === 'whatsapp') {
            $marca = now_utc();
            Database::run('UPDATE testimonials SET sent_via = ?, invited_at = ? WHERE id = ?', ['whatsapp', $marca, $t['id']]);
            Response::ok(['message' => 'ok', 'share_url' => 'https://wa.me/?text=' . rawurlencode($texto)]);
        }
        if ($via === 'telegram') {
            $marca = now_utc();
            Database::run('UPDATE testimonials SET sent_via = ?, invited_at = ? WHERE id = ?', ['telegram', $marca, $t['id']]);
            Response::ok(['message' => 'ok', 'share_url' => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($texto)]);
        }

        // Email directo (SendGrid o servidor), con plantilla editable si existe.
        if (empty($t['client_email'])) { Response::error('Esta invitación no tiene correo del cliente. Edítala o compártela por WhatsApp/Telegram.', 422); }
        $tpl = Mailer::template('testimonial_invite', $loc);
        if ($tpl) {
            [$subject, $body] = $tpl;
            $subject = strtr($subject, ['{{nombre}}' => $t['client_name']]);
            $body = strtr($body, ['{{nombre}}' => $t['client_name'], '{{enlace}}' => $url]);
        } else {
            $subject = ['es' => 'Tu experiencia con ExperientIA vale oro', 'en' => 'Your experience with ExperientIA is gold', 'pt' => 'Sua experiência com a ExperientIA vale ouro'][$loc] ?? 'Tu experiencia con ExperientIA';
            $body = '<p>Hola, ' . htmlspecialchars($t['client_name']) . '.</p><p>Para nosotros tu opinión vale oro. ¿Nos regalas un testimonio de tu experiencia con ExperientIA? Es un formulario de 2 minutos, y si quieres puedes incluir tu foto y el logo de tu empresa.</p>';
        }
        $body .= Mailer::boton($url, ['es' => 'Dejar mi testimonio', 'en' => 'Leave my testimonial', 'pt' => 'Deixar meu depoimento'][$loc] ?? 'Dejar mi testimonio');
        $ok = Mailer::send($t['client_email'], $subject, $body);
        if (! $ok) { Response::error('No se pudo enviar el correo. Revisa el conector SendGrid o comparte el enlace por WhatsApp/Telegram.', 502); }
        Database::run('UPDATE testimonials SET sent_via = ?, invited_at = ? WHERE id = ?', ['email', now_utc(), $t['id']]);
        Response::ok(['message' => 'Invitación enviada a ' . $t['client_email']]);
    }

    /** Edita el contenido y controla publicación/destacado. */
    public function update(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $t = $this->porId($id);
        $campos = ['client_name', 'client_email', 'author', 'cargo', 'empresa', 'quote'];
        $sets = []; $vals = [];
        foreach ($campos as $c) {
            $v = $this->req->input($c, null);
            if ($v !== null) { $sets[] = "$c = ?"; $vals[] = trim((string) $v); }
        }
        $items = $this->req->input('items', null);
        if ($items !== null) { $sets[] = 'items = ?'; $vals[] = json_encode(array_values((array) $items), JSON_UNESCAPED_UNICODE); }
        $featured = $this->req->input('featured', null);
        if ($featured !== null) { $sets[] = 'featured = ?'; $vals[] = $featured ? 1 : 0; }

        $status = $this->req->input('status', null);
        if ($status !== null) {
            if (! in_array($status, ['invitado', 'recibido', 'publicado'], true)) { Response::error('Estado no válido.', 422); }
            if ($status === 'publicado' && trim((string) ($this->req->input('quote', $t['quote'] ?? ''))) === '') {
                Response::error('No se puede publicar un testimonio sin texto.', 422);
            }
            $sets[] = 'status = ?'; $vals[] = $status;
            if ($status === 'publicado') { $sets[] = 'published_at = ?'; $vals[] = now_utc(); }
        }
        if (! $sets) { Response::ok(['message' => 'sin cambios']); }
        $vals[] = $t['id'];
        Database::run('UPDATE testimonials SET ' . implode(', ', $sets) . ' WHERE id = ?', $vals);
        Response::ok(['message' => 'ok']);
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $this->porId($id);
        Database::run('DELETE FROM testimonials WHERE id = ?', [$id]);
        Response::ok(['message' => 'ok']);
    }

    private function porId(string $id): array
    {
        $t = Database::run('SELECT * FROM testimonials WHERE id = ?', [$id])->fetch();
        if (! $t) { Response::error('Testimonio no encontrado.', 404); }
        return $t;
    }
}
