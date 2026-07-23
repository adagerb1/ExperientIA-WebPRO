<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\Response;
use Core\Database;
use Services\AlexIA;
use Services\LeadService;
use Services\Connectors\ConnectorRegistry;
use Services\Connectors\TelegramConnector;
use Services\Connectors\WhatsAppConnector;

/** Webhooks entrantes de terceros: Telegram, WhatsApp, pasarelas de pago. */
final class WebhookController extends Controller
{
    /** Bot comercial (público, captura leads) o interno (solo admins vinculados). */
    public function telegram(string $bot): void
    {
        $secret = ConnectorRegistry::config('telegram')['webhook_secret'] ?? '';
        if ($secret && ($this->req->headers['x-telegram-bot-api-secret-token'] ?? '') !== $secret) {
            Response::error('No autorizado', 401);
        }
        $upd = $this->req->body;
        $msg = $upd['message'] ?? null;
        if (! $msg || empty($msg['text'])) { Response::ok(['ignored' => true]); }

        $chatId = (string) $msg['chat']['id'];
        $texto = $msg['text'];
        $nombre = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? '')) ?: 'Usuario Telegram';

        if ($bot === 'internal') {
            // Solo usuarios del portal admin vinculados por telegram_user_id
            $admin = Database::run('SELECT * FROM admins WHERE telegram_user_id = ? AND active = 1', [(string) $msg['from']['id']])->fetch();
            if (! $admin) {
                TelegramConnector::sendMessage('internal', $chatId, 'Este bot es exclusivo del equipo ExperientIA.' . "\n"
                    . 'Tu ID de Telegram es: <code>' . htmlspecialchars((string) $msg['from']['id']) . '</code>' . "\n"
                    . 'Regístralo en el portal admin (Plataforma → Usuarios) para vincular tu cuenta y recibir notificaciones.');
                Response::ok(['unauthorized' => true]);
            }
            $out = AlexIA::chat('interno', 'telegram', $texto, ['external_id' => $chatId, 'admin_id' => $admin['id'], 'locale' => 'es']);
        } else {
            // Bot comercial: captura el lead y responde como AlexIA comercial
            $lead = LeadService::capture(['name' => $nombre], 'telegram', 'Escribió por Telegram (comercial)', ['mensaje' => $texto], 'telegram');
            $out = AlexIA::chat('comercial', 'telegram', $texto, ['external_id' => $chatId, 'lead_id' => $lead['id'], 'locale' => 'es']);
        }
        TelegramConnector::sendMessage($bot, $chatId, $out['reply']);
        Response::ok(['sent' => true]);
    }

    public function whatsappVerify(): void
    {
        $cfg = ConnectorRegistry::config('whatsapp');
        $mode = $this->req->input('hub_mode') ?? ($_GET['hub.mode'] ?? '');
        $token = $this->req->input('hub_verify_token') ?? ($_GET['hub.verify_token'] ?? '');
        $challenge = $this->req->input('hub_challenge') ?? ($_GET['hub.challenge'] ?? '');
        if ($mode === 'subscribe' && $token === ($cfg['verify_token'] ?? '')) {
            header('Content-Type: text/plain');
            echo $challenge; exit;
        }
        Response::error('Verificación fallida', 403);
    }

    public function whatsapp(): void
    {
        $entry = $this->req->body['entry'][0]['changes'][0]['value'] ?? [];
        $msg = $entry['messages'][0] ?? null;
        if (! $msg) { Response::ok(['ignored' => true]); }
        $from = $msg['from'];
        $texto = $msg['text']['body'] ?? '';
        $nombre = $entry['contacts'][0]['profile']['name'] ?? 'Contacto WhatsApp';

        $lead = LeadService::capture(['name' => $nombre, 'phone_wa' => $from], 'whatsapp', 'Escribió por WhatsApp', ['mensaje' => $texto], 'whatsapp');
        $out = AlexIA::chat('comercial', 'whatsapp', $texto, ['external_id' => $from, 'lead_id' => $lead['id'], 'locale' => 'es']);
        WhatsAppConnector::sendText($from, $out['reply']);
        Response::ok(['sent' => true]);
    }

    /** Confirmación de pago (webhook de la pasarela). */
    public function pago(string $provider): void
    {
        $ref = $this->req->input('reference') ?? $this->req->input('client_reference_id') ?? '';
        $estado = $this->req->input('status', 'pendiente');
        $mapa = ['approved' => 'aprobado', 'APPROVED' => 'aprobado', 'paid' => 'aprobado', 'complete' => 'aprobado', 'declined' => 'rechazado', 'DECLINED' => 'rechazado'];
        $nuevo = $mapa[$estado] ?? 'pendiente';
        if ($ref) {
            Database::run('UPDATE payments SET status = ?, updated_at = ? WHERE reference = ? AND provider = ?', [$nuevo, now_utc(), $ref, $provider]);
        }
        Response::ok(['received' => true]);
    }
}
