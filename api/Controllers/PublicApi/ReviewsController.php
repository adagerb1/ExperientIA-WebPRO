<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Database;

/**
 * Reseñas de Google como prueba social pública: franja de estrellas (promedio +
 * total) y comentarios destacados. Solo expone reseñas sincronizadas; nunca datos
 * privados. Los "destacados" los cura el admin; si no hay, cae a mejores reseñas.
 */
final class ReviewsController extends Controller
{
    public function index(): void
    {
        try {
            // Franja de estrellas: promedio y total sobre todas las reseñas con calificación.
            $agg = Database::run('SELECT COUNT(*) n, AVG(stars) avg FROM gb_reviews WHERE stars > 0')->fetch();
            $total = (int) ($agg['n'] ?? 0);
            $promedio = $total ? round((float) $agg['avg'], 1) : 0;

            // Distribución por estrellas (para la franja).
            $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
            foreach (Database::run('SELECT stars, COUNT(*) c FROM gb_reviews WHERE stars > 0 GROUP BY stars')->fetchAll() as $r) {
                $dist[(int) $r['stars']] = (int) $r['c'];
            }

            // Comentarios destacados: primero los marcados; si faltan, mejores con texto.
            $destacados = Database::run(
                "SELECT author, stars, comment, reply, created_at FROM gb_reviews
                 WHERE featured = 1 AND comment IS NOT NULL AND comment <> '' ORDER BY created_at DESC LIMIT 12"
            )->fetchAll();
            if (! $destacados) {
                $destacados = Database::run(
                    "SELECT author, stars, comment, reply, created_at FROM gb_reviews
                     WHERE stars >= 4 AND comment IS NOT NULL AND comment <> '' ORDER BY stars DESC, created_at DESC LIMIT 6"
                )->fetchAll();
            }
        } catch (\Throwable $e) {
            $total = 0; $promedio = 0; $dist = []; $destacados = [];
        }

        Response::ok([
            'total' => $total,
            'promedio' => $promedio,
            'distribucion' => $dist,
            'destacados' => $destacados,
        ]);
    }
}
