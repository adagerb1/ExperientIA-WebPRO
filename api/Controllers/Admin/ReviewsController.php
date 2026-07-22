<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\Middleware\AuthMiddleware;
use Services\Settings;
use Services\AlexIA;
use Services\Connectors\GoogleBusinessConnector;

/**
 * Gestión de reseñas de Google: prueba social + flujo de respuestas de AlexIA.
 * Por defecto AlexIA SUGIERE y el admin aprueba (y al aprobar se publica en Google).
 * Con el auto-piloto activo, AlexIA genera y publica sola, documentado y visible.
 */
final class ReviewsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rows = Database::run(
            'SELECT id, review_id, name, author, stars, comment, reply, ai_reply, reply_status, reply_at, reply_by, featured, created_at
             FROM gb_reviews ORDER BY featured DESC, created_at DESC LIMIT 200'
        )->fetchAll();

        $total = count($rows);
        $suma = 0; $conEstrellas = 0; $pendientes = 0; $destacadas = 0;
        foreach ($rows as $r) {
            if ((int) $r['stars'] > 0) { $suma += (int) $r['stars']; $conEstrellas++; }
            if (empty($r['reply']) && ($r['reply_status'] ?? '') !== 'publicada') { $pendientes++; }
            if ((int) $r['featured'] === 1) { $destacadas++; }
        }
        Response::ok([
            'items' => $rows,
            'auto_reply' => Settings::bool('reviews_auto_reply', false),
            'resumen' => [
                'total' => $total,
                'promedio' => $conEstrellas ? round($suma / $conEstrellas, 1) : 0,
                'pendientes' => $pendientes,
                'destacadas' => $destacadas,
            ],
        ]);
    }

    /** AlexIA (re)genera un borrador de respuesta para una reseña. */
    public function sugerir(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $rev = Database::run('SELECT * FROM gb_reviews WHERE id = ?', [$id])->fetch();
        if (! $rev) { Response::error('Reseña no encontrada.', 404); }
        try {
            $draft = AlexIA::reviewReply($rev, $this->req->input('locale', 'es'));
        } catch (\Throwable $e) {
            Response::error('AlexIA no disponible: ' . $e->getMessage(), 503);
        }
        if ($draft === '') { Response::error('AlexIA no pudo redactar la respuesta.'); }
        Database::run('UPDATE gb_reviews SET ai_reply = ?, reply_status = CASE WHEN reply_status = \'publicada\' THEN \'publicada\' ELSE \'sugerida\' END WHERE id = ?', [$draft, $id]);
        Response::ok(['message' => 'ok', 'ai_reply' => $draft]);
    }

    /** El admin aprueba (opcionalmente editada) y se publica en Google Business. */
    public function aprobar(string $id): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        $rev = Database::run('SELECT * FROM gb_reviews WHERE id = ?', [$id])->fetch();
        if (! $rev) { Response::error('Reseña no encontrada.', 404); }
        $texto = trim((string) $this->req->input('reply', $rev['ai_reply'] ?? ''));
        if ($texto === '') { Response::error('Escribe o genera una respuesta antes de aprobar.'); }
        if (empty($rev['name'])) { Response::error('Esta reseña no tiene identificador de Google para responder.'); }

        $res = GoogleBusinessConnector::replyReview($rev['name'], $texto);
        if (empty($res['ok'])) { Response::error($res['error'] ?? 'No se pudo publicar en Google.', 502); }

        Database::run('UPDATE gb_reviews SET reply = ?, ai_reply = ?, reply_status = ?, reply_at = ?, reply_by = ? WHERE id = ?',
            [$texto, $texto, 'publicada', now_utc(), $admin['email'] ?? 'admin', $id]);
        Response::ok(['message' => 'Respuesta publicada en Google.']);
    }

    /** Marca/desmarca una reseña como destacada (comentario destacado del sitio). */
    public function destacar(string $id): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $val = $this->req->input('featured') ? 1 : 0;
        Database::run('UPDATE gb_reviews SET featured = ? WHERE id = ?', [$val, $id]);
        Response::ok(['message' => 'ok', 'featured' => $val]);
    }

    /** Activa/desactiva el auto-piloto de respuestas de AlexIA. */
    public function ajustes(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $auto = $this->req->input('auto_reply') ? '1' : '0';
        Settings::set('reviews_auto_reply', $auto);
        Response::ok(['message' => 'ok', 'auto_reply' => $auto === '1']);
    }
}
