<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Database;
use Core\RateLimiter;

/**
 * Testimonios de clientes.
 *   GET  /testimonios         → publicados (prueba social del sitio)
 *   GET  /testimonio/{code}   → datos de la invitación + catálogo de soluciones/productos
 *   POST /testimonio/{code}   → el cliente envía su testimonio (multipart: foto/logo opcionales)
 * El enlace lleva un código corto único; nada se publica sin aprobación del admin.
 */
final class TestimonialsController extends Controller
{
    public function index(): void
    {
        try {
            $rows = Database::run(
                "SELECT author, cargo, empresa, quote, photo, logo, items FROM testimonials
                 WHERE status = 'publicado' ORDER BY featured DESC, sort, published_at DESC LIMIT 24"
            )->fetchAll();
            foreach ($rows as &$r) { $r['items'] = $r['items'] ? (json_decode($r['items'], true) ?: []) : []; }
            unset($r);
        } catch (\Throwable $e) { $rows = []; }
        Response::ok($rows);
    }

    public function invitacion(string $code): void
    {
        RateLimiter::public($this->req);
        $t = $this->porCodigo($code);
        Response::ok([
            'client_name' => $t['client_name'],
            'status' => $t['status'],
            'ya_enviado' => $t['status'] !== 'invitado',
            'catalogo' => $this->catalogo(),
        ]);
    }

    public function enviar(string $code): void
    {
        RateLimiter::public($this->req);
        $t = $this->porCodigo($code);
        if ($t['status'] === 'publicado') { Response::error('Este testimonio ya fue publicado. ¡Gracias!', 409); }

        $quote = trim((string) $this->req->input('quote', ''));
        $author = trim((string) $this->req->input('author', ''));
        if (mb_strlen($quote) < 20) { Response::error('Cuéntanos tu experiencia en al menos una frase (mínimo 20 caracteres).', 422); }
        if ($author === '') { Response::error('Tu nombre es necesario para dar credibilidad al testimonio.', 422); }
        $cargo = trim((string) $this->req->input('cargo', ''));
        $empresa = trim((string) $this->req->input('empresa', ''));
        $locale = in_array($this->req->input('locale'), ['es', 'en', 'pt'], true) ? $this->req->input('locale') : 'es';

        $items = $this->req->input('items', []);
        if (is_string($items)) { $items = json_decode($items, true) ?: []; }
        $items = array_values(array_filter(array_map('strval', (array) $items), fn ($x) => preg_match('/^(sol|prod):[a-z0-9\-]+$/i', $x)));

        $photo = $this->guardarImagen('photo');
        $logo = $this->guardarImagen('logo');

        Database::run(
            'UPDATE testimonials SET status = ?, author = ?, cargo = ?, empresa = ?, quote = ?, locale = ?, items = ?,
             photo = COALESCE(?, photo), logo = COALESCE(?, logo), submitted_at = ? WHERE id = ?',
            ['recibido', $author, $cargo, $empresa, mb_substr($quote, 0, 2000), $locale,
             json_encode($items, JSON_UNESCAPED_UNICODE), $photo, $logo, now_utc(), $t['id']]
        );

        try {
            \Services\Notifier::telegram('💬 <b>Testimonio recibido</b> de ' . htmlspecialchars($author)
                . ($empresa !== '' ? ' · ' . htmlspecialchars($empresa) : '') . ". Revísalo y publícalo en el panel.");
        } catch (\Throwable $e) { /* notificación opcional */ }

        Response::ok(['message' => 'ok']);
    }

    private function porCodigo(string $code): array
    {
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $code);
        $t = Database::run('SELECT * FROM testimonials WHERE code = ?', [$code])->fetch();
        if (! $t) { Response::error('Enlace no válido o vencido. Pide uno nuevo a tu contacto en ExperientIA.', 404); }
        return $t;
    }

    /** Soluciones y productos activos para el multi-select del formulario. */
    private function catalogo(): array
    {
        $out = [];
        try {
            foreach (Database::run('SELECT skey, titulo FROM solutions WHERE active = 1 ORDER BY sort')->fetchAll() as $s) {
                $out[] = ['token' => 'sol:' . $s['skey'], 'nombre' => json_decode($s['titulo'], true) ?: $s['titulo'], 'tipo' => 'solucion'];
            }
            foreach (Database::run('SELECT id, nombre FROM products WHERE active = 1 ORDER BY sort')->fetchAll() as $p) {
                $out[] = ['token' => 'prod:' . $p['id'], 'nombre' => json_decode($p['nombre'], true) ?: $p['nombre'], 'tipo' => 'producto'];
            }
        } catch (\Throwable $e) { /* catálogo vacío */ }
        return $out;
    }

    /** Guarda una imagen subida (JPG/PNG/WebP ≤ 5 MB). Devuelve la URL pública o null. */
    private function guardarImagen(string $campo): ?string
    {
        $f = $_FILES[$campo] ?? null;
        if (! $f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return null; }
        if ($f['size'] > 5 * 1024 * 1024) { Response::error('La imagen supera 5 MB.', 422); }
        $mime = @mime_content_type($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (! $ext) { Response::error('Solo imágenes JPG, PNG o WebP.', 422); }
        $dir = BASE_PATH . '/assets/img/testimonios';
        if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
        $name = $campo . '-' . gmdate('Ymd') . '-' . substr(bin2hex(random_bytes(5)), 0, 10) . '.' . $ext;
        if (! move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) { Response::error('No se pudo guardar la imagen.', 500); }
        return '/assets/img/testimonios/' . $name;
    }
}
