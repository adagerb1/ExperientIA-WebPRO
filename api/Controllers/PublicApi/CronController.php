<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Env;
use Services\SequenceService;

/**
 * Runner de tareas programadas, protegido por clave. Pensado para un cron de
 * cPanel:  curl -s "https://tu-dominio/api/cron/run?key=TU_CRON_KEY"
 * La clave es CRON_KEY del .env (o, si no está, APP_SECRET).
 */
final class CronController extends Controller
{
    public function run(): void
    {
        $key = (string) $this->req->input('key', '');
        $expected = Env::get('CRON_KEY', '') ?: Env::get('APP_SECRET', '');
        if ($expected === '' || ! hash_equals($expected, $key)) {
            Response::error('No autorizado.', 401);
        }
        // Buzón comercial: leer bandeja + triage de AlexIA (silencioso si no está configurado).
        $buzon = null;
        try {
            if (\Services\Connectors\GmailConnector::isReady()) { $buzon = \Services\MailboxService::sync(); }
        } catch (\Throwable $e) { $buzon = ['ok' => false, 'error' => 'buzon: ' . $e->getMessage()]; }

        Response::ok(['secuencias' => SequenceService::processDue(300), 'buzon' => $buzon, 'ran_at' => now_utc()]);
    }
}
