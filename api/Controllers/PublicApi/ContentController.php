<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Database;
use Core\RateLimiter;
use Core\Response;

/** Entrega el contenido del sitio (soluciones, productos, casos, faqs, recursos). */
final class ContentController extends Controller
{
    public function show(string $seccion): void
    {
        RateLimiter::public($this->req);
        $tablas = ['soluciones' => 'solutions', 'productos' => 'products', 'casos' => 'case_studies', 'faqs' => 'faqs', 'recursos' => 'resources'];
        if ($seccion === 'all') {
            $data = [];
            foreach ($tablas as $k => $t) { $data[$k] = $this->fetch($t); }
            Response::ok($data);
        }
        if (! isset($tablas[$seccion])) {
            Response::error('Sección no encontrada.', 404);
        }
        Response::ok($this->fetch($tablas[$seccion]));
    }

    private function fetch(string $tabla): array
    {
        if ($tabla === 'resources') {
            $st = Database::run("SELECT id, slug, type, categories, tipo_label, titulo, extracto, cuerpo, author, read_minutes, cover_image, audio_path, video_url, file_path, downloads FROM resources WHERE active = 1 AND published_at IS NOT NULL AND published_at <= ? ORDER BY COALESCE(featured,0) DESC, sort", [now_utc()]);
        } else {
            $st = Database::run("SELECT * FROM {$tabla} WHERE active = 1 ORDER BY sort");
        }
        return array_map([$this, 'decode'], $st->fetchAll());
    }

    private function decode(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_string($v) && $v !== '' && ($v[0] === '{' || $v[0] === '[')) {
                $d = json_decode($v, true);
                if ($d !== null) { $row[$k] = $d; }
            }
        }
        return $row;
    }
}
