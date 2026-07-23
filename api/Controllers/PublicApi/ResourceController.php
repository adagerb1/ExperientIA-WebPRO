<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Core\Database;
use Core\Env;
use Services\LeadService;
use Services\Mailer;

final class ResourceController extends Controller
{
    public function descargar(): void
    {
        RateLimiter::public($this->req);
        $slug = (string) $this->req->input('slug', '');
        $rec = Database::run("SELECT * FROM resources WHERE slug = ? AND type = 'download' AND active = 1", [$slug])->fetch();
        if (! $rec) { Response::error('Recurso no encontrado.', 404); }

        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5)
            ->country('country', false)->text('company', false, 160)
            ->in('industry', \Core\Taxonomy::industryKeys())->in('company_size', array_keys(biz('company_sizes')));
        $d = $v->failOrValidated();
        $d['locale'] = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        $d = array_merge($d, \Core\Attribution::fromRequest($this->req));

        LeadService::capture($d, 'descarga', 'Descargó: ' . tr($rec['titulo'], 'es'), ['recurso' => $rec['slug']]);
        Database::run('UPDATE resources SET downloads = downloads + 1 WHERE id = ?', [$rec['id']]);

        $exp = time() + 7 * 86400;
        $sig = hash_hmac('sha256', "{$rec['slug']}|{$exp}", Env::get('APP_SECRET', ''));
        $url = Env::get('APP_URL') . '/api/descarga-archivo?slug=' . rawurlencode($rec['slug']) . "&exp={$exp}&sig={$sig}";

        Mailer::send($d['email'], '[ExperientIA] ' . tr($rec['titulo'], $d['locale']),
            '<h2>Su recurso está listo</h2><p><b>' . htmlspecialchars(tr($rec['titulo'], $d['locale'])) . '</b></p><p><a href="' . htmlspecialchars($url) . '">Descargar</a> (enlace válido 7 días)</p>');

        Response::ok(['url' => $url]);
    }

    public function archivo(): void
    {
        $slug = (string) $this->req->input('slug', '');
        $exp = (int) $this->req->input('exp', 0);
        $sig = (string) $this->req->input('sig', '');
        $valid = $exp > time() && hash_equals(hash_hmac('sha256', "{$slug}|{$exp}", Env::get('APP_SECRET', '')), $sig);
        if (! $valid) { Response::error('Enlace vencido o inválido.', 403); }

        $rec = Database::run("SELECT * FROM resources WHERE slug = ? AND type = 'download'", [$slug])->fetch();
        $file = BASE_PATH . '/storage/recursos/' . basename($rec['file_path'] ?? '');
        if (! $rec || ! is_file($file)) { Response::error('Archivo no encontrado.', 404); }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9\-]+/', '-', mb_strtolower(tr($rec['titulo'], 'es'))) . '.pdf"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
