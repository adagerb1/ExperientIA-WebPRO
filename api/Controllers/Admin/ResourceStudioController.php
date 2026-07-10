<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Response;
use Core\Env;
use Core\RateLimiter;
use Core\Middleware\AuthMiddleware;
use Services\AlexIA;
use Services\Connectors\OpenAIConnector;

/** Estudio de recursos con IA: contenido trilingüe, portada y audio. */
final class ResourceStudioController extends Controller
{
    /** Redacta el artículo (ES/EN/PT) + extracto + SEO a partir del brief. */
    public function generar(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);

        $titulo = trim((string) $this->req->input('titulo', ''));
        $cats = (array) $this->req->input('categorias', []);
        $idea = trim((string) $this->req->input('resumen', ''));
        if ($titulo === '' && $idea === '') { Response::error('Indica al menos un título o una idea.', 422); }

        $instr = 'Eres redactor senior de contenidos de ExperientIA SAS (automatización, growth e inteligencia artificial aplicada a negocios). '
            . 'Escribe un artículo de blog profesional, útil y accionable para líderes empresariales. '
            . 'Devuelve ÚNICAMENTE JSON válido (sin markdown ni texto extra) con esta forma exacta: '
            . '{"extracto":{"es":"","en":"","pt":""},"cuerpo":{"es":"","en":"","pt":""},"seo_title":{"es":"","en":"","pt":""},"seo_desc":{"es":"","en":"","pt":""}}. '
            . 'El "cuerpo" es HTML simple (usa <h2>, <p>, <ul><li>, <strong>), 500-800 palabras, sin <h1>. '
            . 'El "extracto" es 1-2 frases. "seo_title" máx 60 caracteres; "seo_desc" máx 155. '
            . 'Redacta genuinamente en español (es), inglés (en) y portugués (pt), no traduzcas literal.';
        $input = "Título: {$titulo}\nCategorías: " . implode(', ', $cats) . "\nIdea/resumen: {$idea}";

        try {
            $raw = AlexIA::ask($instr, $input);
            $data = json_decode($this->extractJson($raw), true);
            if (! is_array($data) || empty($data['cuerpo'])) {
                Response::error('La IA no devolvió contenido válido. Intenta de nuevo.', 502);
            }
            Response::ok([
                'extracto' => $this->tri($data['extracto'] ?? []),
                'cuerpo' => $this->tri($data['cuerpo'] ?? []),
                'seo_title' => $this->tri($data['seo_title'] ?? []),
                'seo_desc' => $this->tri($data['seo_desc'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    /** Genera la imagen de portada (gpt-image-1) y la guarda optimizada. */
    public function portada(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        if (! OpenAIConnector::isReady()) { Response::error('Configura y activa OpenAI en Conectores.', 503); }

        $partes = array_filter([
            trim((string) $this->req->input('instrucciones', '')),
            'Portada editorial para un artículo titulado: "' . trim((string) $this->req->input('titulo', '')) . '".',
            'Estilo: ' . $this->req->input('estilo', 'cinematográfico premium'),
            'Iluminación: ' . $this->req->input('iluminacion', 'natural cálida'),
            'Ambiente: ' . $this->req->input('ambiente', 'inspirador, ejecutivo'),
            'Marca oscura y tecnológica (azul midnight, cian, violeta), sin texto ni logos, alta calidad.',
        ]);
        $prompt = implode(' ', $partes);

        try {
            $img = OpenAIConnector::image($prompt, '1536x1024');
            $bytes = ! empty($img['b64_json']) ? base64_decode($img['b64_json']) : (! empty($img['url']) ? @file_get_contents($img['url']) : null);
            if (! $bytes) { Response::error('La IA no devolvió imagen.', 502); }

            $dir = BASE_PATH . '/assets/img/covers';
            if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
            $base = 'cover-' . gmdate('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
            $file = $this->optimizarPortada($bytes, $dir, $base);

            Response::ok(['cover_image' => '/assets/img/covers/' . $file]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    /** Sube una imagen de portada manualmente (JPG/PNG/WebP) y la optimiza. */
    public function subirImagen(): void
    {
        AuthMiddleware::require($this->req, 'admin');
        $f = $_FILES['archivo'] ?? null;
        if (! $f || $f['error'] !== UPLOAD_ERR_OK) { Response::error('No se recibió la imagen.'); }
        if ($f['size'] > 10 * 1024 * 1024) { Response::error('La imagen supera 10 MB.'); }
        $mime = mime_content_type($f['tmp_name']);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) { Response::error('Solo JPG, PNG o WebP.'); }

        $bytes = file_get_contents($f['tmp_name']);
        $dir = BASE_PATH . '/assets/img/covers';
        if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
        $base = 'cover-' . gmdate('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        $file = $this->optimizarPortada($bytes, $dir, $base);
        Response::ok(['cover_image' => '/assets/img/covers/' . $file]);
    }

    /** Genera el audio (narración TTS) del artículo. */
    public function audio(): void
    {
        $admin = AuthMiddleware::require($this->req, 'admin');
        RateLimiter::user((int) $admin['id'], $this->req);
        if (! OpenAIConnector::isReady()) { Response::error('Configura y activa OpenAI en Conectores.', 503); }

        $texto = trim(strip_tags((string) $this->req->input('texto', '')));
        if ($texto === '') { Response::error('No hay texto para narrar.', 422); }
        $texto = mb_substr($texto, 0, 4000);

        try {
            $mp3 = OpenAIConnector::speak($texto);
            if ($mp3 === '') { Response::error('No se pudo generar el audio.', 502); }
            $dir = BASE_PATH . '/assets/media';
            if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
            $file = 'audio-' . gmdate('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.mp3';
            file_put_contents($dir . '/' . $file, $mp3);
            Response::ok(['audio_path' => '/assets/media/' . $file]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 503);
        }
    }

    /** Recorta/optimiza a 1200x630 (JPG) si hay GD; si no, guarda el original. */
    private function optimizarPortada(string $bytes, string $dir, string $base): string
    {
        if (function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring($bytes);
            if ($src) {
                [$sw, $sh] = [imagesx($src), imagesy($src)];
                [$tw, $th] = [1200, 630];
                $scale = max($tw / $sw, $th / $sh);
                $nw = (int) ($sw * $scale);
                $nh = (int) ($sh * $scale);
                $dst = imagecreatetruecolor($tw, $th);
                imagecopyresampled($dst, $src, (int) (($tw - $nw) / 2), (int) (($th - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);
                imagejpeg($dst, $dir . '/' . $base . '.jpg', 82);
                imagedestroy($src);
                imagedestroy($dst);
                return $base . '.jpg';
            }
        }
        file_put_contents($dir . '/' . $base . '.png', $bytes);
        return $base . '.png';
    }

    /** Normaliza un valor i18n a {es,en,pt}. */
    private function tri($v): array
    {
        $v = is_array($v) ? $v : ['es' => (string) $v];
        return ['es' => $v['es'] ?? '', 'en' => $v['en'] ?? ($v['es'] ?? ''), 'pt' => $v['pt'] ?? ($v['es'] ?? '')];
    }

    private function extractJson(string $s): string
    {
        $a = strpos($s, '{');
        $b = strrpos($s, '}');
        return ($a !== false && $b !== false && $b > $a) ? substr($s, $a, $b - $a + 1) : $s;
    }
}
